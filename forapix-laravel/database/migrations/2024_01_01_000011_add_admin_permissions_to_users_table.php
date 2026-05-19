<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // null = Super Admin (sem restrições)
            // [] = sem nenhuma permissão ainda (operador sem acesso)
            // ['manage_matches', ...] = operador com permissões específicas
            $table->json('admin_permissions')->nullable()->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_permissions');
        });
    }
};
