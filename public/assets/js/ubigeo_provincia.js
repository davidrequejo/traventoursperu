let tablaProvincias = null;

function puedeUbigeoProvincia(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('catalogo_general', accion);
}

function estadoProvinciaActivo(estado) {
  return estado === true || String(estado) === '1';
}

$(function () {
  inicializarTablaProvincias();
  inicializarFormularioProvincia();

  $('#btn-nuevo-provincia').on('click', prepararNuevoProvincia);
  $('.guardar_provincia').on('click', function () {
    $('#form-provincia').submit();
  });
  $('#incluir-eliminados-provincia').on('change', recargarTablaProvincias);
  $('#modal-nuevo-provincia').on('hidden.bs.modal', limpiarFormularioProvincia);
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

function inicializarTablaProvincias() {
  tablaProvincias = $('#tabla-provincias').DataTable({
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
        title: 'Lista de provincias',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/ubigeo-provincias/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function () {
        return {
          incluir_inactivos: $('#incluir-eliminados-provincia').is(':checked') ? 1 : 0,
        };
      },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarErrorUbigeoProvincia(response.message || 'No se pudo cargar provincias.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorUbigeoProvincia('Error al consultar provincias.');
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

          if (puedeUbigeoProvincia('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-provincia" data-id="${row.idubigeo_provincia}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (estadoProvinciaActivo(row.estado) && puedeUbigeoProvincia('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-provincia" data-id="${row.idubigeo_provincia}" data-bs-toggle="tooltip" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (!estadoProvinciaActivo(row.estado) && puedeUbigeoProvincia('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-provincia" data-id="${row.idubigeo_provincia}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      { data: 'nombre', render: (data) => escapeHtmlUbigeoProvincia(data || '-') },
      {
        data: 'departamento.nombre',
        render: (data) => escapeHtmlUbigeoProvincia(data || '-')
      },
      {
        data: 'estado',
        className: 'text-center',
        render: function (estado) {
          return estadoProvinciaActivo(estado)
            ? '<span class="badge bg-success-transparent">Activo</span>'
            : '<span class="badge bg-danger-transparent">Inactivo</span>';
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

  $('#tabla-provincias tbody').on('click', '.btn-editar-provincia', function () {
    editarProvincia($(this).data('id'));
  });

  $('#tabla-provincias tbody').on('click', '.btn-eliminar-provincia', function () {
    eliminarProvincia($(this).data('id'));
  });

  $('#tabla-provincias tbody').on('click', '.btn-restaurar-provincia', function () {
    restaurarProvincia($(this).data('id'));
  });
}

function inicializarFormularioProvincia() {
  cargarDepartamentosProvincia();

  $('#form-provincia').validate({
    rules: {
      idubigeo_departamento: { required: true },
      idubigeo_provincia: { required: true, minlength: 4, maxlength: 4 },
      nombre: { required: true, minlength: 2, maxlength: 100 },
    },
    messages: {
      idubigeo_departamento: {
        required: 'Campo requerido.',
      },
      idubigeo_provincia: {
        required: 'Campo requerido.',
        minlength: 'Debe tener exactamente {0} caracteres.',
        maxlength: 'Debe tener exactamente {0} caracteres.',
      },
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
      guardarProvincia(form);
    },
  });
}

function cargarDepartamentosProvincia() {
  $.ajax({
    url: apiUrl('/ubigeo-departamentos/listar'),
    type: 'GET',
    headers: ajaxHeaders(),
    data: { incluir_inactivos: 0 },
    success: function (response) {
      if (response.status) {
        const $select = $('#provincia_idubigeo_departamento');
        $select.empty().append('<option value="">Seleccione un departamento</option>');
        response.data.forEach(function (departamento) {
          $select.append(`<option value="${departamento.idubigeo_departamento}">${departamento.nombre}</option>`);
        });
      }
    },
    error: function () {
      mostrarErrorUbigeoProvincia('Error al cargar departamentos.');
    },
  });
}

function prepararNuevoProvincia(event) {
  if (!puedeUbigeoProvincia('crear')) {
    event?.preventDefault();
    mostrarErrorUbigeoProvincia('No tienes permiso para crear provincias.');
    return;
  }
  limpiarFormularioProvincia();
  $('#modal-nuevo-provincia-label').text('Nueva Provincia');
  $('.guardar_provincia').html('<i class="ti ti-device-floppy"></i> Guardar');
}

function guardarProvincia(form) {
  const id = $('#provincia_id').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedeUbigeoProvincia('editar')) {
    mostrarErrorUbigeoProvincia('No tienes permiso para editar provincias.');
    return;
  }
  if (!esEdicion && !puedeUbigeoProvincia('crear')) {
    mostrarErrorUbigeoProvincia('No tienes permiso para crear provincias.');
    return;
  }

  const formData = new FormData(form);
  const url = esEdicion
    ? apiUrl(`/ubigeo-provincias/${id}/update`)
    : apiUrl('/ubigeo-provincias/store');
  const finalButtonHtml = esEdicion
    ? '<i class="ti ti-device-floppy"></i> Actualizar'
    : '<i class="ti ti-device-floppy"></i> Guardar';

  if (esEdicion) {
    formData.append('_method', 'PUT');
  }

  cambiarEstadoBotonUbigeoProvincia($('.guardar_provincia'), true, 'Guardando...');

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarErrorUbigeoProvincia(response.message || 'No se pudo guardar la provincia.');
        return;
      }
      mostrarOkUbigeoProvincia(response.message || 'Provincia guardada correctamente.');
      $('#modal-nuevo-provincia').modal('hide');
      recargarTablaProvincias();
    },
    error: function (xhr) {
      mostrarErroresValidacionUbigeoProvincia(xhr);
    },
    complete: function () {
      cambiarEstadoBotonUbigeoProvincia($('.guardar_provincia'), false, finalButtonHtml);
    },
  });
}

function editarProvincia(id) {
  if (!puedeUbigeoProvincia('editar')) {
    mostrarErrorUbigeoProvincia('No tienes permiso para editar provincias.');
    return;
  }

  $.ajax({
    url: apiUrl(`/ubigeo-provincias/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarErrorUbigeoProvincia(response.message || 'No se pudo cargar la provincia.');
        return;
      }
      cargarProvinciaEnFormulario(response.data);
      $('#modal-nuevo-provincia').modal('show');
    },
    error: function () {
      mostrarErrorUbigeoProvincia('Error al cargar provincia.');
    },
  });
}

function cargarProvinciaEnFormulario(data) {
  limpiarFormularioProvincia();
  $('#modal-nuevo-provincia-label').text('Editar Provincia');
  $('.guardar_provincia').html('<i class="ti ti-device-floppy"></i> Actualizar');

  $('#provincia_id').val(data.idubigeo_provincia || '');
  $('#provincia_idubigeo_departamento').val(data.idubigeo_departamento || '');
  $('#provincia_idubigeo_provincia').val(data.idubigeo_provincia || '');
  $('#provincia_nombre').val(data.nombre || '');
}

function eliminarProvincia(id) {
  if (!puedeUbigeoProvincia('eliminar')) {
    mostrarErrorUbigeoProvincia('No tienes permiso para eliminar provincias.');
    return;
  }

  confirmarAccionUbigeoProvincia('Eliminar provincia', 'El registro se enviará a papelera.', 'Sí, eliminar', function () {
    $.ajax({
      url: apiUrl(`/ubigeo-provincias/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorUbigeoProvincia(response.message || 'No se pudo eliminar la provincia.');
          return;
        }
        mostrarOkUbigeoProvincia(response.message || 'Provincia eliminada correctamente.');
        recargarTablaProvincias();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorUbigeoProvincia(response.message || 'Error al eliminar provincia.');
      },
    });
  });
}

function restaurarProvincia(id) {
  if (!puedeUbigeoProvincia('editar')) {
    mostrarErrorUbigeoProvincia('No tienes permiso para restaurar provincias.');
    return;
  }

  confirmarAccionUbigeoProvincia('Restaurar provincia', 'El registro volverá a estar activo.', 'Sí, restaurar', function () {
    $.ajax({
      url: apiUrl(`/ubigeo-provincias/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorUbigeoProvincia(response.message || 'No se pudo restaurar la provincia.');
          return;
        }
        mostrarOkUbigeoProvincia(response.message || 'Provincia restaurada correctamente.');
        recargarTablaProvincias();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorUbigeoProvincia(response.message || 'Error al restaurar provincia.');
      },
    });
  });
}

function limpiarFormularioProvincia() {
  const form = $('#form-provincia');
  form[0]?.reset();
  $('#provincia_id').val('');
  form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
  if (form.data('validator')) {
    form.validate().resetForm();
  }
}

function recargarTablaProvincias() {
  tablaProvincias?.ajax.reload(null, false);
}

function mostrarErroresValidacionUbigeoProvincia(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const validator = $('#form-provincia').data('validator');
    const normalizedErrors = {};
    Object.entries(errors).forEach(function ([key, value]) {
      normalizedErrors[key] = Array.isArray(value) ? value[0] : value;
    });
    if (validator) {
      validator.showErrors(normalizedErrors);
    }
    mostrarErrorUbigeoProvincia(Object.values(normalizedErrors)[0] || 'Revise los campos del formulario.');
    return;
  }
  mostrarErrorUbigeoProvincia(response.message || 'Error al guardar provincia.');
}

function confirmarAccionUbigeoProvincia(title, text, confirmButtonText, callback) {
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

function cambiarEstadoBotonUbigeoProvincia($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function mostrarOkUbigeoProvincia(message) {
  if (typeof toastr !== 'undefined') toastr.success(message);
  else alert(message);
}

function mostrarErrorUbigeoProvincia(message) {
  if (typeof toastr !== 'undefined') toastr.error(message);
  else alert(message);
}

function escapeHtmlUbigeoProvincia(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
