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
     * Cria um saque PIX na VeoPag.
     *
     * @param float  $amount      Valor em BRL
     * @param string $pixKey      Chave PIX do destinatário
     * @param string $externalId  ID único do seu sistema (idempotência)
     * @param array  $recipient   ['name', 'document']
     * @param string $callbackUrl URL do webhook de confirmação
     *
     * @return array ['transactionId', 'status', 'amount', 'fee']
     */
    public function createWithdrawal(float $amount, string $pixKey, string $externalId, array $recipient, string $callbackUrl = ''): array
    {
        $token    = $this->getToken();
        $keyType  = $this->detectPixKeyType($pixKey);
        $document = preg_replace('/\D/', '', $recipient['document'] ?? '');

        // Normalizar telefone para formato +55DDDNUMERO
        $normalizedKey = $pixKey;
        if ($keyType === 'PHONE') {
            $cleanPhone = preg_replace('/\D/', '', $pixKey);
            if (!str_starts_with($cleanPhone, '55')) {
                $cleanPhone = '55' . $cleanPhone;
            }
            $normalizedKey = '+' . $cleanPhone;
        }

        $payload = [
            'amount'      => $amount,
            'external_id' => $externalId,
            'pix_key'     => $normalizedKey,
            'key_type'    => $keyType,
            'name'        => $recipient['name'],
            'description' => "Saque #{$externalId}",
        ];

        // taxId — sempre enviar quando disponível para validação do titular
        if (!empty($document)) {
            $payload['taxId'] = $document;
        }

        if (!empty($callbackUrl)) {
            $payload['clientCallbackUrl'] = $callbackUrl;
        }

        Log::info('VeoPag: enviando saque', [
            'external_id' => $externalId,
            'key_type'    => $keyType,
            'pix_key'     => $normalizedKey,
            'amount'      => $amount,
            'name'        => $recipient['name'] ?? '(vazio)',
            'taxId'       => $document ?: '(vazio)',
        ]);

        $resp = Http::withToken($token)
            ->post("{$this->baseUrl}/api/withdrawals/withdraw", $payload);

        if ($resp->status() === 401) {
            Cache::forget('veopag_token');
            $token = $this->getToken();
            $resp  = Http::withToken($token)
                ->post("{$this->baseUrl}/api/withdrawals/withdraw", $payload);
        }

        if (!$resp->successful()) {
            Log::error('VeoPag: erro ao criar saque', [
                'status'      => $resp->status(),
                'body'        => $resp->body(),
                'external_id' => $externalId,
            ]);
            throw new \RuntimeException($resp->json('message') ?? 'Erro ao processar saque PIX');
        }

        $body       = $resp->json();
        $withdrawal = $body['withdrawal'] ?? $body;

        return [
            'transactionId' => $withdrawal['transaction_id'] ?? $withdrawal['transactionId'] ?? $externalId,
            'status'        => $withdrawal['status'] ?? 'pending',
            'amount'        => (float) ($withdrawal['amount'] ?? $amount),
            'fee'           => (float) ($withdrawal['fee'] ?? 0),
        ];
    }

    /**
     * Detecta o tipo de chave PIX com base no formato.
     *
     * Telefone celular BR: DDD (11-99) + 9 + 8 dígitos = 11 dígitos
     * CPF: 11 dígitos (mas não começa com DDD+9)
     * CNPJ: 14 dígitos
     */
    private function detectPixKeyType(string $pixKey): string
    {
        $clean = preg_replace('/\D/', '', $pixKey);

        // Copia e Cola (BR Code EMV)
        if (str_starts_with($pixKey, '00020101')) {
            return 'COPIAECOLA';
        }

        // Email
        if (str_contains($pixKey, '@')) {
            return 'EMAIL';
        }

        // Chave aleatória (EVP / UUID v4)
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $pixKey)) {
            return 'EVP';
        }

        // Já vem com +55 — é telefone
        if (str_starts_with($pixKey, '+55') || str_starts_with($clean, '55') && strlen($clean) === 13) {
            return 'PHONE';
        }

        // CNPJ: 14 dígitos
        if (strlen($clean) === 14) {
            return 'CNPJ';
        }

        // Telefone celular BR: 11 dígitos onde DDD (2 dígitos) + 9 (indicador móvel)
        // DDDs válidos: 11-99, terceiro dígito deve ser 9
        if (strlen($clean) === 11) {
            $ddd   = (int) substr($clean, 0, 2);
            $digit = substr($clean, 2, 1);
            if ($ddd >= 11 && $ddd <= 99 && $digit === '9') {
                return 'PHONE';
            }
            return 'CPF';
        }

        // Telefone fixo ou com formato diferente (10 dígitos)
        if (strlen($clean) === 10) {
            $ddd = (int) substr($clean, 0, 2);
            if ($ddd >= 11 && $ddd <= 99) {
                return 'PHONE';
            }
        }

        return 'CPF';
    }

    /**
     * Consulta o status de um depósito na VeoPag pelo external_id (nosso ID).
     * Endpoint: GET /api/transactions/deposit?external_id={id}
     *
     * @return array ['status', 'amount', 'paid_at']
     */
    public function getDepositStatus(string $externalId): array
    {
        $token = $this->getToken();

        $resp = Http::withToken($token)
            ->get("{$this->baseUrl}/api/transactions/deposit", [
                'external_id' => $externalId,
            ]);

        if ($resp->status() === 401) {
            Cache::forget('veopag_token');
            $token = $this->getToken();
            $resp  = Http::withToken($token)
                ->get("{$this->baseUrl}/api/transactions/deposit", [
                    'external_id' => $externalId,
                ]);
        }

        if (!$resp->successful()) {
            Log::warning('VeoPag: erro ao consultar depósito', [
                'external_id' => $externalId,
                'status'      => $resp->status(),
                'body'        => $resp->body(),
            ]);
            throw new \RuntimeException('Erro ao consultar status do depósito');
        }

        $body    = $resp->json();
        $deposit = $body['deposit'] ?? $body;

        return [
            'status'  => strtoupper($deposit['status'] ?? ''),
            'amount'  => (float) ($deposit['amount'] ?? 0),
            'paid_at' => $deposit['updated_at'] ?? null,
            'raw'     => $deposit,
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
