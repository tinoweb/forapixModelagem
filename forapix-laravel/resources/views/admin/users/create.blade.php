@extends('admin.layouts.app')
@section('title', 'Novo Usuário')
@section('breadcrumb', 'Usuários / Novo')

@section('content')
<div class="max-w-lg mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold">Criar Usuário</h2>
        <a href="{{ route('admin.users.index') }}" class="admin-btn-ghost">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="glass-card p-6">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 text-sm">
            @csrf

            @if($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-red-400 text-sm">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block text-gray-400 mb-1">Nome completo *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="input-admin w-full" required>
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" class="input-admin w-full" required>
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Senha *</label>
                <input type="password" name="password" class="input-admin w-full" required minlength="8" placeholder="Mínimo 8 caracteres">
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Saldo inicial (R$)</label>
                <input type="number" name="balance" value="{{ old('balance', 0) }}" step="0.01" min="0" class="input-admin w-full">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_admin" id="isAdmin" value="1" {{ old('is_admin') ? 'checked' : '' }} class="rounded">
                <label for="isAdmin" class="text-gray-300">Conceder acesso administrativo</label>
            </div>

            <button type="submit" class="admin-btn-primary w-full mt-2">
                <i class="fas fa-user-plus"></i> Criar Usuário
            </button>
        </form>
    </div>
</div>
@endsection
