<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermisoController extends Controller
{
    public function index()
    {
        return ApiResponse::success(
            Permiso::where('estado_trash', '1')->orderBy('codigo')->get()
        );
    }

    public function store(Request $request)
    {
        $validator = validator($request->all(), $this->rules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $permiso = Permiso::create([
            ...$validator->validated(),
            'user_created' => Auth::id(),
        ]);

        return ApiResponse::success($permiso, 'Permiso creado correctamente.');
    }

    public function show(Permiso $permiso)
    {
        return ApiResponse::success($permiso);
    }

    public function update(Request $request, Permiso $permiso)
    {
        $validator = validator($request->all(), $this->rules(true));

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $permiso->update([
            ...$validator->validated(),
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($permiso, 'Permiso actualizado correctamente.');
    }

    public function destroy(Request $request, Permiso $permiso)
    {
        $permiso->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
        ]);

        return ApiResponse::success($permiso, 'Permiso eliminado correctamente.');
    }

    private function rules(bool $updating = false): array
    {
        $sometimes = $updating ? 'sometimes' : 'nullable';

        return [
            'descripcion' => [$sometimes, 'string', 'max:45'],
            'codigo' => [$sometimes, 'string', 'max:45'],
            'nivel' => [$sometimes, 'string', 'max:45'],
            'icono' => [$sometimes, 'string', 'max:225'],
            'tipo' => [$sometimes, 'string', 'max:100'],
            'nombre_clave' => [$sometimes, 'string', 'max:45'],
            'estado_super_admin' => [$sometimes, 'string', 'max:45'],
        ];
    }
}
