<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\GameMatch;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BetMatchingService — Aposta Casada
 *
 * Regras:
 *  - Apostas no mesmo lado ficam PENDENTES até serem casadas com o lado oposto.
 *  - Ao receber nova aposta, casa automaticamente com o lado oposto (FIFO).
 *  - Ao encerrar: vencedores recebem 90% do pool casado (proporcionalmente).
 *  - Valor não casado é devolvido ao apostador independente do resultado.
 *  - Ao cancelar: devolução total de 100% para todos.
 */
class BetMatchingService
{
    const HOUSE_CUT_PCT = 0.10;

    private const OPPOSING = [
        'first_player'  => 'second_player',
        'second_player' => 'first_player',
    ];

    // ─── CASAMENTO ────────────────────────────────────────────────────────────

    /**
     * Tenta casar a nova aposta com apostas pendentes do lado oposto.
     * Retorna a aposta atualizada com matched_amount preenchido.
     */
    public function matchBet(Bet $newBet): Bet
    {
        $opposingType = self::OPPOSING[$newBet->bet_type] ?? null;

        if (!$opposingType) {
            Log::warning("BetMatchingService: tipo sem oposição — {$newBet->bet_type}");
            return $newBet;
        }

        DB::transaction(function () use ($newBet, $opposingType) {
            $remainingToMatch = (float) $newBet->amount;

            // Pega apostas opostas com saldo não casado (mais antigas primeiro)
            $opposingBets = Bet::where('match_id', $newBet->match_id)
                ->where('bet_type', $opposingType)
                ->where('status', 'pending')
                ->whereRaw('matched_amount < amount')
                ->orderBy('placed_at', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($opposingBets as $opposing) {
                if ($remainingToMatch <= 0) break;

                $available = (float) $opposing->amount - (float) $opposing->matched_amount;
                $canMatch  = min($available, $remainingToMatch);

                $opposing->increment('matched_amount', $canMatch);
                $remainingToMatch -= $canMatch;
            }

            // Atualiza matched_amount da nova aposta
            $matched = (float) $newBet->amount - $remainingToMatch;
            $newBet->matched_amount = $matched;
            $newBet->save();
        });

        return $newBet->fresh();
    }

    // ─── RESOLUÇÃO ────────────────────────────────────────────────────────────

    /**
     * Encerra uma partida: paga vencedores (pool casado ×90%), devolve não casados.
     */
    public function resolveMatch(GameMatch $match, string $winningBetType): array
    {
        $bets = $match->bets()->where('status', 'pending')->with('user')->get();

        if ($bets->isEmpty()) {
            return ['processed' => 0, 'refunded' => 0, 'house_cut' => 0];
        }

        // Pool total casado = soma dos matched_amount dos DOIS lados
        $totalMatchedPool = (float) $bets->sum('matched_amount');

        // Se não há nenhum casamento, devolve tudo
        if ($totalMatchedPool <= 0) {
            DB::transaction(function () use ($bets) {
                foreach ($bets as $bet) {
                    $this->refundFull($bet, 'Partida encerrada sem apostas casadas');
                }
            });
            return ['processed' => 0, 'refunded' => $bets->count(), 'house_cut' => 0];
        }

        $houseCut    = round($totalMatchedPool * self::HOUSE_CUT_PCT, 2);
        $winnerPool  = round($totalMatchedPool - $houseCut, 2);

        $winnerBets        = $bets->where('bet_type', $winningBetType);
        $totalWinnerMatched = (float) $winnerBets->sum('matched_amount');

        $processed = 0;
        $refunded  = 0;

        DB::transaction(function () use (
            $bets, $winningBetType, $winnerPool, $totalWinnerMatched, &$processed, &$refunded
        ) {
            foreach ($bets as $bet) {
                $unmatched = (float) $bet->amount - (float) $bet->matched_amount;

                if ($bet->bet_type === $winningBetType && (float) $bet->matched_amount > 0) {
                    // Vencedor: recebe proporção do pool
                    $payout = $totalWinnerMatched > 0
                        ? round(((float) $bet->matched_amount / $totalWinnerMatched) * $winnerPool, 2)
                        : 0;

                    $this->payWinner($bet, $payout, $unmatched);
                    $processed++;
                } else {
                    // Perdedor: só registra como perdida
                    $bet->update([
                        'status'       => 'lost',
                        'result_amount' => 0,
                        'resolved_at'  => now(),
                    ]);
                    $processed++;

                    // Devolve parte não casada (se houver)
                    if ($unmatched > 0) {
                        $this->refundUnmatched($bet, $unmatched);
                        $refunded++;
                    }
                }
            }
        });

        Log::info("BetMatchingService: partida #{$match->id} encerrada", [
            'winning_type'        => $winningBetType,
            'total_matched_pool'  => $totalMatchedPool,
            'house_cut'           => $houseCut,
            'winner_pool'         => $winnerPool,
            'processed'           => $processed,
        ]);

        return [
            'processed'  => $processed,
            'refunded'   => $refunded,
            'house_cut'  => $houseCut,
            'winner_pool' => $winnerPool,
        ];
    }

    // ─── CANCELAMENTO ─────────────────────────────────────────────────────────

    /**
     * Cancela partida: devolve 100% para todos os apostadores (casados e não casados).
     */
    public function cancelMatch(GameMatch $match, string $reason = 'Partida cancelada'): int
    {
        $bets = $match->bets()->where('status', 'pending')->with('user')->get();

        DB::transaction(function () use ($bets, $reason) {
            foreach ($bets as $bet) {
                $this->refundFull($bet, $reason);
            }
        });

        Log::info("BetMatchingService: partida #{$match->id} cancelada — {$bets->count()} apostas devolvidas");

        return $bets->count();
    }

    // ─── HELPERS PRIVADOS ─────────────────────────────────────────────────────

    private function payWinner(Bet $bet, float $payout, float $unmatched): void
    {
        $totalCredit   = $payout + $unmatched;
        $balanceBefore = (float) $bet->user->balance;

        $bet->user->increment('balance', $totalCredit);
        $bet->user->increment('total_won', $payout);

        $bet->update([
            'status'        => 'won',
            'result_amount' => $payout,
            'resolved_at'   => now(),
        ]);

        Transaction::create([
            'user_id'        => $bet->user_id,
            'type'           => 'win',
            'amount'         => $payout,
            'net_amount'     => $payout,
            'description'    => "Ganho aposta {$bet->bet_id} (pool casado)",
            'reference_id'   => $bet->id,
            'reference_type' => 'bet',
            'status'         => 'completed',
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceBefore + $totalCredit,
            'processed_at'   => now(),
        ]);

        if ($unmatched > 0) {
            Transaction::create([
                'user_id'        => $bet->user_id,
                'type'           => 'refund',
                'amount'         => $unmatched,
                'net_amount'     => $unmatched,
                'description'    => "Devolução não casado — aposta {$bet->bet_id}",
                'reference_id'   => $bet->id,
                'reference_type' => 'bet',
                'status'         => 'completed',
                'balance_before' => $balanceBefore + $payout,
                'balance_after'  => $balanceBefore + $totalCredit,
                'processed_at'   => now(),
            ]);
        }
    }

    private function refundUnmatched(Bet $bet, float $amount): void
    {
        $balanceBefore = (float) $bet->user->balance;
        $bet->user->increment('balance', $amount);

        Transaction::create([
            'user_id'        => $bet->user_id,
            'type'           => 'refund',
            'amount'         => $amount,
            'net_amount'     => $amount,
            'description'    => "Devolução não casado — aposta {$bet->bet_id}",
            'reference_id'   => $bet->id,
            'reference_type' => 'bet',
            'status'         => 'completed',
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceBefore + $amount,
            'processed_at'   => now(),
        ]);
    }

    private function refundFull(Bet $bet, string $reason): void
    {
        $amount        = (float) $bet->amount;
        $balanceBefore = (float) $bet->user->balance;

        $bet->user->increment('balance', $amount);

        $bet->update([
            'status'               => 'cancelled',
            'cancellation_reason'  => $reason,
            'result_amount'        => 0,
            'resolved_at'          => now(),
        ]);

        Transaction::create([
            'user_id'        => $bet->user_id,
            'type'           => 'refund',
            'amount'         => $amount,
            'net_amount'     => $amount,
            'description'    => "Devolução: {$reason} (aposta {$bet->bet_id})",
            'reference_id'   => $bet->id,
            'reference_type' => 'bet',
            'status'         => 'completed',
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceBefore + $amount,
            'processed_at'   => now(),
        ]);
    }

    // ─── UTILIDADE PÚBLICA ────────────────────────────────────────────────────

    /**
     * Retorna estatísticas do pool da partida para exibição no frontend/admin.
     */
    public function getMatchStats(GameMatch $match): array
    {
        $rows = $match->bets()
            ->where('status', 'pending')
            ->selectRaw('bet_type, COUNT(*) as count, SUM(amount) as total, SUM(matched_amount) as matched')
            ->groupBy('bet_type')
            ->get()
            ->keyBy('bet_type');

        $fp = $rows->get('first_player');
        $sp = $rows->get('second_player');

        $totalMatched = ($fp ? (float)$fp->matched : 0) + ($sp ? (float)$sp->matched : 0);
        $houseCut     = round($totalMatched * self::HOUSE_CUT_PCT, 2);

        return [
            'first_player'  => [
                'count'     => $fp ? (int)$fp->count    : 0,
                'total'     => $fp ? (float)$fp->total  : 0.0,
                'matched'   => $fp ? (float)$fp->matched: 0.0,
                'unmatched' => $fp ? (float)$fp->total - (float)$fp->matched : 0.0,
            ],
            'second_player' => [
                'count'     => $sp ? (int)$sp->count    : 0,
                'total'     => $sp ? (float)$sp->total  : 0.0,
                'matched'   => $sp ? (float)$sp->matched: 0.0,
                'unmatched' => $sp ? (float)$sp->total - (float)$sp->matched : 0.0,
            ],
            'total_matched_pool' => $totalMatched,
            'house_cut'          => $houseCut,
            'winner_pool'        => round($totalMatched - $houseCut, 2),
        ];
    }
}
