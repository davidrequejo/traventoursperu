<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\UbigeoDistrito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UbigeoDistritoController extends Controller
{
    public function index()
    {
        return ApiResponse::success(
            UbigeoDistrito::with(['departamento', 'provincia'])
                ->where('estado', '1')
                ->orderBy('nombre')
                ->get()
        );
    }

    public function listar(Request $request)
    {
        $query = UbigeoDistrito::with(['departamento', 'provincia'])
            ->latest('idubigeo_distrito');

        if (! $request->boolean('incluir_inactivos')) {
            $query->where('estado', '1');
        }

        if ($request->filled('idubigeo_distrito')) {
            $query->where('idubigeo_distrito', $request->string('idubigeo_distrito'));
        }

        if ($request->filled('idubigeo_departamento')) {
            $query->where('idubigeo_departamento', $request->string('idubigeo_departamento'));
        }

        if ($request->filled('idubigeo_provincia')) {
            $query->where('idubigeo_provincia', $request->string('idubigeo_provincia'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));

            $query->where(function ($subQuery) use ($buscar) {
                $subQuery
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo_postal', 'like', "%{$buscar}%")
                    ->orWhereHas('departamento', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"))
                    ->orWhereHas('provincia', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"));
            });
        }

        return ApiResponse::success($query->get());
    }

    public function show(UbigeoDistrito $ubigeoDistrito)
    {
        return ApiResponse::success($ubigeoDistrito->load(['departamento', 'provincia']));
    }

    public function store(Request $request)
    {
        $validated = $this->validarDistrito($request);

        return DB::transaction(function () use ($validated) {
            $validated['estado'] = '1';

            $distrito = UbigeoDistrito::create($validated);

            return ApiResponse::success($distrito->load(['departamento', 'provincia']), 'Distrito creado correctamente.');
        });
    }

    public function update(Request $request, UbigeoDistrito $ubigeoDistrito)
    {
        $validated = $this->validarDistrito($request, $ubigeoDistrito->idubigeo_distrito);

        return DB::transaction(function () use ($validated, $ubigeoDistrito) {
            $ubigeoDistrito->update($validated);

            return ApiResponse::success($ubigeoDistrito->load(['departamento', 'provincia']), 'Distrito actualizado correctamente.');
        });
    }

    public function destroy(Request $request, UbigeoDistrito $ubigeoDistrito)
    {
        if ((string) $ubigeoDistrito->estado === '0') {
            return ApiResponse::fail('El distrito ya se encuentra inactivo.', 400);
        }

        $ubigeoDistrito->update([
            'estado' => '0',
        ]);

        return ApiResponse::success($ubigeoDistrito, 'Distrito inactivado correctamente.');
    }

    public function restore(Request $request, string $id)
    {
        $distrito = UbigeoDistrito::where('idubigeo_distrito', $id)
            ->where('estado', '0')
            ->firstOrFail();

        $distrito->update([
            'estado' => '1',
        ]);

        return ApiResponse::success($distrito->load(['departamento', 'provincia']), 'Distrito activado correctamente.');
    }

    private function validarDistrito(Request $request, ?string $idDistrito = null): array
    {
        $validated = $request->validate([
            'idubigeo_distrito' => ['required', 'string', 'size:6'],
            'idubigeo_departamento' => ['required', 'string', 'size:2', 'exists:ubigeo_departamento,idubigeo_departamento'],
            'idubigeo_provincia' => ['required', 'string', 'size:4', 'exists:ubigeo_provincia,idubigeo_provincia'],
            'nombre' => ['required', 'string', 'max:100'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],
            'ubigeo_reniec' => ['nullable', 'string', 'max:10'],
            'ubigeo_inei' => ['nullable', 'string', 'max:10'],
            'superficie' => ['nullable', 'numeric', 'min:0'],
            'altitud' => ['nullable', 'numeric', 'min:0'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'frontera' => ['nullable', 'boolean'],
        ]);

        $uniqueRule = $idDistrito
            ? "unique:ubigeo_distrito,idubigeo_distrito,{$idDistrito},idubigeo_distrito"
            : "unique:ubigeo_distrito,idubigeo_distrito";

        $request->validate([
            'idubigeo_distrito' => [$uniqueRule],
        ]);

        return $validated;
    }
}
