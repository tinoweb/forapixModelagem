@extends('admin.layouts.app')
@section('title', 'Usuário — ' . $user->name)
@section('breadcrumb', 'Usuários / Detalhes')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Cadastros</p>
            <h2 class="text-2xl font-semibold">{{ $user->name }}</h2>
        </div>
        <div class="flex gap-2">
            @if($user->status === 'active' && !$user->is_admin)
                <button onclick="suspendUser({{ $user->id }}, '{{ $user->name }}')" class="admin-btn-ghost text-red-400">
                    <i class="fas fa-ban"></i> Suspender
                </button>
            @elseif($user->status === 'suspended')
                <button onclick="activateUser({{ $user->id }})" class="admin-btn-ghost text-green-400">
                    <i class="fas fa-check-circle"></i> Ativar
                </button>
            @endif
            <button onclick="resetPassword({{ $user->id }})" class="admin-btn-ghost">
                <i class="fas fa-key"></i> Resetar Senha
            </button>
            <a href="{{ route('admin.users.index') }}" class="admin-btn-ghost">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Dados do usuário --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="glass-card p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-14 h-14 rounded-full bg-accent/20 flex items-center justify-center text-xl font-bold text-accent-light">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">{{ $user->name }}</h3>
                        <p class="text-sm text-gray-400">{{ $user->email }}</p>
                        @php
                            $sc = ['active'=>'text-green-400','suspended'=>'text-red-400','pending'=>'text-yellow-400'];
                            $sl = ['active'=>'Ativo','suspended'=>'Suspenso','pending'=>'Pendente'];
                        @endphp
                        <span class="text-xs font-bold {{ $sc[$user->status] ?? 'text-gray-400' }}">
                            {{ $sl[$user->status] ?? $user->status }}
                        </span>
                        @if($user->is_admin)
                            <span class="ml-2 text-xs font-bold text-yellow-400">ADMIN</span>
                        @endif
                    </div>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Saldo atual</span>
                        <span class="font-bold text-green-400">R$ {{ number_format($user->balance, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total apostado</span>
                        <span class="text-white">R$ {{ number_format($totalBet, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total ganho</span>
                        <span class="text-green-400">R$ {{ number_format($totalWon, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Apostas pendentes</span>
                        <span class="text-yellow-400">{{ $pendingBets }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total apostas</span>
                        <span>{{ $user->bets->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Cadastrado em</span>
                        <span>{{ $user->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($user->last_login_at)
                    <div class="flex justify-between">
                        <span class="text-gray-400">Último acesso</span>
                        <span>{{ \Carbon\Carbon::parse($user->last_login_at)->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Editar dados --}}
            <div class="glass-card p-6">
                <h4 class="font-semibold mb-4 text-sm uppercase tracking-wider text-gray-400">Editar dados</h4>
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4 text-sm">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-gray-400 mb-1">Nome</label>
                        <input type="text" name="name" value="{{ $user->name }}" class="input-admin w-full" required>
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-1">Email</label>
                        <input type="email" name="email" value="{{ $user->email }}" class="input-admin w-full" required>
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-1">Saldo (R$)</label>
                        <input type="number" name="balance" value="{{ $user->balance }}" step="0.01" min="0" class="input-admin w-full">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_admin" id="isAdmin" value="1" {{ $user->is_admin ? 'checked' : '' }} class="rounded">
                        <label for="isAdmin" class="text-gray-300">Administrador</label>
                    </div>
                    <button type="submit" class="admin-btn-primary w-full">
                        <i class="fas fa-save"></i> Salvar alterações
                    </button>
                    @if(session('success'))
                        <p class="text-green-400 text-xs text-center">{{ session('success') }}</p>
                    @endif
                </form>
            </div>
        </div>

        {{-- Apostas recentes --}}
        <div class="lg:col-span-2">
            <div class="glass-card p-6">
                <h4 class="font-semibold mb-4">Últimas apostas</h4>
                @if($bets->isEmpty())
                    <p class="text-gray-500 text-sm text-center py-8">Nenhuma aposta ainda.</p>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase border-b border-white/5">
                                <th class="pb-3 text-left">Código</th>
                                <th class="pb-3 text-left">Partida</th>
                                <th class="pb-3 text-center">Tipo</th>
                                <th class="pb-3 text-center">Valor</th>
                                <th class="pb-3 text-center">Status</th>
                                <th class="pb-3 text-center">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($bets as $bet)
                            @php
                                $bs = ['pending'=>['Pendente','text-yellow-400 bg-yellow-400/10'],'won'=>['Ganhou','text-green-400 bg-green-400/10'],'lost'=>['Perdeu','text-red-400 bg-red-400/10'],'cancelled'=>['Cancelada','text-gray-400 bg-gray-400/10']];
                                [$bl, $bc] = $bs[$bet->status] ?? [$bet->status,'text-gray-400 bg-gray-400/10'];
                                $typeMap = ['first_player'=>$bet->match?->firstPlayer?->name ?? 'J1','second_player'=>$bet->match?->secondPlayer?->name ?? 'J2','draw'=>'Empate','par'=>'Par','impar'=>'Ímpar'];
                            @endphp
                            <tr>
                                <td class="py-2 font-mono text-xs text-gray-400">{{ $bet->bet_id }}</td>
                                <td class="py-2 text-xs text-gray-300">
                                    {{ $bet->match?->firstPlayer?->name ?? '—' }} vs {{ $bet->match?->secondPlayer?->name ?? '—' }}
                                </td>
                                <td class="py-2 text-center text-xs">{{ $typeMap[$bet->bet_type] ?? $bet->bet_type }}</td>
                                <td class="py-2 text-center font-semibold">R$ {{ number_format($bet->amount, 2, ',', '.') }}</td>
                                <td class="py-2 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $bc }}">{{ $bl }}</span>
                                </td>
                                <td class="py-2 text-center text-xs text-gray-400">
                                    {{ $bet->placed_at?->format('d/m H:i') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
async function suspendUser(id, name) {
    if (!confirm(`Suspender "${name}"?`)) return;
    const res = await fetch(`/admin/users/${id}/suspend`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    const data = await res.json();
    alert(data.message);
    if (data.success) location.reload();
}
async function activateUser(id) {
    const res = await fetch(`/admin/users/${id}/activate`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    const data = await res.json();
    alert(data.message);
    if (data.success) location.reload();
}
async function resetPassword(id) {
    if (!confirm('Resetar a senha deste usuário?')) return;
    const res = await fetch(`/admin/users/${id}/reset-password`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    const data = await res.json();
    if (data.success) alert(`Nova senha: ${data.password}\n\nGuarde esta senha e envie ao usuário.`);
    else alert(data.message);
}
</script>
@endpush
@endsection
