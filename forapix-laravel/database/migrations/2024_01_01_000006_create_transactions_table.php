<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 30)->unique(); // Human readable transaction ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Transaction details
            $table->enum('type', ['deposit', 'withdraw', 'bet', 'win', 'refund', 'bonus', 'commission']);
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 10, 2)->default(0.00);
            $table->decimal('net_amount', 12, 2); // amount - fee
            $table->text('description')->nullable();
            
            // Reference to other entities
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable(); // bet, match, etc.
            
            // Status and processing
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('failure_reason')->nullable();
            
            // Payment details (for deposits/withdrawals)
            $table->enum('payment_method', ['pix', 'bank_transfer', 'credit_card', 'system'])->nullable();
            $table->string('payment_reference')->nullable(); // PIX key, bank account, etc.
            $table->string('external_transaction_id')->nullable();
            
            // Balance tracking
            $table->decimal('balance_before', 12, 2)->default(0.00);
            $table->decimal('balance_after', 12, 2)->default(0.00);
            
            // Additional data
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index(['status', 'created_at']);
            $table->index(['reference_id', 'reference_type']);
            $table->index(['transaction_id']);
            $table->index(['external_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
