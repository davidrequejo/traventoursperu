let tablaPersonasTipos = null;

function puedePersonaTipo(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('catalogo_general', accion);
}

$(function () {
  inicializarTablaPersonasTipos();
  inicializarFormularioPersonaTipo();

  $('#btn-nuevo-persona-tipo').on('click', prepararNuevoPersonaTipo);
  $('.guardar_persona_tipo').on('click', function () {
    $('#form-persona-tipo').submit();
  });
  $('#incluir-eliminados-persona-tipo').on('change', recargarTablaPersonasTipos);
  $('#modal-nuevo-persona-tipo').on('hidden.bs.modal', limpiarFormularioPersonaTipo);
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

function inicializarTablaPersonasTipos() {
  tablaPersonasTipos = $('#tabla-personas-tipos').DataTable({
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
        exportOptions: { columns: [0, 2, 3, 4, 5] },
        title: 'Lista de tipos de persona',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/personas-tipos/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function (data) {
        data.incluir_trash = $('#incluir-eliminados-persona-tipo').is(':checked') ? 1 : 0;
      },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarErrorPersonaTipo(response.message || 'No se pudo cargar tipos de persona.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorPersonaTipo('Error al consultar tipos de persona.');
      },
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const acciones = [];

          if (puedePersonaTipo('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-persona-tipo" data-id="${row.idpersona_tipo}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '1' && puedePersonaTipo('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-persona-tipo" data-id="${row.idpersona_tipo}" data-bs-toggle="tooltip" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '0' && puedePersonaTipo('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-persona-tipo" data-id="${row.idpersona_tipo}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      { data: 'nombre', render: (data) => escapeHtmlPersonaTipo(data || '-') },
      { data: 'descripcion', render: (data) => escapeHtmlPersonaTipo(data || '-') },
      {
        data: 'estado_trash',
        className: 'text-center',
        render: function (estado) {
          return String(estado) === '1'
            ? '<span class="badge bg-success-transparent">Activo</span>'
            : '<span class="badge bg-danger-transparent">Eliminado</span>';
        },
      },
    ],
    language: {
      lengthMenu: '_MENU_',
      sLoadingRecords: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...',
      search: '',
    },
    drawCallback: function () {
      $('[data-bs-toggle="tooltip"]').tooltip();
    },
    order: [[0, 'desc']],
  });

  $('#tabla-personas-tipos tbody').on('click', '.btn-editar-persona-tipo', function () {
    editarPersonaTipo($(this).data('id'));
  });

  $('#tabla-personas-tipos tbody').on('click', '.btn-eliminar-persona-tipo', function () {
    eliminarPersonaTipo($(this).data('id'));
  });

  $('#tabla-personas-tipos tbody').on('click', '.btn-restaurar-persona-tipo', function () {
    restaurarPersonaTipo($(this).data('id'));
  });
}

function inicializarFormularioPersonaTipo() {
  $('#form-persona-tipo').validate({
    rules: {
      nombre: { required: true, minlength: 2, maxlength: 100 },
      descripcion: { maxlength: 500 },
    },
    messages: {
      nombre: {
        required: 'Campo requerido.',
        minlength: 'Mínimo {0} caracteres.',
        maxlength: 'Máximo {0} caracteres.',
      },
      descripcion: { maxlength: 'Máximo {0} caracteres.' },
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      error.insertAfter(element);
    },
    highlight: function (element) {
      $(element).addClass('is-invalid').removeClass('is-valid');
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid').addClass('is-valid');
    },
    submitHandler: function (form, event) {
      event.preventDefault();
      guardarPersonaTipo(form);
    },
  });
}

function prepararNuevoPersonaTipo(event) {
  if (!puedePersonaTipo('crear')) {
    event?.preventDefault();
    mostrarErrorPersonaTipo('No tienes permiso para crear tipos de persona.');
    return;
  }
  limpiarFormularioPersonaTipo();
  $('#modal-nuevo-persona-tipo-label').text('Nuevo Tipo de Persona');
  $('.guardar_persona_tipo').html('<i class="ti ti-device-floppy"></i> Guardar');
}

function guardarPersonaTipo(form) {
  const id = $('#persona_tipo_id').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedePersonaTipo('editar')) {
    mostrarErrorPersonaTipo('No tienes permiso para editar tipos de persona.');
    return;
  }
  if (!esEdicion && !puedePersonaTipo('crear')) {
    mostrarErrorPersonaTipo('No tienes permiso para crear tipos de persona.');
    return;
  }

  const formData = new FormData(form);
  const url = esEdicion
    ? apiUrl(`/personas-tipos/${id}/update`)
    : apiUrl('/personas-tipos/store');
  const finalButtonHtml = esEdicion
    ? '<i class="ti ti-device-floppy"></i> Actualizar'
    : '<i class="ti ti-device-floppy"></i> Guardar';

  if (esEdicion) {
    formData.append('_method', 'PUT');
  }

  cambiarEstadoBotonPersonaTipo($('.guardar_persona_tipo'), true, 'Guardando...');

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarErrorPersonaTipo(response.message || 'No se pudo guardar el tipo de persona.');
        return;
      }
      mostrarOkPersonaTipo(response.message || 'Tipo de persona guardado correctamente.');
      $('#modal-nuevo-persona-tipo').modal('hide');
      recargarTablaPersonasTipos();
    },
    error: function (xhr) {
      mostrarErroresValidacionPersonaTipo(xhr);
    },
    complete: function () {
      cambiarEstadoBotonPersonaTipo($('.guardar_persona_tipo'), false, finalButtonHtml);
    },
  });
}

function editarPersonaTipo(id) {
  if (!puedePersonaTipo('editar')) {
    mostrarErrorPersonaTipo('No tienes permiso para editar tipos de persona.');
    return;
  }

  $.ajax({
    url: apiUrl(`/personas-tipos/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarErrorPersonaTipo(response.message || 'No se pudo cargar el tipo de persona.');
        return;
      }
      cargarPersonaTipoEnFormulario(response.data);
      $('#modal-nuevo-persona-tipo').modal('show');
    },
    error: function () {
      mostrarErrorPersonaTipo('Error al cargar tipo de persona.');
    },
  });
}

function cargarPersonaTipoEnFormulario(data) {
  limpiarFormularioPersonaTipo();
  $('#modal-nuevo-persona-tipo-label').text('Editar Tipo de Persona');
  $('.guardar_persona_tipo').html('<i class="ti ti-device-floppy"></i> Actualizar');

  $('#persona_tipo_id').val(data.idpersona_tipo || '');
  $('#persona_tipo_nombre').val(data.nombre || '');
  $('#persona_tipo_descripcion').val(data.descripcion || '');
}

function eliminarPersonaTipo(id) {
  if (!puedePersonaTipo('eliminar')) {
    mostrarErrorPersonaTipo('No tienes permiso para eliminar tipos de persona.');
    return;
  }

  confirmarAccionPersonaTipo('Eliminar tipo de persona', 'El registro se enviará a papelera.', 'Sí, eliminar', function () {
    $.ajax({
      url: apiUrl(`/personas-tipos/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorPersonaTipo(response.message || 'No se pudo eliminar el tipo de persona.');
          return;
        }
        mostrarOkPersonaTipo(response.message || 'Tipo de persona eliminado correctamente.');
        recargarTablaPersonasTipos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorPersonaTipo(response.message || 'Error al eliminar tipo de persona.');
      },
    });
  });
}

function restaurarPersonaTipo(id) {
  if (!puedePersonaTipo('editar')) {
    mostrarErrorPersonaTipo('No tienes permiso para restaurar tipos de persona.');
    return;
  }

  confirmarAccionPersonaTipo('Restaurar tipo de persona', 'El registro volverá a estar activo.', 'Sí, restaurar', function () {
    $.ajax({
      url: apiUrl(`/personas-tipos/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorPersonaTipo(response.message || 'No se pudo restaurar el tipo de persona.');
          return;
        }
        mostrarOkPersonaTipo(response.message || 'Tipo de persona restaurado correctamente.');
        recargarTablaPersonasTipos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorPersonaTipo(response.message || 'Error al restaurar tipo de persona.');
      },
    });
  });
}

function limpiarFormularioPersonaTipo() {
  const form = $('#form-persona-tipo');
  form[0]?.reset();
  $('#persona_tipo_id').val('');
  form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
  if (form.data('validator')) {
    form.validate().resetForm();
  }
}

function recargarTablaPersonasTipos() {
  tablaPersonasTipos?.ajax.reload(null, false);
}

function mostrarErroresValidacionPersonaTipo(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const validator = $('#form-persona-tipo').data('validator');
    const normalizedErrors = {};
    Object.entries(errors).forEach(function ([key, value]) {
      normalizedErrors[key] = Array.isArray(value) ? value[0] : value;
    });
    if (validator) {
      validator.showErrors(normalizedErrors);
    }
    mostrarErrorPersonaTipo(Object.values(normalizedErrors)[0] || 'Revise los campos del formulario.');
    return;
  }
  mostrarErrorPersonaTipo(response.message || 'Error al guardar tipo de persona.');
}

function confirmarAccionPersonaTipo(title, text, confirmButtonText, callback) {
  if (typeof Swal === 'undefined') {
    callback();
    return;
  }
  Swal.fire({
    title,
    text,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancelar',
  }).then(function (result) {
    if (result.isConfirmed) callback();
  });
}

function cambiarEstadoBotonPersonaTipo($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function mostrarOkPersonaTipo(message) {
  if (typeof toastr !== 'undefined') toastr.success(message);
  else alert(message);
}

function mostrarErrorPersonaTipo(message) {
  if (typeof toastr !== 'undefined') toastr.error(message);
  else alert(message);
}

function escapeHtmlPersonaTipo(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}