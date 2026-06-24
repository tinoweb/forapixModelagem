<?php
/**
 * Script de Diagnóstico - JrPix Laravel
 * Acesse: https://jrpix.com/api/diagnose.php
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnóstico JrPix - Laravel</h1>";
echo "<pre>";

// 1. Verificar estrutura de pastas
echo "\n=== 1. ESTRUTURA DE PASTAS ===\n";
$requiredDirs = ['app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'storage', 'vendor'];
foreach ($requiredDirs as $dir) {
    $exists = is_dir(__DIR__ . '/' . $dir) ? '✅' : '❌';
    $writable = is_dir(__DIR__ . '/' . $dir) && is_writable(__DIR__ . '/' . $dir) ? '(writable)' : '(read-only)';
    echo sprintf("%-20s %s %s\n", $dir . '/', $exists, $writable);
}

// 2. Verificar arquivos essenciais
echo "\n=== 2. ARQUIVOS ESSENCIAIS ===\n";
$requiredFiles = ['.env', 'artisan', 'composer.json', 'public/index.php', 'public/.htaccess', '.htaccess'];
foreach ($requiredFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file) ? '✅' : '❌';
    echo sprintf("%-30s %s\n", $file, $exists);
}

// 3. Verificar .htaccess atual
echo "\n=== 3. CONTEÚDO DO .HTACCESS (na raiz de api/) ===\n";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo htmlspecialchars(file_get_contents(__DIR__ . '/.htaccess'));
} else {
    echo "❌ .htaccess não encontrado na raiz de api/\n";
}

// 4. Verificar .htaccess em public/
echo "\n=== 4. CONTEÚDO DO .HTACCESS (em public/) ===\n";
if (file_exists(__DIR__ . '/public/.htaccess')) {
    echo htmlspecialchars(file_get_contents(__DIR__ . '/public/.htaccess'));
} else {
    echo "❌ .htaccess não encontrado em public/\n";
}

// 5. Verificar versão do PHP
echo "\n=== 5. VERSÃO DO PHP ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Required: 8.1+\n";

// 6. Verificar extensões do PHP
echo "\n=== 6. EXTENSÕES DO PHP ===\n";
$requiredExtensions = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext) ? '✅' : '❌';
    echo sprintf("%-20s %s\n", $ext, $loaded);
}

// 7. Verificar permissões de storage
echo "\n=== 7. PERMISSÕES STORAGE ===\n";
$storageDirs = ['storage', 'storage/logs', 'storage/framework', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views'];
foreach ($storageDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? '✅' : '❌';
        echo sprintf("%-40s %s (perms: %s)\n", $dir, $writable, $perms);
    } else {
        echo sprintf("%-40s ❌ (não existe)\n", $dir);
    }
}

// 8. Verificar conteúdo do .env (parcial)
echo "\n=== 8. CONFIGURAÇÕES .ENV ===\n";
if (file_exists(__DIR__ . '/.env')) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    echo "APP_ENV: " . (preg_match('/APP_ENV=(.+)/', $envContent, $m) ? $m[1] : 'não definido') . "\n";
    echo "APP_DEBUG: " . (preg_match('/APP_DEBUG=(.+)/', $envContent, $m) ? $m[1] : 'não definido') . "\n";
    echo "APP_URL: " . (preg_match('/APP_URL=(.+)/', $envContent, $m) ? $m[1] : 'não definido') . "\n";
    echo "DB_CONNECTION: " . (preg_match('/DB_CONNECTION=(.+)/', $envContent, $m) ? $m[1] : 'não definido') . "\n";
    echo "DB_DATABASE: " . (preg_match('/DB_DATABASE=(.+)/', $envContent, $m) ? $m[1] : 'não definido') . "\n";
} else {
    echo "❌ .env não encontrado\n";
}

// 9. Verificar logs do Laravel
echo "\n=== 9. LOGS DO LARAVEL ===\n";
$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    echo "Arquivo de log existe: ✅\n";
    echo "Tamanho: " . round($logSize / 1024, 2) . " KB\n";
    echo "\n--- ÚLTIMAS 50 LINHAS DO LOG ---\n";
    $logContent = file_get_contents($logFile);
    $logLines = explode("\n", $logContent);
    $lastLines = array_slice($logLines, -50);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line) . "\n";
    }
} else {
    echo "❌ Arquivo de log não encontrado em storage/logs/laravel.log\n";
}

// 10. Tentar carregar o Laravel
echo "\n=== 10. TENTATIVA DE CARREGAR LARAVEL ===\n";
try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require __DIR__ . '/vendor/autoload.php';
        echo "✅ Autoloader carregado\n";
    } else {
        echo "❌ vendor/autoload.php não encontrado\n";
    }

    if (file_exists(__DIR__ . '/bootstrap/app.php')) {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        echo "✅ Application carregada\n";
        echo "Environment: " . $app->environment() . "\n";
        echo "Debug mode: " . ($app->hasDebugModeEnabled() ? 'on' : 'off') . "\n";
    } else {
        echo "❌ bootstrap/app.php não encontrado\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao carregar Laravel: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// 11. Informações do servidor
echo "\n=== 11. INFORMAÇÕES DO SERVIDOR ===\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "\n";

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
echo "</pre>";
echo "<p style='color: red; font-weight: bold;'>IMPORTANTE: Delete este arquivo após o diagnóstico!</p>";
