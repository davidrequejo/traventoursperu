@php
  $cliente = $reserva->cliente;
  $pagos = $pagos ?? collect();
  $totalReserva = (float) ($reserva->total_reserva ?? 0);
  $totalTours = (float) $reserva->detalles->sum(fn ($detalle) => (float) $detalle->subtotal);
  $totalHotel = (float) $reserva->hoteles->sum(fn ($hotel) => (float) $hotel->precio + (float) ($hotel->adicional ?? 0));
  $totalPagado = (float) $pagos->sum(fn ($pago) => (float) $pago->venta_total);
  $pendiente = max($totalReserva - $totalPagado, 0);
  $estadoReserva = $pendiente > 0 ? 'Pendiente' : 'Confirmado';
  $fechaFicha = function ($fecha) {
      if (! $fecha) {
          return '-';
      }

      return \Illuminate\Support\Carbon::parse($fecha)->locale('es')->translatedFormat('d M Y');
  };
  $horaFicha = fn ($hora) => $hora ? substr((string) $hora, 0, 5) : '-';
  $moneda = fn ($valor) => 'S/ ' . number_format((float) $valor, 2);
  $texto = fn ($valor, $fallback = '-') => trim((string) ($valor ?? '')) !== '' ? $valor : $fallback;
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ficha de reserva {{ $reserva->serie_numero ?? $reserva->idreserva }}</title>
  <style>
    @page { size: A4; margin: 10mm; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { margin: 0; background: #eef2f6; color: #1d2433; font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
    .screen { padding: 14px; }
    .sheet { width: 794px; min-height: 1123px; margin: 0 auto; background: #fff; border: 1px solid #d9e0e8; box-shadow: 0 8px 24px rgba(30, 41, 59, .12); }
    .toolbar { width: 794px; margin: 0 auto 8px; display: flex; justify-content: flex-end; gap: 8px; }
    .btn { border: 1px solid #ccd5df; background: #fff; color: #1d2433; border-radius: 4px; padding: 8px 12px; font-weight: 700; font-size: 12px; cursor: pointer; text-decoration: none; }
    .btn-primary { background: #2f80ed; border-color: #2f80ed; color: #fff; }
    .content { padding: 22px; }
    .header { display: grid; grid-template-columns: 1fr 250px; gap: 16px; align-items: stretch; border-bottom: 2px solid #203b68; padding-bottom: 14px; }
    .brand { display: flex; gap: 14px; align-items: center; }
    .logo { width: 150px; height: 76px; object-fit: contain; border: 1px solid #d6dde6; border-radius: 5px; padding: 8px; }
    .company h1 { margin: 0 0 6px; font-size: 18px; text-transform: uppercase; color: #15294d; }
    .company p { margin: 2px 0; color: #5c6675; }
    .doc-box { border: 2px solid #203b68; border-radius: 6px; text-align: center; padding: 12px; }
    .doc-box h2 { margin: 4px 0 8px; font-size: 20px; color: #203b68; text-transform: uppercase; }
    .doc-box .code { font-size: 16px; font-weight: 700; }
    .section { margin-top: 16px; }
    .section-title { background: #203b68; color: #fff; padding: 7px 10px; font-size: 12px; font-weight: 700; text-transform: uppercase; border-radius: 4px 4px 0 0; }
    .box { border: 1px solid #d8e0ea; border-top: 0; padding: 10px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .field { display: grid; grid-template-columns: 120px 1fr; gap: 8px; margin: 5px 0; }
    .label { color: #64748b; font-weight: 700; }
    .value { color: #111827; }
    .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .metric { border: 1px solid #d8e0ea; border-radius: 5px; padding: 10px; }
    .metric .name { color: #64748b; font-weight: 700; margin-bottom: 6px; }
    .metric .amount { font-size: 20px; font-weight: 700; color: #203b68; }
    .metric.pending .amount { color: #cf3a2f; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f0f3f7; color: #24324a; font-size: 11px; text-align: left; padding: 8px; border: 1px solid #d8e0ea; }
    td { padding: 8px; border: 1px solid #d8e0ea; vertical-align: top; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }
    .badge { display: inline-block; border-radius: 4px; padding: 3px 7px; font-size: 10px; font-weight: 700; background: #e8f5ef; color: #147653; }
    .badge-warning { background: #fff4d8; color: #946200; }
    .muted { color: #64748b; font-size: 11px; }
    .footer { margin-top: 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: end; }
    .sign { border-top: 1px solid #9aa4b2; text-align: center; padding-top: 7px; color: #64748b; }
    @media print {
      body { background: #fff; }
      .screen { padding: 0; }
      .toolbar { display: none; }
      .sheet { width: auto; min-height: auto; margin: 0; border: 0; box-shadow: none; }
      .content { padding: 0; }
    }
  </style>
</head>
<body>
  <div class="screen">
    <div class="toolbar">
      <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir</button>
      <button type="button" class="btn" onclick="window.close()">Cerrar</button>
    </div>

    <main class="sheet">
      <div class="content">
        <header class="header">
          <div class="brand">
            <img class="logo" src="{{ $logoUrl }}" alt="Logo">
            <div class="company">
              <h1>{{ $empresa?->nombre_comercial ?: $empresa?->nombre_razon_social ?: config('app.name') }}</h1>
              <p><strong>RUC:</strong> {{ $empresa?->numero_documento ?: '-' }}</p>
              <p>{{ $empresa?->domicilio_fiscal ?: '-' }}</p>
              <p>{{ $empresa?->telefono1 ?: '' }}{{ $empresa?->telefono2 ? ' - ' . $empresa->telefono2 : '' }} {{ $empresa?->correo ? ' | ' . $empresa->correo : '' }}</p>
            </div>
          </div>
          <div class="doc-box">
            <div>Ficha de Reserva</div>
            <h2>{{ $estadoReserva }}</h2>
            <div class="code">{{ $reserva->serie_numero ?? ('RE-' . str_pad((string) $reserva->idreserva, 6, '0', STR_PAD_LEFT)) }}</div>
          </div>
        </header>

        <section class="section">
          <div class="section-title">Datos del cliente</div>
          <div class="box grid-2">
            <div>
              <div class="field"><div class="label">Cliente</div><div class="value">{{ $texto($cliente?->descripcion) }}</div></div>
              <div class="field"><div class="label">Documento</div><div class="value">{{ $cliente?->docIdentidad?->abreviatura ?? $cliente?->docIdentidad?->nombre ?? 'Doc.' }} {{ $texto($cliente?->numero_documento) }}</div></div>
              <div class="field"><div class="label">Contacto</div><div class="value">{{ $texto($cliente?->celular ?: $cliente?->telefono) }}</div></div>
            </div>
            <div>
              <div class="field"><div class="label">Nro. pax</div><div class="value">{{ $reserva->nro_pasajeros ?? 0 }} persona(s)</div></div>
              <div class="field"><div class="label">Asesor(a)</div><div class="value">{{ $texto($reserva->trabajador?->descripcion) }}</div></div>
              <div class="field"><div class="label">Origen</div><div class="value">{{ $texto($reserva->origen?->descripcion ?? $reserva->origen?->nombre) }}</div></div>
            </div>
          </div>
        </section>

        <section class="section">
          <div class="section-title">Datos de llegada</div>
          <div class="box grid-2">
            <div>
              <div class="field"><div class="label">Llegada</div><div class="value">{{ $fechaFicha($reserva->fecha_llegada) }} {{ $horaFicha($reserva->hora_llegada) }}</div></div>
              <div class="field"><div class="label">Salida</div><div class="value">{{ $fechaFicha($reserva->fecha_salida) }}</div></div>
              <div class="field"><div class="label">Referencia</div><div class="value">{{ $texto($reserva->llegadaEmpresa?->nombre ?? $reserva->llegadaEmpresa?->descripcion) }}</div></div>
            </div>
            <div>
              <div class="field"><div class="label">Recojo</div><div class="value">{{ $texto($reserva->observacion_recojo) }}</div></div>
              <div class="field"><div class="label">Vuelo/ticket</div><div class="value">{{ $texto($reserva->vuelo_ticket) }}</div></div>
              <div class="field"><div class="label">Observacion</div><div class="value">{{ $texto($reserva->itinerario_general ?: $reserva->vuelo_observacion) }}</div></div>
            </div>
          </div>
        </section>

        <section class="section">
          <div class="summary">
            <div class="metric"><div class="name">Total reserva</div><div class="amount">{{ $moneda($totalReserva) }}</div></div>
            <div class="metric"><div class="name">Total pagado</div><div class="amount">{{ $moneda($totalPagado) }}</div></div>
            <div class="metric pending"><div class="name">Pendiente</div><div class="amount">{{ $moneda($pendiente) }}</div></div>
          </div>
        </section>

        <section class="section">
          <div class="section-title">Tours incluidos</div>
          <div class="box">
            <table>
              <thead>
                <tr>
                  <th>Tour</th>
                  <th>Fecha</th>
                  <th>Turno</th>
                  <th class="text-center">Pax</th>
                  <th>Vehiculo</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse($reserva->detalles as $detalle)
                  <tr>
                    <td><strong>{{ $texto($detalle->nombre_tours) }}</strong><div class="muted">{{ $texto($detalle->observacion, '') }}</div></td>
                    <td>{{ $fechaFicha($detalle->fecha_tours) }}</td>
                    <td>{{ $texto($detalle->turno?->descripcion ?? $detalle->idtours_turno) }}</td>
                    <td class="text-center">{{ $detalle->nro_pax ?? 0 }}</td>
                    <td>{{ $texto($detalle->vehiculo, 'Compartido') }}</td>
                    <td class="text-end">{{ $moneda($detalle->subtotal) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center muted">Sin tours registrados</td></tr>
                @endforelse
              </tbody>
              <tfoot>
                <tr><th colspan="5" class="text-end">Total tours</th><th class="text-end">{{ $moneda($totalTours) }}</th></tr>
              </tfoot>
            </table>
          </div>
        </section>

        <section class="section">
          <div class="section-title">Alojamiento</div>
          <div class="box">
            <table>
              <thead>
                <tr>
                  <th>Hotel</th>
                  <th>Fechas</th>
                  <th>Habitacion</th>
                  <th class="text-center">Noches</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse($reserva->hoteles as $hotel)
                  <tr>
                    <td><strong>{{ $texto($hotel->habitacion?->hotel?->persona?->descripcion) }}</strong></td>
                    <td>{{ $fechaFicha($hotel->fecha_check_in) }} al {{ $fechaFicha($hotel->fecha_check_out) }}</td>
                    <td>{{ $texto($hotel->nombre_habitacion) }}<div class="muted">{{ $hotel->nro_pax ?? 0 }} pax</div></td>
                    <td class="text-center">{{ $hotel->nro_noches ?? 0 }}</td>
                    <td class="text-end">{{ $moneda((float) $hotel->precio + (float) ($hotel->adicional ?? 0)) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center muted">Sin alojamiento registrado</td></tr>
                @endforelse
              </tbody>
              <tfoot>
                <tr><th colspan="4" class="text-end">Total alojamiento</th><th class="text-end">{{ $moneda($totalHotel) }}</th></tr>
              </tfoot>
            </table>
          </div>
        </section>

        <section class="section">
          <div class="section-title">Pagos registrados</div>
          <div class="box">
            <table>
              <thead>
                <tr>
                  <th>Comprobante</th>
                  <th>Fecha</th>
                  <th>Estado SUNAT</th>
                  <th>Obs.</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse($pagos as $pago)
                  @php
                    $comprobante = trim(($pago->serie_comprobante ?? '') . '-' . ($pago->numero_comprobante ?? ''));
                  @endphp
                  <tr>
                    <td>{{ $pago->tipoComprobanteSunat?->abreviatura ?? $pago->tipo_comprobante ?? '-' }} {{ $comprobante }}</td>
                    <td>{{ $fechaFicha($pago->fecha_emision) }}</td>
                    <td><span class="badge {{ mb_strtoupper((string) $pago->sunat_estado) === 'POR ENVIAR' ? 'badge-warning' : '' }}">{{ $texto($pago->sunat_estado) }}</span></td>
                    <td>{{ $texto($pago->observacion_documento) }}</td>
                    <td class="text-end">{{ $moneda($pago->venta_total) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center muted">Sin pagos registrados</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <div class="footer">
          <div class="muted">Generado: {{ now('America/Lima')->locale('es')->translatedFormat('d M Y H:i') }}</div>
          <div class="sign">Firma / conformidad</div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
