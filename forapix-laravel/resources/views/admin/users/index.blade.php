@extends('admin.layouts.app')
@section('title', 'Usuários')
@section('breadcrumb', 'Usuários / Listagem')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Cadastros</p>
            <h2 class="text-2xl font-semibold">Gestão de Usuários</h2>
        </div>
        <a href="{{ route('admin.users.create') }}" class="admin-btn-primary">
            <i class="fas fa-user-plus"></i> Novo Usuário
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total</p>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Ativos</p>
            <p class="text-2xl font-bold text-green-400">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Suspensos</p>
            <p class="text-2xl font-bold text-red-400">{{ number_format($stats['suspended']) }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Novos hoje</p>
            <p class="text-2xl font-bold text-accent-light">{{ number_format($stats['new_today']) }}</p>
        </div>
    </div>

    {{-- Filtros + Tabela --}}
    <div class="glass-card p-6">
        <form method="GET" class="grid md:grid-cols-4 gap-3 mb-6 text-sm">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Nome ou email" class="input-admin md:col-span-2">
            <select name="status" class="input-admin">
                <option value="">Todos os status</option>
                <option value="active"    @selected(request('status') === 'active')>Ativo</option>
                <option value="suspended" @selected(request('status') === 'suspended')>Suspenso</option>
                <option value="pending"   @selected(request('status') === 'pending')>Pendente</option>
            </select>
            <button type="submit" class="admin-btn-primary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-400 uppercase border-b border-white/5">
                        <th class="pb-3 text-left">Usuário</th>
                        <th class="pb-3 text-left">Email</th>
                        <th class="pb-3 text-center">Saldo</th>
                        <th class="pb-3 text-center">Apostas</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3 text-center">Cadastro</th>
                        <th class="pb-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($users as $user)
                    <tr class="hover:bg-white/2 transition">
                        <td class="py-3 pr-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center text-xs font-bold text-accent-light flex-shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-white">{{ $user->name }}</p>
                                    @if($user->is_admin)
                                        <span class="text-[10px] text-yellow-400 font-bold uppercase">Admin</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-3 pr-4 text-gray-300">{{ $user->email }}</td>
                        <td class="py-3 text-center font-semibold text-green-400">
                            R$ {{ number_format($user->balance, 2, ',', '.') }}
                        </td>
                        <td class="py-3 text-center text-gray-300">{{ $user->bets_count }}</td>
                        <td class="py-3 text-center">
                            @php
                                $sc = ['active'=>'text-green-400 bg-green-400/10','suspended'=>'text-red-400 bg-red-400/10','pending'=>'text-yellow-400 bg-yellow-400/10'];
                                $sl = ['active'=>'Ativo','suspended'=>'Suspenso','pending'=>'Pendente'];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-[11px] font-bold {{ $sc[$user->status] ?? 'text-gray-400 bg-gray-400/10' }}">
                                {{ $sl[$user->status] ?? $user->status }}
                            </span>
                        </td>
                        <td class="py-3 text-center text-gray-400 text-xs">
                            {{ $user->created_at->format('d/m/Y') }}
                        </td>
                        <td class="py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.users.show', $user) }}"
                                   class="text-accent-light hover:text-white transition" title="Ver detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($user->status === 'active' && !$user->is_admin)
                                    <button onclick="suspendUser({{ $user->id }}, '{{ $user->name }}')"
                                            class="text-red-400 hover:text-red-300 transition" title="Suspender">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                @elseif($user->status === 'suspended')
                                    <button onclick="activateUser({{ $user->id }}, '{{ $user->name }}')"
                                            class="text-green-400 hover:text-green-300 transition" title="Ativar">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center text-gray-500">
                            <i class="fas fa-users text-3xl mb-3 block opacity-30"></i>
                            Nenhum usuário encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $users->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
const ADMIN_USERS_BASE = '{{ rtrim(url('/admin/users'), '/') }}';

async function suspendUser(id, name) {
    if (!confirm(`Suspender o usuário "${name}"?`)) return;
    const res = await fetch(`${ADMIN_USERS_BASE}/${id}/suspend`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    const data = await res.json();
    alert(data.message);
    if (data.success) location.reload();
}

async function activateUser(id, name) {
    if (!confirm(`Ativar o usuário "${name}"?`)) return;
    const res = await fetch(`${ADMIN_USERS_BASE}/${id}/activate`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    const data = await res.json();
    alert(data.message);
    if (data.success) location.reload();
}
</script>
@endpush
@endsection
