@extends('admin.layouts.app')

@section('title', 'Gerenciar Apostas')
@section('breadcrumb', 'Apostas / Listagem')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Financeiro</p>
            <h2 class="text-2xl font-semibold">Gestão de Apostas</h2>
        </div>
        <a href="{{ route('admin.matches.index') }}" class="admin-btn-ghost">
            <i class="fas fa-fist-raised"></i> Ver Partidas
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Apostas</p>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Pendentes</p>
            <p class="text-2xl font-bold text-yellow-400">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Ganhou</p>
            <p class="text-2xl font-bold text-green-400">{{ number_format($stats['won']) }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Perdeu</p>
            <p class="text-2xl font-bold text-red-400">{{ number_format($stats['lost']) }}</p>
        </div>
        <div class="glass-card p-4 lg:col-span-2">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Volume Total</p>
            <p class="text-2xl font-bold text-accent-light">R$ {{ number_format($stats['volume'], 2, ',', '.') }}</p>
        </div>
        <div class="glass-card p-4 lg:col-span-2">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Pago (ganhos)</p>
            <p class="text-2xl font-bold text-gold-light">R$ {{ number_format($stats['paid_out'], 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Filtros + Tabela --}}
    <div class="glass-card p-6">
        <form method="GET" class="grid md:grid-cols-5 gap-3 mb-6 text-sm">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="ID da aposta ou usuário" class="input-admin md:col-span-2">

            <select name="match_id" class="input-admin">
                <option value="">Todas as partidas</option>
                @foreach($matches as $m)
                    <option value="{{ $m->id }}" @selected(request('match_id') == $m->id)>
                        {{ $m->firstPlayer->name ?? 'J1' }} vs {{ $m->secondPlayer->name ?? 'J2' }}
                        ({{ optional($m->match_start)->format('d/m H:i') }})
                    </option>
                @endforeach
            </select>

            <select name="status" class="input-admin">
                <option value="">Todos os status</option>
                @foreach(['pending' => 'Pendente', 'won' => 'Ganhou', 'lost' => 'Perdeu', 'cancelled' => 'Cancelada'] as $v => $l)
                    <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                @endforeach
            </select>

            <button type="submit" class="admin-btn-primary justify-center">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10 text-left text-gray-400 text-xs uppercase tracking-wider">
                        <th class="pb-3 pr-4">ID</th>
                        <th class="pb-3 pr-4">Usuário</th>
                        <th class="pb-3 pr-4">Partida</th>
                        <th class="pb-3 pr-4">Tipo</th>
                        <th class="pb-3 pr-4 text-right">Valor</th>
                        <th class="pb-3 pr-4 text-right">Odds</th>
                        <th class="pb-3 pr-4 text-right">Potencial</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3 pr-4">Data</th>
                        <th class="pb-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($bets as $bet)
                        @php
                            $statusMap = [
                                'pending'   => ['label' => 'Pendente',  'class' => 'badge-info'],
                                'won'       => ['label' => 'Ganhou',    'class' => 'badge-success'],
                                'lost'      => ['label' => 'Perdeu',    'class' => 'badge-danger'],
                                'cancelled' => ['label' => 'Cancelada', 'class' => 'badge-muted'],
                                'refunded'  => ['label' => 'Reemb.',    'class' => 'badge-muted'],
                            ];
                            $st = $statusMap[$bet->status] ?? ['label' => $bet->status, 'class' => 'badge-muted'];

                            $typeMap = [
                                'first_player'  => $bet->match->firstPlayer->name ?? 'Jog. 1',
                                'second_player' => $bet->match->secondPlayer->name ?? 'Jog. 2',
                                'draw'  => 'Empate',
                                'par'   => 'Par',
                                'impar' => 'Ímpar',
                            ];
                            $typeLabel = $typeMap[$bet->bet_type] ?? $bet->bet_type;
                        @endphp
                        <tr class="hover:bg-white/[0.02] transition">
                            <td class="py-3 pr-4">
                                <span class="font-mono text-xs text-accent-light">{{ $bet->bet_id }}</span>
                            </td>
                            <td class="py-3 pr-4">
                                <p class="font-medium">{{ $bet->user->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ $bet->user->email ?? '' }}</p>
                            </td>
                            <td class="py-3 pr-4 max-w-[160px]">
                                <p class="truncate text-xs">
                                    {{ $bet->match->firstPlayer->name ?? 'J1' }}
                                    vs
                                    {{ $bet->match->secondPlayer->name ?? 'J2' }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $bet->match->game->name ?? '' }}</p>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-1 rounded-lg bg-white/5 text-xs">{{ $typeLabel }}</span>
                            </td>
                            <td class="py-3 pr-4 text-right font-semibold">
                                R$ {{ number_format($bet->amount, 2, ',', '.') }}
                            </td>
                            <td class="py-3 pr-4 text-right text-accent-light font-semibold">
                                {{ number_format($bet->odds, 2) }}x
                            </td>
                            <td class="py-3 pr-4 text-right text-gold-light">
                                R$ {{ number_format($bet->potential_win, 2, ',', '.') }}
                            </td>
                            <td class="py-3 pr-4">
                                <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                                @if($bet->status === 'won')
                                    <p class="text-xs text-green-400 mt-0.5">+R$ {{ number_format($bet->result_amount, 2, ',', '.') }}</p>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-xs text-gray-400">
                                {{ optional($bet->placed_at)->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.bets.show', $bet) }}"
                                       class="text-xs px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 transition">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($bet->status === 'pending')
                                        <button onclick="cancelBet({{ $bet->id }}, '{{ $bet->bet_id }}')"
                                                class="text-xs px-3 py-1.5 rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500/20 transition">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-16 text-center text-gray-500">
                                <i class="fas fa-ticket text-4xl mb-3 opacity-40 block"></i>
                                Nenhuma aposta encontrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $bets->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
async function cancelBet(id, betId) {
    if (!confirm(`Cancelar aposta ${betId} e reembolsar o usuário?`)) return;

    const reason = prompt('Motivo do cancelamento (opcional):', 'Cancelada pelo administrador');
    if (reason === null) return;

    const res = await fetch(`/admin/bets/${id}/cancel`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({ reason }),
    });
    const data = await res.json();
    alert(data.message);
    if (data.success) location.reload();
}
</script>
@endpush
@endsection
