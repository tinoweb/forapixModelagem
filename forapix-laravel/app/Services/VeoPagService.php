<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VeoPagService
{
    private string $baseUrl = 'https://api.veopag.com';
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->clientId     = config('services.veopag.client_id', '');
        $this->clientSecret = config('services.veopag.client_secret', '');
    }

    /**
     * Retorna token autenticado com cache de 55 minutos.
     */
    public function getToken(): string
    {
        return Cache::remember('veopag_token', now()->addMinutes(55), function () {
            $resp = Http::post("{$this->baseUrl}/api/auth/login", [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if (!$resp->successful()) {
                Log::error('VeoPag: falha na autenticação', [
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
                throw new \RuntimeException('VeoPag: falha ao autenticar — ' . $resp->body());
            }

            return $resp->json('token');
        });
    }

    /**
     * Cria um depósito PIX na VeoPag.
     *
     * @param float  $amount      Valor em BRL
     * @param string $externalId  ID único do seu sistema (idempotência)
     * @param array  $payer       ['name', 'email', 'document', 'phone'?]
     * @param string $callbackUrl URL do webhook
     *
     * @return array ['transactionId', 'qrcode', 'amount', 'fee', 'status']
     */
    public function createDeposit(float $amount, string $externalId, array $payer, string $callbackUrl = ''): array
    {
        $token = $this->getToken();

        $payload = [
            'amount'      => $amount,
            'external_id' => $externalId,
            'payer'       => [
                'name'     => $payer['name'],
                'email'    => $payer['email'],
                'document' => preg_replace('/\D/', '', $payer['document'] ?? '00000000000'),
            ],
        ];

        if (!empty($payer['phone'])) {
            $payload['payer']['phone'] = preg_replace('/\D/', '', $payer['phone']);
        }

        if (!empty($callbackUrl)) {
            $payload['clientCallbackUrl'] = $callbackUrl;
        }

        $resp = Http::withToken($token)
            ->post("{$this->baseUrl}/api/payments/deposit", $payload);

        // Token expirado — limpa cache e retenta uma vez
        if ($resp->status() === 401) {
            Cache::forget('veopag_token');
            $token = $this->getToken();
            $resp  = Http::withToken($token)
                ->post("{$this->baseUrl}/api/payments/deposit", $payload);
        }

        if (!$resp->successful()) {
            Log::error('VeoPag: erro ao criar depósito', [
                'status'      => $resp->status(),
                'body'        => $resp->body(),
                'external_id' => $externalId,
            ]);
            throw new \RuntimeException($resp->json('message') ?? 'Erro ao criar depósito PIX');
        }

        $body = $resp->json();

        // Idempotência: cobrança já existia
        if (isset($body['idempotent']) && $body['idempotent']) {
            return [
                'transactionId' => $body['transaction_id'],
                'qrcode'        => $body['qrcode'],
                'amount'        => (float) $body['amount'],
                'fee'           => 0,
                'status'        => $body['status'],
                'idempotent'    => true,
            ];
        }

        $qr = $body['qrCodeResponse'];

        return [
            'transactionId' => $qr['transactionId'],
            'qrcode'        => $qr['qrcode'],
            'amount'        => (float) $qr['amount'],
            'fee'           => (float) ($qr['fee'] ?? 0),
            'status'        => $qr['status'],
            'idempotent'    => false,
        ];
    }

    /**
     * Verifica se as credenciais estão configuradas.
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
}
