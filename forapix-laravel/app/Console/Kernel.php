<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Reconcilia depósitos pendentes há mais de 5 minutos a cada 5 minutos
        $schedule->command('deposits:reconcile --minutes=5')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Altera partidas agendadas para ao vivo automaticamente quando o horário de início for atingido
        $schedule->command('matches:start-live')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
