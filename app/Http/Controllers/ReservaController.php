<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Reserva\StoreClienteRapidoRequest;
use App\Http\Requests\Reserva\StorePagoReservaRequest;
use App\Http\Requests\Reserva\StoreReservaRequest;
use App\Http\Requests\Reserva\UpdateReservaRequest;
use App\Models\CuentaBancaria;
use App\Models\Empresa;
use App\Models\Hotel;
use App\Models\HotelHabitacion;
use App\Models\LlegadaPorEmpresa;
use App\Models\LlegadaTipo;
use App\Models\OrigenReserva;
use App\Models\Persona;
use App\Models\RDocumento;
use App\Models\Reserva;
use App\Models\SerieComprobante;
use App\Models\Tour;
use App\Models\TourTurno;
use App\Models\UbigeoDistrito;
use App\Services\ReservaCodigoService;
use App\Services\ReservaPagoService;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaService $reservaService,
        private readonly ReservaPagoService $reservaPagoService,
        private readonly ReservaCodigoService $reservaCodigoService,
    ) {
    }
    public function index()
    {
        return view('reserva');
    }

    public function store(StoreReservaRequest $request)
    {
        $reserva = $this->reservaService->persistir(new Reserva(), $request->validated(), $request->soloTours(), $this->usuarioId($request));

        return ApiResponse::success($this->reservaService->cargarParaFormulario($reserva), 'Reserva registrada correctamente.');
    }

    public function show(Reserva $reserva)
    {
        return ApiResponse::success($this->reservaService->cargarParaFormulario($reserva));
    }

    public function update(UpdateReservaRequest $request, Reserva $reserva)
    {
        $reserva = $this->reservaService->persistir($reserva, $request->validated(), $request->soloTours(), $this->usuarioId($request));

        return ApiResponse::success($this->reservaService->cargarParaFormulario($reserva), 'Reserva actualizada correctamente.');
    }

    public function destroy(Request $request, Reserva $reserva)
    {
        $reserva->update([
            'estado_trash' => '0',
            'user_trash' => $this->usuarioId($request),
            'user_updated' => $this->usuarioId($request),
        ]);

        return ApiResponse::success($reserva, 'Reserva enviada a papelera.');
    }

    public function detalle(Reserva $reserva)
    {
        $reserva->load(['cliente.docIdentidad', 'trabajador', 'origen', 'llegadaEmpresa', 'detalles.turno', 'hoteles.habitacion.hotel.persona']);
        $pagos = $this->reservaPagoService->pagosReserva((int) $reserva->idreserva);
        $html = view('reserva.detalle', compact('reserva', 'pagos'))->render();

        return ApiResponse::success($html);
    }

    public function detalleComprobante(Reserva $reserva)
    {
        $reserva->load(['detalles', 'hoteles']);

        return ApiResponse::success([
            'detalle' => $this->detalleComprobanteReserva($reserva),
        ]);
    }

    public function ficha(Reserva $reserva)
    {
        $reserva->load([
            'cliente.docIdentidad',
            'trabajador',
            'origen',
            'llegadaEmpresa',
            'detalles.turno',
            'hoteles.habitacion.hotel.persona',
        ]);

        $pagos = $this->reservaPagoService->pagosReserva((int) $reserva->idreserva);
        $empresa = Empresa::query()
            ->where('estado_trash', '1')
            ->latest('idempresa')
            ->first();

        return view('reserva.ficha', [
            'reserva' => $reserva,
            'pagos' => $pagos,
            'empresa' => $empresa,
            'logoUrl' => $this->resolverLogoEmpresaFicha($empresa),
        ]);
    }

    public function updateClienteNumeroDocumento(Request $request, int $cliente)
    {
        $validated = $request->validate([
            'numero_documento' => ['required', 'string', 'max:20'],
        ]);

        $persona = Persona::where('idpersona', $cliente)
            ->where('estado_trash', '1')
            ->firstOrFail();

        $persona->update([
            'numero_documento' => $validated['numero_documento'],
            'user_updated' => $this->usuarioId($request),
        ]);

        return ApiResponse::success($persona, 'Numero de documento actualizado correctamente.');
    }

    private function usuarioId(Request $request): int
    {
        $id = $request->user()?->id;

        if (! $id) {
            throw ValidationException::withMessages([
                'auth' => 'La sesion expiro. Vuelve a iniciar sesion.',
            ]);
        }

        return (int) $id;
    }

    public function listar(Request $request)
    {
        $baseQuery = Reserva::with(['cliente.docIdentidad', 'trabajador', 'origen', 'llegadaEmpresa', 'creador'])
            ->where('estado_trash', '1');

        $recordsTotal = (clone $baseQuery)->count('idreserva');
        $query = $baseQuery->latest('idreserva');

        if ($request->filled('filtro_fecha_i')) {
            $query->whereDate('fecha_llegada', '>=', $request->input('filtro_fecha_i'));
        }

        if ($request->filled('filtro_fecha_f')) {
            $query->whereDate('fecha_llegada', '<=', $request->input('filtro_fecha_f'));
        }

        if ($request->filled('filtro_cliente')) {
            $query->where('idcliente', $request->input('filtro_cliente'));
        }

        $search = trim((string) $request->input('search.value', $request->input('sSearch', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('serie_numero', 'like', "%{$search}%")
                    ->orWhereHas('cliente', fn ($p) => $p->where('descripcion', 'like', "%{$search}%")->orWhere('numero_documento', 'like', "%{$search}%"));
            });
        }

        $recordsFiltered = (clone $query)->count('idreserva');
        $totalGeneral = (float) (clone $query)->sum('total_reserva');
        $rowsParaTotales = (clone $query)->get(['idreserva', 'total_reserva']);
        $pagosParaTotales = $this->reservaPagoService->pagosPorReservas($rowsParaTotales);
        $deudaGeneral = $rowsParaTotales->sum(function (Reserva $reserva) use ($pagosParaTotales) {
            return max((float) ($reserva->total_reserva ?? 0) - (float) ($pagosParaTotales[$reserva->idreserva] ?? 0), 0);
        });
        $start = max((int) $request->input('start', $request->input('iDisplayStart', 0)), 0);
        $length = (int) $request->input('length', $request->input('iDisplayLength', 10));
        $rows = $length === -1 ? $query->get() : $query->skip($start)->take(max($length, 1))->get();

        $pagosPorReserva = $this->reservaPagoService->pagosPorReservas($rows);
        $data = $rows->values()->map(function (Reserva $reserva, int $index) use ($start, $pagosPorReserva) {
            return $this->filaDataTable($reserva, $start + $index + 1, (float) ($pagosPorReserva[$reserva->idreserva] ?? 0));
        });

        return response()->json([
            'status' => true,
            'sEcho' => (int) $request->input('sEcho', $request->input('draw', 1)),
            'draw' => (int) $request->input('draw', $request->input('sEcho', 1)),
            'iTotalRecords' => $recordsTotal,
            'iTotalDisplayRecords' => $recordsFiltered,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'total_general' => $totalGeneral,
            'total_general_formateado' => 'S/ ' . number_format($totalGeneral, 2),
            'deuda_general' => $deudaGeneral,
            'deuda_general_formateada' => 'S/ ' . number_format($deudaGeneral, 2),
            'aaData' => $data,
            'data' => $data,
        ]);
    }

    private function filaDataTable(Reserva $reserva, int $numero, float $pagado = 0): array
    {
        $cliente = $reserva->cliente;
        $nombre = trim((string) ($cliente?->descripcion ?? '-'));
        $apellido = trim((string) ($cliente?->nombre_comercial ?? ''));
        $documento = (string) ($cliente?->numero_documento ?? '');
        $tipoDocumento = (string) ($cliente?->tipo_documento ?? '');
        $totalReserva = (float) ($reserva->total_reserva ?? 0);
        $saldo = max($totalReserva - $pagado, 0);
        $total = $this->badgeTotal($totalReserva);
        $deuda = $this->badgeDeuda($saldo, $totalReserva);
        $estado = $this->badgeEstadoPago($saldo, $totalReserva);
        $fechaLlegada = $this->formatearFechaTabla($reserva->fecha_llegada);
        $fechaCreacion = $this->formatearFechaTabla($reserva->created_at);
        $usuarioCreador = $reserva->creador?->display_name ?: ($reserva->creador?->name ?: $reserva->trabajador?->descripcion);
        $usuarioCreacion = $this->usuarioCreacionTabla($usuarioCreador, $fechaCreacion);
        $acciones = sprintf(
            '<button type="button" class="btn btn-sm btn-icon btn-info-light" onclick="mostrar_detalle(%d)" title="Ver"><i class="ri-eye-line"></i></button> <button type="button" class="btn btn-sm btn-icon btn-warning-light" onclick="editarReserva(%d)" title="Editar"><i class="ri-edit-line"></i></button> <button type="button" class="btn btn-sm btn-icon btn-danger-light" onclick="eliminarReserva(%d)" title="Eliminar"><i class="ri-delete-bin-line"></i></button>',
            $reserva->idreserva,
            $reserva->idreserva,
            $reserva->idreserva
        );
        return [
            $numero,
            $acciones,
            e($nombre),
            e($reserva->tours_paquete ?: '-'),
            $fechaLlegada,
            e($reserva->nro_pasajeros ?? '-'),
            e($cliente?->celular ?? '-'),
            $usuarioCreacion,
            $total,
            $deuda,
            $estado,
            $reserva->idreserva,
            $reserva->idcliente,
            $documento,
            $tipoDocumento,
            (int) $reserva->user_created,
        ];
    }

    private function formatearFechaTabla(mixed $fecha): string
    {
        if (! $fecha) {
            return '-';
        }

        return $fecha instanceof \Carbon\CarbonInterface
            ? $fecha->format('d/m/Y')
            : (string) \Carbon\Carbon::parse($fecha)->format('d/m/Y');
    }

    private function usuarioCreacionTabla(?string $usuario, string $fechaCreacion): string
    {
        $nombre = e($usuario ?: '-');
        $fecha = e($fechaCreacion);

        return '<div class="fw-semibold">' . $nombre . '</div><div class="fs-11 text-muted">Creado: ' . $fecha . '</div>';
    }
    private function badgeTotal(float $totalReserva): string
    {
        return '<span class="badge bg-primary-transparent text-primary fw-semibold">S/ ' . number_format($totalReserva, 2) . '</span>';
    }
    private function badgeDeuda(float $saldo, float $totalReserva): string
    {
        $monto = number_format($saldo, 2);

        if ($saldo <= 0) {
            return '<span class="badge bg-success-transparent text-success fw-semibold">S/ ' . $monto . '</span>';
        }

        if ($totalReserva > 0 && $saldo >= $totalReserva) {
            return '<span class="badge bg-danger-transparent text-danger fw-semibold">S/ ' . $monto . '</span>';
        }

        return '<span class="badge bg-warning-transparent text-warning fw-semibold">S/ ' . $monto . '</span>';
    }
    private function badgeEstadoPago(float $saldo, float $totalReserva): string
    {
        if ($saldo <= 0) {
            return '<span class="badge bg-success-transparent text-success fw-semibold">Pagado</span>';
        }

        if ($totalReserva > 0 && $saldo >= $totalReserva) {
            return '<span class="badge bg-danger-transparent text-danger fw-semibold">Sin pago</span>';
        }

        return '<span class="badge bg-warning-transparent text-warning fw-semibold">Pago parcial</span>';
    }
    public function clientes(Request $request)
    {
        return ApiResponse::success($this->optionPersonasPorTipo('CLIENTE', $request->input('term')));
    }

    public function trabajadores(Request $request)
    {
        return ApiResponse::success($this->optionPersonasPorTipo('TRABAJADOR', $request->input('term')));
    }

    private function optionPersonasPorTipo(string $tipo, ?string $term = null): string
    {
        $query = Persona::where('estado_trash', '1')
            ->whereHas('tiposPersona.personaTipo', fn ($q) => $q->where('nombre', $tipo));

        if ($term) {
            $query->where(fn ($q) => $q->where('descripcion', 'like', "%{$term}%")->orWhere('numero_documento', 'like', "%{$term}%"));
        }

        return $query->orderBy('descripcion')->limit(100)->get()->map(function (Persona $p) {
            $text = trim($p->descripcion . ' - ' . $p->numero_documento);
            return sprintf(
                '<option value="%d" data-celular="%s" tipo_documento="%s" numero_documento="%s" direccion="%s">%s</option>',
                $p->idpersona,
                e($p->celular),
                e($p->tipo_documento),
                e($p->numero_documento),
                e($p->direccion),
                e($text)
            );
        })->prepend('<option value="">Seleccione</option>')->implode('');
    }

    public function origenes()
    {
        $rows = OrigenReserva::where('estado_trash', '1')->orderBy('descripcion')->get();
        return ApiResponse::success($this->options($rows, 'idorigen_reserva', 'descripcion'));
    }

    public function llegadaTipos()
    {
        $rows = LlegadaTipo::where('estado_trash', '1')->orderBy('idllegada_tipo')->get();
        return ApiResponse::success($this->options($rows, 'idllegada_tipo', 'descripcion'));
    }

    public function llegadaEmpresas(Request $request)
    {
        $rows = LlegadaPorEmpresa::where('estado_trash', '1')
            ->when($request->filled('idllegada_por'), fn ($q) => $q->where('idllegada_tipo', $request->input('idllegada_por')))
            ->orderBy('descripcion')
            ->get();

        return ApiResponse::success($this->options($rows, 'idllegada_por_empresa', 'descripcion'));
    }

    public function hoteles()
    {
        $rows = Hotel::with('persona')->where('estado_trash', '1')->get()->map(fn ($h) => (object) [
            'id' => $h->idhotel,
            'text' => $h->persona?->descripcion ?? ('Hotel '.$h->idhotel),
        ]);

        return ApiResponse::success($this->options($rows, 'id', 'text'));
    }

    public function habitaciones(Request $request)
    {
        $rows = HotelHabitacion::where('estado_trash', '1')->where('idhotel', $request->input('idhotel'))->orderBy('nombre')->get();
        return ApiResponse::success($this->options($rows, 'idhotel_habitacion', 'nombre'));
    }

    public function habitacion(int $habitacion)
    {
        $habitacion = HotelHabitacion::with('hotel.persona')->findOrFail($habitacion);
        return ApiResponse::success([
            'idhotel' => $habitacion->idhotel,
            'idhotel_habitacion' => $habitacion->idhotel_habitacion,
            'nombre_hotel' => $habitacion->hotel?->persona?->descripcion,
            'nombre_habitacion' => $habitacion->nombre,
            'cant_huespeds' => $habitacion->cant_huespeds,
            'check_in' => $habitacion->hotel?->check_in ? substr((string) $habitacion->hotel->check_in, 0, 5) : '12:00',
            'precio_normal' => $habitacion->precio_normal,
            'precio_coorporativo' => $habitacion->precio_coorporativo,
        ]);
    }

    public function buscarToursCatalogo(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $rows = Tour::with('turno')->where('estado_trash', '1')
            ->where(fn ($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('codigo', 'like', "%{$search}%"))
            ->limit(15)
            ->get()
            ->map(fn ($t) => [
                'idtours' => $t->idtours,
                'codigo' => $t->codigo,
                'nombre' => $t->nombre,
                'precio_tours' => $t->precio_tours,
                'turno' => $t->turno?->descripcion,
            ]);

        return ApiResponse::success($rows);
    }

    public function tour(int $tour)
    {
        $tour = Tour::findOrFail($tour);
        return response()->json([
            'status' => true,
            'data' => $tour,
            'turno' => TourTurno::where('estado_trash', '1')->get(['idtours_turno', 'descripcion as nombre']),
        ]);
    }

    public function detalleTour(int $tour)
    {
        $tour = Tour::findOrFail($tour);
        return ApiResponse::success('<div class="p-3"><h6 class="text-primary">'.e($tour->nombre).'</h6><div>'.$tour->descripcion.'</div></div>');
    }

    public function validarCodigoReserva(Request $request)
    {
        $exists = Reserva::where('serie_numero', $request->input('codigo'))
            ->when($request->filled('idreserva'), fn ($q) => $q->where('idreserva', '<>', $request->input('idreserva')))
            ->exists();

        return response($exists ? 'false' : 'true');
    }

    public function storeClienteRapido(StoreClienteRapidoRequest $request)
    {
        $persona = $this->reservaService->registrarClienteRapido($request->validated(), $this->usuarioId($request));

        return ApiResponse::success($persona->idpersona, 'Cliente registrado correctamente.');
    }

    public function codigo(Request $request)
    {
        return ApiResponse::success([
            'nombre_codigo' => $this->reservaCodigoService->generar($request->input('pre_codigo', 'RE')),
        ]);
    }

    public function distritos(Request $request)
    {
        $term = trim((string) $request->input('term'));
        $query = UbigeoDistrito::conUbicacion()
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('ubigeo_distrito.nombre', 'like', "%{$term}%")
                        ->orWhere('p.nombre', 'like', "%{$term}%")
                        ->orWhere('dep.nombre', 'like', "%{$term}%")
                        ->orWhere('ubigeo_distrito.idubigeo_distrito', 'like', "%{$term}%")
                        ->orWhere('ubigeo_distrito.ubigeo_inei', 'like', "%{$term}%");
                });
            })
            ->orderBy('distrito');

        if ($request->boolean('select2')) {
            $perPage = 20;
            $page = max((int) $request->input('page', 1), 1);
            $rows = $query->skip(($page - 1) * $perPage)->take($perPage + 1)->get();
            $hasMore = $rows->count() > $perPage;

            return ApiResponse::success([
                'results' => $rows->take($perPage)->map(fn ($d) => [
                    'id' => (string) $d->idubigeo_distrito,
                    'text' => trim($d->distrito . ' - ' . $d->provincia . ' - ' . $d->departamento),
                    'provincia' => $d->provincia,
                    'departamento' => $d->departamento,
                    'cod_ubigeo' => $d->ubigeo_inei ?: $d->idubigeo_distrito,
                ])->values(),
                'pagination' => ['more' => $hasMore],
            ]);
        }

        $rows = $query->limit(50)->get();
        $html = $rows->map(fn ($d) => sprintf(
            '<option value="%s" iddistrito="%s">%s</option>',
            e($d->idubigeo_distrito),
            e($d->idubigeo_distrito),
            e($d->distrito)
        ))->prepend('<option value="">Seleccione</option>')->implode('');
        return ApiResponse::success($html);
    }

    public function distrito(int $distrito)
    {
        $d = UbigeoDistrito::with('provincia.departamento')->findOrFail($distrito);
        return ApiResponse::success([
            'departamento' => $d->provincia?->departamento?->nombre,
            'provincia' => $d->provincia?->nombre,
            'ubigeo_inei' => $d->idubigeo_distrito,
        ]);
    }

    public function storePago(StorePagoReservaRequest $request)
    {
        $pago = $this->reservaPagoService->registrarAmortizacion($request->validated(), $this->usuarioId($request));

        return ApiResponse::success($pago, 'Amortizacion registrada correctamente.');
    }

    public function showPago(RDocumento $documento)
    {
        return ApiResponse::success($this->reservaPagoService->datosEdicion($documento));
    }

    public function updatePago(StorePagoReservaRequest $request, RDocumento $documento)
    {
        $pago = $this->reservaPagoService->actualizarAmortizacion($documento, $request->validated(), $this->usuarioId($request));

        return ApiResponse::success($pago, 'Pago actualizado correctamente.');
    }

    public function destroyPago(Request $request, RDocumento $documento)
    {
        $pago = $this->reservaPagoService->eliminarAmortizacion($documento, $this->usuarioId($request));

        return ApiResponse::success($pago, 'Pago eliminado correctamente.');
    }

    public function comprobantesAsociables(Request $request, Reserva $reserva)
    {
        $rows = $this->reservaPagoService
            ->comprobantesAsociables(
                (int) $reserva->idreserva,
                $request->input('term'),
                $request->boolean('todos')
            )
            ->map(function (RDocumento $documento) {
                $comprobante = trim(($documento->serie_comprobante ?? '') . '-' . ($documento->numero_comprobante ?? ''));

                return [
                    'idrdocumento' => (int) $documento->idrdocumento,
                    'comprobante' => $comprobante,
                    'tipo' => $documento->tipoComprobanteSunat?->abreviatura ?? $documento->tipo_comprobante ?? '-',
                    'fecha_emision' => optional($documento->fecha_emision)->format('d/m/Y'),
                    'cliente' => $documento->cliente?->descripcion ?? '-',
                    'total' => number_format((float) $documento->venta_total, 2, '.', ''),
                    'disponible' => number_format((float) ($documento->monto_disponible_reserva ?? $documento->venta_total), 2, '.', ''),
                    'sunat_estado' => $documento->sunat_estado ?: '-',
                ];
            })
            ->values();

        return ApiResponse::success($rows);
    }

    public function asociarComprobante(Request $request, Reserva $reserva)
    {
        $data = $request->validate([
            'idrdocumento' => ['required', 'integer', 'exists:rdocumento,idrdocumento'],
            'monto_cuota' => ['required', 'numeric', 'gt:0'],
        ]);
        $data['idreserva'] = (int) $reserva->idreserva;

        $relacion = $this->reservaPagoService->asociarComprobante($data, $this->usuarioId($request));

        return ApiResponse::success($relacion, 'Comprobante asociado correctamente.');
    }

    public function bancos()
    {
        $cuentas = CuentaBancaria::query()
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
            ]);

        return ApiResponse::success($cuentas->map(function ($cuenta) {
            $nombre = trim((string) ($cuenta->banco_alias ?: $cuenta->banco_nombre ?: 'Cuenta'));
            $numero = trim((string) ($cuenta->cta_cte ?: $cuenta->cci ?: 'Sin numero'));
            $moneda = strtoupper(trim((string) ($cuenta->moneda ?? '')));
            $label = trim($nombre . ' - ' . $numero . ($moneda !== '' ? " ({$moneda})" : ''));

            return [
                'value' => (int) $cuenta->idcuenta_bancaria,
                'label' => $label,
                'selected' => false,
                'disabled' => false,
            ];
        }));
    }

    public function seriesComprobante(Request $request)
    {
        $tipo = match ((string) $request->input('tipo_comprobante')) {
            '01' => 1,
            '03' => 3,
            '12' => 12,
            default => null,
        };

        $rows = SerieComprobante::where('estado_trash', '1')
            ->when($tipo, fn ($query) => $query->where('idsunat_c01_tipo_comprobante', $tipo))
            ->orderByDesc('predeterminado')
            ->orderBy('serie')
            ->get();

        return ApiResponse::success($this->options($rows, 'idserie_comprobante', 'serie'));
    }

    private function detalleComprobanteReserva(Reserva $reserva): string
    {
        $pax = (int) ($reserva->nro_pasajeros ?: 0);
        $personas = $pax === 1 ? '1 persona' : ($pax > 1 ? "{$pax} personas" : 'los pasajeros');

        $fechaInicio = $reserva->fecha_llegada;
        $fechaFin = $reserva->fecha_salida
            ?: $reserva->hoteles->pluck('fecha_check_out')->filter()->max()
            ?: $reserva->detalles->pluck('fecha_tours')->filter()->max();

        $periodo = $fechaInicio
            ? ' del ' . $this->formatoFechaComprobante($fechaInicio) . ($fechaFin ? ' al ' . $this->formatoFechaComprobante($fechaFin) : '')
            : '';

        $incluye = $this->textoIncluyeComprobante($reserva);

        return trim("Paquete turistico para {$personas}{$periodo} incluye {$incluye}");
    }

    private function resolverLogoEmpresaFicha(?Empresa $empresa): string
    {
        $logo = trim((string) ($empresa?->logo ?? ''));
        $candidatos = [
            $logo !== '' ? public_path("assets/modulo/empresa/logo/{$logo}") : null,
            public_path('assets/images/brand-logos/logo-raices-home-rectangulo.png'),
        ];

        foreach ($candidatos as $path) {
            if ($path && file_exists($path)) {
                return asset(str_replace('\\', '/', str_replace(public_path() . DIRECTORY_SEPARATOR, '', $path)));
            }
        }

        return asset('assets/images/brand-logos/logo-raices-home-rectangulo.png');
    }

    private function textoIncluyeComprobante(Reserva $reserva): string
    {
        $incluye = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $reserva->itinerario_general))));
        if ($incluye !== '') {
            return $incluye;
        }

        $tours = $reserva->detalles
            ->pluck('nombre_tours')
            ->filter()
            ->unique()
            ->take(3)
            ->implode(', ');

        return trim('transporte turistico, ingreso y servicio de guia' . ($tours !== '' ? ' en ' . $tours : ''));
    }

    private function formatoFechaComprobante(mixed $fecha): string
    {
        return $fecha instanceof \Carbon\CarbonInterface
            ? $fecha->format('d/m')
            : \Carbon\Carbon::parse($fecha)->format('d/m');
    }

    private function options($rows, string $valueKey, string $textKey): string
    {
        return $rows->map(fn ($row) => sprintf('<option value="%s">%s</option>', e($row->{$valueKey}), e($row->{$textKey} ?? '-')))
            ->prepend('<option value="">Seleccione</option>')
            ->implode('');
    }
}
