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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['head_to_head', 'casino', 'bingo', 'sinuca', 'par_impar'])->default('head_to_head');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->decimal('min_bet', 10, 2)->default(1.00);
            $table->decimal('max_bet', 10, 2)->default(10000.00);
            $table->decimal('house_edge', 5, 4)->default(0.0500); // 5%
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index(['sport_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
