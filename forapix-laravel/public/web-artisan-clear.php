<?php

declare(strict_types=1);

function readEnvValue(string $key, string $envFile): ?string
{
    if (!is_file($envFile) || !is_readable($envFile)) {
        return null;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return null;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $k = trim(substr($line, 0, $pos));
        if ($k !== $key) {
            continue;
        }

        $v = trim(substr($line, $pos + 1));
        if ($v !== '' && (($v[0] === '"' && str_ends_with($v, '"')) || ($v[0] === "'" && str_ends_with($v, "'")))) {
            $v = substr($v, 1, -1);
        }

        return $v;
    }

    return null;
}

header('Content-Type: application/json; charset=utf-8');

$envFile = realpath(__DIR__ . '/../.env') ?: (__DIR__ . '/../.env');
$expectedKey = readEnvValue('WEB_ARTISAN_KEY', $envFile);

$key = (string) ($_GET['key'] ?? '');
$action = (string) ($_GET['action'] ?? '');
$delete = (string) ($_GET['delete'] ?? '');

if (!$expectedKey || $expectedKey === '') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'WEB_ARTISAN_KEY não está configurada no .env.',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if (!hash_equals($expectedKey, $key)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado.',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($action !== 'clear') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ação inválida. Use ?action=clear',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require __DIR__ . '/../bootstrap/app.php';

    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $results = [];
    foreach (['config:clear', 'cache:clear', 'route:clear'] as $cmd) {
        $exitCode = Illuminate\Support\Facades\Artisan::call($cmd);
        $results[] = [
            'command'   => $cmd,
            'exit_code' => $exitCode,
            'output'    => trim((string) Illuminate\Support\Facades\Artisan::output()),
        ];
    }

    $deleted = false;
    if ($delete === '1') {
        $deleted = @unlink(__FILE__);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Comandos executados.',
        'results' => $results,
        'self_deleted' => $deleted,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao executar comandos.',
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
