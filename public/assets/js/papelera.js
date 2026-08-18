let tablaPapelera = null;

function puedePapelera(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('papelera', accion);
}

$(function () {
  inicializarTablaPapelera();

  $('#btn-recargar-papelera').on('click', recargarTablaPapelera);
  $('#filtro-modulo-papelera').on('change', recargarTablaPapelera);
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

function inicializarTablaPapelera() {
  tablaPapelera = $('#tabla-papelera').DataTable({
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
    ],
    ajax: {
      url: apiUrl('/papelera/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function (data) {
        data.modulo = $('#filtro-modulo-papelera').val();
      },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarError(response.message || 'No se pudo cargar la papelera.');
          return [];
        }

        return response.data || [];
      },
      error: function () {
        mostrarError('Error al consultar papelera.');
      },
    },
    columns: [
      {
        data: 'modulo_label',
        render: function (data, _type, row) {
          const color = {
            personas: 'secondary',
            trabajadores: 'primary',
            clientes: 'success',
            usuarios: 'warning',
            empresas: 'info',
          }[row.modulo] || 'secondary';

          return `<span class="badge bg-${color}-transparent">${escapeHtml(data)}</span>`;
        },
      },
      {
        data: 'nombre',
        render: function (data) {
          return `<span class="fw-semibold">${escapeHtml(data || 'Sin nombre')}</span>`;
        },
      },
      {
        data: 'detalle',
        render: function (data) {
          return data ? escapeHtml(data) : '<span class="text-muted">Sin detalle</span>';
        },
      },
      {
        data: 'extra',
        render: function (data) {
          return data ? escapeHtml(data) : '<span class="text-muted">-</span>';
        },
      },
      {
        data: 'updated_at',
        render: function (data) {
          return data ? moment(data).format('DD/MM/YYYY HH:mm') : '-';
        },
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          if (!puedePapelera('editar')) {
            return '<span class="text-muted">-</span>';
          }

          return `
            <button type="button" class="btn btn-sm btn-success-light btn-wave btn-restaurar-papelera"
              data-modulo="${escapeHtml(row.modulo)}" data-id="${escapeHtml(row.id)}">
              <i class="ti ti-restore me-1"></i>Restaurar
            </button>
          `;
        },
      },
    ],
    language: {
      lengthMenu: '_MENU_',
      sLoadingRecords: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...',
      search: '',
    },
    order: [[4, 'desc']],
  });

  $('#tabla-papelera tbody').on('click', '.btn-restaurar-papelera', function () {
    restaurarRegistroPapelera($(this));
  });
}

function restaurarRegistroPapelera($button) {
  if (!puedePapelera('editar')) {
    mostrarError('No tienes permiso para restaurar registros.');
    return;
  }

  const modulo = $button.data('modulo');
  const id = $button.data('id');

  confirmarAccionPapelera('Restaurar registro', 'El registro volvera a estar activo en su modulo.', function () {
    cambiarEstadoBoton($button, true, 'Restaurando...');

    $.ajax({
      url: apiUrl(`/papelera/${modulo}/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarError(response.message || 'No se pudo restaurar el registro.');
          return;
        }

        mostrarOk(response.message || 'Registro restaurado correctamente.');
        recargarTablaPapelera();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarError(response.message || 'Error al restaurar registro.');
      },
      complete: function () {
        cambiarEstadoBoton($button, false, '<i class="ti ti-restore me-1"></i>Restaurar');
      },
    });
  });
}

function recargarTablaPapelera() {
  tablaPapelera?.ajax.reload(null, false);
}

function confirmarAccionPapelera(title, message, callback) {
  if (typeof Swal === 'undefined') {
    callback();
    return;
  }

  Swal.fire({
    title,
    text: message,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Si, restaurar',
    cancelButtonText: 'Cancelar',
  }).then(function (result) {
    if (result.isConfirmed) {
      callback();
    }
  });
}

function cambiarEstadoBoton($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function mostrarOk(message) {
  if (typeof toastr !== 'undefined') {
    toastr.success(message);
    return;
  }

  alert(message);
}

function mostrarError(message) {
  if (typeof toastr !== 'undefined') {
    toastr.error(message);
    return;
  }

  alert(message);
}

function escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
