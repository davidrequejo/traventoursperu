let tablaTipoComprobantes = null;

$(function () {
  inicializarTablaTipoComprobantes();
  $('#btn-recargar-tipo-comprobante').on('click', recargarTablaTipoComprobantes);
});

function apiUrl(path) {
  const base = document.querySelector('meta[name="app-url"]')?.content || '';
  return base + path;
}

function csrf() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function ajaxHeaders() {
  return {
    'X-CSRF-TOKEN': csrf(),
    Accept: 'application/json',
  };
}

function inicializarTablaTipoComprobantes() {
  tablaTipoComprobantes = $('#tabla-tipo-comprobantes').DataTable({
    responsive: true,
    processing: true,
    deferRender: true,
    dom: "<'row'<'col-md-8 pt-2'f><'col-md-4 pt-2 d-flex justify-content-end'<'length'l><'buttons'B>>>r t <'row'<'col-md-6'i><'col-md-6'p>>",
    buttons: [
      {
        text: '<i class="bi bi-arrow-clockwise"></i>',
        className: 'buttons-reload btn btn-outline-info',
        action: function (_event, dt) {
          dt.ajax.reload(null, false);
        },
      },
      {
        extend: 'excel',
        exportOptions: { columns: [0, 1, 2, 3, 4] },
        title: 'Tipos de comprobantes SUNAT',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/sunat/tipos-comprobantes/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      dataSrc: function (response) {
        if (!response.status) {
          mostrarError(response.message || 'No se pudo cargar tipos de comprobantes.');
          return [];
        }

        return response.data || [];
      },
      error: function () {
        mostrarError('Error al consultar tipos de comprobantes.');
      },
    },
    columns: [
      { data: 'codigo', className: 'text-center text-nowrap' },
      {
        data: null,
        className: 'tipo-comprobante-nombre',
        render: function (_data, _type, row) {
          return `
            <div>
              <p class="fw-semibold mb-0">${escapeHtml(row.nombre || 'Sin nombre')}</p>
              <span class="text-muted fs-12">${escapeHtml(row.abreviatura || 'Sin abreviatura')}</span>
            </div>
          `;
        },
      },
      {
        data: 'series_activas',
        className: 'series-comprobante-list',
        orderable: false,
        render: function (series) {
          if (!Array.isArray(series) || series.length === 0) {
            return '<span class="text-muted">Sin series asignadas</span>';
          }

          return series.map(function (serie) {
            const numero = serie.numero !== null && serie.numero !== undefined ? `-${serie.numero}` : '';
            const predeterminado = String(serie.predeterminado) === '1'
              ? '<span class="badge bg-primary-transparent ms-1">Pred.</span>'
              : '';

            return `<span class="badge bg-info-transparent me-1 mb-1">${escapeHtml(serie.serie || '-')}${escapeHtml(numero)}${predeterminado}</span>`;
          }).join('');
        },
      },
      {
        data: 'estado_trash',
        className: 'text-center text-nowrap',
        render: function (_data, type, row) {
          const activo = String(row.estado_trash) === '1';

          if (type === 'sort' || type === 'type') {
            return activo ? 1 : 0;
          }

          if (type === 'export' || type === 'filter') {
            return activo ? 'Activo' : 'Inactivo';
          }

          return activo
            ? '<span class="badge bg-success-transparent">Activo</span>'
            : '<span class="badge bg-danger-transparent">Inactivo</span>';
        },
      },
      {
        data: 'updated_at',
        render: function (data) {
          return data ? moment(data).format('DD/MM/YYYY HH:mm') : '-';
        },
      },
    ],
    language: {
      lengthMenu: '_MENU_',
      sLoadingRecords: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...',
      search: '',
    },
    order: [[3, 'desc'], [0, 'asc']],
  });
}

function recargarTablaTipoComprobantes() {
  tablaTipoComprobantes?.ajax.reload(null, false);
}

function mostrarError(message) {
  if (typeof toastr !== 'undefined') {
    toastr.error(message);
    return;
  }

  alert(message);
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
