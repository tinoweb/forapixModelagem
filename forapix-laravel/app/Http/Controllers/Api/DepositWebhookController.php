<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ResendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositWebhookController extends Controller
{
    public function __construct(private ResendService $resend) {}

    /**
     * Recebe notificação de depósito confirmado da VeoPag.
     * POST /api/webhooks/deposit
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('VeoPag webhook recebido', $payload);

        $type   = $payload['type']   ?? null;
        $status = $payload['status'] ?? null;

        if ($type !== 'Deposit' || $status !== 'COMPLETED') {
            return response()->json(['message' => 'ignored'], 200);
        }

        $externalId    = $payload['external_id']    ?? $payload['externalId']    ?? null;
        $transactionId = $payload['transaction_id'] ?? $payload['transactionId'] ?? null;

        if (!$externalId) {
            Log::warning('VeoPag webhook: external_id ausente', $payload);
            return response()->json(['message' => 'external_id missing'], 400);
        }

        // Busca a transação pelo external_id guardado em metadata
        $transaction = Transaction::where(function ($q) use ($externalId, $transactionId) {
            $q->where('external_transaction_id', $externalId)
              ->orWhere('external_transaction_id', $transactionId);
        })
        ->where('type', 'deposit')
        ->where('status', 'pending')
        ->first();

        if (!$transaction) {
            Log::warning('VeoPag webhook: transação não encontrada ou já processada', compact('externalId'));
            return response()->json(['message' => 'transaction not found or already processed'], 200);
        }

        try {
            DB::beginTransaction();

            $user          = User::findOrFail($transaction->user_id);
            $balanceBefore = $user->balance;
            $amount        = $transaction->amount;

            $user->increment('balance', $amount);
            $user->increment('total_deposited', $amount);

            $transaction->update([
                'status'         => 'completed',
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore + $amount,
                'processed_at'   => now(),
                'metadata'       => array_merge($transaction->metadata ?? [], [
                    'veopag_transaction_id' => $transactionId,
                    'confirmed_at'          => now()->toIso8601String(),
                    'webhook_payload'       => $payload,
                ]),
            ]);

            DB::commit();

            Log::info("VeoPag: depósito confirmado para user {$user->id}", [
                'amount'      => $amount,
                'external_id' => $externalId,
            ]);

            $this->sendEmails($user, $transaction, $amount);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('VeoPag webhook: erro ao processar depósito', [
                'error'      => $e->getMessage(),
                'externalId' => $externalId,
            ]);
            return response()->json(['message' => 'processing error'], 500);
        }

        return response()->json(['message' => 'ok'], 200);
    }

    private function sendEmails(User $user, Transaction $transaction, float $amount): void
    {
        $formattedAmount = 'R$ ' . number_format($amount, 2, ',', '.');
        $transactionId   = $transaction->transaction_id;
        $date            = now()->format('d/m/Y H:i');

        // E-mail para o usuário
        $userHtml = view('emails.deposit-confirmed', compact('user', 'amount', 'formattedAmount', 'transactionId', 'date'))->render();
        $this->resend->send(
            $user->email,
            $user->name,
            "✅ Depósito de {$formattedAmount} confirmado — ForaPix",
            $userHtml
        );

        // E-mail para o admin
        $adminEmail = config('services.admin.email', env('ADMIN_EMAIL', 'admin@apostacasada.net'));
        $adminHtml  = view('emails.admin-deposit-notification', compact('user', 'amount', 'formattedAmount', 'transactionId', 'date'))->render();
        $this->resend->send(
            $adminEmail,
            'Admin ForaPix',
            "💰 Novo depósito: {$formattedAmount} — {$user->name}",
            $adminHtml
        );
    }
}
