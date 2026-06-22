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

                <!-- Campos ocultos para compatibilidade com a validação/banco -->
                <input type="hidden" name="sport_id" value="{{ $sports->firstWhere('slug', 'sinuca')?->id ?? $sports->first()?->id }}">
                <input type="hidden" name="nationality" value="BRA">
                <input type="hidden" name="rating" value="1000">

                <div>
                    <label class="text-sm text-gray-300">Nome completo</label>
                    <input type="text" name="name" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                </div>

                <div>
                    <label class="text-sm text-gray-300">Foto / Avatar</label>
                    <input type="file" name="photo" accept="image/*" class="mt-1 w-full text-sm text-gray-400">
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
