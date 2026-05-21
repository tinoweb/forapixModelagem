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
     * Retorna a URL absoluta da foto do jogador, compatível com:
     * - uploads diretos em public/uploads/ (novo padrão)
     * - storage disk public (players/filename.jpg)
     * - URLs absolutas já salvas no banco
     * - fallback para avatar gerado
     */
    public function getPhotoAttribute(): string
    {
        $url = $this->photo_url;

        if (!$url) {
            return 'https://i.pravatar.cc/150?u=' . $this->id;
        }

        // Já é URL absoluta
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // Salvo via rota Laravel /uploads/{path}
        if (str_starts_with($url, 'uploads/')) {
            return self::buildUploadUrl($url);
        }

        // Salvo via Storage disk public (players/filename.jpg)
        return \Illuminate\Support\Facades\Storage::disk('public')->url($url);
    }

    /**
     * Constroi URL de upload que funciona em local e produção.
     * Usa o contexto da requisição atual para gerar URL base correta.
     */
    private static function buildUploadUrl(string $path): string
    {
        $request = request();
        if ($request) {
            $baseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl();
            return $baseUrl . '/uploads/' . substr($path, strlen('uploads/'));
        }
        // Fallback para contexto sem request (jobs/CLI)
        return config('app.url') . '/uploads/' . substr($path, strlen('uploads/'));
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
