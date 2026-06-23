@extends('admin.layouts.app')

@section('title', 'Detalhe da Aposta')
@section('breadcrumb', 'Apostas / Detalhe')

@section('content')
@php
    $statusMap = [
        'pending'   => ['label' => 'Pendente',  'class' => 'badge-info',    'color' => 'text-blue-300'],
        'won'       => ['label' => 'Ganhou',    'class' => 'badge-success', 'color' => 'text-green-400'],
        'lost'      => ['label' => 'Perdeu',    'class' => 'badge-danger',  'color' => 'text-red-400'],
        'cancelled' => ['label' => 'Cancelada', 'class' => 'badge-muted',   'color' => 'text-gray-400'],
    ];
    $st = $statusMap[$bet->status] ?? ['label' => $bet->status, 'class' => 'badge-muted', 'color' => 'text-gray-400'];

    $typeMap = [
        'first_player'  => $bet->match->firstPlayer->name ?? 'Jogador 1',
        'second_player' => $bet->match->secondPlayer->name ?? 'Jogador 2',
        'draw'  => 'Empate',
        'par'   => 'Par',
        'impar' => 'Ímpar',
    ];
    $typeLabel = $typeMap[$bet->bet_type] ?? $bet->bet_type;
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Aposta</p>
            <h2 class="text-2xl font-semibold font-mono">{{ $bet->bet_id }}</h2>
        </div>
        <a href="{{ route('admin.bets.index') }}" class="admin-btn-ghost">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    {{-- Status badge grande --}}
    <div class="glass-card p-6 flex items-center gap-6">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center
            {{ $bet->status === 'won' ? 'bg-green-500/20' : ($bet->status === 'lost' ? 'bg-red-500/20' : 'bg-blue-500/20') }}">
            <i class="fas {{ $bet->status === 'won' ? 'fa-trophy text-green-400' : ($bet->status === 'lost' ? 'fa-times text-red-400' : 'fa-clock text-blue-400') }} text-3xl"></i>
        </div>
        <div>
            <span class="badge {{ $st['class'] }} text-base px-4 py-1.5">{{ $st['label'] }}</span>
            <p class="text-gray-400 text-sm mt-1">
                Apostado em: <strong class="text-white">{{ $typeLabel }}</strong>
            </p>
        </div>
        <div class="ml-auto text-right">
            <p class="text-3xl font-bold {{ $bet->status === 'won' ? 'text-green-400' : 'text-white' }}">
                @if($bet->status === 'won')
                    +R$ {{ number_format($bet->result_amount, 2, ',', '.') }}
                @else
                    R$ {{ number_format($bet->amount, 2, ',', '.') }}
                @endif
            </p>
            <p class="text-xs text-gray-500">{{ $bet->status === 'won' ? 'valor recebido' : 'valor apostado' }}</p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">

        {{-- Dados da Aposta --}}
        <div class="glass-card p-6 space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Detalhes da Aposta</h3>

            @foreach([
                ['Valor apostado', 'R$ ' . number_format($bet->amount, 2, ',', '.')],
                ['Odds', number_format($bet->odds, 2) . 'x'],
                ['Ganho potencial', 'R$ ' . number_format($bet->potential_win, 2, ',', '.')],
                ['Resultado', $bet->result_amount > 0 ? 'R$ ' . number_format($bet->result_amount, 2, ',', '.') : '—'],
                ['Tipo', $typeLabel],
                ['Realizada em', optional($bet->placed_at)->format('d/m/Y H:i:s') ?? '—'],
                ['Resolvida em', optional($bet->resolved_at)->format('d/m/Y H:i:s') ?? '—'],
            ] as [$label, $value])
                <div class="flex justify-between items-center text-sm border-b border-white/5 pb-3 last:border-0 last:pb-0">
                    <span class="text-gray-400">{{ $label }}</span>
                    <span class="font-semibold">{{ $value }}</span>
                </div>
            @endforeach

            @if($bet->cancellation_reason)
                <div class="mt-2 bg-red-500/10 border border-red-500/20 rounded-xl p-3 text-sm text-red-300">
                    <i class="fas fa-ban mr-2"></i> {{ $bet->cancellation_reason }}
                </div>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Dados do Usuário --}}
            <div class="glass-card p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Usuário</h3>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center">
                        <i class="fas fa-user text-accent-light"></i>
                    </div>
                    <div>
                        <p class="font-semibold">{{ $bet->user->name ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ $bet->user->email ?? '' }}</p>
                    </div>
                </div>
                @if($bet->user)
                    <div class="mt-4 text-sm text-gray-400">
                        Saldo atual: <strong class="text-white">R$ {{ number_format($bet->user->balance, 2, ',', '.') }}</strong>
                    </div>
                @endif
            </div>

            {{-- Dados da Partida --}}
            <div class="glass-card p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Partida</h3>
                <p class="font-semibold mb-1">
                    {{ $bet->match->firstPlayer->name ?? 'J1' }}
                    <span class="text-gray-500 mx-1">vs</span>
                    {{ $bet->match->secondPlayer->name ?? 'J2' }}
                </p>
                <p class="text-xs text-gray-400 mb-3">{{ $bet->match->game?->name ?? '' }} · {{ $bet->match->game?->sport?->name ?? '' }}</p>
                <div class="flex flex-wrap gap-2 text-xs">
                    @php
                        $mStatus = ['scheduled' => ['Agendada','badge-info'], 'live' => ['Ao vivo','badge-success'], 'finished' => ['Encerrada','badge-warning'], 'cancelled' => ['Cancelada','badge-danger']];
                        [$ml, $mc] = $mStatus[$bet->match->status] ?? [$bet->match->status, 'badge-muted'];
                    @endphp
                    <span class="badge {{ $mc }}">{{ $ml }}</span>
                    <span class="px-2 py-1 rounded-lg bg-white/5">
                        {{ optional($bet->match->match_start)->format('d/m/Y H:i') }}
                    </span>
                </div>
                <a href="{{ route('admin.matches.edit', $bet->match) }}"
                   class="mt-4 inline-flex items-center gap-2 text-xs text-accent-light hover:underline">
                    <i class="fas fa-pen"></i> Editar partida
                </a>
            </div>
        </div>
    </div>

    @if($bet->status === 'pending')
        <div class="glass-card p-6 border border-red-500/20">
            <h3 class="text-sm font-semibold text-red-400 uppercase tracking-wider mb-4">
                <i class="fas fa-ban mr-2"></i> Cancelar Aposta
            </h3>
            <p class="text-sm text-gray-400 mb-4">O valor será reembolsado ao saldo do usuário.</p>
            <div class="flex gap-3">
                <input type="text" id="cancelReason" class="input-admin flex-1"
                       placeholder="Motivo (opcional)" value="Cancelada pelo administrador">
                <button onclick="cancelBet({{ $bet->id }}, '{{ $bet->bet_id }}')"
                        class="admin-btn-danger whitespace-nowrap">
                    <i class="fas fa-ban"></i> Cancelar e Reembolsar
                </button>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
const ADMIN_BETS_BASE = '{{ rtrim(url('/admin/bets'), '/') }}';

async function cancelBet(id, betId) {
    const ok = await AdminConfirm.show({
        title:       'Cancelar aposta',
        message:     `Cancelar a aposta <strong>#${betId}</strong> e reembolsar o valor ao usuário? Esta ação não pode ser desfeita.`,
        confirmText: 'Cancelar aposta',
        variant:     'danger',
    });
    if (!ok) return;
    const reason = document.getElementById('cancelReason')?.value || 'Cancelada pelo administrador';

    const res = await fetch(`${ADMIN_BETS_BASE}/${id}/cancel`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({ reason }),
    });
    const data = await res.json();
    if (data.success) {
        await AdminConfirm.show({ title: 'Aposta cancelada', message: data.message, confirmText: 'OK', cancelText: '', variant: 'success' });
        window.location.href = '{{ route("admin.bets.index") }}';
    } else {
        await AdminConfirm.show({ title: 'Erro', message: data.message, confirmText: 'Fechar', cancelText: '', variant: 'danger' });
    }
}
</script>
@endpush
@endsection
