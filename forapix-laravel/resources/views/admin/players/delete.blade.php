@extends('admin.layouts.app')

@section('title', 'Remover Jogador')
@section('breadcrumb', 'Jogadores > Remover')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="glass-card p-6 text-center space-y-6">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-red-500/20 border border-red-500/40 flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
            </div>
            <div>
                <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Confirmação</p>
                <h2 class="text-2xl font-semibold text-white">Excluir {{ $player->name }}?</h2>
                <p class="text-gray-400 mt-3">Essa ação é irreversível e removerá o atleta do histórico de partidas.</p>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-2xl p-4 text-left">
                <p class="text-sm text-gray-400">Esporte: <span class="text-white">{{ $player->sport->name ?? '—' }}</span></p>
                <p class="text-sm text-gray-400">Nacionalidade: <span class="text-white">{{ strtoupper($player->nationality ?? 'N/A') }}</span></p>
                <p class="text-sm text-gray-400">Rating: <span class="text-white">{{ $player->rating }}</span></p>
            </div>

            <div class="flex flex-wrap gap-3 justify-center">
                <a href="{{ route('admin.players.index') }}" class="px-4 py-2 rounded-2xl border border-white/10 text-sm text-gray-300 hover:text-white">
                    Cancelar
                </a>
                <form method="POST" action="{{ route('admin.players.destroy', $player) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-6 py-2 rounded-2xl bg-red-500/20 border border-red-500/40 text-red-200 font-semibold">
                        Excluir definitivamente
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
