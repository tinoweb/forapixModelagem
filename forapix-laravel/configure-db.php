<?php
/**
 * Script para configurar banco de dados no .env
 */

$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);

// Configurações do banco remoto
$dbConfig = [
    'DB_CONNECTION=mysql',
    'DB_HOST=srv1660.hstgr.io',
    'DB_PORT=3306',
    'DB_DATABASE=u401260415_forapix',
    'DB_USERNAME=u401260415_forapix',
    'DB_PASSWORD=Forapix1@2'
];

// Substituir configurações do banco
$envContent = preg_replace('/DB_CONNECTION=.*/', $dbConfig[0], $envContent);
$envContent = preg_replace('/DB_HOST=.*/', $dbConfig[1], $envContent);
$envContent = preg_replace('/DB_PORT=.*/', $dbConfig[2], $envContent);
$envContent = preg_replace('/DB_DATABASE=.*/', $dbConfig[3], $envContent);
$envContent = preg_replace('/DB_USERNAME=.*/', $dbConfig[4], $envContent);
$envContent = preg_replace('/DB_PASSWORD=.*/', $dbConfig[5], $envContent);

// Adicionar configurações específicas do JrPix
$forapixConfig = "

# JrPix Specific
JRPIX_ADMIN_EMAIL=admin@jrpix.com
JRPIX_ADMIN_PASSWORD=Admin@2024!
JRPIX_MIN_BET=1.00
JRPIX_MAX_BET=10000.00
JRPIX_COMMISSION=0.05

# PIX Configuration
PIX_MERCHANT_ID=
PIX_MERCHANT_KEY=
PIX_WEBHOOK_URL=

# External API
SISPTS_API_URL=https://api.sispts.com/api/v1
SISPTS_TERMINAL_ID=121088
SISPTS_TERMINAL_SERIAL=f65e0eae-a381-4463-9b51-c0e1be6b4681

# CORS Settings
SANCTUM_STATEFUL_DOMAINS=jrpix.com,localhost:8080
SESSION_DOMAIN=.jrpix.com
";

$envContent .= $forapixConfig;

// Salvar arquivo
file_put_contents($envFile, $envContent);

echo "✅ Configurações do banco atualizadas!\n";
echo "✅ Configurações específicas do JrPix adicionadas!\n";
echo "\nConfiguração do banco:\n";
echo "Host: srv1660.hstgr.io\n";
echo "Database: u401260415_forapix\n";
echo "Username: u401260415_forapix\n";
echo "Password: Forapix1@2\n";
?>
