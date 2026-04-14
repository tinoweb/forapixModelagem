<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mma = Sport::where('slug', 'mma-ufc')->first();
        $sinuca = Sport::where('slug', 'sinuca')->first();
        $futebol = Sport::where('slug', 'futebol')->first();

        $games = [
            // MMA Games
            [
                'sport_id' => $mma->id,
                'name' => 'UFC Head to Head',
                'slug' => 'ufc-head-to-head',
                'type' => 'head_to_head',
                'description' => 'Apostas em confrontos diretos do UFC',
                'min_bet' => 1.00,
                'max_bet' => 10000.00,
                'house_edge' => 0.05,
                'status' => 'active',
                'settings' => [
                    'allow_draw' => false,
                    'live_betting' => true
                ]
            ],
            
            // Sinuca Games
            [
                'sport_id' => $sinuca->id,
                'name' => 'Sinuca Par ou Ímpar',
                'slug' => 'sinuca-par-impar',
                'type' => 'par_impar',
                'description' => 'Aposte se o resultado será par ou ímpar',
                'min_bet' => 1.00,
                'max_bet' => 5000.00,
                'house_edge' => 0.03,
                'status' => 'active',
                'settings' => [
                    'auto_resolve' => true,
                    'max_duration' => 30 // minutes
                ]
            ],
            [
                'sport_id' => $sinuca->id,
                'name' => 'Sinuca Head to Head',
                'slug' => 'sinuca-head-to-head',
                'type' => 'head_to_head',
                'description' => 'Apostas em confrontos diretos de sinuca',
                'min_bet' => 1.00,
                'max_bet' => 5000.00,
                'house_edge' => 0.04,
                'status' => 'active',
                'settings' => [
                    'allow_draw' => false,
                    'live_betting' => true
                ]
            ],

            // Casino Games
            [
                'sport_id' => null,
                'name' => 'Cassino Online',
                'slug' => 'cassino-online',
                'type' => 'casino',
                'description' => 'Jogos de cassino variados',
                'min_bet' => 0.50,
                'max_bet' => 50000.00,
                'house_edge' => 0.02,
                'status' => 'active',
                'settings' => [
                    'games' => ['slots', 'blackjack', 'roulette'],
                    'instant_play' => true
                ]
            ],

            // Bingo
            [
                'sport_id' => null,
                'name' => 'Bingo Online',
                'slug' => 'bingo-online',
                'type' => 'bingo',
                'description' => 'Bingo com prêmios em dinheiro',
                'min_bet' => 1.00,
                'max_bet' => 1000.00,
                'house_edge' => 0.10,
                'status' => 'active',
                'settings' => [
                    'max_players' => 100,
                    'draw_interval' => 5 // minutes
                ]
            ]
        ];

        foreach ($games as $game) {
            Game::create($game);
        }
    }
}
