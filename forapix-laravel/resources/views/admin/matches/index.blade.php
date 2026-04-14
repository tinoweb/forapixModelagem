@extends('admin.layouts.app')

@section('title', 'Partidas')
@section('breadcrumb', 'Jogos > Partidas > Listagem')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Operações</p>
                <h2 class="text-2xl font-semibold">Agenda de partidas</h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.matches.create') }}" class="px-4 py-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-lime-500 font-semibold">
                    <i class="fas fa-plus mr-2"></i>
                    Agendar partida
                </a>
                <a href="{{ route('admin.matches.index') }}" class="px-4 py-2 rounded-2xl border border-white/10 text-sm text-gray-300 hover:text-white">
                    <i class="fas fa-rotate"></i>
                </a>
            </div>
        </div>

        <div class="glass-card p-6">
            <form method="GET" class="grid lg:grid-cols-4 gap-3 mb-6 text-sm">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por jogador" class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10">
                <select name="game_id" class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10">
                    <option value="">Todos os jogos</option>
                    @foreach($games as $game)
                        <option value="{{ $game->id }}" @selected(request('game_id') == $game->id)>{{ $game->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10">
                    <option value="">Todos os status</option>
                    @foreach(['scheduled' => 'Agendadas', 'live' => 'Ao vivo', 'finished' => 'Encerradas', 'cancelled' => 'Canceladas'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="flex-1 px-4 py-2 rounded-2xl bg-white/5 border border-white/10" placeholder="De">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="flex-1 px-4 py-2 rounded-2xl bg-white/5 border border-white/10" placeholder="Até">
                </div>
            </form>

            <div class="space-y-4">
                @forelse($matches as $match)
                    <div class="bg-[#10152b] border border-white/5 rounded-2xl p-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ $match->game->name ?? 'Jogo indefinido' }}</p>
                            <h3 class="text-xl font-semibold">{{ $match->title ?? ($match->firstPlayer->name . ' vs ' . $match->secondPlayer->name) }}</h3>
                            <p class="text-sm text-gray-400 flex items-center gap-2">
                                <i class="fas fa-calendar"></i>
                                {{ optional($match->match_start)->format('d/m/Y H:i') }} · {{ ucfirst($match->status) }}
                            </p>
                            <div class="flex flex-wrap gap-3 text-xs text-gray-300 mt-3">
                                <span class="px-3 py-1 rounded-full bg-gray-800/70">{{ $match->firstPlayer->name }} ({{ $match->first_player_odds }}x)</span>
                                <span class="px-3 py-1 rounded-full bg-gray-800/70">{{ $match->secondPlayer->name }} ({{ $match->second_player_odds }}x)</span>
                                <span class="px-3 py-1 rounded-full {{ $match->featured ? 'bg-yellow-500/20 text-yellow-100' : 'bg-gray-800/70' }}">{{ $match->featured ? 'Destacado' : 'Padrão' }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.matches.edit', $match) }}" class="px-3 py-2 rounded-xl border border-white/10 text-xs text-gray-200 hover:text-white">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="{{ route('admin.matches.delete', $match) }}" class="px-3 py-2 rounded-xl border border-red-500/40 text-xs text-red-300 hover:text-white">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Nenhuma partida encontrada.</p>
                @endforelse
                            <label class="text-sm text-gray-300">Odds Par</label>
                            <input type="number" step="0.01" min="1.01" name="par_odds" class="mt-1 w-full px-4 py-3 bg-secondary border border-gray-700 rounded-xl" placeholder="1.85">
                        </div>
                        <div>
                            <label class="text-sm text-gray-300">Odds Ímpar</label>
                            <input type="number" step="0.01" min="1.01" name="impar_odds" class="mt-1 w-full px-4 py-3 bg-secondary border border-gray-700 rounded-xl" placeholder="1.95">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-300 flex items-center gap-3">
                            <input type="checkbox" name="featured" value="1" class="w-4 h-4 text-accent focus:ring-accent">
                            Destacar partida na home
                        </label>
                    </div>

                    <div>
                        <label class="text-sm text-gray-300">Descrição/transmissão</label>
                        <textarea name="description" rows="3" class="mt-1 w-full px-4 py-3 bg-secondary border border-gray-700 rounded-xl" placeholder="Detalhes da partida, regras ou link da transmissão"></textarea>
                    </div>

                    <div>
                        <label class="text-sm text-gray-300">Banner (opcional)</label>
                        <input type="file" name="banner_image" accept="image/*" class="mt-1 w-full text-sm text-gray-400">
                        <p class="text-xs text-gray-500 mt-1">Dimensão recomendada: 720x360px</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm text-gray-300">Texto do botão</label>
                            <input type="text" name="banner_button_label" class="mt-1 w-full px-4 py-3 bg-secondary border border-gray-700 rounded-xl" placeholder="Apostar agora">
                        </div>
                        <div>
                            <label class="text-sm text-gray-300">Link do botão</label>
                            <input type="url" name="banner_button_link" class="mt-1 w-full px-4 py-3 bg-secondary border border-gray-700 rounded-xl" placeholder="https://">
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 bg-accent hover:bg-purple-600 transition px-4 py-3 rounded-xl font-semibold">
                            <i class="fas fa-save mr-2"></i>
                            Salvar partida
                        </button>
                        <button type="button" id="resetMatchForm" class="px-4 py-3 rounded-xl border border-gray-600 text-sm text-gray-300 hidden">
                            Cancelar edição
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-primary border border-gray-800 rounded-2xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-sm text-gray-400 uppercase tracking-wide">Jogadores</p>
                        <h2 class="text-2xl font-bold">Cadastro rápido</h2>
                    </div>
                    <span class="text-xs px-3 py-1 rounded-full bg-blue-500/20 text-blue-200">Suporte</span>
                </div>

                <form class="space-y-4 ajax-form" action="{{ route('admin.players.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <label class="text-sm text-gray-300">Nome completo</label>
                        <input type="text" name="name" class="mt-1 w-full px-4 py-3 bg-secondary border border-gray-700 rounded-xl" required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Esporte</label>
                        <select name="sport_id" class="mt-1 w-full px-4 py-3 bg-secondary border border-gray-700 rounded-xl" required>
                            <option value="">Selecione</option>
                            @foreach($sports as $sport)
                                <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Foto do atleta</label>
                        <input type="file" name="photo" accept="image/*" class="mt-1 w-full text-sm text-gray-400">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Bio/Observações</label>
                        <textarea name="bio" rows="3" class="mt-1 w-full px-4 py-3 bg-secondary border border-gray-700 rounded-xl" placeholder="Histórico, títulos, estilo de jogo"></textarea>
                    </div>
                    <button type="submit" data-reload="true" class="w-full bg-secondary hover:bg-gray-700 transition px-4 py-3 rounded-xl font-semibold">
                        <i class="fas fa-user-plus mr-2"></i>
                        Adicionar jogador
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const matchForm = document.getElementById('matchForm');
            const resetBtn = document.getElementById('resetMatchForm');
            const gameSelect = document.getElementById('matchGame');
            const playerSelects = document.querySelectorAll('.player-select');

            function filterPlayersBySport() {
                const sportId = gameSelect?.selectedOptions[0]?.dataset.sport;
                playerSelects.forEach(select => {
                    Array.from(select.options).forEach(option => {
                        if (!option.value) return;
                        option.hidden = sportId && option.dataset.sport && option.dataset.sport !== sportId;
                    });
                });
            }

            gameSelect?.addEventListener('change', filterPlayersBySport);
            filterPlayersBySport();

            function enterMatchEditMode(match) {
                matchForm.action = `{{ url('admin/matches') }}/${match.id}`;
                matchForm.querySelector('[name="_method"]').value = 'PUT';
                matchForm.querySelector('[name="title"]').value = match.title || '';
                matchForm.querySelector('[name="game_id"]').value = match.game_id;
                matchForm.querySelector('[name="status"]').value = match.status || 'scheduled';
                matchForm.querySelector('[name="first_player_id"]').value = match.first_player_id;
                matchForm.querySelector('[name="second_player_id"]').value = match.second_player_id;
                matchForm.querySelector('[name="match_start"]').value = match.match_start || '';
                matchForm.querySelector('[name="betting_deadline"]').value = match.betting_deadline || '';
                matchForm.querySelector('[name="first_player_odds"]').value = match.first_player_odds;
                matchForm.querySelector('[name="second_player_odds"]').value = match.second_player_odds;
                matchForm.querySelector('[name="par_odds"]').value = match.par_odds || '';
                matchForm.querySelector('[name="impar_odds"]').value = match.impar_odds || '';
                matchForm.querySelector('[name="description"]').value = match.description || '';
                matchForm.querySelector('[name="banner_button_label"]').value = match.banner_button_label || '';
                matchForm.querySelector('[name="banner_button_link"]').value = match.banner_button_link || '';
                matchForm.querySelector('[name="featured"]').checked = !!match.featured;
                matchForm.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Atualizar partida';
                resetBtn.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function exitMatchEditMode() {
                matchForm.action = `{{ route('admin.matches.store') }}`;
                matchForm.querySelector('[name="_method"]').value = 'POST';
                matchForm.reset();
                matchForm.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save mr-2"></i>Salvar partida';
                resetBtn.classList.add('hidden');
            }

            document.querySelectorAll('.edit-match').forEach(button => {
                button.addEventListener('click', () => {
                    const data = JSON.parse(button.dataset.match);
                    enterMatchEditMode(data);
                });
            });

            resetBtn?.addEventListener('click', exitMatchEditMode);
        });
    </script>
@endpush
