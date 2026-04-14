<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Administrador',
            'email' => env('FORAPIX_ADMIN_EMAIL', 'admin@forapix.com'),
            'password' => Hash::make(env('FORAPIX_ADMIN_PASSWORD', 'admin123')),
            'phone' => '+55 11 99999-9999',
            'balance' => 0.00,
            'is_admin' => true,
            'status' => 'active',
            'email_verified_at' => now(),
            'metadata' => [
                'created_by' => 'system',
                'role' => 'super_admin'
            ]
        ]);

        // Create demo user
        User::create([
            'name' => 'Carlos Silva',
            'email' => 'carlos@demo.com',
            'password' => Hash::make('demo123'),
            'phone' => '+55 11 98888-8888',
            'balance' => 100.00,
            'total_deposited' => 100.00,
            'is_admin' => false,
            'status' => 'active',
            'email_verified_at' => now(),
            'pix_key' => 'carlos@demo.com',
            'metadata' => [
                'created_by' => 'system',
                'demo_account' => true
            ]
        ]);

        // Create test users
        $testUsers = [
            [
                'name' => 'Maria Santos',
                'email' => 'maria@test.com',
                'balance' => 50.00,
                'total_deposited' => 50.00,
            ],
            [
                'name' => 'João Oliveira',
                'email' => 'joao@test.com',
                'balance' => 200.00,
                'total_deposited' => 200.00,
            ],
            [
                'name' => 'Ana Costa',
                'email' => 'ana@test.com',
                'balance' => 75.00,
                'total_deposited' => 75.00,
            ]
        ];

        foreach ($testUsers as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('test123'),
                'phone' => '+55 11 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'balance' => $userData['balance'],
                'total_deposited' => $userData['total_deposited'],
                'is_admin' => false,
                'status' => 'active',
                'email_verified_at' => now(),
                'pix_key' => $userData['email'],
                'metadata' => [
                    'created_by' => 'system',
                    'test_account' => true
                ]
            ]);
        }
    }
}
