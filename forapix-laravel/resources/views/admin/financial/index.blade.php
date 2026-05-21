@extends('admin.layouts.app')
@section('title', 'Financeiro')
@section('breadcrumb', 'Financeiro')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Gestão</p>
            <h2 class="text-2xl font-semibold">Financeiro</h2>
        </div>
    </div>

    {{-- Cards de resumo --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="glass-card p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Depósitos hoje</p>
            <p class="text-lg font-bold text-blue-400">R$ {{ number_format($stats['deposits_today'], 2, ',', '.') }}</p>
        </div>
        <div class="glass-card p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Depósitos pendentes</p>
            <p class="text-lg font-bold text-yellow-400">{{ $stats['deposits_pending'] }}</p>
        </div>
        <div class="glass-card p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Saques pendentes</p>
            <p class="text-lg font-bold {{ $stats['withdrawals_pending'] > 0 ? 'text-red-400' : 'text-gray-400' }}">
                {{ $stats['withdrawals_pending'] }}
            </p>
        </div>
        <div class="glass-card p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Saques hoje</p>
            <p class="text-lg font-bold text-orange-400">R$ {{ number_format($stats['withdrawals_today'], 2, ',', '.') }}</p>
        </div>
        <div class="glass-card p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Fluxo líquido (mês)</p>
            <p class="text-lg font-bold {{ $stats['net_flow_month'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                R$ {{ number_format($stats['net_flow_month'], 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-white/10">
        <a href="?tab=withdrawals"
           class="px-4 py-2 text-sm font-medium transition rounded-t-lg {{ $tab === 'withdrawals' ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white' }}">
            <i class="fas fa-arrow-up mr-2"></i>Saques
            @if($stats['withdrawals_pending'] > 0)
                <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs bg-red-500 text-white rounded-full">{{ $stats['withdrawals_pending'] }}</span>
            @endif
        </a>
        <a href="?tab=deposits"
           class="px-4 py-2 text-sm font-medium transition rounded-t-lg {{ $tab === 'deposits' ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white' }}">
            <i class="fas fa-arrow-down mr-2"></i>Depósitos
            @if($stats['deposits_pending'] > 0)
                <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs bg-yellow-500 text-white rounded-full">{{ $stats['deposits_pending'] }}</span>
            @endif
        </a>
    </div>

    {{-- ══ TAB SAQUES ══ --}}
    @if($tab === 'withdrawals')
    <div class="glass-card overflow-hidden">
        {{-- Filtros --}}
        <div class="p-4 border-b border-white/5">
            <form method="GET" action="" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tab" value="withdrawals">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Buscar usuário</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Nome ou e-mail..."
                        class="input-admin text-sm w-52">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Status</label>
                    <select name="status" class="input-admin text-sm">
                        <option value="">Todos</option>
                        <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pendente</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Concluído</option>
                        <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Falhou</option>
                    </select>
                </div>
                <button type="submit" class="admin-btn-primary text-sm">Filtrar</button>
                <a href="?tab=withdrawals" class="admin-btn-ghost text-sm">Limpar</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-400 uppercase bg-white/3">
                    <tr>
                        <th class="px-4 py-3 text-left">Usuário</th>
                        <th class="px-4 py-3 text-left">Valor</th>
                        <th class="px-4 py-3 text-left">Chave PIX</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Data</th>
                        <th class="px-4 py-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($withdrawals as $tx)
                    <tr class="hover:bg-white/3 transition" id="row-w-{{ $tx->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $tx->user->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $tx->user->email ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 font-bold text-orange-400">
                            R$ {{ number_format($tx->amount, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-gray-300 text-xs">
                            {{ $tx->metadata['pix_key'] ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $sc = ['pending'=>'text-yellow-400 bg-yellow-400/10','completed'=>'text-green-400 bg-green-400/10','failed'=>'text-red-400 bg-red-400/10'];
                                $sl = ['pending'=>'Pendente','completed'=>'Pago','failed'=>'Falhou'];
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $sc[$tx->status] ?? '' }}">
                                {{ $sl[$tx->status] ?? $tx->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $tx->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($tx->status === 'pending')
                            <div class="flex justify-center gap-2">
                                <button onclick="approveWithdrawal({{ $tx->id }})"
                                    class="px-2 py-1 text-xs bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30 transition">
                                    <i class="fas fa-check"></i> Aprovar
                                </button>
                                <button onclick="rejectWithdrawal({{ $tx->id }})"
                                    class="px-2 py-1 text-xs bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition">
                                    <i class="fas fa-times"></i> Rejeitar
                                </button>
                            </div>
                            @else
                                <span class="text-gray-600 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-arrow-up text-2xl mb-2 block opacity-30"></i>
                            Nenhum saque encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
        <div class="p-4 border-t border-white/5">
            {{ $withdrawals->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- ══ TAB DEPÓSITOS ══ --}}
    @if($tab === 'deposits')
    <div class="glass-card overflow-hidden">
        {{-- Filtros --}}
        <div class="p-4 border-b border-white/5">
            <form method="GET" action="" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tab" value="deposits">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Buscar usuário</label>
                    <input type="text" name="dep_search" value="{{ request('dep_search') }}"
                        placeholder="Nome ou e-mail..."
                        class="input-admin text-sm w-52">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Status</label>
                    <select name="dep_status" class="input-admin text-sm">
                        <option value="">Todos</option>
                        <option value="pending"   {{ request('dep_status') === 'pending'   ? 'selected' : '' }}>Pendente</option>
                        <option value="completed" {{ request('dep_status') === 'completed' ? 'selected' : '' }}>Confirmado</option>
                        <option value="failed"    {{ request('dep_status') === 'failed'    ? 'selected' : '' }}>Falhou</option>
                    </select>
                </div>
                <button type="submit" class="admin-btn-primary text-sm">Filtrar</button>
                <a href="?tab=deposits" class="admin-btn-ghost text-sm">Limpar</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-400 uppercase bg-white/3">
                    <tr>
                        <th class="px-4 py-3 text-left">Usuário</th>
                        <th class="px-4 py-3 text-left">Valor</th>
                        <th class="px-4 py-3 text-left">Referência</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Data</th>
                        <th class="px-4 py-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($deposits as $tx)
                    <tr class="hover:bg-white/3 transition" id="row-d-{{ $tx->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $tx->user->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $tx->user->email ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 font-bold text-blue-400">
                            R$ {{ number_format($tx->amount, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $tx->transaction_id }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $sc = ['pending'=>'text-yellow-400 bg-yellow-400/10','completed'=>'text-green-400 bg-green-400/10','failed'=>'text-red-400 bg-red-400/10'];
                                $sl = ['pending'=>'Pendente','completed'=>'Confirmado','failed'=>'Falhou'];
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $sc[$tx->status] ?? '' }}">
                                {{ $sl[$tx->status] ?? $tx->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $tx->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($tx->status === 'pending')
                            <button onclick="approveDeposit({{ $tx->id }})"
                                class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition">
                                <i class="fas fa-check"></i> Confirmar
                            </button>
                            @else
                                <span class="text-gray-600 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-arrow-down text-2xl mb-2 block opacity-30"></i>
                            Nenhum depósito encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deposits->hasPages())
        <div class="p-4 border-t border-white/5">
            {{ $deposits->links() }}
        </div>
        @endif
    </div>
    @endif

</div>

@push('scripts')
<script>
const BASE = '{{ url("/admin/financial") }}';
const CSRF = '{{ csrf_token() }}';

async function approveWithdrawal(id) {
    if (!confirm('Confirmar aprovação deste saque? Isso marca como pago manualmente.')) return;
    const r = await fetch(`${BASE}/withdrawals/${id}/approve`, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json'},
    });
    const data = await r.json();
    if (data.success) {
        document.getElementById(`row-w-${id}`)?.remove();
        showAdminToast(data.message, 'success');
        updateBadges();
    } else {
        showAdminToast(data.message, 'error');
    }
}

async function rejectWithdrawal(id) {
    const note = prompt('Motivo da rejeição (opcional):') ?? '';
    if (note === null) return;
    const r = await fetch(`${BASE}/withdrawals/${id}/reject`, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify({note}),
    });
    const data = await r.json();
    if (data.success) {
        document.getElementById(`row-w-${id}`)?.remove();
        showAdminToast(data.message, 'success');
        updateBadges();
    } else {
        showAdminToast(data.message, 'error');
    }
}

async function approveDeposit(id) {
    if (!confirm('Confirmar este depósito manualmente?')) return;
    const r = await fetch(`${BASE}/deposits/${id}/approve`, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json'},
    });
    const data = await r.json();
    if (data.success) {
        document.getElementById(`row-d-${id}`)?.remove();
        showAdminToast(data.message, 'success');
    } else {
        showAdminToast(data.message, 'error');
    }
}

function showAdminToast(msg, type) {
    const toast = document.createElement('div');
    const colors = {success: '#22c55e', error: '#ef4444'};
    toast.style.cssText = `position:fixed;bottom:24px;right:24px;background:#1e2540;border:1px solid ${colors[type]||'#7c3aed'};color:#fff;padding:12px 20px;border-radius:12px;font-size:14px;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.4)`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

function updateBadges() { setTimeout(() => window.location.reload(), 1500); }
</script>
@endpush
@endsection
