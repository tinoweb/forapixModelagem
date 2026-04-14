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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 12, 2)->default(0.00)->after('password');
            $table->decimal('total_deposited', 12, 2)->default(0.00)->after('balance');
            $table->decimal('total_withdrawn', 12, 2)->default(0.00)->after('total_deposited');
            $table->decimal('total_bet', 12, 2)->default(0.00)->after('total_withdrawn');
            $table->decimal('total_won', 12, 2)->default(0.00)->after('total_bet');
            $table->string('phone', 20)->nullable()->after('email');
            $table->date('birth_date')->nullable()->after('phone');
            $table->string('document', 20)->nullable()->after('birth_date'); // CPF
            $table->string('pix_key')->nullable()->after('document');
            $table->boolean('is_admin')->default(false)->after('pix_key');
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending_verification'])->default('active')->after('is_admin');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->json('metadata')->nullable()->after('last_login_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'balance',
                'total_deposited', 
                'total_withdrawn',
                'total_bet',
                'total_won',
                'phone',
                'birth_date',
                'document',
                'pix_key',
                'is_admin',
                'status',
                'last_login_at',
                'last_login_ip',
                'metadata'
            ]);
        });
    }
};
