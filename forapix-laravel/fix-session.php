<?php

/**
 * Script para corrigir configurações de sessão
 */

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "Arquivo .env não encontrado!\n";
    exit(1);
}

$envContent = file_get_contents($envFile);

// Configurações de sessão para corrigir o erro 419
$sessionConfigs = [
    'SESSION_LIFETIME' => '480',  // 8 horas
    'SESSION_DRIVER' => 'file',
    'SESSION_ENCRYPT' => 'false',
    'SESSION_EXPIRE_ON_CLOSE' => 'false',
];

foreach ($sessionConfigs as $key => $value) {
    $pattern = "/^{$key}=.*$/m";
    $replacement = "{$key}={$value}";
    
    if (preg_match($pattern, $envContent)) {
        $envContent = preg_replace($pattern, $replacement, $envContent);
        echo "Atualizado: {$key}={$value}\n";
    } else {
        $envContent .= "\n{$replacement}";
        echo "Adicionado: {$key}={$value}\n";
    }
}

file_put_contents($envFile, $envContent);

echo "\nConfiguração de sessão atualizada com sucesso!\n";
echo "Execute: php artisan config:clear\n";
?>
