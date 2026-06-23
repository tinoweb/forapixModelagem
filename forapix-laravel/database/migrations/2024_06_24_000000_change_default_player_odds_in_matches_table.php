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
        Schema::table('matches', function (Blueprint $table) {
            $table->decimal('first_player_odds', 5, 2)->default(1.80)->change();
            $table->decimal('second_player_odds', 5, 2)->default(1.80)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->decimal('first_player_odds', 5, 2)->default(1.00)->change();
            $table->decimal('second_player_odds', 5, 2)->default(1.00)->change();
        });
    }
};
