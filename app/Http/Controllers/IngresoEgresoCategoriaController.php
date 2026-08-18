<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\IngresoEgresoCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IngresoEgresoCategoriaController extends Controller
{
    public function listar(Request $request)
    {
        $query = IngresoEgresoCategoria::query()
            ->latest('idingreso_egreso_categoria');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('idingreso_egreso_categoria')) {
            $query->where('idingreso_egreso_categoria', $request->integer('idingreso_egreso_categoria'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));

            $query->where(function ($subQuery) use ($buscar) {
                $subQuery
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        return ApiResponse::success($query->get());
    }

    public function show(IngresoEgresoCategoria $ingresoEgresoCategoria)
    {
        return ApiResponse::success($ingresoEgresoCategoria);
    }

    public function store(Request $request)
    {
        $validated = $this->validarIngresoEgresoCategoria($request);

        return DB::transaction(function () use ($request, $validated) {
            $validated['estado_trash'] = '1';
            $validated['user_trash'] = null;
            $validated['user_created'] = $request->user()->id;
            $validated['user_updated'] = $request->user()->id;

            $categoria = IngresoEgresoCategoria::create($validated);

            return ApiResponse::success($categoria, 'Categoria de ingreso/egreso registrada correctamente.');
        });
    }

    public function update(Request $request, IngresoEgresoCategoria $ingresoEgresoCategoria)
    {
        $validated = $this->validarIngresoEgresoCategoria($request, $ingresoEgresoCategoria->idingreso_egreso_categoria);

        return DB::transaction(function () use ($request, $ingresoEgresoCategoria, $validated) {
            $validated['user_updated'] = $request->user()->id;
            $ingresoEgresoCategoria->update($validated);

            return ApiResponse::success($ingresoEgresoCategoria, 'Categoria de ingreso/egreso actualizada correctamente.');
        });
    }

    public function destroy(Request $request, IngresoEgresoCategoria $ingresoEgresoCategoria)
    {
        if ((string) $ingresoEgresoCategoria->estado_trash === '0') {
            return ApiResponse::fail('La categoria de ingreso/egreso ya se encuentra eliminada.', 400);
        }

        $ingresoEgresoCategoria->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($ingresoEgresoCategoria, 'Categoria de ingreso/egreso eliminada correctamente.');
    }

    public function restore(Request $request, int $id)
    {
        $categoria = IngresoEgresoCategoria::where('idingreso_egreso_categoria', $id)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $categoria->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($categoria, 'Categoria de ingreso/egreso restaurada correctamente.');
    }

    private function validarIngresoEgresoCategoria(Request $request, ?int $idIngresoEgresoCategoria = null): array
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:250'],
        ]);

        $request->validate([
            'nombre' => [
                Rule::unique('ingreso_egreso_categoria', 'nombre')
                    ->ignore($idIngresoEgresoCategoria, 'idingreso_egreso_categoria'),
            ],
        ]);

        return $validated;
    }
}
