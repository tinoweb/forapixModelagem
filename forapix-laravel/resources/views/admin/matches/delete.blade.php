@extends('admin.layouts.app')

@section('title', 'Excluir Partida')
@section('breadcrumb', 'Partidas / Excluir')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="glass-card p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-red-500/15 border border-red-500/30 flex items-center justify-center">
                    <i class="fas fa-triangle-exclamation text-red-300 text-2xl"></i>
                </div>
                <div>
                    <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Confirmação</p>
                    <h2 class="text-2xl font-semibold">Excluir partida</h2>
                </div>
            </div>

            <p class="text-gray-300 mb-6">
                Tem certeza que deseja excluir a partida
                <strong class="text-white">{{ $match->title ?? (($match->firstPlayer?->name ?? 'Jog. 1') . ' vs ' . ($match->secondPlayer?->name ?? 'Jog. 2')) }}</strong>?
                Esta ação não poderá ser desfeita.
            </p>

            @php 
                $pendingBets = $match->bets()->where('status', 'pending')->count();
                $resolvedBets = $match->bets()->whereIn('status', ['won', 'lost', 'cancelled'])->count();
                $totalBets = $match->bets()->count();
            @endphp

            <div class="bg-[#10152b] border border-white/5 rounded-2xl p-4 mb-6 space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Jogo:</span> <span>{{ $match->game?->name ?? '---' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Início:</span> <span>{{ optional($match->match_start)->format('d/m/Y H:i') ?? '--' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status:</span> <span class="uppercase">{{ $match->status }}</span></div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Apostas pendentes:</span> 
                    <span class="{{ $pendingBets > 0 ? 'text-red-300 font-semibold' : 'text-green-300' }}">{{ $pendingBets }} ({{ $totalBets }} total)</span>
                </div>
            </div>

            @if($pendingBets > 0)
                <div class="bg-yellow-500/10 border border-yellow-500/30 text-yellow-200 rounded-2xl p-4 mb-6 text-sm">
                    <i class="fas fa-circle-info mr-2"></i>
                    Esta partida possui <strong>{{ $pendingBets }} aposta(s) pendente(s)</strong> e <strong>não poderá ser excluída</strong>. Cancele a partida primeiro para devolver os valores aos apostadores.
                </div>
            @elseif($resolvedBets > 0)
                <div class="bg-red-500/10 border border-red-500/30 text-red-200 rounded-2xl p-4 mb-6 text-sm">
                    <i class="fas fa-circle-info mr-2"></i>
                    <strong>Atenção:</strong> Esta partida já possui <strong>{{ $resolvedBets }} aposta(s) finalizada(s)</strong>. Ao excluí-la definitivamente, o histórico dessas apostas também será removido do sistema.
                </div>
            @endif

            <form action="{{ route('admin.matches.destroy', $match) }}" method="POST" class="flex gap-3 ajax-form" data-redirect="{{ route('admin.matches.index') }}" data-reset="false">
                @csrf
                @method('DELETE')
                <a href="{{ route('admin.matches.index') }}" class="admin-btn-ghost flex-1 justify-center">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="admin-btn-danger flex-1 justify-center" @disabled($pendingBets > 0)>
                    <i class="fas fa-trash"></i> Excluir definitivamente
                </button>
            </form>
        </div>
    </div>
@endsection
