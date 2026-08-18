<?php

declare(strict_types=1);

/**
 * Archivo temporal para ejecutar comandos de cache de Laravel desde cPanel.
 *
 * IMPORTANTE:
 * - Ejecutar solo una vez despues de subir cambios.
 * - Borrar este archivo despues de usarlo.
 * - URL:
 *   /ejecutar_artisan_produccion.php?token=ERP_OPTIMIZA_2026_CACHE&run=1
 *
 * Si cache:clear falla porque el cache usa PostgreSQL/DB, usar:
 *   /ejecutar_artisan_produccion.php?token=ERP_OPTIMIZA_2026_CACHE&run=1&modo=seguro
 */

$token = 'ERP_OPTIMIZA_2026_CACHE';

if (!hash_equals($token, (string) ($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit('Acceso denegado.');
}

header('Content-Type: text/plain; charset=utf-8');

if ((string) ($_GET['run'] ?? '') !== '1') {
    echo "Ejecutor listo.\n\n";
    echo "Para ejecutar los comandos abre:\n";
    echo "/ejecutar_artisan_produccion.php?token={$token}&run=1\n";
    exit;
}

set_time_limit(300);

$basePath = dirname(__DIR__);

require $basePath . '/vendor/autoload.php';

$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$modo = (string) ($_GET['modo'] ?? 'seguro');

$safeCommands = [
    'view:clear',
    'route:clear',
    'config:clear',
    'config:cache',
    'route:cache',
    'view:cache',
];

$fullCommands = [
    'optimize:clear',
    'view:clear',
    'route:clear',
    'config:clear',
    'cache:clear',
    'config:cache',
    'route:cache',
    'view:cache',
];

$commands = $modo === 'completo' ? $fullCommands : $safeCommands;

echo "Ejecutando comandos Artisan...\n";
echo "Base path: {$basePath}\n\n";
echo "Modo: {$modo}\n\n";

limpiarArchivosLaravel($basePath);

foreach ($commands as $command) {
    echo ">>> php artisan {$command}\n";

    try {
        $status = $kernel->call($command);
        $output = trim($kernel->output());

        echo $output !== '' ? $output . "\n" : "(sin salida)\n";
        echo "Estado: {$status}\n\n";
    } catch (Throwable $e) {
        echo "ERROR ejecutando {$command}:\n";
        echo $e->getMessage() . "\n\n";
        break;
    }
}

echo "Proceso terminado.\n";
echo "Por seguridad, borra public/ejecutar_artisan_produccion.php despues de usarlo.\n";

function limpiarArchivosLaravel(string $basePath): void
{
    echo ">>> Limpieza manual de archivos cacheados\n";

    eliminarContenidoDirectorio($basePath . '/storage/framework/views', ['.gitignore']);
    eliminarContenidoDirectorio($basePath . '/storage/framework/cache/data', ['.gitignore']);
    eliminarArchivosPorPatron($basePath . '/bootstrap/cache/*.php');

    echo "Limpieza manual terminada.\n\n";
}

function eliminarArchivosPorPatron(string $pattern): void
{
    foreach (glob($pattern) ?: [] as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function eliminarContenidoDirectorio(string $dir, array $except = []): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || in_array($item, $except, true)) {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            eliminarContenidoDirectorio($path, $except);
            @rmdir($path);
            continue;
        }

        @unlink($path);
    }
}
