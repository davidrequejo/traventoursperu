<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Persona;
use App\Models\PersonaTipoPersona;
use App\Models\UbigeoDistrito;
use App\Services\PersonaCodigoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    private const PERSONA_TIPO_PROVEEDOR = 4;

    public function select(Request $request)
    {
        $search = trim((string) $request->input('term', ''));
        $page = max((int) $request->input('page', 1), 1);
        $perPage = 10;

        $query = Persona::query()
            ->where('estado_trash', '1')
            ->whereHas('tiposPersona', fn ($subQuery) => $subQuery->where('idpersona_tipo', self::PERSONA_TIPO_PROVEEDOR))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('nombre_comercial', 'like', "%{$search}%")
                        ->orWhere('nombre_persona_natural', 'like', "%{$search}%")
                        ->orWhere('apellido_paterno_persona_natural', 'like', "%{$search}%")
                        ->orWhere('apellido_materno_persona_natural', 'like', "%{$search}%")
                        ->orWhere('numero_documento', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('idpersona');

        $proveedores = (clone $query)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get();

        return response()->json([
            'results' => $proveedores
                ->take($perPage)
                ->map(fn (Persona $proveedor) => [
                    'id' => $proveedor->idpersona,
                    'text' => trim(($proveedor->numero_documento ? "{$proveedor->numero_documento} - " : '') . $this->resolverNombrePersona($proveedor)),
                ]),
            'pagination' => ['more' => $proveedores->count() > $perPage],
        ]);
    }

    public function storeRapido(Request $request)
    {
        $validated = $request->validate([
            'tipo_persona_sunat' => ['required', 'string', 'in:NATURAL,JURIDICA'],
            'tipo_documento' => ['required', 'integer', Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')->where('estado_trash', '1')],
            'numero_documento' => ['required', 'string', 'max:20', Rule::unique('persona', 'numero_documento')],
            'descripcion' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'nombre_persona_natural' => ['nullable', 'string', 'max:100'],
            'apellido_paterno_persona_natural' => ['nullable', 'string', 'max:100'],
            'apellido_materno_persona_natural' => ['nullable', 'string', 'max:100'],
            'sexo' => ['nullable', 'string', 'in:M,F'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'estado_civil' => ['nullable', 'string', 'max:20'],
            'nacionalidad' => ['nullable', 'string', 'max:50'],
            'correo' => ['nullable', 'email', 'max:255'],
            'celular' => ['nullable', 'string', 'max:15'],
            'direccion' => ['nullable', 'string'],
            'direccion_referencia' => ['nullable', 'string'],
            'iddistrito' => ['nullable', 'integer', Rule::exists('ubigeo_distrito', 'idubigeo_distrito')],
            'cod_ubigeo' => ['nullable', 'string', 'max:10'],
        ], [
            'tipo_persona_sunat.required' => 'Seleccione el tipo de persona.',
            'tipo_documento.required' => 'Seleccione el tipo de documento.',
            'numero_documento.required' => 'Ingrese el numero de documento.',
            'descripcion.required' => 'Ingrese el nombre o razon social del proveedor.',
        ]);

        return DB::transaction(function () use ($validated) {
            $proveedor = Persona::create([
                'codigo' => PersonaCodigoService::siguienteCodigo(),
                'tipo_persona_sunat' => $validated['tipo_persona_sunat'],
                'tipo_documento' => $validated['tipo_documento'],
                'numero_documento' => $validated['numero_documento'],
                'descripcion' => $validated['descripcion'],
                'nombre_comercial' => $validated['nombre_comercial'] ?? null,
                'nombre_persona_natural' => $validated['nombre_persona_natural'] ?? null,
                'apellido_paterno_persona_natural' => $validated['apellido_paterno_persona_natural'] ?? null,
                'apellido_materno_persona_natural' => $validated['apellido_materno_persona_natural'] ?? null,
                'sexo' => $validated['sexo'] ?? null,
                'fecha_nacimiento' => $validated['fecha_nacimiento'] ?? null,
                'estado_civil' => $validated['estado_civil'] ?? null,
                'nacionalidad' => $validated['nacionalidad'] ?? null,
                'correo' => $validated['correo'] ?? null,
                'celular' => $validated['celular'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'direccion_referencia' => $validated['direccion_referencia'] ?? null,
                'iddistrito' => $validated['iddistrito'] ?? null,
                'cod_ubigeo' => $validated['cod_ubigeo'] ?? null,
                'estado_trash' => '1',
                'user_trash' => null,
                'user_created' => Auth::id(),
                'user_updated' => Auth::id(),
            ]);

            PersonaTipoPersona::firstOrCreate(
                [
                    'idpersona' => $proveedor->idpersona,
                    'idpersona_tipo' => self::PERSONA_TIPO_PROVEEDOR,
                ],
                [
                    'user_created' => Auth::id(),
                    'user_updated' => Auth::id(),
                ]
            );

            return ApiResponse::success([
                'idpersona' => $proveedor->idpersona,
                'nombre_proveedor' => $this->resolverNombrePersona($proveedor),
                'numero_documento' => $proveedor->numero_documento,
            ], 'Proveedor registrado correctamente.');
        });
    }

    public function buscarPorDocumento(Request $request)
    {
        $payload = $request->validate([
            'tipo_documento' => [
                'required',
                'integer',
                Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')
                    ->where('estado_trash', '1'),
            ],
            'numero_documento' => ['required', 'string', 'max:20'],
        ]);

        $persona = Persona::with('tiposPersona')
            ->where('tipo_documento', (int) $payload['tipo_documento'])
            ->where('numero_documento', trim($payload['numero_documento']))
            ->where('estado_trash', '1')
            ->first();

        if (! $persona) {
            return ApiResponse::success([
                'existe_persona' => false,
                'tiene_tipo_solicitado' => false,
                'persona' => null,
                'idpersona' => null,
            ], 'Persona no encontrada.');
        }

        $tieneTipoProveedor = $persona->tiposPersona->contains(function ($tipoRel) {
            return (string) $tipoRel->idpersona_tipo === (string) self::PERSONA_TIPO_PROVEEDOR;
        });

        return ApiResponse::success([
            'existe_persona' => true,
            'tiene_tipo_solicitado' => $tieneTipoProveedor,
            'persona' => [
                'idpersona' => $persona->idpersona,
                'numero_documento' => $persona->numero_documento,
                'nombre_proveedor' => $this->resolverNombrePersona($persona),
            ],
            'idpersona' => $persona->idpersona,
        ], $tieneTipoProveedor ? 'La persona ya es proveedor.' : 'Persona encontrada sin tipo proveedor.');
    }

    public function asociarTipoProveedor(Request $request)
    {
        $payload = $request->validate([
            'idpersona' => ['required', 'integer', Rule::exists('persona', 'idpersona')->where('estado_trash', '1')],
        ]);

        $persona = Persona::where('idpersona', $payload['idpersona'])->firstOrFail();

        PersonaTipoPersona::firstOrCreate(
            [
                'idpersona' => $persona->idpersona,
                'idpersona_tipo' => self::PERSONA_TIPO_PROVEEDOR,
            ],
            [
                'user_created' => Auth::id(),
                'user_updated' => Auth::id(),
            ]
        );

        return ApiResponse::success([
            'idpersona' => $persona->idpersona,
            'nombre_proveedor' => $this->resolverNombrePersona($persona),
            'numero_documento' => $persona->numero_documento,
        ], 'Persona agregada como proveedor correctamente.');
    }

    public function distritosSelect(Request $request)
    {
        $search = trim((string) $request->input('term', ''));
        $page = max((int) $request->input('page', 1), 1);
        $perPage = 10;

        $query = UbigeoDistrito::query()
            ->with('provincia.departamento')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('idubigeo_distrito', 'like', "%{$search}%")
                        ->orWhereHas('provincia', fn ($provinciaQuery) => $provinciaQuery->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('provincia.departamento', fn ($departamentoQuery) => $departamentoQuery->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->orderBy('nombre');

        $distritos = (clone $query)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get();

        return response()->json([
            'results' => $distritos
                ->take($perPage)
                ->map(function (UbigeoDistrito $distrito) {
                    $provincia = $distrito->provincia;
                    $departamento = $provincia?->departamento;
                    $detalle = collect([$provincia?->nombre, $departamento?->nombre])->filter()->implode(' - ');

                    return [
                        'id' => $distrito->idubigeo_distrito,
                        'text' => trim($distrito->nombre . ($detalle ? " ({$detalle})" : '')),
                        'provincia' => $provincia?->nombre,
                        'departamento' => $departamento?->nombre,
                        'cod_ubigeo' => $distrito->idubigeo_distrito,
                    ];
                }),
            'pagination' => ['more' => $distritos->count() > $perPage],
        ]);
    }

    private function resolverNombrePersona(?Persona $persona): string
    {
        if (! $persona) {
            return '-';
        }

        $nombreNatural = trim(collect([
            $persona->nombre_persona_natural,
            $persona->apellido_paterno_persona_natural,
            $persona->apellido_materno_persona_natural,
        ])->filter()->implode(' '));

        return collect([
            $persona->descripcion,
            $persona->nombre_comercial,
            $nombreNatural,
        ])->first(fn ($valor) => filled($valor)) ?: 'Sin nombre';
    }
}
