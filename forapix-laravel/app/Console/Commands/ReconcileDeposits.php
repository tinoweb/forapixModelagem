<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use App\Services\VeoPagService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcileDeposits extends Command
{
    protected $signature   = 'deposits:reconcile {--minutes=30 : Reconciliar depósitos pendentes há mais de X minutos}';
    protected $description = 'Consulta VeoPag e confirma depósitos pendentes que já foram pagos';

    public function handle(VeoPagService $veopag): int
    {
        if (!$veopag->isConfigured()) {
            $this->error('VeoPag não configurada.');
            return self::FAILURE;
        }

        $minutes = (int) $this->option('minutes');

        $pending = Transaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->whereNotNull('external_transaction_id')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->with('user')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Nenhum depósito pendente encontrado.');
            return self::SUCCESS;
        }

        $this->info("Reconciliando {$pending->count()} depósito(s) pendente(s)...");
        $confirmed = 0;
        $failed    = 0;

        foreach ($pending as $transaction) {
            $this->line("  → {$transaction->transaction_id} (R$ {$transaction->amount})");

            try {
                $result = $veopag->getDepositStatus($transaction->external_transaction_id);

                $paidStatuses = ['COMPLETED', 'PAID', 'APPROVED', 'CONFIRMED', 'SUCCESS'];

                if (!in_array($result['status'], $paidStatuses)) {
                    $this->line("     status VeoPag: {$result['status']} — ignorado");
                    continue;
                }

                $user          = $transaction->user;
                $balanceBefore = (float) $user->balance;
                $amount        = (float) $transaction->amount;

                DB::transaction(function () use ($user, $transaction, $amount, $balanceBefore, $result) {
                    $user->increment('balance', $amount);
                    $user->increment('total_deposited', $amount);

                    $transaction->update([
                        'status'         => 'completed',
                        'balance_before' => $balanceBefore,
                        'balance_after'  => $balanceBefore + $amount,
                        'processed_at'   => now(),
                        'metadata'       => array_merge($transaction->metadata ?? [], [
                            'confirmed_via'  => 'reconciliation',
                            'confirmed_at'   => now()->toIso8601String(),
                            'veopag_status'  => $result['status'],
                        ]),
                    ]);
                });

                $this->info("     ✅ Confirmado! Saldo de {$user->name}: R$ " . number_format($balanceBefore + $amount, 2, ',', '.'));
                Log::info("ReconcileDeposits: depósito {$transaction->transaction_id} confirmado", [
                    'user_id' => $user->id,
                    'amount'  => $amount,
                ]);
                $confirmed++;

            } catch (\Throwable $e) {
                $this->warn("     ⚠ Erro: {$e->getMessage()}");
                Log::error("ReconcileDeposits: erro em {$transaction->transaction_id}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Reconciliação concluída: {$confirmed} confirmado(s), {$failed} erro(s).");
        return self::SUCCESS;
    }
}
