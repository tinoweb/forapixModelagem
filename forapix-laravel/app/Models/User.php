<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'birth_date',
        'document',
        'pix_key',
        'balance',
        'total_deposited',
        'total_withdrawn',
        'total_bet',
        'total_won',
        'is_admin',
        'status',
        'last_login_at',
        'last_login_ip',
        'two_factor_secret',
        'two_factor_enabled',
        'metadata'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'total_bet' => 'decimal:2',
        'total_won' => 'decimal:2',
        'is_admin' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'last_login_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Get all bets for this user
     */
    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Get all transactions for this user
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get pending bets
     */
    public function pendingBets(): HasMany
    {
        return $this->bets()->where('status', 'pending');
    }

    /**
     * Get won bets
     */
    public function wonBets(): HasMany
    {
        return $this->bets()->where('status', 'won');
    }

    /**
     * Get lost bets
     */
    public function lostBets(): HasMany
    {
        return $this->bets()->where('status', 'lost');
    }

    /**
     * Get completed transactions
     */
    public function completedTransactions(): HasMany
    {
        return $this->transactions()->where('status', 'completed');
    }

    /**
     * Get deposits
     */
    public function deposits(): HasMany
    {
        return $this->transactions()->where('type', 'deposit');
    }

    /**
     * Get withdrawals
     */
    public function withdrawals(): HasMany
    {
        return $this->transactions()->where('type', 'withdraw');
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user can bet
     */
    public function canBet(): bool
    {
        return $this->isActive() && $this->balance > 0;
    }

    /**
     * Check if user can withdraw
     */
    public function canWithdraw(): bool
    {
        return $this->isActive() && $this->balance > 0 && $this->pix_key;
    }

    /**
     * Get user's age
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    /**
     * Get user's profit/loss
     */
    public function getProfitLossAttribute(): float
    {
        return $this->total_won - $this->total_bet;
    }

    /**
     * Get user's win rate
     */
    public function getWinRateAttribute(): float
    {
        $totalBets = $this->bets()->whereIn('status', ['won', 'lost'])->count();
        $wonBets = $this->wonBets()->count();
        
        return $totalBets > 0 ? round(($wonBets / $totalBets) * 100, 2) : 0;
    }

    /**
     * Update user login information
     */
    public function updateLoginInfo(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress
        ]);
    }

    /**
     * Add balance to user account
     */
    public function addBalance(float $amount, string $description = null): Transaction
    {
        $balanceBefore = $this->balance;
        $this->increment('balance', $amount);
        
        return Transaction::create([
            'user_id' => $this->id,
            'type' => 'deposit',
            'amount' => $amount,
            'net_amount' => $amount,
            'description' => $description ?? 'Depósito',
            'status' => 'completed',
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'processed_at' => now()
        ]);
    }

    /**
     * Subtract balance from user account
     */
    public function subtractBalance(float $amount, string $description = null): Transaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Saldo insuficiente');
        }

        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        
        return Transaction::create([
            'user_id' => $this->id,
            'type' => 'withdraw',
            'amount' => $amount,
            'net_amount' => $amount,
            'description' => $description ?? 'Saque',
            'status' => 'completed',
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'processed_at' => now()
        ]);
    }

    /**
     * Place a bet
     */
    public function placeBet(GameMatch $match, string $betType, float $amount): Bet
    {
        if (!$this->canBet()) {
            throw new \Exception('Usuário não pode apostar');
        }

        if ($this->balance < $amount) {
            throw new \Exception('Saldo insuficiente');
        }

        if (!$match->isBettingOpen()) {
            throw new \Exception('Apostas fechadas para esta partida');
        }

        // Sistema pool — odds e potential_win são estimativas, payout real calculado ao encerrar
        $odds         = 0;
        $potentialWin = 0;

        // Deduct balance
        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        $this->increment('total_bet', $amount);

        // Create bet
        $bet = Bet::create([
            'user_id' => $this->id,
            'match_id' => $match->id,
            'bet_type' => $betType,
            'amount' => $amount,
            'odds' => $odds,
            'potential_win' => $potentialWin,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        // Create transaction
        Transaction::create([
            'user_id' => $this->id,
            'type' => 'bet',
            'amount' => $amount,
            'net_amount' => $amount,
            'description' => "Aposta {$bet->bet_id}",
            'reference_id' => $bet->id,
            'reference_type' => 'bet',
            'status' => 'completed',
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'processed_at' => now()
        ]);

        // Update match statistics
        $match->updateBetStats();

        return $bet;
    }
}
