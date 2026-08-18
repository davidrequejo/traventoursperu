let tablaTrabajadores = null;
let trabajadorSeleccionadoId = null;

function puedeTrabajador(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('trabajadores', accion);
}

$(function () {
  inicializarSelectsTrabajador();
  inicializarTablaTrabajadores();
  inicializarFormularioTrabajador();
  inicializarMenuContextualTrabajador();

  $('#btn-nuevo-trabajador').on('click', function () {
    if (!puedeTrabajador('crear')) {
      mostrarError('No tienes permiso para crear trabajadores.');
      return;
    }

    prepararNuevoTrabajador();
  });
  $('#btn-regresar-trabajador, #btn-cancelar-trabajador').on('click', function () {
    mostrarVistaTrabajadores('tabla');
  });
  $('#btn-recargar-trabajadores').on('click', recargarTablaTrabajadores);
  $('#btn-remover-imagen-trabajador').on('click', removerImagenTrabajador);

  $('#trabajador_imagen').on('change', previsualizarImagenTrabajador);
  $('#trabajador_sexo').on('change', function () {
    if (!$('#trabajador_imagenactual').val() && !$('#trabajador_imagen').val()) {
      actualizarImagenDefaultTrabajador();
    }
  });

  $('#trabajador_iddistrito').on('change', actualizarUbigeoTrabajador);
  $('#btn-buscar-documento-trabajador').on('click', buscarDocumentoTrabajador);
  $('#form-trabajador').on('input change keyup', 'input, select, textarea', function () {
    actualizarIndicadorCamposRequeridosTrabajador();
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

function actualizarIndicadorCamposRequeridosTrabajador() {
  const $form = $('#form-trabajador');
  const $indicador = $('#indicador-campos-requeridos-trabajador');
  const $icono = $('#icono-campos-requeridos-trabajador');
  const $path = $('#path-campos-requeridos-trabajador');
  const iconoCompleto = 'M20 8h-5.61l1.12-3.37c.2-.61.1-1.28-.27-1.8-.38-.52-.98-.83-1.62-.83h-1.61c-.3 0-.58.13-.77.36L6.54 8H4.01c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h13.31a2 2 0 0 0 1.87-1.3l2.76-7.35c.04-.11.06-.23.06-.35v-2c0-1.1-.9-2-2-2ZM6 19H4v-9h2zm14-7.18L17.31 19H8V9.36L12.47 4h1.15l-1.56 4.68a1.01 1.01 0 0 0 .95 1.32h7v1.82Z';
  const iconoPendiente = 'M20 3H6.69a2 2 0 0 0-1.87 1.3L2.06 11.65c-.04.11-.06.23-.06.35v2c0 1.1.9 2 2 2h5.61l-1.12 3.37c-.2.61-.1 1.28.27 1.8.38.52.98.83 1.62.83h1.61c.3 0 .58-.13.77-.36L17.46 16H20c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2ZM16 14.64 11.53 20h-1.15l1.56-4.68A1.01 1.01 0 0 0 10.99 14H4v-1.82L6.69 5H16v9.64ZM20 14h-2V5h2v9Z';

  if (!$form.length || !$indicador.length || !$icono.length || !$path.length) return;

  const validator = $form.data('validator');
  const rules = validator?.settings?.rules || {
    tipo_documento: { required: true },
    numero_documento: { required: true },
    descripcion: { required: true },
  };

  const requiredFields = Object.keys(rules).filter(function (name) {
    const rule = rules[name];
    return rule === 'required' || rule?.required === true;
  });

  const completos = requiredFields.length > 0 && requiredFields.every(function (name) {
    const $field = $form.find(`[name="${name}"]`);
    if (!$field.length || $field.prop('disabled')) return true;

    if ($field.is(':checkbox, :radio')) {
      return $form.find(`[name="${name}"]:checked`).length > 0;
    }

    const value = $field.val();
    return Array.isArray(value)
      ? value.length > 0
      : String(value || '').trim() !== '';
  });

  $indicador
    .toggleClass('bg-primary-transparent', !completos)
    .toggleClass('bg-success-transparent', completos)
    .attr('title', completos ? 'Campos requeridos completos' : 'Campos requeridos pendientes');

  $icono.attr('fill', completos ? '#009551' : '#989797');
  $path.attr('d', completos ? iconoCompleto : iconoPendiente);
}

function inicializarSelectsTrabajador() {
  $('#trabajador_tipo_persona_sunat, #trabajador_tipo_documento, #trabajador_sexo, #trabajador_estado_civil, #trabajador_nacionalidad').select2({
    theme: 'bootstrap4',
    allowClear: true,
    placeholder: 'Seleccione',
    width: '100%',
  });

  lista_select2(apiUrl('/select2/select2distrito'), '#trabajador_iddistrito');
  $('#trabajador_iddistrito').select2({
    theme: 'bootstrap4',
    allowClear: true,
    placeholder: 'Seleccionar distrito',
    width: '100%',
  });

  cargarCargosTrabajador();
}

function cargarCargosTrabajador() {
  const $cargo = $('#trabajador_idcargo');

  if (!$cargo.hasClass('select2-hidden-accessible')) {
    $cargo.select2({
      theme: 'bootstrap4',
      allowClear: true,
      placeholder: 'Seleccionar cargo',
      width: '100%',
    });
  }

  $.ajax({
    url: apiUrl('/trabajadores/cargos'),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      const cargos = response.status ? (response.data || []) : [];
      const options = ['<option value="">Seleccione cargo</option>']
        .concat(cargos.map((cargo) => `<option value="${cargo.idpersona_cargo}">${escapeHtml(cargo.nombre)}</option>`));

      $cargo
        .html(options.join(''))
        .trigger('change');
    },
    error: function () {
      mostrarError('No se pudo cargar la lista de cargos.');
    },
  });
}

function inicializarTablaTrabajadores() {
  tablaTrabajadores = $('#tabla-trabajadores').DataTable({
    responsive: false,
    processing: true,
    deferRender: true,
    dom: "<'row'<'col-md-7 col-lg-8 col-xl-9 col-xxl-10 pt-2'f><'col-md-5 col-lg-4 col-xl-3 col-xxl-2 pt-2 d-flex justify-content-end align-items-center'<'length'l><'buttons'B>>>r t <'row'<'col-md-6'i><'col-md-6'p>>",
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
        title: 'Lista de trabajadores',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/trabajadores/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      dataSrc: function (response) {
        if (!response.status) {
          mostrarError(response.message || 'No se pudo cargar trabajadores.');
          return [];
        }

        return response.data || [];
      },
      error: function () {
        mostrarError('Error al consultar trabajadores.');
      },
    },
    columns: [
      { data: 'codigo', className: 'text-center', defaultContent: '-' },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const acciones = [`
            <button type="button" class="btn btn-sm btn-icon btn-info btn-ver-trabajador" data-id="${row.idpersona}" data-bs-toggle="tooltip" title="Ver">
              <i class="ri-eye-line"></i>
            </button>`];

          if (puedeTrabajador('editar')) {
            acciones.push(`
            <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-trabajador" data-id="${row.idpersona}" data-bs-toggle="tooltip" title="Editar">
              <i class="ri-edit-line"></i>
            </button>`);
          }

          if (puedeTrabajador('eliminar')) {
            acciones.push(`
            <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-trabajador" data-id="${row.idpersona}" data-bs-toggle="tooltip" title="Eliminar">
              <i class="ri-delete-bin-line"></i>
            </button>`);
          }

          return acciones.join('');
        },
      },
      {
        data: null,
        className: 'trabajador-persona',
        render: function (row) {
          const documento = [row.tipo_documento_label || row.tipo_documento, row.numero_documento].filter(Boolean).join(' ');
          const fotoPerfil = row.foto_perfil || (row.sexo === 'F' ? 'mujer.png' : 'hombre.png');
          const fotoPerfilUrl = apiUrl(`/assets/modulo/persona/perfil/${fotoPerfil}`);

          return `
            <div class="d-flex align-items-center">
              <span class="avatar avatar-md me-2">
                <img src="${fotoPerfilUrl}" alt="" onerror="this.src='${apiUrl('/assets/modulo/persona/perfil/hombre.png')}';">
              </span>
              <div>
                <p class="fw-semibold mb-0">${escapeHtml(row.nombre_trabajador || row.descripcion || 'Sin nombre')}</p>
                <span class="text-muted fs-12">${escapeHtml(documento || 'Sin documento')}</span>
              </div>
            </div>
          `;
        },
      },
      {
        data: null,
        render: function (_data, _type, row) {
          return row.cargo?.nombre
            ? `<span class="badge bg-primary-transparent">${escapeHtml(row.cargo.nombre)}</span>`
            : '<span class="text-muted">Sin cargo</span>';
        },
      },
      {
        data: null,
        render: function (_data, _type, row) {
          const correo = row.correo ? `<div><i class="ri-mail-line me-1"></i>${escapeHtml(row.correo)}</div>` : '';
          const celular = row.celular ? `<div><i class="ri-phone-line me-1"></i>${escapeHtml(row.celular)}</div>` : '';

          return correo || celular || '<span class="text-muted">Sin contacto</span>';
        },
      },
      {
        data: null,
        render: function (_data, _type, row) {
          return row.direccion
            ? `<span class="text-break">${escapeHtml(row.direccion)}</span>`
            : '<span class="text-muted">Sin direccion</span>';
        },
      },
      {
        data: 'estado_trash',
        className: 'text-center',
        render: function (estado) {
          return String(estado) === '1'
            ? '<span class="badge bg-success-transparent">Activo</span>'
            : '<span class="badge bg-danger-transparent">Eliminado</span>';
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
    drawCallback: function () {
      $('[data-bs-toggle="tooltip"]').tooltip();
    },
    order: [[0, 'desc']],
  });

  $('#tabla-trabajadores tbody').on('click', '.btn-ver-trabajador', function () {
    verTrabajador($(this).data('id'));
  });

  $('#tabla-trabajadores tbody').on('click', '.btn-editar-trabajador', function () {
    editarTrabajador($(this).data('id'));
  });

  $('#tabla-trabajadores tbody').on('click', '.btn-eliminar-trabajador', function () {
    eliminarTrabajador($(this).data('id'));
  });

  $('#tabla-trabajadores tbody').on('contextmenu', 'tr', function (event) {
    event.preventDefault();
    const rowData = tablaTrabajadores.row(this).data();

    if (rowData) {
      mostrarMenuContextualTrabajador(event, rowData.idpersona);
    }
  });
}

function inicializarFormularioTrabajador() {
  $('#form-trabajador').validate({
    ignore: '.select2-search__field, .select2-input, .select2-focusser',
    rules: {
      tipo_documento: { required: true },
      numero_documento: { required: true, minlength: 4, maxlength: 20 },
      descripcion: { required: true, minlength: 4, maxlength: 255 },
      correo: { email: true },
    },
    messages: {
      tipo_documento: { required: 'Campo requerido.' },
      numero_documento: {
        required: 'Campo requerido.',
        minlength: 'MINIMO {0} caracteres.',
        maxlength: 'MAXIMO {0} caracteres.',
      },
      descripcion: {
        required: 'Campo requerido.',
        minlength: 'MINIMO {0} caracteres.',
        maxlength: 'MAXIMO {0} caracteres.',
      },
      correo: { email: 'Ingrese un correo valido.' },
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');

      if ($(element).hasClass('select2-hidden-accessible')) {
        error.insertAfter($(element).next('.select2-container'));
        return;
      }

      const $group = element.closest('.input-group');
      if ($group.length) {
        error.insertAfter($group);
        return;
      }

      error.insertAfter(element);
    },
    highlight: function (element) {
      $(element).addClass('is-invalid').removeClass('is-valid');
      marcarSelect2Trabajador(element, true);
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid').addClass('is-valid');
      marcarSelect2Trabajador(element, false);
    },
    submitHandler: function (form, event) {
      event.preventDefault();
      guardarTrabajador();
    },
  });

  actualizarIndicadorCamposRequeridosTrabajador();
}

function marcarSelect2Trabajador(element, invalid) {
  const $element = $(element);
  if (!$element.hasClass('select2-hidden-accessible')) return;

  $element.next('.select2-container')
    .find('.select2-selection')
    .toggleClass('is-invalid', invalid)
    .toggleClass('is-valid', !invalid);
}

function mostrarVistaTrabajadores(vista) {
  const mostrandoFormulario = vista === 'formulario';

  $('#div-tabla-trabajadores').toggle(!mostrandoFormulario);
  $('#div-formulario-trabajador').toggle(mostrandoFormulario);
  $('#btn-nuevo-trabajador').toggle(!mostrandoFormulario);
  $('#btn-regresar-trabajador').toggle(mostrandoFormulario);
}

function prepararNuevoTrabajador() {
  if (!puedeTrabajador('crear')) {
    mostrarError('No tienes permiso para crear trabajadores.');
    return;
  }

  limpiarFormularioTrabajador();
  $('#titulo-formulario-trabajador').text('Nuevo trabajador');
  $('#btn-guardar-trabajador').html('<i class="ti ti-device-floppy"></i> Guardar');
  mostrarVistaTrabajadores('formulario');
}

function limpiarFormularioTrabajador() {
  const form = $('#form-trabajador')[0];
  form?.reset();
  $('#trabajador_idpersona, #trabajador_imagenactual, #trabajador_iddistrito_envio').val('');
  $('#trabajador_tipo_persona_sunat').val('NATURAL').trigger('change');
  $('#trabajador_tipo_documento, #trabajador_sexo, #trabajador_estado_civil, #trabajador_iddistrito, #trabajador_idcargo').val(null).trigger('change');
  $('#trabajador_nacionalidad').val('PERUANA').trigger('change');
  $('#trabajador_departamento, #trabajador_provincia').val('');
  $('#trabajador_imagen').val('');
  actualizarImagenDefaultTrabajador();
  $('#form-trabajador .is-invalid, #form-trabajador .is-valid').removeClass('is-invalid is-valid');
  $('#form-trabajador').validate().resetForm();

  const firstTab = document.querySelector('[data-bs-target="#trabajador-pane-general"]');
  if (firstTab && typeof bootstrap !== 'undefined') {
    bootstrap.Tab.getOrCreateInstance(firstTab).show();
  }

  actualizarIndicadorCamposRequeridosTrabajador();
}

function guardarTrabajador() {
  const id = $('#trabajador_idpersona').val();
  const accion = id ? 'editar' : 'crear';

  if (!puedeTrabajador(accion)) {
    mostrarError(`No tienes permiso para ${accion} trabajadores.`);
    return;
  }

  const formData = new FormData($('#form-trabajador')[0]);
  const url = id ? apiUrl(`/trabajadores/${id}/update`) : apiUrl('/trabajadores/store');
  const finalButtonHtml = id
    ? '<i class="ti ti-device-floppy"></i> Actualizar'
    : '<i class="ti ti-device-floppy"></i> Guardar';

  if (id) {
    formData.append('_method', 'PUT');
  }

  cambiarEstadoBoton($('#btn-guardar-trabajador'), true, 'Guardando...');

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo guardar el trabajador.');
        return;
      }

      mostrarOk(response.message || 'Trabajador guardado correctamente.');
      mostrarVistaTrabajadores('tabla');
      recargarTablaTrabajadores();
    },
    error: function (xhr) {
      mostrarErroresValidacionTrabajador(xhr);
    },
    complete: function () {
      cambiarEstadoBoton($('#btn-guardar-trabajador'), false, finalButtonHtml);
    },
  });
}

function verTrabajador(id) {
  $.ajax({
    url: apiUrl(`/trabajadores/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo cargar el trabajador.');
        return;
      }

      renderDetalleTrabajador(response.data);
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-detalle-trabajador')).show();
    },
    error: function () {
      mostrarError('Error al cargar trabajador.');
    },
  });
}

function renderDetalleTrabajador(trabajador) {
  const foto = trabajador.foto_perfil || (trabajador.sexo === 'F' ? 'mujer.png' : 'hombre.png');
  const documento = [trabajador.tipo_documento_label || trabajador.tipo_documento, trabajador.numero_documento].filter(Boolean).join(' ');

  $('#modal-detalle-trabajador-title').text(trabajador.nombre_trabajador || 'Detalle del trabajador');
  $('#detalle-trabajador-body').html(`
    <div class="p-4 border-bottom border-block-end-dashed main-profile-cover">
      <div class="d-sm-flex align-items-center">
        <span class="avatar avatar-xxl avatar-rounded me-3">
          <img src="${apiUrl(`/assets/modulo/persona/perfil/${foto}`)}" alt="" onerror="this.src='${apiUrl('/assets/modulo/persona/perfil/hombre.png')}';">
        </span>
        <div class="flex-fill main-profile-info">
          <h6 class="fw-semibold mb-1 text-fixed-white">${escapeHtml(trabajador.nombre_trabajador || trabajador.descripcion || 'Sin nombre')}</h6>
          <p class="mb-1 text-fixed-white op-7">${escapeHtml(documento || 'Sin documento')}</p>
          <p class="mb-0 text-fixed-white op-6">${escapeHtml(trabajador.cargo?.nombre || 'Sin cargo')}</p>
        </div>
      </div>
    </div>
    <div class="p-4">
      <div class="row gy-3">
        ${renderDetalleItemTrabajador('Correo', trabajador.correo || '-', 'ri-mail-line')}
        ${renderDetalleItemTrabajador('Celular', trabajador.celular || '-', 'ri-phone-line')}
        ${renderDetalleItemTrabajador('Direccion', trabajador.direccion || '-', 'ri-map-pin-line')}
        ${renderDetalleItemTrabajador('Nacimiento', trabajador.fecha_nacimiento || '-', 'ri-calendar-line')}
        ${renderDetalleItemTrabajador('Estado civil', trabajador.estado_civil || '-', 'ri-user-heart-line')}
        ${renderDetalleItemTrabajador('Nacionalidad', trabajador.nacionalidad || '-', 'ri-flag-line')}
      </div>
    </div>
  `);
}

function renderDetalleItemTrabajador(label, value, icon) {
  return `
    <div class="col-md-6">
      <div class="d-flex align-items-center">
        <span class="avatar avatar-sm avatar-rounded bg-light text-muted me-2">
          <i class="${icon}"></i>
        </span>
        <div>
          <p class="mb-0 fw-semibold text-break">${escapeHtml(value)}</p>
          <span class="fs-12 text-muted">${escapeHtml(label)}</span>
        </div>
      </div>
    </div>
  `;
}

function editarTrabajador(id) {
  if (!puedeTrabajador('editar')) {
    mostrarError('No tienes permiso para editar trabajadores.');
    return;
  }

  $.ajax({
    url: apiUrl(`/trabajadores/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo cargar el trabajador.');
        return;
      }

      cargarTrabajadorEnFormulario(response.data);
      mostrarVistaTrabajadores('formulario');
    },
    error: function () {
      mostrarError('Error al cargar trabajador.');
    },
  });
}

function cargarTrabajadorEnFormulario(data) {
  limpiarFormularioTrabajador();
  $('#titulo-formulario-trabajador').text('Editar trabajador');
  $('#btn-guardar-trabajador').html('<i class="ti ti-device-floppy"></i> Actualizar');
  $('#trabajador_idpersona').val(data.idpersona);
  $('#trabajador_tipo_persona_sunat').val(data.tipo_persona_sunat || 'NATURAL').trigger('change');
  $('#trabajador_tipo_documento').val(data.tipo_documento).trigger('change');
  $('#trabajador_numero_documento').val(data.numero_documento);
  $('#trabajador_descripcion').val(data.descripcion);
  $('#trabajador_nombre').val(data.nombre_persona_natural);
  $('#trabajador_apellido_paterno').val(data.apellido_paterno_persona_natural);
  $('#trabajador_apellido_materno').val(data.apellido_materno_persona_natural);
  $('#trabajador_sexo').val(data.sexo).trigger('change');
  $('#trabajador_fecha_nacimiento').val(normalizarFecha(data.fecha_nacimiento));
  $('#trabajador_estado_civil').val(data.estado_civil).trigger('change');
  $('#trabajador_celular').val(data.celular);
  $('#trabajador_correo').val(data.correo);
  $('#trabajador_direccion').val(data.direccion);
  $('#trabajador_direccion_referencia').val(data.direccion_referencia);
  $('#trabajador_cod_ubigeo').val(data.cod_ubigeo);
  seleccionarNacionalidadTrabajador(data.nacionalidad || 'PERUANA');
  seleccionarCargoTrabajador(data.idcargo_trabajador, data.cargo?.nombre);
  $('#trabajador_numero_licencia').val(data.numero_licencia);
  $('#trabajador_placa_vehiculo').val(data.placa_vehiculo);

  seleccionarDistritoTrabajador(data.iddistrito);

  const foto = data.foto_perfil || (data.sexo === 'F' ? 'mujer.png' : 'hombre.png');
  $('#trabajador_imagenactual').val(data.foto_perfil || '');
  $('#trabajador_imagenmuestra').attr('src', apiUrl(`/assets/modulo/persona/perfil/${foto}`));
  actualizarIndicadorCamposRequeridosTrabajador();
}

function seleccionarNacionalidadTrabajador(nacionalidad) {
  const $nacionalidad = $('#trabajador_nacionalidad');
  if (!nacionalidad || !$nacionalidad.length) return;

  if (!$nacionalidad.find(`option[value="${nacionalidad}"]`).length) {
    $nacionalidad.append(new Option(nacionalidad, nacionalidad, true, true));
  }

  $nacionalidad.val(nacionalidad).trigger('change');
}

function seleccionarCargoTrabajador(idcargo, nombreCargo) {
  const $cargo = $('#trabajador_idcargo');
  if (!idcargo || !$cargo.length) return;

  if (!$cargo.find(`option[value="${idcargo}"]`).length) {
    $cargo.append(new Option(nombreCargo || 'Cargo seleccionado', idcargo, true, true));
  }

  $cargo.val(String(idcargo)).trigger('change');
}

function seleccionarDistritoTrabajador(iddistrito) {
  if (!iddistrito) return;

  $('#trabajador_iddistrito option').each(function () {
    if ($(this).attr('iddistrito') == iddistrito) {
      $(this).prop('selected', true);
      return false;
    }

    return true;
  });

  $('#trabajador_iddistrito').trigger('change');
}

function eliminarTrabajador(id) {
  if (!puedeTrabajador('eliminar')) {
    mostrarError('No tienes permiso para eliminar trabajadores.');
    return;
  }

  confirmarAccionTrabajador('Eliminar trabajador', 'El trabajador se enviara a papelera.', function () {
    $.ajax({
      url: apiUrl(`/trabajadores/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (response.status) {
          mostrarOk(response.message || 'Trabajador eliminado correctamente.');
          recargarTablaTrabajadores();
          return;
        }

        mostrarError(response.message || 'No se pudo eliminar el trabajador.');
      },
      error: function () {
        mostrarError('Error al eliminar trabajador.');
      },
    });
  });
}

function restaurarTrabajador(id) {
  if (!puedeTrabajador('editar')) {
    mostrarError('No tienes permiso para restaurar trabajadores.');
    return;
  }

  confirmarAccionTrabajador('Restaurar trabajador', 'El trabajador volvera a estar activo.', function () {
    $.ajax({
      url: apiUrl(`/trabajadores/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (response.status) {
          mostrarOk(response.message || 'Trabajador restaurado correctamente.');
          recargarTablaTrabajadores();
          return;
        }

        mostrarError(response.message || 'No se pudo restaurar el trabajador.');
      },
      error: function () {
        mostrarError('Error al restaurar trabajador.');
      },
    });
  });
}

function inicializarMenuContextualTrabajador() {
  $(document).on('click', function (event) {
    if (!$(event.target).closest('#menu-contextual-trabajador').length) {
      ocultarMenuContextualTrabajador();
    }
  });

  $('#opcion-t-ver').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuTrabajador(verTrabajador);
  });
  $('#opcion-t-editar').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuTrabajador(editarTrabajador);
  });
  $('#opcion-t-eliminar').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuTrabajador(eliminarTrabajador);
  });
  $('#opcion-t-restaurar').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuTrabajador(restaurarTrabajador);
  });
}

function mostrarMenuContextualTrabajador(event, trabajadorId) {
  const $menu = $('#menu-contextual-trabajador');
  trabajadorSeleccionadoId = trabajadorId;
  $menu.css({ display: 'block', left: `${event.pageX}px`, top: `${event.pageY}px` });
}

function ocultarMenuContextualTrabajador() {
  $('#menu-contextual-trabajador').hide();
}

function ejecutarAccionMenuTrabajador(callback) {
  const id = trabajadorSeleccionadoId;
  ocultarMenuContextualTrabajador();

  if (!id) {
    mostrarError('Seleccione un trabajador.');
    return;
  }

  callback(id);
}

function recargarTablaTrabajadores() {
  tablaTrabajadores?.ajax.reload(null, false);
}

function actualizarUbigeoTrabajador() {
  const selectedOption = $('#trabajador_iddistrito').find('option:selected');
  $('#trabajador_departamento').val(selectedOption.attr('departamento') || '');
  $('#trabajador_provincia').val(selectedOption.attr('provincia') || '');
  $('#trabajador_iddistrito_envio').val(selectedOption.attr('iddistrito') || '');
  actualizarIndicadorCamposRequeridosTrabajador();
}

function buscarDocumentoTrabajador() {
  const idPersona = ($('#trabajador_idpersona').val() || '').trim();
  const tipoDocumento = ($('#trabajador_tipo_documento').val() || '').trim();
  const numeroDocumento = ($('#trabajador_numero_documento').val() || '').trim();

  if (tipoDocumento === '' || numeroDocumento.length < 4) return;

  if (idPersona !== '') {
    buscarSunatReniecTrabajador();
    return;
  }

  $.ajax({
    url: apiUrl('/trabajadores/buscar-por-documento'),
    type: 'GET',
    dataType: 'json',
    headers: ajaxHeaders(),
    data: {
      tipo_documento: tipoDocumento,
      numero_documento: numeroDocumento,
      idpersona_tipo: '2',
    },
  }).done(function (response) {
    if (response?.status !== true) return;

    const data = response.data;

    if (!data.existe_persona) {
      buscarSunatReniecTrabajador();
      return;
    }

    if (data.existe_persona && !data.tiene_tipo_solicitado) {
      confirmarAsignarTipoTrabajador(data.idpersona);
      return;
    }

    if (data.existe_persona && data.tiene_tipo_solicitado) {
      mostrarInfo('Esta persona ya esta registrada como trabajador.');
      cargarTrabajadorEnFormulario(data.persona);
      mostrarVistaTrabajadores('formulario');
    }
  }).fail(function () {
    mostrarError('Error al buscar trabajador por documento.');
  });
}

function buscarSunatReniecTrabajador() {
  if (typeof buscar_sunat_reniec !== 'function') {
    mostrarInfo('Busqueda automatica no disponible.');
    return;
  }

  buscar_sunat_reniec(
    '#form-trabajador',
    '',
    '#trabajador_tipo_documento',
    '#trabajador_numero_documento',
    '#trabajador_descripcion',
    '#trabajador_descripcion',
    '#trabajador_nombre',
    '#trabajador_apellido_paterno',
    '#trabajador_apellido_materno',
    '#trabajador_direccion',
    '#trabajador_iddistrito',
    '#trabajador_cod_ubigeo',
    '#trabajador_tipo_persona_sunat'
  );
  setTimeout(actualizarIndicadorCamposRequeridosTrabajador, 300);
}

function confirmarAsignarTipoTrabajador(idpersona) {
  if (typeof Swal === 'undefined') {
    asociarTipoPersonaTrabajador(idpersona);
    return;
  }

  Swal.fire({
    title: 'Persona encontrada',
    text: 'La persona existe pero no esta registrada como trabajador. Desea agregarla como trabajador?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Si, agregar',
    cancelButtonText: 'Cancelar',
  }).then(function (result) {
    if (result.isConfirmed) {
      asociarTipoPersonaTrabajador(idpersona);
    }
  });
}

function asociarTipoPersonaTrabajador(idpersona) {
  $.ajax({
    url: apiUrl('/personas/asociar-tipo'),
    type: 'POST',
    headers: ajaxHeaders(),
    data: {
      idpersona,
      idpersona_tipo: '2',
    },
    success: function (response) {
      if (response.status) {
        mostrarOk(response.message || 'Trabajador asociado correctamente.');
        recargarTablaTrabajadores();
        mostrarVistaTrabajadores('tabla');
        return;
      }

      mostrarError(response.message || 'No se pudo asociar el trabajador.');
    },
    error: function () {
      mostrarError('Error al asociar trabajador.');
    },
  });
}

function previsualizarImagenTrabajador() {
  const input = this;
  if (!input.files || !input.files[0]) return;

  const reader = new FileReader();
  reader.onload = function (event) {
    $('#trabajador_imagenmuestra').attr('src', event.target.result);
  };
  reader.readAsDataURL(input.files[0]);
}

function removerImagenTrabajador() {
  $('#trabajador_imagen').val('');
  $('#trabajador_imagenactual').val('');
  actualizarImagenDefaultTrabajador();
}

function actualizarImagenDefaultTrabajador() {
  const defaultImage = $('#trabajador_sexo').val() === 'F' ? 'mujer.png' : 'hombre.png';
  $('#trabajador_imagenmuestra').attr('src', apiUrl(`/assets/modulo/persona/perfil/${defaultImage}`));
}

function mostrarErroresValidacionTrabajador(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const firstMessage = Object.values(errors).flat()[0] || 'Revise los campos del formulario.';
    mostrarError(firstMessage);
    return;
  }

  mostrarError(response.message || 'Error al guardar trabajador.');
}

function confirmarAccionTrabajador(title, message, callback) {
  if (typeof Swal === 'undefined') {
    callback();
    return;
  }

  Swal.fire({
    title,
    text: message,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, continuar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
  }).then(function (result) {
    if (result.isConfirmed) {
      callback();
    }
  });
}

function cambiarEstadoBoton($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function normalizarFecha(value) {
  if (!value) return '';

  return String(value).slice(0, 10);
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

function mostrarInfo(message) {
  if (typeof toastr !== 'undefined') {
    toastr.info(message);
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
