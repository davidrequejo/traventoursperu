let tablaSeriesComprobantes = null;
let tiposComprobante = [];
let envioSerieEnCurso = false;

function puedeSerieComprobante(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('series_de_comprobantes', accion);
}

$(function () {
  cargarTiposComprobantes().always(function () {
    inicializarSelectsSerieComprobante();
    inicializarTablaSeriesComprobantes();
    inicializarFormularioSerieComprobante();
  });

  $('#btn-nueva-serie-comprobante').on('click', prepararNuevaSerieComprobante);
  $('#btn-regresar-serie-comprobante, #btn-cancelar-serie-comprobante').on('click', function () {
    mostrarVistaSerieComprobante('tabla');
  });
  $('#btn-recargar-serie-comprobante').on('click', recargarTablaSeriesComprobantes);
  $('#incluir-desactivados-serie-comprobante').on('change', recargarTablaSeriesComprobantes);
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

function cargarTiposComprobantes() {
  return $.ajax({
    url: apiUrl('/sunat/series-comprobantes/tipos'),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      tiposComprobante = response.status ? (response.data || []) : [];
    },
    error: function (xhr) {
      const response = obtenerRespuestaErrorApi(xhr);
      mostrarError(obtenerMensajeApi(response, 'Error al cargar tipos de comprobantes.'));
    },
  });
}

function inicializarSelectsSerieComprobante() {
  llenarSelectTipoComprobante('#serie_idsunat_c01_tipo_comprobante', 'Seleccionar tipo de comprobante');
  llenarSelectTipoComprobante('#serie_tipo_comprobante_adicional', 'Sin tipo adicional', true);

  $('#serie_idsunat_c01_tipo_comprobante').select2({
    theme: 'bootstrap4',
    allowClear: true,
    width: '100%',
    placeholder: "Seleccione",
  });

  $('#serie_tipo_comprobante_adicional').select2({
    theme: 'bootstrap4',
    allowClear: true,
    width: '100%',
    placeholder: "Seleccione",
  });
}

function llenarSelectTipoComprobante(selector, placeholder, permitirVacio = false) {
  const options = [];  

  tiposComprobante.forEach(function (tipo) {
    const text = `${tipo.codigo || ''} - ${tipo.abreviatura || tipo.nombre || ''}`.trim();
    options.push(`<option value="${tipo.idsunat_c01_tipo_comprobante}">${escapeHtml(text)}</option>`);
  });

  $(selector).html(options.join(''));
}

function inicializarTablaSeriesComprobantes() {
  tablaSeriesComprobantes = $('#tabla-series-comprobantes').DataTable({
    responsive: false,
    processing: true,
    serverSide: true,
    deferRender: true,
    searchDelay: 350,
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
        exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7] },
        title: 'Series de comprobantes',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/sunat/series-comprobantes/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function (data) {
        data.incluir_trash = $('#incluir-desactivados-serie-comprobante').is(':checked') ? 1 : 0;
      },
      error: function (xhr) {
        const response = obtenerRespuestaErrorApi(xhr);
        mostrarError(obtenerMensajeApi(response, 'Error al consultar series.'));
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

          if (puedeSerieComprobante('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-serie-comprobante" data-id="${row.idserie_comprobante}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '1' && puedeSerieComprobante('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-desactivar-serie-comprobante" data-id="${row.idserie_comprobante}" data-bs-toggle="tooltip" title="Desactivar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '0' && puedeSerieComprobante('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-serie-comprobante" data-id="${row.idserie_comprobante}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      { data: 'idserie_comprobante', className: 'text-center' },
      {
        data: 'tipo_comprobante',
        render: function (tipo) {
          if (!tipo) return '<span class="text-muted">Sin comprobante</span>';
          return `<span class="fw-semibold">${escapeHtml(tipo.codigo || '')}</span> ${escapeHtml(tipo.abreviatura || tipo.nombre || '')}`;
        },
      },
      { data: 'serie', className: 'text-center fw-semibold' },
      {
        data: 'numero',
        className: 'text-center',
        render: function (data) {
          return data !== null && data !== undefined ? escapeHtml(data) : '<span class="text-muted">-</span>';
        },
      },
      {
        data: 'comprobante_adicional',
        render: function (tipo) {
          if (!tipo) return '<span class="text-muted">-</span>';
          return `${escapeHtml(tipo.codigo || '')} - ${escapeHtml(tipo.abreviatura || tipo.nombre || '')}`;
        },
      },
      {
        data: 'predeterminado',
        className: 'text-center',
        render: function (data) {
          return String(data) === '1'
            ? '<span class="badge bg-primary-transparent">Si</span>'
            : '<span class="text-muted">No</span>';
        },
      },
      {
        data: 'estado_trash',
        className: 'text-center',
        render: function (estado) {
          return String(estado) === '1'
            ? '<span class="badge bg-success-transparent">Activo</span>'
            : '<span class="badge bg-danger-transparent">Desactivado</span>';
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
    order: [[1, 'desc']],
  });

  $('#tabla-series-comprobantes tbody').on('click', '.btn-editar-serie-comprobante', function () {
    editarSerieComprobante($(this).data('id'));
  });

  $('#tabla-series-comprobantes tbody').on('click', '.btn-desactivar-serie-comprobante', function () {
    desactivarSerieComprobante($(this).data('id'));
  });

  $('#tabla-series-comprobantes tbody').on('click', '.btn-restaurar-serie-comprobante', function () {
    restaurarSerieComprobante($(this).data('id'));
  });
}

function inicializarFormularioSerieComprobante() {
  $('#form-serie-comprobante').validate({
    ignore: '.select2-search__field, .select2-input, .select2-focusser',
    rules: {
      idsunat_c01_tipo_comprobante: { required: true },
      serie: {
        required: true,
        maxlength: 10,
        remote: {
          url: apiUrl('/sunat/series-comprobantes/validar-serie'),
          type: 'GET',
          headers: ajaxHeaders(),
          data: {
            idserie_comprobante: function () {
              return $('#idserie_comprobante').val();
            },
            serie: function () {
              return $('#serie_comprobante_serie').val();
            },
          },
        },
      },
      numero: { digits: true, min: 0 },
    },
    messages: {
      idsunat_c01_tipo_comprobante: { required: 'Campo requerido.' },
      serie: { required: 'Campo requerido.', maxlength: 'MAXIMO {0} caracteres.', remote: 'La serie ya esta registrada.' },
      numero: { digits: 'Ingrese solo numeros.', min: 'Ingrese un numero mayor o igual a {0}.' },
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');

      if ($(element).hasClass('select2-hidden-accessible')) {
        error.insertAfter($(element).next('.select2-container'));
        return;
      }

      error.insertAfter(element);
    },
    highlight: function (element) {
      $(element).addClass('is-invalid').removeClass('is-valid');
      marcarSelect2SerieComprobante(element, true);
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid').addClass('is-valid');
      marcarSelect2SerieComprobante(element, false);
    },
    submitHandler: guardarSerieComprobante,
  });
}

function marcarSelect2SerieComprobante(element, invalid) {
  const $element = $(element);
  if (!$element.hasClass('select2-hidden-accessible')) return;

  $element.next('.select2-container')
    .find('.select2-selection')
    .toggleClass('is-invalid', invalid)
    .toggleClass('is-valid', !invalid);
}

function prepararNuevaSerieComprobante() {
  if (!puedeSerieComprobante('crear')) {
    mostrarError('No tienes permiso para crear series.');
    return;
  }

  limpiarFormularioSerieComprobante();
  $('#titulo-formulario-serie-comprobante').text('Nueva serie');
  $('#btn-guardar-serie-comprobante').html('<i class="ti ti-device-floppy"></i> Guardar');
  mostrarVistaSerieComprobante('formulario');
}

function editarSerieComprobante(id) {
  if (!puedeSerieComprobante('editar')) {
    mostrarError('No tienes permiso para editar series.');
    return;
  }

  $.getJSON(apiUrl(`/sunat/series-comprobantes/${id}`), function (response) {
    if (!response.status) {
      mostrarError(response.message || 'No se pudo cargar la serie.');
      return;
    }

    cargarSerieComprobanteEnFormulario(response.data);
    mostrarVistaSerieComprobante('formulario');
  }).fail(function (xhr) {
    const response = obtenerRespuestaErrorApi(xhr);
    mostrarError(obtenerMensajeApi(response, 'Error al cargar serie.'));
  });
}

function cargarSerieComprobanteEnFormulario(data) {
  limpiarFormularioSerieComprobante();
  $('#titulo-formulario-serie-comprobante').text('Editar serie');
  $('#btn-guardar-serie-comprobante').html('<i class="ti ti-device-floppy"></i> Actualizar');
  $('#idserie_comprobante').val(data.idserie_comprobante);
  $('#serie_idsunat_c01_tipo_comprobante').val(data.idsunat_c01_tipo_comprobante).trigger('change');
  $('#serie_tipo_comprobante_adicional').val(data.tipo_comprobante_adicional).trigger('change');
  $('#serie_comprobante_serie').val(data.serie);
  $('#serie_comprobante_numero').val(data.numero);
  $('#serie_comprobante_predeterminado').prop('checked', String(data.predeterminado) === '1');
}

function guardarSerieComprobante(form) {
  if (envioSerieEnCurso) {
    return;
  }

  const id = $('#idserie_comprobante').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedeSerieComprobante('editar')) {
    mostrarError('No tienes permiso para editar series.');
    return;
  }

  if (!esEdicion && !puedeSerieComprobante('crear')) {
    mostrarError('No tienes permiso para crear series.');
    return;
  }

  const $button = $('#btn-guardar-serie-comprobante');
  const formData = new FormData(form);
  const url = esEdicion ? apiUrl(`/sunat/series-comprobantes/${id}`) : apiUrl('/sunat/series-comprobantes');

  if (esEdicion) {
    formData.append('_method', 'PUT');
  }

  envioSerieEnCurso = true;
  cambiarEstadoBoton($button, true, '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...');
  cambiarEstadoFormularioSerieComprobante(true);

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo guardar la serie.');
        return;
      }

      mostrarOk(response.message || 'Serie guardada correctamente.');
      mostrarVistaSerieComprobante('tabla');
      recargarTablaSeriesComprobantes();
    },
    error: function (xhr) {
      mostrarErroresValidacionSerieComprobante(xhr);
    },
    complete: function () {
      envioSerieEnCurso = false;
      cambiarEstadoFormularioSerieComprobante(false);
      cambiarEstadoBoton($button, false, esEdicion ? '<i class="ti ti-device-floppy"></i> Actualizar' : '<i class="ti ti-device-floppy"></i> Guardar');
    },
  });
}

function desactivarSerieComprobante(id) {
  if (!puedeSerieComprobante('eliminar')) {
    mostrarError('No tienes permiso para desactivar series.');
    return;
  }

  confirmarAccionSerie('Desactivar serie', 'La serie dejara de mostrarse como activa.', 'Si, desactivar', function () {
    $.ajax({
      url: apiUrl(`/sunat/series-comprobantes/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        mostrarOk(response.message || 'Serie desactivada correctamente.');
        recargarTablaSeriesComprobantes();
      },
      error: function (xhr) {
        const response = obtenerRespuestaErrorApi(xhr);
        mostrarError(obtenerMensajeApi(response, 'Error al desactivar serie.'));
      },
    });
  });
}

function restaurarSerieComprobante(id) {
  if (!puedeSerieComprobante('editar')) {
    mostrarError('No tienes permiso para restaurar series.');
    return;
  }

  confirmarAccionSerie('Restaurar serie', 'La serie volvera a estar activa.', 'Si, restaurar', function () {
    $.ajax({
      url: apiUrl(`/sunat/series-comprobantes/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        mostrarOk(response.message || 'Serie restaurada correctamente.');
        recargarTablaSeriesComprobantes();
      },
      error: function (xhr) {
        const response = obtenerRespuestaErrorApi(xhr);
        mostrarError(obtenerMensajeApi(response, 'Error al restaurar serie.'));
      },
    });
  });
}

function limpiarFormularioSerieComprobante() {
  
  $('#idserie_comprobante').val('');
  $('#serie_idsunat_c01_tipo_comprobante').val('').trigger('change');
  $('#serie_tipo_comprobante_adicional').val('').trigger('change');
  $('#serie_comprobante_predeterminado').prop('checked', false);

  $(".form-control").removeClass('is-valid');
  $(".form-control").removeClass('is-invalid');
  $(".error.invalid-feedback").remove();
}

function mostrarVistaSerieComprobante(vista) {
  const esFormulario = vista === 'formulario';
  $('#div-tabla-series-comprobantes').toggle(!esFormulario);
  $('#div-formulario-serie-comprobante').toggle(esFormulario);
  $('#btn-regresar-serie-comprobante').toggle(esFormulario);
  $('#btn-nueva-serie-comprobante').toggle(!esFormulario && puedeSerieComprobante('crear'));
}

function recargarTablaSeriesComprobantes() {
  tablaSeriesComprobantes?.ajax.reload(null, false);
}

function confirmarAccionSerie(title, text, confirmButtonText, callback) {
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
    if (result.isConfirmed) {
      callback();
    }
  });
}

function cambiarEstadoBoton($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function cambiarEstadoFormularioSerieComprobante(disabled) {
  $('#form-serie-comprobante')
    .find('input, select, textarea, button')
    .not('#btn-guardar-serie-comprobante')
    .prop('disabled', disabled);
}

function mostrarErroresValidacionSerieComprobante(xhr) {
  const response = obtenerRespuestaErrorApi(xhr);
  const errors = obtenerErroresApi(response);

  if (xhr.status === 422 && Object.keys(errors).length) {
    const validator = $('#form-serie-comprobante').data('validator');
    const normalizedErrors = {};

    Object.entries(errors).forEach(function ([key, value]) {
      normalizedErrors[key] = Array.isArray(value) ? value[0] : value;
    });

    if (validator) {
      validator.showErrors(normalizedErrors);
    }

    mostrarError(obtenerMensajeApi(response, Object.values(normalizedErrors)[0] || 'Revise los campos del formulario.'));
    return;
  }

  mostrarError(obtenerMensajeApi(response, 'Error al guardar serie.'));
}

function obtenerRespuestaErrorApi(xhr) {
  return xhr?.responseJSON || {};
}

function obtenerErroresApi(response) {
  // Formato ApiResponse::validation => { status:false, message:'...', data:{ campo:[...] } }
  if (response && typeof response.data === 'object' && !Array.isArray(response.data)) {
    return response.data;
  }

  return {};
}

function obtenerMensajeApi(response, fallback) {
  if (response?.message) {
    return response.message;
  }

  const errors = obtenerErroresApi(response);
  const firstError = Object.values(errors).flat()[0];
  return firstError || fallback;
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
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
