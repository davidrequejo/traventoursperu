<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function listar(Request $request)
    {
        $query = Categoria::query()
            ->latest('idcategoria');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('idcategoria')) {
            $query->where('idcategoria', $request->integer('idcategoria'));
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

    public function show(Categoria $categoria)
    {
        return ApiResponse::success($categoria);
    }

    public function store(Request $request)
    {
        $validated = $this->validarCategoria($request);

        return DB::transaction(function () use ($request, $validated) {
            $validated['estado_trash'] = '1';
            $validated['user_trash'] = null;
            $validated['user_created'] = $request->user()->id;
            $validated['user_updated'] = $request->user()->id;

            $categoria = Categoria::create($validated);

            return ApiResponse::success($categoria, 'Categoria registrada correctamente.');
        });
    }

    public function update(Request $request, Categoria $categoria)
    {
        $validated = $this->validarCategoria($request, $categoria->idcategoria);

        return DB::transaction(function () use ($request, $categoria, $validated) {
            $validated['user_updated'] = $request->user()->id;
            $categoria->update($validated);

            return ApiResponse::success($categoria, 'Categoria actualizada correctamente.');
        });
    }

    public function destroy(Request $request, Categoria $categoria)
    {
        if ((string) $categoria->estado_trash === '0') {
            return ApiResponse::fail('La categoria ya se encuentra eliminada.', 400);
        }

        $categoria->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($categoria, 'Categoria eliminada correctamente.');
    }

    public function restore(Request $request, int $categoria)
    {
        $categoria = Categoria::where('idcategoria', $categoria)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $categoria->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($categoria, 'Categoria restaurada correctamente.');
    }

    private function validarCategoria(Request $request, ?int $idCategoria = null): array
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:250'],
        ]);

        $request->validate([
            'nombre' => [
                Rule::unique('categoria', 'nombre')->ignore($idCategoria, 'idcategoria'),
            ],
        ]);

        return $validated;
    }
}
