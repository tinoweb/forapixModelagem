<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMailBuilder;
use App\Models\User;
use App\Services\ResendService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Controller responsável pelo fluxo de recuperação de senha
 * via e-mail (esqueci minha senha).
 *
 * Fluxo:
 *  1. forgotPassword: gera token, salva hash em password_reset_tokens
 *     e dispara e-mail via Resend.
 *  2. resetPassword: valida token + email, atualiza senha do usuário
 *     e revoga tokens de acesso existentes.
 */
class PasswordResetController extends Controller
{
    /**
     * Tempo de expiração do token em minutos.
     */
    private const TOKEN_EXPIRATION_MINUTES = 60;

    /**
     * Tempo mínimo entre solicitações para o mesmo e-mail (segundos).
     */
    private const THROTTLE_SECONDS = 60;

    public function __construct(
        private readonly ResendService $resend,
        private readonly PasswordResetMailBuilder $mailBuilder
    ) {
    }

    /**
     * Solicita o envio de e-mail de recuperação de senha.
     * Sempre responde 200 (genérico) para não revelar se o e-mail existe.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'E-mail inválido',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));
        $genericResponse = response()->json([
            'success' => true,
            'message' => 'Se o e-mail estiver cadastrado, enviaremos as instruções de recuperação.',
        ]);

        $user = User::where('email', $email)->first();
        if (!$user) {
            // Resposta genérica — não revela se o e-mail existe
            return $genericResponse;
        }

        // Throttle: bloqueia novas tentativas dentro do limite
        $existing = DB::table('password_reset_tokens')->where('email', $email)->first();
        if ($existing && $existing->created_at) {
            $secondsSince = now()->diffInSeconds($existing->created_at);
            if ($secondsSince < self::THROTTLE_SECONDS) {
                return $genericResponse;
            }
        }

        // Gera token aleatório (plain) e armazena versão hash
        $plainToken  = Str::random(64);
        $hashedToken = hash('sha256', $plainToken);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token'      => $hashedToken,
                'created_at' => now(),
            ]
        );

        // Monta link e envia o e-mail
        try {
            $resetUrl = $this->buildResetUrl($plainToken, $email);
            $html = $this->mailBuilder->buildHtml($user->name, $resetUrl, self::TOKEN_EXPIRATION_MINUTES);
            $text = $this->mailBuilder->buildText($user->name, $resetUrl, self::TOKEN_EXPIRATION_MINUTES);

            $this->resend->send(
                $user->email,
                $user->name,
                'Recuperação de senha — ApostaCasada',
                $html,
                $text
            );
        } catch (\Throwable $e) {
            Log::error('PasswordResetController@forgotPassword: erro ao enviar e-mail', [
                'email'   => $email,
                'message' => $e->getMessage(),
            ]);
            // Mantém resposta genérica mesmo em erro de envio
        }

        return $genericResponse;
    }

    /**
     * Valida token e redefine a senha do usuário.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email      = strtolower(trim($request->input('email')));
        $plainToken = $request->input('token');
        $password   = $request->input('password');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado.',
            ], 400);
        }

        $hashedToken = hash('sha256', $plainToken);
        if (!hash_equals($record->token, $hashedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado.',
            ], 400);
        }

        // Verifica expiração
        $createdAt = $record->created_at ? \Carbon\Carbon::parse($record->created_at) : null;
        if (!$createdAt || $createdAt->diffInMinutes(now()) > self::TOKEN_EXPIRATION_MINUTES) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Token expirado. Solicite uma nova recuperação.',
            ], 400);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        // Atualiza senha
        $user->password = Hash::make($password);
        $user->save();

        // Remove o token usado (single-use)
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Revoga todos os tokens Sanctum existentes — força novo login
        try {
            $user->tokens()->delete();
        } catch (\Throwable $e) {
            Log::warning('PasswordResetController@resetPassword: falha ao revogar tokens', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Senha redefinida com sucesso. Faça login com a nova senha.',
        ]);
    }

    /**
     * Monta a URL absoluta do frontend para a tela de redefinição.
     */
    private function buildResetUrl(string $token, string $email): string
    {
        $base = rtrim((string) config('services.frontend.url'), '/');
        $query = http_build_query([
            'reset-token' => $token,
            'email'       => $email,
        ]);
        return "{$base}/?{$query}";
    }
}
