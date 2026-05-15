<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Bet extends Model
{
    use HasFactory;

    protected $fillable = [
        'bet_id',
        'user_id',
        'match_id',
        'bet_type',
        'amount',
        'matched_amount',
        'odds',
        'potential_win',
        'status',
        'result_amount',
        'cancellation_reason',
        'placed_at',
        'resolved_at',
        'ip_address',
        'user_agent',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'matched_amount' => 'decimal:2',
        'odds' => 'decimal:2',
        'potential_win' => 'decimal:2',
        'result_amount' => 'decimal:2',
        'placed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bet) {
            if (!$bet->bet_id) {
                $bet->bet_id = static::generateBetId();
            }
            if (!$bet->placed_at) {
                $bet->placed_at = now();
            }
        });
    }

    /**
     * Get the user that owns this bet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the match for this bet
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    /**
     * Scope for pending bets
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for won bets
     */
    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    /**
     * Scope for lost bets
     */
    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    /**
     * Scope for user bets
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for match bets
     */
    public function scopeForMatch($query, $matchId)
    {
        return $query->where('match_id', $matchId);
    }

    /**
     * Generate unique bet ID
     */
    public static function generateBetId(): string
    {
        do {
            $id = 'BET' . strtoupper(Str::random(8));
        } while (static::where('bet_id', $id)->exists());

        return $id;
    }

    /**
     * Check if bet is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if bet is won
     */
    public function isWon(): bool
    {
        return $this->status === 'won';
    }

    /**
     * Check if bet is lost
     */
    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    /**
     * Check if bet can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return $this->isPending() && $this->match->isBettingOpen();
    }

    /**
     * Resolve bet based on match result
     */
    public function resolve(string $matchResult): void
    {
        if (!$this->isPending()) {
            return;
        }

        $isWinner = $this->bet_type === $matchResult;
        $status = $isWinner ? 'won' : 'lost';
        $resultAmount = $isWinner ? $this->potential_win : 0;

        $this->update([
            'status' => $status,
            'result_amount' => $resultAmount,
            'resolved_at' => now()
        ]);

        // Update user balance if won
        if ($isWinner) {
            $this->user->increment('balance', $resultAmount);
            $this->user->increment('total_won', $resultAmount);

            // Create transaction record
            Transaction::create([
                'user_id' => $this->user_id,
                'type' => 'win',
                'amount' => $resultAmount,
                'net_amount' => $resultAmount,
                'description' => "Ganho da aposta {$this->bet_id}",
                'reference_id' => $this->id,
                'reference_type' => 'bet',
                'status' => 'completed',
                'balance_before' => $this->user->balance - $resultAmount,
                'balance_after' => $this->user->balance,
                'processed_at' => now()
            ]);
        }
    }

    /**
     * Cancel bet and refund user
     */
    public function cancel(string $reason = null): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'resolved_at' => now()
        ]);

        // Refund user
        $this->user->increment('balance', $this->amount);

        // Create refund transaction
        Transaction::create([
            'user_id' => $this->user_id,
            'type' => 'refund',
            'amount' => $this->amount,
            'net_amount' => $this->amount,
            'description' => "Reembolso da aposta {$this->bet_id}",
            'reference_id' => $this->id,
            'reference_type' => 'bet',
            'status' => 'completed',
            'balance_before' => $this->user->balance - $this->amount,
            'balance_after' => $this->user->balance,
            'processed_at' => now()
        ]);

        return true;
    }

    /**
     * Get bet type label
     */
    public function getBetTypeLabel(): string
    {
        return match($this->bet_type) {
            'first_player' => $this->match->firstPlayer?->name ?? 'Jogador 1',
            'second_player' => $this->match->secondPlayer?->name ?? 'Jogador 2',
            'draw' => 'Empate',
            'par' => 'Par',
            'impar' => 'Ímpar',
            default => 'Desconhecido'
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'won' => 'Ganhou',
            'lost' => 'Perdeu',
            'cancelled' => 'Cancelada',
            'refunded' => 'Reembolsada',
            default => 'Desconhecido'
        };
    }
}
