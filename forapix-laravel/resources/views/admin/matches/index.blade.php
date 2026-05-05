@extends('admin.layouts.app')

@section('title', 'Partidas')
@section('breadcrumb', 'Partidas / Listagem')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Operações</p>
                <h2 class="text-2xl font-semibold">Agenda de partidas</h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.matches.create') }}" class="admin-btn-primary">
                    <i class="fas fa-plus"></i> Agendar partida
                </a>
                <a href="{{ route('admin.matches.index') }}" class="admin-btn-ghost" title="Atualizar">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </div>

        <!-- Stats rápidas -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @php
                $statsCounts = [
                    'scheduled' => $matches->getCollection()->where('status','scheduled')->count(),
                    'live' => $matches->getCollection()->where('status','live')->count(),
                    'finished' => $matches->getCollection()->where('status','finished')->count(),
                    'cancelled' => $matches->getCollection()->where('status','cancelled')->count(),
                ];
            @endphp
            <div class="glass-card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Agendadas</p>
                <p class="text-2xl font-bold text-blue-300">{{ $statsCounts['scheduled'] }}</p>
            </div>
            <div class="glass-card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Ao vivo</p>
                <p class="text-2xl font-bold text-success-light">{{ $statsCounts['live'] }}</p>
            </div>
            <div class="glass-card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Encerradas</p>
                <p class="text-2xl font-bold text-gold-light">{{ $statsCounts['finished'] }}</p>
            </div>
            <div class="glass-card p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wider">Canceladas</p>
                <p class="text-2xl font-bold text-red-300">{{ $statsCounts['cancelled'] }}</p>
            </div>
        </div>

        <!-- Filtros + Listagem -->
        <div class="glass-card p-6">
            <form method="GET" class="grid md:grid-cols-5 gap-3 mb-6 text-sm">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por jogador" class="input-admin md:col-span-2">
                <select name="game_id" class="input-admin">
                    <option value="">Todos os jogos</option>
                    @foreach($games as $game)
                        <option value="{{ $game->id }}" @selected(request('game_id') == $game->id)>{{ $game->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="input-admin">
                    <option value="">Todos os status</option>
                    @foreach(['scheduled' => 'Agendadas', 'live' => 'Ao vivo', 'finished' => 'Encerradas', 'cancelled' => 'Canceladas'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="admin-btn-primary flex-1 justify-center">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    @if(request()->hasAny(['search','game_id','status','date_from','date_to']))
                        <a href="{{ route('admin.matches.index') }}" class="admin-btn-ghost" title="Limpar filtros">
                            <i class="fas fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </form>

            <div class="space-y-3">
                @forelse($matches as $match)
                    @php
                        $statusMap = [
                            'scheduled' => ['label' => 'Agendada', 'class' => 'badge-info'],
                            'live' => ['label' => 'Ao vivo', 'class' => 'badge-success'],
                            'finished' => ['label' => 'Encerrada', 'class' => 'badge-warning'],
                            'cancelled' => ['label' => 'Cancelada', 'class' => 'badge-danger'],
                            'postponed' => ['label' => 'Adiada', 'class' => 'badge-muted'],
                        ];
                        $st = $statusMap[$match->status] ?? ['label' => $match->status, 'class' => 'badge-muted'];
                    @endphp
                    <div class="bg-[#10152b] border border-white/5 rounded-2xl p-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between hover:border-white/10 transition">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="text-[11px] uppercase tracking-[0.2em] text-gray-500">{{ $match->game->name ?? 'Jogo' }}</span>
                                <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                                @if($match->featured)
                                    <span class="badge badge-warning"><i class="fas fa-star"></i> Destaque</span>
                                @endif
                            </div>
                            <h3 class="text-lg font-semibold truncate">
                                {{ $match->title ?? (($match->firstPlayer->name ?? 'Jog. 1') . ' vs ' . ($match->secondPlayer->name ?? 'Jog. 2')) }}
                            </h3>
                            <p class="text-sm text-gray-400 flex items-center gap-2 mt-1">
                                <i class="fas fa-calendar"></i>
                                {{ optional($match->match_start)->format('d/m/Y H:i') ?? '--' }}
                                @if($match->betting_deadline)
                                    · <i class="fas fa-clock"></i> Apostas até {{ optional($match->betting_deadline)->format('d/m H:i') }}
                                @endif
                            </p>
                            <div class="flex flex-wrap gap-2 text-xs mt-3">
                                <span class="px-3 py-1 rounded-full bg-white/5 border border-white/10">
                                    {{ $match->firstPlayer->name ?? 'J1' }} <span class="text-accent-light font-semibold">{{ number_format($match->first_player_odds, 2) }}x</span>
                                </span>
                                <span class="px-3 py-1 rounded-full bg-white/5 border border-white/10">
                                    {{ $match->secondPlayer->name ?? 'J2' }} <span class="text-accent-light font-semibold">{{ number_format($match->second_player_odds, 2) }}x</span>
                                </span>
                                @if($match->draw_odds)
                                    <span class="px-3 py-1 rounded-full bg-white/5 border border-white/10">Empate <span class="text-accent-light font-semibold">{{ number_format($match->draw_odds, 2) }}x</span></span>
                                @endif
                                @if($match->par_odds)
                                    <span class="px-3 py-1 rounded-full bg-white/5 border border-white/10">Par <span class="text-gold-light font-semibold">{{ number_format($match->par_odds, 2) }}x</span></span>
                                @endif
                                @if($match->impar_odds)
                                    <span class="px-3 py-1 rounded-full bg-white/5 border border-white/10">Ímpar <span class="text-gold-light font-semibold">{{ number_format($match->impar_odds, 2) }}x</span></span>
                                @endif
                                <span class="px-3 py-1 rounded-full bg-white/5 border border-white/10">
                                    <i class="fas fa-dice text-gray-400"></i> {{ $match->bets->count() }} apostas
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 lg:flex-col lg:items-stretch">
                            <a href="{{ route('admin.matches.edit', $match) }}" class="admin-btn-ghost" title="Editar">
                                <i class="fas fa-pen"></i> Editar
                            </a>
                            <a href="{{ route('admin.bets.index', ['match_id' => $match->id]) }}" class="admin-btn-ghost" title="Apostas">
                                <i class="fas fa-ticket"></i> Apostas
                            </a>
                            <a href="{{ route('admin.matches.delete', $match) }}" class="admin-btn-danger" title="Excluir">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-flag-checkered text-4xl mb-3 opacity-60"></i>
                        <p class="text-sm">Nenhuma partida encontrada.</p>
                        <a href="{{ route('admin.matches.create') }}" class="inline-block mt-4 admin-btn-primary">
                            <i class="fas fa-plus"></i> Agendar a primeira
                        </a>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $matches->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
