<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $modulos, string $accion = 'ver'): Response
    {
        $user = $request->user();
        $modulosPermitidos = array_filter(array_map('trim', explode('|', $modulos)));
        $accionesPermitidas = array_filter(array_map('trim', explode('|', $accion)));

        if ($user && $this->esAccionPermitidaSobrePerfilPropio($request, $modulosPermitidos)) {
            return $next($request);
        }

        if (! $user || ! $this->tienePermiso($user, $modulosPermitidos, $accionesPermitidas)) {
            $message = 'No tienes permiso para realizar esta accion.';

            if ($request->expectsJson() || $request->ajax()) {
                return ApiResponse::forbidden($message);
            }

            abort(403, $message);
        }

        return $next($request);
    }

    private function tienePermiso($user, array $modulos, array $acciones): bool
    {
        foreach ($acciones as $accion) {
            if ($user->puede($modulos, $accion)) {
                return true;
            }
        }

        return false;
    }

    private function esAccionPermitidaSobrePerfilPropio(Request $request, array $modulos): bool
    {
        if (! in_array('usuarios_del_sistema', $modulos, true)) {
            return false;
        }

        if (! in_array($request->route()?->getName(), [
            'usuarios.show',
            'usuarios.sesiones',
            'usuarios.sesiones.cerrar',
            'usuarios.sesiones.cerrar-todas',
        ], true)) {
            return false;
        }

        $routeUser = $request->route('user');
        $routeUserId = is_object($routeUser) && method_exists($routeUser, 'getKey')
            ? $routeUser->getKey()
            : $routeUser;

        return (int) Auth::id() === (int) $routeUserId;
    }
}
