<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $sinuca = Sport::where('slug', 'sinuca')->first();
        $mma = Sport::where('slug', 'mma-ufc')->first();

        // Criar jogadores de sinuca com fotos
        $sinucaPlayers = collect([
            [
                'name' => 'Igão Parceiro', 
                'sport_id' => $sinuca?->id, 
                'slug' => 'igao-parceiro', 
                'status' => 'active', 
                'rating' => 4.5,
                'photo_url' => 'http://localhost:3000/assets/images/jogador1.png',
                'bio' => 'Jogador profissional de sinuca há 10 anos, especialista em jogadas de defesa.',
                'nationality' => 'BRA',
                'weight' => 75.5,
            ],
            [
                'name' => 'Maycon de Teixeira', 
                'sport_id' => $sinuca?->id, 
                'slug' => 'maycon-teixeira', 
                'status' => 'active', 
                'rating' => 4.8,
                'photo_url' => '/assets/images/jogador2.png',
                'bio' => 'Campeão brasileiro de sinuca 2023, estilo agressivo e preciso.',
                'nationality' => 'BRA',
                'weight' => 72.0,
            ],
            [
                'name' => 'Fábio Cabeludo', 
                'sport_id' => $sinuca?->id, 
                'slug' => 'fabio-cabeludo', 
                'status' => 'active', 
                'rating' => 4.2,
                'photo_url' => 'http://localhost:3000/assets/images/jogador1.png',
                'bio' => 'Conhecido por sua técnica no jogo de par ou ímpar.',
                'nationality' => 'BRA',
                'weight' => 78.0,
            ],
            [
                'name' => 'Diego Sinuqueiro', 
                'sport_id' => $sinuca?->id, 
                'slug' => 'diego-sinuqueiro', 
                'status' => 'active', 
                'rating' => 4.0,
                'photo_url' => '/assets/images/jogador2.png',
                'bio' => 'Jogador versátil, competiu em campeonatos internacionais.',
                'nationality' => 'BRA',
                'weight' => 76.0,
            ],
            [
                'name' => 'Carlos Taco de Ouro', 
                'sport_id' => $sinuca?->id, 
                'slug' => 'carlos-taco-ouro', 
                'status' => 'active', 
                'rating' => 4.7,
                'photo_url' => 'http://localhost:3000/assets/images/jogador1.png',
                'bio' => 'Apelidado de Taco de Ouro pela precisão de suas tacadas.',
                'nationality' => 'BRA',
                'weight' => 74.5,
            ],
            [
                'name' => 'Rafael Bilhar Master', 
                'sport_id' => $sinuca?->id, 
                'slug' => 'rafael-bilhar', 
                'status' => 'active', 
                'rating' => 4.3,
                'photo_url' => '/assets/images/jogador2.png',
                'bio' => 'Especialista em jogos de bilhar, transitando para sinuca.',
                'nationality' => 'BRA',
                'weight' => 73.0,
            ],
        ])->map(fn($data) => Player::updateOrCreate(['slug' => $data['slug']], $data));

        // Criar jogadores de MMA com fotos
        $mmaPlayers = collect([
            [
                'name' => 'Jon Jones', 
                'sport_id' => $mma?->id, 
                'slug' => 'jon-jones', 
                'status' => 'active', 
                'rating' => 5.0,
                'photo_url' => 'http://localhost:3000/assets/images/jogador1.png',
                'bio' => 'Considerado um dos maiores lutadores da história do MMA.',
                'nationality' => 'USA',
                'weight' => 120.2,
            ],
            [
                'name' => 'Stipe Miocic', 
                'sport_id' => $mma?->id, 
                'slug' => 'stipe-miocic', 
                'status' => 'active', 
                'rating' => 4.6,
                'photo_url' => '/assets/images/jogador2.png',
                'bio' => 'Ex-campeão peso-pesado do UFC com background em boxe.',
                'nationality' => 'USA',
                'weight' => 110.5,
            ],
            [
                'name' => 'Alex Poatan', 
                'sport_id' => $mma?->id, 
                'slug' => 'alex-poatan', 
                'status' => 'active', 
                'rating' => 4.9,
                'photo_url' => 'http://localhost:3000/assets/images/jogador1.png',
                'bio' => 'Campeão peso-pesado e meio-pesado do UFC, poderoso nocauteador.',
                'nationality' => 'BRA',
                'weight' => 120.5,
            ],
            [
                'name' => 'Israel Adesanya', 
                'sport_id' => $mma?->id, 
                'slug' => 'israel-adesanya', 
                'status' => 'active', 
                'rating' => 4.7,
                'photo_url' => '/assets/images/jogador2.png',
                'bio' => 'Ex-campeão meio-pesado do UFC, estilo técnico e preciso.',
                'nationality' => 'NGA',
                'weight' => 84.0,
            ],
        ])->map(fn($data) => Player::updateOrCreate(['slug' => $data['slug']], $data));

        // Criar partidas de sinuca
        $sinucaGame = Game::where('slug', 'sinuca-head-to-head')->first();
        $sinucaParImpar = Game::where('slug', 'sinuca-par-impar')->first();

        if ($sinucaGame && $sinucaPlayers->count() >= 6) {
            // Partida agendada (apostas abertas)
            GameMatch::create([
                'game_id' => $sinucaGame->id,
                'title' => 'Sinuca Head to Head',
                'first_player_id' => $sinucaPlayers[0]->id,
                'second_player_id' => $sinucaPlayers[1]->id,
                'first_player_odds' => 1.80,
                'second_player_odds' => 1.90,
                'draw_odds' => 3.50,
                'par_odds' => 1.85,
                'impar_odds' => 1.95,
                'match_start' => now()->addHours(3),
                'betting_deadline' => now()->addHours(2),
                'status' => 'scheduled',
                'featured' => true,
                'description' => 'Confronto emocionante entre Igão Parceiro e Maycon de Teixeira!',
                'metadata' => [
                    'stream_url' => 'https://www.youtube.com/embed/example',
                    'banner_image' => '/assets/images/sinuca-game.png',
                    'banner_button_label' => 'Apostar Agora',
                    'banner_button_link' => '/matches/2',
                ],
            ]);

            // Partida ao vivo
            GameMatch::create([
                'game_id' => $sinucaParImpar->id ?? $sinucaGame->id,
                'title' => 'Sinuca Par ou Ímpar',
                'first_player_id' => $sinucaPlayers[2]->id,
                'second_player_id' => $sinucaPlayers[3]->id,
                'first_player_odds' => 2.10,
                'second_player_odds' => 1.75,
                'draw_odds' => 4.00,
                'par_odds' => 1.90,
                'impar_odds' => 1.90,
                'first_player_score' => 3,
                'second_player_score' => 2,
                'match_start' => now()->subHour(),
                'betting_deadline' => now()->subMinutes(30),
                'status' => 'live',
                'featured' => true,
                'description' => 'Fábio Cabeludo vs Diego Sinuqueiro - Ao vivo!',
                'metadata' => [
                    'stream_url' => 'https://www.youtube.com/embed/live-example',
                    'banner_image' => '/assets/images/sinuca-game.png',
                    'banner_button_label' => 'Assistir Agora',
                    'banner_button_link' => 'https://youtube.com',
                ],
            ]);

            // Partida encerrada
            GameMatch::create([
                'game_id' => $sinucaGame->id,
                'title' => 'Sinuca Head to Head',
                'first_player_id' => $sinucaPlayers[4]->id,
                'second_player_id' => $sinucaPlayers[5]->id,
                'first_player_odds' => 1.65,
                'second_player_odds' => 2.20,
                'draw_odds' => 3.80,
                'par_odds' => 1.85,
                'impar_odds' => 1.95,
                'first_player_score' => 5,
                'second_player_score' => 3,
                'winner_player_id' => $sinucaPlayers[4]->id,
                'match_start' => now()->subDays(1),
                'match_end' => now()->subDays(1)->addHours(2),
                'betting_deadline' => now()->subDays(1)->subHour(),
                'status' => 'finished',
                'featured' => false,
                'total_bets_count' => 12,
                'total_bets_amount' => 1500.00,
                'description' => 'Carlos venceu com autoridade!',
                'metadata' => [
                    'banner_image' => '/assets/images/sinuca-game.png',
                ],
            ]);
        }

        // Criar partida de MMA
        $ufcGame = Game::where('slug', 'ufc-head-to-head')->first();
        if ($ufcGame && $mmaPlayers->count() >= 4) {
            GameMatch::create([
                'game_id' => $ufcGame->id,
                'title' => 'UFC Head to Head',
                'first_player_id' => $mmaPlayers[0]->id,
                'second_player_id' => $mmaPlayers[1]->id,
                'first_player_odds' => 1.55,
                'second_player_odds' => 2.40,
                'match_start' => now()->addDays(2),
                'betting_deadline' => now()->addDays(2)->subHour(),
                'status' => 'scheduled',
                'featured' => true,
                'description' => 'Jon Jones vs Stipe Miocic - O grande confronto!',
                'metadata' => [
                    'banner_image' => 'https://placehold.co/600x300/dc2626/ffffff?text=UFC+Scheduled',
                    'banner_button_label' => 'Apostar Agora',
                    'banner_button_link' => '/matches/5',
                ],
            ]);

            GameMatch::create([
                'game_id' => $ufcGame->id,
                'title' => 'UFC Head to Head',
                'first_player_id' => $mmaPlayers[2]->id,
                'second_player_id' => $mmaPlayers[3]->id,
                'first_player_odds' => 1.70,
                'second_player_odds' => 2.10,
                'first_player_score' => 2,
                'second_player_score' => 0,
                'winner_player_id' => $mmaPlayers[2]->id,
                'match_start' => now()->subDays(3),
                'match_end' => now()->subDays(3)->addHours(1),
                'betting_deadline' => now()->subDays(3)->subHour(),
                'status' => 'finished',
                'featured' => false,
                'total_bets_count' => 45,
                'total_bets_amount' => 8500.00,
                'description' => 'Poatan nocauteou Adesanya no 2º round!',
                'metadata' => [
                    'banner_image' => 'https://placehold.co/600x300/4b5563/ffffff?text=UFC+Finished',
                ],
            ]);
        }

        // Criar usuário de teste
        User::firstOrCreate(
            ['email' => 'demo@forapix.com'],
            [
                'name' => 'Usuário Demo',
                'password' => Hash::make('password'),
                'balance' => 500.00,
                'status' => 'active',
            ]
        );

        $this->command->info('✅ Dados de demonstração criados com sucesso!');
        $this->command->info('📧 Usuário demo: demo@forapix.com / password');
    }
}
