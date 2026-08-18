<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\UbigeoDepartamentoController;

try {
    $controller = new UbigeoDepartamentoController();
    $request = new Request();

    // Probar el método index
    $response = $controller->index();
    echo 'Respuesta del método index:' . PHP_EOL;
    echo json_encode($response->getData(), JSON_PRETTY_PRINT) . PHP_EOL;

    // Probar el método listar
    $response = $controller->listar($request);
    echo PHP_EOL . 'Respuesta del método listar:' . PHP_EOL;
    echo json_encode($response->getData(), JSON_PRETTY_PRINT) . PHP_EOL;

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo 'Archivo: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo 'Trace:' . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}
