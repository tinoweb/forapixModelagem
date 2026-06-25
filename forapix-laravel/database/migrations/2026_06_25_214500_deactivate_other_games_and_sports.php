<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Deactivate all games except "Sinuca Par ou Ímpar" (ID: 2)
        DB::table('games')->where('id', '!=', 2)->update(['status' => 'inactive']);

        // Deactivate all sports except "Sinuca" (ID: 6)
        DB::table('sports')->where('id', '!=', 6)->update(['status' => 'inactive']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-activate games
        DB::table('games')->update(['status' => 'active']);

        // Re-activate sports
        DB::table('sports')->update(['status' => 'active']);
    }
};
