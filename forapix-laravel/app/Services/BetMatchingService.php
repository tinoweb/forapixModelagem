<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\GameMatch;
use App\Models\Transaction;
use App\Services\ResendService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BetMatchingService — Motor de Apostas Casadas (Peer-to-Peer)
 *
 * Responsável por toda a lógica financeira do sistema:
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  CASAMENTO (matchBet)                                           │
 * │  Nova aposta chega → busca opostos pendentes (FIFO) → casa      │
 * │  proporcionalmente até zerar o valor ou esgotar opostos.        │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  RESOLUÇÃO (resolveMatch)                                       │
 * │  Pool casado total → Casa retém 10% → 90% vai para vencedores  │
 * │  proporcionalmente ao matched_amount de cada um.                │
 * │  Parte não casada (amount - matched_amount) é sempre devolvida. │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  CANCELAMENTO DE PARTIDA (cancelMatch)                          │
 * │  Admin cancela partida → 100% devolvido a todos, casados ou não.│
 * └─────────────────────────────────────────────────────────────────┘
 *
 * @see BETTING_RULES.md para documentação completa das regras de negócio.
 */
class BetMatchingService
{
    /**
     * Taxa retida pela casa sobre o pool casado ao encerrar a partida.
     * 0.10 = 10%
     */
    const HOUSE_CUT_PCT = 0.10;

    private const OPPOSING = [
        'first_player'  => 'second_player',
        'second_player' => 'first_player',
    ];

    // ─── CASAMENTO ────────────────────────────────────────────────────────────

    /**
     * Tenta casar a nova aposta com apostas pendentes do lado oposto.
     *
     * ALGORITMO FIFO (First In, First Out):
     *  1. Determina o tipo oposto (first_player ↔ second_player).
     *  2. Busca apostas opostas com saldo não casado (matched_amount < amount),
     *     ordenadas pela mais antiga (placed_at ASC).
     *  3. Itera e casa proporcionalmente até esgotar o valor da nova aposta
     *     ou não haver mais opostas disponíveis.
     *
     * CASAMENTO PARCIAL:
     *  - Uma aposta pode ser casada em múltiplas rodadas com diferentes apostadores.
     *  - matched_amount cresce gradualmente; amount permanece fixo.
     *  - Parte não casada (amount − matched_amount) é devolvida no encerramento.
     *
     * EXEMPLO:
     *  João: R$100 no J1 (matched=0) → Pedro: R$50 no J2
     *  → João fica matched=50, pendente=50
     *  → Pedro fica matched=50, pendente=0 (100% casado)
     *
     * @param  Bet  $newBet  Aposta recém-criada para tentar casar.
     * @return Bet           Aposta com matched_amount atualizado.
     */
    public function matchBet(Bet $newBet): Bet
    {
        $opposingType = self::OPPOSING[$newBet->bet_type] ?? null;

        if (!$opposingType) {
            Log::warning("BetMatchingService: tipo sem oposição — {$newBet->bet_type}");
            return $newBet;
        }

        $matchedOpposingIds = [];

        DB::transaction(function () use ($newBet, $opposingType, &$matchedOpposingIds) {
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

                // Registra para notificar depois da transação
                $matchedOpposingIds[] = $opposing->id;
            }

            // Atualiza matched_amount da nova aposta
            $matched = (float) $newBet->amount - $remainingToMatch;
            $newBet->matched_amount = $matched;
            $newBet->save();
        });

        $freshBet = $newBet->fresh();

        // Notifica os apostadores opostos que tiveram suas apostas (parcialmente) casadas
        if (!empty($matchedOpposingIds)) {
            $this->notifyMatchedBettors($matchedOpposingIds);
        }

        // Notifica o próprio apostador sobre o resultado do casamento da nova aposta
        $this->notifyNewBettor($freshBet);

        return $freshBet;
    }

    // ─── RESOLUÇÃO ────────────────────────────────────────────────────────────

    /**
     * Encerra uma partida e distribui os valores.
     *
     * FÓRMULA DE PAGAMENTO:
     *  pool_total    = SUM(matched_amount) de todos os apostadores
     *  taxa_casa     = pool_total × 10%
     *  pool_ganhos   = pool_total × 90%
     *
     *  Para cada VENCEDOR:
     *    payout = (matched_amount_individual / total_matched_lado_vencedor) × pool_ganhos
     *    + devolução da parcela não casada (amount − matched_amount)
     *
     *  Para cada PERDEDOR:
     *    → matched_amount vai para o pool (perdido)
     *    → amount − matched_amount é devolvido (nunca estava em risco)
     *
     * CASO EXTREMO — sem casamentos:
     *  Se nenhuma aposta foi casada (totalMatchedPool = 0),
     *  100% é devolvido a todos sem cobrar taxa.
     *
     * @param  GameMatch  $match          Partida a encerrar.
     * @param  string     $winningBetType Tipo vencedor: 'first_player' ou 'second_player'.
     * @return array  ['processed', 'refunded', 'house_cut', 'winner_pool']
     */
    public function resolveMatch(GameMatch $match, string $winningBetType): array
    {
        $bets = $match->bets()->where('status', 'pending')->with('user')->get();

        if ($bets->isEmpty()) {
            return ['processed' => 0, 'refunded' => 0, 'house_cut' => 0, 'winner_pool' => 0];
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
            return ['processed' => 0, 'refunded' => $bets->count(), 'house_cut' => 0, 'winner_pool' => 0];
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

                if ((float) $bet->matched_amount <= 0) {
                    // Nunca casada — reembolso integral independente do lado
                    $this->refundFull($bet, 'Aposta não casada — reembolso integral');
                    $refunded++;
                } elseif ($bet->bet_type === $winningBetType) {
                    // Vencedor com parte casada: recebe proporção do pool + devolução não casada
                    $payout = $totalWinnerMatched > 0
                        ? round(((float) $bet->matched_amount / $totalWinnerMatched) * $winnerPool, 2)
                        : 0;

                    $this->payWinner($bet, $payout, $unmatched);
                    $processed++;
                } else {
                    // Perdedor com parte casada: registra como perdida
                    $bet->update([
                        'status'        => 'lost',
                        'result_amount' => 0,
                        'resolved_at'   => now(),
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
     * Cancela uma partida inteira e reembolsa 100% de TODOS os apostadores.
     *
     * Diferente do encerramento normal, o cancelamento ignora o casamento:
     *  - Apostadores com matched_amount > 0 recebem o amount completo de volta.
     *  - Nenhuma taxa é cobrada.
     *  - Todas as apostas pendentes passam para status 'cancelled'.
     *
     * Este método é exclusivo para uso administrativo.
     *
     * @param  GameMatch  $match   Partida a cancelar.
     * @param  string     $reason  Motivo do cancelamento (gravado em cada aposta).
     * @return int                 Quantidade de apostas reembolsadas.
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

    // ─── NOTIFICAÇÕES DE CASAMENTO ────────────────────────────────────────────

    /**
     * Envia email para apostadores opostos que tiveram matched_amount atualizado.
     * Chamado após a transação de casamento ser confirmada.
     */
    private function notifyMatchedBettors(array $betIds): void
    {
        $bets = Bet::whereIn('id', $betIds)
            ->with(['user', 'match.game', 'match.firstPlayer', 'match.secondPlayer'])
            ->get();

        foreach ($bets as $bet) {
            try {
                $this->sendMatchedEmail($bet);
            } catch (\Throwable $e) {
                Log::error("BetMatchingService: falha ao enviar email de casamento para aposta #{$bet->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Notifica o apostador que acabou de colocar a aposta sobre o resultado do casamento.
     * Só envia se algum valor foi casado (matched_amount > 0).
     */
    private function notifyNewBettor(Bet $bet): void
    {
        if ((float) $bet->matched_amount <= 0) return;

        try {
            $bet->load(['user', 'match.game', 'match.firstPlayer', 'match.secondPlayer']);
            $this->sendMatchedEmail($bet);
        } catch (\Throwable $e) {
            Log::error("BetMatchingService: falha ao notificar novo apostador #{$bet->id}: " . $e->getMessage());
        }
    }

    /**
     * Monta e envia o email de casamento para um apostador.
     */
    private function sendMatchedEmail(Bet $bet): void
    {
        $user  = $bet->user;
        $match = $bet->match;

        if (!$user || !$match) return;

        $total    = (float) $bet->amount;
        $matched  = (float) $bet->matched_amount;
        $unmatched = $total - $matched;

        $betLabels = [
            'first_player'  => $match->firstPlayer?->name ?? 'Jogador 1',
            'second_player' => $match->secondPlayer?->name ?? 'Jogador 2',
        ];

        $html = view('emails.bet_matched', [
            'userName'        => $user->name,
            'player1'         => explode(' ', $match->firstPlayer?->name ?? 'Jogador 1')[0],
            'player2'         => explode(' ', $match->secondPlayer?->name ?? 'Jogador 2')[0],
            'betLabel'        => $betLabels[$bet->bet_type] ?? $bet->bet_type,
            'betCode'         => $bet->bet_id ?? '#' . $bet->id,
            'totalAmount'     => number_format($total,   2, ',', '.'),
            'matchedAmount'   => number_format($matched,   2, ',', '.'),
            'unmatchedAmount' => number_format($unmatched, 2, ',', '.'),
            'isFullyMatched'  => $matched >= $total && $total > 0,
            'appUrl'          => config('app.url', 'https://apostacasada.net'),
        ])->render();

        (new ResendService())->send(
            $user->email,
            $user->name,
            $matched >= $total
                ? '🎉 Aposta confirmada — ApostaCasada'
                : '✅ Aposta parcialmente casada — ApostaCasada',
            $html
        );
    }

    // ─── HELPERS PRIVADOS ─────────────────────────────────────────────────────

    private function payWinner(Bet $bet, float $payout, float $unmatched): void
    {
        $totalCredit   = $payout + $unmatched;
        $balanceBefore = (float) $bet->user->balance;

        $bet->user->increment('balance', $totalCredit);
        $bet->user->increment('total_won', $payout);
        // Todo o valor creditado (prêmio + parte não casada) volta/fica disponível para saque
        $bet->user->increment('withdrawable_balance', $totalCredit);

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
        $bet->user->increment('withdrawable_balance', $amount);

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
        $bet->user->increment('withdrawable_balance', $amount);

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
     * Retorna estatísticas em tempo real do pool da partida.
     *
     * Usado pelo frontend e pelo painel admin para exibir:
     *  - Quantas apostas e quanto dinheiro há em cada lado
     *  - Quanto já está efetivamente casado (em disputa)
     *  - Quanto ainda está pendente (não casado)
     *  - Estimativa da taxa da casa e do pool de ganhos
     *
     * @param  GameMatch  $match
     * @return array  {
     *   first_player:  { count, total, matched, unmatched }
     *   second_player: { count, total, matched, unmatched }
     *   total_matched_pool, house_cut, winner_pool
     * }
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
