<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Services\VeoPagService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialController extends Controller
{
    /**
     * Visão geral financeira com tabs depósitos/saques
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'withdrawals');

        $stats = [
            'deposits_today'       => Transaction::where('type', 'deposit')->where('status', 'completed')->whereDate('created_at', today())->sum('amount'),
            'deposits_pending'     => Transaction::where('type', 'deposit')->where('status', 'pending')->count(),
            'withdrawals_pending'  => Transaction::where('type', 'withdraw')->where('status', 'pending')->count(),
            'withdrawals_today'    => Transaction::where('type', 'withdraw')->where('status', 'completed')->whereDate('updated_at', today())->sum('amount'),
            'net_flow_month'       => Transaction::where('type', 'deposit')->where('status', 'completed')->where('created_at', '>=', Carbon::now()->startOfMonth())->sum('amount')
                                    - Transaction::where('type', 'withdraw')->where('status', 'completed')->where('created_at', '>=', Carbon::now()->startOfMonth())->sum('amount'),
        ];

        // Saques
        $withdrawalsQuery = Transaction::with('user')
            ->where('type', 'withdraw')
            ->latest();

        if ($request->filled('status')) {
            $withdrawalsQuery->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $withdrawalsQuery->whereHas('user', fn($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%"));
        }

        $withdrawals = $withdrawalsQuery->paginate(20, ['*'], 'w_page')->withQueryString()->appends(['tab' => 'withdrawals']);

        // Depósitos
        $depositsQuery = Transaction::with('user')
            ->where('type', 'deposit')
            ->latest();

        if ($request->filled('dep_status')) {
            $depositsQuery->where('status', $request->dep_status);
        }
        if ($request->filled('dep_search')) {
            $depositsQuery->whereHas('user', fn($q) => $q->where('name', 'like', "%{$request->dep_search}%")->orWhere('email', 'like', "%{$request->dep_search}%"));
        }

        $deposits = $depositsQuery->paginate(20, ['*'], 'd_page')->withQueryString()->appends(['tab' => 'deposits']);

        return view('admin.financial.index', compact('stats', 'withdrawals', 'deposits', 'tab'));
    }

    /**
     * Aprovar saque manualmente (quando VeoPag não confirmou automaticamente)
     */
    public function approveWithdrawal(Request $request, Transaction $transaction)
    {
        if ($transaction->type !== 'withdraw' || $transaction->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Saque não está pendente.'], 422);
        }

        $transaction->update([
            'status'       => 'completed',
            'processed_at' => now(),
            'metadata'     => array_merge($transaction->metadata ?? [], [
                'approved_by'  => auth()->id(),
                'approved_at'  => now()->toIso8601String(),
                'approved_note' => $request->note ?? 'Aprovado manualmente pelo admin',
            ]),
        ]);

        Log::info("Admin aprovou saque manualmente", [
            'transaction_id' => $transaction->transaction_id,
            'user_id'        => $transaction->user_id,
            'amount'         => $transaction->amount,
            'admin_id'       => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Saque de R$ " . number_format($transaction->amount, 2, ',', '.') . " aprovado.",
        ]);
    }

    /**
     * Rejeitar/estornar saque pendente
     */
    public function rejectWithdrawal(Request $request, Transaction $transaction)
    {
        if ($transaction->type !== 'withdraw' || $transaction->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Saque não está pendente.'], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::findOrFail($transaction->user_id);
            $user->increment('balance', $transaction->amount);
            $user->increment('withdrawable_balance', $transaction->amount);
            $user->decrement('total_withdrawn', $transaction->amount);

            $transaction->update([
                'status'       => 'failed',
                'processed_at' => now(),
                'metadata'     => array_merge($transaction->metadata ?? [], [
                    'rejected_by'   => auth()->id(),
                    'rejected_at'   => now()->toIso8601String(),
                    'rejected_note' => $request->note ?? 'Rejeitado pelo admin',
                ]),
            ]);

            DB::commit();

            Log::warning("Admin rejeitou saque — saldo estornado", [
                'transaction_id' => $transaction->transaction_id,
                'user_id'        => $transaction->user_id,
                'amount'         => $transaction->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Saque rejeitado e R$ " . number_format($transaction->amount, 2, ',', '.') . " devolvido ao usuário.",
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirmar depósito manualmente (fallback sem webhook)
     */
    public function approveDeposit(Request $request, Transaction $transaction)
    {
        if ($transaction->type !== 'deposit' || $transaction->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Depósito não está pendente.'], 422);
        }

        try {
            DB::beginTransaction();

            $user          = User::findOrFail($transaction->user_id);
            $balanceBefore = (float) $user->balance;

            $user->increment('balance', $transaction->amount);
            $user->increment('total_deposited', $transaction->amount);

            $transaction->update([
                'status'         => 'completed',
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceBefore + $transaction->amount,
                'processed_at'   => now(),
                'metadata'       => array_merge($transaction->metadata ?? [], [
                    'approved_by' => auth()->id(),
                    'approved_at' => now()->toIso8601String(),
                    'source'      => 'manual_admin',
                ]),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Depósito de R$ " . number_format($transaction->amount, 2, ',', '.') . " confirmado.",
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reconcilia depósitos pendentes consultando a VeoPag diretamente.
     * Executável pelo navegador via painel admin.
     */
    public function reconcileDeposits(Request $request)
    {
        $veopag = app(VeoPagService::class);

        if (!$veopag->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'VeoPag não configurada.'], 422);
        }

        $pending = Transaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->whereNotNull('external_transaction_id')
            ->with('user')
            ->get();

        if ($pending->isEmpty()) {
            return response()->json(['success' => true, 'message' => 'Nenhum depósito pendente encontrado.', 'confirmed' => 0]);
        }

        $confirmed = 0;
        $errors    = [];
        $paidStatuses = ['COMPLETED', 'PAID', 'APPROVED', 'CONFIRMED', 'SUCCESS'];

        foreach ($pending as $transaction) {
            try {
                $result = $veopag->getDepositStatus($transaction->external_transaction_id);

                if (!in_array($result['status'], $paidStatuses)) {
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
                            'confirmed_via' => 'admin_reconcile',
                            'confirmed_at'  => now()->toIso8601String(),
                            'veopag_status' => $result['status'],
                        ]),
                    ]);
                });

                Log::info("Admin reconcile: depósito {$transaction->transaction_id} confirmado", [
                    'user_id' => $user->id,
                    'amount'  => $amount,
                ]);

                $confirmed++;

            } catch (\Throwable $e) {
                $errors[] = "{$transaction->transaction_id}: {$e->getMessage()}";
                Log::error("Admin reconcile erro em {$transaction->transaction_id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success'   => true,
            'message'   => "{$confirmed} depósito(s) confirmado(s) de {$pending->count()} pendente(s).",
            'confirmed' => $confirmed,
            'total'     => $pending->count(),
            'errors'    => $errors,
        ]);
    }
}
