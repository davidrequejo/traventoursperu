<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\UsuarioPermiso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UsuarioPermisoController extends Controller
{
    public function index(Request $request)
    {
        $query = UsuarioPermiso::with(['user', 'permiso'])
            ->where('estado_trash', '1');

        if ($request->filled('idusers')) {
            $query->where('idusers', $request->integer('idusers'));
        }

        return ApiResponse::success($query->latest('idusuario_permiso')->get());
    }

    public function store(Request $request)
    {
        $validator = validator($request->all(), $this->rules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $usuarioPermiso = UsuarioPermiso::create([
            ...$validator->validated(),
            'user_created' => Auth::id(),
        ]);

        return ApiResponse::success(
            $usuarioPermiso->load(['user', 'permiso']),
            'Permiso asignado correctamente.'
        );
    }

    public function show(UsuarioPermiso $usuarioPermiso)
    {
        return ApiResponse::success($usuarioPermiso->load(['user', 'permiso']));
    }

    public function update(Request $request, UsuarioPermiso $usuarioPermiso)
    {
        $validator = validator($request->all(), $this->rules(true));

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $usuarioPermiso->update([
            ...$validator->validated(),
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success(
            $usuarioPermiso->load(['user', 'permiso']),
            'Permiso de usuario actualizado correctamente.'
        );
    }

    public function destroy(Request $request, UsuarioPermiso $usuarioPermiso)
    {
        $usuarioPermiso->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
        ]);

        return ApiResponse::success($usuarioPermiso, 'Permiso de usuario eliminado correctamente.');
    }

    private function rules(bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return [
            'idusers' => [$required, 'integer', Rule::exists('users', 'id')],
            'idpermiso' => [$required, 'integer', Rule::exists('permiso', 'idpermiso')],
            'crear' => ['nullable', 'string', 'max:1'],
            'editar' => ['nullable', 'string', 'max:1'],
            'eliminar' => ['nullable', 'string', 'max:1'],
        ];
    }
}
