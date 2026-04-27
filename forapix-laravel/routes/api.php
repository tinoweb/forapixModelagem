<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\BetController;
use App\Http\Controllers\Api\WalletController;

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

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

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
    Route::post('/wallet/deposit/confirm', [WalletController::class, 'confirmDeposit']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);
});

// Test route
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'ForaPix API funcionando!',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
