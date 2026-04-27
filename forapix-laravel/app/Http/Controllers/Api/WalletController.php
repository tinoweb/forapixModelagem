<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Request a deposit (generates PIX info)
     */
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:50000'
        ]);

        $user = $request->user();

        // Generate PIX data
        $pixKey = config('services.pix.key', 'forapix@pix.com.br');
        $pixCode = $this->generatePixCode($validated['amount'], $user);

        // Create pending transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $validated['amount'],
            'net_amount' => $validated['amount'],
            'description' => 'Depósito via PIX',
            'status' => 'pending',
            'balance_before' => $user->balance,
            'balance_after' => $user->balance,
            'metadata' => [
                'pix_code' => $pixCode,
                'pix_key' => $pixKey,
                'method' => 'pix'
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $validated['amount'],
                'pix_key' => $pixKey,
                'pix_code' => $pixCode,
                'qr_code_url' => null,
                'expires_at' => now()->addMinutes(30)->toIso8601String()
            ],
            'message' => 'Depósito solicitado com sucesso'
        ]);
    }

    /**
     * Confirm a deposit (simulated for now)
     */
    public function confirmDeposit(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|exists:transactions,id'
        ]);

        $user = $request->user();
        $transaction = Transaction::where('user_id', $user->id)
            ->where('id', $validated['transaction_id'])
            ->where('type', 'deposit')
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $balanceBefore = $user->balance;
            $user->increment('balance', $transaction->amount);
            $user->increment('total_deposited', $transaction->amount);

            $transaction->update([
                'status' => 'completed',
                'balance_before' => $balanceBefore,
                'balance_after' => $user->fresh()->balance,
                'processed_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => $user->fresh()->balance,
                    'transaction' => $transaction->fresh()
                ],
                'message' => 'Depósito confirmado com sucesso!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar depósito'
            ], 422);
        }
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
