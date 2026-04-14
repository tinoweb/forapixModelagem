<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sports = [
            [
                'name' => 'MMA/UFC',
                'slug' => 'mma-ufc',
                'icon' => 'fa-hand-fist',
                'status' => 'active',
                'metadata' => [
                    'description' => 'Mixed Martial Arts e Ultimate Fighting Championship',
                    'popular' => true
                ]
            ],
            [
                'name' => 'Futebol',
                'slug' => 'futebol',
                'icon' => 'fa-futbol',
                'status' => 'active',
                'metadata' => [
                    'description' => 'Futebol nacional e internacional',
                    'popular' => true
                ]
            ],
            [
                'name' => 'Basquete',
                'slug' => 'basquete',
                'icon' => 'fa-basketball',
                'status' => 'active',
                'metadata' => [
                    'description' => 'NBA, NBB e outras ligas',
                    'popular' => false
                ]
            ],
            [
                'name' => 'Tênis',
                'slug' => 'tenis',
                'icon' => 'fa-baseball',
                'status' => 'active',
                'metadata' => [
                    'description' => 'ATP, WTA e Grand Slams',
                    'popular' => false
                ]
            ],
            [
                'name' => 'Boxe',
                'slug' => 'boxe',
                'icon' => 'fa-hand-fist',
                'status' => 'active',
                'metadata' => [
                    'description' => 'Boxe profissional mundial',
                    'popular' => false
                ]
            ],
            [
                'name' => 'Sinuca',
                'slug' => 'sinuca',
                'icon' => 'fa-8ball',
                'status' => 'active',
                'metadata' => [
                    'description' => 'Jogos de sinuca e bilhar',
                    'popular' => true
                ]
            ]
        ];

        foreach ($sports as $sport) {
            Sport::create($sport);
        }
    }
}
