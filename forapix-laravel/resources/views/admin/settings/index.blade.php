@extends('admin.layouts.app')

@section('title', 'Configurações')
@section('breadcrumb', 'Configurações')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-[12px] uppercase tracking-[0.3em] text-gray-500">Sistema</p>
                <h2 class="text-2xl font-semibold">Configurações</h2>
            </div>
        </div>

        <form class="space-y-6 ajax-form" action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- WhatsApp -->
            <div class="border-t border-white/10 pt-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <i class="fab fa-whatsapp text-green-500"></i> WhatsApp
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="whatsapp_enabled" id="whatsapp_enabled" 
                            {{ $settings['whatsapp_enabled']->value ?? false ? 'checked' : '' }}
                            class="w-5 h-5 rounded bg-[#10162c] border-white/10 text-accent focus:ring-accent">
                        <label for="whatsapp_enabled" class="text-sm text-gray-300">Habilitar botão WhatsApp no suporte</label>
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Número de WhatsApp</label>
                        <input type="text" name="whatsapp_number" 
                            value="{{ $settings['whatsapp_number']->value ?? '' }}"
                            class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl" 
                            placeholder="5511999999999">
                        <p class="text-xs text-gray-500 mt-1">Formato: 5511999999999 (código do país + DDD + número, sem traços ou espaços)</p>
                    </div>
                </div>
            </div>

            <!-- Suporte -->
            <div class="border-t border-white/10 pt-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <i class="fas fa-envelope text-accent"></i> Suporte
                </h3>
                <div>
                    <label class="text-sm text-gray-300">Email de suporte</label>
                    <input type="email" name="support_email" 
                        value="{{ $settings['support_email']->value ?? 'suporte@apostacasada.com' }}"
                        class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl">
                </div>
            </div>

            <!-- Limites Financeiros -->
            <div class="border-t border-white/10 pt-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <i class="fas fa-dollar-sign text-yellow-500"></i> Limites Financeiros
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-300">Depósito mínimo (R$)</label>
                        <input type="number" step="0.01" min="1" name="min_deposit_amount" 
                            value="{{ $settings['min_deposit_amount']->value ?? 10 }}"
                            class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl">
                    </div>
                    <div>
                        <label class="text-sm text-gray-300">Saque mínimo (R$)</label>
                        <input type="number" step="0.01" min="1" name="min_withdraw_amount" 
                            value="{{ $settings['min_withdraw_amount']->value ?? 10 }}"
                            class="mt-1 w-full px-4 py-3 bg-[#10162c] border border-white/10 rounded-2xl">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="submit" class="px-6 py-3 bg-accent hover:bg-accent/80 text-white text-sm font-medium rounded-2xl transition">
                    <i class="fas fa-save mr-2"></i> Salvar configurações
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.querySelector('.ajax-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Salvando...';

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showAdminToast(data.message, 'success');
        } else {
            showAdminToast(data.message || 'Erro ao salvar', 'error');
        }
    } catch (err) {
        showAdminToast('Erro de conexão', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
</script>
@endpush
@endsection
