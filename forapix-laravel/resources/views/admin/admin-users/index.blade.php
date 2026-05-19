@extends('admin.layouts.app')
@section('title', 'Operadores do Painel')
@section('breadcrumb', 'Administração / Operadores')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Controle de Acesso</p>
            <h2 class="text-2xl font-semibold">Operadores do Painel</h2>
        </div>
        @if($currentUser->isSuperAdmin())
        <a href="{{ route('admin.admin-users.create') }}" class="admin-btn-primary">
            <i class="fas fa-user-shield"></i> Novo Operador
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 rounded-xl px-4 py-3 text-sm">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total operadores</p>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Super Admins</p>
            <p class="text-2xl font-bold text-accent-light">{{ number_format($stats['super']) }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Operadores</p>
            <p class="text-2xl font-bold text-yellow-400">{{ number_format($stats['operators']) }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Ativos</p>
            <p class="text-2xl font-bold text-green-400">{{ number_format($stats['active']) }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="glass-card p-5">
        <form method="GET" class="flex flex-wrap gap-3 text-sm">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Nome ou email" class="input-admin flex-1 min-w-[200px]">
            <select name="type" class="input-admin">
                <option value="">Todos os tipos</option>
                <option value="super"    @selected(request('type') === 'super')>Super Admin</option>
                <option value="operator" @selected(request('type') === 'operator')>Operador</option>
            </select>
            <button type="submit" class="admin-btn-primary">
                <i class="fas fa-search"></i> Filtrar
            </button>
            @if(request()->anyFilled(['search','type']))
                <a href="{{ route('admin.admin-users.index') }}" class="admin-btn-secondary">Limpar</a>
            @endif
        </form>
    </div>

    {{-- Tabela --}}
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-white/5 text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-6 py-4 text-left">Operador</th>
                        <th class="px-6 py-4 text-left">Tipo</th>
                        <th class="px-6 py-4 text-left">Permissões</th>
                        <th class="px-6 py-4 text-left">Status</th>
                        <th class="px-6 py-4 text-left">Último acesso</th>
                        @if($currentUser->isSuperAdmin())
                        <th class="px-6 py-4 text-right">Ações</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($adminUsers as $operator)
                    <tr class="hover:bg-white/2 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($operator->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-white">{{ $operator->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $operator->email }}</p>
                                </div>
                                @if($currentUser->id === $operator->id)
                                    <span class="text-xs bg-accent/20 text-accent-light px-2 py-0.5 rounded-full">Você</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($operator->isSuperAdmin())
                                <span class="inline-flex items-center gap-1 text-xs bg-purple-500/15 text-purple-300 px-2 py-1 rounded-full">
                                    <i class="fas fa-crown text-[10px]"></i> Super Admin
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs bg-yellow-500/15 text-yellow-300 px-2 py-1 rounded-full">
                                    <i class="fas fa-user-cog text-[10px]"></i> Operador
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($operator->isSuperAdmin())
                                <span class="text-xs text-gray-400">Acesso total</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach($operator->admin_permissions ?? [] as $perm)
                                        <span class="text-[11px] bg-white/5 border border-white/10 px-2 py-0.5 rounded-full text-gray-300">
                                            {{ $availablePermissions[$perm] ?? $perm }}
                                        </span>
                                    @endforeach
                                    @if(empty($operator->admin_permissions))
                                        <span class="text-xs text-gray-500">Nenhuma</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($operator->status === 'active')
                                <span class="inline-flex items-center gap-1 text-xs bg-green-500/15 text-green-400 px-2 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span> Ativo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs bg-red-500/15 text-red-400 px-2 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span> Suspenso
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            {{ $operator->last_login_at ? $operator->last_login_at->diffForHumans() : 'Nunca' }}
                        </td>
                        @if($currentUser->isSuperAdmin())
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.admin-users.edit', $operator) }}"
                                   class="text-xs bg-white/5 hover:bg-accent/20 text-gray-300 hover:text-white px-3 py-1.5 rounded-lg transition">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($currentUser->id !== $operator->id)
                                <button onclick="toggleStatus({{ $operator->id }}, '{{ $operator->name }}', '{{ $operator->status }}')"
                                        class="text-xs bg-white/5 hover:bg-yellow-500/20 text-gray-300 hover:text-yellow-400 px-3 py-1.5 rounded-lg transition">
                                    <i class="fas fa-{{ $operator->status === 'active' ? 'ban' : 'check' }}"></i>
                                </button>
                                <button onclick="resetPassword({{ $operator->id }}, '{{ $operator->name }}')"
                                        class="text-xs bg-white/5 hover:bg-blue-500/20 text-gray-300 hover:text-blue-400 px-3 py-1.5 rounded-lg transition">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button onclick="deleteOperator({{ $operator->id }}, '{{ $operator->name }}')"
                                        class="text-xs bg-white/5 hover:bg-red-500/20 text-gray-300 hover:text-red-400 px-3 py-1.5 rounded-lg transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-user-shield text-3xl mb-3 block opacity-30"></i>
                            Nenhum operador encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($adminUsers->hasPages())
            <div class="px-6 py-4 border-t border-white/5">
                {{ $adminUsers->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Modal de confirmação --}}
<div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-[#151a35] border border-white/10 rounded-2xl p-6 max-w-sm w-full mx-4">
        <h3 id="modalTitle" class="text-lg font-semibold mb-2"></h3>
        <p id="modalBody" class="text-sm text-gray-400 mb-6"></p>
        <div class="flex gap-3">
            <button onclick="closeModal()" class="admin-btn-secondary flex-1">Cancelar</button>
            <button id="modalConfirm" class="admin-btn-primary flex-1">Confirmar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let pendingAction = null;

function openModal(title, body, action) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').textContent = body;
    pendingAction = action;
    document.getElementById('confirmModal').classList.remove('hidden');
    document.getElementById('confirmModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    document.getElementById('confirmModal').classList.remove('flex');
    pendingAction = null;
}

document.getElementById('modalConfirm').addEventListener('click', () => {
    if (pendingAction) pendingAction();
    closeModal();
});

function toggleStatus(id, name, currentStatus) {
    const action = currentStatus === 'active' ? 'suspender' : 'ativar';
    openModal(
        `${action.charAt(0).toUpperCase() + action.slice(1)} operador`,
        `Deseja ${action} o operador "${name}"?`,
        () => adminRequest(`{{ url('admin/admin-users') }}/${id}/toggle-status`, 'POST')
    );
}

function resetPassword(id, name) {
    openModal(
        'Redefinir senha',
        `Deseja gerar uma nova senha aleatória para "${name}"?`,
        () => adminRequest(`{{ url('admin/admin-users') }}/${id}/reset-password`, 'POST', true)
    );
}

function deleteOperator(id, name) {
    openModal(
        'Excluir operador',
        `Tem certeza que deseja excluir "${name}"? Esta ação não pode ser desfeita.`,
        () => adminRequest(`{{ url('admin/admin-users') }}/${id}`, 'DELETE')
    );
}

async function adminRequest(url, method, showPassword = false) {
    try {
        const res = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            if (showPassword && data.password) {
                alert(`Nova senha: ${data.password}\n\nAnote antes de fechar!`);
            }
            showAdminToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAdminToast(data.message || 'Erro ao executar.', 'error');
        }
    } catch (e) {
        showAdminToast('Erro de conexão.', 'error');
    }
}

function showAdminToast(msg, type = 'success') {
    const color = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const toast = document.createElement('div');
    toast.className = `${color} text-white text-sm px-4 py-3 rounded-xl shadow-lg`;
    toast.textContent = msg;
    document.getElementById('adminToast').appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}
</script>
@endpush
@endsection
