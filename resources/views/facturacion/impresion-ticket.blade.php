<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $nombreTipo }} - {{ $comprobante }}</title>
  <style>
    @page { margin: 0; size: 80mm auto; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    body { margin: 0; background-color: white; display: flex; justify-content: center; align-items: flex-start; font-family: Arial, Helvetica, sans-serif; color: #111; }
    .tm_hide_print { margin-right: 6px; padding-top: 5px; }
    .tool-btn { width: 40px; height: 40px; margin-bottom: 5px; cursor: pointer; border: 1px solid #d7dbe0; border-radius: 4px; background: #fff; display: flex; align-items: center; justify-content: center; }
    .tool-btn:hover { background: #f4f4f5; }
    .lds-spinner { display: block; width: 24px; height: 24px; }
    #iframe-img-descarga { background-color: #f0f1f7; border-radius: 5px; padding-top: 1px; }
    .ticket-table { font-size: 12px; }
    .dotted { border-bottom: 1px dotted black; margin-top: 8px; margin-bottom: 8px; }
    @media print {
      body { display: block; }
      .tm_hide_print { display: none !important; }
      #iframe-img-descarga { background: #fff; border-radius: 0; padding-top: 0; }
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
@endphp

@unless($modoPdf ?? false)
  <div class="tm_hide_print">
    <button type="button" class="tool-btn" onclick="window.print()" title="Imprimir ticket">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#08a62f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4"/><path d="M7 13h10v8H7z"/></svg>
    </button>
    <button type="button" class="tool-btn" id="btn-descargar" onclick="descargar_imagen()" title="Descargar imagen">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#c76a00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 8h.01"/><path d="M12.5 21H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v6.5"/><path d="m3 16 5-5 4 4"/><path d="M19 16v6"/><path d="m22 19-3 3-3-3"/></svg>
    </button>
    <button type="button" class="tool-btn" id="btn-compartir" onclick="compartir_imagen()" title="Compartir imagen">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#014cbc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="12" r="3"/><circle cx="18" cy="6" r="3"/><circle cx="18" cy="18" r="3"/><path d="m8.7 10.7 6.6-3.4"/><path d="m8.7 13.3 6.6 3.4"/></svg>
    </button>
    <a class="tool-btn" href="{{ url()->current() }}-pdf" target="_blank" title="Descargar PDF">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#990000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 12V5a2 2 0 0 1 2-2h7l5 5v4"/><path d="M5 18h1.5a1.5 1.5 0 0 0 0-3H5v6"/><path d="M17 18h2"/><path d="M20 15h-3v6"/><path d="M11 15v6h1a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2z"/></svg>
    </a>
  </div>
@endunless

<div id="iframe-img-descarga">
  <br>
  <table class="ticket-table" border="0" align="center" width="300">
    <tbody>
      <tr><td align="center"><img src="{{ $logo }}" width="{{ (int)($empresa?->logo_c_r ?? 0) === 0 ? 150 : 100 }}" alt="Logo"></td></tr>
      <tr align="center"><td style="font-size: 14px">.::<strong>{{ $empresa?->nombre_comercial ?: $empresa?->nombre_razon_social }}</strong>::.</td></tr>
      <tr align="center"><td style="font-size: 10px">{{ $empresa?->nombre_razon_social }}</td></tr>
      <tr align="center"><td style="font-size: 14px"><strong>R.U.C. {{ $empresa?->numero_documento }}</strong></td></tr>
      <tr align="center"><td style="font-size: 10px">{!! e($empresa?->domicilio_fiscal) !!}<br>{{ $empresa?->telefono1 }}{{ $empresa?->telefono2 ? '-' . $empresa->telefono2 : '' }}</td></tr>
      <tr align="center"><td style="font-size: 10px">{{ $empresa?->correo }}</td></tr>
      <tr align="center"><td style="font-size: 10px">{{ $empresa?->web }}</td></tr>
      <tr><td><div class="dotted"></div></td></tr>
      <tr><td align="center"><strong style="font-size: 14px">{{ $nombreTipo }}{{ $codigoTipo !== '12' ? ' ELECTRONICA' : '' }}</strong><br><b style="font-size: 14px">{{ $comprobante }}</b></td></tr>
      <tr><td><div class="dotted"></div></td></tr>
    </tbody>
  </table>

  <table class="ticket-table" border="0" align="center" width="300">
    <tbody>
      <tr><td><strong>Emision:</strong> {{ $fechaEmisionDmy }}</td><td><strong>Hora:</strong> {{ $fechaEmisionHora }}</td></tr>
      <tr><td colspan="2"><strong>Cliente:</strong> {{ $clienteNombre }}</td></tr>
      <tr><td colspan="2"><strong>DNI/RUC:</strong> {{ $cliente?->numero_documento }}</td></tr>
      <tr><td colspan="2"><strong>Dir.:</strong> {{ $cliente?->direccion }}</td></tr>
      @if($codigoTipo === '07')
        <tr><td colspan="2"><strong>Doc. Baja:</strong> {{ $documento->nc_serie_y_numero }}</td></tr>
      @endif
      <tr><td colspan="2"><strong>Atencion:</strong> {{ $usuarioCodigo }}</td></tr>
      <tr><td colspan="2"><strong>Observacion:</strong> {{ $documento->observacion_documento }}</td></tr>
    </tbody>
  </table>

  <table class="ticket-table" border="0" align="center" width="300">
    <thead>
      <tr><td colspan="4"><div class="dotted"></div></td></tr>
      <tr><th style="text-align:center;">Cant.</th><th style="text-align:center;">Descripcion</th><th style="text-align:center;">P.U.</th><th style="text-align:center;">Importe</th></tr>
      <tr><td colspan="4"><div class="dotted"></div></td></tr>
    </thead>
    <tbody style="font-size: 11px;">
      @foreach($detalles as $detalle)
        <tr><td colspan="4" style="font-size: 11px">{{ $detalle['descripcion'] }}</td></tr>
        <tr>
          <td style="text-align: center;">{{ rtrim(rtrim(number_format($detalle['cantidad'], 2, '.', ''), '0'), '.') }}</td>
          <td></td>
          <td style="text-align: center;">{{ number_format($detalle['precio_venta'], 2, '.', ',') }}</td>
          <td style="text-align: right;">{{ number_format($detalle['subtotal_no_descuento'], 2, '.', ',') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table border="0" align="center" width="300" style="font-size: 12px"><tr><td><div class="dotted"></div></td></tr></table>

  <table border="0" align="center" width="300" style="font-size: 12px">
    <tr><td style="text-align: right;"><strong>Subtotal</strong></td><td>:</td><td style="text-align: right;">{{ $subtotalNoDcto }}</td></tr>
    <tr><td style="text-align: right;"><strong>Descuento</strong></td><td>:</td><td style="text-align: right;">{{ $ventaDescuento }}</td></tr>
    <tr><td style="text-align: right;"><strong>Op. Exonerado</strong></td><td>:</td><td style="text-align: right;">{{ $ventaSubtotal }}</td></tr>
    <tr><td style="text-align: right;"><strong>IGV ({{ number_format((float) $documento->impuesto, 2) }}%)</strong></td><td>:</td><td style="text-align: right;">{{ $ventaIgv }}</td></tr>
    <tr><td style="text-align: right;"><strong>TOTAL</strong></td><td>:</td><td style="text-align: right;"><strong>{{ $ventaTotal }}</strong></td></tr>
  </table>

  <table border="0" align="center" width="300" style="font-size: 12px">
    <tr><td colspan="3"><div class="dotted"></div></td></tr>
    <tr><td colspan="3"><b>Son: </b>{{ $totalEnLetras }}</td></tr>
    <tr><td colspan="3"><div class="dotted"></div></td></tr>
    @if($codigoTipo !== '07')
      @foreach($metodosPago as $metodo)
        <tr><td><b>{{ $metodo['nombre'] }}</b></td><td>:</td><td>{{ number_format($metodo['monto'], 2, '.', ',') }}</td></tr>
        @if($metodo['voucher'])
          <tr><td><b>Nro. Baucher</b></td><td>:</td><td>{{ $metodo['voucher'] }}</td></tr>
        @endif
        <tr><td colspan="3"><div style="margin-bottom: 5px;"></div></td></tr>
      @endforeach
      <tr><td><b>VUELTO</b></td><td>:</td><td>{{ $totalVuelto }}</td></tr>
    @endif
    <tr><td colspan="3"><div class="dotted"></div></td></tr>
    <tr><td><b>Nro. Operacion</b></td><td>:</td><td>{{ $documento->idrdocumento }}</td></tr>
  </table>

  <table border="0" align="center" width="300" style="font-size: 12px">
    <tbody>
      <tr>
        <td>
          @if($qrDataUri)
            <img src="{{ $qrDataUri }}" width="100" height="100" alt="QR">
          @endif
        </td>
        <td style="font-size: 11px;">
          <span>Autorizado mediante resolucion Nro: 182-2016/SUNAT Representacion impresa del comprobante de venta electronico, puede ser consultada en:</span>
          <span><b>{{ $empresa?->web }}</b></span>
          <span style="font-size: 10px; margin-top: 5px;">Hash: {{ $documento->sunat_hash }}</span>
        </td>
      </tr>
      <tr><td style="font-size: 10px; text-align: center;" colspan="2"><strong>SERVICIOS TRANSFERIDOS EN LA REGION AMAZONICA SELVA PARA SER CONSUMIDOS EN LA MISMA.</strong></td></tr>
      <tr><td style="font-size: 8px; text-align: center;" colspan="2">Ver comprobante en <strong>{{ $empresa?->web }}</strong></td></tr>
    </tbody>
  </table>
  <p>&nbsp;</p>
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
