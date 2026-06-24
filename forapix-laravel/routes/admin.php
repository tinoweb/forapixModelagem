<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\GameManagementController;
use App\Http\Controllers\Admin\PlayerManagementController;
use App\Http\Controllers\Admin\BetManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\FinancialController;
use App\Http\Controllers\Admin\SettingsController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Rotas para o painel administrativo do JrPix
|
*/

// Authentication Routes (não protegidas)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/refresh-csrf', function() {
        return response()->json(['token' => csrf_token()]);
    })->name('refresh-csrf');
});

// Protected Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['admin'])->group(function () {
    
    // Dashboard
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard.index');
    
    // Dashboard API endpoints
    Route::get('/api/activities', [AdminController::class, 'getRecentActivities'])->name('api.activities');
    Route::get('/api/financial-summary', [AdminController::class, 'getFinancialSummary'])->name('api.financial-summary');
    Route::get('/api/chart-data', [AdminController::class, 'getChartData'])->name('api.chart-data');
    Route::get('/api/system-health', [AdminController::class, 'systemHealth'])->name('api.system-health');

    // Profile Management
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::get('/profile/sessions', [AuthController::class, 'sessions'])->name('profile.sessions');
    
    // Two-Factor Authentication
    Route::get('/profile/two-factor', [AuthController::class, 'showTwoFactor'])->name('profile.two-factor');
    Route::post('/profile/two-factor/enable', [AuthController::class, 'enableTwoFactor'])->name('profile.two-factor.enable');
    Route::post('/profile/two-factor/disable', [AuthController::class, 'disableTwoFactor'])->name('profile.two-factor.disable');

    // Game Management
    Route::prefix('games')->name('games.')->group(function () {
        Route::get('/', [GameManagementController::class, 'games'])->name('index');
        Route::get('/create', [GameManagementController::class, 'showCreateGameForm'])->name('create');
        Route::get('/{game}/edit', [GameManagementController::class, 'showEditGameForm'])->name('edit');
        Route::get('/{game}/delete', [GameManagementController::class, 'confirmDeleteGame'])->name('delete');
        Route::post('/', [GameManagementController::class, 'createGame'])->name('store');
        Route::put('/{game}', [GameManagementController::class, 'updateGame'])->name('update');
        Route::delete('/{game}', [GameManagementController::class, 'deleteGame'])->name('destroy');
    });

    // Match Management
    Route::prefix('matches')->name('matches.')->group(function () {
        Route::get('/', [GameManagementController::class, 'matches'])->name('index');
        Route::get('/create', [GameManagementController::class, 'showCreateMatchForm'])->name('create');
        Route::get('/{match}/edit', [GameManagementController::class, 'showEditMatchForm'])->name('edit');
        Route::get('/{match}/delete', [GameManagementController::class, 'confirmDeleteMatch'])->name('delete');
        Route::post('/', [GameManagementController::class, 'createMatch'])->name('store');
        Route::put('/{match}', [GameManagementController::class, 'updateMatch'])->name('update');
        Route::delete('/{match}', [GameManagementController::class, 'deleteMatch'])->name('destroy');
        Route::get('/{match}/stats', [GameManagementController::class, 'getMatchStats'])->name('stats');
        Route::post('/bulk-update', [GameManagementController::class, 'bulkUpdateMatches'])->name('bulk-update');
        Route::post('/{match}/resolve-bets', [BetManagementController::class, 'resolveMatch'])->name('resolve-bets');
        Route::post('/{match}/cancel', [GameManagementController::class, 'cancelMatch'])->name('cancel');
        Route::post('/{match}/toggle-live-betting', [GameManagementController::class, 'toggleLiveBetting'])->name('toggle-live-betting');
        Route::post('/{match}/update-score', [GameManagementController::class, 'updateScore'])->name('update-score');
    });

    // Bet Management
    Route::prefix('bets')->name('bets.')->group(function () {
        Route::get('/', [BetManagementController::class, 'index'])->name('index');
        Route::get('/{bet}', [BetManagementController::class, 'show'])->name('show');
        Route::post('/{bet}/cancel', [BetManagementController::class, 'cancel'])->name('cancel');
    });

    // Player Management
    Route::prefix('players')->name('players.')->group(function () {
        Route::get('/', [PlayerManagementController::class, 'index'])->name('index');
        Route::get('/create', [PlayerManagementController::class, 'create'])->name('create');
        Route::post('/', [PlayerManagementController::class, 'store'])->name('store');
        Route::get('/{player}/edit', [PlayerManagementController::class, 'edit'])->name('edit');
        Route::put('/{player}', [PlayerManagementController::class, 'update'])->name('update');
        Route::get('/{player}/delete', [PlayerManagementController::class, 'confirmDelete'])->name('delete');
        Route::delete('/{player}', [PlayerManagementController::class, 'destroy'])->name('destroy');
        Route::get('/api/list', [PlayerManagementController::class, 'apiIndex'])->name('api');
    });

    // Test email
    Route::post('/test-email', [GameManagementController::class, 'testEmail'])->name('test-email');

    // Financial Management
    Route::prefix('financial')->name('financial.')->group(function () {
        Route::get('/',                                    [FinancialController::class, 'index'])->name('index');
        Route::post('/withdrawals/{transaction}/approve', [FinancialController::class, 'approveWithdrawal'])->name('withdrawals.approve');
        Route::post('/withdrawals/{transaction}/reject',  [FinancialController::class, 'rejectWithdrawal'])->name('withdrawals.reject');
        Route::post('/deposits/{transaction}/approve',    [FinancialController::class, 'approveDeposit'])->name('deposits.approve');
        Route::post('/deposits/reconcile',                [FinancialController::class, 'reconcileDeposits'])->name('deposits.reconcile');
    });

    // Admin Users (Operadores do painel)
    Route::prefix('admin-users')->name('admin-users.')->group(function () {
        Route::get('/',                          [AdminUsersController::class, 'index'])->name('index');
        Route::get('/create',                    [AdminUsersController::class, 'create'])->name('create');
        Route::post('/',                         [AdminUsersController::class, 'store'])->name('store');
        Route::get('/{adminUser}/edit',          [AdminUsersController::class, 'edit'])->name('edit');
        Route::put('/{adminUser}',               [AdminUsersController::class, 'update'])->name('update');
        Route::post('/{adminUser}/reset-password', [AdminUsersController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{adminUser}/toggle-status',  [AdminUsersController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{adminUser}',            [AdminUsersController::class, 'destroy'])->name('destroy');
    });

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::post('/{user}/suspend', [UserManagementController::class, 'suspend'])->name('suspend');
        Route::post('/{user}/activate', [UserManagementController::class, 'activate'])->name('activate');
        Route::post('/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
    });

    // System Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
    });

    /*
    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::post('/{user}/suspend', [UserManagementController::class, 'suspend'])->name('suspend');
        Route::post('/{user}/activate', [UserManagementController::class, 'activate'])->name('activate');
        Route::get('/{user}/bets', [UserManagementController::class, 'getUserBets'])->name('bets');
        Route::get('/{user}/transactions', [UserManagementController::class, 'getUserTransactions'])->name('transactions');
    });

    // Bet Management
    Route::prefix('bets')->name('bets.')->group(function () {
        Route::get('/', [BetManagementController::class, 'index'])->name('index');
        Route::get('/{bet}', [BetManagementController::class, 'show'])->name('show');
        Route::post('/{bet}/resolve', [BetManagementController::class, 'resolve'])->name('resolve');
        Route::post('/{bet}/cancel', [BetManagementController::class, 'cancel'])->name('cancel');
        Route::post('/bulk-resolve', [BetManagementController::class, 'bulkResolve'])->name('bulk-resolve');
    });

    // Financial Management
    Route::prefix('financial')->name('financial.')->group(function () {
        Route::get('/', [FinancialController::class, 'index'])->name('index');
        Route::get('/transactions', [FinancialController::class, 'transactions'])->name('transactions');
        Route::get('/deposits', [FinancialController::class, 'deposits'])->name('deposits');
        Route::get('/withdrawals', [FinancialController::class, 'withdrawals'])->name('withdrawals');
        Route::get('/reports', [FinancialController::class, 'reports'])->name('reports');
        Route::post('/transactions/{transaction}/approve', [FinancialController::class, 'approveTransaction'])->name('transactions.approve');
        Route::post('/transactions/{transaction}/reject', [FinancialController::class, 'rejectTransaction'])->name('transactions.reject');
    });

    // System Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::post('/notifications', [SettingsController::class, 'createNotification'])->name('notifications.store');
        Route::get('/banners', [SettingsController::class, 'banners'])->name('banners');
        Route::post('/banners', [SettingsController::class, 'createBanner'])->name('banners.store');
        Route::get('/logs', [SettingsController::class, 'logs'])->name('logs');
        Route::post('/cache/clear', [SettingsController::class, 'clearCache'])->name('cache.clear');
    });
    */
});
