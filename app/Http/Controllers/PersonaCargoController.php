<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\PersonaCargo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PersonaCargoController extends Controller
{
    public function index()
    {
        return ApiResponse::success(
            PersonaCargo::where('estado_trash', '1')->orderBy('nombre')->get()
        );
    }

    public function listar(Request $request)
    {
        $query = PersonaCargo::query()
            ->latest('idpersona_cargo');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('idpersona_cargo')) {
            $query->where('idpersona_cargo', $request->integer('idpersona_cargo'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));

            $query->where('nombre', 'like', "%{$buscar}%");
        }

        return ApiResponse::success($query->get());
    }

    public function show(PersonaCargo $personaCargo)
    {
        return ApiResponse::success($personaCargo);
    }

    public function store(Request $request)
    {
        $validated = $this->validarCargo($request);

        return DB::transaction(function () use ($validated, $request) {
            $validated['estado_trash'] = '1';
            $validated['user_trash'] = null;
            $validated['user_created'] = Auth::id();
            $validated['user_updated'] = Auth::id();

            $cargo = PersonaCargo::create($validated);

            return ApiResponse::success($cargo, 'Cargo creado correctamente.');
        });
    }

    public function update(Request $request, PersonaCargo $personaCargo)
    {
        $validated = $this->validarCargo($request, $personaCargo->idpersona_cargo);

        return DB::transaction(function () use ($validated, $personaCargo) {
            $validated['user_updated'] = Auth::id();
            $personaCargo->update($validated);

            return ApiResponse::success($personaCargo, 'Cargo actualizado correctamente.');
        });
    }

    public function destroy(Request $request, PersonaCargo $personaCargo)
    {
        if ((string) $personaCargo->estado_trash === '0') {
            return ApiResponse::fail('El cargo ya se encuentra eliminado.', 400);
        }

        $personaCargo->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($personaCargo, 'Cargo eliminado correctamente.');
    }

    public function restore(Request $request, int $id)
    {
        $personaCargo = PersonaCargo::where('idpersona_cargo', $id)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $personaCargo->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($personaCargo, 'Cargo restaurado correctamente.');
    }

    private function validarCargo(Request $request, ?int $idPersonaCargo = null): array
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:45'],
        ]);

        $uniqueRule = $idPersonaCargo
            ? "unique:persona_cargo,nombre,{$idPersonaCargo},idpersona_cargo"
            : "unique:persona_cargo,nombre";

        $request->validate([
            'nombre' => [$uniqueRule],
        ]);

        return $validated;
    }
}
