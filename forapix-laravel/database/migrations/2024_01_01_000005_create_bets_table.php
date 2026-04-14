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
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->string('bet_id', 20)->unique(); // Human readable bet ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('match_id')->constrained()->onDelete('cascade');
            
            // Bet details
            $table->enum('bet_type', ['first_player', 'second_player', 'draw', 'par', 'impar']);
            $table->decimal('amount', 10, 2);
            $table->decimal('odds', 5, 2);
            $table->decimal('potential_win', 10, 2);
            
            // Status and resolution
            $table->enum('status', ['pending', 'won', 'lost', 'cancelled', 'refunded'])->default('pending');
            $table->decimal('result_amount', 10, 2)->default(0.00);
            $table->text('cancellation_reason')->nullable();
            
            // Timestamps
            $table->timestamp('placed_at');
            $table->timestamp('resolved_at')->nullable();
            
            // Additional info
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['match_id', 'status']);
            $table->index(['status', 'placed_at']);
            $table->index(['bet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
