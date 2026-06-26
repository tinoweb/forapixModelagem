<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\GameMatch;
use App\Services\BetMatchingService;
use App\Services\ResendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BetController extends Controller
{
    /**
     * Place a bet on a match
     */
    public function store(Request $request)
    {
        $rawMatchId = $request->input('match_id');
        if (!is_numeric($rawMatchId) && !empty($rawMatchId)) {
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($rawMatchId);
            if (!empty($decoded)) {
                $request->merge(['match_id' => $decoded[0]]);
            }
        }

        $validated = $request->validate([
            'match_id' => 'required|exists:matches,id',
            'bet_type' => 'required|in:first_player,second_player',
            'amount'   => 'required|numeric|min:10'
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
            $faltam = $validated['amount'] - $user->balance;
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível realizar esta ação: Saldo insuficiente para apostar (Valor R$ ' .
                    number_format($validated['amount'], 2, ',', '.') . ', faltam R$ ' .
                    number_format($faltam, 2, ',', '.') . ')'
            ], 422);
        }

        // Validate bet amount limits
        if ($match->game && !$match->game->isValidBetAmount($validated['amount'])) {
            return response()->json([
                'success' => false,
                'message' => "Valor da aposta deve ser entre R$ " . number_format($match->game->min_bet, 2) . " e R$ " . number_format($match->game->max_bet, 2)
            ], 422);
        }

        try {
            DB::beginTransaction();

            $bet = $user->placeBet($match, $validated['bet_type'], $validated['amount']);

            DB::commit();

            // Casamento automático com apostas do lado oposto (FIFO)
            $bet = (new BetMatchingService())->matchBet($bet);

            // Disparar email de confirmação (não bloqueia a resposta)
            try {
                $this->sendBetConfirmationEmail($user, $bet, $match);
            } catch (\Throwable $e) {
                Log::error('Erro ao enviar email de aposta: ' . $e->getMessage());
            }

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
     * Send bet confirmation email via Resend
     */
    private function sendBetConfirmationEmail($user, $bet, $match): void
    {
        $betLabels = [
            'first_player'  => $match->firstPlayer?->name ?? 'Jogador 1',
            'second_player' => $match->secondPlayer?->name ?? 'Jogador 2',
            'draw'          => 'Empate',
            'par'           => 'Par',
            'impar'         => 'Ímpar',
        ];

        $html = view('emails.bet_confirmed', [
            'userName'     => $user->name,
            'gameName'     => $match->game?->name ?? 'Sinuca',
            'player1'      => explode(' ', $match->firstPlayer?->name ?? 'Jogador 1')[0],
            'player2'      => explode(' ', $match->secondPlayer?->name ?? 'Jogador 2')[0],
            'matchDate'    => optional($match->match_start)->format('d/m/Y H:i') ?? '--',
            'betLabel'     => $betLabels[$bet->bet_type] ?? $bet->bet_type,
            'amount'       => number_format($bet->amount, 2, ',', '.'),
            'odds'         => number_format($bet->odds, 2, ',', '.'),
            'potentialWin' => number_format($bet->potential_win, 2, ',', '.'),
            'betCode'      => $bet->bet_id ?? "#" . $bet->id,
            'appUrl'       => config('app.url', 'https://apostacasada.net'),
        ])->render();

        (new ResendService())->send(
            $user->email,
            $user->name,
            '✅ Aposta confirmada — ApostaCasada',
            $html
        );
    }

    /**
     * Get user's bets
     */
    public function index(Request $request)
    {
        $rawMatchId = $request->input('match_id');
        if (!is_numeric($rawMatchId) && !empty($rawMatchId)) {
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($rawMatchId);
            if (!empty($decoded)) {
                $request->merge(['match_id' => $decoded[0]]);
            }
        }

        $query = Bet::with(['match.game', 'match.firstPlayer', 'match.secondPlayer'])
            ->where('user_id', $request->user()->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by match
        if ($request->has('match_id')) {
            $query->where('match_id', $request->match_id);
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
