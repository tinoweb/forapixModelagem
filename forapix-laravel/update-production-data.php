<?php
/**
 * Script para atualizar URLs de produção no banco de dados
 * Acesse: https://apostacasada.net/api/update-production-data.php
 * Depois DELETE este arquivo!
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "<h1>Atualizando dados de produção...</h1>";
echo "<pre>";

// 1. Atualizar photo_url dos jogadores
echo "\n1. Atualizando fotos dos jogadores...\n";
$playersUpdated = DB::table('players')
    ->where('photo_url', 'like', '%localhost%')
    ->orWhere('photo_url', 'like', 'http://localhost%')
    ->update([
        'photo_url' => DB::raw("REPLACE(REPLACE(photo_url, 'http://localhost:3000', ''), 'http://localhost:8000', '')")
    ]);
echo "Jogadores atualizados: {$playersUpdated}\n";

// 2. Atualizar banner_image nos metadata das partidas
echo "\n2. Atualizando banners das partidas...\n";
$matches = DB::table('matches')->get();
$matchesUpdated = 0;
foreach ($matches as $match) {
    $metadata = json_decode($match->metadata, true);
    if (isset($metadata['banner_image']) && str_contains($metadata['banner_image'], 'localhost')) {
        $metadata['banner_image'] = str_replace(
            ['http://localhost:3000', 'http://localhost:8000'],
            '',
            $metadata['banner_image']
        );
        DB::table('matches')->where('id', $match->id)->update([
            'metadata' => json_encode($metadata)
        ]);
        $matchesUpdated++;
    }
}
echo "Partidas atualizadas: {$matchesUpdated}\n";

// 3. Atualizar game images
echo "\n3. Atualizando imagens dos jogos...\n";
$gamesUpdated = DB::table('games')
    ->where('image', 'like', '%localhost%')
    ->update([
        'image' => DB::raw("REPLACE(REPLACE(image, 'http://localhost:3000', ''), 'http://localhost:8000', '')")
    ]);
echo "Jogos atualizados: {$gamesUpdated}\n";

// 4. Listar URLs restantes com localhost
echo "\n4. Verificando URLs restantes com localhost...\n";
$remainingPlayers = DB::table('players')
    ->where('photo_url', 'like', '%localhost%')
    ->count();
$remainingGames = DB::table('games')
    ->where('image', 'like', '%localhost%')
    ->count();

echo "Jogadores com localhost: {$remainingPlayers}\n";
echo "Jogos com localhost: {$remainingGames}\n";

// 5. Verificar storage symlink
echo "\n5. Verificando storage symlink...\n";
$storageLink = __DIR__ . '/public/storage';
if (is_link($storageLink)) {
    echo "Symlink existe: ✅\n";
    echo "Aponta para: " . readlink($storageLink) . "\n";
} elseif (is_dir($storageLink)) {
    echo "Diretório físico existe (não é symlink): ✅\n";
} else {
    echo "Storage não encontrado em public/storage: ❌\n";
    echo "Tentando criar diretório...\n";
    $target = __DIR__ . '/storage/app/public';
    if (is_dir($target)) {
        if (copy_dir($target, $storageLink)) {
            echo "Diretório copiado: ✅\n";
        } else {
            echo "Erro ao copiar diretório\n";
        }
    }
}

// 6. Listar arquivos em storage
echo "\n6. Arquivos em storage/app/public...\n";
$storagePath = __DIR__ . '/storage/app/public';
if (is_dir($storagePath)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($storagePath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $count = 0;
    foreach ($iterator as $file) {
        if ($count < 20) {
            echo $file->getPathname() . " (" . round($file->getSize()/1024, 2) . " KB)\n";
        }
        $count++;
    }
    echo "Total de arquivos: {$count}\n";
} else {
    echo "Diretório storage/app/public não existe\n";
}

echo "\n=== FIM ===\n";
echo "</pre>";
echo "<p style='color:red'><b>DELETE ESTE ARQUIVO APÓS USAR!</b></p>";

function copy_dir($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            if (is_dir($src . '/' . $file)) {
                copy_dir($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
    return true;
}
?>
