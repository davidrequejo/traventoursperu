let tablaDistritos = null;

function puedeUbigeoDistrito(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('catalogo_general', accion);
}

function estadoDistritoActivo(estado) {
  return estado === true || String(estado) === '1';
}

$(function () {
  inicializarTablaDistritos();
  inicializarFormularioDistrito();

  $('#btn-nuevo-distrito').on('click', prepararNuevoDistrito);
  $('.guardar_distrito').on('click', function () {
    $('#form-distrito').submit();
  });
  $('#incluir-eliminados-distrito').on('change', recargarTablaDistritos);
  $('#modal-nuevo-distrito').on('hidden.bs.modal', limpiarFormularioDistrito);

  // Eventos para cargar provincias dependientes
  $('#distrito_idubigeo_departamento').on('change', function () {
    cargarProvinciasPorDepartamento($(this).val());
  });
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

function inicializarTablaDistritos() {
  tablaDistritos = $('#tabla-distritos').DataTable({
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
        exportOptions: { columns: [0, 2, 3, 4, 5, 6] },
        title: 'Lista de distritos',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/ubigeo-distritos/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function () {
        return {
          incluir_inactivos: $('#incluir-eliminados-distrito').is(':checked') ? 1 : 0,
        };
      },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarErrorUbigeoDistrito(response.message || 'No se pudo cargar distritos.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorUbigeoDistrito('Error al consultar distritos.');
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

          if (puedeUbigeoDistrito('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-distrito" data-id="${row.idubigeo_distrito}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (estadoDistritoActivo(row.estado) && puedeUbigeoDistrito('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-distrito" data-id="${row.idubigeo_distrito}" data-bs-toggle="tooltip" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (!estadoDistritoActivo(row.estado) && puedeUbigeoDistrito('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-distrito" data-id="${row.idubigeo_distrito}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      
      { data: 'nombre', render: (data) => escapeHtmlUbigeoDistrito(data || '-') },
      {
        data: 'provincia.nombre',
        render: (data) => escapeHtmlUbigeoDistrito(data || '-')
      },
      {
        data: 'departamento.nombre',
        render: (data) => escapeHtmlUbigeoDistrito(data || '-')
      },
      {
        data: 'estado',
        className: 'text-center',
        render: function (estado) {
          return estadoDistritoActivo(estado)
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

  $('#tabla-distritos tbody').on('click', '.btn-editar-distrito', function () {
    editarDistrito($(this).data('id'));
  });

  $('#tabla-distritos tbody').on('click', '.btn-eliminar-distrito', function () {
    eliminarDistrito($(this).data('id'));
  });

  $('#tabla-distritos tbody').on('click', '.btn-restaurar-distrito', function () {
    restaurarDistrito($(this).data('id'));
  });
}

function inicializarFormularioDistrito() {
  cargarDepartamentosDistrito();

  $('#form-distrito').validate({
    rules: {
      idubigeo_departamento: { required: true },
      idubigeo_provincia: { required: true },
      idubigeo_distrito: { required: true, minlength: 6, maxlength: 6 },
      nombre: { required: true, minlength: 2, maxlength: 100 },
      codigo_postal: { maxlength: 10 },
      ubigeo_reniec: { maxlength: 10 },
      ubigeo_inei: { maxlength: 10 },
      superficie: { min: 0 },
      altitud: { min: 0 },
      latitud: { range: [-90, 90] },
      longitud: { range: [-180, 180] },
    },
    messages: {
      idubigeo_departamento: {
        required: 'Campo requerido.',
      },
      idubigeo_provincia: {
        required: 'Campo requerido.',
      },
      idubigeo_distrito: {
        required: 'Campo requerido.',
        minlength: 'Debe tener exactamente {0} caracteres.',
        maxlength: 'Debe tener exactamente {0} caracteres.',
      },
      nombre: {
        required: 'Campo requerido.',
        minlength: 'Mínimo {0} caracteres.',
        maxlength: 'Máximo {0} caracteres.',
      },
      codigo_postal: {
        maxlength: 'Máximo {0} caracteres.',
      },
      ubigeo_reniec: {
        maxlength: 'Máximo {0} caracteres.',
      },
      ubigeo_inei: {
        maxlength: 'Máximo {0} caracteres.',
      },
      superficie: {
        min: 'Debe ser mayor o igual a {0}.',
      },
      altitud: {
        min: 'Debe ser mayor o igual a {0}.',
      },
      latitud: {
        range: 'Debe estar entre {0} y {1}.',
      },
      longitud: {
        range: 'Debe estar entre {0} y {1}.',
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
      guardarDistrito(form);
    },
  });
}

function cargarDepartamentosDistrito() {
  $.ajax({
    url: apiUrl('/ubigeo-departamentos/listar'),
    type: 'GET',
    headers: ajaxHeaders(),
    data: { incluir_inactivos: 0 },
    success: function (response) {
      if (response.status) {
        const $select = $('#distrito_idubigeo_departamento');
        $select.empty().append('<option value="">Seleccione un departamento</option>');
        response.data.forEach(function (departamento) {
          $select.append(`<option value="${departamento.idubigeo_departamento}">${departamento.nombre}</option>`);
        });
      }
    },
    error: function () {
      mostrarErrorUbigeoDistrito('Error al cargar departamentos.');
    },
  });
}

function cargarProvinciasPorDepartamento(idDepartamento) {
  const $provinciaSelect = $('#distrito_idubigeo_provincia');
  $provinciaSelect.empty().append('<option value="">Seleccione una provincia</option>');

  if (!idDepartamento) {
    return Promise.resolve();
  }

  return $.ajax({
    url: apiUrl('/ubigeo-provincias/listar'),
    type: 'GET',
    headers: ajaxHeaders(),
    data: {
      incluir_inactivos: 0,
      idubigeo_departamento: idDepartamento
    },
    success: function (response) {
      if (response.status) {
        response.data.forEach(function (provincia) {
          $provinciaSelect.append(`<option value="${provincia.idubigeo_provincia}">${provincia.nombre}</option>`);
        });
      }
    },
    error: function () {
      mostrarErrorUbigeoDistrito('Error al cargar provincias.');
    },
  });
}

function prepararNuevoDistrito(event) {
  if (!puedeUbigeoDistrito('crear')) {
    event?.preventDefault();
    mostrarErrorUbigeoDistrito('No tienes permiso para crear distritos.');
    return;
  }
  limpiarFormularioDistrito();
  $('#modal-nuevo-distrito-label').text('Nuevo Distrito');
  $('.guardar_distrito').html('<i class="ti ti-device-floppy"></i> Guardar');
}

function guardarDistrito(form) {
  const id = $('#distrito_id').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedeUbigeoDistrito('editar')) {
    mostrarErrorUbigeoDistrito('No tienes permiso para editar distritos.');
    return;
  }
  if (!esEdicion && !puedeUbigeoDistrito('crear')) {
    mostrarErrorUbigeoDistrito('No tienes permiso para crear distritos.');
    return;
  }

  const formData = new FormData(form);
  const url = esEdicion
    ? apiUrl(`/ubigeo-distritos/${id}/update`)
    : apiUrl('/ubigeo-distritos/store');
  const finalButtonHtml = esEdicion
    ? '<i class="ti ti-device-floppy"></i> Actualizar'
    : '<i class="ti ti-device-floppy"></i> Guardar';

  if (esEdicion) {
    formData.append('_method', 'PUT');
  }

  cambiarEstadoBotonUbigeoDistrito($('.guardar_distrito'), true, 'Guardando...');

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarErrorUbigeoDistrito(response.message || 'No se pudo guardar el distrito.');
        return;
      }
      mostrarOkUbigeoDistrito(response.message || 'Distrito guardado correctamente.');
      $('#modal-nuevo-distrito').modal('hide');
      recargarTablaDistritos();
    },
    error: function (xhr) {
      mostrarErroresValidacionUbigeoDistrito(xhr);
    },
    complete: function () {
      cambiarEstadoBotonUbigeoDistrito($('.guardar_distrito'), false, finalButtonHtml);
    },
  });
}

function editarDistrito(id) {
  if (!puedeUbigeoDistrito('editar')) {
    mostrarErrorUbigeoDistrito('No tienes permiso para editar distritos.');
    return;
  }

  $.ajax({
    url: apiUrl(`/ubigeo-distritos/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarErrorUbigeoDistrito(response.message || 'No se pudo cargar el distrito.');
        return;
      }
      cargarDistritoEnFormulario(response.data);
      $('#modal-nuevo-distrito').modal('show');
    },
    error: function () {
      mostrarErrorUbigeoDistrito('Error al cargar distrito.');
    },
  });
}

function cargarDistritoEnFormulario(data) {
  limpiarFormularioDistrito();
  $('#modal-nuevo-distrito-label').text('Editar Distrito');
  $('.guardar_distrito').html('<i class="ti ti-device-floppy"></i> Actualizar');

  $('#distrito_id').val(data.idubigeo_distrito || '');
  $('#distrito_idubigeo_departamento').val(data.idubigeo_departamento || '');
  $('#distrito_idubigeo_distrito').val(data.idubigeo_distrito || '');
  $('#distrito_nombre').val(data.nombre || '');
  $('#distrito_codigo_postal').val(data.codigo_postal || '');
  $('#distrito_ubigeo_reniec').val(data.ubigeo_reniec || '');
  $('#distrito_ubigeo_inei').val(data.ubigeo_inei || '');
  $('#distrito_superficie').val(data.superficie || '');
  $('#distrito_altitud').val(data.altitud || '');
  $('#distrito_latitud').val(data.latitud || '');
  $('#distrito_longitud').val(data.longitud || '');
  $('#distrito_frontera').prop('checked', Boolean(data.frontera));

  // Cargar provincias del departamento seleccionado
  if (data.idubigeo_departamento) {
    cargarProvinciasPorDepartamento(data.idubigeo_departamento).then(() => {
      $('#distrito_idubigeo_provincia').val(data.idubigeo_provincia || '');
    });
  }
}

function eliminarDistrito(id) {
  if (!puedeUbigeoDistrito('eliminar')) {
    mostrarErrorUbigeoDistrito('No tienes permiso para eliminar distritos.');
    return;
  }

  confirmarAccionUbigeoDistrito('Eliminar distrito', 'El registro se enviará a papelera.', 'Sí, eliminar', function () {
    $.ajax({
      url: apiUrl(`/ubigeo-distritos/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorUbigeoDistrito(response.message || 'No se pudo eliminar el distrito.');
          return;
        }
        mostrarOkUbigeoDistrito(response.message || 'Distrito eliminado correctamente.');
        recargarTablaDistritos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorUbigeoDistrito(response.message || 'Error al eliminar distrito.');
      },
    });
  });
}

function restaurarDistrito(id) {
  if (!puedeUbigeoDistrito('editar')) {
    mostrarErrorUbigeoDistrito('No tienes permiso para restaurar distritos.');
    return;
  }

  confirmarAccionUbigeoDistrito('Restaurar distrito', 'El registro volverá a estar activo.', 'Sí, restaurar', function () {
    $.ajax({
      url: apiUrl(`/ubigeo-distritos/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorUbigeoDistrito(response.message || 'No se pudo restaurar el distrito.');
          return;
        }
        mostrarOkUbigeoDistrito(response.message || 'Distrito restaurado correctamente.');
        recargarTablaDistritos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorUbigeoDistrito(response.message || 'Error al restaurar distrito.');
      },
    });
  });
}

function limpiarFormularioDistrito() {
  const form = $('#form-distrito');
  form[0]?.reset();
  $('#distrito_id').val('');
  $('#distrito_idubigeo_provincia').empty().append('<option value="">Seleccione una provincia</option>');
  form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
  if (form.data('validator')) {
    form.validate().resetForm();
  }
}

function recargarTablaDistritos() {
  tablaDistritos?.ajax.reload(null, false);
}

function mostrarErroresValidacionUbigeoDistrito(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const validator = $('#form-distrito').data('validator');
    const normalizedErrors = {};
    Object.entries(errors).forEach(function ([key, value]) {
      normalizedErrors[key] = Array.isArray(value) ? value[0] : value;
    });
    if (validator) {
      validator.showErrors(normalizedErrors);
    }
    mostrarErrorUbigeoDistrito(Object.values(normalizedErrors)[0] || 'Revise los campos del formulario.');
    return;
  }
  mostrarErrorUbigeoDistrito(response.message || 'Error al guardar distrito.');
}

function confirmarAccionUbigeoDistrito(title, text, confirmButtonText, callback) {
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

function cambiarEstadoBotonUbigeoDistrito($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function mostrarOkUbigeoDistrito(message) {
  if (typeof toastr !== 'undefined') toastr.success(message);
  else alert(message);
}

function mostrarErrorUbigeoDistrito(message) {
  if (typeof toastr !== 'undefined') toastr.error(message);
  else alert(message);
}

function escapeHtmlUbigeoDistrito(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
