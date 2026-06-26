@extends('admin.layouts.app')

@section('title', 'Trancar Apostas')
@section('breadcrumb', 'Partidas / Trancamento')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Controle Rápido</p>
                <h2 class="text-2xl font-semibold">Trancar / Destrancar Apostas</h2>
                <p class="text-xs text-gray-400 mt-1">Gerencie a suspensão de apostas em tempo real para partidas ativas.</p>
            </div>
            <div>
                <a href="{{ route('admin.matches.betting-locks') }}" class="admin-btn-ghost" title="Atualizar">
                    <i class="fas fa-rotate mr-1"></i> Atualizar
                </a>
            </div>
        </div>

        <!-- Matches list -->
        <div class="space-y-4">
            @forelse($matches as $match)
                @php
                    $p1Name = $match->firstPlayer?->name ?? 'Jogador 1';
                    $p2Name = $match->secondPlayer?->name ?? 'Jogador 2';
                @endphp
                <div class="glass-card p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-l-4 {{ $match->betting_locked ? 'border-l-warning' : 'border-l-success' }}">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1.5">
                            @if($match->status === 'live')
                                <span class="badge badge-success animate-pulse">Ao vivo</span>
                            @else
                                <span class="badge badge-info">Agendada</span>
                            @endif
                            <span class="text-xs text-gray-400 font-semibold">{{ $match->game?->name ?? 'Jogo' }}</span>
                        </div>
                        
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <span>{{ $p1Name }}</span>
                            <span class="text-gray-500 font-normal text-sm">vs</span>
                            <span>{{ $p2Name }}</span>
                        </h3>

                        <p class="text-xs text-gray-400 flex items-center gap-1.5 mt-2">
                            <i class="fas fa-calendar-alt text-gray-500"></i>
                            Início: {{ optional($match->match_start)->format('d/m/Y H:i') ?? '--' }}
                        </p>
                    </div>

                    <!-- Lock Action Button (Large and touch friendly) -->
                    <div class="flex items-center gap-3 self-end sm:self-center">
                        <div id="status-text-{{ $match->id }}" class="text-right hidden sm:block">
                            <p class="text-xs text-gray-400">Status das Apostas</p>
                            <p class="text-sm font-bold {{ $match->betting_locked ? 'text-warning' : 'text-success' }}">
                                {{ $match->betting_locked ? '🔒 Trancado' : '🔓 Liberado' }}
                            </p>
                        </div>
                        
                        <button id="lock-btn-{{ $match->id }}" 
                                onclick="toggleBettingLock({{ $match->id }}, {{ $match->betting_locked ? 'false' : 'true' }})"
                                class="w-full sm:w-auto px-5 py-3 rounded-2xl font-bold flex items-center justify-center gap-2 transition active:scale-95 text-sm min-h-[48px] {{ $match->betting_locked ? 'bg-warning/20 text-warning border border-warning/30 hover:bg-warning/35' : 'bg-success/20 text-success border border-success/30 hover:bg-success/35' }}">
                            @if($match->betting_locked)
                                <i class="fas fa-unlock text-base"></i>
                                <span>Destrancar Apostas</span>
                            @else
                                <i class="fas fa-lock text-base"></i>
                                <span>Trancar Apostas</span>
                            @endif
                        </button>
                    </div>
                </div>
            @empty
                <div class="glass-card p-12 text-center text-gray-500">
                    <i class="fas fa-lock-open text-4xl mb-3 opacity-60"></i>
                    <p class="text-sm">Nenhuma partida ativa ou agendada para gerenciar no momento.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
async function toggleBettingLock(matchId, isLocking) {
    const btn = document.getElementById(`lock-btn-${matchId}`);
    const statusText = document.getElementById(`status-text-${matchId}`);
    if (!btn) return;
    
    const originalHtml = btn.innerHTML;
    const originalClass = btn.className;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-base"></i> Processando...';
    
    try {
        const res = await fetch(`/admin/matches/${matchId}/toggle-betting-lock`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await res.json();
        if (data.success) {
            showAdminToast(data.message, 'success');
            
            // Update UI instantly
            if (data.betting_locked) {
                // Button state: Warning (Orange) to Destrancar
                btn.className = "w-full sm:w-auto px-5 py-3 rounded-2xl font-bold flex items-center justify-center gap-2 transition active:scale-95 text-sm min-h-[48px] bg-warning/20 text-warning border border-warning/30 hover:bg-warning/35";
                btn.innerHTML = '<i class="fas fa-unlock text-base"></i> <span>Destrancar Apostas</span>';
                btn.setAttribute('onclick', `toggleBettingLock(${matchId}, false)`);
                
                // Status text update
                if (statusText) {
                    statusText.innerHTML = '<p class="text-xs text-gray-400">Status das Apostas</p><p class="text-sm font-bold text-warning">🔒 Trancado</p>';
                }
                
                // Update left border of card
                btn.closest('.glass-card').classList.remove('border-l-success');
                btn.closest('.glass-card').classList.add('border-l-warning');
            } else {
                // Button state: Success (Green) to Trancar
                btn.className = "w-full sm:w-auto px-5 py-3 rounded-2xl font-bold flex items-center justify-center gap-2 transition active:scale-95 text-sm min-h-[48px] bg-success/20 text-success border border-success/30 hover:bg-success/35";
                btn.innerHTML = '<i class="fas fa-lock text-base"></i> <span>Trancar Apostas</span>';
                btn.setAttribute('onclick', `toggleBettingLock(${matchId}, true)`);
                
                // Status text update
                if (statusText) {
                    statusText.innerHTML = '<p class="text-xs text-gray-400">Status das Apostas</p><p class="text-sm font-bold text-success">🔓 Liberado</p>';
                }
                
                // Update left border of card
                btn.closest('.glass-card').classList.remove('border-l-warning');
                btn.closest('.glass-card').classList.add('border-l-success');
            }
        } else {
            showAdminToast(data.message || 'Erro ao alterar trancamento', 'error');
            btn.innerHTML = originalHtml;
            btn.className = originalClass;
        }
    } catch (e) {
        showAdminToast('Erro de conexão.', 'error');
        btn.innerHTML = originalHtml;
        btn.className = originalClass;
    } finally {
        btn.disabled = false;
    }
}
</script>
@endpush
