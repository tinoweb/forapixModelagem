<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'name',
        'slug',
        'type',
        'image',
        'description',
        'min_bet',
        'max_bet',
        'house_edge',
        'status',
        'settings'
    ];

    protected $casts = [
        'min_bet' => 'decimal:2',
        'max_bet' => 'decimal:2',
        'house_edge' => 'decimal:4',
        'settings' => 'array'
    ];

    /**
     * Get the sport that owns this game
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Get all matches for this game
     */
    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    /**
     * Get active matches only
     */
    public function activeMatches(): HasMany
    {
        return $this->matches()->whereIn('status', ['scheduled', 'live']);
    }

    /**
     * Get live matches only
     */
    public function liveMatches(): HasMany
    {
        return $this->matches()->where('status', 'live');
    }

    /**
     * Get upcoming matches
     */
    public function upcomingMatches(): HasMany
    {
        return $this->matches()
            ->where('status', 'scheduled')
            ->where('betting_deadline', '>', now());
    }

    /**
     * Scope for active games
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for game type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Check if betting is allowed for this game
     */
    public function isBettingAllowed(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Validate bet amount
     */
    public function isValidBetAmount(float $amount): bool
    {
        return $amount >= $this->min_bet && $amount <= $this->max_bet;
    }
}
