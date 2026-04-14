@extends('admin.layouts.app')

@section('title', 'Jogos')
@section('breadcrumb', 'Jogos > Listagem')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Operações</p>
                <h2 class="text-2xl font-semibold">Catálogo de jogos</h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.games.create') }}" class="px-4 py-2 rounded-2xl bg-gradient-to-r from-purple-500 to-indigo-500 font-semibold">
                    <i class="fas fa-plus mr-2"></i>
                    Novo jogo
                </a>
                <button class="px-4 py-2 rounded-2xl border border-white/10 text-sm text-gray-300 hover:text-white" onclick="location.reload()">
                    <i class="fas fa-rotate"></i>
                </button>
            </div>
        </div>

        <div class="glass-card p-6">
            <form method="GET" class="grid md:grid-cols-4 gap-3 mb-6 text-sm">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome"
                    class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10" />
                <select name="sport_id" class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10">
                    <option value="">Todos os esportes</option>
                    @foreach($sports as $sport)
                        <option value="{{ $sport->id }}" @selected(request('sport_id') == $sport->id)>{{ $sport->name }}</option>
                    @endforeach
                </select>
                <select name="type" class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10">
                    <option value="">Todos os tipos</option>
                    @foreach(['head_to_head' => 'Head to head','sinuca' => 'Sinuca','par_impar' => 'Par/Ímpar','casino' => 'Cassino','bingo' => 'Bingo'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10">
                    <option value="">Status</option>
                    @foreach(['active' => 'Ativo','inactive' => 'Inativo','maintenance' => 'Manutenção'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>

            <div class="overflow-hidden rounded-2xl border border-white/5">
                <table class="min-w-full divide-y divide-white/5 text-sm">
                    <thead class="bg-white/5">
                        <tr class="text-left text-gray-400 uppercase text-[11px] tracking-[0.2em]">
                            <th class="px-6 py-4">Jogo</th>
                            <th class="px-6 py-4">Limites</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($games as $game)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-6 py-4">
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $game->sport->name ?? 'Sem esporte' }}</p>
                                    <p class="text-base font-semibold">{{ $game->name }}</p>
                                    <p class="text-xs text-gray-400">{{ ucfirst(str_replace('_', ' ', $game->type)) }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold">R$ {{ number_format($game->min_bet, 2, ',', '.') }} - R$ {{ number_format($game->max_bet, 2, ',', '.') }}</p>
                                    <p class="text-xs text-gray-400">Margem {{ number_format(($game->house_edge ?? 0) * 100, 2, ',', '.') }}%</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs {{ $game->status === 'active' ? 'bg-green-500/20 text-green-200' : 'bg-yellow-500/20 text-yellow-200' }}">
                                        {{ strtoupper($game->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.games.edit', $game) }}" class="px-3 py-2 rounded-xl border border-white/10 text-xs text-gray-200 hover:text-white">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="{{ route('admin.games.delete', $game) }}" class="px-3 py-2 rounded-xl border border-red-500/40 text-xs text-red-300 hover:text-white">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">Nenhum jogo encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $games->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
