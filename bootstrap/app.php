<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\VerificarPermiso;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

if (! function_exists('databaseConnectionErrorDetails')) {
    function databaseConnectionErrorDetails(\Throwable $exception): ?array
    {
        $previous = $exception->getPrevious();
        $code = $previous instanceof \PDOException
            ? (string) $previous->getCode()
            : (string) $exception->getCode();
        $message = $exception->getMessage();

        if ($code === '1045' || str_contains($message, 'SQLSTATE[HY000] [1045]')) {
            return [
                'code' => '1045',
                'title' => 'Credenciales de base de datos incorrectas.',
                'message' => 'Laravel no pudo iniciar sesion en MySQL con el usuario o la clave configurada.',
                'suggestion' => 'Revisa DB_USERNAME y DB_PASSWORD en el archivo .env.',
            ];
        }

        if ($code === '1049' || str_contains($message, 'SQLSTATE[HY000] [1049]')) {
            return [
                'code' => '1049',
                'title' => 'La base de datos no existe.',
                'message' => 'MySQL respondio, pero no encontro la base de datos configurada para la aplicacion.',
                'suggestion' => 'Revisa DB_DATABASE en .env o crea esa base de datos en MySQL.',
            ];
        }

        if ($code === '2002' || str_contains($message, 'SQLSTATE[HY000] [2002]')) {
            return [
                'code' => '2002',
                'title' => 'No hay conexion con MySQL.',
                'message' => 'La aplicacion no pudo conectarse al servidor de base de datos.',
                'suggestion' => 'Verifica que MySQL este encendido en Laragon y que DB_HOST y DB_PORT sean correctos.',
            ];
        }

        if ($code === '2006' || str_contains($message, 'server has gone away')) {
            return [
                'code' => '2006',
                'title' => 'La conexion con MySQL se cerro.',
                'message' => 'MySQL corto la conexion mientras Laravel intentaba ejecutar la consulta.',
                'suggestion' => 'Reinicia MySQL y revisa si la consulta o importacion esta tardando demasiado.',
            ];
        }

        if ($code === '2013' || str_contains($message, 'Lost connection')) {
            return [
                'code' => '2013',
                'title' => 'Se perdio la conexion con MySQL.',
                'message' => 'La conexion se interrumpio durante la comunicacion con la base de datos.',
                'suggestion' => 'Revisa estabilidad del servicio MySQL, tiempo de espera y carga del servidor.',
            ];
        }

        return null;
    }
}

if (! function_exists('isDatabaseConnectionError')) {
    function isDatabaseConnectionError(\Throwable $exception): bool
    {
        return databaseConnectionErrorDetails($exception) !== null;
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectUsersTo('/inicio');

        $middleware->alias([
            'permiso' => VerificarPermiso::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (QueryException $e, Request $request) {
            $databaseError = databaseConnectionErrorDetails($e);

            if ($databaseError === null) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $databaseError['title'],
                    'detail' => $databaseError['message'],
                    'suggestion' => $databaseError['suggestion'],
                    'code' => $databaseError['code'],
                ], 503);
            }

            return response()->view('errors.database-unavailable', [
                'databaseError' => $databaseError,
            ], 503);
        });
    })->create();
