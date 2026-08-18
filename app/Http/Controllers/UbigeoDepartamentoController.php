<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\UbigeoDepartamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UbigeoDepartamentoController extends Controller
{
    public function index()
    {
        return ApiResponse::success(
            UbigeoDepartamento::where('estado', '1')->orderBy('nombre')->get()
        );
    }

    public function listar(Request $request)
    {
        $query = UbigeoDepartamento::query()
            ->latest('idubigeo_departamento');

        if (! $request->boolean('incluir_inactivos')) {
            $query->where('estado', '1');
        }

        if ($request->filled('idubigeo_departamento')) {
            $query->where('idubigeo_departamento', $request->string('idubigeo_departamento'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));

            $query->where(function ($subQuery) use ($buscar) {
                $subQuery
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('region', 'like', "%{$buscar}%");
            });
        }

        return ApiResponse::success($query->get());
    }

    public function show(UbigeoDepartamento $ubigeoDepartamento)
    {
        return ApiResponse::success($ubigeoDepartamento);
    }

    public function store(Request $request)
    {
        $validated = $this->validarDepartamento($request);

        return DB::transaction(function () use ($validated, $request) {
            $validated['estado'] = '1';

            $departamento = UbigeoDepartamento::create($validated);

            return ApiResponse::success($departamento, 'Departamento creado correctamente.');
        });
    }

    public function update(Request $request, UbigeoDepartamento $ubigeoDepartamento)
    {
        $validated = $this->validarDepartamento($request, $ubigeoDepartamento->idubigeo_departamento);

        return DB::transaction(function () use ($validated, $ubigeoDepartamento) {
            $ubigeoDepartamento->update($validated);

            return ApiResponse::success($ubigeoDepartamento, 'Departamento actualizado correctamente.');
        });
    }

    public function destroy(Request $request, UbigeoDepartamento $ubigeoDepartamento)
    {
        if ((string) $ubigeoDepartamento->estado === '0') {
            return ApiResponse::fail('El departamento ya se encuentra inactivo.', 400);
        }

        $ubigeoDepartamento->update([
            'estado' => '0',
        ]);

        return ApiResponse::success($ubigeoDepartamento, 'Departamento inactivado correctamente.');
    }

    public function restore(Request $request, string $id)
    {
        $departamento = UbigeoDepartamento::where('idubigeo_departamento', $id)
            ->where('estado', '0')
            ->firstOrFail();

        $departamento->update([
            'estado' => '1',
        ]);

        return ApiResponse::success($departamento, 'Departamento activado correctamente.');
    }

    private function validarDepartamento(Request $request, ?string $idDepartamento = null): array
    {
        $validated = $request->validate([
            'idubigeo_departamento' => ['required', 'string', 'size:2'],
            'nombre' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'macroregion_inei' => ['nullable', 'string', 'max:100'],
            'macroregion_minsa' => ['nullable', 'string', 'max:100'],
            'iso_3166_2' => ['nullable', 'string', 'size:6'],
        ]);

        $uniqueRule = $idDepartamento
            ? "unique:ubigeo_departamento,idubigeo_departamento,{$idDepartamento},idubigeo_departamento"
            : "unique:ubigeo_departamento,idubigeo_departamento";

        $request->validate([
            'idubigeo_departamento' => [$uniqueRule],
        ]);

        return $validated;
    }
}
