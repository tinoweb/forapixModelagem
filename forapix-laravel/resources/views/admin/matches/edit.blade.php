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

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Odds jogador 1</label>
                        <input type="number" step="0.01" min="1.01" name="first_player_odds" value="{{ $match->first_player_odds }}" class="input-admin mt-1">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Odds jogador 2</label>
                        <input type="number" step="0.01" min="1.01" name="second_player_odds" value="{{ $match->second_player_odds }}" class="input-admin mt-1">
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Odds Empate</label>
                        <input type="number" step="0.01" min="1.01" name="draw_odds" value="{{ $match->draw_odds }}" class="input-admin mt-1" placeholder="3.00">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Odds Par</label>
                        <input type="number" step="0.01" min="1.01" name="par_odds" value="{{ $match->par_odds }}" class="input-admin mt-1" placeholder="1.85">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Odds Ímpar</label>
                        <input type="number" step="0.01" min="1.01" name="impar_odds" value="{{ $match->impar_odds }}" class="input-admin mt-1" placeholder="1.95">
                    </div>
                </div>

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
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($meta['banner_image']) }}" alt="Banner atual" class="h-32 rounded-xl border border-white/10">
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

            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="text-sm text-gray-300">Resultado da partida <span class="text-red-400">*</span></label>
                    <select id="resolveResult" class="input-admin mt-1">
                        <option value="">Selecione o resultado</option>
                        <option value="first_player">{{ $match->firstPlayer->name ?? 'Jogador 1' }} venceu</option>
                        <option value="second_player">{{ $match->secondPlayer->name ?? 'Jogador 2' }} venceu</option>
                        @if($match->draw_odds) <option value="draw">Empate</option> @endif
                        @if($match->par_odds)  <option value="par">Par</option> @endif
                        @if($match->impar_odds) <option value="impar">Ímpar</option> @endif
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-300">Jogador vencedor (opcional)</label>
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
async function resolveMatch(matchId) {
    const result = document.getElementById('resolveResult').value;
    if (!result) { alert('Selecione o resultado da partida antes de encerrar.'); return; }

    if (!confirm('Encerrar partida e processar todas as apostas pendentes? Esta ação não pode ser desfeita.')) return;

    const payload = {
        result,
        winner_player_id: document.getElementById('resolveWinner').value || null,
        first_player_score: parseInt(document.getElementById('resolveScore1').value) || 0,
        second_player_score: parseInt(document.getElementById('resolveScore2').value) || 0,
    };

    const res = await fetch(`/admin/matches/${matchId}/resolve-bets`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify(payload),
    });

    const data = await res.json();
    alert(data.message);
    if (data.success) location.reload();
}
</script>
@endpush
