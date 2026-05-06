<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'game_id',
        'title',
        'description',
        'first_player_id',
        'second_player_id',
        'first_player_odds',
        'second_player_odds',
        'draw_odds',
        'par_odds',
        'impar_odds',
        'betting_deadline',
        'match_start',
        'match_end',
        'status',
        'result',
        'winner_player_id',
        'first_player_score',
        'second_player_score',
        'external_id',
        'external_source',
        'metadata',
        'featured',
        'total_bets_amount',
        'total_bets_count'
    ];

    protected $casts = [
        'first_player_odds' => 'decimal:2',
        'second_player_odds' => 'decimal:2',
        'draw_odds' => 'decimal:2',
        'par_odds' => 'decimal:2',
        'impar_odds' => 'decimal:2',
        'betting_deadline' => 'datetime',
        'match_start' => 'datetime',
        'match_end' => 'datetime',
        'metadata' => 'array',
        'featured' => 'boolean',
        'total_bets_amount' => 'decimal:2',
    ];

    protected $appends = [
        'time_remaining'
    ];

    /**
     * Get the game that owns this match
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the first player
     */
    public function firstPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'first_player_id');
    }

    /**
     * Get the second player
     */
    public function secondPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'second_player_id');
    }

    /**
     * Get all bets for this match
     */
    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class, 'match_id');
    }

    /**
     * Get pending bets only
     */
    public function pendingBets(): HasMany
    {
        return $this->bets()->where('status', 'pending');
    }

    /**
     * Scope for active matches
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'live']);
    }

    /**
     * Scope for live matches
     */
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    /**
     * Scope for upcoming matches
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
            ->where('betting_deadline', '>', now());
    }

    /**
     * Scope for finished matches
     */
    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    /**
     * Scope for featured matches
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Check if betting is still open
     */
    public function isBettingOpen(): bool
    {
        return in_array($this->status, ['scheduled', 'live']) && 
               $this->betting_deadline > now();
    }

    /**
     * Check if match is live
     */
    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    /**
     * Check if match is finished
     */
    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    /**
     * Get time remaining for betting
     */
    public function getTimeRemainingAttribute(): ?string
    {
        if (!$this->isBettingOpen()) {
            return null;
        }

        $diff = now()->diff($this->betting_deadline);
        
        if ($diff->days > 0) {
            return $diff->days . ' dias';
        } elseif ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'min';
        } else {
            return $diff->i . ' minutos';
        }
    }

    /**
     * Get odds for a specific bet type
     */
    public function getOddsForBetType(string $betType): ?float
    {
        return match($betType) {
            'first_player' => $this->first_player_odds,
            'second_player' => $this->second_player_odds,
            'draw' => $this->draw_odds,
            'par' => $this->par_odds,
            'impar' => $this->impar_odds,
            default => null
        };
    }

    /**
     * Calculate potential win for a bet
     */
    public function calculatePotentialWin(string $betType, float $amount): float
    {
        $odds = $this->getOddsForBetType($betType);
        return $odds ? $amount * $odds : 0;
    }

    /**
     * Resolve match and process bets
     */
    public function resolveMatch(string $result, array $scores = []): void
    {
        $this->update([
            'status' => 'finished',
            'result' => $result,
            'match_end' => now(),
            'first_player_score' => $scores['first_player'] ?? $this->first_player_score,
            'second_player_score' => $scores['second_player'] ?? $this->second_player_score,
        ]);

        // Process all pending bets
        $this->pendingBets()->each(function ($bet) use ($result) {
            $bet->resolve($result);
        });
    }

    /**
     * Update bet statistics
     */
    public function updateBetStats(): void
    {
        $stats = $this->bets()->selectRaw('
            COUNT(*) as count,
            SUM(amount) as total_amount
        ')->first();

        $this->update([
            'total_bets_count' => $stats->count ?? 0,
            'total_bets_amount' => $stats->total_amount ?? 0,
        ]);
    }
}
