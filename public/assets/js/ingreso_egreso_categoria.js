let tablaIngresoEgresoCategorias = null;

function puedeIngresoEgresoCategoria(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('catalogo_general', accion);
}

$(function () {
  inicializarTablaIngresoEgresoCategorias();
  inicializarFormularioIngresoEgresoCategoria();

  $('#btn-nuevo-ingreso-egreso-categoria').on('click', prepararNuevoIngresoEgresoCategoria);
  $('.guardar_ingreso_egreso_categoria').on('click', function () {
    $('#form-ingreso-egreso-categoria').submit();
  });
  $('#incluir-eliminados-ingreso-egreso-categoria').on('change', recargarTablaIngresoEgresoCategorias);
  $('#modal-nuevo-ingreso-egreso-categoria').on('hidden.bs.modal', limpiarFormularioIngresoEgresoCategoria);
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

function inicializarTablaIngresoEgresoCategorias() {
  tablaIngresoEgresoCategorias = $('#tabla-ingreso-egreso-categorias').DataTable({
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
        exportOptions: { columns: [1, 2, 3] },
        title: 'Lista de categorias de ingreso y egreso',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/ingreso-egreso-categorias/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function () {
        return {
          incluir_trash: $('#incluir-eliminados-ingreso-egreso-categoria').is(':checked') ? 1 : 0,
        };
      },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarErrorIngresoEgresoCategoria(response.message || 'No se pudo cargar categorias.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorIngresoEgresoCategoria('Error al consultar categorias.');
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

          if (puedeIngresoEgresoCategoria('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-ingreso-egreso-categoria" data-id="${row.idingreso_egreso_categoria}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '1' && puedeIngresoEgresoCategoria('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-ingreso-egreso-categoria" data-id="${row.idingreso_egreso_categoria}" data-bs-toggle="tooltip" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '0' && puedeIngresoEgresoCategoria('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-ingreso-egreso-categoria" data-id="${row.idingreso_egreso_categoria}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      { data: 'nombre', render: (data) => escapeHtmlIngresoEgresoCategoria(data || '-') },
      { data: 'descripcion', render: (data) => escapeHtmlIngresoEgresoCategoria(data || '-') },
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
    order: [[1, 'asc']],
  });

  $('#tabla-ingreso-egreso-categorias tbody').on('click', '.btn-editar-ingreso-egreso-categoria', function () {
    editarIngresoEgresoCategoria($(this).data('id'));
  });

  $('#tabla-ingreso-egreso-categorias tbody').on('click', '.btn-eliminar-ingreso-egreso-categoria', function () {
    eliminarIngresoEgresoCategoria($(this).data('id'));
  });

  $('#tabla-ingreso-egreso-categorias tbody').on('click', '.btn-restaurar-ingreso-egreso-categoria', function () {
    restaurarIngresoEgresoCategoria($(this).data('id'));
  });
}

function inicializarFormularioIngresoEgresoCategoria() {
  $('#form-ingreso-egreso-categoria').validate({
    rules: {
      nombre: { required: true, minlength: 2, maxlength: 100 },
      descripcion: { maxlength: 250 },
    },
    messages: {
      nombre: {
        required: 'Campo requerido.',
        minlength: 'Minimo {0} caracteres.',
        maxlength: 'Maximo {0} caracteres.',
      },
      descripcion: { maxlength: 'Maximo {0} caracteres.' },
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
      guardarIngresoEgresoCategoria(form);
    },
  });
}

function prepararNuevoIngresoEgresoCategoria(event) {
  if (!puedeIngresoEgresoCategoria('crear')) {
    event?.preventDefault();
    mostrarErrorIngresoEgresoCategoria('No tienes permiso para crear categorias.');
    return;
  }
  limpiarFormularioIngresoEgresoCategoria();
  $('#modal-nuevo-ingreso-egreso-categoria-label').text('Nueva Categoria Ingreso/Egreso');
  $('.guardar_ingreso_egreso_categoria').html('<i class="ti ti-device-floppy"></i> Guardar');
}

function guardarIngresoEgresoCategoria(form) {
  const id = $('#ingreso_egreso_categoria_id').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedeIngresoEgresoCategoria('editar')) {
    mostrarErrorIngresoEgresoCategoria('No tienes permiso para editar categorias.');
    return;
  }
  if (!esEdicion && !puedeIngresoEgresoCategoria('crear')) {
    mostrarErrorIngresoEgresoCategoria('No tienes permiso para crear categorias.');
    return;
  }

  const formData = new FormData(form);
  const url = esEdicion
    ? apiUrl(`/ingreso-egreso-categorias/${id}/update`)
    : apiUrl('/ingreso-egreso-categorias/store');
  const finalButtonHtml = esEdicion
    ? '<i class="ti ti-device-floppy"></i> Actualizar'
    : '<i class="ti ti-device-floppy"></i> Guardar';

  if (esEdicion) {
    formData.append('_method', 'PUT');
  }

  cambiarEstadoBotonIngresoEgresoCategoria($('.guardar_ingreso_egreso_categoria'), true, 'Guardando...');

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarErrorIngresoEgresoCategoria(response.message || 'No se pudo guardar la categoria.');
        return;
      }
      mostrarOkIngresoEgresoCategoria(response.message || 'Categoria guardada correctamente.');
      $('#modal-nuevo-ingreso-egreso-categoria').modal('hide');
      recargarTablaIngresoEgresoCategorias();
    },
    error: function (xhr) {
      mostrarErroresValidacionIngresoEgresoCategoria(xhr);
    },
    complete: function () {
      cambiarEstadoBotonIngresoEgresoCategoria($('.guardar_ingreso_egreso_categoria'), false, finalButtonHtml);
    },
  });
}

function editarIngresoEgresoCategoria(id) {
  if (!puedeIngresoEgresoCategoria('editar')) {
    mostrarErrorIngresoEgresoCategoria('No tienes permiso para editar categorias.');
    return;
  }

  $.ajax({
    url: apiUrl(`/ingreso-egreso-categorias/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarErrorIngresoEgresoCategoria(response.message || 'No se pudo cargar la categoria.');
        return;
      }
      cargarIngresoEgresoCategoriaEnFormulario(response.data);
      $('#modal-nuevo-ingreso-egreso-categoria').modal('show');
    },
    error: function () {
      mostrarErrorIngresoEgresoCategoria('Error al cargar categoria.');
    },
  });
}

function cargarIngresoEgresoCategoriaEnFormulario(data) {
  limpiarFormularioIngresoEgresoCategoria();
  $('#modal-nuevo-ingreso-egreso-categoria-label').text('Editar Categoria Ingreso/Egreso');
  $('.guardar_ingreso_egreso_categoria').html('<i class="ti ti-device-floppy"></i> Actualizar');

  $('#ingreso_egreso_categoria_id').val(data.idingreso_egreso_categoria || '');
  $('#ingreso_egreso_categoria_nombre').val(data.nombre || '');
  $('#ingreso_egreso_categoria_descripcion').val(data.descripcion || '');
}

function eliminarIngresoEgresoCategoria(id) {
  if (!puedeIngresoEgresoCategoria('eliminar')) {
    mostrarErrorIngresoEgresoCategoria('No tienes permiso para eliminar categorias.');
    return;
  }

  confirmarAccionIngresoEgresoCategoria('Eliminar categoria', 'El registro se enviara a papelera.', 'Si, eliminar', function () {
    $.ajax({
      url: apiUrl(`/ingreso-egreso-categorias/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorIngresoEgresoCategoria(response.message || 'No se pudo eliminar la categoria.');
          return;
        }
        mostrarOkIngresoEgresoCategoria(response.message || 'Categoria eliminada correctamente.');
        recargarTablaIngresoEgresoCategorias();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorIngresoEgresoCategoria(response.message || 'Error al eliminar categoria.');
      },
    });
  });
}

function restaurarIngresoEgresoCategoria(id) {
  if (!puedeIngresoEgresoCategoria('editar')) {
    mostrarErrorIngresoEgresoCategoria('No tienes permiso para restaurar categorias.');
    return;
  }

  confirmarAccionIngresoEgresoCategoria('Restaurar categoria', 'El registro volvera a estar activo.', 'Si, restaurar', function () {
    $.ajax({
      url: apiUrl(`/ingreso-egreso-categorias/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorIngresoEgresoCategoria(response.message || 'No se pudo restaurar la categoria.');
          return;
        }
        mostrarOkIngresoEgresoCategoria(response.message || 'Categoria restaurada correctamente.');
        recargarTablaIngresoEgresoCategorias();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorIngresoEgresoCategoria(response.message || 'Error al restaurar categoria.');
      },
    });
  });
}

function limpiarFormularioIngresoEgresoCategoria() {
  const form = $('#form-ingreso-egreso-categoria');
  form[0]?.reset();
  $('#ingreso_egreso_categoria_id').val('');
  form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
  if (form.data('validator')) {
    form.validate().resetForm();
  }
}

function recargarTablaIngresoEgresoCategorias() {
  tablaIngresoEgresoCategorias?.ajax.reload(null, false);
}

function mostrarErroresValidacionIngresoEgresoCategoria(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const validator = $('#form-ingreso-egreso-categoria').data('validator');
    const normalizedErrors = {};
    Object.entries(errors).forEach(function ([key, value]) {
      normalizedErrors[key] = Array.isArray(value) ? value[0] : value;
    });
    if (validator) {
      validator.showErrors(normalizedErrors);
    }
    mostrarErrorIngresoEgresoCategoria(Object.values(normalizedErrors)[0] || 'Revise los campos del formulario.');
    return;
  }
  mostrarErrorIngresoEgresoCategoria(response.message || 'Error al guardar categoria.');
}

function confirmarAccionIngresoEgresoCategoria(title, text, confirmButtonText, callback) {
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

function cambiarEstadoBotonIngresoEgresoCategoria($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function mostrarOkIngresoEgresoCategoria(message) {
  if (typeof toastr !== 'undefined') toastr.success(message);
  else alert(message);
}

function mostrarErrorIngresoEgresoCategoria(message) {
  if (typeof toastr !== 'undefined') toastr.error(message);
  else alert(message);
}

function escapeHtmlIngresoEgresoCategoria(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
