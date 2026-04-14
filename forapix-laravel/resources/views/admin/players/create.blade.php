@extends('admin.layouts.app')

@section('title', 'Novo Jogador')
@section('breadcrumb', 'Jogadores > Criar')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Cadastro</p>
                    <h2 class="text-2xl font-semibold">Cadastrar atleta</h2>
                </div>
                <a href="{{ route('admin.players.index') }}" class="px-4 py-2 rounded-2xl border border-white/10 text-sm text-gray-300 hover:text-white">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Voltar
                </a>
            </div>

            <form class="space-y-4" action="{{ route('admin.players.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="text-sm text-gray-300">Nome completo</label>
                    <input type="text" name="name" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Esporte</label>
                        <select name="sport_id" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                            <option value="">Selecione</option>
                            @foreach($sports as $sport)
                                <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Nacionalidade (ISO)</label>
                        <input type="text" name="nationality" maxlength="3" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="BRA">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Data de nascimento</label>
                        <input type="date" name="birth_date" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Rating inicial</label>
                        <input type="number" step="0.01" name="rating" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" value="1000">
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Foto / Avatar</label>
                    <input type="file" name="photo" accept="image/*" class="mt-1 w-full text-sm text-gray-400">
                </div>

                <div>
                    <label class="text-sm text-gray-300">Bio</label>
                    <textarea name="bio" rows="4" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="Histórico, conquistas e estilo de jogo"></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-purple-500 to-indigo-500 px-4 py-3 rounded-2xl font-semibold">
                        <i class="fas fa-save mr-2"></i>
                        Salvar jogador
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
