<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'photo_url',
        'sport_id',
        'bio',
        'birth_date',
        'nationality',
        'weight',
        'height',
        'stats',
        'rating',
        'status'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'weight' => 'decimal:2',
        'rating' => 'decimal:2',
        'stats' => 'array'
    ];

    /**
     * Get the sport that owns this player
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Get matches where this player is the first player
     */
    public function firstPlayerMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'first_player_id');
    }

    /**
     * Get matches where this player is the second player
     */
    public function secondPlayerMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'second_player_id');
    }

    /**
     * Get all matches for this player
     */
    public function allMatches()
    {
        return GameMatch::where('first_player_id', $this->id)
            ->orWhere('second_player_id', $this->id);
    }

    /**
     * Scope for active players
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for players by sport
     */
    public function scopeBySport($query, $sportId)
    {
        return $query->where('sport_id', $sportId);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get player's win rate
     */
    public function getWinRateAttribute(): float
    {
        $stats = $this->stats ?? [];
        $wins = $stats['wins'] ?? 0;
        $total = ($stats['wins'] ?? 0) + ($stats['losses'] ?? 0) + ($stats['draws'] ?? 0);
        
        return $total > 0 ? round(($wins / $total) * 100, 2) : 0;
    }

    /**
     * Get player's age
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    /**
     * Update player stats
     */
    public function updateStats(string $result): void
    {
        $stats = $this->stats ?? ['wins' => 0, 'losses' => 0, 'draws' => 0];
        
        if (isset($stats[$result])) {
            $stats[$result]++;
            $this->update(['stats' => $stats]);
        }
    }
}
