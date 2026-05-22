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
                'balance'              => $user->balance,
                'withdrawable_balance' => $user->withdrawable_balance,
                'can_withdraw'         => $user->canWithdraw(),
                'total_deposited'      => $user->total_deposited,
                'total_withdrawn'      => $user->total_withdrawn,
                'total_bet'            => $user->total_bet,
                'total_won'            => $user->total_won,
                'profit_loss'          => $user->profit_loss,
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
     * Consulta status de um depósito pendente (polling do frontend).
     * Se ainda pendente, consulta a VeoPag diretamente e confirma se pago.
     */
    public function depositStatus(Request $request, string $transactionId)
    {
        $transaction = Transaction::where('user_id', $request->user()->id)
            ->where('transaction_id', $transactionId)
            ->where('type', 'deposit')
            ->firstOrFail();

        // Já confirmado — retorna direto
        if ($transaction->status === 'completed') {
            return response()->json([
                'success' => true,
                'data'    => [
                    'transaction_id' => $transaction->transaction_id,
                    'status'         => 'completed',
                    'amount'         => $transaction->amount,
                    'balance'        => $request->user()->fresh()->balance,
                ],
            ]);
        }

        // Ainda pendente — consulta VeoPag pelo nosso external_id para verificar se foi pago
        $externalId = $transaction->external_transaction_id ?? null;

        if ($externalId) {
            $veopagTxId = $externalId; // alias para compatibilidade
            try {
                $veopag = app(VeoPagService::class);
                $result = $veopag->getDepositStatus($veopagTxId);

                $paidStatuses = ['COMPLETED', 'PAID', 'APPROVED', 'CONFIRMED', 'SUCCESS'];

                if (in_array($result['status'], $paidStatuses)) {
                    // Confirmar automaticamente
                    $user          = $request->user()->fresh();
                    $balanceBefore = (float) $user->balance;
                    $amount        = (float) $transaction->amount;

                    DB::beginTransaction();
                    try {
                        $user->increment('balance', $amount);
                        $user->increment('total_deposited', $amount);

                        $transaction->update([
                            'status'         => 'completed',
                            'balance_before' => $balanceBefore,
                            'balance_after'  => $balanceBefore + $amount,
                            'processed_at'   => now(),
                            'metadata'       => array_merge($transaction->metadata ?? [], [
                                'confirmed_via' => 'polling',
                                'confirmed_at'  => now()->toIso8601String(),
                                'veopag_status' => $result['status'],
                            ]),
                        ]);

                        DB::commit();

                        Log::info("Depósito confirmado via polling — user {$user->id}", [
                            'transaction_id' => $transactionId,
                            'amount'         => $amount,
                        ]);
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        Log::error('Erro ao confirmar depósito via polling: ' . $e->getMessage());
                    }

                    return response()->json([
                        'success' => true,
                        'data'    => [
                            'transaction_id' => $transaction->transaction_id,
                            'status'         => 'completed',
                            'amount'         => $amount,
                            'balance'        => $user->fresh()->balance,
                        ],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Polling VeoPag falhou: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'transaction_id' => $transaction->transaction_id,
                'status'         => 'pending',
                'amount'         => $transaction->amount,
                'balance'        => null,
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
     * Solicita um saque PIX.
     * Regra: só pode sacar o saldo proveniente de ganhos (withdrawable_balance).
     */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount'  => 'required|numeric|min:10',
            'pix_key' => 'required|string|max:255',
        ]);

        $user   = $request->user();
        $amount = (float) $validated['amount'];

        if (!$user->canWithdraw()) {
            return response()->json([
                'success' => false,
                'message' => 'Você ainda não possui saldo sacável. Deposite, jogue e ganhe para poder sacar.',
            ], 422);
        }

        if ($amount > $user->maxWithdrawable()) {
            return response()->json([
                'success' => false,
                'message' => 'Valor solicitado excede seu saldo sacável de R$ '
                    . number_format($user->maxWithdrawable(), 2, ',', '.') . '.',
            ], 422);
        }

        if ($amount > (float) $user->balance) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $balanceBefore = (float) $user->balance;
            $externalId    = 'sw-' . $user->id . '-' . time();

            $user->decrement('balance', $amount);
            $user->decrement('withdrawable_balance', $amount);
            $user->increment('total_withdrawn', $amount);

            $veopag  = app(VeoPagService::class);
            $veopagId = null;
            $status   = 'pending';

            if ($veopag->isConfigured()) {
                $callbackUrl = config('app.url') . '/api/webhooks/withdraw';
                $result   = $veopag->createWithdrawal($amount, $validated['pix_key'], $externalId, [
                    'name'     => $user->name,
                    'document' => $user->document ?? '00000000000',
                ], $callbackUrl);
                $veopagId = $result['transactionId'];
                $status   = strtolower($result['status']) === 'completed' ? 'completed' : 'pending';
            }

            $transaction = Transaction::create([
                'user_id'                 => $user->id,
                'type'                    => 'withdraw',
                'amount'                  => $amount,
                'net_amount'              => $amount,
                'description'             => 'Saque via PIX',
                'status'                  => $status,
                'payment_method'          => 'pix',
                'external_transaction_id' => $externalId,
                'payment_reference'       => $veopagId,
                'balance_before'          => $balanceBefore,
                'balance_after'           => (float) $user->fresh()->balance,
                'processed_at'            => $status === 'completed' ? now() : null,
                'metadata'                => [
                    'pix_key'              => $validated['pix_key'],
                    'veopag_transaction_id' => $veopagId,
                    'source'               => $veopag->isConfigured() ? 'veopag' : 'manual',
                ],
            ]);

            DB::commit();

            $msg = $status === 'completed'
                ? 'Saque processado! O PIX será enviado em instantes.'
                : 'Saque solicitado! Você receberá o PIX em até 24 horas.';

            return response()->json([
                'success' => true,
                'data'    => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount'         => $amount,
                    'status'         => $status,
                    'pix_key'        => $validated['pix_key'],
                ],
                'message' => $msg,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Saque erro: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar saque: ' . $e->getMessage(),
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
