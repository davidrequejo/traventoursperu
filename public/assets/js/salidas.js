const SalidasState = {
  fecha: document.body.dataset.fechaActual || new Date().toISOString().slice(0, 10),
  items: [],
};

function salidasBaseUrl(path = '') {
  return `${document.querySelector('meta[name="app-url"]')?.content || ''}/salidas${path}`;
}

function salidasEscape(valor) {
  return $('<div>').text(valor ?? '').html();
}

function salidasFechaSumar(fecha, dias) {
  const date = new Date(`${fecha}T00:00:00`);
  date.setDate(date.getDate() + dias);
  return date.toISOString().slice(0, 10);
}

$(function () {
  $('#salidas-fecha').val(SalidasState.fecha);
  cargarSalidas();

  $('#salidas-fecha').on('change', function () {
    SalidasState.fecha = this.value || SalidasState.fecha;
    cargarSalidas();
  });

  $('#btn-salidas-hoy').on('click', function () {
    SalidasState.fecha = document.body.dataset.fechaActual || new Date().toISOString().slice(0, 10);
    $('#salidas-fecha').val(SalidasState.fecha);
    cargarSalidas();
  });

  $('#btn-salidas-anterior').on('click', function () { cambiarDiaSalidas(-1); });
  $('#btn-salidas-siguiente').on('click', function () { cambiarDiaSalidas(1); });
  $(document).on('click', '.js-ver-detalle-salida', abrirDetalleSalida);
  $('[data-bs-toggle="tooltip"]').tooltip();
});

function cambiarDiaSalidas(dias) {
  SalidasState.fecha = salidasFechaSumar($('#salidas-fecha').val() || SalidasState.fecha, dias);
  $('#salidas-fecha').val(SalidasState.fecha);
  cargarSalidas();
}

function cargarSalidas() {
  $('#salidas-lista').html(`
    <div class="salida-empty">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <div>Cargando cronograma...</div>
    </div>
  `);

  $.get(salidasBaseUrl('/listar'), { fecha: SalidasState.fecha }, function (respuesta) {
    if (respuesta.status !== true) {
      mostrarErrorSalidas(respuesta);
      return;
    }

    SalidasState.items = respuesta.data.items || [];
    $('#salidas-titulo').html(`Cronograma de salidas: <span>${salidasEscape(respuesta.data.fecha || SalidasState.fecha)}</span>`);
    renderSalidas();
  }).fail(function (xhr) {
    mostrarErrorSalidas(xhr);
  });
}

function renderSalidas() {
  const $lista = $('#salidas-lista');

  if (!SalidasState.items.length) {
    $lista.html(`
      <div class="salida-empty">
        <i class="ri-route-line fs-1 text-muted d-block mb-2"></i>
        <div class="fw-semibold">Sin salidas programadas</div>
        <div class="fs-12">No hay detalles de reserva para esta fecha.</div>
      </div>
    `);
    return;
  }

  $lista.html(SalidasState.items.map(renderSalidaCard).join(''));
}

function renderSalidaCard(item, index) {
  return `
    <div class="salida-card">
      <div class="salida-card-body">
        <div class="salida-tour">${salidasEscape(item.tour)}</div>
        <div class="salida-pax">${item.pax_total} PASAJEROS <i class="ri-team-fill"></i></div>
        <div class="salida-shared">Compartido ${item.pax_compartido} pax</div>
        <a href="javascript:void(0);" class="salida-link js-ver-detalle-salida" data-index="${index}">Click ver detalles</a>
      </div>
    </div>
  `;
}

function abrirDetalleSalida() {
  const index = Number($(this).data('index'));
  const item = SalidasState.items[index];
  if (!item) return;

  $('#modal-salida-detalles-label').text(item.tour || 'Detalle de salida');
  $('#modal-salida-detalles-subtitle').text(`${item.pax_total} pasajero(s) - Compartido ${item.pax_compartido} pax - Privado ${item.pax_privado} pax`);
  $('#salida-detalles-body').html(item.detalles.map(renderSalidaDetalle).join(''));
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-salida-detalles')).show();
}

function renderSalidaDetalle(detalle) {
  const observacion = detalle.observacion ? `<div class="text-muted fs-12 mt-1"><i class="ri-file-text-line me-1"></i>${salidasEscape(detalle.observacion)}</div>` : '';
  return `
    <div class="salida-detail-row">
      <div class="d-flex justify-content-between gap-2 flex-wrap">
        <div>
          <div class="fw-bold">${salidasEscape(detalle.cliente)}</div>
          <div class="text-muted fs-12"><i class="ri-id-card-line me-1"></i>${salidasEscape(detalle.documento)} <span class="mx-1">|</span> <i class="ri-phone-line me-1"></i>${salidasEscape(detalle.telefono)}</div>
        </div>
        <div class="text-end">
          <span class="badge bg-primary-transparent text-primary">${detalle.pax} pax</span>
          <span class="badge bg-info-transparent text-info">${salidasEscape(detalle.vehiculo)}</span>
        </div>
      </div>
      <div class="row g-2 mt-2 fs-12">
        <div class="col-md-4"><span class="text-muted">Reserva:</span> <strong>${salidasEscape(detalle.codigo_reserva)}</strong></div>
        <div class="col-md-4"><span class="text-muted">Turno:</span> <strong>${salidasEscape(detalle.turno)}</strong></div>
        <div class="col-md-4"><span class="text-muted">Fecha:</span> <strong>${salidasEscape(detalle.fecha_tours_texto)}</strong></div>
        <div class="col-12"><span class="text-muted">Recojo:</span> <strong>${salidasEscape(detalle.recojo)}</strong></div>
      </div>
      ${observacion}
    </div>
  `;
}

function mostrarErrorSalidas(xhr) {
  const mensaje = xhr?.responseJSON?.message || xhr?.message || 'No se pudo completar la operacion.';
  Swal.fire('Error', mensaje, 'error');
}
