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
                <div class="ml-auto flex gap-3">
                    <select class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-sm" onchange="window.location = '?sport_id=' + this.value">
                        <option value="">Todos os esportes</option>
                        @foreach($sports as $sport)
                            <option value="{{ $sport->id }}" @selected(request('sport_id') == $sport->id)>{{ $sport->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                @foreach($players as $player)
                    <div class="bg-[#10152b] border border-white/5 rounded-2xl p-4 flex gap-4">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden border border-white/10">
                            <img src="{{ $player->photo_url ? Storage::url($player->photo_url) : 'https://i.pravatar.cc/150?u=' . $player->id }}" alt="{{ $player->name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-semibold">{{ $player->name }}</h3>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-100">{{ $player->sport->name ?? '---' }}</span>
                            </div>
                            <p class="text-sm text-gray-400">{{ $player->bio ? \Illuminate\Support\Str::limit($player->bio, 80) : 'Sem descrição' }}</p>
                            <div class="flex items-center gap-3 mt-3 text-xs text-gray-400">
                                <span><i class="fas fa-flag me-1"></i>{{ strtoupper($player->nationality ?? 'N/A') }}</span>
                                <span><i class="fas fa-star me-1 text-amber-400"></i>{{ $player->rating }}</span>
                                <span><i class="fas fa-calendar me-1"></i>{{ optional($player->birth_date)->format('d/m/Y') ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $players->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
