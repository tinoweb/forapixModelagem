@extends('admin.layouts.app')

@section('title', 'Meu Perfil')
@section('breadcrumb', 'Meu Perfil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Conta</p>
                <h2 class="text-2xl font-semibold">Meu Perfil</h2>
            </div>
        </div>

        <form class="space-y-6" action="{{ route('admin.profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-300">Nome</label>
                    <input type="text" name="name" value="{{ $user->name }}" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                </div>
                <div>
                    <label class="text-sm text-gray-300">Email</label>
                    <input type="email" name="email" value="{{ $user->email }}" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" required>
                </div>
            </div>

            <div class="border-t border-white/10 pt-6">
                <h3 class="text-lg font-semibold mb-4">Alterar Senha</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm text-gray-300">Senha atual</label>
                        <input type="password" name="current_password" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl">
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-300">Nova senha</label>
                            <input type="password" name="password" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="Mínimo 8 caracteres">
                        </div>
                        <div>
                            <label class="text-sm text-gray-300">Confirmar nova senha</label>
                            <input type="password" name="password_confirmation" class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" placeholder="Repita a senha">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-6 py-3 rounded-2xl border border-white/10 text-sm text-gray-300 hover:text-white">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-accent hover:bg-accent/80 text-white text-sm font-medium rounded-2xl transition">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
