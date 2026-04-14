@extends('admin.layouts.app')

@section('title', 'Novo Jogo')
@section('breadcrumb', 'Jogos > Criar')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Cadastro</p>
                    <h2 class="text-2xl font-semibold">Cadastrar jogo</h2>
                </div>
                <a href="{{ route('admin.games.index') }}" class="px-4 py-2 rounded-2xl border border-white/10 text-sm text-gray-300 hover:text-white">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Voltar
                </a>
            </div>

            <form class="space-y-4 ajax-form" action="{{ route('admin.games.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Nome do jogo</label>
                        <input type="text" name="name" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Esporte</label>
                        <select name="sport_id" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                            <option value="">Selecione</option>
                            @foreach($sports as $sport)
                                <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Tipo</label>
                        <select name="type" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                            @foreach(['head_to_head' => 'Head to head','sinuca' => 'Sinuca','par_impar' => 'Par/Ímpar','casino' => 'Cassino','bingo' => 'Bingo'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Status</label>
                        <select name="status" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                            @foreach(['active' => 'Ativo','inactive' => 'Inativo','maintenance' => 'Manutenção'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Aposta mínima (R$)</label>
                        <input type="number" step="0.01" min="1" name="min_bet" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" value="10" required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Aposta máxima (R$)</label>
                        <input type="number" step="0.01" min="1" name="max_bet" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" value="5000" required>
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Margem da casa (%)</label>
                    <input type="number" step="0.01" min="0" max="20" name="house_edge" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" value="5" required>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Descrição</label>
                    <textarea name="description" rows="3" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="Resumo do jogo e orientações"></textarea>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Imagem / Banner</label>
                    <input type="file" name="image" accept="image/*" class="mt-1 w-full text-sm text-gray-400">
                    <p class="text-xs text-gray-500 mt-1">PNG ou JPG até 2MB</p>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Configurações extras (JSON)</label>
                    <textarea name="settings" rows="4" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder='{"show_results":true}'></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-purple-500 to-indigo-500 px-4 py-3 rounded-2xl font-semibold">
                        <i class="fas fa-save mr-2"></i>
                        Salvar jogo
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
