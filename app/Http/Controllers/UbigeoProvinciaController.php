<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\UbigeoProvincia;
use App\Models\UbigeoDepartamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UbigeoProvinciaController extends Controller
{
    public function index()
    {
        return ApiResponse::success(
            UbigeoProvincia::with('departamento')
                ->where('estado', '1')
                ->orderBy('nombre')
                ->get()
        );
    }

    public function listar(Request $request)
    {
        $query = UbigeoProvincia::with('departamento')
            ->latest('idubigeo_provincia');

        if (! $request->boolean('incluir_inactivos')) {
            $query->where('estado', '1');
        }

        if ($request->filled('idubigeo_provincia')) {
            $query->where('idubigeo_provincia', $request->string('idubigeo_provincia'));
        }

        if ($request->filled('idubigeo_departamento')) {
            $query->where('idubigeo_departamento', $request->string('idubigeo_departamento'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));

            $query->where(function ($subQuery) use ($buscar) {
                $subQuery
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhereHas('departamento', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"));
            });
        }

        return ApiResponse::success($query->get());
    }

    public function show(UbigeoProvincia $ubigeoProvincia)
    {
        return ApiResponse::success($ubigeoProvincia->load('departamento'));
    }

    public function store(Request $request)
    {
        $validated = $this->validarProvincia($request);

        return DB::transaction(function () use ($validated) {
            $validated['estado'] = '1';

            $provincia = UbigeoProvincia::create($validated);

            return ApiResponse::success($provincia->load('departamento'), 'Provincia creada correctamente.');
        });
    }

    public function update(Request $request, UbigeoProvincia $ubigeoProvincia)
    {
        $validated = $this->validarProvincia($request, $ubigeoProvincia->idubigeo_provincia);

        return DB::transaction(function () use ($validated, $ubigeoProvincia) {
            $ubigeoProvincia->update($validated);

            return ApiResponse::success($ubigeoProvincia->load('departamento'), 'Provincia actualizada correctamente.');
        });
    }

    public function destroy(Request $request, UbigeoProvincia $ubigeoProvincia)
    {
        if ((string) $ubigeoProvincia->estado === '0') {
            return ApiResponse::fail('La provincia ya se encuentra inactiva.', 400);
        }

        $ubigeoProvincia->update([
            'estado' => '0',
        ]);

        return ApiResponse::success($ubigeoProvincia, 'Provincia inactivada correctamente.');
    }

    public function restore(Request $request, string $id)
    {
        $provincia = UbigeoProvincia::where('idubigeo_provincia', $id)
            ->where('estado', '0')
            ->firstOrFail();

        $provincia->update([
            'estado' => '1',
        ]);

        return ApiResponse::success($provincia->load('departamento'), 'Provincia activada correctamente.');
    }

    private function validarProvincia(Request $request, ?string $idProvincia = null): array
    {
        $validated = $request->validate([
            'idubigeo_provincia' => ['required', 'string', 'size:4'],
            'idubigeo_departamento' => ['required', 'string', 'size:2', 'exists:ubigeo_departamento,idubigeo_departamento'],
            'nombre' => ['required', 'string', 'max:100'],
        ]);

        $uniqueRule = $idProvincia
            ? "unique:ubigeo_provincia,idubigeo_provincia,{$idProvincia},idubigeo_provincia"
            : "unique:ubigeo_provincia,idubigeo_provincia";

        $request->validate([
            'idubigeo_provincia' => [$uniqueRule],
        ]);

        return $validated;
    }
}
