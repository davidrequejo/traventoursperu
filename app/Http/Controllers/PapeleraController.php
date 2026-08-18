<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Empresa;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PapeleraController extends Controller
{
    private const TIPO_TRABAJADOR = 2;
    private const TIPO_CLIENTE = 3;

    public function index()
    {
        return view('papelera');
    }

    public function listar(Request $request)
    {
        $modulo = (string) $request->input('modulo', 'todos');
        $items = collect();

        if ($modulo === 'todos' || $modulo === 'personas') {
            $items = $items->merge($this->personasEliminadas());
        } elseif ($modulo === 'trabajadores') {
            $items = $items->merge($this->personasEliminadas('trabajadores', 'Trabajador', self::TIPO_TRABAJADOR));
        } elseif ($modulo === 'clientes') {
            $items = $items->merge($this->personasEliminadas('clientes', 'Cliente', self::TIPO_CLIENTE));
        }

        if ($modulo === 'todos' || $modulo === 'usuarios') {
            $items = $items->merge($this->usuariosEliminados());
        }

        if ($modulo === 'todos' || $modulo === 'empresas') {
            $items = $items->merge($this->empresasEliminadas());
        }

        return ApiResponse::success(
            $items->sortByDesc('updated_at')->values(),
            'Registros en papelera'
        );
    }

    public function restaurar(Request $request, string $modulo, int $id)
    {
        return match ($modulo) {
            'trabajadores', 'clientes', 'personas' => $this->restaurarPersona($request, $modulo, $id),
            'usuarios' => $this->restaurarUsuario($request, $id),
            'empresas' => $this->restaurarEmpresa($request, $id),
            default => ApiResponse::fail('Modulo no soportado por la papelera.', 404),
        };
    }

    private function personasEliminadas(?string $modulo = null, ?string $moduloLabel = null, ?int $idTipoPersona = null): Collection
    {
        return Persona::query()
            ->with(['cargo', 'docIdentidad', 'tiposPersona.personaTipo'])
            ->where('estado_trash', '0')
            ->when($idTipoPersona, fn ($query) => $query->whereHas('tiposPersona', fn ($subQuery) => $subQuery->where('persona_tipo_persona.idpersona_tipo', $idTipoPersona)))
            ->latest('updated_at')
            ->get()
            ->map(function (Persona $persona) use ($modulo, $moduloLabel) {
                $tipoPrincipal = $persona->tiposPersona->first()?->personaTipo;
                $moduloDetectado = $modulo ?: $this->detectarModuloPersona($tipoPrincipal?->idpersona_tipo);
                $moduloLabelDetectado = $moduloLabel ?: ($tipoPrincipal?->nombre ? ucfirst(strtolower($tipoPrincipal->nombre)) : 'Persona');
                $nombre = collect([
                    trim(collect([
                        $persona->nombre_persona_natural,
                        $persona->apellido_paterno_persona_natural,
                        $persona->apellido_materno_persona_natural,
                    ])->filter()->implode(' ')),
                    $persona->descripcion,
                    $persona->nombre_comercial,
                ])->first(fn ($value) => filled($value)) ?: 'Sin nombre';

                return [
                    'id' => $persona->idpersona,
                    'modulo' => $moduloDetectado,
                    'modulo_label' => $moduloLabelDetectado,
                    'nombre' => $nombre,
                    'detalle' => trim(collect([
                        $persona->docIdentidad?->abreviatura ?: $persona->tipo_documento,
                        $persona->numero_documento,
                    ])->filter()->implode(' ')),
                    'extra' => $persona->cargo?->nombre ?: $persona->correo ?: $persona->celular,
                    'updated_at' => optional($persona->updated_at)->toDateTimeString(),
                ];
            });
    }

    private function usuariosEliminados(): Collection
    {
        return User::query()
            ->with(['persona.docIdentidad', 'persona.cargo'])
            ->where('estado_trash', '0')
            ->latest('updated_at')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'modulo' => 'usuarios',
                    'modulo_label' => 'Usuario',
                    'nombre' => $user->display_name,
                    'detalle' => $user->email,
                    'extra' => $user->display_role,
                    'updated_at' => optional($user->updated_at)->toDateTimeString(),
                ];
            });
    }

    private function empresasEliminadas(): Collection
    {
        return Empresa::query()
            ->with('persona')
            ->where('estado_trash', '0')
            ->latest('updated_at')
            ->get()
            ->map(fn (Empresa $empresa) => [
                'id' => $empresa->idempresa,
                'modulo' => 'empresas',
                'modulo_label' => 'Empresa',
                'nombre' => $empresa->nombre_razon_social ?: $empresa->persona?->descripcion ?: 'Sin razon social',
                'detalle' => trim(collect([$empresa->tipo_documento, $empresa->numero_documento])->filter()->implode(' ')),
                'extra' => $empresa->correo ?: $empresa->telefono1 ?: $empresa->nombre_comercial,
                'updated_at' => optional($empresa->updated_at)->toDateTimeString(),
            ]);
    }

    private function restaurarPersona(Request $request, string $modulo, int $id)
    {
        $tipoPersona = match ($modulo) {
            'trabajadores' => self::TIPO_TRABAJADOR,
            'clientes' => self::TIPO_CLIENTE,
            default => null,
        };
        $label = match ($modulo) {
            'trabajadores' => 'Trabajador',
            'clientes' => 'Cliente',
            default => 'Persona',
        };

        $persona = Persona::query()
            ->where('idpersona', $id)
            ->where('estado_trash', '0')
            ->when($tipoPersona, fn ($query) => $query->whereHas('tiposPersona', fn ($subQuery) => $subQuery->where('persona_tipo_persona.idpersona_tipo', $tipoPersona)))
            ->firstOrFail();

        $persona->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($persona, "{$label} restaurado correctamente.");
    }

    private function detectarModuloPersona(?int $idTipoPersona): string
    {
        return match ($idTipoPersona) {
            self::TIPO_TRABAJADOR => 'trabajadores',
            self::TIPO_CLIENTE => 'clientes',
            default => 'personas',
        };
    }

    private function restaurarUsuario(Request $request, int $id)
    {
        $user = User::query()
            ->where('id', $id)
            ->where('estado_trash', '0')
            ->firstOrFail();

        DB::transaction(function () use ($request, $user) {
            $user->update([
                'estado_trash' => '1',
                'user_trash' => null,
                'user_updated' => Auth::id(),
            ]);
        });

        return ApiResponse::success($user, 'Usuario restaurado correctamente.');
    }

    private function restaurarEmpresa(Request $request, int $id)
    {
        $empresa = Empresa::query()
            ->where('idempresa', $id)
            ->where('estado_trash', '0')
            ->firstOrFail();

        if (Empresa::query()->where('estado_trash', '1')->where('idempresa', '<>', $empresa->idempresa)->exists()) {
            return ApiResponse::fail('No se puede restaurar porque ya existe una empresa activa.', 422);
        }

        $empresa->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($empresa, 'Empresa restaurada correctamente.');
    }
}
