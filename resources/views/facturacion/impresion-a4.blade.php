<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $nombreArchivo }}</title>
  <style>
    @page { size: A4; margin: 8mm; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    body { background: #fff !important; margin: 0; font-family: Arial, Helvetica, sans-serif; color: #5f5f5f; }
    .screen-wrap { display: flex; justify-content: center; align-items: flex-start; gap: 8px; padding: 8px; }
    .tm_hide_print { flex: 0 0 44px; }
    .tool-btn { width: 40px; height: 40px; margin-bottom: 5px; cursor: pointer; border: 1px solid #d7dbe0; border-radius: 4px; background: #fff; display: inline-flex; align-items: center; justify-content: center; }
    .tool-btn:hover { background: #f4f4f5; }
    .lds-spinner { display: block; width: 24px; height: 24px; }
    .document { background-color: #fff; font-size: 11px; width: 780px !important; border-radius: 5px; }
    .document-inner { padding: 10px 12px; }
    .top-table, .info-table, .condition-table, .items-table, .bottom-table, .totals-table { width: 100%; border-collapse: collapse; }
    .logo-cell { width: 190px; vertical-align: middle; }
    .logo-box { height: 95px; background-repeat: no-repeat; background-position: left center; background-size: contain; }
    .company-cell { vertical-align: middle; line-height: 1.42; font-size: 10px; }
    .company-name { color: #555; font-weight: 700; letter-spacing: .1px; }
    .document-header { width: 310px; vertical-align: middle; }
    .document-header-box { border: 2px solid #bdbdbd; border-radius: 22px; text-align: center; padding: 22px 12px 20px; color: #626262; line-height: 1.5; overflow: hidden; }
    .document-ruc { font-size: 11px; margin-bottom: 12px; }
    .document-title { font-size: 16px; font-weight: 700; text-transform: uppercase; margin-bottom: 14px; }
    .document-number { font-size: 12px; }
    .rounded-box { border: 2px solid #bdbdbd; border-radius: 18px; overflow: hidden; }
    .client-box { margin-top: 10px; padding: 8px 16px; min-height: 96px; }
    .client-left { width: 58%; vertical-align: top; }
    .client-middle { width: 23%; vertical-align: top; }
    .client-right { width: 19%; vertical-align: top; }
    .label { color: #555; font-weight: 700; white-space: nowrap; }
    .value { color: #5f5f5f; }
    .info-table td { padding: 4px 0; vertical-align: top; }
    .condition-box { margin-top: 5px; }
    .condition-table td { width: 20%; border-left: 2px solid #bdbdbd; text-align: center; padding: 7px 5px 8px; line-height: 1.45; }
    .condition-table td:first-child { border-left: 0; }
    .condition-label { display: block; color: #555; font-weight: 700; font-size: 10px; }
    .condition-value { display: block; color: #666; font-size: 11px; margin-top: 4px; }
    .items-table { margin-top: 8px; table-layout: fixed; }
    .items-table th { background: #aaa; color: #fff; font-weight: 400; text-align: center; padding: 6px 5px; border: 1px solid #bdbdbd; }
    .items-table td { border: 1px solid #c8c8c8; padding: 6px 5px; color: #5f5f5f; vertical-align: top; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .description-cell { word-break: normal; overflow-wrap: break-word; }
    .bottom-table { margin-top: 28px; }
    .observations-cell { width: 64%; vertical-align: top; padding-right: 18px; }
    .totals-cell { width: 36%; vertical-align: top; }
    .observations-title { color: #555; font-weight: 700; margin-bottom: 8px; }
    .observations-text { min-height: 120px; white-space: pre-wrap; }
    .letters-box { margin-top: 14px; line-height: 1.45; }
    .qr-box { margin-top: 12px; }
    .qr-box img { width: 92px; height: 92px; }
    .totals-table { border-collapse: separate; border-spacing: 7px 4px; }
    .totals-table td { height: 26px; padding: 5px 8px; }
    .total-label { width: 52%; background: #aaa; color: #fff; text-align: center; border-radius: 7px; }
    .total-value { width: 48%; border: 2px solid #bdbdbd; border-radius: 7px; text-align: right; color: #5f5f5f; }
    .grand-total-label { background: #456b4e; color: #fff; }
    .grand-total-value { border-color: #456b4e; color: #456b4e; }
    .footer-note { text-align: center; font-size: 10px; margin-top: 18px; color: #666; }
    @media print {
      body { background: #fff !important; }
      .screen-wrap { display: block; padding: 0; }
      .tm_hide_print { display: none !important; }
      .document { width: 100% !important; border-radius: 0; }
      .document-inner { padding: 0; }
    }
  </style>
</head>
<body>
@php
  $logo = ($modoPdf ?? false) && $logoDataUri ? $logoDataUri : $logoUrl;
  $subtotalNoDcto = number_format((float) $subtotalNoDescuento, 2, '.', ',');
  $ventaSubtotal = number_format((float) $documento->venta_subtotal, 2, '.', ',');
  $ventaDescuento = number_format((float) $documento->venta_descuento, 2, '.', ',');
  $ventaIgv = number_format((float) $documento->venta_igv, 2, '.', ',');
  $ventaTotal = number_format((float) $documento->venta_total, 2, '.', ',');
  $totalVuelto = number_format((float) $documento->total_vuelto, 2, '.', ',');
  $moneda = 'SOLES';
  $igvPorcentaje = (float) ($empresa?->igv ?? 18);
  $formaPago = $codigoTipo === '07' ? '-' : 'Contado';
  $empresaCiudad = collect([$empresa?->distrito, $empresa?->provincia, $empresa?->departamento])->filter()->implode(' - ');
@endphp
<div class="screen-wrap">
  @unless($modoPdf ?? false)
    <div class="tm_hide_print">
      <button type="button" class="tool-btn" onclick="window.print()" title="Imprimir A4">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#08a62f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4"/><path d="M7 13h10v8H7z"/></svg>
      </button>
      <button type="button" class="tool-btn" id="btn-descargar" onclick="descargar_imagen()" title="Descargar imagen">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#c76a00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 8h.01"/><path d="M12.5 21H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v6.5"/><path d="m3 16 5-5 4 4"/><path d="M19 16v6"/><path d="m22 19-3 3-3-3"/></svg>
      </button>
      <button type="button" class="tool-btn" id="btn-compartir" onclick="compartir_imagen()" title="Compartir imagen">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#014cbc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="12" r="3"/><circle cx="18" cy="6" r="3"/><circle cx="18" cy="18" r="3"/><path d="m8.7 10.7 6.6-3.4"/><path d="m8.7 13.3 6.6 3.4"/></svg>
      </button>
      <a class="tool-btn" id="btn-descargar-pdf" href="{{ url()->current() }}-pdf" target="_blank" title="Descargar PDF">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#990000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 12V5a2 2 0 0 1 2-2h7l5 5v4"/><path d="M5 18h1.5a1.5 1.5 0 0 0 0-3H5v6"/><path d="M17 18h2"/><path d="M20 15h-3v6"/><path d="M11 15v6h1a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2z"/></svg>
      </a>
    </div>
  @endunless

  <div id="iframe-img-descarga" class="document">
    <div class="document-inner">
      <table class="top-table">
        <tr>
          <td class="logo-cell">
            <div class="logo-box" style="background-image: url('{{ $logo }}');"></div>
          </td>
          <td class="company-cell">
            <div class="company-name">{{ $empresa?->nombre_razon_social ?: '-' }}</div>
            <div>{{ $empresa?->domicilio_fiscal ?: '-' }}</div>
            <br>
            <div>{{ $empresaCiudad }}</div>
          </td>
          <td class="document-header">
            <div class="document-header-box">
              <div class="document-ruc">RUC: {{ $empresa?->numero_documento ?: '-' }}</div>
              <div class="document-title">{{ $nombreTipo }}{{ $codigoTipo !== '12' ? ' ELECTRONICA' : '' }}</div>
              <div class="document-number">Nro. {{ $comprobante }}</div>
            </div>
          </td>
        </tr>
      </table>

      <div class="rounded-box client-box">
        <table class="info-table">
          <tr>
            <td class="client-left">
              <table class="info-table">
                <tr><td class="label" style="width: 76px;">Cliente:</td><td class="value">{{ $clienteNombre }}</td></tr>
                <tr><td class="label">RUC:</td><td class="value">{{ $cliente?->numero_documento ?: '-' }}</td></tr>
                <tr><td class="label">Direccion:</td><td class="value">{{ $cliente?->direccion ?: '-' }}</td></tr>
                <tr><td class="label">Ciudad:</td><td class="value">-</td></tr>
              </table>
            </td>
            <td class="client-middle">
              <table class="info-table">
                <tr><td class="label" style="width: 70px;">Moneda:</td><td class="value">{{ $moneda }}</td></tr>
              </table>
            </td>
            <td class="client-right">
              <table class="info-table">
                <tr><td class="label" style="width: 44px;">IGV:</td><td class="value">{{ number_format($igvPorcentaje, 2, '.', '') }} %</td></tr>
              </table>
            </td>
          </tr>
        </table>
      </div>

      <div class="rounded-box condition-box">
        <table class="condition-table">
          <tr>
            <td><span class="condition-label">Fecha de Emision:</span><span class="condition-value">{{ $fechaEmisionDmy ?: $fechaEmisionFormato }}</span></td>
            <td><span class="condition-label">Forma de Pago:</span><span class="condition-value">{{ $formaPago }}</span></td>
            <td><span class="condition-label">Orden de Compra:</span><span class="condition-value">-</span></td>
            <td><span class="condition-label">Fecha de Vencimiento:</span><span class="condition-value">-</span></td>
            <td><span class="condition-label">N Guia de Remision:</span><span class="condition-value">-</span></td>
          </tr>
        </table>
      </div>

      <table role="grid" class="items-table">
        <thead>
          <tr>
            <th style="width: 11%;">CODIGO</th>
            <th style="width: 7%;">CANT.</th>
            <th style="width: 7%;">UNID.</th>
            <th style="width: 40%;">DESCRIPCION</th>
            <th style="width: 11%;">V. UNIT.</th>
            <th style="width: 11%;">DSCTO.</th>
            <th style="width: 13%;">V. VENTA</th>
          </tr>
        </thead>
        <tbody>
          @foreach($detalles as $detalle)
            <tr class="item-list">
              <td class="text-center">{{ $detalle['codigo'] ?: '-' }}</td>
              <td class="text-center">{{ rtrim(rtrim(number_format($detalle['cantidad'], 2, '.', ''), '0'), '.') }}</td>
              <td class="text-center">{{ $detalle['unidad'] }}</td>
              <td class="description-cell">{{ $detalle['descripcion'] }}</td>
              <td class="text-right">{{ number_format($detalle['precio_venta'], 2, '.', ',') }}</td>
              <td class="text-right">{{ number_format($detalle['descuento'], 2, '.', ',') }}</td>
              <td class="text-right">{{ number_format($detalle['subtotal'], 2, '.', ',') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <table class="bottom-table">
        <tr>
          <td class="observations-cell">
            <div class="observations-title">OBSERVACIONES</div>
            <div class="observations-text">{{ $documento->observacion_documento }}</div>
            <div class="letters-box"><span class="label">IMPORTE EN LETRAS:</span> {{ $totalEnLetras }}</div>
            @if($qrDataUri)
              <div class="qr-box">
                <img src="{{ $qrDataUri }}" alt="QR">
                <div>{{ $documento->sunat_hash }}</div>
              </div>
            @endif
          </td>
          <td class="totals-cell">
            <table class="totals-table">
              <tr><td class="total-label">Subtotal</td><td class="total-value">S/ {{ $subtotalNoDcto }}</td></tr>
              <tr><td class="total-label">Descuento</td><td class="total-value">S/ {{ $ventaDescuento }}</td></tr>
              <tr><td class="total-label">OP. Exonerada</td><td class="total-value">S/ {{ $ventaSubtotal }}</td></tr>
              <tr><td class="total-label">I.G.V</td><td class="total-value">S/ {{ $ventaIgv }}</td></tr>
              <tr><td class="total-label grand-total-label">TOTAL</td><td class="total-value grand-total-value">S/ {{ $ventaTotal }}</td></tr>
            </table>
          </td>
        </tr>
      </table>

      <div class="footer-note">
        Representacion impresa de la <b style="text-transform: lowercase;">{{ $nombreTipo }}</b> electronica. Consulte su documento en <strong>{{ $empresa?->web }}</strong>
        @if($codigoTipo !== '07')
          <br>Forma de pago: {{ $formaPago }}
          @foreach($metodosPago as $metodo)
            | {{ $metodo['nombre'] }}: S/ {{ number_format($metodo['monto'], 2, '.', ',') }}
          @endforeach
          | Vuelto: S/ {{ $totalVuelto }}
        @endif
      </div>
    </div>
  </div>
</div>

@unless($modoPdf ?? false)
  <script src="https://cdn.jsdelivr.net/npm/dom-to-image-more@3.5.0/dist/dom-to-image-more.min.js"></script>
  <script>
    const nombreArchivo = @json($nombreArchivo);
    const spinnerSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="lds-spinner"><circle cx="50" cy="50" fill="none" stroke="#000" stroke-width="10" r="35" stroke-dasharray="164 57"><animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" values="0 50 50;360 50 50"></animateTransform></circle></svg>';

    function descargar_imagen() {
      const btn = document.getElementById('btn-descargar');
      const old = btn.innerHTML;
      btn.innerHTML = spinnerSVG;
      domtoimage.toJpeg(document.getElementById('iframe-img-descarga'), { quality: 0.95 }).then(function (dataUrl) {
        const link = document.createElement('a');
        link.download = nombreArchivo + '.jpeg';
        link.href = dataUrl;
        link.click();
        btn.innerHTML = old;
      }).catch(function () { btn.innerHTML = old; });
    }

    function compartir_imagen() {
      const btn = document.getElementById('btn-compartir');
      const old = btn.innerHTML;
      btn.innerHTML = spinnerSVG;
      domtoimage.toBlob(document.getElementById('iframe-img-descarga'), { quality: 0.95 }).then(function (blob) {
        const file = new File([blob], nombreArchivo + '.png', { type: blob.type });
        if (navigator.canShare && navigator.canShare({ files: [file] })) {
          return navigator.share({ title: 'Comprobante: ' + document.title, text: 'Guarda este comprobante en un lugar seguro.', files: [file] });
        }
        alert('La API de compartir no es soportada en este navegador.');
      }).finally(function () { btn.innerHTML = old; });
    }
  </script>
@endunless
</body>
</html>
