<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Sport;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Get all games
     */
    public function index(Request $request)
    {
        $query = Game::with('sport')->active();

        // Filter by sport
        if ($request->has('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $games = $query->get();

        return response()->json([
            'success' => true,
            'data' => $games,
            'message' => 'Jogos carregados com sucesso'
        ]);
    }

    /**
     * Get all sports
     */
    public function sports()
    {
        $sports = Sport::active()->with('activeGames')->get();

        return response()->json([
            'success' => true,
            'data' => $sports,
            'message' => 'Esportes carregados com sucesso'
        ]);
    }

    /**
     * Get game by slug
     */
    public function show($slug)
    {
        $game = Game::with(['sport', 'activeMatches'])
            ->where('slug', $slug)
            ->active()
            ->first();

        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Jogo não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $game,
            'message' => 'Jogo carregado com sucesso'
        ]);
    }
}
