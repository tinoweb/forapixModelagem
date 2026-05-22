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
        $query = GameMatch::with(['game.sport', 'firstPlayer', 'secondPlayer']);

        // Filter by status com lógica de deadline expirado
        if ($request->has('status') && $request->status !== 'all') {
            $status = $request->status;

            if ($status === 'scheduled') {
                // Agendadas: todas com status scheduled
                $query->where('status', 'scheduled');

            } elseif ($status === 'finished') {
                // Encerradas: finalizadas + agendadas com prazo já expirado
                $query->where(function ($q) {
                    $q->where('status', 'finished')
                      ->orWhere('status', 'cancelled')
                      ->orWhere(function ($q2) {
                          $q2->where('status', 'scheduled')
                             ->where('betting_deadline', '<=', now());
                      });
                });

            } else {
                $query->where('status', $status);
            }
        } elseif (!$request->has('status')) {
            // Sem filtro: ativas (live + scheduled com prazo futuro)
            $query->where(function ($q) {
                $q->where('status', 'live')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'scheduled')
                         ->where('betting_deadline', '>', now());
                  });
            });
        }

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

        // Featured matches
        if ($request->has('featured')) {
            $query->where('featured', true);
        }

        // Ordenação: agendadas = mais próxima primeiro | live/finished = mais recente primeiro
        $requestedStatus = $request->status ?? null;
        if ($requestedStatus === 'scheduled') {
            $query->orderBy('match_start', 'asc');
        } else {
            $query->orderBy('match_start', 'desc');
        }

        $matches = $query->paginate(20);

        // Adiciona can_bet em cada partida para o frontend saber se pode apostar
        $matches->getCollection()->transform(function ($match) {
            $match->can_bet = $match->isBettingOpen();
            return $match;
        });

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
        ])->find($id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Partida não encontrada'
            ], 404);
        }

        // Estatísticas reais de apostas casadas (pool)
        $poolStats = (new \App\Services\BetMatchingService())->getMatchStats($match);

        $match->bet_stats          = $poolStats;
        $match->total_bets         = $poolStats['first_player']['count'] + $poolStats['second_player']['count'];
        $match->can_bet            = $match->isBettingOpen();
        $match->time_remaining     = $match->time_remaining;

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
        $matches = GameMatch::with(['game.sport', 'firstPlayer', 'secondPlayer'])
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
        $matches = GameMatch::with(['game.sport', 'firstPlayer', 'secondPlayer'])
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
