<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\GameMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BetController extends Controller
{
    /**
     * Place a bet on a match
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'match_id' => 'required|exists:matches,id',
            'bet_type' => 'required|in:first_player,second_player,draw,par,impar',
            'amount' => 'required|numeric|min:1'
        ]);

        $user = $request->user();
        $match = GameMatch::with(['game', 'firstPlayer', 'secondPlayer'])->findOrFail($validated['match_id']);

        // Validate betting is open
        if (!$match->isBettingOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'Apostas encerradas para esta partida'
            ], 422);
        }

        // Validate user can bet
        if (!$user->canBet()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não pode apostar'
            ], 422);
        }

        // Validate balance
        if ($user->balance < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente'
            ], 422);
        }

        // Validate bet amount limits
        if ($match->game && !$match->game->isValidBetAmount($validated['amount'])) {
            return response()->json([
                'success' => false,
                'message' => "Valor da aposta deve ser entre R$ " . number_format($match->game->min_bet, 2) . " e R$ " . number_format($match->game->max_bet, 2)
            ], 422);
        }

        // Validate odds exist for bet type
        $odds = $match->getOddsForBetType($validated['bet_type']);
        if (!$odds) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de aposta indisponível para esta partida'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $bet = $user->placeBet($match, $validated['bet_type'], $validated['amount']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $bet->load('match.game', 'match.firstPlayer', 'match.secondPlayer'),
                'message' => 'Aposta realizada com sucesso!'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get user's bets
     */
    public function index(Request $request)
    {
        $query = Bet::with(['match.game', 'match.firstPlayer', 'match.secondPlayer'])
            ->where('user_id', $request->user()->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bets = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bets,
            'message' => 'Apostas carregadas com sucesso'
        ]);
    }

    /**
     * Get a specific bet
     */
    public function show(Request $request, $id)
    {
        $bet = Bet::with(['match.game', 'match.firstPlayer', 'match.secondPlayer'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bet,
            'message' => 'Aposta carregada com sucesso'
        ]);
    }

    /**
     * Cancel a bet
     */
    public function cancel(Request $request, $id)
    {
        $bet = Bet::where('user_id', $request->user()->id)->findOrFail($id);

        if (!$bet->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta aposta não pode ser cancelada'
            ], 422);
        }

        $bet->cancel('Cancelada pelo usuário');

        return response()->json([
            'success' => true,
            'data' => $bet->fresh(),
            'message' => 'Aposta cancelada e valor reembolsado'
        ]);
    }
}
