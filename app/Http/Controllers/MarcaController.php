<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MarcaController extends Controller
{
    public function listar(Request $request)
    {
        $query = Marca::query()
            ->latest('idmarca');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('idmarca')) {
            $query->where('idmarca', $request->integer('idmarca'));
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

    public function show(Marca $marca)
    {
        return ApiResponse::success($marca);
    }

    public function store(Request $request)
    {
        $validated = $this->validarMarca($request);

        return DB::transaction(function () use ($request, $validated) {
            $validated['estado_trash'] = '1';
            $validated['user_trash'] = null;
            $validated['user_created'] = $request->user()->id;
            $validated['user_updated'] = $request->user()->id;

            $marca = Marca::create($validated);

            return ApiResponse::success($marca, 'Marca registrada correctamente.');
        });
    }

    public function update(Request $request, Marca $marca)
    {
        $validated = $this->validarMarca($request, $marca->idmarca);

        return DB::transaction(function () use ($request, $marca, $validated) {
            $validated['user_updated'] = $request->user()->id;
            $marca->update($validated);

            return ApiResponse::success($marca, 'Marca actualizada correctamente.');
        });
    }

    public function destroy(Request $request, Marca $marca)
    {
        if ((string) $marca->estado_trash === '0') {
            return ApiResponse::fail('La marca ya se encuentra eliminada.', 400);
        }

        $marca->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($marca, 'Marca eliminada correctamente.');
    }

    public function restore(Request $request, int $marca)
    {
        $marca = Marca::where('idmarca', $marca)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $marca->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($marca, 'Marca restaurada correctamente.');
    }

    private function validarMarca(Request $request, ?int $idMarca = null): array
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:250'],
        ]);

        $request->validate([
            'nombre' => [
                Rule::unique('marca', 'nombre')->ignore($idMarca, 'idmarca'),
            ],
        ]);

        return $validated;
    }
}
