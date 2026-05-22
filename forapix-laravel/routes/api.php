<?php

use App\Http\Controllers\FileServeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\BetController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\DepositWebhookController;
use App\Http\Controllers\Api\WithdrawWebhookController;
use App\Http\Controllers\Api\WebhookDebugController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Serve arquivos de upload (sem auth) — acessível em /api/uploads/{path}
Route::get('/uploads/{path}', [FileServeController::class, 'serve'])
    ->where('path', '.+')
    ->name('api.uploads.serve');

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [PasswordResetController::class, 'resetPassword']);

// Games and Sports (public)
Route::get('/sports', [GameController::class, 'sports']);
Route::get('/games', [GameController::class, 'index']);
Route::get('/games/{slug}', [GameController::class, 'show']);

// Matches (public)
Route::get('/matches', [MatchController::class, 'index']);
Route::get('/matches/live', [MatchController::class, 'live']);
Route::get('/matches/upcoming', [MatchController::class, 'upcoming']);
Route::get('/matches/{id}', [MatchController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);

    // User info
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
            'message' => 'Usuário autenticado'
        ]);
    });

    // Bets
    Route::get('/bets', [BetController::class, 'index']);
    Route::post('/bets', [BetController::class, 'store']);
    Route::get('/bets/{id}', [BetController::class, 'show']);
    Route::post('/bets/{id}/cancel', [BetController::class, 'cancel']);

    // Wallet
    Route::get('/wallet/balance', [WalletController::class, 'balance']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::get('/wallet/deposit/{transactionId}/status', [WalletController::class, 'depositStatus']);
    Route::post('/wallet/deposit/confirm', [WalletController::class, 'confirmDeposit']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);

    // System Settings (público para frontend)
    Route::get('/settings', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'whatsapp_number' => \App\Models\SystemSetting::get('whatsapp_number', ''),
                'whatsapp_enabled' => \App\Models\SystemSetting::get('whatsapp_enabled', false),
                'support_email' => \App\Models\SystemSetting::get('support_email', 'suporte@apostacasada.com'),
                'min_deposit_amount' => \App\Models\SystemSetting::get('min_deposit_amount', 10),
                'min_withdraw_amount' => \App\Models\SystemSetting::get('min_withdraw_amount', 10),
            ]
        ]);
    });
});

// Webhook VeoPag (sem autenticação — chamado pela plataforma)
Route::post('/webhooks/deposit', [DepositWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/webhooks/withdraw', [WithdrawWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Webhook debug temporário — REMOVER após diagnóstico
Route::post('/webhooks/debug', [WebhookDebugController::class, 'capture'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Test route
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'ForaPix API funcionando!',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
