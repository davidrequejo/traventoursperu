@php
  $cliente = $reserva->cliente;
  $totalReserva = (float) ($reserva->total_reserva ?? 0);
  $totalTours = (float) $reserva->detalles->sum(fn ($detalle) => (float) $detalle->subtotal);
  $totalHotel = (float) $reserva->hoteles->sum(fn ($hotel) => (float) $hotel->precio + (float) ($hotel->adicional ?? 0));
  $pagos = $pagos ?? collect();
  $totalPagado = (float) $pagos->sum(fn ($pago) => (float) ($pago->reserva_monto_aplicado ?? $pago->venta_total));
  $pendiente = max($totalReserva - $totalPagado, 0);
  $porcentajePagado = $totalReserva > 0 ? round(($totalPagado / $totalReserva) * 100) : 0;
  $porcentajePendiente = $totalReserva > 0 ? max(100 - $porcentajePagado, 0) : 0;
  $estadoReserva = $pendiente > 0 ? 'Pendiente' : 'Confirmado';
  $estadoClass = $pendiente > 0 ? 'bg-warning text-dark' : 'bg-success';
  $tipoReserva = strtoupper((string) $reserva->tours_paquete) === 'SI' ? 'Tours' : 'Paquete';
  $fotoDefault = $cliente?->sexo === 'F' ? 'mujer.png' : 'hombre.png';
  $fotoPerfil = $cliente?->foto_perfil ?: $fotoDefault;
  $fotoUrl = asset('assets/modulo/persona/perfil/' . $fotoPerfil);
  $fotoFallback = asset('assets/modulo/persona/perfil/' . $fotoDefault);
  $detallePago = trim(($cliente?->descripcion ?? 'Cliente') . ' | Reserva ' . ($reserva->serie_numero ?? $reserva->idreserva));
  $fechaReservaDetalle = function ($fecha) {
      if (! $fecha) {
          return '-';
      }

      return \Illuminate\Support\Carbon::parse($fecha)->locale('es')->translatedFormat('d M Y');
  };
  $badgeEstadoSunatReserva = function ($estado) {
      $estado = trim((string) ($estado ?: '-'));
      $normalizado = mb_strtoupper($estado);
      $class = match ($normalizado) {
          'ACEPTADA', 'EMITIDO', 'EMITIDA' => 'bg-success-transparent',
          'POR ENVIAR' => 'bg-warning-transparent',
          default => 'bg-danger-transparent',
      };

      return '<span class="badge ' . $class . '">' . e($estado) . '</span>';
  };
@endphp

<style>
  .reserva-pagos-table {
    font-size: 11px;
  }

  .reserva-pagos-table thead th {
    font-size: 11px;
    line-height: 1.15;
  }

  .reserva-pagos-table tbody td,
  .reserva-pagos-table tfoot th {
    font-size: 11px;
    line-height: 1.2;
  }
</style>

<div class="bg-light p-3 p-lg-4 rounded-2">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
      <button type="button" class="btn btn-danger btn-sm btn-icon" onclick="show_hide_form(1);">
        <i class="ri-arrow-left-line"></i>
      </button>
      <div>
        <h5 class="mb-0 fw-semibold">Paquetes Turisticos</h5>
        <p class="text-muted fs-12 mb-0">Administra de manera eficiente todos tus Paquetes.</p>
      </div>
    </div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 fs-12">
        <li class="breadcrumb-item"><a href="javascript:void(0);">Paquetes</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $tipoReserva }}</li>
      </ol>
    </nav>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3 p-lg-4">
      <div class="row g-3 align-items-stretch">
        <div class="col-xl-3 col-lg-4">
          <div class="border rounded-2 h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h6 class="mb-1 fw-semibold">Datos Del cliente</h6>
                <div class="text-muted fs-12">
                  Codigo Reserva : <span class="text-primary">{{ $reserva->serie_numero ?? '-' }}</span>
                </div>
              </div>
              <button type="button" class="btn btn-light btn-sm btn-icon"><i class="ri-more-2-fill"></i></button>
            </div>
            <div class="text-center py-2">
              <span class="avatar avatar-xxl avatar-rounded border border-3 border-primary-transparent mb-3">
                <img src="{{ $fotoUrl }}" alt="Cliente" onerror="this.src='{{ $fotoFallback }}';">
              </span>
              <h6 class="fw-semibold mb-1 text-uppercase">{{ $cliente?->descripcion ?? '-' }}</h6>
              <div class="text-muted fs-12">
                {{ $cliente?->docIdentidad?->abreviatura ?? $cliente?->docIdentidad?->nombre ?? 'Doc.' }}: {{ $cliente?->numero_documento ?: '-' }}
              </div>
              <div class="text-muted fs-12">Contacto : {{ $cliente?->celular ?: '-' }}</div>
            </div>
          </div>
        </div>

        <div class="col-xl-5 col-lg-8">
          <div class="row g-0 h-100 border rounded-2 overflow-hidden bg-white shadow-sm">
            <div class="col-sm-4 bg-primary text-white p-3 d-flex flex-column justify-content-around text-sm-end">
              <div class="fw-semibold fs-12">Estado:</div>
              <div class="fw-semibold fs-12">N° PAX:</div>
              <div class="fw-semibold fs-12">Fecha de llegada:</div>
              <div class="fw-semibold fs-12">Referencia llegada:</div>
              <div class="fw-semibold fs-12">Asesor(a):</div>
              <div class="fw-semibold fs-12">R.Usuario:</div>
            </div>
            <div class="col-sm-8 p-3 d-flex flex-column justify-content-around gap-2">
              <div><span class="badge {{ $estadoClass }}">{{ $estadoReserva }}</span></div>
              <div><strong>{{ $reserva->nro_pasajeros ?? 0 }}</strong> (persona)</div>
              <div>
                <strong>{{ $fechaReservaDetalle($reserva->fecha_llegada) }}</strong>
                <span class="text-muted fs-12">Hora: {{ $reserva->hora_llegada ? substr((string) $reserva->hora_llegada, 0, 5) : '-' }} - Salida: {{ $fechaReservaDetalle($reserva->fecha_salida) }}</span>
              </div>
              <div>{{ $reserva->llegadaEmpresa?->nombre ?? $reserva->llegadaEmpresa?->descripcion ?? '-' }}</div>
              <div>
                {{ $reserva->trabajador?->descripcion ?? '-' }}
                <div class="text-muted fs-11">DNI: {{ $reserva->trabajador?->numero_documento ?? '-' }}</div>
              </div>
              <div>{{ $reserva->user_created ?? '-' }} <span class="text-muted fs-11">actualizado: {{ optional($reserva->updated_at)->format('d/m/y, H:i') }}</span></div>
            </div>
          </div>
        </div>

        <div class="col-xl-4">
          <div class="row g-0 h-100 border rounded-2 overflow-hidden bg-white shadow-sm">
            <div class="col-sm-6 bg-info text-white p-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold fs-12 mb-2">Total Reserva</div>
                  <h4 class="text-white mb-1">S/ {{ number_format($totalReserva, 2) }}</h4>
                  <div class="fs-11">% General 100%</div>
                </div>
                <span class="avatar avatar-sm bg-white text-info"><i class="ri-money-dollar-circle-line"></i></span>
              </div>
            </div>
            <div class="col-sm-6 bg-danger text-white p-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold fs-12 mb-2">Pendiente</div>
                  <h4 class="text-white mb-1">S/ {{ number_format($pendiente, 2) }}</h4>
                  <div class="fs-11">% Pendiente {{ $porcentajePendiente }}%</div>
                </div>
                <span class="avatar avatar-sm bg-white text-danger"><i class="ri-bank-card-line"></i></span>
              </div>
            </div>
            <div class="col-sm-6 bg-success text-white p-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold fs-12 mb-2">Total Pagado</div>
                  <h4 class="text-white mb-1">S/ {{ number_format($totalPagado, 2) }}</h4>
                  <div class="fs-11">% Pagado {{ $porcentajePagado }}%</div>
                </div>
                <span class="avatar avatar-sm bg-white text-success"><i class="ri-cash-line"></i></span>
              </div>
            </div>
            <div class="col-sm-6 p-3 d-flex align-items-center justify-content-center gap-3">
              <button type="button" class="btn btn-success btn-sm" onclick="amortizar({{ (int) $reserva->idcliente }}, {{ number_format($pendiente, 2, '.', '') }}, @js($detallePago));">
                <i class="ri-add-circle-line me-1"></i> Amortizar
              </button>
              <a class="btn btn-info btn-sm" href="{{ route('reservas.ficha', $reserva) }}" target="_blank" rel="noopener">
                <i class="ri-file-list-3-line me-1"></i> Ficha
              </a>
              <button type="button" class="btn btn-outline-primary btn-sm" onclick="abrirModalAsociarComprobanteReserva({{ (int) $reserva->idreserva }}, {{ number_format($pendiente, 2, '.', '') }});">
                <i class="ri-link-m me-1"></i> Asociar comprobante
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-xl-7">
      <div class="d-flex align-items-center gap-2 mb-2">
        <i class="ri-map-pin-line"></i>
        <h6 class="mb-0 fw-semibold">Datos de tours</h6>
      </div>
      <div class="table-responsive bg-white rounded-2 shadow-sm">
        <table class="table table-striped table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Nombre tours</th>
              <th>N° PAX</th>
              <th>Vehiculo</th>
              <th>Turno</th>
              <th>F. asignada</th>
              <th>Estado</th>
              <th class="text-end">Total</th>
              <th>Obs.</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($reserva->detalles as $detalle)
              <tr>
                <td class="fw-semibold">{{ $detalle->nombre_tours }}</td>
                <td>{{ $detalle->nro_pax }}</td>
                <td><span class="badge bg-primary">{{ $detalle->vehiculo ?: 'Compartido' }}</span></td>
                <td>{{ $detalle->turno?->descripcion ?? $detalle->idtours_turno ?? '-' }}</td>
                <td>{{ $fechaReservaDetalle($detalle->fecha_tours) }}</td>
                <td><span class="badge bg-success-transparent">Realizado</span></td>
                <td class="text-end text-primary">S/ {{ number_format((float) $detalle->subtotal, 2) }}</td>
                <td>
                  <button type="button" class="btn btn-info btn-sm">Traspasar »</button>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-4">Sin tours registrados</td></tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <th colspan="6" class="text-end">Total:</th>
              <th class="text-end text-primary">S/ {{ number_format($totalTours, 2) }}</th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="d-flex align-items-center gap-2 mb-2">
        <i class="ri-hotel-line"></i>
        <h6 class="mb-0 fw-semibold">Datos reservas de alojamiento</h6>
      </div>
      <div class="table-responsive bg-white rounded-2 shadow-sm">
        <table class="table table-striped table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Nombre Hotel</th>
              <th>Fecha</th>
              <th>Detalle Hab.</th>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            @forelse($reserva->hoteles as $hotel)
              <tr>
                <td class="fw-semibold">{{ $hotel->habitacion?->hotel?->persona?->descripcion ?? '-' }}</td>
                <td>
                  <span class="badge bg-info">{{ $fechaReservaDetalle($hotel->fecha_check_in) }}</span>
                  <span class="badge bg-info">{{ $fechaReservaDetalle($hotel->fecha_check_out) }}</span>
                </td>
                <td>
                  Tipo Hab: <strong>{{ $hotel->nombre_habitacion ?: '-' }}</strong>
                  <div class="fs-11 text-muted">{{ $hotel->nro_pax ?? 0 }} pax - {{ $hotel->nro_noches ?? 0 }} noche(s)</div>
                </td>
                <td class="text-end text-primary">S/ {{ number_format((float) $hotel->precio + (float) ($hotel->adicional ?? 0), 2) }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted py-4">Sin habitaciones registradas</td></tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <th colspan="3" class="text-end">Total:</th>
              <th class="text-end text-primary">S/ {{ number_format($totalHotel, 2) }}</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="col-xl-7">
      <div class="d-flex align-items-center gap-2 mb-2">
        <i class="ri-bank-card-line"></i>
        <h6 class="mb-0 fw-semibold">Datos de Pagos</h6>
      </div>
      <div class="table-responsive bg-white rounded-2 shadow-sm">
        <table class="table table-striped table-hover align-middle mb-0 reserva-pagos-table">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Tipo Comprobante</th>
              <th>Numero</th>
              <th>Fecha Creacion</th>
              <th>Nombres</th>
              <th class="text-end">Total</th>
              <th>Obs.</th>
              <th class="text-center">Estado SUNAT</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pagos as $index => $pago)
              @php
                $codigoPago = trim((string) ($pago->tipoComprobanteSunat?->codigo ?: $pago->tipo_comprobante));
                $estadoSunatPago = mb_strtoupper(trim((string) ($pago->sunat_estado ?: '')));
                $esFacturaBoleta = in_array($codigoPago, ['01', '03'], true);
                $comprobantePago = trim(($pago->serie_comprobante ?? '') . '-' . ($pago->numero_comprobante ?? ''));
                $montoAplicadoPago = (float) ($pago->reserva_monto_aplicado ?? $pago->venta_total);
                $tipoRelacionPago = (string) ($pago->reserva_tipo_pago ?? 'pago');
                $esAsociacionPago = $tipoRelacionPago === 'asociacion';
                $puedeEditarPago = ! $esAsociacionPago && (! $esFacturaBoleta || in_array($estadoSunatPago, ['EMITIDO', 'EMITIDA', 'POR ENVIAR'], true));
              @endphp
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $pago->tipoComprobanteSunat?->abreviatura ?? $pago->tipo_comprobante ?? '-' }}</td>
                <td>{{ $comprobantePago }}</td>
                <td>{{ $fechaReservaDetalle($pago->fecha_emision) }}</td>
                <td>{{ $pago->cliente?->descripcion ?? '-' }}</td>
                <td class="text-end text-primary">
                  S/ {{ number_format($montoAplicadoPago, 2) }}
                  @if(abs($montoAplicadoPago - (float) $pago->venta_total) > 0.009)
                    <div class="fs-11 text-muted">Doc: S/ {{ number_format((float) $pago->venta_total, 2) }}</div>
                  @endif
                </td>
                <td>
                  <span class="badge {{ $tipoRelacionPago === 'asociacion' ? 'bg-info-transparent' : 'bg-success-transparent' }}">
                    {{ $tipoRelacionPago === 'asociacion' ? 'Asociado' : 'Pago' }}
                  </span>
                  <div class="fs-11">{{ $pago->observacion_documento ?: '-' }}</div>
                </td>
                <td class="text-center">{!! $badgeEstadoSunatReserva($pago->sunat_estado) !!}</td>
                <td class="text-center">
                  <div class="btn-list d-inline-flex gap-1">
                    <button type="button" class="btn btn-sm btn-icon btn-primary-light" onclick="abrirModalImpresionReserva({{ (int) $pago->idrdocumento }}, @js($comprobantePago));" title="Imprimir comprobante">
                      <i class="ri-printer-line"></i>
                    </button>
                    @if($esAsociacionPago)
                      <button type="button" class="btn btn-sm btn-icon btn-danger-light" onclick="eliminarPagoReserva({{ (int) $pago->idrdocumento }});" title="Quitar asociacion">
                        <i class="ri-link-unlink-m"></i>
                      </button>
                    @elseif($puedeEditarPago)
                      <button type="button" class="btn btn-sm btn-icon btn-warning-light" onclick="editarPagoReserva({{ (int) $pago->idrdocumento }});" title="Editar pago">
                        <i class="ri-edit-line"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-icon btn-danger-light" onclick="eliminarPagoReserva({{ (int) $pago->idrdocumento }});" title="Eliminar pago">
                        <i class="ri-delete-bin-line"></i>
                      </button>
                    @else
                      <button type="button" class="btn btn-sm btn-icon btn-info-light" onclick="abrirModalImpresionReserva({{ (int) $pago->idrdocumento }}, @js($comprobantePago));" title="Ver comprobante">
                        <i class="ri-eye-line"></i>
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="9" class="text-center text-muted py-4">Sin pagos registrados</td></tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <th colspan="5" class="text-end">Total:</th>
              <th class="text-end text-primary">S/ {{ number_format($totalPagado, 2) }}</th>
              <th></th>
              <th></th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-4">
    <button type="button" class="btn btn-danger" onclick="show_hide_form(1);">
      <i class="ri-close-line me-1"></i> Cancelar
    </button>
  </div>
</div>
