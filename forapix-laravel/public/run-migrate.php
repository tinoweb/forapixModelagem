<?php
/**
 * Executor de artisan migrate via URL — uso único em produção.
 * Acesse: https://apostacasada.net/api/run-migrate.php?key=SUA_CHAVE
 *
 * IMPORTANTE: Delete este arquivo após executar!
 */

define('SECRET_KEY', 'fp-migrate-2024-xK9z');

$key = $_GET['key'] ?? '';
if ($key !== SECRET_KEY) {
    http_response_code(403);
    die(json_encode(['error' => 'Acesso negado']));
}

// Bootstrap Laravel
$appBase = dirname(__DIR__);
require $appBase . '/vendor/autoload.php';

$app = require $appBase . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$results = [];

// ── Executa migrate ────────────────────────────────────────────────────────
$exitCode = $kernel->call('migrate', ['--force' => true]);
$output   = $kernel->output();

$results[] = "=== php artisan migrate --force ===";
$results[] = $output ?: '(sem output)';
$results[] = "Exit code: {$exitCode}";
$results[] = "";

// ── Verifica colunas da tabela users ──────────────────────────────────────
try {
    $columns = Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM users");
    $names   = array_map(fn($c) => $c->Field, $columns);
    $results[] = "=== Colunas da tabela users ===";
    $results[] = implode(', ', $names);
    $results[] = "";
    $results[] = "withdrawable_balance existe: " . (in_array('withdrawable_balance', $names) ? "SIM ✓" : "NÃO ✗");
} catch (\Throwable $e) {
    $results[] = "Erro ao verificar colunas: " . $e->getMessage();
}

$results[] = "";
$results[] = "⚠️  APAGUE ESTE ARQUIVO APÓS O USO!";
$results[] = "Arquivo: public/run-migrate.php";

echo implode("\n", $results);
