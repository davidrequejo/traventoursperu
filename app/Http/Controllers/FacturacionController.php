<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use App\Models\CuentaBancaria;
use App\Models\Empresa;
use App\Models\Persona;
use App\Models\PersonaTipo;
use App\Models\PersonaTipoPersona;
use App\Models\Producto;
use App\Models\RDocumento;
use App\Models\RDocumentoArchivo;
use App\Models\SerieComprobante;
use App\Models\SunatC01TipoComprobante;
use App\Models\SunatC09CodigoNotaCredito;
use App\Models\SunatC06DocIdentidad;
use App\Services\FacturacionElectronica\SunatFacturacionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Luecano\NumeroALetras\NumeroALetras;

class FacturacionController extends Controller
{
    private const TIPOS_DOCUMENTO_PERMITIDOS = ['12', '01', '03', '07', '08'];

    public function index()
    {
        return view('facturacion');
    }

    public function filtros()
    {
        $baseDocumentos = RDocumento::query()
            ->where('estado_trash', '1')
            ->whereHas('tipoComprobanteSunat', function ($subQuery) {
                $subQuery
                    ->where('estado_trash', '1')
                    ->whereIn('codigo', self::TIPOS_DOCUMENTO_PERMITIDOS);
            });

        $tiposDocumento = SunatC01TipoComprobante::query()
            ->where('estado_trash', '1')
            ->whereIn('codigo', self::TIPOS_DOCUMENTO_PERMITIDOS)
            ->orderBy('codigo')
            ->get(['idsunat_c01_tipo_comprobante', 'codigo', 'nombre', 'abreviatura'])
            ->map(fn (SunatC01TipoComprobante $tipo) => [
                'id' => (int) $tipo->idsunat_c01_tipo_comprobante,
                'text' => trim(collect([$tipo->codigo, $tipo->abreviatura ?: $tipo->nombre])->filter()->implode(' - ')),
            ])
            ->values();

        $estados = (clone $baseDocumentos)
            ->whereNotNull('sunat_estado')
            ->select('sunat_estado')
            ->distinct()
            ->orderBy('sunat_estado')
            ->pluck('sunat_estado')
            ->map(fn ($estado) => trim((string) $estado))
            ->filter(fn ($estado) => filled($estado))
            ->unique(fn ($estado) => mb_strtoupper($estado))
            ->map(fn ($estado) => [
                'id' => $estado,
                'text' => $estado,
            ])
            ->values();

        $anios = (clone $baseDocumentos)
            ->whereNotNull('fecha_emision')
            ->selectRaw('YEAR(fecha_emision) as anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn ($anio) => (int) $anio)
            ->filter(fn ($anio) => $anio >= 2000)
            ->map(fn ($anio) => [
                'id' => $anio,
                'text' => (string) $anio,
            ])
            ->values();

        return ApiResponse::success([
            'tipos_documento' => $tiposDocumento,
            'estados' => $estados,
            'anios' => $anios,
        ]);
    }

    public function clientes(Request $request)
    {
        $searchRaw = $request->input('search', $request->input('term', ''));
        if (is_array($searchRaw)) {
            $searchRaw = $searchRaw['value'] ?? '';
        }

        $search = trim((string) ($searchRaw ?? ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(10, min((int) $request->input('per_page', 20), 50));

        $documentosPermitidos = RDocumento::query()
            ->select('idpersona_cliente')
            ->where('estado_trash', '1')
            ->whereNotNull('idpersona_cliente')
            ->whereHas('tipoComprobanteSunat', function ($subQuery) {
                $subQuery
                    ->where('estado_trash', '1')
                    ->whereIn('codigo', self::TIPOS_DOCUMENTO_PERMITIDOS);
            });
        $this->aplicarFiltroFechas($documentosPermitidos, $request);

        $query = Persona::query()
            ->with('docIdentidad:idsunat_c06_doc_identidad,abreviatura')
            ->whereIn('idpersona', $documentosPermitidos);

        if ($search !== '') {
            $terminos = collect(preg_split('/\s+/', $search))
                ->filter(fn ($item) => filled($item))
                ->map(fn ($item) => trim((string) $item))
                ->take(8)
                ->values();

            if ($terminos->isEmpty()) {
                $terminos = collect([$search]);
            }

            foreach ($terminos as $termino) {
                $query->where(function ($subQuery) use ($termino) {
                    $subQuery
                        ->where('descripcion', 'like', "%{$termino}%")
                        ->orWhere('nombre_comercial', 'like', "%{$termino}%")
                        ->orWhere('nombre_persona_natural', 'like', "%{$termino}%")
                        ->orWhere('apellido_paterno_persona_natural', 'like', "%{$termino}%")
                        ->orWhere('apellido_materno_persona_natural', 'like', "%{$termino}%")
                        ->orWhere('numero_documento', 'like', "%{$termino}%");
                });
            }
        }

        $total = (clone $query)->count('idpersona');
        $offset = ($page - 1) * $perPage;

        $clientes = $query
            ->orderByDesc('idpersona')
            ->skip($offset)
            ->take($perPage)
            ->get([
                'idpersona',
                'descripcion',
                'nombre_comercial',
                'nombre_persona_natural',
                'apellido_paterno_persona_natural',
                'apellido_materno_persona_natural',
                'tipo_documento',
                'numero_documento',
            ])
            ->map(function (Persona $cliente) {
                $nombre = $this->resolverNombrePersona($cliente);
                $documento = $this->resolverDocumentoPersona($cliente);

                return [
                    'id' => (int) $cliente->idpersona,
                    'text' => trim($nombre . ($documento !== '' ? " - {$documento}" : '')),
                ];
            })
            ->values();

        return ApiResponse::success([
            'items' => $clientes,
            'pagination' => [
                'more' => ($offset + $clientes->count()) < $total,
            ],
            'page' => $page,
            'total' => $total,
        ]);
    }

    public function clientesFactura(Request $request)
    {
        $searchRaw = $request->input('search', $request->input('term', ''));
        if (is_array($searchRaw)) {
            $searchRaw = $searchRaw['value'] ?? '';
        }

        $search = trim((string) ($searchRaw ?? ''));
        $idTipoDocumento = (int) $request->input('idsunat_c01_tipo_comprobante', 0);
        $tipoDocumento = $idTipoDocumento > 0
            ? SunatC01TipoComprobante::query()
                ->where('estado_trash', '1')
                ->whereIn('codigo', ['01', '03', '12'])
                ->find($idTipoDocumento)
            : null;
        $tipoDocumentoCodigo = trim((string) ($tipoDocumento?->codigo ?? '01'));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(10, min((int) $request->input('per_page', 20), 50));

        $query = Persona::query()
            ->with('docIdentidad:idsunat_c06_doc_identidad,code_sunat,abreviatura')
            ->where('estado_trash', '1');

        if ($tipoDocumentoCodigo === '01') {
            $query
                ->whereHas('docIdentidad', fn ($docQuery) => $docQuery->where('code_sunat', '6'))
                ->whereRaw("CHAR_LENGTH(TRIM(COALESCE(numero_documento, ''))) = 11");
        }

        if ($search !== '') {
            $terminos = collect(preg_split('/\s+/', $search))
                ->filter(fn ($item) => filled($item))
                ->map(fn ($item) => trim((string) $item))
                ->take(8)
                ->values();

            foreach ($terminos as $termino) {
                $query->where(function ($subQuery) use ($termino) {
                    $subQuery
                        ->where('descripcion', 'like', "%{$termino}%")
                        ->orWhere('nombre_comercial', 'like', "%{$termino}%")
                        ->orWhere('nombre_persona_natural', 'like', "%{$termino}%")
                        ->orWhere('apellido_paterno_persona_natural', 'like', "%{$termino}%")
                        ->orWhere('apellido_materno_persona_natural', 'like', "%{$termino}%")
                        ->orWhere('numero_documento', 'like', "%{$termino}%");
                });
            }
        }

        $total = (clone $query)->count('idpersona');
        $offset = ($page - 1) * $perPage;

        $clientes = $query
            ->orderByDesc('idpersona')
            ->skip($offset)
            ->take($perPage)
            ->get([
                'idpersona',
                'tipo_documento',
                'descripcion',
                'nombre_comercial',
                'nombre_persona_natural',
                'apellido_paterno_persona_natural',
                'apellido_materno_persona_natural',
                'numero_documento',
            ])
            ->map(function (Persona $cliente) {
                $nombre = $this->resolverNombrePersona($cliente);
                $documento = trim((string) ($cliente->numero_documento ?? ''));
                $abreviatura = trim((string) ($cliente->docIdentidad?->abreviatura ?? ''));
                $documentoTexto = $this->resolverDocumentoPersona($cliente);

                return [
                    'id' => (int) $cliente->idpersona,
                    'text' => trim($nombre . ($documentoTexto !== '' ? " - {$documentoTexto}" : '')),
                    'numero_documento' => $documento,
                    'documento_abreviatura' => $abreviatura,
                ];
            })
            ->values();

        return ApiResponse::success([
            'items' => $clientes,
            'pagination' => [
                'more' => ($offset + $clientes->count()) < $total,
            ],
        ]);
    }

    public function storeClienteRapido(Request $request)
    {
        $validated = $request->validate([
            'tipo_persona_sunat' => ['required', Rule::in(['NATURAL', 'JURIDICA'])],
            'codigo_documento_sunat' => ['required', Rule::in(['1', '6'])],
            'numero_documento' => ['required', 'string', 'max:20', Rule::unique('persona', 'numero_documento')],
            'descripcion' => ['required', 'string', 'max:255'],
            'celular' => ['nullable', 'string', 'max:15'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['codigo_documento_sunat'] === '6' && strlen(trim($validated['numero_documento'])) !== 11) {
            return ApiResponse::fail('El RUC debe tener 11 digitos.', 422);
        }

        if ($validated['codigo_documento_sunat'] === '1' && strlen(trim($validated['numero_documento'])) !== 8) {
            return ApiResponse::fail('El DNI debe tener 8 digitos.', 422);
        }

        $tipoDocumento = SunatC06DocIdentidad::query()
            ->where('estado_trash', '1')
            ->where('code_sunat', $validated['codigo_documento_sunat'])
            ->first();

        if (! $tipoDocumento) {
            return ApiResponse::fail('No se encontro el tipo de documento SUNAT configurado.', 422);
        }

        $tipoCliente = PersonaTipo::query()
            ->where('estado_trash', '1')
            ->where('nombre', 'CLIENTE')
            ->first();

        if (! $tipoCliente) {
            return ApiResponse::fail('No se encontro el tipo de persona CLIENTE configurado.', 422);
        }

        $persona = DB::transaction(function () use ($validated, $tipoDocumento, $tipoCliente, $request) {
            $persona = Persona::create([
                'tipo_persona_sunat' => $validated['tipo_persona_sunat'],
                'tipo_documento' => $tipoDocumento->idsunat_c06_doc_identidad,
                'numero_documento' => trim($validated['numero_documento']),
                'descripcion' => trim($validated['descripcion']),
                'celular' => $validated['celular'] ?? null,
                'correo' => $validated['correo'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'estado_trash' => '1',
                'user_trash' => null,
                'user_created' => $request->user()?->id,
                'user_updated' => $request->user()?->id,
            ]);

            PersonaTipoPersona::create([
                'idpersona' => $persona->idpersona,
                'idpersona_tipo' => $tipoCliente->idpersona_tipo,
                'user_created' => $request->user()?->id,
                'user_updated' => $request->user()?->id,
            ]);

            return $persona->load('docIdentidad:idsunat_c06_doc_identidad,abreviatura');
        });

        $nombre = $this->resolverNombrePersona($persona);
        $documento = $this->resolverDocumentoPersona($persona);

        return ApiResponse::success([
            'id' => (int) $persona->idpersona,
            'text' => trim($nombre . ($documento !== '' ? " - {$documento}" : '')),
        ], 'Cliente registrado correctamente.');
    }

    public function catalogosCreacion(Request $request)
    {
        $idUsuario = (int) $request->user()->id;
        $igvEmpresa = $this->obtenerIgvEmpresaConfigurado();
        $tiposDocumento = SunatC01TipoComprobante::query()
            ->where('estado_trash', '1')
            ->whereIn('codigo', ['01', '03', '12'])
            ->orderBy('codigo')
            ->get(['idsunat_c01_tipo_comprobante', 'codigo', 'nombre', 'abreviatura'])
            ->map(fn (SunatC01TipoComprobante $tipo) => [
                'id' => (int) $tipo->idsunat_c01_tipo_comprobante,
                'codigo' => (string) $tipo->codigo,
                'nombre' => (string) $tipo->nombre,
                'abreviatura' => (string) ($tipo->abreviatura ?: $tipo->nombre),
                'text' => trim(collect([$tipo->codigo, $tipo->abreviatura ?: $tipo->nombre])->filter()->implode(' - ')),
            ])
            ->values();

        $series = SerieComprobante::query()
            ->from('serie_comprobante as sc')
            ->join('sunat_c01_tipo_comprobante as tc', 'tc.idsunat_c01_tipo_comprobante', '=', 'sc.idsunat_c01_tipo_comprobante')
            ->join('permiso_usuario_serie as pus', function ($join) use ($idUsuario) {
                $join
                    ->on('pus.idserie_comprobante', '=', 'sc.idserie_comprobante')
                    ->where('pus.idusers', '=', $idUsuario);
            })
            ->where('sc.estado_trash', '1')
            ->whereIn('tc.codigo', ['01', '03', '12'])
            ->orderByDesc('sc.predeterminado')
            ->orderBy('tc.codigo')
            ->orderBy('sc.serie')
            ->get([
                'sc.idserie_comprobante',
                'sc.idsunat_c01_tipo_comprobante',
                'sc.serie',
                'sc.numero',
                'sc.predeterminado',
            ])
            ->map(fn (SerieComprobante $serie) => [
                'idserie_comprobante' => (int) $serie->idserie_comprobante,
                'idsunat_c01_tipo_comprobante' => (int) $serie->idsunat_c01_tipo_comprobante,
                'serie' => (string) $serie->serie,
                'numero' => (int) ($serie->numero ?? 0),
                'siguiente' => ((int) ($serie->numero ?? 0)) + 1,
                'predeterminado' => (string) ($serie->predeterminado ?? '0'),
            ])
            ->values();

        $motivosNotaCredito = SunatC09CodigoNotaCredito::query()
            ->where('estado_trash', '1')
            ->orderBy('codigo')
            ->get(['idsunat_c09_codigo_nota_credito', 'codigo', 'nombre'])
            ->map(fn (SunatC09CodigoNotaCredito $motivo) => [
                'id' => (int) $motivo->idsunat_c09_codigo_nota_credito,
                'codigo' => (string) $motivo->codigo,
                'text' => trim("{$motivo->codigo} - {$motivo->nombre}"),
            ])
            ->values();

        $seriesNotaCredito = SerieComprobante::query()
            ->from('serie_comprobante as sc')
            ->join('sunat_c01_tipo_comprobante as tc', 'tc.idsunat_c01_tipo_comprobante', '=', 'sc.idsunat_c01_tipo_comprobante')
            ->join('sunat_c01_tipo_comprobante as tca', 'tca.idsunat_c01_tipo_comprobante', '=', 'sc.tipo_comprobante_adicional')
            ->join('permiso_usuario_serie as pus', function ($join) use ($idUsuario) {
                $join
                    ->on('pus.idserie_comprobante', '=', 'sc.idserie_comprobante')
                    ->where('pus.idusers', '=', $idUsuario);
            })
            ->where('sc.estado_trash', '1')
            ->where('tc.estado_trash', '1')
            ->where('tc.codigo', '07')
            ->where('tca.estado_trash', '1')
            ->whereIn('tca.codigo', ['01', '03'])
            ->orderByDesc('sc.predeterminado')
            ->orderBy('sc.serie')
            ->get(['sc.idserie_comprobante', 'sc.serie', 'sc.numero', 'sc.predeterminado', 'tca.codigo as tipo_comprobante_adicional_codigo'])
            ->map(fn (SerieComprobante $serie) => [
                'id' => (int) $serie->idserie_comprobante,
                'serie' => (string) $serie->serie,
                'text' => "{$serie->serie} (sig: " . (((int) ($serie->numero ?? 0)) + 1) . ')',
                'predeterminado' => (string) ($serie->predeterminado ?? '0'),
                'tipo_comprobante_adicional_codigo' => (string) $serie->tipo_comprobante_adicional_codigo,
            ])
            ->values();

        $cuentasBancarias = CuentaBancaria::query()
            ->from('cuenta_bancaria as cb')
            ->leftJoin('banco as b', 'b.idbanco', '=', 'cb.idbanco')
            ->where('cb.estado_trash', '1')
            ->orderBy('b.nombre')
            ->orderBy('cb.idcuenta_bancaria')
            ->get([
                'cb.idcuenta_bancaria',
                'cb.cta_cte',
                'cb.cci',
                'cb.moneda',
                'b.nombre as banco_nombre',
                'b.alias as banco_alias',
            ])
            ->map(function ($cuenta) {
                $nombre = trim((string) ($cuenta->banco_alias ?: $cuenta->banco_nombre ?: 'Cuenta'));
                $numero = trim((string) ($cuenta->cta_cte ?: $cuenta->cci ?: 'Sin numero'));
                $moneda = strtoupper(trim((string) ($cuenta->moneda ?? '')));

                return [
                    'idcuenta_bancaria' => (int) $cuenta->idcuenta_bancaria,
                    'nombre' => $nombre,
                    'text' => trim("{$nombre} - {$numero}" . ($moneda !== '' ? " ({$moneda})" : '')),
                ];
            })
            ->values();

        return ApiResponse::success([
            'tipos_documento' => $tiposDocumento,
            'igv' => $igvEmpresa,
            'series' => $series,
            'motivos_nota_credito' => $motivosNotaCredito,
            'series_nota_credito' => $seriesNotaCredito,
            'cuentas_bancarias' => $cuentasBancarias,
        ]);
    }

    public function productos(Request $request)
    {
        $searchRaw = $request->input('search.value');
        if ($searchRaw === null) {
            $searchRaw = $request->input('search', $request->input('term', ''));
        }
        if (is_array($searchRaw)) {
            $searchRaw = $searchRaw['value'] ?? '';
        }

        $search = trim((string) ($searchRaw ?? ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(10, min((int) $request->input('per_page', $request->input('limit', 20)), 200));

        $query = Producto::query()
            ->where('estado_trash', '1')
            ->orderBy('nombre');
        $queryBaseSinBusqueda = clone $query;

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('codigo', 'like', "%{$search}%")
                    ->orWhere('codigo_alterno', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion_adicional', 'like', "%{$search}%");
            });
        }

        $normalizarProductos = function ($productos) {
            return $productos
                ->map(function (Producto $producto) {
                    $codigo = trim((string) ($producto->codigo_alterno ?: $producto->codigo));
                    $nombre = trim((string) $producto->nombre);

                    return [
                        'id' => (int) $producto->idproducto,
                        'idproducto' => (int) $producto->idproducto,
                        'text' => trim(($codigo !== '' ? "{$codigo} - " : '') . $nombre),
                        'codigo' => $codigo,
                        'nombre' => $nombre,
                        'precio_venta' => round((float) ($producto->precio_venta ?? 0), 2),
                    ];
                })
                ->values();
        };

        if ($request->has('draw')) {
            $draw = (int) $request->input('draw', 1);
            $start = max((int) $request->input('start', 0), 0);
            $length = (int) $request->input('length', 10);
            $length = $length === -1 ? -1 : max(1, min($length, 200));

            $recordsTotal = (clone $queryBaseSinBusqueda)->count('idproducto');
            $recordsFiltered = (clone $query)->count('idproducto');

            $column = (int) $request->input('order.0.column', 2);
            $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

            $query->reorder();
            match ($column) {
                1 => $query->orderBy('codigo', $direction),
                3 => $query->orderBy('precio_venta', $direction),
                default => $query->orderBy('nombre', $direction),
            };

            $productos = $length === -1
                ? $query->get(['idproducto', 'codigo', 'codigo_alterno', 'nombre', 'precio_venta'])
                : $query->skip($start)->take($length)->get(['idproducto', 'codigo', 'codigo_alterno', 'nombre', 'precio_venta']);

            return response()->json([
                'status' => true,
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $normalizarProductos($productos),
            ]);
        }

        $total = (clone $query)->count('idproducto');
        $offset = ($page - 1) * $perPage;

        $productos = $query
            ->skip($offset)
            ->take($perPage)
            ->get(['idproducto', 'codigo', 'codigo_alterno', 'nombre', 'precio_venta']);
        $productos = $normalizarProductos($productos);

        return ApiResponse::success([
            'items' => $productos,
            'pagination' => [
                'more' => ($offset + $productos->count()) < $total,
            ],
        ]);
    }

    public function storeFactura(Request $request)
    {
        $idTipoComprobante = (int) $request->input('idsunat_c01_tipo_comprobante', 0);
        $tipoComprobante = SunatC01TipoComprobante::query()
            ->where('estado_trash', '1')
            ->whereIn('codigo', ['01', '03', '12'])
            ->find($idTipoComprobante);

        if (! $tipoComprobante) {
            return ApiResponse::fail('Seleccione un tipo de comprobante valido.', 422);
        }

        $codigoTipoDocumento = trim((string) $tipoComprobante->codigo);
        if (! in_array($codigoTipoDocumento, ['01', '03', '12'], true)) {
            return ApiResponse::fail('Solo se permite crear NOTA DE VENTA (12), FACTURA (01) y BOLETA (03) desde este formulario.', 422);
        }

        $idUsuario = (int) $request->user()->id;
        $igvEmpresa = $this->obtenerIgvEmpresaConfigurado();
        $validated = $this->validarFactura($request, (int) $tipoComprobante->idsunat_c01_tipo_comprobante, $codigoTipoDocumento);
        $archivos = $request->file('documentos', []);

        return DB::transaction(function () use ($validated, $tipoComprobante, $idUsuario, $igvEmpresa, $codigoTipoDocumento, $archivos) {
            $cliente = Persona::query()
                ->with('docIdentidad:idsunat_c06_doc_identidad,code_sunat')
                ->where('estado_trash', '1')
                ->findOrFail((int) $validated['idpersona_cliente']);

            if ($codigoTipoDocumento === '01' && trim((string) ($cliente->docIdentidad?->code_sunat ?? '')) !== '6') {
                return ApiResponse::fail('Para emitir FACTURA (01), el cliente debe tener documento RUC.', 422);
            }

            if ($codigoTipoDocumento === '01' && ! preg_match('/^\d{11}$/', trim((string) ($cliente->numero_documento ?? '')))) {
                return ApiResponse::fail('Para FACTURA (01), el cliente debe tener un numero de RUC de 11 digitos.', 422);
            }

            $serie = $this->obtenerSeriePermitida(
                (int) $validated['idserie_comprobante'],
                $idUsuario,
                (int) $tipoComprobante->idsunat_c01_tipo_comprobante,
                true
            );
            $detalleCalculado = $this->calcularDetalleYTotales(
                $validated['detalles'],
                $igvEmpresa,
                (string) ($tipoComprobante->abreviatura ?: $tipoComprobante->nombre ?: $tipoComprobante->codigo)
            );
            $metodosPago = $this->normalizarMetodosPago($validated['metodos_pago'] ?? [], (float) $detalleCalculado['total']);
            $numeroSiguiente = ((int) ($serie->numero ?? 0)) + 1;

            $serie->update([
                'numero' => $numeroSiguiente,
                'user_updated' => $idUsuario,
            ]);

            $ahora = now();
            $fechaEmision = Carbon::parse($validated['fecha_emision'])->setTimeFrom($ahora);

            $documento = RDocumento::create([
                'idpersona_cliente' => (int) $validated['idpersona_cliente'],
                'origen' => 'facturacion',
                'crear_enviar_sunat' => 'NO',
                'idsunat_c01' => (int) $tipoComprobante->idsunat_c01_tipo_comprobante,
                'tipo_comprobante' => (string) $tipoComprobante->codigo,
                'serie_comprobante' => (string) $serie->serie,
                'numero_comprobante' => (string) $numeroSiguiente,
                'fecha_emision' => $fechaEmision,
                'impuesto' => $detalleCalculado['impuesto'],
                'venta_subtotal' => $detalleCalculado['subtotal'],
                'venta_descuento' => $detalleCalculado['descuento'],
                'venta_igv' => $detalleCalculado['igv'],
                'venta_total' => $detalleCalculado['total'],
                'venta_cuotas' => 'contado',
                'vc_cantidad_total' => 0,
                'vc_cantidad_pagada' => 0,
                'vc_estado' => 'pagado',
                'total_recibido' => $detalleCalculado['total'],
                'total_vuelto' => 0,
                'sunat_estado' => $this->estadoSunatInicial($codigoTipoDocumento),
                'observacion_documento' => $validated['observacion_documento'] ?? null,
                'estado_trash' => '1',
                'user_trash' => null,
                'user_created' => $idUsuario,
                'user_updated' => $idUsuario,
            ]);

            $documento->detalles()->createMany($detalleCalculado['detalles']);
            $this->registrarMetodosPago($documento, $metodosPago, $idUsuario);
            $this->registrarArchivosFacturacion(
                $documento,
                is_array($archivos) ? $archivos : [$archivos],
                $idUsuario,
                is_array($validated['documentos_nombres'] ?? null) ? $validated['documentos_nombres'] : []
            );

            return ApiResponse::success(
                $documento->fresh()->load(['cliente.docIdentidad', 'tipoComprobanteSunat', 'detalles']),
                match ($codigoTipoDocumento) {
                    '12' => 'Nota de venta registrada correctamente.',
                    '01' => 'Factura registrada correctamente.',
                    default => 'Boleta registrada correctamente.',
                }
            );
        });
    }

    public function actualizarComprobante(Request $request, RDocumento $documento)
    {
        $documento->loadMissing('tipoComprobanteSunat');
        $codigoTipoDocumento = trim((string) ($documento->tipoComprobanteSunat?->codigo ?: $documento->tipo_comprobante));
        $esTicket = $codigoTipoDocumento === '12';
        $esDocumentoLegal = in_array($codigoTipoDocumento, ['01', '03', '07', '08'], true);

        if (! $esTicket && ! $esDocumentoLegal) {
            return ApiResponse::fail('El tipo de comprobante no se puede editar desde esta opcion.', 422);
        }

        if ((string) $documento->estado_trash !== '1') {
            return ApiResponse::fail('El comprobante no se encuentra activo.', 422);
        }

        if ($esDocumentoLegal && strtoupper(trim((string) $documento->sunat_estado)) === 'ACEPTADA') {
            return ApiResponse::fail('El comprobante ya fue aceptado por SUNAT y no se puede editar.', 422);
        }

        $idTipoComprobante = (int) $request->input('idsunat_c01_tipo_comprobante', 0);
        $tipoComprobante = SunatC01TipoComprobante::query()
            ->where('estado_trash', '1')
            ->whereIn('codigo', $esTicket ? ['01', '03', '12'] : [$codigoTipoDocumento])
            ->find($idTipoComprobante);

        if (! $tipoComprobante) {
            return ApiResponse::fail(
                $esTicket
                    ? 'Seleccione un tipo de comprobante valido para editar el ticket.'
                    : 'El tipo de comprobante legal no se puede modificar.',
                422
            );
        }

        $idUsuario = (int) $request->user()->id;
        $igvEmpresa = $this->obtenerIgvEmpresaConfigurado();
        $codigoTipoDocumentoEditado = trim((string) $tipoComprobante->codigo);
        $validated = $this->validarFactura(
            $request,
            $idTipoComprobante,
            $codigoTipoDocumentoEditado,
            $esTicket ? ['01', '03', '12'] : [$codigoTipoDocumento],
            $esTicket,
            $esTicket
        );
        $archivos = $request->file('documentos', []);

        return DB::transaction(function () use ($validated, $documento, $idUsuario, $igvEmpresa, $tipoComprobante, $codigoTipoDocumento, $codigoTipoDocumentoEditado, $esTicket, $esDocumentoLegal, $archivos) {
            $original = RDocumento::query()
                ->lockForUpdate()
                ->findOrFail($documento->getKey());

            $fechaOriginal = Carbon::parse($original->fecha_emision);

            if ($esDocumentoLegal) {
                $serieOriginal = SerieComprobante::query()
                    ->where('idsunat_c01_tipo_comprobante', (int) $original->idsunat_c01)
                    ->where('serie', trim((string) $original->serie_comprobante))
                    ->first();

                if (! $serieOriginal) {
                    return ApiResponse::fail('No se pudo validar la serie original del comprobante legal.', 422);
                }

                if (
                    (int) $validated['idsunat_c01_tipo_comprobante'] !== (int) $original->idsunat_c01
                    || (int) $validated['idserie_comprobante'] !== (int) $serieOriginal->idserie_comprobante
                    || Carbon::parse($validated['fecha_emision'])->toDateString() !== $fechaOriginal->toDateString()
                ) {
                    return ApiResponse::fail('En un comprobante legal no se permite modificar tipo, serie ni fecha de emision.', 422);
                }
            }

            $cliente = Persona::query()
                ->with('docIdentidad:idsunat_c06_doc_identidad,code_sunat')
                ->where('estado_trash', '1')
                ->findOrFail((int) $validated['idpersona_cliente']);

            if ($codigoTipoDocumentoEditado === '01' && trim((string) ($cliente->docIdentidad?->code_sunat ?? '')) !== '6') {
                return ApiResponse::fail('Para emitir FACTURA (01), el cliente debe tener documento RUC.', 422);
            }

            if ($codigoTipoDocumentoEditado === '01' && ! preg_match('/^\d{11}$/', trim((string) ($cliente->numero_documento ?? '')))) {
                return ApiResponse::fail('Para FACTURA (01), el cliente debe tener un numero de RUC de 11 digitos.', 422);
            }

            $serie = $esTicket
                ? $this->obtenerSeriePermitida(
                    (int) $validated['idserie_comprobante'],
                    $idUsuario,
                    (int) $tipoComprobante->idsunat_c01_tipo_comprobante,
                    true
                )
                : $serieOriginal;
            $cambioSerie = $esTicket && (
                (int) $original->idsunat_c01 !== (int) $tipoComprobante->idsunat_c01_tipo_comprobante
                || trim((string) $serie->serie) !== trim((string) $original->serie_comprobante)
            );
            $numeroComprobante = (string) $original->numero_comprobante;

            if ($cambioSerie) {
                $numeroComprobante = (string) (((int) ($serie->numero ?? 0)) + 1);
                $serie->update([
                    'numero' => (int) $numeroComprobante,
                    'user_updated' => $idUsuario,
                ]);
            }

            $detalleCalculado = $this->calcularDetalleYTotales(
                $validated['detalles'],
                $igvEmpresa,
                (string) ($tipoComprobante->abreviatura ?: $tipoComprobante->nombre ?: $codigoTipoDocumentoEditado)
            );
            $metodosPago = $this->normalizarMetodosPago($validated['metodos_pago'] ?? [], (float) $detalleCalculado['total']);
            $ahora = now();

            $original->update([
                'idpersona_cliente' => (int) $validated['idpersona_cliente'],
                'idsunat_c01' => (int) $tipoComprobante->idsunat_c01_tipo_comprobante,
                'tipo_comprobante' => $codigoTipoDocumentoEditado,
                'serie_comprobante' => (string) $serie->serie,
                'numero_comprobante' => $numeroComprobante,
                'fecha_emision' => $esTicket
                    ? Carbon::parse($validated['fecha_emision'])->setTimeFrom($ahora)
                    : $fechaOriginal,
                'impuesto' => $detalleCalculado['impuesto'],
                'venta_subtotal' => $detalleCalculado['subtotal'],
                'venta_descuento' => $detalleCalculado['descuento'],
                'venta_igv' => $detalleCalculado['igv'],
                'venta_total' => $detalleCalculado['total'],
                'total_recibido' => $detalleCalculado['total'],
                'sunat_estado' => $esTicket
                    ? $this->estadoSunatInicial($codigoTipoDocumentoEditado)
                    : 'POR ENVIAR',
                'sunat_observacion' => null,
                'sunat_code' => null,
                'sunat_mensaje' => null,
                'sunat_error' => null,
                'observacion_documento' => $validated['observacion_documento'] ?? null,
                'user_updated' => $idUsuario,
            ]);

            $this->sincronizarDetallesDocumento($original, $detalleCalculado['detalles'], $idUsuario);
            $this->registrarMetodosPago($original, $metodosPago, $idUsuario);
            $this->eliminarArchivosFacturacion($original, $validated['documentos_eliminar'] ?? [], $idUsuario);
            $this->registrarArchivosFacturacion(
                $original,
                is_array($archivos) ? $archivos : [$archivos],
                $idUsuario,
                is_array($validated['documentos_nombres'] ?? null) ? $validated['documentos_nombres'] : []
            );

            return ApiResponse::success(
                $original->fresh()->load(['cliente.docIdentidad', 'tipoComprobanteSunat', 'detalles']),
                'Comprobante actualizado con estado POR ENVIAR.'
            );
        });
    }

    public function listar(Request $request)
    {
        $resolverNombrePersona = fn (?Persona $persona): string => $this->resolverNombrePersona($persona);

        $normalizarListado = static function ($documentos) use ($resolverNombrePersona): void {
            $documentos->each(function (RDocumento $documento) use ($resolverNombrePersona) {
                $tipoComprobante = $documento->tipoComprobanteSunat;

                $documento->tipo_documento_codigo = trim((string) ($tipoComprobante?->codigo ?? ''));
                $documento->tipo_documento_nombre = trim((string) ($tipoComprobante?->nombre ?? ''));
                $documento->tipo_documento_abreviatura = trim((string) ($tipoComprobante?->abreviatura ?? ''));

                $documento->cliente_nombre = $resolverNombrePersona($documento->cliente);
                $documento->cliente_descripcion = trim((string) ($documento->cliente?->descripcion ?? '')) ?: $documento->cliente_nombre;
                $documento->cliente_documento = trim((string) ($documento->cliente?->numero_documento ?? ''));
                $documento->cliente_documento_abreviatura = trim((string) ($documento->cliente?->docIdentidad?->abreviatura ?? ''));

                $documento->comprobante = trim((($documento->serie_comprobante ?: '-') . '-' . ($documento->numero_comprobante ?: '-')));
                $documento->items_count = (int) ($documento->items_count ?? 0);
            });
        };

        $query = RDocumento::query()
            ->whereHas('tipoComprobanteSunat', function ($subQuery) {
                $subQuery
                    ->where('estado_trash', '1')
                    ->whereIn('codigo', self::TIPOS_DOCUMENTO_PERMITIDOS);
            })
            ->with([
                'tipoComprobanteSunat:idsunat_c01_tipo_comprobante,codigo,nombre,abreviatura',
                'cliente:idpersona,tipo_documento,descripcion,nombre_comercial,nombre_persona_natural,apellido_paterno_persona_natural,apellido_materno_persona_natural,numero_documento,sexo,foto_perfil',
                'cliente.docIdentidad:idsunat_c06_doc_identidad,abreviatura',
            ])
            ->withCount([
                'detalles as items_count' => function ($subQuery) {
                    $subQuery->whereNotNull('idproducto');
                },
            ])
            ->withExists([
                'relacionadoDeDocumentos as tiene_nota_credito' => function ($subQuery) {
                    $subQuery
                        ->whereHas('documento.tipoComprobanteSunat', fn ($query) => $query->where('codigo', '07'))
                        ->whereHas('documento', fn ($query) => $query
                            ->where('estado_trash', '1')
                            ->whereNotIn('sunat_estado', ['ERROR', 'RECHAZADA']));
                },
            ]);

        if (! $request->boolean('incluir_trash')) {
            $query->where('estado_trash', '1');
        }

        $this->aplicarFiltroFechas($query, $request);

        $idCliente = (int) $request->input('idpersona_cliente', 0);
        if ($idCliente > 0) {
            $query->where('idpersona_cliente', $idCliente);
        }

        $idsTipoDocumento = collect((array) $request->input('idsunat_c01', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($idsTipoDocumento->isNotEmpty()) {
            $query->whereIn('idsunat_c01', $idsTipoDocumento);
        }

        $estadosSunat = collect((array) $request->input('sunat_estado', []))
            ->map(fn ($estado) => mb_strtoupper(trim((string) $estado)))
            ->filter(fn ($estado) => filled($estado))
            ->unique()
            ->values();

        if ($estadosSunat->isNotEmpty()) {
            $query->where(function ($subQuery) use ($estadosSunat) {
                foreach ($estadosSunat as $estadoSunat) {
                    $subQuery->orWhereRaw('UPPER(TRIM(sunat_estado)) = ?', [$estadoSunat]);
                }
            });
        }

        if (! $request->has('draw')) {
            $documentos = $query->orderByDesc('idrdocumento')->get();
            $normalizarListado($documentos);
            return ApiResponse::success($documentos);
        }

        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length === -1 ? -1 : max(1, min($length, 200));
        $search = trim((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count('idrdocumento');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('serie_comprobante', 'like', "%{$search}%")
                    ->orWhere('numero_comprobante', 'like', "%{$search}%")
                    ->orWhere('sunat_estado', 'like', "%{$search}%")
                    ->orWhereHas('tipoComprobanteSunat', function ($tipoQuery) use ($search) {
                        $tipoQuery
                            ->where('codigo', 'like', "%{$search}%")
                            ->orWhere('nombre', 'like', "%{$search}%")
                            ->orWhere('abreviatura', 'like', "%{$search}%");
                    })
                    ->orWhereHas('cliente', function ($clienteQuery) use ($search) {
                        $clienteQuery
                            ->where('descripcion', 'like', "%{$search}%")
                            ->orWhere('nombre_comercial', 'like', "%{$search}%")
                            ->orWhere('nombre_persona_natural', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno_persona_natural', 'like', "%{$search}%")
                            ->orWhere('apellido_materno_persona_natural', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%");
                    });
            });
        }

        $recordsFiltered = (clone $query)->count('idrdocumento');

        $column = (int) $request->input('order.0.column', 1);
        $direction = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->reorder();
        match ($column) {
            1 => $query->orderBy('fecha_emision', $direction),
            2 => $query->orderBy(
                Persona::query()
                    ->selectRaw("COALESCE(NULLIF(TRIM(descripcion), ''), NULLIF(TRIM(nombre_comercial), ''), NULLIF(TRIM(CONCAT_WS(' ', nombre_persona_natural, apellido_paterno_persona_natural, apellido_materno_persona_natural)), ''), '')")
                    ->whereColumn('persona.idpersona', 'rdocumento.idpersona_cliente')
                    ->limit(1),
                $direction
            ),
            3 => $query
                ->orderBy('serie_comprobante', $direction)
                ->orderByRaw('CAST(numero_comprobante AS UNSIGNED) ' . $direction),
            4 => $query->orderBy('venta_total', $direction),
            6 => $query->orderBy('sunat_estado', $direction),
            default => $query,
        };
        $query->orderBy('idrdocumento', 'desc');

        $documentos = $length === -1
            ? $query->get()
            : $query->skip($start)->take($length)->get();

        $normalizarListado($documentos);

        return response()->json([
            'status' => true,
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $documentos,
        ]);
    }

    public function detalle(RDocumento $documento)
    {
        $documento->load([
            'tipoComprobanteSunat:idsunat_c01_tipo_comprobante,codigo,nombre,abreviatura',
            'cliente:idpersona,tipo_documento,descripcion,nombre_comercial,nombre_persona_natural,apellido_paterno_persona_natural,apellido_materno_persona_natural,numero_documento,sexo,foto_perfil',
            'cliente.docIdentidad:idsunat_c06_doc_identidad,abreviatura',
            'detalles:idrdocumento_detalle,idrdocumento,idproducto,descripcion,cantidad,precio_venta,precio_venta_descuento,descuento,descuento_porcentaje,subtotal',
            'detalles.producto:idproducto,codigo,nombre',
            'metodosPago:idrdocumento_metodo_pago,idrdocumento,idcuenta_bancaria,monto,codigo_voucher',
            'metodosPago.cuentaBancaria:idcuenta_bancaria,idbanco,cta_cte,cci,moneda',
            'metodosPago.cuentaBancaria.banco:idbanco,nombre,alias',
            'archivos' => fn ($query) => $query
                ->where('estado_trash', '1')
                ->select('idrdocumento_archivo', 'idrdocumento', 'nombre_original', 'nombre_guardado', 'nombre_visible', 'extension', 'tipo_mime', 'peso_bytes', 'estado_trash'),
        ]);

        $tipo = $documento->tipoComprobanteSunat;
        $documento->tipo_documento_codigo = trim((string) ($tipo?->codigo ?? ''));
        $documento->tipo_documento_nombre = trim((string) ($tipo?->nombre ?? ''));
        $documento->tipo_documento_abreviatura = trim((string) ($tipo?->abreviatura ?? ''));
        $documento->cliente_nombre = $this->resolverNombrePersona($documento->cliente);
        $documento->cliente_descripcion = trim((string) ($documento->cliente?->descripcion ?? '')) ?: $documento->cliente_nombre;
        $documento->cliente_documento = trim((string) ($documento->cliente?->numero_documento ?? ''));
        $documento->cliente_documento_abreviatura = trim((string) ($documento->cliente?->docIdentidad?->abreviatura ?? ''));
        $documento->cliente_sexo = strtoupper(trim((string) ($documento->cliente?->sexo ?? '')));
        $documento->cliente_foto_perfil = trim((string) ($documento->cliente?->foto_perfil ?? ''));
        $documento->comprobante = trim((($documento->serie_comprobante ?: '-') . '-' . ($documento->numero_comprobante ?: '-')));
        $documento->idserie_comprobante_edicion = (int) (SerieComprobante::query()
            ->where('idsunat_c01_tipo_comprobante', (int) $documento->idsunat_c01)
            ->where('serie', trim((string) $documento->serie_comprobante))
            ->value('idserie_comprobante') ?? 0);
        $documento->detalle_documento = collect($documento->detalles)
            ->map(fn ($detalle) => [
                'codigo' => trim((string) ($detalle->producto?->codigo ?? '')),
                'descripcion' => trim((string) $detalle->descripcion) ?: trim((string) ($detalle->producto?->nombre ?? '')) ?: 'Item sin descripcion',
                'cantidad' => $detalle->cantidad,
                'subtotal' => $detalle->subtotal,
            ])
            ->values();
        $documento->productos_asignados = $documento->detalle_documento
            ->pluck('descripcion')
            ->filter()
            ->unique()
            ->values();

        return ApiResponse::success($documento);
    }

    public function imprimirDocumento(RDocumento $documento, string $formato)
    {
        $formato = Str::lower(trim($formato));
        if (! in_array($formato, ['a4', 'ticket', 'a4-pdf', 'ticket-pdf'], true)) {
            abort(404);
        }

        $data = $this->prepararDocumentoImpresionFacturacion($documento);
        $esPdf = Str::endsWith($formato, '-pdf');
        $vistaFormato = $esPdf ? Str::before($formato, '-pdf') : $formato;

        if ($esPdf) {
            $html = view("facturacion.impresion-{$vistaFormato}", [
                ...$data,
                'modoPdf' => true,
            ])->render();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper($vistaFormato === 'ticket' ? [0, 0, 226.77, 650] : 'A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $data['nombreArchivo'] . '.pdf"',
            ]);
        }

        return response()
            ->view("facturacion.impresion-{$vistaFormato}", [
                ...$data,
                'modoPdf' => false,
            ])
            ->header('X-Robots-Tag', 'noindex');
    }

    public function enviarSunat(RDocumento $documento, SunatFacturacionService $sunatFacturacionService)
    {
        try {
            if (strtoupper(trim((string) $documento->sunat_estado)) === 'ANULADO') {
                return ApiResponse::fail('El comprobante esta anulado y no puede enviarse a SUNAT.', 422);
            }

            $resultado = $sunatFacturacionService->enviar($documento);

            if (($resultado['estado'] ?? '') !== 'ACEPTADA') {
                return ApiResponse::fail(
                    (string) ($resultado['mensaje'] ?? 'SUNAT no acepto el comprobante.'),
                    422,
                    $resultado,
                    is_numeric($resultado['code'] ?? null) ? (int) $resultado['code'] : null
                );
            }

            return ApiResponse::success($resultado, 'Comprobante aceptado por SUNAT.');
        } catch (\RuntimeException $e) {
            return ApiResponse::fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return ApiResponse::error($e, 500);
        }
    }

    public function anularSunat(Request $request, RDocumento $documento)
    {
        $validated = $request->validate([
            'idsunat_c09' => [
                'required',
                'integer',
                Rule::exists('sunat_c09_codigo_nota_credito', 'idsunat_c09_codigo_nota_credito')
                    ->where('estado_trash', '1'),
            ],
            'idserie_comprobante' => ['required', 'integer'],
            'observacion_documento' => ['nullable', 'string', 'max:1000'],
        ], [
            'idsunat_c09.required' => 'Seleccione el motivo SUNAT de la nota de credito.',
            'idserie_comprobante.required' => 'Seleccione la serie de nota de credito.',
        ]);

        $notaCredito = DB::transaction(function () use ($validated, $documento, $request) {
            $original = RDocumento::query()
                ->with(['tipoComprobanteSunat', 'detalles'])
                ->lockForUpdate()
                ->findOrFail($documento->getKey());
            $codigoOriginal = trim((string) ($original->tipoComprobanteSunat?->codigo ?: $original->tipo_comprobante));

            if (! in_array($codigoOriginal, ['01', '03'], true)) {
                abort(response()->json([
                    'status' => false,
                    'message' => 'Solo se puede anular una factura (01) o boleta (03) mediante nota de credito.',
                ], 422));
            }

            if (strtoupper(trim((string) $original->sunat_estado)) !== 'ACEPTADA') {
                abort(response()->json([
                    'status' => false,
                    'message' => 'Solo se puede anular un comprobante aceptado por SUNAT.',
                ], 422));
            }

            $this->validarPlazoAnulacionNotaCredito($original);

            $yaTieneNota = RDocumento::query()
                ->where('estado_trash', '1')
                ->whereNotIn('sunat_estado', ['ERROR', 'RECHAZADA'])
                ->whereHas('tipoComprobanteSunat', fn ($query) => $query->where('codigo', '07'))
                ->whereHas('documentosRelacionados', fn ($query) => $query->where('idrdocumento_r', $original->getKey()))
                ->exists();

            if ($yaTieneNota) {
                abort(response()->json([
                    'status' => false,
                    'message' => 'El comprobante ya tiene una nota de credito registrada.',
                ], 422));
            }

            $tipoNotaCredito = SunatC01TipoComprobante::query()
                ->where('estado_trash', '1')
                ->where('codigo', '07')
                ->firstOrFail();
            $serie = $this->obtenerSeriePermitida(
                (int) $validated['idserie_comprobante'],
                (int) $request->user()->id,
                (int) $tipoNotaCredito->idsunat_c01_tipo_comprobante,
                true,
                (int) $original->idsunat_c01
            );
            $numeroSiguiente = ((int) ($serie->numero ?? 0)) + 1;
            $serie->update([
                'numero' => $numeroSiguiente,
                'user_updated' => (int) $request->user()->id,
            ]);

            $nota = RDocumento::create([
                'idpersona_cliente' => $original->idpersona_cliente,
                'origen' => 'facturacion',
                'crear_enviar_sunat' => 'NO',
                'idsunat_c01' => (int) $tipoNotaCredito->idsunat_c01_tipo_comprobante,
                'tipo_comprobante' => '07',
                'serie_comprobante' => (string) $serie->serie,
                'numero_comprobante' => (string) $numeroSiguiente,
                'fecha_emision' => now(),
                'impuesto' => $original->impuesto,
                'venta_subtotal' => $original->venta_subtotal,
                'venta_descuento' => $original->venta_descuento,
                'venta_igv' => $original->venta_igv,
                'venta_total' => $original->venta_total,
                'venta_cuotas' => 'contado',
                'vc_cantidad_total' => 0,
                'vc_cantidad_pagada' => 0,
                'vc_estado' => 'pagado',
                'total_recibido' => $original->venta_total,
                'total_vuelto' => 0,
                'idsunat_c09' => (int) $validated['idsunat_c09'],
                'nc_tipo_comprobante' => $codigoOriginal,
                'nc_serie_y_numero' => trim("{$original->serie_comprobante}-{$original->numero_comprobante}"),
                'sunat_estado' => $this->estadoSunatInicial('07'),
                'observacion_documento' => $validated['observacion_documento'] ?? null,
                'estado_trash' => '1',
                'user_created' => (int) $request->user()->id,
                'user_updated' => (int) $request->user()->id,
            ]);

            $nota->detalles()->createMany($original->detalles->map(fn ($detalle) => [
                'idlote' => $detalle->idlote,
                'idproducto' => $detalle->idproducto,
                'descripcion' => $detalle->descripcion,
                'v_tipo_comprobante' => 'NOTA DE CREDITO',
                'v_fecha_emision' => now(),
                'cantidad' => $detalle->cantidad,
                'precio_compra' => $detalle->precio_compra,
                'precio_venta' => $detalle->precio_venta,
                'precio_venta_descuento' => $detalle->precio_venta_descuento,
                'descuento' => $detalle->descuento,
                'descuento_porcentaje' => $detalle->descuento_porcentaje,
                'subtotal' => $detalle->subtotal,
                'subtotal_no_descuento' => $detalle->subtotal_no_descuento,
            ])->all());
            $nota->documentosRelacionados()->create(['idrdocumento_r' => $original->getKey()]);

            return $nota;
        });

        return ApiResponse::success([
            'idrdocumento' => (int) $notaCredito->getKey(),
            'estado' => (string) $notaCredito->sunat_estado,
            'comprobante' => trim("{$notaCredito->serie_comprobante}-{$notaCredito->numero_comprobante}"),
        ], 'Nota de credito registrada con estado POR ENVIAR.');
    }

    public function desactivarTicket(Request $request, RDocumento $documento)
    {
        $documento->loadMissing('tipoComprobanteSunat');
        $codigoDocumento = trim((string) ($documento->tipoComprobanteSunat?->codigo ?: $documento->tipo_comprobante));

        if ($codigoDocumento !== '12') {
            return ApiResponse::fail('Solo los tickets (12) se pueden desactivar directamente.', 422);
        }

        if ((string) $documento->estado_trash === '0') {
            return ApiResponse::fail('El ticket ya se encuentra desactivado.', 422);
        }

        $documento->update([
            'sunat_estado' => 'ANULADO',
            'estado_trash' => '0',
            'user_trash' => (int) $request->user()->id,
            'user_updated' => (int) $request->user()->id,
        ]);

        return ApiResponse::success(null, 'Ticket desactivado correctamente.');
    }

    public function actualizarNotaCredito(Request $request, RDocumento $documento)
    {
        $validated = $request->validate([
            'idsunat_c09' => [
                'required',
                'integer',
                Rule::exists('sunat_c09_codigo_nota_credito', 'idsunat_c09_codigo_nota_credito')
                    ->where('estado_trash', '1'),
            ],
            'idserie_comprobante' => ['required', 'integer'],
            'observacion_documento' => ['nullable', 'string', 'max:1000'],
        ], [
            'idsunat_c09.required' => 'Seleccione el motivo SUNAT de la nota de credito.',
            'idserie_comprobante.required' => 'Seleccione la serie de nota de credito.',
        ]);

        $documento->loadMissing('tipoComprobanteSunat');
        $codigoDocumento = trim((string) ($documento->tipoComprobanteSunat?->codigo ?: $documento->tipo_comprobante));

        if ($codigoDocumento !== '07') {
            return ApiResponse::fail('Solo se permite editar notas de credito (07) desde esta opcion.', 422);
        }

        if (strtoupper(trim((string) $documento->sunat_estado)) === 'ACEPTADA') {
            return ApiResponse::fail('La nota de credito ya fue aceptada por SUNAT y no se puede editar.', 422);
        }

        if ((string) $documento->estado_trash !== '1') {
            return ApiResponse::fail('La nota de credito no se encuentra activa.', 422);
        }

        DB::transaction(function () use ($validated, $documento, $request) {
            $nota = RDocumento::query()
                ->lockForUpdate()
                ->findOrFail($documento->getKey());

            if (strtoupper(trim((string) $nota->sunat_estado)) === 'ACEPTADA') {
                abort(response()->json([
                    'status' => false,
                    'message' => 'La nota de credito ya fue aceptada por SUNAT y no se puede editar.',
                ], 422));
            }

            $serieOriginal = SerieComprobante::query()
                ->where('idsunat_c01_tipo_comprobante', (int) $nota->idsunat_c01)
                ->where('serie', trim((string) $nota->serie_comprobante))
                ->first();

            if (! $serieOriginal || (int) $validated['idserie_comprobante'] !== (int) $serieOriginal->idserie_comprobante) {
                abort(response()->json([
                    'status' => false,
                    'message' => 'La serie de una nota de credito no se puede modificar.',
                ], 422));
            }

            $nota->update([
                'idsunat_c09' => (int) $validated['idsunat_c09'],
                'observacion_documento' => $validated['observacion_documento'] ?? null,
                'sunat_estado' => 'POR ENVIAR',
                'sunat_observacion' => null,
                'sunat_code' => null,
                'sunat_mensaje' => null,
                'sunat_error' => null,
                'user_updated' => (int) $request->user()->id,
            ]);
        });

        return ApiResponse::success(null, 'Nota de credito actualizada con estado POR ENVIAR.');
    }

    public function descargarArchivoSunat(RDocumento $documento, string $tipo)
    {
        $tipo = strtolower(trim($tipo));
        if (! in_array($tipo, ['xml', 'cdr'], true)) {
            abort(404);
        }

        $documento->loadMissing('tipoComprobanteSunat');

        $codigoTipo = trim((string) ($documento->tipoComprobanteSunat?->codigo ?: $documento->tipo_comprobante));
        if (! in_array($codigoTipo, ['01', '03', '07', '08'], true)) {
            abort(404, 'El comprobante no es un documento electronico descargable.');
        }

        $nombreBase = $this->nombreArchivoSunat($documento, $codigoTipo);
        $carpeta = $this->carpetaSunatDocumento($codigoTipo);
        $base = (string) config('sunat.paths.base');
        $nombreArchivo = $tipo === 'xml' ? "{$nombreBase}.xml" : "R-{$nombreBase}.zip";
        $path = $base . DIRECTORY_SEPARATOR . $carpeta . DIRECTORY_SEPARATOR . $tipo . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (! File::exists($path)) {
            abort(404, "No se encontro el archivo {$tipo} del comprobante.");
        }

        return response()->download($path, $nombreArchivo);
    }

    private function resolverNombrePersona(?Persona $persona): string
    {
        if (! $persona) {
            return 'Sin cliente';
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

    private function resolverDocumentoPersona(?Persona $persona): string
    {
        if (! $persona) {
            return '';
        }

        $documento = trim((string) ($persona->numero_documento ?? ''));
        if ($documento === '') {
            return '';
        }

        $abreviatura = trim((string) ($persona->docIdentidad?->abreviatura ?? ''));

        return trim(($abreviatura !== '' ? "{$abreviatura} " : '') . $documento);
    }

    private function prepararDocumentoImpresionFacturacion(RDocumento $documento): array
    {
        $documento->load([
            'tipoComprobanteSunat:idsunat_c01_tipo_comprobante,codigo,nombre,abreviatura',
            'cliente:idpersona,tipo_documento,descripcion,nombre_comercial,nombre_persona_natural,apellido_paterno_persona_natural,apellido_materno_persona_natural,numero_documento,direccion,correo,celular',
            'cliente.docIdentidad:idsunat_c06_doc_identidad,code_sunat,abreviatura',
            'detalles:idrdocumento_detalle,idrdocumento,idproducto,descripcion,cantidad,precio_venta,descuento,descuento_porcentaje,subtotal,subtotal_no_descuento',
            'detalles.producto:idproducto,codigo,nombre',
            'metodosPago:idrdocumento_metodo_pago,idrdocumento,idcuenta_bancaria,monto,codigo_voucher',
            'metodosPago.cuentaBancaria:idcuenta_bancaria,idbanco,cta_cte,cci,moneda',
            'metodosPago.cuentaBancaria.banco:idbanco,nombre,alias',
        ]);

        $empresa = Empresa::query()
            ->where('estado_trash', '1')
            ->latest('idempresa')
            ->first();

        $tipo = $documento->tipoComprobanteSunat;
        $codigoTipo = trim((string) ($tipo?->codigo ?: $documento->tipo_comprobante));
        $nombreTipo = $codigoTipo === '12'
            ? 'NOTA DE VENTA'
            : mb_strtoupper(trim((string) ($tipo?->nombre ?: $tipo?->abreviatura ?: 'COMPROBANTE')));
        $comprobante = trim("{$documento->serie_comprobante}-{$documento->numero_comprobante}");
        $fechaEmision = $documento->fecha_emision ? Carbon::parse($documento->fecha_emision) : null;
        $cliente = $documento->cliente;
        $total = (float) ($documento->venta_total ?? 0);
        $logoUrl = $this->resolverLogoEmpresaImpresion($empresa);
        $logoPath = public_path(ltrim((string) parse_url($logoUrl, PHP_URL_PATH), '/'));

        $detalles = $documento->detalles
            ->map(function ($detalle) {
                $cantidad = (float) ($detalle->cantidad ?? 0);
                $precio = (float) ($detalle->precio_venta ?? 0);
                $subtotal = (float) ($detalle->subtotal ?? ($cantidad * $precio));
                $subtotalNoDescuento = (float) ($detalle->subtotal_no_descuento ?? ($cantidad * $precio));

                return [
                    'codigo' => trim((string) ($detalle->producto?->codigo ?? '')),
                    'descripcion' => trim((string) $detalle->descripcion) ?: 'Item sin descripcion',
                    'unidad' => 'UNIDAD',
                    'cantidad' => $cantidad,
                    'precio_venta' => $precio,
                    'descuento' => (float) ($detalle->descuento ?? 0),
                    'subtotal' => $subtotal,
                    'subtotal_no_descuento' => $subtotalNoDescuento,
                ];
            })
            ->values();

        $metodosPago = $documento->metodosPago
            ->map(function ($metodo) {
                $cuenta = $metodo->cuentaBancaria;
                $banco = trim((string) ($cuenta?->banco?->alias ?: $cuenta?->banco?->nombre ?: 'Metodo de pago'));
                $numero = trim((string) ($cuenta?->cta_cte ?: $cuenta?->cci ?: ''));
                $voucher = trim((string) ($metodo->codigo_voucher ?? ''));

                return [
                    'nombre' => trim($banco . ($numero !== '' ? " - {$numero}" : '')),
                    'monto' => (float) ($metodo->monto ?? 0),
                    'voucher' => $voucher,
                ];
            })
            ->values();

        $qrTexto = implode('|', [
            trim((string) ($empresa?->numero_documento ?? '')),
            $codigoTipo,
            trim((string) $documento->serie_comprobante),
            trim((string) $documento->numero_comprobante),
            number_format((float) ($documento->venta_igv ?? 0), 2, '.', ''),
            number_format($total, 2, '.', ''),
            $fechaEmision?->format('Y-m-d') ?? '',
            trim((string) ($cliente?->docIdentidad?->code_sunat ?? '')),
            trim((string) ($cliente?->numero_documento ?? '')),
        ]);
        $nombreArchivo = Str::slug($nombreTipo . '-' . ($cliente?->numero_documento ?: 'cliente') . '-' . $comprobante, '-');

        return [
            'empresa' => $empresa,
            'documento' => $documento,
            'cliente' => $cliente,
            'detalles' => $detalles,
            'metodosPago' => $metodosPago,
            'logoUrl' => $logoUrl,
            'logoDataUri' => $this->imagenDataUri($logoPath),
            'nombreTipo' => $nombreTipo,
            'codigoTipo' => $codigoTipo,
            'comprobante' => $comprobante,
            'fechaEmision' => $fechaEmision,
            'fechaEmisionFormato' => $fechaEmision?->format('Y-m-d') ?? '',
            'fechaEmisionDmy' => $fechaEmision?->format('d/m/Y') ?? '',
            'fechaEmisionHora' => $fechaEmision?->format('h:i A') ?? '',
            'clienteNombre' => $this->resolverNombrePersona($cliente),
            'clienteDocumento' => $this->resolverDocumentoPersona($cliente),
            'totalEnLetras' => $this->resolverTotalEnLetrasImpresion($total),
            'qrTexto' => $qrTexto,
            'qrDataUri' => $this->generarQrDataUri($qrTexto, $comprobante),
            'nombreArchivo' => $nombreArchivo,
            'subtotalNoDescuento' => $detalles->sum('subtotal_no_descuento'),
            'usuarioCodigo' => 'ADM' . ((int) ($documento->user_created ?? 0)),
        ];
    }

    private function resolverLogoEmpresaImpresion(?Empresa $empresa): string
    {
        $logo = trim((string) ($empresa?->logo ?? ''));
        $candidatos = [
            $logo !== '' ? public_path("assets/modulo/empresa/logo/{$logo}") : null,
            public_path('assets/images/brand-logos/logo-raices-home-rectangulo.png'),
        ];

        foreach ($candidatos as $path) {
            if ($path && File::exists($path)) {
                return asset(Str::after(str_replace('\\', '/', $path), str_replace('\\', '/', public_path()) . '/'));
            }
        }

        return asset('assets/images/brand-logos/logo-raices-home-rectangulo.png');
    }

    private function resolverTotalEnLetrasImpresion(float $total): string
    {
        if (class_exists(NumeroALetras::class)) {
            return (new NumeroALetras())->toInvoice($total, 2, ' SOLES');
        }

        $totalCentimos = (int) round($total * 100);
        $entero = intdiv($totalCentimos, 100);
        $centimos = $totalCentimos % 100;

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('es_PE', \NumberFormatter::SPELLOUT);
            $letras = mb_strtoupper((string) $formatter->format($entero));

            return 'SON ' . $letras . ' CON ' . str_pad((string) $centimos, 2, '0', STR_PAD_LEFT) . '/100 SOLES';
        }

        return 'SON ' . number_format($total, 2, '.', '') . ' SOLES';
    }

    private function generarQrDataUri(string $texto, string $etiqueta): string
    {
        try {
            if (class_exists(QrCode::class) && class_exists(PngWriter::class)) {
                $qrCode = new QrCode(
                    data: $texto,
                    encoding: new Encoding('UTF-8'),
                    errorCorrectionLevel: ErrorCorrectionLevel::Low,
                    size: 600,
                    margin: 10,
                    roundBlockSizeMode: RoundBlockSizeMode::Margin,
                    foregroundColor: new Color(0, 0, 0),
                    backgroundColor: new Color(255, 255, 255)
                );

                $label = new Label(
                    text: $etiqueta,
                    textColor: new Color(255, 0, 0)
                );

                return (new PngWriter())->write($qrCode, null, $label)->getDataUri();
            }

            if (class_exists(\BaconQrCode\Writer::class) && class_exists(\BaconQrCode\Renderer\Image\SvgImageBackEnd::class)) {
                $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(240),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                );
                $svg = (new \BaconQrCode\Writer($renderer))->writeString($texto);

                return 'data:image/svg+xml;base64,' . base64_encode($svg);
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }

    private function imagenDataUri(string $path): string
    {
        if ($path === '' || ! File::exists($path)) {
            return '';
        }

        return 'data:' . File::mimeType($path) . ';base64,' . base64_encode(File::get($path));
    }

    private function nombreArchivoSunat(RDocumento $documento, string $codigoTipo): string
    {
        $ruc = trim((string) Empresa::query()
            ->where('estado_trash', '1')
            ->latest('idempresa')
            ->value('numero_documento'));
        $serie = trim((string) $documento->serie_comprobante);
        $numero = trim((string) $documento->numero_comprobante);

        if ($ruc === '' || $serie === '' || $numero === '') {
            abort(404, 'No se pudo resolver el nombre del archivo SUNAT.');
        }

        return "{$ruc}-{$codigoTipo}-{$serie}-{$numero}";
    }

    private function carpetaSunatDocumento(string $codigoTipo): string
    {
        return (string) config("sunat.paths.documentos.{$codigoTipo}", 'xml');
    }

    private function aplicarFiltroFechas($query, Request $request): void
    {
        $fechaInicio = $this->normalizarFechaFiltro($request->input('fecha_inicio'));
        $fechaFin = $this->normalizarFechaFiltro($request->input('fecha_fin'));

        if ($fechaInicio) {
            $query->where('fecha_emision', '>=', $fechaInicio->startOfDay());
        }

        if ($fechaFin) {
            $query->where('fecha_emision', '<=', $fechaFin->endOfDay());
        }
    }

    private function normalizarFechaFiltro($fecha): ?Carbon
    {
        $fecha = trim((string) ($fecha ?? ''));

        if ($fecha === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $fecha);
        } catch (\Throwable) {
            return null;
        }
    }

    private function validarFactura(
        Request $request,
        int $idTipoComprobante,
        string $codigoTipoDocumento,
        array $codigosTipoPermitidos = ['01', '03', '12'],
        bool $validarFechaActual = true,
        bool $requerirSerieActiva = true
    ): array
    {
        $detalles = $request->input('detalles', []);
        if (is_string($detalles)) {
            $decodificado = json_decode($detalles, true);
            $detalles = is_array($decodificado) ? $decodificado : [];
        }

        $metodosPago = $this->decodificarArregloJson($request->input('metodos_pago', []));
        $documentosNombres = $this->decodificarArregloJson($request->input('documentos_nombres', []));
        $documentosEliminar = $this->decodificarArregloJson($request->input('documentos_eliminar', []));

        $request->merge([
            'idsunat_c01_tipo_comprobante' => $request->input('idsunat_c01_tipo_comprobante') === '' ? null : $request->input('idsunat_c01_tipo_comprobante'),
            'idpersona_cliente' => $request->input('idpersona_cliente') === '' ? null : $request->input('idpersona_cliente'),
            'idserie_comprobante' => $request->input('idserie_comprobante') === '' ? null : $request->input('idserie_comprobante'),
            'fecha_emision' => $request->input('fecha_emision') ?: now()->toDateString(),
            'detalles' => $detalles,
            'metodos_pago' => $metodosPago,
            'documentos_nombres' => $documentosNombres,
            'documentos_eliminar' => $documentosEliminar,
        ]);

        $reglaSerie = Rule::exists('serie_comprobante', 'idserie_comprobante')
            ->where('idsunat_c01_tipo_comprobante', $idTipoComprobante);

        if ($requerirSerieActiva) {
            $reglaSerie->where('estado_trash', '1');
        }

        $validated = $request->validate([
            'idsunat_c01_tipo_comprobante' => [
                'required',
                'integer',
                Rule::exists('sunat_c01_tipo_comprobante', 'idsunat_c01_tipo_comprobante')
                    ->where('estado_trash', '1')
                    ->whereIn('codigo', $codigosTipoPermitidos),
            ],
            'idpersona_cliente' => [
                'required',
                'integer',
                Rule::exists('persona', 'idpersona')->where('estado_trash', '1'),
            ],
            'idserie_comprobante' => ['required', 'integer', $reglaSerie],
            'fecha_emision' => ['required', 'date_format:Y-m-d'],
            'observacion_documento' => ['nullable', 'string', 'max:1000'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.idproducto' => ['required', 'integer', Rule::exists('producto', 'idproducto')->where('estado_trash', '1')],
            'detalles.*.descripcion' => ['required', 'string', 'max:250'],
            'detalles.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'detalles.*.precio_venta' => ['required', 'numeric', 'gt:0'],
            'detalles.*.descuento' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'metodos_pago' => ['required', 'array', 'min:1'],
            'metodos_pago.*.idcuenta_bancaria' => [
                'required',
                'integer',
                Rule::exists('cuenta_bancaria', 'idcuenta_bancaria')->where('estado_trash', '1'),
            ],
            'metodos_pago.*.monto' => ['required', 'numeric', 'gt:0'],
            'metodos_pago.*.codigo_voucher' => ['nullable', 'string', 'max:60'],
            'documentos' => ['nullable', 'array'],
            'documentos.*' => ['nullable', 'file', 'max:10240'],
            'documentos_nombres' => ['nullable', 'array'],
            'documentos_nombres.*.modo' => ['nullable', 'string', Rule::in(['archivo', 'otro'])],
            'documentos_nombres.*.nombre_visible' => ['nullable', 'string', 'max:190'],
            'documentos_eliminar' => ['nullable', 'array'],
            'documentos_eliminar.*' => ['integer'],
        ], [
            'idsunat_c01_tipo_comprobante.required' => 'Seleccione el tipo de comprobante.',
            'idsunat_c01_tipo_comprobante.exists' => 'Seleccione un tipo de comprobante valido.',
            'idpersona_cliente.required' => 'Seleccione el cliente.',
            'idserie_comprobante.required' => 'Seleccione una serie.',
            'fecha_emision.required' => 'Seleccione la fecha de emision.',
            'detalles.required' => 'Debe agregar al menos un item.',
            'detalles.min' => 'Debe agregar al menos un item.',
            'detalles.*.idproducto.required' => 'Seleccione un producto para cada item.',
            'detalles.*.descripcion.required' => 'La descripcion del item es obligatoria.',
            'detalles.*.cantidad.required' => 'La cantidad del item es obligatoria.',
            'detalles.*.cantidad.gt' => 'La cantidad debe ser mayor a cero.',
            'detalles.*.precio_venta.required' => 'El precio del item es obligatorio.',
            'detalles.*.precio_venta.gt' => 'El precio debe ser mayor a cero.',
            'metodos_pago.required' => 'Agregue al menos un metodo de pago.',
            'metodos_pago.min' => 'Agregue al menos un metodo de pago.',
            'metodos_pago.*.idcuenta_bancaria.required' => 'Seleccione cuenta bancaria / metodo en cada fila.',
            'metodos_pago.*.idcuenta_bancaria.exists' => 'Uno o mas metodos de pago no son validos o estan inactivos.',
            'metodos_pago.*.monto.required' => 'Complete el monto de cada metodo de pago.',
            'metodos_pago.*.monto.gt' => 'El monto de cada metodo de pago debe ser mayor a cero.',
            'documentos.*.max' => 'Cada archivo adjunto debe pesar maximo 10 MB.',
            'documentos_nombres.*.nombre_visible.max' => 'El nombre visible del archivo debe tener maximo 190 caracteres.',
        ]);

        if ($validarFechaActual && in_array($codigoTipoDocumento, ['01', '03'], true) && Carbon::parse($validated['fecha_emision'])->toDateString() !== now()->toDateString()) {
            $nombre = $codigoTipoDocumento === '01' ? 'FACTURA (01)' : 'BOLETA (03)';
            abort(response()->json([
                'status' => false,
                'message' => "Para {$nombre}, la fecha de emision debe ser la fecha actual.",
                'errors' => [
                    'fecha_emision' => ["Para {$nombre}, la fecha de emision debe ser la fecha actual."],
                ],
            ], 422));
        }

        foreach ($validated['documentos_nombres'] ?? [] as $indice => $nombreDocumento) {
            if (($nombreDocumento['modo'] ?? 'archivo') === 'otro' && trim((string) ($nombreDocumento['nombre_visible'] ?? '')) === '') {
                abort(response()->json([
                    'status' => false,
                    'message' => 'Ingrese el nombre visible de cada documento configurado con otro nombre.',
                    'errors' => [
                        "documentos_nombres.{$indice}.nombre_visible" => ['Ingrese el nombre visible del documento.'],
                    ],
                ], 422));
            }
        }

        return $validated;
    }

    private function decodificarArregloJson(mixed $valor): array
    {
        if (is_string($valor)) {
            $decodificado = json_decode($valor, true);
            return is_array($decodificado) ? $decodificado : [];
        }

        return is_array($valor) ? $valor : [];
    }

    private function normalizarMetodosPago(array $metodosPago, float $totalDocumento): array
    {
        $normalizados = collect($metodosPago)
            ->map(fn ($item) => [
                'idcuenta_bancaria' => (int) ($item['idcuenta_bancaria'] ?? 0),
                'monto' => round((float) ($item['monto'] ?? 0), 2),
                'codigo_voucher' => mb_substr(trim((string) ($item['codigo_voucher'] ?? '')), 0, 60),
            ])
            ->filter(fn ($item) => $item['idcuenta_bancaria'] > 0 && $item['monto'] > 0)
            ->values();

        if ($normalizados->isEmpty()) {
            throw new HttpResponseException(ApiResponse::fail('Agregue al menos un metodo de pago valido.', 422));
        }

        $suma = round((float) $normalizados->sum('monto'), 2);
        if (abs($suma - round($totalDocumento, 2)) > 0.01) {
            throw new HttpResponseException(ApiResponse::fail('La suma de los metodos de pago debe coincidir con el total del comprobante.', 422, [
                'total_comprobante' => round($totalDocumento, 2),
                'total_metodos_pago' => $suma,
            ]));
        }

        return $normalizados->all();
    }

    private function sincronizarDetallesDocumento(RDocumento $documento, array $detalles, int $idUsuario): void
    {
        $detallesExistentes = $documento->detalles()
            ->orderBy('idrdocumento_detalle')
            ->get()
            ->values();

        foreach (array_values($detalles) as $indice => $detalle) {
            $detalleExistente = $detallesExistentes->get($indice);

            if ($detalleExistente) {
                $detalleExistente->update($detalle);
                continue;
            }

            $documento->detalles()->create($detalle);
        }

        $idsEliminar = $detallesExistentes
            ->slice(count($detalles))
            ->pluck('idrdocumento_detalle')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($idsEliminar->isEmpty()) {
            return;
        }

        DB::table('rdocumento_cuota_pago')
            ->whereIn('idrdocumento_detalle', $idsEliminar)
            ->update([
                'idrdocumento_detalle' => null,
                'updated_at' => now(),
                'user_updated' => $idUsuario,
            ]);

        $documento->detalles()
            ->whereIn('idrdocumento_detalle', $idsEliminar)
            ->delete();
    }

    private function validarPlazoAnulacionNotaCredito(RDocumento $documento): void
    {
        $fechaEmision = Carbon::parse($documento->fecha_emision)->startOfDay();
        $fechaLimite = $this->obtenerFechaLimiteAnulacionNotaCredito($fechaEmision);
        $hoy = now()->startOfDay();

        if ($hoy->lte($fechaLimite)) {
            return;
        }

        $diasTranscurridos = max(0, $fechaEmision->diffInDays($hoy));
        abort(response()->json([
            'status' => false,
            'message' => sprintf(
                'El comprobante fue emitido el %s. Han transcurrido %d dias desde su emision. El plazo permitido por SUNAT es hasta el decimo quinto (15) dia habil del mes siguiente a la emision. Ya no se puede anular mediante nota de credito. Fecha limite: %s.',
                $fechaEmision->format('d/m/Y'),
                $diasTranscurridos,
                $fechaLimite->format('d/m/Y')
            ),
        ], 422));
    }

    private function obtenerFechaLimiteAnulacionNotaCredito(Carbon $fechaEmision): Carbon
    {
        $fecha = $fechaEmision->copy()->addMonthNoOverflow()->startOfMonth();
        $diasHabiles = 0;

        while ($diasHabiles < 15) {
            if ($this->esDiaHabilSunat($fecha)) {
                $diasHabiles++;
            }

            if ($diasHabiles < 15) {
                $fecha->addDay();
            }
        }

        return $fecha;
    }

    private function esDiaHabilSunat(Carbon $fecha): bool
    {
        if ($fecha->isWeekend()) {
            return false;
        }

        $anio = (int) $fecha->year;
        $feriados = [
            '01-01',
            '05-01',
            '06-07',
            '06-29',
            '07-23',
            '07-28',
            '07-29',
            '08-06',
            '08-30',
            '10-08',
            '11-01',
            '12-08',
            '12-09',
            '12-25',
        ];
        $pascua = $this->obtenerDomingoPascua($anio);
        $feriados[] = $pascua->copy()->subDays(3)->format('m-d');
        $feriados[] = $pascua->copy()->subDays(2)->format('m-d');

        return ! in_array($fecha->format('m-d'), $feriados, true);
    }

    private function obtenerDomingoPascua(int $anio): Carbon
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::createMidnightDate($anio, $mes, $dia);
    }

    private function registrarMetodosPago(RDocumento $documento, array $metodosPago, int $idUsuario): void
    {
        DB::table('rdocumento_metodo_pago')
            ->where('idrdocumento', (int) $documento->idrdocumento)
            ->delete();

        DB::table('rdocumento_metodo_pago')->insert(
            collect($metodosPago)->map(fn ($metodo) => [
                'idrdocumento' => (int) $documento->idrdocumento,
                'idcuenta_bancaria' => (int) $metodo['idcuenta_bancaria'],
                'monto' => round((float) $metodo['monto'], 2),
                'codigo_voucher' => trim((string) ($metodo['codigo_voucher'] ?? '')) ?: null,
                'comprobante' => null,
                'comprobante_nombre_visible' => null,
                'comprobante_nombre_original' => null,
                'comprobante_size_bytes' => null,
                'estado_trash' => '1',
                'created_at' => now(),
                'updated_at' => now(),
                'user_trash' => null,
                'user_created' => $idUsuario,
                'user_updated' => $idUsuario,
            ])->all()
        );
    }

    private function registrarArchivosFacturacion(RDocumento $documento, array $archivos, int $idUsuario, array $documentosNombres = []): void
    {
        $archivosValidos = collect($archivos)
            ->values()
            ->filter(fn ($archivo) => $archivo instanceof UploadedFile && $archivo->isValid())
            ->values();

        if ($archivosValidos->isEmpty()) {
            return;
        }

        $directorio = $this->asegurarDirectorioArchivosFacturacion();

        foreach ($archivosValidos as $indice => $archivo) {
            $extension = strtolower((string) $archivo->getClientOriginalExtension());
            $nombreOriginal = (string) $archivo->getClientOriginalName();
            $tipoMime = $archivo->getClientMimeType();
            $pesoBytes = (int) ($archivo->getSize() ?? 0);
            $metaNombre = is_array($documentosNombres[$indice] ?? null) ? $documentosNombres[$indice] : [];
            $nombreGuardado = Str::uuid()->toString() . ($extension !== '' ? ".{$extension}" : '');
            $archivo->move($directorio, $nombreGuardado);

            RDocumentoArchivo::create([
                'idrdocumento' => (int) $documento->idrdocumento,
                'nombre_original' => $nombreOriginal,
                'nombre_guardado' => $nombreGuardado,
                'nombre_visible' => $this->resolverNombreVisibleArchivoFacturacion($archivo, $metaNombre),
                'extension' => $extension !== '' ? $extension : null,
                'tipo_mime' => $tipoMime,
                'peso_bytes' => $pesoBytes,
                'observacion_documento' => null,
                'estado_trash' => '1',
                'user_trash' => null,
                'user_created' => $idUsuario,
                'user_updated' => $idUsuario,
            ]);
        }
    }

    private function eliminarArchivosFacturacion(RDocumento $documento, array $idsArchivos, int $idUsuario): void
    {
        $ids = collect($idsArchivos)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $cantidad = RDocumentoArchivo::query()
            ->where('idrdocumento', (int) $documento->idrdocumento)
            ->where('estado_trash', '1')
            ->whereIn('idrdocumento_archivo', $ids)
            ->count();

        if ($cantidad !== $ids->count()) {
            throw new HttpResponseException(ApiResponse::fail('Uno o mas documentos seleccionados para eliminar no son validos.', 422));
        }

        RDocumentoArchivo::query()
            ->whereIn('idrdocumento_archivo', $ids)
            ->update([
                'estado_trash' => '0',
                'user_trash' => $idUsuario,
                'user_updated' => $idUsuario,
            ]);
    }

    private function resolverNombreVisibleArchivoFacturacion(UploadedFile $archivo, array $metaNombre): string
    {
        $nombreBase = trim((string) pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'Documento';
        $modo = strtolower(trim((string) ($metaNombre['modo'] ?? 'archivo')));
        $nombreVisible = trim((string) ($metaNombre['nombre_visible'] ?? ''));

        return mb_substr($modo === 'otro' && $nombreVisible !== '' ? $nombreVisible : $nombreBase, 0, 190);
    }

    private function asegurarDirectorioArchivosFacturacion(): string
    {
        $directorio = public_path('assets/modulo/rdocumento/facturacion');
        if (! is_dir($directorio) && ! mkdir($directorio, 0755, true) && ! is_dir($directorio)) {
            throw new \RuntimeException('No se pudo crear el directorio de documentos de facturacion.');
        }

        return $directorio;
    }

    private function calcularDetalleYTotales(array $detalles, float $impuesto, string $tipoComprobante): array
    {
        $acumuladoSubtotal = 0.0;
        $acumuladoDescuento = 0.0;
        $detalleFinal = [];

        foreach ($detalles as $item) {
            $cantidad = (float) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio_venta'] ?? 0);
            $descuentoMonto = (float) ($item['descuento'] ?? 0);
            $descuentoPorcentaje = (float) ($item['descuento_porcentaje'] ?? 0);

            $subtotalNoDescuento = round($cantidad * $precio, 2);
            $descuentoDesdePorcentaje = round($subtotalNoDescuento * ($descuentoPorcentaje / 100), 2);

            if ($descuentoMonto <= 0 && $descuentoPorcentaje > 0) {
                $descuentoMonto = $descuentoDesdePorcentaje;
            }

            if ($descuentoPorcentaje <= 0 && $descuentoMonto > 0 && $subtotalNoDescuento > 0) {
                $descuentoPorcentaje = round(($descuentoMonto * 100) / $subtotalNoDescuento, 2);
            }

            $descuentoMonto = min($descuentoMonto, $subtotalNoDescuento);
            $subtotal = round($subtotalNoDescuento - $descuentoMonto, 2);
            $precioConDescuento = $cantidad > 0 ? round($subtotal / $cantidad, 2) : $precio;

            $detalleFinal[] = [
                'idlote' => null,
                'idproducto' => (int) ($item['idproducto'] ?? 0),
                'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                'v_tipo_comprobante' => mb_substr($tipoComprobante, 0, 40),
                'v_fecha_emision' => now(),
                'cantidad' => $cantidad,
                'precio_compra' => 0,
                'precio_venta' => $precio,
                'precio_venta_descuento' => $precioConDescuento,
                'descuento' => $descuentoMonto,
                'descuento_porcentaje' => $descuentoPorcentaje,
                'subtotal' => $subtotal,
                'subtotal_no_descuento' => $subtotalNoDescuento,
            ];

            $acumuladoSubtotal += $subtotal;
            $acumuladoDescuento += $descuentoMonto;
        }

        $acumuladoSubtotal = round($acumuladoSubtotal, 2);
        $acumuladoDescuento = round($acumuladoDescuento, 2);
        $impuestoAplicado = round(max($impuesto, 0), 2);
        $igv = round($acumuladoSubtotal * ($impuestoAplicado / 100), 2);
        $total = round($acumuladoSubtotal + $igv, 2);

        return [
            'detalles' => $detalleFinal,
            'impuesto' => $impuestoAplicado,
            'subtotal' => $acumuladoSubtotal,
            'descuento' => $acumuladoDescuento,
            'igv' => $igv,
            'total' => $total,
        ];
    }

    private function obtenerIgvEmpresaConfigurado(): float
    {
        $empresa = Empresa::query()
            ->where('estado_trash', '1')
            ->latest('idempresa')
            ->first();

        if (! $empresa) {
            abort(response()->json([
                'status' => false,
                'message' => 'No existe una empresa activa configurada.',
            ], 422));
        }

        return round(max((float) ($empresa->igv ?? 0), 0), 2);
    }

    private function obtenerSeriePermitida(
        int $idSerie,
        int $idUsuario,
        int $idTipoComprobante,
        bool $forUpdate,
        ?int $idTipoComprobanteAdicional = null
    ): SerieComprobante
    {
        $query = SerieComprobante::query()
            ->from('serie_comprobante as sc')
            ->join('permiso_usuario_serie as pus', function ($join) use ($idUsuario) {
                $join
                    ->on('pus.idserie_comprobante', '=', 'sc.idserie_comprobante')
                    ->where('pus.idusers', '=', $idUsuario);
            })
            ->where('sc.idserie_comprobante', $idSerie)
            ->where('sc.estado_trash', '1')
            ->where('sc.idsunat_c01_tipo_comprobante', $idTipoComprobante)
            ->select('sc.*');

        if ($idTipoComprobanteAdicional !== null) {
            $query->where('sc.tipo_comprobante_adicional', $idTipoComprobanteAdicional);
        }

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $serie = $query->first();

        if (! $serie) {
            abort(response()->json([
                'status' => false,
                'message' => 'La serie seleccionada no esta permitida para tu usuario o no corresponde al tipo de comprobante afectado.',
            ], 422));
        }

        return $serie;
    }

    private function estadoSunatInicial(string $codigoTipoDocumento): string
    {
        return match (trim($codigoTipoDocumento)) {
            '12' => 'ACEPTADA',
            '01', '03', '07', '08' => 'POR ENVIAR',
            default => throw new \InvalidArgumentException('Tipo de comprobante no permitido para generar el estado SUNAT inicial.'),
        };
    }
}
