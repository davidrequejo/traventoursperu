<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = null, string $message = 'OK')
    {
        return response()->json([
            'status'     => true,
            'code_error' => 0,
            'message'    => $message,
            'data'       => $data,
        ], 200);
    }   

    public static function validation(array $errors, string $message = 'Errores de validacion')
    {
        return response()->json([
            'status'     => false,
            'code_error' => 422,
            'message'    => $message,
            'data'       => $errors,
        ], 422);
    }

    

    // ✅ Método base para errores HTTP amigables (sin stacktrace)
    public static function fail(string $message, int $httpCode = 400, $data = null, ?int $codeError = null)
    {
        return response()->json([
            'status'     => false,
            'code_error' => $codeError ?? $httpCode,
            'message'    => $message,
            'data'       => $data,
        ], $httpCode);
    }

    public static function forbidden(string $message = 'No tienes permisos para esta accion.', $data = null)
    {
        return self::fail($message, 403, $data, 403);
    }

    public static function unauthorized(string $message = 'No autenticado.', $data = null)
    {
        return self::fail($message, 401, $data, 401);
    }

    // ✅ Tu error() lo dejamos pero mejor: stacktrace solo si APP_DEBUG=true
    public static function error(\Throwable|string $e, int $httpCode = 500, $data = null)
    {
        if ($e instanceof \Throwable) {
            $trace = config('app.debug')
                ? '<br><b>Rutas de errores:</b><br>' . nl2br($e->getTraceAsString())
                : null;

            return response()->json([
                'status'     => false,
                'code_error' => $e->getCode() ?: $httpCode,
                'message'    => $e->getMessage(),
                'data'       => $trace,
            ], $httpCode);
        }

        return response()->json([
            'status'     => false,
            'code_error' => $httpCode,
            'message'    => $e,
            'data'       => $data,
        ], $httpCode);
    }

    
}
