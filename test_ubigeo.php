<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $departamentos = App\Models\UbigeoDepartamento::all();
    echo 'Departamentos encontrados: ' . $departamentos->count() . PHP_EOL;

    if ($departamentos->count() > 0) {
        echo 'Primer departamento: ' . $departamentos->first()->nombre . PHP_EOL;
    }

    $provincias = App\Models\UbigeoProvincia::all();
    echo 'Provincias encontradas: ' . $provincias->count() . PHP_EOL;

    $distritos = App\Models\UbigeoDistrito::all();
    echo 'Distritos encontrados: ' . $distritos->count() . PHP_EOL;

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo 'Archivo: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
