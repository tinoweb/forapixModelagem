<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ResendService
{
    private Client $client;
    private string $apiKey;
    private string $fromAddress;
    private string $fromName;

    public function __construct()
    {
        $this->apiKey      = config('services.resend.api_key', '');
        $this->fromAddress = config('services.resend.from_address', 'noreply@apostacasada.net');
        $this->fromName    = config('services.resend.from_name', 'ApostaCasada');

        $this->client = new Client([
            'base_uri' => 'https://api.resend.com/',
            'timeout'  => 10,
            'headers'  => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Envia um email via Resend API
     */
    public function send(string $to, string $toName, string $subject, string $html, string $text = ''): bool
    {
        if (empty($this->apiKey)) {
            Log::warning('ResendService: RESEND_API_KEY não configurada. Email não enviado.', compact('to', 'subject'));
            return false;
        }

        try {
            $payload = [
                'from'    => "{$this->fromName} <{$this->fromAddress}>",
                'to'      => ["{$toName} <{$to}>"],
                'subject' => $subject,
                'html'    => $html,
            ];

            if (!empty($text)) {
                $payload['text'] = $text;
            }

            $response = $this->client->post('emails', [
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("ResendService: Email enviado com sucesso para {$to} — {$subject}");
                return true;
            }

            Log::error("ResendService: Falha ao enviar email para {$to}. Status: {$statusCode}");
            return false;

        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $body       = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'sem resposta';
            Log::error("ResendService: HTTP {$statusCode} ao enviar para {$to} | {$e->getMessage()} | Resend response: {$body}");
            return false;
        } catch (\Throwable $e) {
            Log::error("ResendService: Exceção [{$e->getCode()}] ao enviar email para {$to}: {$e->getMessage()}");
            return false;
        }
    }
}
