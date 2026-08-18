let tablaTours = null;
let catalogosTours = {
  turnos: [],
};

function puedeTour(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('tours', accion);
}

function apiUrl(path) {
  const base = document.querySelector('meta[name="app-url"]')?.content || '';
  return base + path;
}

function csrf() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

function ajaxHeaders() {
  return {
    'X-CSRF-TOKEN': csrf(),
    Accept: 'application/json',
  };
}

$(function () {
  inicializarPantallaTours();
});

async function inicializarPantallaTours() {
  try {
    await cargarCatalogosTours();
    inicializarSelect2Tours();
    inicializarTablaTours();
    inicializarFormularioTours();
    enlazarEventosTours();
  } catch (error) {
    mostrarErrorTour('No se pudo inicializar el mÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³dulo de tours.');
    console.error(error);
  }
}

function enlazarEventosTours() {
  $('#btn-nuevo-tour').on('click', prepararNuevoTour);
  $('#btn-recargar-tours').on('click', recargarTablaTours);
  $('#btn-nuevo-turno').on('click', crearTurnoRapido);
  $('#incluir-eliminados-tours').on('change', recargarTablaTours);
  $('#btn-guardar-tour').on('click', function () {
    $('#form-tour').submit();
  });

  $('#tabla-tours tbody').on('click', '.btn-editar-tour', function () {
    editarTour($(this).data('id'));
  });

  $('#tabla-tours tbody').on('click', '.btn-eliminar-tour', function () {
    eliminarTour($(this).data('id'));
  });

  $('#tabla-tours tbody').on('click', '.btn-restaurar-tour', function () {
    restaurarTour($(this).data('id'));
  });
}

function inicializarSelect2Tours() {
  const options = {
    theme: 'bootstrap4',
    width: '100%',
    allowClear: true,
    placeholder: 'Seleccione',
    dropdownParent: $('#modal-nuevo-tour'),
  };

  $('#tour_idtours_turno').select2(options);
  $('#tour_idubigeo_distrito').select2(Object.assign({}, options, {
    ajax: {
      url: apiUrl('/tours/distritos'),
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          term: params.term || '',
          page: params.page || 1,
        };
      },
      processResults: function (data) {
        return {
          results: data.results || [],
          pagination: data.pagination || { more: false },
        };
      },
      headers: ajaxHeaders(),
    },
    minimumInputLength: 1,
    templateResult: function (item) {
      return item.text || item.id;
    },
    templateSelection: function (item) {
      return item.text || item.id;
    },
  }));

  $('#tour_idtours_turno, #tour_idubigeo_distrito').on('change', function () {
    if ($('#form-tour').data('validator')) {
      $(this).valid();
    }
    actualizarEstadoSelect2($(this));
  });
}

async function cargarCatalogosTours() {
  const response = await $.ajax({
    url: apiUrl('/tours/catalogos'),
    type: 'GET',
    headers: ajaxHeaders(),
  });

  if (!response?.status) {
    throw new Error(response?.message || 'No se pudieron cargar los catÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡logos de tours.');
  }

  catalogosTours = response.data || catalogosTours;
  renderCatalogosTours();
}

function renderCatalogosTours() {
  renderSelect('#tour_idtours_turno', catalogosTours.turnos || [], 'idtours_turno', 'descripcion');
}

function renderSelect(selector, items, valueKey, labelKey) {
  const $select = $(selector);
  const value = $select.val();

  $select.empty().append('<option value="">Seleccione</option>');

  items.forEach((item) => {
    const option = new Option(item[labelKey] || '-', item[valueKey], false, false);
    $select.append(option);
  });

  if (value) {
    $select.val(String(value));
  }

  $select.trigger('change.select2');
}

function inicializarTablaTours() {
  tablaTours = $('#tabla-tours').DataTable({
    responsive: true,
    processing: true,
    serverSide: true,
    deferRender: true,
    searchDelay: 350,
    dom: "<'row'<'col-md-8 pt-2'f><'col-md-4 pt-2 d-flex justify-content-end'<'length'l><'buttons'B>>>r t <'row'<'col-md-6'i><'col-md-6'p>>",
    buttons: [
      {
        text: '<i class="bi bi-arrow-clockwise"></i>',
        className: 'buttons-reload btn btn-outline-info',
        action: function (_e, dt) {
          dt.ajax.reload(null, false);
        },
      },
      {
        extend: 'excel',
        exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8] },
        title: 'Listado de tours',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/tours/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function (data) {
        data.incluir_trash = $('#incluir-eliminados-tours').is(':checked') ? 1 : 0;
      },
      dataSrc: function (response) {
        if (response && response.status === false) {
          mostrarErrorTour(response?.message || 'No se pudo listar los tours.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorTour('Error al consultar tours.');
      },
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const actions = [];

          if (puedeTour('editar')) {
            actions.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-tour" data-id="${row.idtours}" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '1' && puedeTour('eliminar')) {
            actions.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-tour" data-id="${row.idtours}" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '0' && puedeTour('editar')) {
            actions.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-tour" data-id="${row.idtours}" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return actions.join('') || '<span class="text-muted">-</span>';
        },
      },
      { data: 'codigo', render: renderPlano },
      { data: 'nombre', render: renderNegrita },
      { data: 'turno.descripcion', defaultContent: '-', render: renderPlano },
      { data: 'distrito.nombre', defaultContent: '-', render: renderPlano },
      { data: 'precio_publico', className: 'text-end', render: renderMoneda },
      { data: 'precio_corporativo', className: 'text-end', render: renderMoneda },
      { data: 'precio_tours', className: 'text-end', render: renderMoneda },
      { data: 'precio_web', className: 'text-end', render: renderMoneda },
      {
        data: 'estado_trash',
        className: 'text-center',
        render: (estado) => String(estado) === '1'
          ? '<span class="badge bg-success-transparent">Activo</span>'
          : '<span class="badge bg-danger-transparent">Eliminado</span>',
      },
    ],
    order: [[1, 'desc']],
    language: {
      lengthMenu: '_MENU_',
      sLoadingRecords: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...',
      search: '',
    },
  });
}

function inicializarFormularioTours() {
  $('#form-tour').validate({
    ignore: '.select2-search__field, .select2-input, .select2-focusser',
    rules: {
      idtours_turno: { required: true },
      idubigeo_distrito: { required: true },
      nombre: { required: true, maxlength: 250 },
      codigo: { maxlength: 10 },
      precio_publico: { number: true, min: 0 },
      precio_corporativo: { number: true, min: 0 },
      precio_tours: { number: true, min: 0 },
      precio_web: { number: true, min: 0 },
      duracion: { maxlength: 225 },
      descripcion_inicial: { maxlength: 1000 },
      descripcion: { maxlength: 2000 },
      descripcion_momento_destacados: { maxlength: 2000 },
      informacion_importante: { maxlength: 2000 },
      descripcion_incluye_noincluye: { maxlength: 2000 },
      ubicacion_maps: { maxlength: 2000 },
      brochure: { maxlength: 255 },
    },
    messages: {
      idtours_turno: { required: 'Seleccione un turno.' },
      idubigeo_distrito: { required: 'Seleccione un distrito.' },
      nombre: { required: 'Campo requerido.', maxlength: 'MAXIMO {0} caracteres.' },
      codigo: { maxlength: 'MAXIMO {0} caracteres.' },
      precio_publico: { number: 'Ingrese un numero valido.', min: 'Ingrese un numero mayor o igual a {0}.' },
      precio_corporativo: { number: 'Ingrese un numero valido.', min: 'Ingrese un numero mayor o igual a {0}.' },
      precio_tours: { number: 'Ingrese un numero valido.', min: 'Ingrese un numero mayor o igual a {0}.' },
      precio_web: { number: 'Ingrese un numero valido.', min: 'Ingrese un numero mayor o igual a {0}.' },
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

      if ($(element).hasClass('select2-hidden-accessible')) {
        $(element).next('.select2-container').find('.select2-selection').addClass('is-invalid').removeClass('is-valid');
      }
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid').addClass('is-valid');

      if ($(element).hasClass('select2-hidden-accessible')) {
        $(element).next('.select2-container').find('.select2-selection').removeClass('is-invalid').addClass('is-valid');
      }
    },
    submitHandler: function () {
      guardarTour();
    },
  });
}

function crearTurnoRapido() {
  Swal.fire({
    // El aviso se abre desde un modal de Bootstrap. Al montarlo dentro del
    // mismo modal, el focus trap de Bootstrap permite escribir en el input.
    target: document.getElementById('modal-nuevo-tour'),
    title: 'Nuevo turno',
    input: 'text',
    inputLabel: 'Descripción del turno',
    inputPlaceholder: 'Ej. Mañana',
    showCancelButton: true,
    confirmButtonText: 'Guardar',
    cancelButtonText: 'Cancelar',
    didOpen: () => {
      Swal.getInput()?.focus();
    },
    inputValidator: (value) => !String(value || '').trim() ? 'Ingrese la descripción del turno.' : undefined,
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.ajax({
      url: apiUrl('/tours/turnos/store'),
      type: 'POST',
      headers: ajaxHeaders(),
      data: { descripcion: result.value.trim() },
      success: async function (response) {
        if (!response?.status) {
          mostrarErrorTour(response?.message || 'No se pudo registrar el turno.');
          return;
        }

        await cargarCatalogosTours();
        $('#tour_idtours_turno').val(String(response.data.idtours_turno)).trigger('change');
        mostrarOkTour(response.message || 'Turno registrado correctamente.');
      },
      error: function (xhr) {
        mostrarErrorTour(extraerPrimerError(xhr) || 'No se pudo registrar el turno.');
      },
    });
  });
}
function prepararNuevoTour() {
  if (!puedeTour('crear')) {
    mostrarErrorTour('No tienes permiso para crear tours.');
    return;
  }

  limpiarFormularioTour();
  $('#modal-nuevo-tour-label').text('Nuevo Tour');
  $('#btn-guardar-tour').text('Guardar');
  $('#modal-nuevo-tour').modal('show');
}

function editarTour(id) {
  if (!puedeTour('editar')) {
    mostrarErrorTour('No tienes permiso para editar tours.');
    return;
  }

  $.ajax({
    url: apiUrl(`/tours/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response?.status) {
        mostrarErrorTour(response?.message || 'No se pudo cargar el tour.');
        return;
      }

      cargarTourEnFormulario(response.data || {});
      $('#modal-nuevo-tour-label').text('Editar Tour');
      $('#btn-guardar-tour').text('Actualizar');
      $('#modal-nuevo-tour').modal('show');
    },
    error: function () {
      mostrarErrorTour('Error al cargar tour.');
    },
  });
}

function cargarTourEnFormulario(data) {
  limpiarFormularioTour();

  $('#tour_idtours').val(data.idtours || '');
  $('#tour_codigo').val(data.codigo || '');
  $('#tour_nombre').val(data.nombre || '');
  $('#tour_idtours_turno').val(data.idtours_turno || '').trigger('change');
  $('#tour_idubigeo_distrito').append(new Option(data.distrito?.nombre || '', data.idubigeo_distrito || '', true, true)).trigger('change');
  $('#tour_precio_publico').val(data.precio_publico ?? '');
  $('#tour_precio_corporativo').val(data.precio_corporativo ?? '');
  $('#tour_precio_tours').val(data.precio_tours ?? '');
  $('#tour_precio_web').val(data.precio_web ?? '');
  $('#tour_duracion').val(data.duracion || '');
  $('#tour_hora_recojo').val(data.hora_recojo || '');
  $('#tour_hora_retorno').val(data.hora_retorno || '');
  $('#tour_descripcion_inicial').val(data.descripcion_inicial || '');
  $('#tour_descripcion').val(data.descripcion || '');
  $('#tour_descripcion_momento_destacados').val(data.descripcion_momento_destacados || '');
  $('#tour_informacion_importante').val(data['informaciÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³n importante'] || '');
  $('#tour_descripcion_incluye_noincluye').val(data.descripcion_incluye_noincluye || '');
  $('#tour_ubicacion_maps').val(data.ubicacion_maps || '');
  $('#tour_brochure').val(data.brochure || '');
}

function guardarTour() {
  const id = $('#tour_idtours').val();
  const url = id ? apiUrl(`/tours/${id}/update`) : apiUrl('/tours/store');
  const method = id ? 'PUT' : 'POST';
  const data = $('#form-tour').serialize();

  $.ajax({
    url,
    type: method,
    headers: ajaxHeaders(),
    data,
    success: function (response) {
      if (!response?.status) {
        mostrarErrorTour(response?.message || 'No se pudo guardar el tour.');
        return;
      }

      $('#modal-nuevo-tour').modal('hide');
      mostrarOkTour(response.message || 'Tour guardado correctamente.');
      recargarTablaTours();
    },
    error: function (xhr) {
      mostrarErrorTour(extraerPrimerError(xhr) || 'Error al guardar tour.');
    },
  });
}

function eliminarTour(id) {
  confirmarAccion('ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¿Deseas eliminar este tour?', function () {
    $.ajax({
      url: apiUrl(`/tours/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response?.status) {
          mostrarErrorTour(response?.message || 'No se pudo eliminar el tour.');
          return;
        }

        mostrarOkTour(response.message || 'Tour eliminado correctamente.');
        recargarTablaTours();
      },
      error: function (xhr) {
        mostrarErrorTour(extraerPrimerError(xhr) || 'Error al eliminar tour.');
      },
    });
  });
}

function restaurarTour(id) {
  confirmarAccion('ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¿Deseas restaurar este tour?', function () {
    $.ajax({
      url: apiUrl(`/tours/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response?.status) {
          mostrarErrorTour(response?.message || 'No se pudo restaurar el tour.');
          return;
        }

        mostrarOkTour(response.message || 'Tour restaurado correctamente.');
        recargarTablaTours();
      },
      error: function (xhr) {
        mostrarErrorTour(extraerPrimerError(xhr) || 'Error al restaurar tour.');
      },
    });
  });
}

function recargarTablaTours() {
  if (tablaTours) {
    tablaTours.ajax.reload(null, false);
  }
}

function limpiarFormularioTour() {
  $('#form-tour')[0].reset();
  $('#tour_idtours').val('');
  $('#tour_idtours_turno').val('').trigger('change');
  $('#tour_idubigeo_distrito').val('').trigger('change');
  $('#tour_nombre').removeClass('is-valid is-invalid');
  $('#tour_codigo').removeClass('is-valid is-invalid');
  $('#tour_idtours_turno').next('.select2-container').find('.select2-selection').removeClass('is-valid is-invalid');
  $('#tour_idubigeo_distrito').next('.select2-container').find('.select2-selection').removeClass('is-valid is-invalid');
  $('#form-tour').validate().resetForm();
}

function confirmarAccion(texto, callback) {
  Swal.fire({
    title: 'Confirmar',
    text: texto,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'SÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­',
    cancelButtonText: 'Cancelar',
  }).then(function (result) {
    if (result.isConfirmed) {
      callback();
    }
  });
}

function actualizarEstadoSelect2($element) {
  if (!$element?.hasClass('select2-hidden-accessible')) return;

  const $selection = $element.next('.select2-container').find('.select2-selection');
  const isValid = String($element.val() || '').trim() !== '';

  $selection.toggleClass('is-valid', isValid);
  $selection.toggleClass('is-invalid', !isValid);
}

function extraerPrimerError(xhr) {
  const response = xhr?.responseJSON || {};
  const errors = response?.data || response?.errors || {};
  const first = Object.values(errors)[0];
  if (!first) return null;
  return Array.isArray(first) ? first[0] : first;
}

function renderNegrita(data) {
  return `<span class="fw-semibold">${escapeHtml(data || '-')}</span>`;
}

function renderPlano(data) {
  if (data === null || data === undefined || data === '') return '<span class="text-muted">-</span>';
  return escapeHtml(data);
}

function renderMoneda(data) {
  if (data === null || data === undefined || data === '') return '<span class="text-muted">-</span>';
  return `S/ ${Number(data).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function mostrarOkTour(message) {
  if (typeof toastr !== 'undefined') {
    toastr.success(message);
    return;
  }
  alert(message);
}

function mostrarErrorTour(message) {
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
