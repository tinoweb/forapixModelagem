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
                            @foreach($games as $game)
                                <option value="{{ $game->id }}" selected>{{ $game->name }}</option>
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
                        <label class="text-sm text-gray-300">Hora de início do jogo</label>
                        <input type="datetime-local" name="match_start" id="match_start" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Hora de término do jogo</label>
                        <input type="datetime-local" name="match_end" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl">
                    </div>
                </div>
                <!-- betting_deadline é preenchido automaticamente com o horário de início -->
                <input type="hidden" name="betting_deadline" id="betting_deadline">



                <div>
                    <label class="text-sm text-gray-300">Descrição / transmissão</label>
                    <textarea name="description" rows="3" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="Detalhes, regras ou link da transmissão"></textarea>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Link da transmissão (stream)</label>
                    <input type="url" name="stream_url" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="https://youtube.com/...">
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

                <div class="flex flex-col gap-3 py-2">
                    <label class="text-sm text-gray-300 flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="featured" value="1" class="w-4 h-4 text-accent rounded border-white/10 bg-white/5">
                        Destacar partida na home
                    </label>
                    <label class="text-sm text-gray-300 flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="betting_locked" value="1" class="w-4 h-4 text-accent rounded border-white/10 bg-white/5">
                        <span class="text-yellow-400 font-medium">Trancar Apostas (bloqueia novos palpites e impede cancelamentos pelos usuários)</span>
                    </label>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const matchStartInput = document.getElementById('match_start');
    const bettingDeadlineInput = document.getElementById('betting_deadline');

    function syncDeadline() {
        if (matchStartInput && bettingDeadlineInput) {
            bettingDeadlineInput.value = matchStartInput.value;
        }
    }

    if (matchStartInput) {
        matchStartInput.addEventListener('change', syncDeadline);
    }

    // Sync antes do envio do formulário
    const form = document.querySelector('.ajax-form');
    if (form) {
        form.addEventListener('submit', syncDeadline);
    }
});
</script>
@endpush
