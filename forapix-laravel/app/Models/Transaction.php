<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'type',
        'amount',
        'fee',
        'net_amount',
        'description',
        'reference_id',
        'reference_type',
        'status',
        'failure_reason',
        'payment_method',
        'payment_reference',
        'external_transaction_id',
        'balance_before',
        'balance_after',
        'metadata',
        'ip_address',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (!$transaction->transaction_id) {
                $transaction->transaction_id = static::generateTransactionId();
            }
        });
    }

    /**
     * Get the user that owns this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related bet (if applicable)
     */
    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class, 'reference_id')
            ->where('reference_type', 'bet');
    }

    /**
     * Scope for deposits
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    /**
     * Scope for withdrawals
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdraw');
    }

    /**
     * Scope for bets
     */
    public function scopeBets($query)
    {
        return $query->where('type', 'bet');
    }

    /**
     * Scope for wins
     */
    public function scopeWins($query)
    {
        return $query->where('type', 'win');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for user transactions
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId(): string
    {
        $prefix = match(request()->input('type', 'TXN')) {
            'deposit' => 'DEP',
            'withdraw' => 'WTH',
            'bet' => 'BET',
            'win' => 'WIN',
            'refund' => 'REF',
            'bonus' => 'BON',
            default => 'TXN'
        };

        do {
            $id = $prefix . date('Ymd') . strtoupper(Str::random(6));
        } while (static::where('transaction_id', $id)->exists());

        return $id;
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now()
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'processed_at' => now()
        ]);
    }

    /**
     * Get type label
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'deposit' => 'Depósito',
            'withdraw' => 'Saque',
            'bet' => 'Aposta',
            'win' => 'Ganho',
            'refund' => 'Reembolso',
            'bonus' => 'Bônus',
            'commission' => 'Comissão',
            default => 'Transação'
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'processing' => 'Processando',
            'completed' => 'Concluída',
            'failed' => 'Falhou',
            'cancelled' => 'Cancelada',
            default => 'Desconhecido'
        };
    }

    /**
     * Get payment method label
     */
    public function getPaymentMethodLabel(): string
    {
        return match($this->payment_method) {
            'pix' => 'PIX',
            'bank_transfer' => 'Transferência Bancária',
            'credit_card' => 'Cartão de Crédito',
            'system' => 'Sistema',
            default => 'Não informado'
        };
    }

    /**
     * Check if transaction affects balance positively
     */
    public function isCredit(): bool
    {
        return in_array($this->type, ['deposit', 'win', 'refund', 'bonus']);
    }

    /**
     * Check if transaction affects balance negatively
     */
    public function isDebit(): bool
    {
        return in_array($this->type, ['withdraw', 'bet', 'commission']);
    }
}
