@extends('admin.layouts.app')
@section('title', 'Novo Operador')
@section('breadcrumb', 'Administração / Operadores / Novo')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.admin-users.index') }}" class="text-gray-400 hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Operadores</p>
            <h2 class="text-2xl font-semibold">Novo Operador</h2>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 text-sm space-y-1">
        @foreach($errors->all() as $error)
            <p><i class="fas fa-exclamation-circle mr-1"></i>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('admin.admin-users.store') }}" class="space-y-5">
        @csrf

        <div class="glass-card p-6 space-y-5">
            <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider border-b border-white/5 pb-3">
                Dados do operador
            </h3>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Nome completo *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="input-admin w-full" placeholder="Nome do operador">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="input-admin w-full" placeholder="email@apostacasada.net">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Senha *</label>
                    <input type="password" name="password" required minlength="8"
                           class="input-admin w-full" placeholder="Mínimo 8 caracteres">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Confirmar senha *</label>
                    <input type="password" name="password_confirmation" required
                           class="input-admin w-full" placeholder="Repita a senha">
                </div>
            </div>
        </div>

        {{-- Permissões --}}
        <div class="glass-card p-6 space-y-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider">Permissões de acesso</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Deixe tudo desmarcado para criar um <strong class="text-purple-300">Super Admin</strong> (acesso total).
                    Marque apenas as áreas que este operador pode acessar.
                </p>
            </div>

            <div class="bg-purple-500/5 border border-purple-500/20 rounded-xl p-4 flex items-start gap-3">
                <i class="fas fa-crown text-purple-400 mt-0.5"></i>
                <div>
                    <p class="text-sm font-medium text-purple-300">Super Admin</p>
                    <p class="text-xs text-gray-500">Não selecione nenhuma permissão abaixo para conceder acesso total ao sistema.</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                @foreach($availablePermissions as $key => $label)
                <label class="flex items-start gap-3 bg-white/3 hover:bg-white/5 border border-white/5 hover:border-accent/30 rounded-xl p-4 cursor-pointer transition group">
                    <input type="checkbox" name="permissions[]" value="{{ $key }}"
                           @checked(in_array($key, old('permissions', [])))
                           class="mt-0.5 accent-purple-500 w-4 h-4 flex-shrink-0">
                    <div>
                        <p class="text-sm font-medium text-white group-hover:text-accent-light transition">{{ $label }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $permissionDescriptions[$key] ?? '' }}</p>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('admin.admin-users.index') }}" class="admin-btn-secondary flex-1 text-center">Cancelar</a>
            <button type="submit" class="admin-btn-primary flex-1">
                <i class="fas fa-user-plus"></i> Criar operador
            </button>
        </div>
    </form>
</div>
@endsection
