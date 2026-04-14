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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('photo_url')->nullable();
            $table->foreignId('sport_id')->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 3)->nullable(); // ISO country code
            $table->decimal('weight', 5, 2)->nullable();
            $table->integer('height')->nullable(); // in cm
            $table->json('stats')->nullable(); // wins, losses, draws, etc.
            $table->decimal('rating', 6, 2)->default(1000.00);
            $table->enum('status', ['active', 'inactive', 'retired'])->default('active');
            $table->timestamps();
            
            $table->index(['sport_id', 'status']);
            $table->index(['rating']);
            $table->index(['slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
