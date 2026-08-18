let tablaPersonasCargos = null;

function puedePersonaCargo(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('catalogo_general', accion);
}

$(function () {
  inicializarTablaPersonasCargos();
  inicializarFormularioPersonaCargo();

  $('#btn-nuevo-persona-cargo').on('click', prepararNuevoPersonaCargo);
  $('.guardar_persona_cargo').on('click', function () {
    $('#form-persona-cargo').submit();
  });
  $('#incluir-eliminados-persona-cargo').on('change', recargarTablaPersonasCargos);
  $('#modal-nuevo-persona-cargo').on('hidden.bs.modal', limpiarFormularioPersonaCargo);
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

function inicializarTablaPersonasCargos() {
  tablaPersonasCargos = $('#tabla-personas-cargos').DataTable({
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
        exportOptions: { columns: [0, 2, 3, 4] },
        title: 'Lista de cargos',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/persona-cargos/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function () {
        return {
          incluir_trash: $('#incluir-eliminados-persona-cargo').is(':checked') ? 1 : 0,
        };
      },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarErrorPersonaCargo(response.message || 'No se pudo cargar cargos.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorPersonaCargo('Error al consultar cargos.');
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

          if (puedePersonaCargo('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-persona-cargo" data-id="${row.idpersona_cargo}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '1' && puedePersonaCargo('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-persona-cargo" data-id="${row.idpersona_cargo}" data-bs-toggle="tooltip" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '0' && puedePersonaCargo('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-persona-cargo" data-id="${row.idpersona_cargo}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      { data: 'nombre', render: (data) => escapeHtmlPersonaCargo(data || '-') },
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

  $('#tabla-personas-cargos tbody').on('click', '.btn-editar-persona-cargo', function () {
    editarPersonaCargo($(this).data('id'));
  });

  $('#tabla-personas-cargos tbody').on('click', '.btn-eliminar-persona-cargo', function () {
    eliminarPersonaCargo($(this).data('id'));
  });

  $('#tabla-personas-cargos tbody').on('click', '.btn-restaurar-persona-cargo', function () {
    restaurarPersonaCargo($(this).data('id'));
  });
}

function inicializarFormularioPersonaCargo() {
  $('#form-persona-cargo').validate({
    rules: {
      nombre: { required: true, minlength: 2, maxlength: 45 },
    },
    messages: {
      nombre: {
        required: 'Campo requerido.',
        minlength: 'Mínimo {0} caracteres.',
        maxlength: 'Máximo {0} caracteres.',
      },
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
      guardarPersonaCargo(form);
    },
  });
}

function prepararNuevoPersonaCargo(event) {
  if (!puedePersonaCargo('crear')) {
    event?.preventDefault();
    mostrarErrorPersonaCargo('No tienes permiso para crear cargos.');
    return;
  }
  limpiarFormularioPersonaCargo();
  $('#modal-nuevo-persona-cargo-label').text('Nuevo Cargo');
  $('.guardar_persona_cargo').html('<i class="ti ti-device-floppy"></i> Guardar');
}

function guardarPersonaCargo(form) {
  const id = $('#persona_cargo_id').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedePersonaCargo('editar')) {
    mostrarErrorPersonaCargo('No tienes permiso para editar cargos.');
    return;
  }
  if (!esEdicion && !puedePersonaCargo('crear')) {
    mostrarErrorPersonaCargo('No tienes permiso para crear cargos.');
    return;
  }

  const formData = new FormData(form);
  const url = esEdicion
    ? apiUrl(`/persona-cargos/${id}/update`)
    : apiUrl('/persona-cargos/store');
  const finalButtonHtml = esEdicion
    ? '<i class="ti ti-device-floppy"></i> Actualizar'
    : '<i class="ti ti-device-floppy"></i> Guardar';

  if (esEdicion) {
    formData.append('_method', 'PUT');
  }

  cambiarEstadoBotonPersonaCargo($('.guardar_persona_cargo'), true, 'Guardando...');

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarErrorPersonaCargo(response.message || 'No se pudo guardar el cargo.');
        return;
      }
      mostrarOkPersonaCargo(response.message || 'Cargo guardado correctamente.');
      $('#modal-nuevo-persona-cargo').modal('hide');
      recargarTablaPersonasCargos();
    },
    error: function (xhr) {
      mostrarErroresValidacionPersonaCargo(xhr);
    },
    complete: function () {
      cambiarEstadoBotonPersonaCargo($('.guardar_persona_cargo'), false, finalButtonHtml);
    },
  });
}

function editarPersonaCargo(id) {
  if (!puedePersonaCargo('editar')) {
    mostrarErrorPersonaCargo('No tienes permiso para editar cargos.');
    return;
  }

  $.ajax({
    url: apiUrl(`/persona-cargos/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarErrorPersonaCargo(response.message || 'No se pudo cargar el cargo.');
        return;
      }
      cargarPersonaCargoEnFormulario(response.data);
      $('#modal-nuevo-persona-cargo').modal('show');
    },
    error: function () {
      mostrarErrorPersonaCargo('Error al cargar cargo.');
    },
  });
}

function cargarPersonaCargoEnFormulario(data) {
  limpiarFormularioPersonaCargo();
  $('#modal-nuevo-persona-cargo-label').text('Editar Cargo');
  $('.guardar_persona_cargo').html('<i class="ti ti-device-floppy"></i> Actualizar');

  $('#persona_cargo_id').val(data.idpersona_cargo || '');
  $('#persona_cargo_nombre').val(data.nombre || '');
}

function eliminarPersonaCargo(id) {
  if (!puedePersonaCargo('eliminar')) {
    mostrarErrorPersonaCargo('No tienes permiso para eliminar cargos.');
    return;
  }

  confirmarAccionPersonaCargo('Eliminar cargo', 'El registro se enviará a papelera.', 'Sí, eliminar', function () {
    $.ajax({
      url: apiUrl(`/persona-cargos/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorPersonaCargo(response.message || 'No se pudo eliminar el cargo.');
          return;
        }
        mostrarOkPersonaCargo(response.message || 'Cargo eliminado correctamente.');
        recargarTablaPersonasCargos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorPersonaCargo(response.message || 'Error al eliminar cargo.');
      },
    });
  });
}

function restaurarPersonaCargo(id) {
  if (!puedePersonaCargo('editar')) {
    mostrarErrorPersonaCargo('No tienes permiso para restaurar cargos.');
    return;
  }

  confirmarAccionPersonaCargo('Restaurar cargo', 'El registro volverá a estar activo.', 'Sí, restaurar', function () {
    $.ajax({
      url: apiUrl(`/persona-cargos/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorPersonaCargo(response.message || 'No se pudo restaurar el cargo.');
          return;
        }
        mostrarOkPersonaCargo(response.message || 'Cargo restaurado correctamente.');
        recargarTablaPersonasCargos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorPersonaCargo(response.message || 'Error al restaurar cargo.');
      },
    });
  });
}

function limpiarFormularioPersonaCargo() {
  const form = $('#form-persona-cargo');
  form[0]?.reset();
  $('#persona_cargo_id').val('');
  form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
  if (form.data('validator')) {
    form.validate().resetForm();
  }
}

function recargarTablaPersonasCargos() {
  tablaPersonasCargos?.ajax.reload(null, false);
}

function mostrarErroresValidacionPersonaCargo(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const validator = $('#form-persona-cargo').data('validator');
    const normalizedErrors = {};
    Object.entries(errors).forEach(function ([key, value]) {
      normalizedErrors[key] = Array.isArray(value) ? value[0] : value;
    });
    if (validator) {
      validator.showErrors(normalizedErrors);
    }
    mostrarErrorPersonaCargo(Object.values(normalizedErrors)[0] || 'Revise los campos del formulario.');
    return;
  }
  mostrarErrorPersonaCargo(response.message || 'Error al guardar cargo.');
}

function confirmarAccionPersonaCargo(title, text, confirmButtonText, callback) {
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

function cambiarEstadoBotonPersonaCargo($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function mostrarOkPersonaCargo(message) {
  if (typeof toastr !== 'undefined') toastr.success(message);
  else alert(message);
}

function mostrarErrorPersonaCargo(message) {
  if (typeof toastr !== 'undefined') toastr.error(message);
  else alert(message);
}

function escapeHtmlPersonaCargo(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
