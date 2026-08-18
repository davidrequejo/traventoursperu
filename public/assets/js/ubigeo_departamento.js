let tablaDepartamentos = null;

function puedeUbigeoDepartamento(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('catalogo_general', accion);
}

function estadoDepartamentoActivo(estado) {
  return estado === true || String(estado) === '1';
}

$(function () {
  inicializarTablaDepartamentos();
  inicializarFormularioDepartamento();

  $('#btn-nuevo-departamento').on('click', prepararNuevoDepartamento);
  $('.guardar_departamento').on('click', function () {
    $('#form-departamento').submit();
  });
  $('#incluir-eliminados-departamento').on('change', recargarTablaDepartamentos);
  $('#modal-nuevo-departamento').on('hidden.bs.modal', limpiarFormularioDepartamento);
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

function inicializarTablaDepartamentos() {
  tablaDepartamentos = $('#tabla-departamentos').DataTable({
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
        title: 'Lista de departamentos',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/ubigeo-departamentos/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function () {
        return {
          incluir_inactivos: $('#incluir-eliminados-departamento').is(':checked') ? 1 : 0,
        };
      },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarErrorUbigeoDepartamento(response.message || 'No se pudo cargar departamentos.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorUbigeoDepartamento('Error al consultar departamentos.');
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

          if (puedeUbigeoDepartamento('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-departamento" data-id="${row.idubigeo_departamento}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (estadoDepartamentoActivo(row.estado) && puedeUbigeoDepartamento('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-departamento" data-id="${row.idubigeo_departamento}" data-bs-toggle="tooltip" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (!estadoDepartamentoActivo(row.estado) && puedeUbigeoDepartamento('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-departamento" data-id="${row.idubigeo_departamento}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      { data: 'nombre', render: (data) => escapeHtmlUbigeoDepartamento(data || '-') },
      {
        data: 'estado',
        className: 'text-center',
        render: function (estado) {
          return estadoDepartamentoActivo(estado)
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

  $('#tabla-departamentos tbody').on('click', '.btn-editar-departamento', function () {
    editarDepartamento($(this).data('id'));
  });

  $('#tabla-departamentos tbody').on('click', '.btn-eliminar-departamento', function () {
    eliminarDepartamento($(this).data('id'));
  });

  $('#tabla-departamentos tbody').on('click', '.btn-restaurar-departamento', function () {
    restaurarDepartamento($(this).data('id'));
  });
}

function inicializarFormularioDepartamento() {
  $('#form-departamento').validate({
    rules: {
      idubigeo_departamento: { required: true, minlength: 2, maxlength: 2 },
      nombre: { required: true, minlength: 2, maxlength: 100 },
    },
    messages: {
      idubigeo_departamento: {
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
      guardarDepartamento(form);
    },
  });
}

function prepararNuevoDepartamento(event) {
  if (!puedeUbigeoDepartamento('crear')) {
    event?.preventDefault();
    mostrarErrorUbigeoDepartamento('No tienes permiso para crear departamentos.');
    return;
  }
  limpiarFormularioDepartamento();
  $('#modal-nuevo-departamento-label').text('Nuevo Departamento');
  $('.guardar_departamento').html('<i class="ti ti-device-floppy"></i> Guardar');
}

function guardarDepartamento(form) {
  const id = $('#departamento_id').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedeUbigeoDepartamento('editar')) {
    mostrarErrorUbigeoDepartamento('No tienes permiso para editar departamentos.');
    return;
  }
  if (!esEdicion && !puedeUbigeoDepartamento('crear')) {
    mostrarErrorUbigeoDepartamento('No tienes permiso para crear departamentos.');
    return;
  }

  const formData = new FormData(form);
  const url = esEdicion
    ? apiUrl(`/ubigeo-departamentos/${id}/update`)
    : apiUrl('/ubigeo-departamentos/store');
  const finalButtonHtml = esEdicion
    ? '<i class="ti ti-device-floppy"></i> Actualizar'
    : '<i class="ti ti-device-floppy"></i> Guardar';

  if (esEdicion) {
    formData.append('_method', 'PUT');
  }

  cambiarEstadoBotonUbigeoDepartamento($('.guardar_departamento'), true, 'Guardando...');

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarErrorUbigeoDepartamento(response.message || 'No se pudo guardar el departamento.');
        return;
      }
      mostrarOkUbigeoDepartamento(response.message || 'Departamento guardado correctamente.');
      $('#modal-nuevo-departamento').modal('hide');
      recargarTablaDepartamentos();
    },
    error: function (xhr) {
      mostrarErroresValidacionUbigeoDepartamento(xhr);
    },
    complete: function () {
      cambiarEstadoBotonUbigeoDepartamento($('.guardar_departamento'), false, finalButtonHtml);
    },
  });
}

function editarDepartamento(id) {
  if (!puedeUbigeoDepartamento('editar')) {
    mostrarErrorUbigeoDepartamento('No tienes permiso para editar departamentos.');
    return;
  }

  $.ajax({
    url: apiUrl(`/ubigeo-departamentos/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarErrorUbigeoDepartamento(response.message || 'No se pudo cargar el departamento.');
        return;
      }
      cargarDepartamentoEnFormulario(response.data);
      $('#modal-nuevo-departamento').modal('show');
    },
    error: function () {
      mostrarErrorUbigeoDepartamento('Error al cargar departamento.');
    },
  });
}

function cargarDepartamentoEnFormulario(data) {
  limpiarFormularioDepartamento();
  $('#modal-nuevo-departamento-label').text('Editar Departamento');
  $('.guardar_departamento').html('<i class="ti ti-device-floppy"></i> Actualizar');

  $('#departamento_id').val(data.idubigeo_departamento || '');
  $('#departamento_idubigeo_departamento').val(data.idubigeo_departamento || '');
  $('#departamento_nombre').val(data.nombre || '');
}

function eliminarDepartamento(id) {
  if (!puedeUbigeoDepartamento('eliminar')) {
    mostrarErrorUbigeoDepartamento('No tienes permiso para eliminar departamentos.');
    return;
  }

  confirmarAccionUbigeoDepartamento('Eliminar departamento', 'El registro se enviará a papelera.', 'Sí, eliminar', function () {
    $.ajax({
      url: apiUrl(`/ubigeo-departamentos/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorUbigeoDepartamento(response.message || 'No se pudo eliminar el departamento.');
          return;
        }
        mostrarOkUbigeoDepartamento(response.message || 'Departamento eliminado correctamente.');
        recargarTablaDepartamentos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorUbigeoDepartamento(response.message || 'Error al eliminar departamento.');
      },
    });
  });
}

function restaurarDepartamento(id) {
  if (!puedeUbigeoDepartamento('editar')) {
    mostrarErrorUbigeoDepartamento('No tienes permiso para restaurar departamentos.');
    return;
  }

  confirmarAccionUbigeoDepartamento('Restaurar departamento', 'El registro volverá a estar activo.', 'Sí, restaurar', function () {
    $.ajax({
      url: apiUrl(`/ubigeo-departamentos/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorUbigeoDepartamento(response.message || 'No se pudo restaurar el departamento.');
          return;
        }
        mostrarOkUbigeoDepartamento(response.message || 'Departamento restaurado correctamente.');
        recargarTablaDepartamentos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorUbigeoDepartamento(response.message || 'Error al restaurar departamento.');
      },
    });
  });
}

function limpiarFormularioDepartamento() {
  const form = $('#form-departamento');
  form[0]?.reset();
  $('#departamento_id').val('');
  form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
  if (form.data('validator')) {
    form.validate().resetForm();
  }
}

function recargarTablaDepartamentos() {
  tablaDepartamentos?.ajax.reload(null, false);
}

function mostrarErroresValidacionUbigeoDepartamento(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const validator = $('#form-departamento').data('validator');
    const normalizedErrors = {};
    Object.entries(errors).forEach(function ([key, value]) {
      normalizedErrors[key] = Array.isArray(value) ? value[0] : value;
    });
    if (validator) {
      validator.showErrors(normalizedErrors);
    }
    mostrarErrorUbigeoDepartamento(Object.values(normalizedErrors)[0] || 'Revise los campos del formulario.');
    return;
  }
  mostrarErrorUbigeoDepartamento(response.message || 'Error al guardar departamento.');
}

function confirmarAccionUbigeoDepartamento(title, text, confirmButtonText, callback) {
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

function cambiarEstadoBotonUbigeoDepartamento($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function mostrarOkUbigeoDepartamento(message) {
  if (typeof toastr !== 'undefined') toastr.success(message);
  else alert(message);
}

function mostrarErrorUbigeoDepartamento(message) {
  if (typeof toastr !== 'undefined') toastr.error(message);
  else alert(message);
}

function escapeHtmlUbigeoDepartamento(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
