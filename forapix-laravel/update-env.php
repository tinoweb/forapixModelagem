<?php
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "Arquivo .env não encontrado";
    exit(1);
}
$vars = [
    'SESSION_DOMAIN' => 'localhost',
    'SESSION_SECURE_COOKIE' => 'false',
    'APP_URL' => 'http://localhost:8000',
];
$env = file_get_contents($envFile);
foreach ($vars as $key => $value) {
    $pattern = "/^{$key}=.*$/m";
    $line = "{$key}={$value}";
    if (preg_match($pattern, $env)) {
        $env = preg_replace($pattern, $line, $env);
    } else {
        $env .= "\n{$line}";
    }
}
file_put_contents($envFile, $env);
echo "Ambiente atualizado.\n";
