<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\VeoPagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /**
     * Get user balance and wallet info
     */
    public function balance(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $user->balance,
                'total_deposited' => $user->total_deposited,
                'total_withdrawn' => $user->total_withdrawn,
                'total_bet' => $user->total_bet,
                'total_won' => $user->total_won,
                'profit_loss' => $user->profit_loss,
            ],
            'message' => 'Saldo carregado com sucesso'
        ]);
    }

    /**
     * Get transaction history
     */
    public function transactions(Request $request)
    {
        $query = Transaction::where('user_id', $request->user()->id);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'message' => 'Transações carregadas com sucesso'
        ]);
    }

    /**
     * Cria depósito PIX via VeoPag
     */
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount'   => 'required|numeric|min:10|max:50000',
            'document' => 'nullable|string',
            'phone'    => 'nullable|string',
        ]);

        $user   = $request->user();
        $amount = (float) $validated['amount'];

        $veopag = app(VeoPagService::class);

        // Se VeoPag não configurada, retorna fallback (dev/testes)
        if (!$veopag->isConfigured()) {
            $pixCode = $this->generatePixCode($amount, $user);

            $transaction = Transaction::create([
                'user_id'        => $user->id,
                'type'           => 'deposit',
                'amount'         => $amount,
                'net_amount'     => $amount,
                'description'    => 'Depósito via PIX',
                'status'         => 'pending',
                'payment_method' => 'pix',
                'balance_before' => $user->balance,
                'balance_after'  => $user->balance,
                'metadata'       => ['pix_code' => $pixCode, 'source' => 'fallback'],
            ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount'         => $amount,
                    'qrcode'         => $pixCode,
                    'expires_at'     => now()->addMinutes(30)->toIso8601String(),
                    'source'         => 'fallback',
                ],
                'message' => 'Depósito gerado (modo local)',
            ]);
        }

        // external_id baseado no user + timestamp (idempotente)
        $externalId  = 'fp-' . $user->id . '-' . time();
        $callbackUrl = config('app.url') . '/api/webhooks/deposit';

        try {
            $result = $veopag->createDeposit($amount, $externalId, [
                'name'     => $user->name,
                'email'    => $user->email,
                'document' => $validated['document'] ?? '00000000000',
                'phone'    => $validated['phone'] ?? null,
            ], $callbackUrl);

        } catch (\Throwable $e) {
            Log::error('VeoPag deposit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PIX: ' . $e->getMessage(),
            ], 422);
        }

        $transaction = Transaction::create([
            'user_id'                => $user->id,
            'type'                   => 'deposit',
            'amount'                 => $amount,
            'net_amount'             => $amount,
            'description'            => 'Depósito via PIX (VeoPag)',
            'status'                 => 'pending',
            'payment_method'         => 'pix',
            'payment_reference'      => $result['transactionId'],
            'external_transaction_id'=> $externalId,
            'balance_before'         => $user->balance,
            'balance_after'          => $user->balance,
            'metadata'               => [
                'veopag_transaction_id' => $result['transactionId'],
                'qrcode'                => $result['qrcode'],
                'fee'                   => $result['fee'],
                'source'                => 'veopag',
            ],
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'transaction_id' => $transaction->transaction_id,
                'amount'         => $result['amount'],
                'qrcode'         => $result['qrcode'],
                'fee'            => $result['fee'],
                'expires_at'     => now()->addMinutes(30)->toIso8601String(),
                'source'         => 'veopag',
            ],
            'message' => 'PIX gerado com sucesso! Aguarde o pagamento.',
        ]);
    }

    /**
     * Consulta status de um depósito pendente (polling do frontend)
     */
    public function depositStatus(Request $request, string $transactionId)
    {
        $transaction = Transaction::where('user_id', $request->user()->id)
            ->where('transaction_id', $transactionId)
            ->where('type', 'deposit')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => [
                'transaction_id' => $transaction->transaction_id,
                'status'         => $transaction->status,
                'amount'         => $transaction->amount,
                'balance'        => $transaction->status === 'completed'
                    ? $request->user()->fresh()->balance
                    : null,
            ],
        ]);
    }

    /**
     * Confirm a deposit — mantido para compatibilidade
     */
    public function confirmDeposit(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Confirmação automática via webhook. Aguarde o processamento.',
        ], 422);
    }

    /**
     * Request a withdrawal
     */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'pix_key' => 'required|string'
        ]);

        $user = $request->user();

        if (!$user->canWithdraw()) {
            return response()->json([
                'success' => false,
                'message' => 'Saque não disponível'
            ], 422);
        }

        if ($user->balance < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $balanceBefore = $user->balance;
            $user->decrement('balance', $validated['amount']);
            $user->increment('total_withdrawn', $validated['amount']);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdraw',
                'amount' => $validated['amount'],
                'net_amount' => $validated['amount'],
                'description' => 'Saque via PIX',
                'status' => 'pending',
                'balance_before' => $balanceBefore,
                'balance_after' => $user->fresh()->balance,
                'metadata' => [
                    'pix_key' => $validated['pix_key'],
                    'method' => 'pix'
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Saque solicitado com sucesso'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar saque'
            ], 422);
        }
    }

    /**
     * Generate PIX code (simplified)
     */
    private function generatePixCode(float $amount, $user): string
    {
        $pixKey = config('services.pix.key', 'forapix@pix.com.br');
        $merchantName = 'FORAPIX';
        $merchantCity = 'SAO PAULO';
        $txid = 'DEP' . strtoupper(substr(md5($user->id . time()), 0, 8));

        return "00020126580014br.gov.bcb.pix0136{$pixKey}5204000053039865405" . number_format($amount, 2, '', '') . "5802BR5925{$merchantName}6009{$merchantCity}62070503***6304" . strtoupper(substr(md5($pixKey . $amount), 0, 4));
    }
}
