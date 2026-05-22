<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador temporário para diagnóstico de webhooks.
 * REMOVER após confirmar o formato do payload da VeoPag.
 */
class WebhookDebugController extends Controller
{
    public function capture(Request $request)
    {
        $data = [
            'timestamp'  => now()->toIso8601String(),
            'method'     => $request->method(),
            'headers'    => $request->headers->all(),
            'payload'    => $request->all(),
            'raw_body'   => $request->getContent(),
            'ip'         => $request->ip(),
        ];

        Log::info('WEBHOOK_DEBUG capturado', $data);

        return response()->json(['received' => true, 'data' => $data], 200);
    }
}
