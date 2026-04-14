@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-primary rounded-2xl p-6 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Total de Usuários</p>
                            <p class="text-3xl font-bold">{{ $stats['users']['total'] ?? 0 }}</p>
                            <p class="text-green-400 text-sm">
                                <i class="fas fa-arrow-up"></i>
                                +{{ $stats['users']['new_today'] ?? 0 }} hoje
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-users text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Bets -->
                <div class="bg-primary rounded-2xl p-6 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Total Apostas</p>
                            <p class="text-3xl font-bold">R$ {{ number_format($stats['bets']['total_amount'] ?? 0, 2, ',', '.') }}</p>
                            <p class="text-green-400 text-sm">
                                <i class="fas fa-arrow-up"></i>
                                R$ {{ number_format($stats['bets']['today_amount'] ?? 0, 2, ',', '.') }} hoje
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-dice text-purple-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Live Matches -->
                <div class="bg-primary rounded-2xl p-6 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Partidas Ao Vivo</p>
                            <p class="text-3xl font-bold">{{ $stats['matches']['live'] ?? 0 }}</p>
                            <p class="text-yellow-400 text-sm">
                                <i class="fas fa-clock"></i>
                                {{ $stats['matches']['scheduled'] ?? 0 }} aguardando
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-broadcast-tower text-red-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="bg-primary rounded-2xl p-6 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Lucro da Casa</p>
                            <p class="text-3xl font-bold">R$ {{ number_format($stats['revenue']['house_profit'] ?? 0, 2, ',', '.') }}</p>
                            <p class="text-green-400 text-sm">
                                <i class="fas fa-percentage"></i>
                                Margem: 5%
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-chart-line text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Revenue Chart -->
                <div class="bg-primary rounded-2xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold mb-4">Receita dos Últimos 7 Dias</h3>
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>

                <!-- Bets Chart -->
                <div class="bg-primary rounded-2xl p-6 border border-gray-700">
                    <h3 class="text-lg font-semibold mb-4">Apostas por Tipo de Jogo</h3>
                    <canvas id="betsChart" width="400" height="200"></canvas>
                </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-primary rounded-2xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold mb-4">Atividade Recente</h3>
        <div class="space-y-4">
            <div class="flex items-center gap-4 p-4 bg-secondary rounded-xl">
                <div class="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-plus text-green-400"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold">Novo usuário registrado</p>
                    <p class="text-gray-400 text-sm">Carlos Silva se registrou na plataforma</p>
                </div>
                <span class="text-gray-400 text-sm">2 min atrás</span>
            </div>

            <div class="flex items-center gap-4 p-4 bg-secondary rounded-xl">
                <div class="w-10 h-10 bg-purple-500/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-dice text-purple-400"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold">Nova aposta realizada</p>
                    <p class="text-gray-400 text-sm">João apostou R$ 50,00 em Sinuca - Par</p>
                </div>
                <span class="text-gray-400 text-sm">5 min atrás</span>
            </div>

            <div class="flex items-center gap-4 p-4 bg-secondary rounded-xl">
                <div class="w-10 h-10 bg-blue-500/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-arrow-down text-blue-400"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold">Depósito aprovado</p>
                    <p class="text-gray-400 text-sm">Maria depositou R$ 100,00 via PIX</p>
                </div>
                <span class="text-gray-400 text-sm">8 min atrás</span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Receita (R$)',
                    data: [1200, 1900, 3000, 2500, 2200, 3000, 2800],
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#ffffff'
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: '#9ca3af'
                        },
                        grid: {
                            color: 'rgba(156, 163, 175, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#9ca3af'
                        },
                        grid: {
                            color: 'rgba(156, 163, 175, 0.1)'
                        }
                    }
                }
            }
        });

        // Bets Chart
        const betsCtx = document.getElementById('betsChart').getContext('2d');
        new Chart(betsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Sinuca', 'MMA', 'Futebol', 'Cassino'],
                datasets: [{
                    data: [35, 25, 20, 20],
                    backgroundColor: [
                        '#f59e0b',
                        '#ef4444',
                        '#10b981',
                        '#7c3aed'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#ffffff',
                            padding: 20
                        }
                    }
                }
            }
        });
    </script>
@endpush
