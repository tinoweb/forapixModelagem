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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            
            // Players
            $table->foreignId('first_player_id')->nullable()->constrained('players')->onDelete('cascade');
            $table->foreignId('second_player_id')->nullable()->constrained('players')->onDelete('cascade');
            
            // Odds
            $table->decimal('first_player_odds', 5, 2)->default(1.00);
            $table->decimal('second_player_odds', 5, 2)->default(1.00);
            $table->decimal('draw_odds', 5, 2)->nullable();
            
            // Special odds for games like sinuca
            $table->decimal('par_odds', 5, 2)->nullable();
            $table->decimal('impar_odds', 5, 2)->nullable();
            
            // Timing
            $table->timestamp('betting_deadline');
            $table->timestamp('match_start');
            $table->timestamp('match_end')->nullable();
            
            // Status and Results
            $table->enum('status', ['scheduled', 'live', 'finished', 'cancelled', 'postponed'])->default('scheduled');
            $table->enum('result', ['first_player', 'second_player', 'draw', 'par', 'impar'])->nullable();
            
            // Scores
            $table->integer('first_player_score')->default(0);
            $table->integer('second_player_score')->default(0);
            
            // External API reference
            $table->string('external_id')->nullable();
            $table->string('external_source')->nullable(); // sispts, manual, etc.
            
            // Additional data
            $table->json('metadata')->nullable();
            $table->boolean('featured')->default(false);
            $table->decimal('total_bets_amount', 12, 2)->default(0.00);
            $table->integer('total_bets_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['game_id', 'status']);
            $table->index(['status', 'betting_deadline']);
            $table->index(['featured', 'status']);
            $table->index(['external_id', 'external_source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
