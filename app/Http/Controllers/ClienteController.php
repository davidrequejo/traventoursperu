<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Persona;
use App\Models\PersonaTipoPersona;
use App\Models\UbigeoDistrito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Services\PersonaCodigoService;



class ClienteController extends Controller
{
    private function getPerfilDirectory(): string
    {
        return 'assets/modulo/persona/perfil';
    } 

    public function index(Request $request)
    {
        return view('cliente');
    }

    public function listar(Request $request)
    {
        $columnas = collect(Schema::getColumnListing('persona'));

        $query = Persona::with(['cargo', 'docIdentidad', 'distrito.provincia.departamento', 'conyuge.docIdentidad'])
            ->whereHas('tiposPersona', function ($q) {
                $q->where('idpersona_tipo', '3'); // Solo clientes
            });

        if (!$request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        if ($request->filled('idpersona')) {
            $query->where('idpersona', $request->integer('idpersona'));
        }

        if ($request->filled('numero_documento')) {
            $query->where('numero_documento', $request->string('numero_documento'));
        }

        $camposBusqueda = $columnas->intersect([
            'codigo',
            'descripcion',
            'nombre_comercial',
            'nombre_persona_natural',
            'apellido_paterno_persona_natural',
            'apellido_materno_persona_natural',
            'nombre_razonsocial',
            'apellidos_nombrecomercial',
            'numero_documento',
            'correo',
            'celular',
            'tipo_persona_sunat',
            'estado_civil',
            'nacionalidad',
            'direccion',
            'cod_ubigeo',
        ]);

        $aplicarBusqueda = function ($subQuery, string $buscar) use ($camposBusqueda) {
            foreach ($camposBusqueda as $campo) {
                $subQuery->orWhere($campo, 'LIKE', "%{$buscar}%");
            }

            $subQuery
                ->orWhereHas('docIdentidad', function ($docQuery) use ($buscar) {
                    $docQuery
                        ->where('abreviatura', 'LIKE', "%{$buscar}%")
                        ->orWhere('descripcion', 'LIKE', "%{$buscar}%");
                })
                ->orWhereHas('distrito', function ($distritoQuery) use ($buscar) {
                    $distritoQuery->where('nombre', 'LIKE', "%{$buscar}%");
                })
                ->orWhereHas('distrito.provincia', function ($provinciaQuery) use ($buscar) {
                    $provinciaQuery->where('nombre', 'LIKE', "%{$buscar}%");
                })
                ->orWhereHas('distrito.provincia.departamento', function ($departamentoQuery) use ($buscar) {
                    $departamentoQuery->where('nombre', 'LIKE', "%{$buscar}%");
                });
        };

        if (! $request->has('draw')) {
            if ($request->filled('buscar')) {
                $buscar = trim((string) $request->input('buscar'));
                $query->where(function ($subQuery) use ($aplicarBusqueda, $buscar) {
                    $aplicarBusqueda($subQuery, $buscar);
                });
            }

            $clientes = $query->latest('idpersona')->get();
            $this->normalizarClientes($clientes);

            return ApiResponse::success($clientes);
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $search = trim((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count('idpersona');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($aplicarBusqueda, $search) {
                $aplicarBusqueda($subQuery, $search);
            });
        }

        $recordsFiltered = (clone $query)->count('idpersona');

        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumns = [
            0 => 'codigo',
            2 => 'descripcion',
            3 => 'correo',
            4 => 'tipo_persona_sunat',
            5 => 'estado_civil',
            6 => 'nacionalidad',
            7 => 'estado_trash',
            8 => 'updated_at',
        ];
        $orderBy = $orderColumns[$orderColumnIndex] ?? 'idpersona';
        $query->orderBy($orderBy, $orderDir);
        if ($orderBy !== 'idpersona') {
            $query->orderBy('idpersona', 'desc');
        }

        if ($length === -1) {
            $clientes = $query->get();
        } else {
            $length = max(1, min($length, 200));
            $clientes = $query->skip($start)->take($length)->get();
        }

        $this->normalizarClientes($clientes);

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $clientes,
        ]);
    }

    public function show(Persona $cliente)
    {
        $cliente->load(['cargo', 'docIdentidad', 'users', 'distrito.provincia.departamento', 'conyuge.docIdentidad']);
        $this->normalizarClientes(collect([$cliente]));

        return ApiResponse::success($cliente);
    }

    /**
     * Store a newly created resource in storage.
     */
    // Método store
    public function store(Request $request)
    {
        $validated = $this->validarPersona($request);

        if ($request->has('iddistrito_envio')) {
            $validated['iddistrito'] = $request->input('iddistrito_envio');
        }

        DB::beginTransaction();
        try {

            if (empty($validated['codigo'])) {
                $validated['codigo'] = $this->generarCodigo();
            }

            // Guardar imagen (si viene) y obtener solo el nombre
            if ($request->hasFile('imagen')) {
                $nombreImagen = $this->guardarImagenPersonalizada($request->file('imagen'));
                $validated['foto_perfil'] = $nombreImagen;
            }

            $validated['estado_trash'] = '1';
            $validated['user_created'] = Auth::id();
            $validated['user_updated'] = Auth::id();
            $validated['user_trash'] = null;

            $persona = Persona::create($validated);

            // Sincronizar tipos de persona (stakeholders)
            $this->syncTiposPersona($request, $persona);
            $this->sincronizarConyuge($request, $persona);

            DB::commit();
            $persona->load('conyuge.docIdentidad');
            $this->normalizarClientes(collect([$persona]));
            return ApiResponse::success($persona, 'Cliente registrado correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error($e, 'Error al registrar el cliente.');
        }
    }

    // Método update
    public function update(Request $request, Persona $cliente)
    {

        $validated = $this->validarPersona($request, $cliente->idpersona);

        if ($request->has('iddistrito_envio')) {
            $validated['iddistrito'] = $request->input('iddistrito_envio');
        }

        DB::beginTransaction();
        try {
            $idConyugeAnterior = $cliente->idconyuge;

            // Si se envía nueva imagen
            if ($request->hasFile('imagen')) {
                // Eliminar la imagen anterior si existe
                if ($cliente->foto_perfil) {
                    $this->eliminarImagenPersonalizada($cliente->foto_perfil);
                }
                $nombreImagen = $this->guardarImagenPersonalizada($request->file('imagen'));
                $validated['foto_perfil'] = $nombreImagen;
            } else {
                // Si no viene nueva imagen pero se indica que se debe eliminar la actual (imagenactual vacío)
                if ($request->has('imagenactual') && empty($request->input('imagenactual'))) {
                    if ($cliente->foto_perfil) {
                        $this->eliminarImagenPersonalizada($cliente->foto_perfil);
                        $validated['foto_perfil'] = null;
                    }
                }
            }

            $validated['user_updated'] = Auth::id();
            $cliente->update($validated);

            // Sincronizar tipos de persona (stakeholders)
            $this->syncTiposPersona($request, $cliente);
            $this->sincronizarConyuge($request, $cliente, $idConyugeAnterior);

            DB::commit();
            $cliente->load('conyuge.docIdentidad');
            $this->normalizarClientes(collect([$cliente]));
            return ApiResponse::success($cliente, 'Cliente actualizado correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error($e, 'Error al actualizar el cliente.');
        }
    }

    public function destroy(Request $request, Persona $cliente)
    {
        if ((string) $cliente->estado_trash === '0') {
            return ApiResponse::fail('El cliente ya se encuentra eliminado.', 400);
        }

        $cliente->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
        ]);

        return ApiResponse::success($cliente, 'Cliente eliminado correctamente.');
    }

    public function restore(Request $request, int $cliente)
    {
        $persona = Persona::where('idpersona', $cliente)
            ->where('estado_trash', '0')
            ->firstOrFail();

        $persona->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($persona, 'Cliente restaurado correctamente.');
    }


    
  /**
   * Sirve para mostrar el formulario de edición de un registro existente.
   * - Abrir pantalla “Editar proyecto”
   * - GET /proyectos/{proyecto}/edit
   */
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
                'idpersona_tipo'   => ['sometimes', 'nullable', 'string', 'max:10'],
            ]);

            $tipoDocumento = (int) $payload['tipo_documento'];
            $numeroDocumento = trim($payload['numero_documento']);
            $tipoRequerido = $payload['idpersona_tipo'] ?? null;

            // Buscar persona sin filtrar por tipo
            $persona = Persona::with(['tiposPersona', 'conyuge.docIdentidad'])
                ->where('tipo_documento', $tipoDocumento)
                ->where('numero_documento', $numeroDocumento)
                ->where('estado_trash', '1')
                ->first();

            if (!$persona) {
                return ApiResponse::success([
                    'existe_persona' => false,
                    'tiene_tipo_solicitado' => false,
                    'persona' => null,
                    'idpersona' => null,
                ], 'Persona no encontrada.');
            }

            // Verificar si tiene el tipo requerido
            $tieneTipo = false;
            if ($tipoRequerido) {
                $tieneTipo = $persona->tiposPersona->contains(function ($tipoRel) use ($tipoRequerido) {
                    return $tipoRel->idpersona_tipo == $tipoRequerido;
                });
            }

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


    /**
     * Asocia un tipo de persona a una persona existente.
     * No elimina otros tipos existentes.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function asociarTipo(Request $request)
    {
        try {
            $payload = $request->validate([
                'idpersona' => ['required', 'integer', 'exists:persona,idpersona'],
                'idpersona_tipo' => ['required', 'string', 'max:10'],
            ]);

            $idpersona = $payload['idpersona'];
            $idpersonaTipo = $payload['idpersona_tipo'];

            // Verificar si ya existe la relación
            $existe = PersonaTipoPersona::where('idpersona', $idpersona)
                ->where('idpersona_tipo', $idpersonaTipo)
                ->exists();

            if ($existe) {
                return ApiResponse::error('La persona ya tiene asignado este tipo.', 409);
            }

            // Crear la relación incluyendo los campos user_created y user_updated
            PersonaTipoPersona::create([
                'idpersona' => $idpersona,
                'idpersona_tipo' => $idpersonaTipo,
                'estado_trash' => '1',
                'user_created' => Auth::id(),
                'user_updated' => Auth::id(),
            ]);

            return ApiResponse::success([
                'asignado' => true,
                'idpersona' => $idpersona,
                'idpersona_tipo' => $idpersonaTipo,
            ], 'Tipo asignado correctamente.');

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function registrarConyuge(Request $request)
    {
        $validated = $request->validate([
            'tipo_documento' => [
                'required',
                'integer',
                Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')
                    ->where('estado_trash', '1'),
            ],
            'numero_documento' => ['required', 'string', 'max:20', Rule::unique('persona', 'numero_documento')],
            'descripcion' => ['required', 'string', 'max:255'],
            'nombre_persona_natural' => ['nullable', 'string', 'max:100'],
            'apellido_paterno_persona_natural' => ['nullable', 'string', 'max:100'],
            'apellido_materno_persona_natural' => ['nullable', 'string', 'max:100'],
            'sexo' => ['required', 'string', Rule::in(['M', 'F'])],
            'fecha_nacimiento' => ['required', 'date'],
            'nacionalidad' => ['required', 'string', 'max:50'],
            'celular' => ['nullable', 'string', 'max:15'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['required', 'string'],
            'direccion_referencia' => ['nullable', 'string'],
            'iddistrito' => ['nullable', 'integer', 'exists:ubigeo_distrito,idubigeo_distrito'],
            'cod_ubigeo' => ['nullable', 'string', 'max:10'],
        ]);

        $persona = DB::transaction(function () use ($validated) {
            $persona = Persona::create([
                ...$validated,
                'codigo' => $this->generarCodigo(),
                'tipo_persona_sunat' => 'NATURAL',
                'estado_civil' => 'CASADO',
                'estado_trash' => '1',
                'user_created' => Auth::id(),
                'user_updated' => Auth::id(),
            ]);

            PersonaTipoPersona::firstOrCreate(
                [
                    'idpersona' => $persona->idpersona,
                    'idpersona_tipo' => '3',
                ],
                [
                    'user_created' => Auth::id(),
                    'user_updated' => Auth::id(),
                ],
            );

            return $persona;
        });

        $persona->load('docIdentidad');

        return ApiResponse::success($persona, 'Cónyuge registrado correctamente.');
    }

    /**
     * Agrega un tipo de persona (stakeholder) a la persona.
     * No elimina otros tipos existentes.
     *
     * @param Request $request
     * @param Persona $persona
     * @return void
     */
    private function syncTiposPersona(Request $request, Persona $persona): void
    {
        $campoTipo = 'idpersona_tipo';

        // Si se envía un tipo válido
        if ($request->filled($campoTipo)) {
            $idpersona_tipo = $request->input($campoTipo);
            
            // Verificar si ya existe esa combinación para evitar duplicados
            $existe = PersonaTipoPersona::where('idpersona', $persona->idpersona)
                ->where('idpersona_tipo', $idpersona_tipo)
                ->exists();

            if (!$existe) {
                PersonaTipoPersona::create([
                    'idpersona'       => $persona->idpersona,
                    'idpersona_tipo'  => $idpersona_tipo,
                    'user_created'    => Auth::id(),
                    'user_updated'    => Auth::id(),
                ]);
            }
        }
        // Si no se envía tipo, no se hace nada (no se eliminan los existentes)
    }

    /**
     * Validar los datos de entrada para Persona.
     */
    private function validarPersona(Request $request, ?int $idPersona = null): array
    {
        $rules = [
            'codigo' => 'nullable|string|max:20|unique:persona,codigo,' . $idPersona . ',idpersona',
            'idpersona_tipo' => 'required|exists:persona_tipo,idpersona_tipo',
            'tipo_persona_sunat' => 'nullable|string|in:NATURAL,JURIDICA',
            'tipo_documento' => ['required', 'integer', Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')->where('estado_trash', '1')],
            'numero_documento' => 'required|string|max:20|unique:persona,numero_documento,' . $idPersona . ',idpersona',
            'descripcion' => 'nullable|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'nombre_persona_natural' => 'nullable|string|max:100',
            'apellido_paterno_persona_natural' => 'nullable|string|max:100',
            'apellido_materno_persona_natural' => 'nullable|string|max:100',
            'fecha_nacimiento' => 'nullable|date',
            'nacionalidad' => 'nullable|string|max:50',
            'estado_civil' => ['nullable', 'string', Rule::in(['SOLTERO', 'CASADO', 'DIVORCIADO', 'VIUDO'])],
            'idconyuge' => [
                'nullable',
                'required_if:estado_civil,CASADO',
                'integer',
                Rule::exists('persona', 'idpersona')->where('estado_trash', '1'),
                Rule::notIn(array_filter([$idPersona])),
            ],
            'conyuge_tipo_documento' => [
                'nullable',
                'required_if:estado_civil,CASADO',
                'integer',
                Rule::exists('sunat_c06_doc_identidad', 'idsunat_c06_doc_identidad')->where('estado_trash', '1'),
            ],
            'conyuge_numero_documento' => ['nullable', 'required_if:estado_civil,CASADO', 'string', 'max:20'],
            'conyuge_descripcion' => ['nullable', 'required_if:estado_civil,CASADO', 'string', 'max:255'],
            'conyuge_celular' => ['nullable', 'string', 'max:15'],
            'celular' => 'nullable|string|max:15',
            'direccion' => 'nullable|string',
            'direccion_referencia' => 'nullable|string',
            'iddistrito_envio' => 'nullable|integer|exists:ubigeo_distrito,idubigeo_distrito', // regla temporal
            'cod_ubigeo' => 'nullable|string|max:10',
            'correo' => 'nullable|email|max:255',
            'sexo' => 'nullable|string|in:M,F',
            'numero_licencia' => 'nullable|string|max:50',
            'placa_vehiculo' => 'nullable|string|max:20',
            'foto_perfil' => 'nullable|image|max:2048',
            'imagen' => 'nullable|image|max:2048', // 2MB
            'imagenactual' => 'nullable|string',
        ];

        $validated = $request->validate($rules);
        
        // Eliminar iddistrito_envio del array final ya que no existe en el modelo
        unset($validated['iddistrito_envio']);
            // Eliminar los campos que no pertenecen al modelo (solo para validación)
        unset(
            $validated['imagen'],
            $validated['imagenactual'],
            $validated['conyuge_tipo_documento'],
            $validated['conyuge_numero_documento'],
            $validated['conyuge_descripcion'],
            $validated['conyuge_celular'],
        );
        
        // Si se envió iddistrito_envio, ya lo hemos mapeado antes, por lo que solo limpiamos
        // Nota: el mapeo debe hacerse antes de llamar a create/update, no aquí.
        
        $validated = array_filter($validated, function ($value) {
            return !is_null($value);
        });

        if ($request->has('estado_civil') && ! $request->filled('estado_civil')) {
            $validated['estado_civil'] = null;
        }

        if ($request->input('estado_civil') !== 'CASADO') {
            $validated['idconyuge'] = null;
        }

        return $validated;
    }

    private function sincronizarConyuge(Request $request, Persona $persona, ?int $idConyugeAnterior = null): void
    {
        $estadoCivil = $request->input('estado_civil');
        $idConyugeAnterior ??= $persona->idconyuge;
        $conyugeAnterior = $idConyugeAnterior
            ? Persona::query()->find($idConyugeAnterior)
            : null;

        if ($estadoCivil !== 'CASADO') {
            $persona->update(['idconyuge' => null]);

            if ($conyugeAnterior && (int) $conyugeAnterior->idconyuge === (int) $persona->idpersona) {
                $conyugeAnterior->update([
                    'estado_civil' => $estadoCivil,
                    'idconyuge' => null,
                    'user_updated' => Auth::id(),
                ]);
            }

            return;
        }

        $conyuge = Persona::query()
            ->where('estado_trash', '1')
            ->findOrFail($request->integer('idconyuge'));

        if ((int) $conyuge->idpersona === (int) $persona->idpersona) {
            throw ValidationException::withMessages([
                'idconyuge' => 'Una persona no puede registrarse como su propio cónyuge.',
            ]);
        }

        if ($conyuge->idconyuge && (int) $conyuge->idconyuge !== (int) $persona->idpersona) {
            throw ValidationException::withMessages([
                'idconyuge' => 'La persona seleccionada ya tiene otro cónyuge registrado.',
            ]);
        }

        if ($conyugeAnterior && (int) $conyugeAnterior->idpersona !== (int) $conyuge->idpersona) {
            $conyugeAnterior->update([
                'idconyuge' => null,
                'user_updated' => Auth::id(),
            ]);
        }

        $persona->update([
            'estado_civil' => 'CASADO',
            'idconyuge' => $conyuge->idpersona,
            'user_updated' => Auth::id(),
        ]);

        $conyuge->update([
            'estado_civil' => 'CASADO',
            'idconyuge' => $persona->idpersona,
            'user_updated' => Auth::id(),
        ]);
    }

    /**
     * Generar un código único para la persona (ej: PER-0001).
     */
    /*private function generarCodigo(): string
    {
        $ultimo = Persona::where('codigo', 'LIKE', 'PER-%')
            ->orderBy('idpersona', 'desc')
            ->first();

        if (!$ultimo || !$ultimo->codigo) {
            return 'PER-0001';
        }

        $numero = (int) substr($ultimo->codigo, 4);
        $nuevo = $numero + 1;
        return 'PER-' . str_pad($nuevo, 4, '0', STR_PAD_LEFT);
    }*/

    private function generarCodigo(): string
    {
        return PersonaCodigoService::siguienteCodigo();
    }

    private function normalizarClientes($clientes): void
    {
        $clientes->each(function (Persona $cliente) {
            if (blank($cliente->foto_perfil)) {
                $cliente->foto_perfil = $cliente->sexo === 'F' ? 'mujer.png' : 'hombre.png';
            }

            $cliente->tipo_documento_label = $cliente->docIdentidad?->abreviatura ?: $cliente->tipo_documento;
            $cliente->conyuge_nombre = $cliente->conyuge
                ? $this->resolverNombrePersona($cliente->conyuge)
                : null;
            $cliente->conyuge_tipo_documento_label = $cliente->conyuge?->docIdentidad?->abreviatura
                ?: $cliente->conyuge?->tipo_documento;

            $cliente->nombre_cliente = collect([
                $cliente->descripcion,
                $cliente->nombre_comercial,
                $cliente->nombre_persona_natural,
                trim(collect([
                    $cliente->apellido_paterno_persona_natural,
                    $cliente->apellido_materno_persona_natural,
                ])->filter()->implode(' ')),
                trim(collect([
                    $cliente->nombre_razonsocial,
                    $cliente->apellidos_nombrecomercial,
                ])->filter()->implode(' ')),
            ])->first(fn ($valor) => filled($valor)) ?: 'Sin nombre';
        });
    }

    private function resolverNombrePersona(Persona $persona): string
    {
        return collect([
            $persona->descripcion,
            trim(collect([
                $persona->nombre_persona_natural,
                $persona->apellido_paterno_persona_natural,
                $persona->apellido_materno_persona_natural,
            ])->filter()->implode(' ')),
            $persona->nombre_comercial,
        ])->first(fn ($valor) => filled($valor)) ?: 'Sin nombre';
    }

    public function getselect2distrito()
    {
        try {
            $data = UbigeoDistrito::ConUbicacion()
                ->orderBy('departamento')
                ->orderBy('provincia')
                ->orderBy('distrito')
                ->get();

            $options = '';
            foreach ($data as $t) {
                $options .= '<option value="'.$t->distrito.'" iddistrito="'.$t->idubigeo_distrito.'" codubigueo="'.$t->ubigeo_inei.'" idprovincia="'.$t->idubigeo_provincia.'" provincia="'.$t->provincia.'"'
                    .' iddepartamento="'.$t->idubigeo_departamento.'" departamento="'.$t->departamento.'">' . e($t->distrito) .'</option>';
            }

            return ApiResponse::success($options, 'Lista de distritos obtenida');

        } catch (\Throwable $e) {
            return ApiResponse::error($e);
        }
    }

    /**
     * Guarda una imagen en la ruta personalizada y retorna la ruta relativa.
     * @param \Illuminate\Http\UploadedFile $imagen
     * @return string Ruta relativa desde public (ej: assets/modulo/persona/perfil/nombre.jpg)
     */
    // Tu función guardarImagenPersonalizada modificada (retorna solo el nombre)
    private function guardarImagenPersonalizada($imagen): string
    {
        $extension = $imagen->getClientOriginalExtension();
        $nombreArchivo = 'cliente_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
        $directorio = public_path($this->getPerfilDirectory());
        
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        $imagen->move($directorio, $nombreArchivo);
        
        return $nombreArchivo; // solo el nombre
    }

    // Tu función eliminarImagenPersonalizada (recibe el nombre)
    private function eliminarImagenPersonalizada(?string $nombreArchivo): void
    {
        if ($nombreArchivo) {
            $ruta = public_path($this->getPerfilDirectory() . '/' . $nombreArchivo);
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
    }

}
