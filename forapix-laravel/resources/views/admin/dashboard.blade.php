@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Visão geral')

@section('content')
    @php
        $iconColors = [
            'blue'   => ['bg' => 'bg-blue-500/15',   'txt' => 'text-blue-300'],
            'purple' => ['bg' => 'bg-accent/15',      'txt' => 'text-accent-light'],
            'red'    => ['bg' => 'bg-red-500/15',    'txt' => 'text-red-300'],
            'green'  => ['bg' => 'bg-success/15',     'txt' => 'text-success-light'],
            'orange' => ['bg' => 'bg-gold/15',        'txt' => 'text-gold-light'],
            'yellow' => ['bg' => 'bg-yellow-500/15',  'txt' => 'text-yellow-300'],
        ];
    @endphp

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="glass-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Usuários</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($stats['users']['total'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-success-light text-xs mt-2">
                        <i class="fas fa-arrow-up"></i>
                        +{{ $stats['users']['new_today'] ?? 0 }} hoje · {{ $stats['users']['active'] ?? 0 }} ativos
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-500/15 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-blue-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="glass-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Apostas</p>
                    <p class="text-3xl font-bold mt-1">R$ {{ number_format($stats['bets']['total_amount'] ?? 0, 2, ',', '.') }}</p>
                    <p class="text-accent-light text-xs mt-2">
                        <i class="fas fa-bolt"></i>
                        R$ {{ number_format($stats['bets']['today_amount'] ?? 0, 2, ',', '.') }} hoje
                    </p>
                </div>
                <div class="w-12 h-12 bg-accent/15 rounded-xl flex items-center justify-center">
                    <i class="fas fa-dice text-accent-light text-xl"></i>
                </div>
            </div>
        </div>

        <div class="glass-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Partidas</p>
                    <p class="text-3xl font-bold mt-1">{{ $stats['matches']['live'] ?? 0 }} <span class="text-sm text-gray-500 font-normal">ao vivo</span></p>
                    <p class="text-gold-light text-xs mt-2">
                        <i class="fas fa-clock"></i>
                        {{ $stats['matches']['scheduled'] ?? 0 }} agendadas · {{ $stats['matches']['finished_today'] ?? 0 }} encerradas hoje
                    </p>
                </div>
                <div class="w-12 h-12 bg-red-500/15 rounded-xl flex items-center justify-center">
                    <i class="fas fa-broadcast-tower text-red-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="glass-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Lucro da casa</p>
                    <p class="text-3xl font-bold mt-1">R$ {{ number_format($stats['revenue']['house_profit'] ?? 0, 2, ',', '.') }}</p>
                    <p class="text-success-light text-xs mt-2">
                        <i class="fas fa-chart-line"></i>
                        Acumulado do período
                    </p>
                </div>
                <div class="w-12 h-12 bg-success/15 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-success-light text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 text-sm">
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Depósitos</p>
            <p class="text-xl font-bold mt-1">R$ {{ number_format($stats['transactions']['deposits'] ?? 0, 2, ',', '.') }}</p>
            <p class="text-success-light text-xs">Hoje: R$ {{ number_format($stats['transactions']['today_deposits'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Saques</p>
            <p class="text-xl font-bold mt-1">R$ {{ number_format($stats['transactions']['withdrawals'] ?? 0, 2, ',', '.') }}</p>
            <p class="text-gold-light text-xs">Hoje: R$ {{ number_format($stats['transactions']['today_withdrawals'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Apostas pendentes</p>
            <p class="text-xl font-bold mt-1">{{ $stats['bets']['pending'] ?? 0 }}</p>
            <p class="text-gray-500 text-xs">Ganhas: {{ $stats['bets']['won'] ?? 0 }} · Perdidas: {{ $stats['bets']['lost'] ?? 0 }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider">Jogos ativos</p>
            <p class="text-xl font-bold mt-1">{{ $stats['games']['active'] ?? 0 }}</p>
            <p class="text-gray-500 text-xs">Total cadastrados: {{ $stats['games']['total'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
        <div class="glass-card p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Receita dos últimos 7 dias</h3>
                <span class="badge badge-info">R$ {{ number_format(array_sum($chartData['revenue']['values'] ?? []), 2, ',', '.') }}</span>
            </div>
            <div class="relative" style="height: 280px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <div class="glass-card p-6">
            <h3 class="text-lg font-semibold mb-4">Apostas por jogo</h3>
            @if(!empty($chartData['games']['labels']))
                <div class="relative" style="height: 280px;">
                    <canvas id="betsChart"></canvas>
                </div>
            @else
                <div class="text-center py-10 text-gray-500 text-sm">
                    <i class="fas fa-dice text-3xl mb-2 opacity-50"></i>
                    <p>Sem apostas registradas ainda.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold">Atividade recente</h3>
            <span class="badge badge-muted">{{ count($recentActivities) }} eventos</span>
        </div>

        @forelse($recentActivities as $act)
            @php
                $c = $iconColors[$act['color']] ?? $iconColors['purple'];
            @endphp
            <div class="flex items-center gap-4 p-4 bg-[#10152b] rounded-xl border border-white/5 mb-3 last:mb-0">
                <div class="w-10 h-10 {{ $c['bg'] }} rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas {{ $act['icon'] }} {{ $c['txt'] }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm">{{ $act['title'] }}</p>
                    <p class="text-gray-400 text-sm truncate">{{ $act['description'] }}</p>
                </div>
                <span class="text-gray-500 text-xs whitespace-nowrap">
                    {{ optional($act['created_at'])->diffForHumans() ?? '--' }}
                </span>
            </div>
        @empty
            <div class="text-center py-10 text-gray-500">
                <i class="fas fa-inbox text-3xl mb-2 opacity-60"></i>
                <p class="text-sm">Nenhuma atividade recente ainda.</p>
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const revenueData = @json($chartData['revenue'] ?? ['labels' => [], 'values' => []]);
        const betsData = @json($chartData['games'] ?? ['labels' => [], 'values' => []]);

        const revenueCanvas = document.getElementById('revenueChart');
        if (revenueCanvas) {
            new Chart(revenueCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: revenueData.labels,
                    datasets: [{
                        label: 'Receita (R$)',
                        data: revenueData.values,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.15)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#fbbf24',
                        pointBorderColor: '#fff',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: '#cbd5e1' } }
                    },
                    scales: {
                        y: {
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(148,163,184,0.08)' }
                        },
                        x: {
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(148,163,184,0.05)' }
                        }
                    }
                }
            });
        }

        const betsCanvas = document.getElementById('betsChart');
        if (betsCanvas && betsData.labels.length) {
            new Chart(betsCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: betsData.labels,
                    datasets: [{
                        data: betsData.values,
                        backgroundColor: ['#f59e0b', '#ef4444', '#22c55e', '#7c3aed', '#3b82f6', '#ec4899']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#cbd5e1', padding: 14, boxWidth: 12 }
                        }
                    }
                }
            });
        }
    </script>
@endpush
