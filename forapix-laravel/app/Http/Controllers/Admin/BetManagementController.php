<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\GameMatch;
use App\Models\Transaction;
use Illuminate\Http\Request;

class BetManagementController extends Controller
{
    /**
     * Lista todas as apostas com filtros
     */
    public function index(Request $request)
    {
        $query = Bet::with(['user', 'match.game', 'match.firstPlayer', 'match.secondPlayer'])
                    ->latest('placed_at');

        if ($request->filled('match_id')) {
            $query->where('match_id', $request->match_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('bet_type')) {
            $query->where('bet_type', $request->bet_type);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")
                                                  ->orWhere('email', 'like', "%{$request->search}%"))
                  ->orWhere('bet_id', 'like', "%{$request->search}%");
            });
        }

        $bets = $query->paginate(20)->withQueryString();

        $matches = GameMatch::with(['firstPlayer', 'secondPlayer'])
                            ->orderBy('match_start', 'desc')
                            ->get();

        $stats = [
            'total'     => Bet::count(),
            'pending'   => Bet::where('status', 'pending')->count(),
            'won'       => Bet::where('status', 'won')->count(),
            'lost'      => Bet::where('status', 'lost')->count(),
            'cancelled' => Bet::where('status', 'cancelled')->count(),
            'volume'    => Bet::sum('amount'),
            'paid_out'  => Bet::where('status', 'won')->sum('result_amount'),
        ];

        return view('admin.bets.index', compact('bets', 'matches', 'stats'));
    }

    /**
     * Exibe detalhes de uma aposta
     */
    public function show(Bet $bet)
    {
        $bet->load(['user', 'match.game.sport', 'match.firstPlayer', 'match.secondPlayer']);
        return view('admin.bets.show', compact('bet'));
    }

    /**
     * Encerra uma partida e processa todas as apostas pendentes
     */
    public function resolveMatch(Request $request, GameMatch $match)
    {
        $request->validate([
            'result'              => 'required|in:first_player,second_player,draw,par,impar',
            'winner_player_id'    => 'nullable|exists:players,id',
            'first_player_score'  => 'nullable|integer|min:0',
            'second_player_score' => 'nullable|integer|min:0',
        ]);

        if ($match->status === 'finished') {
            return response()->json([
                'success' => false,
                'message' => 'Esta partida já foi encerrada anteriormente.',
            ], 400);
        }

        $match->resolveMatch($request->result, [
            'first_player'  => $request->first_player_score,
            'second_player' => $request->second_player_score,
        ]);

        $updates = ['status' => 'finished', 'match_end' => now()];
        if ($request->filled('winner_player_id')) {
            $updates['winner_player_id'] = $request->winner_player_id;
        }
        $match->update($updates);

        $processed = $match->bets()->whereIn('status', ['won', 'lost'])->count();

        return response()->json([
            'success' => true,
            'message' => "Partida encerrada! {$processed} apostas foram processadas.",
        ]);
    }

    /**
     * Cancela uma aposta individual e reembolsa o usuário
     */
    public function cancel(Request $request, Bet $bet)
    {
        if ($bet->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Só é possível cancelar apostas com status pendente.',
            ], 400);
        }

        $reason = $request->input('reason', 'Cancelada pelo administrador');
        $balanceBefore = $bet->user->balance;

        $bet->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $reason,
            'resolved_at'         => now(),
        ]);

        $bet->user->increment('balance', $bet->amount);

        Transaction::create([
            'user_id'        => $bet->user_id,
            'type'           => 'refund',
            'amount'         => $bet->amount,
            'net_amount'     => $bet->amount,
            'description'    => "Reembolso da aposta {$bet->bet_id} (admin: {$reason})",
            'reference_id'   => $bet->id,
            'reference_type' => 'bet',
            'status'         => 'completed',
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceBefore + $bet->amount,
            'processed_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Aposta {$bet->bet_id} cancelada. R$ " . number_format($bet->amount, 2, ',', '.') . " reembolsados.",
        ]);
    }
}
