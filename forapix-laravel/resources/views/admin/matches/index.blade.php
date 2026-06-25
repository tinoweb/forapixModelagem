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

                        // Calcular porcentagem de apostas por jogador
                        $totalBets = $match->bets->count();
                        $player1Bets = $match->bets->where('bet_type', 'first_player')->count();
                        $player2Bets = $match->bets->where('bet_type', 'second_player')->count();
                        $player1Percent = $totalBets > 0 ? round(($player1Bets / $totalBets) * 100) : 0;
                        $player2Percent = $totalBets > 0 ? round(($player2Bets / $totalBets) * 100) : 0;
                    @endphp
                    <div class="bg-[#10152b] border border-white/5 rounded-2xl p-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between hover:border-white/10 transition">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="material-icons text-accent-light text-lg">sports</span>
                                <span class="text-[11px] uppercase tracking-[0.2em] text-gray-500">{{ $match->game?->name ?? 'Jogo' }}</span>
                                <span class="badge {{ $st['class'] }}">{{ $st['label'] }}</span>
                                @if($match->featured)
                                    <span class="badge badge-warning"><i class="fas fa-star"></i> Destaque</span>
                                @endif
                                @if($match->betting_locked)
                                    <span class="badge badge-danger"><i class="fas fa-lock mr-1"></i> Trancada</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between gap-4 mt-3">
                                @php
                                    $s1 = $match->first_player_score ?? 0;
                                    $s2 = $match->second_player_score ?? 0;
                                    $p1Leading = $s1 > $s2;
                                    $p2Leading = $s2 > $s1;
                                    $tied = $s1 === $s2;
                                @endphp
                                <div class="text-left">
                                    <p class="text-sm text-gray-400">Jogador 1</p>
                                    <p class="text-lg font-semibold {{ $p1Leading ? 'text-success' : '' }}">{{ explode(' ', $match->firstPlayer?->name ?? 'Jog. 1')[0] }}</p>
                                    <p class="text-accent-light font-semibold">{{ number_format($match->first_player_odds, 2) }}x</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $player1Percent }}% das apostas</p>
                                </div>
                                <div class="text-center flex flex-col items-center gap-1">
                                    @if(in_array($match->status, ['live', 'finished']))
                                        <div class="flex items-center gap-2">
                                            <span class="text-3xl font-black {{ $p1Leading ? 'text-white' : 'text-gray-400' }}">{{ $s1 }}</span>
                                            <span class="text-gray-600 font-bold text-lg">×</span>
                                            <span class="text-3xl font-black {{ $p2Leading ? 'text-white' : 'text-gray-400' }}">{{ $s2 }}</span>
                                        </div>
                                        @if($match->status === 'live')
                                            @if($tied)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-300 uppercase tracking-wider">Empate</span>
                                            @elseif($p1Leading)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-500/20 text-green-300 uppercase tracking-wider">J1 liderando</span>
                                            @else
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-500/20 text-green-300 uppercase tracking-wider">J2 liderando</span>
                                            @endif
                                            @if($match->live_betting_open)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-accent/20 text-accent-light uppercase tracking-wider animate-pulse">Apostas abertas</span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-gray-500 font-bold text-sm">VS</span>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-400">Jogador 2</p>
                                    <p class="text-lg font-semibold {{ $p2Leading ? 'text-success' : '' }}">{{ explode(' ', $match->secondPlayer?->name ?? 'Jog. 2')[0] }}</p>
                                    <p class="text-accent-light font-semibold">{{ number_format($match->second_player_odds, 2) }}x</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $player2Percent }}% das apostas</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-400 flex items-center gap-2 mt-3">
                                <i class="fas fa-calendar"></i>
                                Início: {{ optional($match->match_start)->format('d/m/Y H:i') ?? '--' }}
                                @if($match->match_end)
                                    · <i class="fas fa-clock"></i> Término: {{ optional($match->match_end)->format('d/m/Y H:i') }}
                                @endif
                            </p>
                            <div class="flex flex-wrap gap-2 text-xs mt-3">
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
                        <!-- Botões: grid 2 colunas no mobile, coluna no desktop -->
                        <div class="grid grid-cols-2 gap-2 mt-1 lg:mt-0 lg:flex lg:flex-col lg:items-stretch lg:min-w-[130px]">
                            @if(!in_array($match->status, ['finished','cancelled']))
                            <button onclick="openScoreModal({{ $match->id }}, '{{ addslashes($match->firstPlayer?->name ?? 'Jogador 1') }}', '{{ addslashes($match->secondPlayer?->name ?? 'Jogador 2') }}', {{ $match->first_player_score ?? 0 }}, {{ $match->second_player_score ?? 0 }})"
                                    class="admin-btn-primary col-span-2 justify-center">
                                <i class="fas fa-hashtag"></i> Placar
                            </button>
                            <button id="lock-btn-{{ $match->id }}" onclick="toggleBettingLock({{ $match->id }}, {{ $match->betting_locked ? 'false' : 'true' }})"
                                    class="col-span-2 justify-center {{ $match->betting_locked ? 'admin-btn-warning' : 'admin-btn-ghost' }}">
                                @if($match->betting_locked)
                                    <i class="fas fa-unlock mr-1"></i> Destrancar
                                @else
                                    <i class="fas fa-lock mr-1 text-yellow-400"></i> Trancar
                                @endif
                            </button>
                            @endif
                            <a href="{{ route('admin.matches.edit', $match) }}" class="admin-btn-ghost justify-center">
                                <i class="fas fa-pen"></i> Editar
                            </a>
                            <a href="{{ route('admin.bets.index', ['match_id' => $match->id]) }}" class="admin-btn-ghost justify-center">
                                <i class="fas fa-ticket"></i> Apostas
                            </a>
                            @if(!in_array($match->status, ['finished','cancelled']))
                            <button onclick="openCancelModal({{ $match->id }}, '{{ addslashes($match->title ?? ($match->firstPlayer?->name ?? '') . ' vs ' . ($match->secondPlayer?->name ?? '')) }}')"
                                    class="admin-btn-warning justify-center">
                                <i class="fas fa-ban"></i> Cancelar
                            </button>
                            <a href="{{ route('admin.matches.delete', $match) }}" class="admin-btn-danger justify-center">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                            @else
                            <a href="{{ route('admin.matches.delete', $match) }}" class="admin-btn-danger col-span-2 justify-center">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                            @endif
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
<!-- Modal Editar Placar -->
<div id="scoreModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm px-4">
    <div class="bg-[#10152b] border border-accent/30 rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hashtag text-accent-light"></i>
            </div>
            <div>
                <h3 class="font-bold text-white text-lg">Atualizar Placar</h3>
                <p class="text-sm text-gray-400" id="scoreMatchTitle"></p>
            </div>
        </div>

        <div class="bg-accent/10 border border-accent/20 rounded-xl p-3 mb-5 text-xs text-accent-light">
            <i class="fas fa-circle-info mr-1"></i>
            <strong>Automático:</strong> Empate → abre apostas ao vivo. Jogador na frente → fecha apostas.
        </div>

        <form id="scoreForm" method="POST" action="">
            @csrf
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-xs text-gray-400 mb-2 uppercase tracking-wider" id="scoreLabel1">Jogador 1</label>
                    <input type="number" name="first_player_score" id="scoreInput1"
                        min="0" max="999" value="0"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-4 text-center text-3xl font-black text-white focus:outline-none focus:border-accent-light transition">
                </div>
                <div class="text-center">
                    <span class="text-2xl font-black text-gray-600">×</span>
                </div>
                <div class="flex-1">
                    <label class="block text-xs text-gray-400 mb-2 uppercase tracking-wider text-right" id="scoreLabel2">Jogador 2</label>
                    <input type="number" name="second_player_score" id="scoreInput2"
                        min="0" max="999" value="0"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-4 text-center text-3xl font-black text-white focus:outline-none focus:border-accent-light transition">
                </div>
            </div>

            <div id="scoreTieAlert" class="hidden mt-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-3 text-xs text-yellow-300">
                <i class="fas fa-unlock mr-1"></i> Placar empatado — apostas ao vivo serão <strong>abertas</strong> automaticamente.
            </div>
            <div id="scoreLeadAlert" class="hidden mt-4 bg-blue-500/10 border border-blue-500/30 rounded-xl p-3 text-xs text-blue-300">
                <i class="fas fa-lock mr-1"></i> Jogador na frente — apostas ao vivo serão <strong>fechadas</strong>.
            </div>

            <div class="flex gap-3 mt-5">
                <button type="button" onclick="closeScoreModal()" class="flex-1 px-4 py-3 rounded-xl border border-white/10 text-gray-300 text-sm font-semibold hover:bg-white/5 transition">
                    Cancelar
                </button>
                <button type="button" id="scoreSaveBtn" onclick="submitScore()" class="flex-1 px-4 py-3 rounded-xl bg-accent hover:bg-accent-light text-white text-sm font-bold transition flex items-center justify-center gap-2">
                    <i class="fas fa-check"></i> Salvar Placar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cancelar Partida -->
<div id="cancelMatchModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm px-4">
    <div class="bg-[#10152b] border border-red-500/30 rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ban text-red-400"></i>
            </div>
            <div>
                <h3 class="font-bold text-white text-lg">Cancelar Partida</h3>
                <p class="text-sm text-gray-400" id="cancelMatchTitle"></p>
            </div>
        </div>

        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-3 mb-4 text-sm text-yellow-300">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            Todas as apostas pendentes serão <strong>reembolsadas automaticamente</strong>.
        </div>

        <label class="block text-sm text-gray-400 mb-1">Motivo do cancelamento</label>
        <textarea id="cancelReason" rows="3"
            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-red-400 resize-none"
            placeholder="Ex: Jogador desistiu, data alterada, problema técnico..."></textarea>

        <div class="flex gap-3 mt-5">
            <button onclick="closeCancelModal()" class="flex-1 px-4 py-3 rounded-xl border border-white/10 text-gray-300 text-sm font-semibold hover:bg-white/5 transition">
                Voltar
            </button>
            <button onclick="confirmCancelMatch()" id="cancelConfirmBtn"
                class="flex-1 px-4 py-3 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-bold transition flex items-center justify-center gap-2">
                <i class="fas fa-ban"></i> Confirmar cancelamento
            </button>
        </div>
    </div>
</div>

<script>
const ADMIN_MATCHES_BASE = '{{ rtrim(url('/admin/matches'), '/') }}';

let _scoreMatchId = null;

function openScoreModal(matchId, p1Name, p2Name, s1, s2) {
    _scoreMatchId = matchId;
    document.getElementById('scoreMatchTitle').textContent = p1Name + ' vs ' + p2Name;
    document.getElementById('scoreLabel1').textContent = p1Name;
    document.getElementById('scoreLabel2').textContent = p2Name;
    document.getElementById('scoreInput1').value = s1;
    document.getElementById('scoreInput2').value = s2;
    updateScoreAlerts();
    const modal = document.getElementById('scoreModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeScoreModal() {
    const modal = document.getElementById('scoreModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function updateScoreAlerts() {
    const s1 = parseInt(document.getElementById('scoreInput1').value) || 0;
    const s2 = parseInt(document.getElementById('scoreInput2').value) || 0;
    const tied = s1 === s2;
    document.getElementById('scoreTieAlert').classList.toggle('hidden', !tied);
    document.getElementById('scoreLeadAlert').classList.toggle('hidden', tied);
}

async function submitScore() {
    if (!_scoreMatchId) return;

    const s1  = parseInt(document.getElementById('scoreInput1').value) || 0;
    const s2  = parseInt(document.getElementById('scoreInput2').value) || 0;
    const btn = document.getElementById('scoreSaveBtn');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Salvando...';

    try {
        const res = await fetch(`${ADMIN_MATCHES_BASE}/${_scoreMatchId}/update-score`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                first_player_score:  s1,
                second_player_score: s2
            })
        });

        const data = await res.json();
        closeScoreModal();

        if (data.success) {
            showAdminToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showAdminToast(data.message || 'Erro ao salvar placar.', 'error');
        }
    } catch (e) {
        showAdminToast('Erro de conexão ao salvar placar.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check mr-2"></i> Salvar Placar';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('scoreInput1').addEventListener('input', updateScoreAlerts);
    document.getElementById('scoreInput2').addEventListener('input', updateScoreAlerts);
});

let _cancelMatchId = null;

function openCancelModal(matchId, title) {
    _cancelMatchId = matchId;
    document.getElementById('cancelMatchTitle').textContent = title;
    document.getElementById('cancelReason').value = '';
    const modal = document.getElementById('cancelMatchModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCancelModal() {
    _cancelMatchId = null;
    const modal = document.getElementById('cancelMatchModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function confirmCancelMatch() {
    if (!_cancelMatchId) return;
    const reason = document.getElementById('cancelReason').value.trim() || 'Cancelada pelo administrador';
    const btn = document.getElementById('cancelConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelando...';

    try {
        const res = await fetch(`${ADMIN_MATCHES_BASE}/${_cancelMatchId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ reason })
        });
        const data = await res.json();
        closeCancelModal();
        if (data.success) {
            showAdminToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAdminToast(data.message || 'Erro ao cancelar', 'error');
        }
    } catch (e) {
        showAdminToast('Erro de conexão.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-ban"></i> Confirmar cancelamento';
    }
}

async function toggleBettingLock(matchId, isLocking) {
    const btn = document.getElementById(`lock-btn-${matchId}`);
    if (!btn) return;
    
    const originalHtml = btn.innerHTML;
    const originalClass = btn.className;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
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
            
            // Atualiza o botão sem recarregar a página inteira para ser extremamente fluido!
            if (data.betting_locked) {
                btn.className = "col-span-2 justify-center admin-btn-warning";
                btn.innerHTML = '<i class="fas fa-unlock mr-1"></i> Destrancar';
                btn.setAttribute('onclick', `toggleBettingLock(${matchId}, false)`);
            } else {
                btn.className = "col-span-2 justify-center admin-btn-ghost";
                btn.innerHTML = '<i class="fas fa-lock mr-1 text-yellow-400"></i> Trancar';
                btn.setAttribute('onclick', `toggleBettingLock(${matchId}, true)`);
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

function showAdminToast(msg, type = 'success') {
    const colors = { success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-yellow-500' };
    const toast = document.createElement('div');
    toast.className = `fixed bottom-6 left-1/2 -translate-x-1/2 z-[9999] ${colors[type] ?? colors.success} text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-2xl transition-all`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}
</script>
@endsection
