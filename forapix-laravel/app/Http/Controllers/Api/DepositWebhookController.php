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

        // Validar X-Webhook-Signature (se configurado no .env)
        $secret = config('services.veopag.webhook_signature', '');
        if (!empty($secret)) {
            $received = $request->header('X-Webhook-Signature', '');
            if (!hash_equals($secret, $received)) {
                Log::warning('VeoPag webhook: assinatura inválida', ['ip' => $request->ip()]);
                return response()->json(['message' => 'unauthorized'], 401);
            }
        }

        Log::info('VeoPag webhook recebido', $payload);

        $type   = strtolower($payload['type']   ?? $payload['event'] ?? '');
        $status = strtoupper($payload['status'] ?? $payload['state']  ?? '');

        // Aceita: type deposit/pix/payment + status completed/paid/approved
        $isDeposit  = in_array($type,   ['deposit', 'pix', 'payment', 'credit', '']);
        $isComplete = in_array($status, ['COMPLETED', 'PAID', 'APPROVED', 'CONFIRMED', 'SUCCESS']);

        if (!$isComplete) {
            Log::info("VeoPag webhook depósito ignorado (status={$status}, type={$type})", ['payload' => $payload]);
            return response()->json(['message' => 'ignored'], 200);
        }

        $externalId    = $payload['external_id']    ?? $payload['externalId']    ?? null;
        $transactionId = $payload['transaction_id'] ?? $payload['transactionId'] ?? null;

        if (!$externalId && !$transactionId) {
            Log::warning('VeoPag webhook depósito: nenhum ID identificável no payload', $payload);
            return response()->json(['message' => 'no identifier found'], 400);
        }

        // Busca por qualquer um dos identificadores possíveis
        $transaction = Transaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->where(function ($q) use ($externalId, $transactionId) {
                if ($externalId) {
                    $q->orWhere('external_transaction_id', $externalId)
                      ->orWhere('payment_reference', $externalId);
                }
                if ($transactionId) {
                    $q->orWhere('external_transaction_id', $transactionId)
                      ->orWhere('payment_reference', $transactionId);
                }
            })
            ->first();

        // Fallback: buscar em metadata->veopag_transaction_id
        if (!$transaction && $transactionId) {
            $transaction = Transaction::where('type', 'deposit')
                ->where('status', 'pending')
                ->whereJsonContains('metadata->veopag_transaction_id', $transactionId)
                ->first();
        }

        if (!$transaction) {
            Log::warning('VeoPag webhook depósito: transação não encontrada ou já processada', [
                'external_id'    => $externalId,
                'transaction_id' => $transactionId,
            ]);
            return response()->json(['message' => 'transaction not found or already processed'], 200);
        }

        try {
            DB::beginTransaction();

            // Lock the user for update to prevent concurrent balance updates
            $user = User::where('id', $transaction->user_id)->lockForUpdate()->firstOrFail();

            // Atomically update transaction from pending to completed
            $updated = Transaction::where('id', $transaction->id)
                ->where('status', 'pending')
                ->update([
                    'status'         => 'completed',
                    'balance_before' => $user->balance,
                    'balance_after'  => $user->balance + $transaction->amount,
                    'processed_at'   => now(),
                    'metadata'       => array_merge($transaction->metadata ?? [], [
                        'veopag_transaction_id' => $transactionId,
                        'confirmed_at'          => now()->toIso8601String(),
                        'webhook_payload'       => $payload,
                    ]),
                ]);

            if (!$updated) {
                DB::commit();
                return response()->json(['message' => 'transaction already processed'], 200);
            }

            $amount = (float) $transaction->amount;
            $user->increment('balance', $amount);
            $user->increment('total_deposited', $amount);

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
            "✅ Depósito de {$formattedAmount} confirmado — JrPix",
            $userHtml
        );

        // E-mail para o admin
        $adminEmail = config('services.admin.email', env('ADMIN_EMAIL', 'admin@jrpix.com'));
        $adminHtml  = view('emails.admin-deposit-notification', compact('user', 'amount', 'formattedAmount', 'transactionId', 'date'))->render();
        $this->resend->send(
            $adminEmail,
            'Admin JrPix',
            "💰 Novo depósito: {$formattedAmount} — {$user->name}",
            $adminHtml
        );
    }
}
