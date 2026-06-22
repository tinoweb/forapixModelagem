@extends('admin.layouts.app')

@section('title', 'Editar Partida')
@section('breadcrumb', 'Partidas / Editar')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Edição</p>
                    <h2 class="text-2xl font-semibold">{{ $match->title ?? 'Partida #' . $match->id }}</h2>
                </div>
                <a href="{{ route('admin.matches.index') }}" class="admin-btn-ghost">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>

            @php
                $meta = $match->metadata ?? [];
            @endphp

            <form class="space-y-4 ajax-form" action="{{ route('admin.matches.update', $match) }}" method="POST" enctype="multipart/form-data" data-reload="true">
                @csrf
                @method('PUT')

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Título</label>
                        <input type="text" name="title" value="{{ $match->title }}" class="input-admin mt-1" placeholder="Sinuca - Par ou Ímpar">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Status</label>
                        <select name="status" class="input-admin mt-1">
                            @foreach(['scheduled' => 'Agendada', 'live' => 'Ao vivo', 'finished' => 'Encerrada', 'cancelled' => 'Cancelada', 'postponed' => 'Adiada'] as $v => $l)
                                <option value="{{ $v }}" @selected($match->status === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Jogador 1</label>
                        <select name="first_player_id" class="input-admin mt-1">
                            @foreach($players as $player)
                                <option value="{{ $player->id }}" @selected($match->first_player_id == $player->id)>{{ $player->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Jogador 2</label>
                        <select name="second_player_id" class="input-admin mt-1">
                            @foreach($players as $player)
                                <option value="{{ $player->id }}" @selected($match->second_player_id == $player->id)>{{ $player->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Início da partida</label>
                        <input type="datetime-local" name="match_start" value="{{ optional($match->match_start)->format('Y-m-d\TH:i') }}" class="input-admin mt-1">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Limite de apostas</label>
                        <input type="datetime-local" name="betting_deadline" value="{{ optional($match->betting_deadline)->format('Y-m-d\TH:i') }}" class="input-admin mt-1">
                    </div>
                </div>

                <!-- Campos ocultos para compatibilidade com o banco de dados -->
                <input type="hidden" name="draw_odds" value="{{ $match->draw_odds }}">
                <input type="hidden" name="par_odds" value="{{ $match->par_odds }}">
                <input type="hidden" name="impar_odds" value="{{ $match->impar_odds }}">

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Placar Jogador 1</label>
                        <input type="number" min="0" name="first_player_score" value="{{ $match->first_player_score }}" class="input-admin mt-1">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Placar Jogador 2</label>
                        <input type="number" min="0" name="second_player_score" value="{{ $match->second_player_score }}" class="input-admin mt-1">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Vencedor</label>
                        <select name="winner_player_id" class="input-admin mt-1">
                            <option value="">Nenhum</option>
                            @foreach($players as $player)
                                @if($player->id == $match->first_player_id || $player->id == $match->second_player_id)
                                    <option value="{{ $player->id }}" @selected($match->winner_player_id == $player->id)>{{ $player->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Link da transmissão (stream)</label>
                        <input type="url" name="stream_url" value="{{ $meta['stream_url'] ?? '' }}" class="input-admin mt-1" placeholder="https://youtube.com/...">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Fim da partida</label>
                        <input type="datetime-local" name="match_end" value="{{ optional($match->match_end)->format('Y-m-d\TH:i') }}" class="input-admin mt-1">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <label class="text-sm text-gray-300 flex items-center gap-3">
                        <input type="checkbox" name="featured" value="1" class="w-4 h-4 text-accent" @checked($match->featured)>
                        Destacar partida na home
                    </label>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Descrição</label>
                    <textarea name="description" rows="3" class="input-admin mt-1" placeholder="Detalhes da partida, regras ou link da transmissão">{{ $match->description }}</textarea>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Banner</label>
                    @if(!empty($meta['banner_image']))
                        <div class="mt-2 mb-2">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($meta['banner_image']) }}" alt="Banner atual" class="h-32 rounded-xl border border-white/10">
                        </div>
                    @endif
                    <input type="file" name="banner_image" accept="image/*" class="mt-1 w-full text-sm text-gray-400">
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Texto do botão (banner)</label>
                        <input type="text" name="banner_button_label" value="{{ $meta['banner_button_label'] ?? '' }}" class="input-admin mt-1" placeholder="Apostar agora">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Link do botão (banner)</label>
                        <input type="url" name="banner_button_link" value="{{ $meta['banner_button_link'] ?? '' }}" class="input-admin mt-1" placeholder="https://">
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="admin-btn-primary flex-1 justify-center">
                        <i class="fas fa-save"></i> Atualizar partida
                    </button>
                    <a href="{{ route('admin.matches.index') }}" class="admin-btn-ghost">Cancelar</a>
                </div>
            </form>
        </div>

        {{-- Painel: Apostas da Partida --}}
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.3em] text-gray-500">Financeiro</p>
                    <h3 class="text-lg font-semibold">Apostas desta Partida</h3>
                </div>
                <a href="{{ route('admin.bets.index', ['match_id' => $match->id]) }}" class="admin-btn-ghost text-sm">
                    <i class="fas fa-ticket"></i> Ver todas
                </a>
            </div>
            @php
                $betStats = [
                    'total'   => $match->bets()->count(),
                    'pending' => $match->bets()->where('status', 'pending')->count(),
                    'won'     => $match->bets()->where('status', 'won')->count(),
                    'lost'    => $match->bets()->where('status', 'lost')->count(),
                    'volume'  => $match->bets()->sum('amount'),
                ];
            @endphp
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <div class="bg-white/5 rounded-xl p-3 text-center">
                    <p class="text-xs text-gray-500">Total</p>
                    <p class="text-xl font-bold">{{ $betStats['total'] }}</p>
                </div>
                <div class="bg-yellow-500/10 rounded-xl p-3 text-center">
                    <p class="text-xs text-yellow-400">Pendentes</p>
                    <p class="text-xl font-bold text-yellow-400">{{ $betStats['pending'] }}</p>
                </div>
                <div class="bg-green-500/10 rounded-xl p-3 text-center">
                    <p class="text-xs text-green-400">Ganhou</p>
                    <p class="text-xl font-bold text-green-400">{{ $betStats['won'] }}</p>
                </div>
                <div class="bg-red-500/10 rounded-xl p-3 text-center">
                    <p class="text-xs text-red-400">Perdeu</p>
                    <p class="text-xl font-bold text-red-400">{{ $betStats['lost'] }}</p>
                </div>
            </div>
            <p class="text-sm text-gray-400 mb-2">
                Volume total: <strong class="text-white">R$ {{ number_format($betStats['volume'], 2, ',', '.') }}</strong>
            </p>
        </div>

        {{-- Painel: Apostas Ao Vivo (só quando partida está live) --}}
        @if($match->status === 'live')
        <div class="glass-card p-6 border {{ $match->live_betting_open ? 'border-green-500/40' : 'border-white/10' }}">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.3em] {{ $match->live_betting_open ? 'text-green-400' : 'text-gray-400' }}">Apostas Ao Vivo</p>
                    <h3 class="text-lg font-semibold">Controle de Apostas em Tempo Real</h3>
                    <p class="text-sm text-gray-400 mt-1">
                        Abra as apostas quando estiver empatado e feche quando um jogador sair na frente.
                    </p>
                </div>
                <div id="liveBettingStatus" class="text-right">
                    @if($match->live_betting_open)
                        <span class="inline-flex items-center gap-2 bg-green-500/20 text-green-400 px-4 py-2 rounded-xl text-sm font-bold">
                            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span> APOSTAS ABERTAS
                        </span>
                    @else
                        <span class="inline-flex items-center gap-2 bg-red-500/10 text-red-400 px-4 py-2 rounded-xl text-sm font-bold">
                            <span class="w-2 h-2 bg-red-400 rounded-full"></span> APOSTAS FECHADAS
                        </span>
                    @endif
                </div>
            </div>

            @if($match->live_betting_open && $match->live_betting_opened_at)
                <p class="text-xs text-green-400/70 mb-4">
                    Abertas desde: {{ $match->live_betting_opened_at->format('H:i:s') }}
                </p>
            @endif

            <button id="toggleLiveBettingBtn"
                onclick="toggleLiveBetting({{ $match->id }})"
                class="w-full px-4 py-4 rounded-2xl font-bold text-base flex items-center justify-center gap-2 transition hover:opacity-90
                       {{ $match->live_betting_open
                           ? 'bg-gradient-to-r from-red-500 to-red-600'
                           : 'bg-gradient-to-r from-green-500 to-emerald-500' }}">
                @if($match->live_betting_open)
                    <i class="fas fa-lock"></i> Fechar Apostas Ao Vivo
                @else
                    <i class="fas fa-bolt"></i> Abrir Apostas Ao Vivo
                @endif
            </button>
        </div>
        @endif

        {{-- Painel: Encerrar e Resolver Apostas --}}
        @if($match->status !== 'finished' && $match->status !== 'cancelled')
        <div class="glass-card p-6 border border-emerald-500/20">
            <div class="mb-5">
                <p class="text-[11px] uppercase tracking-[0.3em] text-emerald-400">Encerramento</p>
                <h3 class="text-lg font-semibold">Encerrar Partida e Processar Apostas</h3>
                <p class="text-sm text-gray-400 mt-1">
                    Ao encerrar, todas as apostas pendentes serão resolvidas automaticamente.
                    Ganhadores receberão o valor no saldo.
                </p>
            </div>

            {{-- Resumo do pool atual --}}
            @php
                $poolStats = (new \App\Services\BetMatchingService())->getMatchStats($match);
            @endphp
            <div class="grid grid-cols-3 gap-3 mb-5 text-center text-sm">
                <div class="bg-[#10162c] rounded-xl p-3">
                    <p class="text-gray-400 text-xs mb-1">{{ $match->firstPlayer->name ?? 'Jogador 1' }}</p>
                    <p class="font-bold text-white">R$ {{ number_format($poolStats['first_player']['total'], 2, ',', '.') }}</p>
                    <p class="text-emerald-400 text-xs">R$ {{ number_format($poolStats['first_player']['matched'], 2, ',', '.') }} casado</p>
                </div>
                <div class="bg-[#10162c] rounded-xl p-3">
                    <p class="text-gray-400 text-xs mb-1">Pool casado</p>
                    <p class="font-bold text-emerald-400">R$ {{ number_format($poolStats['total_matched_pool'], 2, ',', '.') }}</p>
                    <p class="text-yellow-400 text-xs">Casa: R$ {{ number_format($poolStats['house_cut'], 2, ',', '.') }}</p>
                </div>
                <div class="bg-[#10162c] rounded-xl p-3">
                    <p class="text-gray-400 text-xs mb-1">{{ $match->secondPlayer->name ?? 'Jogador 2' }}</p>
                    <p class="font-bold text-white">R$ {{ number_format($poolStats['second_player']['total'], 2, ',', '.') }}</p>
                    <p class="text-emerald-400 text-xs">R$ {{ number_format($poolStats['second_player']['matched'], 2, ',', '.') }} casado</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="text-sm text-gray-300">Resultado da partida <span class="text-red-400">*</span></label>
                    <select id="resolveResult" class="input-admin mt-1">
                        <option value="">Selecione o resultado</option>
                        <option value="first_player">🏆 {{ $match->firstPlayer->name ?? 'Jogador 1' }} venceu</option>
                        <option value="second_player">🏆 {{ $match->secondPlayer->name ?? 'Jogador 2' }} venceu</option>
                        <option value="cancelled">↩ Jogo não concluído — devolver tudo</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-300">Jogador vencedor (placar)</label>
                    <select id="resolveWinner" class="input-admin mt-1">
                        <option value="">Nenhum / Não aplicável</option>
                        <option value="{{ $match->first_player_id }}">{{ $match->firstPlayer->name ?? 'Jogador 1' }}</option>
                        <option value="{{ $match->second_player_id }}">{{ $match->secondPlayer->name ?? 'Jogador 2' }}</option>
                    </select>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="text-sm text-gray-300">Placar {{ $match->firstPlayer->name ?? 'J1' }}</label>
                    <input type="number" id="resolveScore1" class="input-admin mt-1" min="0"
                           value="{{ $match->first_player_score ?? 0 }}">
                </div>
                <div>
                    <label class="text-sm text-gray-300">Placar {{ $match->secondPlayer->name ?? 'J2' }}</label>
                    <input type="number" id="resolveScore2" class="input-admin mt-1" min="0"
                           value="{{ $match->second_player_score ?? 0 }}">
                </div>
            </div>

            <button onclick="resolveMatch({{ $match->id }})"
                    class="w-full bg-gradient-to-r from-emerald-500 to-lime-500 px-4 py-4 rounded-2xl font-bold text-base flex items-center justify-center gap-2 hover:opacity-90 transition">
                <i class="fas fa-flag-checkered"></i>
                Encerrar Partida e Processar {{ $betStats['pending'] }} Aposta(s) Pendente(s)
            </button>
        </div>
        @else
        <div class="glass-card p-6 border border-white/5 text-center text-gray-500">
            <i class="fas fa-check-circle text-3xl mb-2 text-green-500 opacity-60 block"></i>
            Partida já encerrada — apostas foram processadas.
        </div>
        @endif

    </div>
@endsection

@push('scripts')
<script>
const ADMIN_MATCHES_BASE = '{{ rtrim(url('/admin/matches'), '/') }}';

async function toggleLiveBetting(matchId) {
    const btn = document.getElementById('toggleLiveBettingBtn');
    const isOpen = btn.textContent.trim().includes('Fechar');
    const action = isOpen ? 'fechar' : 'abrir';

    const ok = await AdminConfirm.show({
        title:       `${action === 'abrir' ? 'Abrir' : 'Fechar'} apostas ao vivo`,
        message:     `Confirmar: <strong>${action}</strong> as apostas ao vivo desta partida agora?`,
        confirmText: action === 'abrir' ? 'Abrir apostas' : 'Fechar apostas',
        variant:     action === 'abrir' ? 'success' : 'warning',
    });
    if (!ok) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aguarde...';

    const res = await fetch(`${ADMIN_MATCHES_BASE}/${matchId}/toggle-live-betting`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
    });

    const data = await res.json();
    if (data.success) location.reload();
    else { btn.disabled = false; await AdminConfirm.show({ title: 'Erro', message: data.message, confirmText: 'Fechar', cancelText: '', variant: 'danger' }); }
}

async function resolveMatch(matchId) {
    const result = document.getElementById('resolveResult').value;
    if (!result) {
        await AdminConfirm.show({ title: 'Campo obrigatório', message: 'Selecione o resultado da partida antes de encerrar.', confirmText: 'Entendi', cancelText: '', variant: 'warning' });
        return;
    }

    const ok = await AdminConfirm.show({
        title:       'Encerrar partida',
        message:     'Processar todas as apostas pendentes desta partida? <strong>Esta ação não pode ser desfeita.</strong>',
        confirmText: 'Encerrar e processar',
        variant:     'danger',
    });
    if (!ok) return;

    const payload = {
        result,
        winner_player_id: document.getElementById('resolveWinner').value || null,
        first_player_score: parseInt(document.getElementById('resolveScore1').value) || 0,
        second_player_score: parseInt(document.getElementById('resolveScore2').value) || 0,
    };

    const res = await fetch(`${ADMIN_MATCHES_BASE}/${matchId}/resolve-bets`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify(payload),
    });

    const data = await res.json();
    if (data.success) {
        await AdminConfirm.show({ title: 'Partida encerrada!', message: data.message, confirmText: 'OK', cancelText: '', variant: 'success' });
        location.reload();
    } else {
        await AdminConfirm.show({ title: 'Erro ao encerrar', message: data.message, confirmText: 'Fechar', cancelText: '', variant: 'danger' });
    }
}
</script>
@endpush
