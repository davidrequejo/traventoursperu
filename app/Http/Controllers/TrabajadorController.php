<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Persona;
use App\Models\PersonaTipoPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\PersonaCodigoService;

class TrabajadorController extends Controller
{
    private const PERSONA_TIPO_TRABAJADOR = 2;

    private function getPerfilDirectory(): string
    {
        return 'assets/modulo/persona/perfil';
    }

    public function index()
    {
        return view('trabajador');
    }

    public function listar(Request $request)
    {
        $query = Persona::with(['cargo', 'docIdentidad'])
            ->whereHas('tiposPersona', fn ($subQuery) => $subQuery->where('persona_tipo_persona.idpersona_tipo', self::PERSONA_TIPO_TRABAJADOR))
            ->latest('idpersona');

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->input('buscar'));
            $query->where(function ($subQuery) use ($buscar) {
                $subQuery
                    ->where('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('nombre_persona_natural', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno_persona_natural', 'like', "%{$buscar}%")
                    ->orWhere('apellido_materno_persona_natural', 'like', "%{$buscar}%")
                    ->orWhere('numero_documento', 'like', "%{$buscar}%")
                    ->orWhere('correo', 'like', "%{$buscar}%")
                    ->orWhere('celular', 'like', "%{$buscar}%");
            });
        }

        $trabajadores = $query->get();
        $this->normalizarTrabajadores($trabajadores);

        return ApiResponse::success($trabajadores);
    }

    public function show(Persona $trabajador)
    {
        $trabajador->load(['cargo', 'docIdentidad', 'users']);
        $this->normalizarTrabajadores(collect([$trabajador]));

        return ApiResponse::success($trabajador);
    }

    public function buscarPorDocumento(Request $request)
    {
        try {
            $payload = $request->validate([
                'tipo_documento' => [
                    'required',
                    'integer',
                    Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')
                        ->where('estado_trash', '1'),
                ],
                'numero_documento' => ['required', 'string', 'max:20'],
                'idpersona_tipo' => ['sometimes', 'nullable', 'string', 'max:10'],
            ]);

            $tipoDocumento = (int) $payload['tipo_documento'];
            $numeroDocumento = trim($payload['numero_documento']);
            $tipoRequerido = $payload['idpersona_tipo'] ?? self::PERSONA_TIPO_TRABAJADOR;

            $persona = Persona::with(['tiposPersona', 'cargo', 'docIdentidad'])
                ->where('tipo_documento', $tipoDocumento)
                ->where('numero_documento', $numeroDocumento)
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

            $this->normalizarTrabajadores(collect([$persona]));

            $tieneTipo = $persona->tiposPersona->contains(function ($tipoRel) use ($tipoRequerido) {
                return (string) $tipoRel->idpersona_tipo === (string) $tipoRequerido;
            });

            return ApiResponse::success([
                'existe_persona' => true,
                'tiene_tipo_solicitado' => $tieneTipo,
                'persona' => $persona,
                'idpersona' => $persona->idpersona,
            ], $tieneTipo ? 'Persona ya tiene el tipo solicitado.' : 'Persona existe pero sin el tipo solicitado.');
        } catch (\Throwable $e) {
            return ApiResponse::error($e);
        }
    }

    public function store(Request $request)
    {
        $validated = $this->validarTrabajador($request);
        $this->mapearDistrito($request, $validated);

        return DB::transaction(function () use ($request, $validated) {
            if (empty($validated['codigo'])) {
                $validated['codigo'] = $this->generarCodigo();
            }

            if ($request->hasFile('imagen')) {
                $validated['foto_perfil'] = $this->guardarImagenPersonalizada($request->file('imagen'));
            }

            $validated['tipo_persona_sunat'] = $validated['tipo_persona_sunat'] ?? 'NATURAL';
            $validated['estado_trash'] = '1';
            $validated['user_created'] = Auth::id();
            $validated['user_updated'] = Auth::id();
            $validated['user_trash'] = null;

            $trabajador = Persona::create($validated);
            $this->syncTipoTrabajador($trabajador, Auth::id());

            return ApiResponse::success($trabajador->load(['cargo', 'docIdentidad']), 'Trabajador registrado correctamente.');
        });
    }

    public function update(Request $request, Persona $trabajador)
    {
        $validated = $this->validarTrabajador($request, $trabajador->idpersona);
        $this->mapearDistrito($request, $validated);

        return DB::transaction(function () use ($request, $trabajador, $validated) {
            if ($request->hasFile('imagen')) {
                $this->eliminarImagenPersonalizada($trabajador->foto_perfil);
                $validated['foto_perfil'] = $this->guardarImagenPersonalizada($request->file('imagen'));
            } elseif ($request->has('imagenactual') && blank($request->input('imagenactual'))) {
                $this->eliminarImagenPersonalizada($trabajador->foto_perfil);
                $validated['foto_perfil'] = null;
            }

            $validated['tipo_persona_sunat'] = $validated['tipo_persona_sunat'] ?? 'NATURAL';
            $validated['user_updated'] = Auth::id();

            $trabajador->update($validated);
            $this->syncTipoTrabajador($trabajador, Auth::id());

            return ApiResponse::success($trabajador->load(['cargo', 'docIdentidad']), 'Trabajador actualizado correctamente.');
        });
    }

    public function destroy(Request $request, Persona $trabajador)
    {
        if ((string) $trabajador->estado_trash === '0') {
            return ApiResponse::fail('El trabajador ya se encuentra eliminado.', 400);
        }

        $trabajador->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
        ]);

        return ApiResponse::success($trabajador, 'Trabajador eliminado correctamente.');
    }

    public function restore(Request $request, int $trabajador)
    {
        $persona = Persona::where('idpersona', $trabajador)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $persona->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($persona, 'Trabajador restaurado correctamente.');
    }

    private function validarTrabajador(Request $request, ?int $idPersona = null): array
    {
        $rules = [
            'codigo' => ['nullable', 'string', 'max:20', Rule::unique('persona', 'codigo')->ignore($idPersona, 'idpersona')],
            'idcargo_trabajador' => ['nullable', 'integer', Rule::exists('persona_cargo', 'idpersona_cargo')],
            'tipo_persona_sunat' => ['nullable', 'string', 'in:NATURAL,JURIDICA'],
            'tipo_documento' => ['required', 'integer', Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')->where('estado_trash', '1')],
            'numero_documento' => ['required', 'string', 'max:20', Rule::unique('persona', 'numero_documento')->ignore($idPersona, 'idpersona')],
            'descripcion' => ['required', 'string', 'max:255'],
            'nombre_persona_natural' => ['nullable', 'string', 'max:100'],
            'apellido_paterno_persona_natural' => ['nullable', 'string', 'max:100'],
            'apellido_materno_persona_natural' => ['nullable', 'string', 'max:100'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'nacionalidad' => ['nullable', 'string', 'max:50'],
            'estado_civil' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:15'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'direccion_referencia' => ['nullable', 'string', 'max:255'],
            'iddistrito_envio' => ['nullable', 'integer', Rule::exists('ubigeo_distrito', 'idubigeo_distrito')],
            'cod_ubigeo' => ['nullable', 'string', 'max:10'],
            'correo' => ['nullable', 'email', 'max:255'],
            'sexo' => ['nullable', 'string', 'in:M,F'],
            'numero_licencia' => ['nullable', 'string', 'max:50'],
            'placa_vehiculo' => ['nullable', 'string', 'max:20'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'imagenactual' => ['nullable', 'string'],
        ];

        $validated = $request->validate($rules);
        unset($validated['imagen'], $validated['imagenactual'], $validated['iddistrito_envio']);

        return $validated;
    }

    private function mapearDistrito(Request $request, array &$validated): void
    {
        if ($request->filled('iddistrito_envio')) {
            $validated['iddistrito'] = $request->integer('iddistrito_envio');
        }
    }

    private function syncTipoTrabajador(Persona $persona, int $userId): void
    {
        PersonaTipoPersona::firstOrCreate(
            [
                'idpersona' => $persona->idpersona,
                'idpersona_tipo' => self::PERSONA_TIPO_TRABAJADOR,
            ],
            [
                'user_created' => $userId,
                'user_updated' => $userId,
            ]
        );
    }

    private function generarCodigo(): string
    {
        return PersonaCodigoService::siguienteCodigo();
    }

    private function normalizarTrabajadores($trabajadores): void
    {
        $trabajadores->each(function (Persona $trabajador) {
            $trabajador->foto_perfil = filled($trabajador->foto_perfil)
                ? $trabajador->foto_perfil
                : ($trabajador->sexo === 'F' ? 'mujer.png' : 'hombre.png');

            $trabajador->tipo_documento_label = $trabajador->docIdentidad?->abreviatura ?: $trabajador->tipo_documento;

            $trabajador->nombre_trabajador = collect([
                trim(collect([
                    $trabajador->nombre_persona_natural,
                    $trabajador->apellido_paterno_persona_natural,
                    $trabajador->apellido_materno_persona_natural,
                ])->filter()->implode(' ')),
                $trabajador->descripcion,
            ])->first(fn ($valor) => filled($valor)) ?: 'Sin nombre';
        });
    }

    private function guardarImagenPersonalizada($imagen): string
    {
        $extension = $imagen->getClientOriginalExtension();
        $nombreArchivo = 'trabajador_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
        $directorio = public_path($this->getPerfilDirectory());

        if (! file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $imagen->move($directorio, $nombreArchivo);

        return $nombreArchivo;
    }

    private function eliminarImagenPersonalizada(?string $nombreArchivo): void
    {
        if (blank($nombreArchivo) || in_array($nombreArchivo, ['hombre.png', 'mujer.png'], true)) {
            return;
        }

        $ruta = public_path($this->getPerfilDirectory() . '/' . $nombreArchivo);

        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }
}
