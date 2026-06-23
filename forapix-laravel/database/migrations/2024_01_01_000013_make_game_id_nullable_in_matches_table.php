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
            $table->dropForeign(['game_id']);
            $table->unsignedBigInteger('game_id')->nullable()->change();
            $table->foreign('game_id')->references('id')->on('games')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->unsignedBigInteger('game_id')->nullable(false)->change();
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });
    }
};
