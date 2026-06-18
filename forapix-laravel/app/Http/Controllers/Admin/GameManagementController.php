<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GameManagementController extends Controller
{
    /**
     * List all games
     */
    public function games(Request $request)
    {
        $query = Game::with(['sport', 'matches']);

        // Filters
        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $games = $query->paginate(15);
        $sports = Sport::orderBy('name')->get();

        return view('admin.games.index', compact('games', 'sports'));
    }

    public function showCreateGameForm()
    {
        $sports = Sport::orderBy('name')->get();

        return view('admin.games.create', compact('sports'));
    }

    public function showEditGameForm(Game $game)
    {
        $sports = Sport::orderBy('name')->get();

        return view('admin.games.edit', compact('game', 'sports'));
    }

    public function confirmDeleteGame(Game $game)
    {
        return view('admin.games.delete', compact('game'));
    }

    /**
     * Create new game
     */
    public function createGame(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sport_id' => 'nullable|exists:sports,id',
            'type' => 'required|in:head_to_head,casino,bingo,sinuca,par_impar',
            'description' => 'nullable|string',
            'min_bet' => 'required|numeric|min:0.01',
            'max_bet' => 'required|numeric|min:0.01',
            'house_edge' => 'required|numeric|min:0|max:1',
            'status' => 'nullable|in:active,inactive,maintenance',
            'image' => 'nullable|image|max:2048',
            'settings' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $gameData = $request->except('image');
        $gameData['slug'] = Str::slug($request->name);
        $gameData['created_by'] = auth()->id();
        $gameData['status'] = $request->input('status', 'active');
        $gameData['house_edge'] = $request->input('house_edge') / 100;

        // Handle image upload
        if ($request->hasFile('image')) {
            $gameData['image'] = $request->file('image')->store('games');
        }

        // Parse settings JSON
        if ($request->has('settings')) {
            $gameData['settings'] = json_decode($request->settings, true);
        }

        $game = Game::create($gameData);

        return response()->json([
            'success' => true,
            'data' => $game->load('sport'),
            'message' => 'Jogo criado com sucesso'
        ]);
    }

    /**
     * Update game
     */
    public function updateGame(Request $request, Game $game)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'sport_id' => 'sometimes|nullable|exists:sports,id',
            'type' => 'sometimes|in:head_to_head,casino,bingo,sinuca,par_impar',
            'description' => 'sometimes|nullable|string',
            'min_bet' => 'sometimes|numeric|min:0.01',
            'max_bet' => 'sometimes|numeric|min:0.01',
            'house_edge' => 'sometimes|numeric|min:0|max:1',
            'status' => 'sometimes|in:active,inactive,maintenance',
            'image' => 'sometimes|nullable|image|max:2048',
            'settings' => 'sometimes|nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $gameData = $request->except('image');
        $gameData['updated_by'] = auth()->id();

        // Update slug if name changed
        if ($request->has('name')) {
            $gameData['slug'] = Str::slug($request->name);
        }

        if ($request->has('house_edge')) {
            $gameData['house_edge'] = $request->input('house_edge') / 100;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $gameData['image'] = $request->file('image')->store('games');
        }

        // Parse settings JSON
        if ($request->has('settings')) {
            $gameData['settings'] = json_decode($request->settings, true);
        }

        $game->update($gameData);

        return response()->json([
            'success' => true,
            'data' => $game->load('sport'),
            'message' => 'Jogo atualizado com sucesso'
        ]);
    }

    /**
     * Delete game
     */
    public function deleteGame(Game $game)
    {
        // Check if game has active matches
        $activeMatches = $game->matches()->whereIn('status', ['scheduled', 'live'])->count();
        
        if ($activeMatches > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir jogo com partidas ativas'
            ], 400);
        }

        $game->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jogo excluído com sucesso'
        ]);
    }

    /**
     * List all matches
     */
    public function matches(Request $request)
    {
        $query = GameMatch::with(['game.sport', 'firstPlayer', 'secondPlayer', 'bets']);

        // Filters
        if ($request->filled('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('match_start', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('match_start', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->whereHas('firstPlayer', function($sq) use ($request) {
                    $sq->where('name', 'like', '%' . $request->search . '%');
                })->orWhereHas('secondPlayer', function($sq) use ($request) {
                    $sq->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }

        $matches = $query->orderBy('match_start', 'desc')->paginate(15);
        $games = Game::where('status', 'active')->with('sport')->get();
        $players = Player::orderBy('name')->get();
        $sports = Sport::orderBy('name')->get();

        return view('admin.matches.index', compact('matches', 'games', 'players', 'sports'));
    }

    public function showCreateMatchForm()
    {
        $games = Game::where('status', 'active')->with('sport')->get();
        $players = Player::orderBy('name')->get();

        return view('admin.matches.create', compact('games', 'players'));
    }

    public function showEditMatchForm(GameMatch $match)
    {
        $games = Game::where('status', 'active')->with('sport')->get();
        $players = Player::orderBy('name')->get();

        return view('admin.matches.edit', compact('match', 'games', 'players'));
    }

    public function confirmDeleteMatch(GameMatch $match)
    {
        return view('admin.matches.delete', compact('match'));
    }

    /**
     * Create new match
     */
    public function createMatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'game_id' => 'required|exists:games,id',
            'first_player_id' => 'required|exists:players,id',
            'second_player_id' => 'required|exists:players,id|different:first_player_id',
            'match_start' => 'required|date',
            'match_end' => 'nullable|date|after:match_start',
            'betting_deadline' => 'required|date|before:match_start',
            'first_player_odds' => 'nullable|numeric|min:1.01',
            'second_player_odds' => 'nullable|numeric|min:1.01',
            'draw_odds' => 'nullable|numeric|min:1.01',
            'par_odds' => 'nullable|numeric|min:1.01',
            'impar_odds' => 'nullable|numeric|min:1.01',
            'description' => 'nullable|string',
            'featured' => 'boolean',
            'betting_options' => 'nullable|json',
            'status' => 'nullable|in:scheduled,live,finished,cancelled,postponed',
            'stream_url' => 'nullable|url',
            'banner_image' => 'nullable|image|max:4096',
            'banner_button_label' => 'nullable|string|max:40',
            'banner_button_link' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $matchData = $request->except(['banner_image', 'banner_button_label', 'banner_button_link', 'stream_url']);
        $matchData['created_by'] = auth()->id();
        $matchData['status'] = $request->input('status', 'scheduled');

        // Parse betting options JSON
        if ($request->has('betting_options')) {
            $matchData['betting_options'] = json_decode($request->betting_options, true);
        }

        $metadata = $matchData['metadata'] ?? [];
        if ($request->filled('stream_url')) {
            $metadata['stream_url'] = $request->input('stream_url');
        }
        if ($request->hasFile('banner_image')) {
            $metadata['banner_image'] = $request->file('banner_image')->store('matches/banners');
        } elseif (empty($metadata['banner_image'])) {
            // Imagem padrão para todas as partidas
            $metadata['banner_image'] = 'matches/banners/6b0z8T0MQaoG4SVQ4B9MOiw4rvhqWUf3aCquHoGn.png';
        }
        if ($request->filled('banner_button_label')) {
            $metadata['banner_button_label'] = $request->input('banner_button_label');
        }
        if ($request->filled('banner_button_link')) {
            $metadata['banner_button_link'] = $request->input('banner_button_link');
        }
        $matchData['metadata'] = $metadata;

        $match = GameMatch::create($matchData);

        return response()->json([
            'success' => true,
            'data' => $match->load(['game', 'firstPlayer', 'secondPlayer']),
            'message' => 'Partida criada com sucesso'
        ]);
    }

    /**
     * Update match
     */
    public function updateMatch(Request $request, GameMatch $match)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|nullable|string|max:255',
            'first_player_id' => 'sometimes|exists:players,id',
            'second_player_id' => 'sometimes|exists:players,id',
            'match_start' => 'sometimes|date',
            'match_end' => 'sometimes|nullable|date',
            'betting_deadline' => 'sometimes|date',
            'first_player_odds' => 'sometimes|nullable|numeric|min:1.01',
            'second_player_odds' => 'sometimes|nullable|numeric|min:1.01',
            'draw_odds' => 'sometimes|nullable|numeric|min:1.01',
            'par_odds' => 'sometimes|nullable|numeric|min:1.01',
            'impar_odds' => 'sometimes|nullable|numeric|min:1.01',
            'first_player_score' => 'sometimes|nullable|integer|min:0',
            'second_player_score' => 'sometimes|nullable|integer|min:0',
            'winner_player_id' => 'sometimes|nullable|exists:players,id',
            'status' => 'sometimes|in:scheduled,live,finished,cancelled,postponed',
            'description' => 'sometimes|nullable|string',
            'featured' => 'sometimes|boolean',
            'result' => 'sometimes|nullable|json',
            'betting_options' => 'sometimes|nullable|json',
            'stream_url' => 'sometimes|nullable|url',
            'banner_image' => 'sometimes|nullable|image|max:4096',
            'banner_button_label' => 'sometimes|nullable|string|max:40',
            'banner_button_link' => 'sometimes|nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $matchData = $request->except(['banner_image', 'banner_button_label', 'banner_button_link', 'stream_url']);
        $matchData['updated_by'] = auth()->id();

        // Parse JSON fields
        if ($request->has('result')) {
            $matchData['result'] = json_decode($request->result, true);
        }

        if ($request->has('betting_options')) {
            $matchData['betting_options'] = json_decode($request->betting_options, true);
        }

        // Handle status changes
        if ($request->has('status')) {
            $this->handleStatusChange($match, $request->status, $matchData);
        }

        $metadata = $match->metadata ?? [];
        if ($request->has('stream_url')) {
            $metadata['stream_url'] = $request->input('stream_url');
        }
        if ($request->hasFile('banner_image')) {
            $metadata['banner_image'] = $request->file('banner_image')->store('matches/banners');
        }
        if ($request->has('banner_button_label')) {
            $metadata['banner_button_label'] = $request->input('banner_button_label');
        }
        if ($request->has('banner_button_link')) {
            $metadata['banner_button_link'] = $request->input('banner_button_link');
        }
        if (!empty($metadata)) {
            $matchData['metadata'] = $metadata;
        }

        $match->update($matchData);

        return response()->json([
            'success' => true,
            'data' => $match->load(['game', 'firstPlayer', 'secondPlayer']),
            'message' => 'Partida atualizada com sucesso'
        ]);
    }

    /**
     * Handle match status changes
     */
    private function handleStatusChange(GameMatch $match, $newStatus, &$matchData)
    {
        switch ($newStatus) {
            case 'live':
                if ($match->status === 'scheduled') {
                    $matchData['actual_start'] = now();
                }
                break;

            case 'finished':
                if (in_array($match->status, ['scheduled', 'live'])) {
                    $matchData['finished_at'] = now();
                    // Process bets resolution will be handled by a job
                }
                break;

            case 'cancelled':
                if (in_array($match->status, ['scheduled', 'live'])) {
                    $matchData['cancelled_at'] = now();
                    // Cancel and refund bets will be handled by a job
                }
                break;
        }
    }

    /**
     * Test Resend email integration
     */
    public function testEmail(\Illuminate\Http\Request $request)
    {
        $admin = \Illuminate\Support\Facades\Auth::guard('admin')->user();
        $apiKey = config('services.resend.api_key', '');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'RESEND_API_KEY não configurada no .env do servidor.'
            ], 422);
        }

        $html = view('emails.bet_confirmed', [
            'userName'     => $admin->name ?? 'Admin',
            'gameName'     => 'Sinuca',
            'player1'      => 'Carlos',
            'player2'      => 'João',
            'matchDate'    => now()->format('d/m/Y H:i'),
            'betLabel'     => 'Carlos',
            'amount'       => '50,00',
            'odds'         => '1,75',
            'potentialWin' => '87,50',
            'betCode'      => 'BETTEST01',
            'appUrl'       => config('app.url', 'https://forapix.com'),
        ])->render();

        $resend = new \App\Services\ResendService();
        $sent = $resend->send(
            $admin->email,
            $admin->name ?? 'Admin',
            '🧪 Teste de email — ForaPix',
            $html
        );

        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => "Email de teste enviado para {$admin->email} com sucesso!"
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Falha ao enviar. Verifique os logs do Laravel (storage/logs/laravel.log).'
        ], 500);
    }

    /**
     * Cancel match and refund all pending bets
     */
    public function cancelMatch(Request $request, GameMatch $match)
    {
        if (in_array($match->status, ['finished', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Esta partida não pode ser cancelada (já encerrada ou cancelada).'
            ], 422);
        }

        $reason = $request->input('reason', 'Partida cancelada pelo administrador');

        DB::beginTransaction();
        try {
            $pendingBets = $match->bets()->where('status', 'pending')->with('user')->get();
            $refundedCount = 0;

            foreach ($pendingBets as $bet) {
                $bet->update([
                    'status'              => 'cancelled',
                    'cancellation_reason' => $reason,
                    'resolved_at'         => now(),
                ]);
                $bet->user->increment('balance', $bet->amount);
                \App\Models\Transaction::create([
                    'user_id'     => $bet->user_id,
                    'type'        => 'refund',
                    'amount'      => $bet->amount,
                    'net_amount'  => $bet->amount,
                    'status'      => 'completed',
                    'description' => "Reembolso — partida #{$match->id} cancelada: {$reason}",
                    'reference'   => $bet->bet_id ?? "bet-{$bet->id}",
                ]);
                $refundedCount++;
            }

            $match->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Partida cancelada com sucesso. {$refundedCount} aposta(s) reembolsada(s).",
                'refunded_count' => $refundedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar partida: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete match
     */
    public function deleteMatch(GameMatch $match)
    {
        // Check if match has bets
        $betsCount = $match->bets()->count();
        
        if ($betsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir partida com apostas'
            ], 400);
        }

        $match->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partida excluída com sucesso'
        ]);
    }

    /**
     * Get match statistics
     */
    public function getMatchStats(GameMatch $match)
    {
        $stats = [
            'total_bets' => $match->bets()->count(),
            'total_amount' => $match->bets()->sum('amount'),
            'first_player_bets' => $match->bets()->where('bet_type', 'first_player')->count(),
            'second_player_bets' => $match->bets()->where('bet_type', 'second_player')->count(),
            'first_player_amount' => $match->bets()->where('bet_type', 'first_player')->sum('amount'),
            'second_player_amount' => $match->bets()->where('bet_type', 'second_player')->sum('amount'),
            'average_bet' => $match->bets()->avg('amount'),
            'largest_bet' => $match->bets()->max('amount'),
            'unique_bettors' => $match->bets()->distinct('user_id')->count('user_id'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Abre ou fecha apostas ao vivo manualmente (toggle)
     */
    public function toggleLiveBetting(Request $request, GameMatch $match)
    {
        if ($match->status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'Apostas ao vivo só podem ser controladas quando a partida está em andamento (status: ao vivo).',
            ], 422);
        }

        if (in_array($match->status, ['finished', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Partida já encerrada.',
            ], 422);
        }

        $opening = !$match->live_betting_open;

        $match->update([
            'live_betting_open'       => $opening,
            'live_betting_opened_at'  => $opening ? now() : $match->live_betting_opened_at,
            'live_betting_closed_at'  => !$opening ? now() : null,
        ]);

        $msg = $opening
            ? '🟢 Apostas ao vivo ABERTAS! Apostadores podem apostar agora.'
            : '🔴 Apostas ao vivo FECHADAS.';

        return response()->json([
            'success'           => true,
            'message'           => $msg,
            'live_betting_open' => $opening,
        ]);
    }

    /**
     * Atualiza o placar de uma partida manualmente.
     * Lógica automática:
     *  - Muda status para 'live' se estava 'scheduled'
     *  - Empate no placar → abre apostas ao vivo automaticamente
     *  - Um jogador na frente → fecha apostas ao vivo
     */
    public function updateScore(Request $request, GameMatch $match)
    {
        $request->validate([
            'first_player_score'  => 'required|integer|min:0|max:999',
            'second_player_score' => 'required|integer|min:0|max:999',
        ]);

        if (in_array($match->status, ['finished', 'cancelled'])) {
            $errorMsg = 'Não é possível atualizar o placar de uma partida encerrada ou cancelada.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return back()->with('error', $errorMsg);
        }

        $s1 = (int) $request->first_player_score;
        $s2 = (int) $request->second_player_score;

        $isTie             = ($s1 === $s2);
        $liveBettingOpen   = $isTie && $match->status !== 'scheduled';
        $newStatus         = $match->status === 'scheduled' ? 'live' : $match->status;

        // Se o jogo acabou de começar (primeiro placar diferente de 0-0), coloca ao vivo
        if ($match->status === 'scheduled' && ($s1 > 0 || $s2 > 0)) {
            $newStatus = 'live';
        }

        // Ao vivo: empate abre apostas, vantagem fecha
        if ($newStatus === 'live') {
            $liveBettingOpen = $isTie;
        }

        $match->update([
            'first_player_score'    => $s1,
            'second_player_score'   => $s2,
            'status'                => $newStatus,
            'live_betting_open'     => $liveBettingOpen,
            'live_betting_opened_at' => ($liveBettingOpen && !$match->live_betting_open) ? now() : $match->live_betting_opened_at,
            'live_betting_closed_at' => (!$liveBettingOpen && $match->live_betting_open) ? now() : $match->live_betting_closed_at,
        ]);

        $msg = "Placar atualizado: {$s1} × {$s2}.";
        if ($isTie && $newStatus === 'live') {
            $msg .= ' Empate detectado — apostas ao vivo ABERTAS automaticamente.';
        } elseif (!$isTie && $match->getOriginal('live_betting_open')) {
            $msg .= ' Apostas ao vivo fechadas (jogador na frente).';
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'match'   => [
                    'status'              => $match->status,
                    'live_betting_open'   => $match->live_betting_open,
                    'first_player_score'  => $s1,
                    'second_player_score' => $s2,
                ],
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Bulk update matches
     */
    public function bulkUpdateMatches(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'match_ids' => 'required|array',
            'match_ids.*' => 'exists:matches,id',
            'action' => 'required|in:activate,deactivate,cancel,feature,unfeature',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $matches = GameMatch::whereIn('id', $request->match_ids);
        $updated = 0;

        switch ($request->action) {
            case 'activate':
                $updated = $matches->update(['status' => 'scheduled']);
                break;
            case 'deactivate':
                $updated = $matches->update(['status' => 'cancelled']);
                break;
            case 'cancel':
                $updated = $matches->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                break;
            case 'feature':
                $updated = $matches->update(['featured' => true]);
                break;
            case 'unfeature':
                $updated = $matches->update(['featured' => false]);
                break;
        }

        return response()->json([
            'success' => true,
            'message' => "{$updated} partidas atualizadas com sucesso"
        ]);
    }
}
