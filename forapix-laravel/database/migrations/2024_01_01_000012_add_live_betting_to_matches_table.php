<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->boolean('live_betting_open')->default(false)->after('betting_deadline');
            $table->timestamp('live_betting_opened_at')->nullable()->after('live_betting_open');
            $table->timestamp('live_betting_closed_at')->nullable()->after('live_betting_opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['live_betting_open', 'live_betting_opened_at', 'live_betting_closed_at']);
        });
    }
};
