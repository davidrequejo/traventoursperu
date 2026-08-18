<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\PersonaTipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonaTipoController extends Controller
{
    public function index()
    {
        // return view('persona_tipo');
    }

    public function listar(Request $request)
    {
        $query = PersonaTipo::query()
            ->latest('idpersona_tipo'); // o latest('created_at') si prefieres

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('idpersona_tipo')) {
            $query->where('idpersona_tipo', $request->integer('idpersona_tipo'));
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

    public function show(PersonaTipo $personaTipo)
    {
        return ApiResponse::success($personaTipo);
    }

    public function store(Request $request)
    {
        $validated = $this->validarPersonaTipo($request);

        return DB::transaction(function () use ($request, $validated) {
            $validated['estado_trash'] = '1';
            $validated['user_trash'] = null;
            $validated['user_created'] = $request->user()->id;
            $validated['user_updated'] = $request->user()->id;

            $personaTipo = PersonaTipo::create($validated);

            return ApiResponse::success($personaTipo, 'Tipo de persona registrado correctamente.');
        });
    }

    public function update(Request $request, PersonaTipo $personaTipo)
    {
        $validated = $this->validarPersonaTipo($request, $personaTipo->idpersona_tipo);

        return DB::transaction(function () use ($request, $personaTipo, $validated) {
            $validated['user_updated'] = $request->user()->id;
            $personaTipo->update($validated);

            return ApiResponse::success($personaTipo, 'Tipo de persona actualizado correctamente.');
        });
    }

    public function destroy(Request $request, PersonaTipo $personaTipo)
    {
        if ((string) $personaTipo->estado_trash === '0') {
            return ApiResponse::fail('El tipo de persona ya se encuentra eliminado.', 400);
        }

        $personaTipo->update([
            'estado_trash' => '0',
            'user_trash' => $request->user()->id,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($personaTipo, 'Tipo de persona eliminado correctamente.');
    }

    public function restore(Request $request, int $id)
    {
        $personaTipo = PersonaTipo::where('idpersona_tipo', $id)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $personaTipo->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => $request->user()->id,
        ]);

        return ApiResponse::success($personaTipo, 'Tipo de persona restaurado correctamente.');
    }

    private function validarPersonaTipo(Request $request, ?int $idPersonaTipo = null): array
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
        ]);

        // Regla de unicidad manual porque el validate no acepta closures fácilmente aquí
        $uniqueRule = $idPersonaTipo 
            ? "unique:persona_tipo,nombre,{$idPersonaTipo},idpersona_tipo"
            : "unique:persona_tipo,nombre";

        $request->validate([
            'nombre' => [$uniqueRule],
        ]);

        return $validated;
    }
}