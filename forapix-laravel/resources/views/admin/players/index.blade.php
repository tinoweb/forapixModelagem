@extends('admin.layouts.app')

@section('title', 'Jogadores')
@section('breadcrumb', 'Jogadores > Listagem')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Equipe</p>
                <h2 class="text-2xl font-semibold">Banco de atletas</h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.players.create') }}" class="px-4 py-2 rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-500 font-semibold">
                    <i class="fas fa-user-plus mr-2"></i>
                    Novo jogador
                </a>
                <a href="{{ route('admin.players.index') }}" class="px-4 py-2 rounded-2xl border border-white/10 text-sm text-gray-300 hover:text-white">
                    <i class="fas fa-rotate"></i>
                    Atualizar lista
                </a>
            </div>
        </div>

        <div class="glass-card p-6">
            <div class="flex flex-wrap items-center gap-4 mb-6">
                <div>
                    <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Filtros</p>
                    <h2 class="text-2xl font-semibold">Filtrar jogadores</h2>
                </div>
                <div class="ml-auto flex gap-3 items-center">
                    <select class="input-admin" onchange="window.location = '?sport_id=' + this.value">
                        <option value="">Todos os esportes</option>
                        @foreach($sports as $sport)
                            <option value="{{ $sport->id }}" @selected(request('sport_id') == $sport->id)>{{ $sport->name }}</option>
                        @endforeach
                    </select>
                    @if(request()->filled('sport_id'))
                        <a href="{{ route('admin.players.index') }}" class="admin-btn-ghost" title="Limpar filtro">
                            <i class="fas fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </div>

            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl bg-success/15 border border-success/30 text-success-light text-sm">
                    <i class="fas fa-circle-check mr-2"></i>{{ session('success') }}
                </div>
            @endif

            <div class="grid md:grid-cols-2 gap-5">
                @forelse($players as $player)
                    <div class="bg-[#10152b] border border-white/5 rounded-2xl p-4 flex gap-4 hover:border-white/10 transition">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden border border-white/10 flex-shrink-0">
                            <img src="{{ $player->photo }}" alt="{{ $player->name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-lg font-semibold truncate">{{ $player->name }}</h3>
                                <span class="badge badge-info">{{ $player->sport->name ?? '---' }}</span>
                            </div>
                            <p class="text-sm text-gray-400 mt-1">{{ $player->bio ? \Illuminate\Support\Str::limit($player->bio, 80) : 'Sem descrição' }}</p>
                            <div class="flex items-center gap-3 mt-3 text-xs text-gray-400 flex-wrap">
                                <span><i class="fas fa-flag mr-1"></i>{{ strtoupper($player->nationality ?? 'N/A') }}</span>
                                <span><i class="fas fa-star mr-1 text-gold-light"></i>{{ $player->rating ?? '0' }}</span>
                                <span><i class="fas fa-calendar mr-1"></i>{{ optional($player->birth_date)->format('d/m/Y') ?? '-' }}</span>
                            </div>
                            <div class="flex gap-2 mt-3">
                                <a href="{{ route('admin.players.edit', $player) }}" class="admin-btn-ghost text-xs px-3 py-1.5">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                <a href="{{ route('admin.players.delete', $player) }}" class="admin-btn-danger text-xs px-3 py-1.5">
                                    <i class="fas fa-trash"></i> Excluir
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-2 text-center py-12 text-gray-500">
                        <i class="fas fa-users-slash text-4xl mb-3 opacity-60"></i>
                        <p class="text-sm">Nenhum jogador cadastrado.</p>
                        <a href="{{ route('admin.players.create') }}" class="inline-block mt-4 admin-btn-primary">
                            <i class="fas fa-user-plus"></i> Cadastrar o primeiro
                        </a>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $players->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
