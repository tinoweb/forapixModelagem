<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->keyBy('key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_number' => 'nullable|string|max:20',
            'whatsapp_enabled' => 'nullable',
            'support_email' => 'nullable|email',
            'min_deposit_amount' => 'nullable|numeric|min:1',
            'min_withdraw_amount' => 'nullable|numeric|min:1',
            'min_bet_amount' => 'nullable|numeric|min:1',
        ]);

        // WhatsApp
        SystemSetting::set('whatsapp_number', $validated['whatsapp_number'] ?? '', 'string', 'Número de WhatsApp para suporte (formato: 5511999999999)');
        SystemSetting::set('whatsapp_enabled', $request->has('whatsapp_enabled'), 'boolean', 'Habilitar botão WhatsApp no suporte');

        // Email de suporte
        SystemSetting::set('support_email', $validated['support_email'] ?? 'suporte@apostacasada.com', 'string', 'Email de suporte');

        // Limites financeiros
        SystemSetting::set('min_deposit_amount', $validated['min_deposit_amount'] ?? 10, 'number', 'Valor mínimo de depósito');
        SystemSetting::set('min_withdraw_amount', $validated['min_withdraw_amount'] ?? 10, 'number', 'Valor mínimo de saque');
        SystemSetting::set('min_bet_amount', $validated['min_bet_amount'] ?? 5, 'number', 'Valor mínimo de aposta');

        return response()->json([
            'success' => true,
            'message' => 'Configurações atualizadas com sucesso!',
        ]);
    }
}
