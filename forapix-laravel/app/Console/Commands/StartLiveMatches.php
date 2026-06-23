<?php

namespace App\Console\Commands;

use App\Models\GameMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StartLiveMatches extends Command
{
    protected $signature   = 'matches:start-live';
    protected $description = 'Altera o status de partidas agendadas para ao vivo se o horário de início foi atingido';

    public function handle(): int
    {
        $now = now();

        $matches = GameMatch::where('status', 'scheduled')
            ->where('match_start', '<=', $now)
            ->get();

        if ($matches->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("Iniciando transição para ao vivo de {$matches->count()} partida(s)...");

        foreach ($matches as $match) {
            $this->line("  → Partida #{$match->id} ({$match->title}): agendada para {$match->match_start->format('d/m/Y H:i')}");

            $match->update([
                'status'                => 'live',
                'live_betting_open'     => true,
                'live_betting_opened_at' => now(),
            ]);

            $this->info("     ✅ Partida #{$match->id} agora está AO VIVO!");
            Log::info("StartLiveMatches: partida #{$match->id} alterada para ao vivo", [
                'title' => $match->title,
                'match_start' => $match->match_start,
            ]);
        }

        return self::SUCCESS;
    }
}
