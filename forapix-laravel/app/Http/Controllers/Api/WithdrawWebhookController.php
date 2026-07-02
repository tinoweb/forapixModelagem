<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawWebhookController extends Controller
{
    /**
     * Recebe notificação de saque processado pela VeoPag.
     * POST /api/webhooks/withdraw
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('VeoPag saque webhook recebido', $payload);

        $type   = $payload['type']   ?? null;
        $status = $payload['status'] ?? null;

        // Só processa saques concluídos ou falhos
        if ($type !== 'Withdrawal') {
            return response()->json(['message' => 'ignored'], 200);
        }

        $externalId    = $payload['external_id']    ?? $payload['externalId']    ?? null;
        $transactionId = $payload['transaction_id'] ?? $payload['transactionId'] ?? null;

        if (!$externalId) {
            Log::warning('VeoPag saque webhook: external_id ausente', $payload);
            return response()->json(['message' => 'external_id missing'], 400);
        }

        $transaction = Transaction::where(function ($q) use ($externalId, $transactionId) {
            $q->where('external_transaction_id', $externalId)
              ->orWhere('external_transaction_id', $transactionId);
        })
        ->where('type', 'withdraw')
        ->whereIn('status', ['pending', 'completed'])
        ->first();

        if (!$transaction) {
            Log::warning('VeoPag saque webhook: transação não encontrada ou já processada', compact('externalId'));
            return response()->json(['message' => 'transaction not found or already processed'], 200);
        }

        try {
            DB::beginTransaction();

            $newStatus = match (strtoupper($status)) {
                'COMPLETED', 'PAID', 'SUCCESS' => 'completed',
                'FAILED', 'REJECTED', 'ERROR'  => 'failed',
                default                         => 'pending',
            };

            // Se a transação já estava completada e o status do webhook é de sucesso,
            // apenas atualizamos metadados e encerramos para evitar processamento duplicado.
            if ($transaction->status === 'completed' && $newStatus === 'completed') {
                $transaction->update([
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'veopag_transaction_id' => $transactionId,
                        'webhook_payload'       => $payload,
                        'confirmed_at'          => now()->toIso8601String(),
                    ]),
                ]);
                DB::commit();
                return response()->json(['message' => 'ok (already completed)'], 200);
            }

            // Se a transação falhou, estorna o saldo e withdrawable_balance ao usuário
            if ($newStatus === 'failed') {
                $transaction->update([
                    'status'       => 'failed',
                    'processed_at' => now(),
                    'metadata'     => array_merge($transaction->metadata ?? [], [
                        'veopag_transaction_id' => $transactionId,
                        'webhook_payload'       => $payload,
                        'failed_at'             => now()->toIso8601String(),
                    ]),
                ]);

                $user = $transaction->user;
                $user->increment('balance', $transaction->amount);
                $user->increment('withdrawable_balance', $transaction->amount);
                $user->decrement('total_withdrawn', $transaction->amount);

                Log::warning("VeoPag: saque falhou para user {$user->id}, saldo estornado", [
                    'amount'      => $transaction->amount,
                    'external_id' => $externalId,
                ]);
            } else {
                $transaction->update([
                    'status'       => $newStatus,
                    'processed_at' => $newStatus !== 'pending' ? now() : null,
                    'metadata'     => array_merge($transaction->metadata ?? [], [
                        'veopag_transaction_id' => $transactionId,
                        'confirmed_at'          => now()->toIso8601String(),
                        'webhook_payload'       => $payload,
                    ]),
                ]);

                Log::info("VeoPag: saque confirmado para user {$transaction->user_id}", [
                    'amount'      => $transaction->amount,
                    'external_id' => $externalId,
                ]);
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('VeoPag saque webhook: erro ao processar', [
                'error'      => $e->getMessage(),
                'externalId' => $externalId,
            ]);
            return response()->json(['message' => 'processing error'], 500);
        }

        return response()->json(['message' => 'ok'], 200);
    }
}
