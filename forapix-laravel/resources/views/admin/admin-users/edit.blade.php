@extends('admin.layouts.app')
@section('title', 'Editar Operador')
@section('breadcrumb', 'Administração / Operadores / Editar')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.admin-users.index') }}" class="text-gray-400 hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Operadores</p>
            <h2 class="text-2xl font-semibold">{{ $adminUser->name }}</h2>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 rounded-xl px-4 py-3 text-sm">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if($errors->any())
    <div class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl px-4 py-3 text-sm space-y-1">
        @foreach($errors->all() as $error)
            <p><i class="fas fa-exclamation-circle mr-1"></i>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    @if($currentUser->id === $adminUser->id)
    <div class="bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 rounded-xl px-4 py-3 text-sm">
        <i class="fas fa-info-circle mr-2"></i>Você está editando sua própria conta. As permissões não podem ser alteradas.
    </div>
    @endif

    <form method="POST" action="{{ route('admin.admin-users.update', $adminUser) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="glass-card p-6 space-y-5">
            <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider border-b border-white/5 pb-3">
                Dados do operador
            </h3>
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Nome completo *</label>
                    <input type="text" name="name" value="{{ old('name', $adminUser->name) }}" required
                           class="input-admin w-full">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $adminUser->email) }}" required
                           class="input-admin w-full">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Status</label>
                    <select name="status" class="input-admin w-full"
                            @if($currentUser->id === $adminUser->id) disabled @endif>
                        <option value="active"    @selected(old('status', $adminUser->status) === 'active')>Ativo</option>
                        <option value="suspended" @selected(old('status', $adminUser->status) === 'suspended')>Suspenso</option>
                    </select>
                    @if($currentUser->id === $adminUser->id)
                        <input type="hidden" name="status" value="{{ $adminUser->status }}">
                    @endif
                </div>
            </div>
        </div>

        {{-- Permissões --}}
        <div class="glass-card p-6 space-y-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider">Permissões de acesso</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Deixe tudo desmarcado para <strong class="text-purple-300">Super Admin</strong> (acesso total).
                </p>
            </div>

            @if($currentUser->id === $adminUser->id)
                <div class="bg-yellow-500/5 border border-yellow-500/20 rounded-xl p-4 text-sm text-yellow-400">
                    <i class="fas fa-lock mr-2"></i>Você não pode alterar suas próprias permissões.
                </div>
                @foreach($adminUser->admin_permissions ?? [] as $perm)
                    <input type="hidden" name="permissions[]" value="{{ $perm }}">
                @endforeach
            @else
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($availablePermissions as $key => $label)
                    <label class="flex items-start gap-3 bg-white/3 hover:bg-white/5 border border-white/5 hover:border-accent/30 rounded-xl p-4 cursor-pointer transition group">
                        <input type="checkbox" name="permissions[]" value="{{ $key }}"
                               @checked(in_array($key, old('permissions', $adminUser->admin_permissions ?? [])))
                               class="mt-0.5 accent-purple-500 w-4 h-4 flex-shrink-0">
                        <div>
                            <p class="text-sm font-medium text-white group-hover:text-accent-light transition">{{ $label }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex gap-3">
            <a href="{{ route('admin.admin-users.index') }}" class="admin-btn-secondary flex-1 text-center">Cancelar</a>
            <button type="submit" class="admin-btn-primary flex-1">
                <i class="fas fa-save"></i> Salvar alterações
            </button>
        </div>
    </form>
</div>
@endsection
