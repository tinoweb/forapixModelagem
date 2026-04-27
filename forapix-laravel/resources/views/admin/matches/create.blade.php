@extends('admin.layouts.app')

@section('title', 'Agendar Partida')
@section('breadcrumb', 'Partidas > Criar')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Cadastro</p>
                    <h2 class="text-2xl font-semibold">Nova partida</h2>
                </div>
                <a href="{{ route('admin.matches.index') }}" class="px-4 py-2 rounded-2xl border border-white/10 text-sm text-gray-300 hover:text-white">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Voltar
                </a>
            </div>

            <form class="space-y-4 ajax-form" action="{{ route('admin.matches.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Título</label>
                        <input type="text" name="title" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="Sinuca - Par ou Ímpar">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Jogo</label>
                        <select name="game_id" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                            <option value="">Selecione</option>
                            @foreach($games as $game)
                                <option value="{{ $game->id }}">{{ $game->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Jogador 1</label>
                        <select name="first_player_id" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                            <option value="">Selecione</option>
                            @foreach($players as $player)
                                <option value="{{ $player->id }}">{{ $player->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Jogador 2</label>
                        <select name="second_player_id" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                            <option value="">Selecione</option>
                            @foreach($players as $player)
                                <option value="{{ $player->id }}">{{ $player->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Início da partida</label>
                        <input type="datetime-local" name="match_start" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Limite de apostas</label>
                        <input type="datetime-local" name="betting_deadline" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Odds jogador 1</label>
                        <input type="number" step="0.01" min="1.01" name="first_player_odds" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" value="1.80" required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Odds jogador 2</label>
                        <input type="number" step="0.01" min="1.01" name="second_player_odds" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" value="1.90" required>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Odds Empate</label>
                        <input type="number" step="0.01" min="1.01" name="draw_odds" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="3.00">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Odds Par</label>
                        <input type="number" step="0.01" min="1.01" name="par_odds" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="1.85">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Odds Ímpar</label>
                        <input type="number" step="0.01" min="1.01" name="impar_odds" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="1.95">
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Descrição / transmissão</label>
                    <textarea name="description" rows="3" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="Detalhes, regras ou link da transmissão"></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Link da transmissão (stream)</label>
                        <input type="url" name="stream_url" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="https://youtube.com/...">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Fim da partida (opcional)</label>
                        <input type="datetime-local" name="match_end" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Banner</label>
                        <input type="file" name="banner_image" accept="image/*" class="mt-1 w-full text-sm text-gray-400">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Texto do botão</label>
                        <input type="text" name="banner_button_label" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="Apostar agora">
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Link do botão</label>
                    <input type="url" name="banner_button_link" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="https://">
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-emerald-500 to-lime-500 px-4 py-3 rounded-2xl font-semibold">
                        <i class="fas fa-save mr-2"></i>
                        Salvar partida
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
