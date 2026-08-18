<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\IngresoEgreso;
use App\Models\IngresoEgresoCategoria;
use App\Models\Persona;
use App\Models\SerieComprobante;
use App\Models\SunatC01TipoComprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class IngresoEgresoController extends Controller
{
    private const PERSONA_TIPO_TRABAJADOR = 2;

    public function index()
    {
        return view('ingreso_egreso');
    }

    public function listar(Request $request)
    {
        $query = IngresoEgreso::query()
            ->select('ingreso_egreso.*')
            ->leftJoin('persona as proveedor', 'proveedor.idpersona', '=', 'ingreso_egreso.idproveedor')
            ->leftJoin('persona as trabajador', 'trabajador.idpersona', '=', 'ingreso_egreso.idtrabajador')
            ->leftJoin('ingreso_egreso_categoria as categoria', 'categoria.idingreso_egreso_categoria', '=', 'ingreso_egreso.idotros_gastos_categoria')
            ->with(['proveedor', 'trabajador', 'categoria'])
            ->latest('ingreso_egreso.idingreso_egreso');

        if (! $request->boolean('incluir_trash')) {
            $query->where('ingreso_egreso.estado_trash', '1');
        }

        if (! $request->has('draw')) {
            if ($request->filled('buscar')) {
                $this->aplicarBusqueda($query, trim((string) $request->input('buscar')));
            }

            $rows = $query->get();
            $this->normalizarListado($rows);

            return ApiResponse::success($rows);
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length === -1 ? -1 : max(1, min($length, 200));
        $search = trim((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count('ingreso_egreso.idingreso_egreso');

        if ($search !== '') {
            $this->aplicarBusqueda($query, $search);
        }

        $recordsFiltered = (clone $query)->count('ingreso_egreso.idingreso_egreso');
        $this->aplicarOrden($query, $request);

        $rows = $length === -1
            ? $query->get()
            : $query->skip($start)->take($length)->get();

        $this->normalizarListado($rows);

        return response()->json([
            'status' => true,
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function trabajadoresSelect(Request $request)
    {
        return $this->personasSelect($request, self::PERSONA_TIPO_TRABAJADOR);
    }

    public function categoriasSelect(Request $request)
    {
        $search = trim((string) $request->input('term', ''));
        $page = max((int) $request->input('page', 1), 1);
        $perPage = 10;

        $query = IngresoEgresoCategoria::query()
            ->where('estado_trash', '1')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre');

        $categorias = (clone $query)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get();

        return response()->json([
            'results' => $categorias
                ->take($perPage)
                ->map(fn (IngresoEgresoCategoria $categoria) => [
                    'id' => $categoria->idingreso_egreso_categoria,
                    'text' => $categoria->nombre,
                ]),
            'pagination' => ['more' => $categorias->count() > $perPage],
        ]);
    }

    public function storeCategoriaRapida(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('ingreso_egreso_categoria', 'nombre')],
            'descripcion' => ['nullable', 'string', 'max:250'],
        ]);

        $categoria = IngresoEgresoCategoria::create([
            ...$validated,
            'estado_trash' => '1',
            'user_trash' => null,
            'user_created' => Auth::id(),
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($categoria, 'Categoria registrada correctamente.');
    }

    public function tiposComprobante(Request $request)
    {
        $tipos = SunatC01TipoComprobante::query()
            ->where('estado_trash', '1')
            ->orderBy('nombre')
            ->get(['idsunat_c01_tipo_comprobante', 'codigo', 'nombre', 'abreviatura']);

        return ApiResponse::success($tipos);
    }

    public function seriesComprobante(Request $request)
    {
        $tipoCodigo = trim((string) $request->input('tipo_comprobante', ''));

        $query = SerieComprobante::query()
            ->with('tipoComprobante')
            ->where('estado_trash', '1')
            ->orderByDesc('predeterminado')
            ->orderBy('serie');

        if ($tipoCodigo !== '') {
            $query->whereHas('tipoComprobante', fn ($subQuery) => $subQuery->where('codigo', $tipoCodigo));
        }

        return ApiResponse::success($query->get(['idserie_comprobante', 'idsunat_c01_tipo_comprobante', 'serie', 'numero', 'predeterminado']));
    }

    public function show(IngresoEgreso $ingresoEgreso)
    {
        $ingresoEgreso->load(['proveedor', 'trabajador', 'categoria']);
        $this->normalizarListado(collect([$ingresoEgreso]));

        return ApiResponse::success($ingresoEgreso);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $payload = $this->prepararPayload($request, $validated);

        $ingresoEgreso = IngresoEgreso::create([
            ...$payload,
            'estado_trash' => '1',
            'user_trash' => null,
            'user_created' => Auth::id(),
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($ingresoEgreso->load(['proveedor', 'trabajador', 'categoria']), 'Ingreso/Egreso registrado correctamente.');
    }

    public function update(Request $request, IngresoEgreso $ingresoEgreso)
    {
        $validated = $this->validar($request);
        $payload = $this->prepararPayload($request, $validated, $ingresoEgreso);

        $ingresoEgreso->update([
            ...$payload,
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($ingresoEgreso->load(['proveedor', 'trabajador', 'categoria']), 'Ingreso/Egreso actualizado correctamente.');
    }

    public function destroy(Request $request, IngresoEgreso $ingresoEgreso)
    {
        if ((string) $ingresoEgreso->estado_trash === '0') {
            return ApiResponse::fail('El registro ya se encuentra eliminado.', 400);
        }

        $ingresoEgreso->update([
            'estado_trash' => '0',
            'user_trash' => Auth::id(),
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($ingresoEgreso, 'Ingreso/Egreso enviado a papelera correctamente.');
    }

    public function restore(Request $request, IngresoEgreso $ingresoEgreso)
    {
        if ((string) $ingresoEgreso->estado_trash === '1') {
            return ApiResponse::fail('El registro ya se encuentra activo.', 400);
        }

        $ingresoEgreso->update([
            'estado_trash' => '1',
            'user_trash' => null,
            'user_updated' => Auth::id(),
        ]);

        return ApiResponse::success($ingresoEgreso, 'Ingreso/Egreso restaurado correctamente.');
    }

    private function personasSelect(Request $request, int $tipoPersona)
    {
        $search = trim((string) $request->input('term', ''));
        $page = max((int) $request->input('page', 1), 1);
        $perPage = 10;

        $query = Persona::query()
            ->where('estado_trash', '1')
            ->whereHas('tiposPersona', fn ($subQuery) => $subQuery->where('idpersona_tipo', $tipoPersona))
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

        $personas = (clone $query)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get();

        return response()->json([
            'results' => $personas
                ->take($perPage)
                ->map(fn (Persona $persona) => [
                    'id' => $persona->idpersona,
                    'text' => trim(($persona->numero_documento ? "{$persona->numero_documento} - " : '') . $this->resolverNombrePersona($persona)),
                ]),
            'pagination' => ['more' => $personas->count() > $perPage],
        ]);
    }

    private function aplicarBusqueda($query, string $search): void
    {
        $query->where(function ($subQuery) use ($search) {
            $subQuery
                ->where('ingreso_egreso.tipo_comprobante', 'like', "%{$search}%")
                ->orWhere('ingreso_egreso.tipo_movimiento', 'like', "%{$search}%")
                ->orWhere('ingreso_egreso.serie_comprobante', 'like', "%{$search}%")
                ->orWhere('ingreso_egreso.descripcion_comprobante', 'like', "%{$search}%")
                ->orWhere('categoria.nombre', 'like', "%{$search}%")
                ->orWhere('proveedor.descripcion', 'like', "%{$search}%")
                ->orWhere('proveedor.nombre_comercial', 'like', "%{$search}%")
                ->orWhere('proveedor.numero_documento', 'like', "%{$search}%")
                ->orWhere('trabajador.descripcion', 'like', "%{$search}%")
                ->orWhere('trabajador.numero_documento', 'like', "%{$search}%");
        });
    }

    private function aplicarOrden($query, Request $request): void
    {
        $column = (int) $request->input('order.0.column', 1);
        $direction = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($column) {
            1 => $query->orderBy('ingreso_egreso.fecha_ingreso', $direction),
            2 => $query->orderBy('ingreso_egreso.tipo_movimiento', $direction),
            3 => $query->orderBy('categoria.nombre', $direction),
            4 => $query->orderBy('proveedor.descripcion', $direction),
            6 => $query->orderBy('ingreso_egreso.precio_con_igv', $direction),
            7 => $query->orderBy('ingreso_egreso.estado_trash', $direction),
            default => $query->orderBy('ingreso_egreso.idingreso_egreso', 'desc'),
        };

        if ($column !== 0) {
            $query->orderBy('ingreso_egreso.idingreso_egreso', 'desc');
        }
    }

    private function validar(Request $request): array
    {
        $request->merge([
            'idproveedor' => $request->input('idproveedor') === '' ? null : $request->input('idproveedor'),
            'idtrabajador' => $request->input('idtrabajador') === '' ? null : $request->input('idtrabajador'),
            'idotros_gastos_categoria' => $request->input('idotros_gastos_categoria') === '' ? null : $request->input('idotros_gastos_categoria'),
            'tipo_movimiento' => mb_strtoupper(trim((string) $request->input('tipo_movimiento', ''))),
            'precio_sin_igv' => $request->input('precio_sin_igv') === '' ? 0 : $request->input('precio_sin_igv'),
            'precio_igv' => $request->input('precio_igv') === '' ? 0 : $request->input('precio_igv'),
            'val_igv' => $request->input('val_igv') === '' ? 0 : $request->input('val_igv'),
            'precio_con_igv' => $request->input('precio_con_igv') === '' ? 0 : $request->input('precio_con_igv'),
        ]);

        return $request->validate([
            'idproveedor' => ['nullable', 'integer', Rule::exists('persona', 'idpersona')->where('estado_trash', '1')],
            'idtrabajador' => ['nullable', 'integer', Rule::exists('persona', 'idpersona')->where('estado_trash', '1')],
            'idotros_gastos_categoria' => ['required', 'integer', Rule::exists('ingreso_egreso_categoria', 'idingreso_egreso_categoria')->where('estado_trash', '1')],
            'tipo_movimiento' => ['required', 'string', Rule::in(['INGRESO', 'EGRESO'])],
            'tipo_comprobante' => ['nullable', 'string', 'max:30'],
            'serie_comprobante' => ['nullable', 'string', 'max:30'],
            'fecha_ingreso' => ['required', 'date'],
            'precio_sin_igv' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'precio_igv' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'val_igv' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'precio_con_igv' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'descripcion_comprobante' => ['nullable', 'string'],
            'comprobante_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'comprobante_actual' => ['nullable', 'string'],
        ], [
            'idotros_gastos_categoria.required' => 'Seleccione una categoria.',
            'tipo_movimiento.required' => 'Seleccione si es ingreso o egreso.',
            'tipo_movimiento.in' => 'El tipo debe ser INGRESO o EGRESO.',
            'fecha_ingreso.required' => 'Seleccione la fecha.',
            'precio_sin_igv.required' => 'Ingrese el precio sin IGV.',
            'precio_con_igv.required' => 'Ingrese el precio con IGV.',
        ]);
    }

    private function prepararPayload(Request $request, array $validated, ?IngresoEgreso $actual = null): array
    {
        unset($validated['comprobante_file'], $validated['comprobante_actual']);

        $fecha = Carbon::parse($validated['fecha_ingreso']);
        $validated['name_day'] = mb_strtoupper($fecha->locale('es')->isoFormat('dddd'));
        $validated['name_month'] = mb_strtoupper($fecha->locale('es')->isoFormat('MMMM'));
        $validated['name_year'] = (int) $fecha->format('Y');

        if ($request->hasFile('comprobante_file')) {
            if ($actual?->comprobante) {
                $this->eliminarComprobante($actual->comprobante);
            }
            $validated['comprobante'] = $this->guardarComprobante($request->file('comprobante_file'));
        } elseif ($request->has('comprobante_actual') && blank($request->input('comprobante_actual'))) {
            if ($actual?->comprobante) {
                $this->eliminarComprobante($actual->comprobante);
            }
            $validated['comprobante'] = null;
        }

        return $validated;
    }

    private function guardarComprobante($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $nombreArchivo = 'ingreso_egreso_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
        $directorio = public_path('assets/modulo/ingreso_egreso/comprobantes');

        if (! file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $file->move($directorio, $nombreArchivo);

        return $nombreArchivo;
    }

    private function eliminarComprobante(?string $nombreArchivo): void
    {
        if (blank($nombreArchivo)) {
            return;
        }

        $ruta = public_path('assets/modulo/ingreso_egreso/comprobantes/' . $nombreArchivo);

        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    private function normalizarListado($rows): void
    {
        $rows->each(function (IngresoEgreso $row) {
            $row->proveedor_nombre = $this->resolverNombrePersona($row->proveedor);
            $row->trabajador_nombre = $this->resolverNombrePersona($row->trabajador);
            $row->categoria_nombre = $row->categoria?->nombre ?: 'Sin categoria';
            $row->tipo_movimiento_label = $row->tipo_movimiento ?: 'EGRESO';
            $row->comprobante_url = $row->comprobante
                ? asset('assets/modulo/ingreso_egreso/comprobantes/' . $row->comprobante)
                : null;
        });
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
