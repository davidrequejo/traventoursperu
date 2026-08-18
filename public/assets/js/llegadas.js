const LlegadasState = {
  fecha: document.body.dataset.fechaActual || new Date().toISOString().slice(0, 10),
  items: [],
};

function llegadasBaseUrl(path = '') {
  return `${document.querySelector('meta[name="app-url"]')?.content || ''}/llegadas${path}`;
}

function llegadasHeaders() {
  return {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    Accept: 'application/json',
  };
}

function llegadasEscape(valor) {
  return $('<div>').text(valor ?? '').html();
}

function llegadasFechaSumar(fecha, dias) {
  const date = new Date(`${fecha}T00:00:00`);
  date.setDate(date.getDate() + dias);
  return date.toISOString().slice(0, 10);
}

$(function () {
  $('#llegadas-fecha').val(LlegadasState.fecha);
  cargarLlegadas();

  $('#llegadas-fecha').on('change', function () {
    LlegadasState.fecha = this.value || LlegadasState.fecha;
    cargarLlegadas();
  });

  $('#btn-llegadas-hoy').on('click', function () {
    LlegadasState.fecha = document.body.dataset.fechaActual || new Date().toISOString().slice(0, 10);
    $('#llegadas-fecha').val(LlegadasState.fecha);
    cargarLlegadas();
  });

  $('#btn-llegadas-anterior').on('click', function () {
    cambiarDiaLlegadas(-1);
  });

  $('#btn-llegadas-siguiente').on('click', function () {
    cambiarDiaLlegadas(1);
  });

  $('#btn-llegadas-copiar').on('click', copiarLlegadas);
  $('#btn-llegadas-pdf').on('click', imprimirLlegadas);
  $('#form-llegada-recojo').on('submit', guardarRecojoLlegada);
  $(document).on('click', '.js-asignar-recojo-llegada', abrirModalRecojoLlegada);
  $('[data-bs-toggle="tooltip"]').tooltip();
});

function cambiarDiaLlegadas(dias) {
  LlegadasState.fecha = llegadasFechaSumar($('#llegadas-fecha').val() || LlegadasState.fecha, dias);
  $('#llegadas-fecha').val(LlegadasState.fecha);
  cargarLlegadas();
}

function cargarLlegadas() {
  const $lista = $('#llegadas-lista');
  $lista.html(`
    <div class="llegada-empty">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <div>Cargando llegadas...</div>
    </div>
  `);

  $.get(llegadasBaseUrl('/listar'), { fecha: LlegadasState.fecha }, function (respuesta) {
    if (respuesta.status !== true) {
      mostrarErrorLlegadas(respuesta);
      return;
    }

    LlegadasState.items = respuesta.data.items || [];
    $('#llegadas-titulo').text(respuesta.data.titulo || 'FECHA LLEGADA :');
    renderLlegadas();
  }).fail(function (xhr) {
    mostrarErrorLlegadas(xhr);
  });
}

function renderLlegadas() {
  const $lista = $('#llegadas-lista');

  if (!LlegadasState.items.length) {
    $lista.html(`
      <div class="llegada-empty">
        <i class="ri-flight-land-line fs-1 text-muted d-block mb-2"></i>
        <div class="fw-semibold">Sin llegadas registradas</div>
        <div class="fs-12">No hay reservas con llegada para esta fecha.</div>
      </div>
    `);
    return;
  }

  $lista.html(LlegadasState.items.map(renderLlegadaCard).join(''));
  $('[data-bs-toggle="tooltip"]').tooltip();
}

function renderLlegadaCard(item) {
  const appUrl = document.querySelector('meta[name="app-url"]')?.content || '';
  return `
    <div class="llegada-card">
      <div class="llegada-card-body">
        <div class="llegada-card-top">
          <span class="badge bg-success">${item.pax} Pax</span>
          <span class="badge bg-primary-transparent text-primary"><i class="ri-time-line me-1"></i>Hora llegada: ${llegadasEscape(item.hora_llegada)}</span>
        </div>
        <div class="text-muted fs-11 mb-2"><i class="ri-calendar-line me-1"></i>Fecha llegada: ${llegadasEscape(item.fecha_llegada_texto)}</div>
        <h6 class="fw-bold mb-1">${llegadasEscape(item.cliente)}</h6>
        <div class="llegada-line fs-12"><i class="ri-id-card-line"></i><span>${llegadasEscape(item.documento)}</span></div>
        <div class="llegada-line fs-12"><i class="ri-plane-line"></i><span>${llegadasEscape(item.llegada_empresa)}</span></div>
        <div class="llegada-line fs-12"><i class="ri-phone-line"></i><span>${llegadasEscape(item.telefono)}</span></div>

        <div class="llegada-info">
          <div class="llegada-line fs-12"><i class="ri-calendar-event-line"></i><span>${llegadasEscape(item.fecha_llegada_texto)}</span></div>
          <div class="llegada-line fs-12"><i class="ri-time-line"></i><span>${llegadasEscape(item.hora_llegada)}</span></div>
          <div class="llegada-line fs-12 mb-0"><i class="ri-hotel-line"></i><span>${llegadasEscape(item.recojo_texto)}</span></div>
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
          <button type="button" class="btn btn-primary js-asignar-recojo-llegada" data-id="${item.idreserva}">Asignar Recojo</button>
          <a class="btn btn-light" href="${appUrl}/reservas?idreserva=${item.idreserva}" target="_blank">
            <i class="ri-edit-line me-1"></i>Reserva
          </a>
        </div>
      </div>
    </div>
  `;
}

function abrirModalRecojoLlegada() {
  const id = Number($(this).data('id'));
  const item = LlegadasState.items.find((row) => Number(row.idreserva) === id);
  if (!item) return;

  $('#llegada-recojo-id').val(item.idreserva);
  $('#llegada-recojo-cliente').val(item.cliente || '');
  $('#llegada-recojo-observacion').val(item.observacion_recojo || '');
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-llegada-recojo')).show();
}

function guardarRecojoLlegada(event) {
  event.preventDefault();

  const id = $('#llegada-recojo-id').val();
  const $boton = $('#btn-guardar-llegada-recojo');
  const htmlOriginal = $boton.html();

  $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Guardando...');

  $.ajax({
    url: llegadasBaseUrl(`/${id}/recojo`),
    type: 'PATCH',
    headers: llegadasHeaders(),
    data: $('#form-llegada-recojo').serialize(),
    success: function (respuesta) {
      bootstrap.Modal.getInstance(document.getElementById('modal-llegada-recojo'))?.hide();
      Swal.fire({ icon: 'success', title: 'Correcto', text: respuesta.message || 'Recojo actualizado.', timer: 1600, showConfirmButton: false });
      cargarLlegadas();
    },
    error: function (xhr) {
      mostrarErrorLlegadas(xhr);
    },
    complete: function () {
      $boton.prop('disabled', false).html(htmlOriginal);
    },
  });
}

function textoLlegadas() {
  const titulo = $('#llegadas-titulo').text();
  const lineas = LlegadasState.items.map((item) => [
    `${item.pax} Pax - ${item.cliente}`,
    `Fecha llegada: ${item.fecha_llegada_texto}`,
    `Hora llegada: ${item.hora_llegada}`,
    `Llegada por: ${item.llegada_empresa}`,
    `Telefono: ${item.telefono}`,
    `Recojo: ${item.recojo_texto}`,
  ].join('\n'));

  return [titulo, ...lineas].join('\n\n');
}

function copiarLlegadas() {
  const texto = textoLlegadas();
  if (!texto.trim()) return;

  navigator.clipboard.writeText(texto).then(function () {
    toastr_success('Copiado', 'Llegadas copiadas al portapapeles.', 900);
  }).catch(function () {
    const temporal = $('<textarea>').val(texto).appendTo('body').select();
    document.execCommand('copy');
    temporal.remove();
    toastr_success('Copiado', 'Llegadas copiadas al portapapeles.', 900);
  });
}

function imprimirLlegadas() {
  const ventana = window.open('', '_blank');
  if (!ventana) return;

  ventana.document.write(`
    <html>
      <head>
        <title>Llegadas</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 24px; color: #111827; }
          h2 { color: #6c5ce7; font-size: 18px; }
          .card { border: 1px solid #d1d5db; border-radius: 8px; padding: 14px; margin-bottom: 12px; break-inside: avoid; }
          .muted { color: #6b7280; font-size: 12px; }
          .title { font-weight: 700; margin: 8px 0; }
          @media print { button { display: none; } }
        </style>
      </head>
      <body>
        <button onclick="window.print()">Imprimir / Guardar PDF</button>
        <h2>${llegadasEscape($('#llegadas-titulo').text())}</h2>
        ${LlegadasState.items.map((item) => `
          <div class="card">
            <div><strong>${item.pax} Pax</strong> <span class="muted">Hora llegada: ${llegadasEscape(item.hora_llegada)}</span></div>
            <div class="title">${llegadasEscape(item.cliente)}</div>
            <div class="muted">Documento: ${llegadasEscape(item.documento)}</div>
            <div class="muted">Llegada por: ${llegadasEscape(item.llegada_empresa)}</div>
            <div class="muted">Telefono: ${llegadasEscape(item.telefono)}</div>
            <div class="muted">Recojo: ${llegadasEscape(item.recojo_texto)}</div>
          </div>
        `).join('')}
      </body>
    </html>
  `);
  ventana.document.close();
}

function mostrarErrorLlegadas(xhr) {
  const mensaje = xhr?.responseJSON?.message || xhr?.message || 'No se pudo completar la operacion.';
  Swal.fire('Error', mensaje, 'error');
}
