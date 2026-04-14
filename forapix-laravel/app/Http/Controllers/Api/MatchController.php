<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Game;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    /**
     * Get all matches
     */
    public function index(Request $request)
    {
        $query = GameMatch::with(['game', 'firstPlayer', 'secondPlayer'])
            ->active()
            ->orderBy('betting_deadline', 'asc');

        // Filter by game
        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        // Filter by sport
        if ($request->has('sport_id')) {
            $query->whereHas('game', function($q) use ($request) {
                $q->where('sport_id', $request->sport_id);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Featured matches
        if ($request->has('featured')) {
            $query->where('featured', true);
        }

        $matches = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $matches,
            'message' => 'Partidas carregadas com sucesso'
        ]);
    }

    /**
     * Get match by ID
     */
    public function show($id)
    {
        $match = GameMatch::with([
            'game.sport', 
            'firstPlayer', 
            'secondPlayer',
            'bets' => function($query) {
                $query->where('status', 'pending');
            }
        ])->find($id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Partida não encontrada'
            ], 404);
        }

        // Add betting status
        $match->can_bet = $match->isBettingOpen();
        $match->time_remaining = $match->time_remaining;

        return response()->json([
            'success' => true,
            'data' => $match,
            'message' => 'Partida carregada com sucesso'
        ]);
    }

    /**
     * Get live matches
     */
    public function live()
    {
        $matches = GameMatch::with(['game', 'firstPlayer', 'secondPlayer'])
            ->live()
            ->orderBy('match_start', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $matches,
            'message' => 'Partidas ao vivo carregadas com sucesso'
        ]);
    }

    /**
     * Get upcoming matches
     */
    public function upcoming()
    {
        $matches = GameMatch::with(['game', 'firstPlayer', 'secondPlayer'])
            ->upcoming()
            ->orderBy('betting_deadline', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $matches,
            'message' => 'Próximas partidas carregadas com sucesso'
        ]);
    }
}
