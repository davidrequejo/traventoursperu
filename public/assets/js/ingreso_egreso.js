let tablaIngresoEgreso = null;
let ingresoEgresoCalculoDesde = 'sin_igv';

function puedeIngresoEgreso(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('ingreso_y_egreso', accion);
}

$(function () {
  inicializarSelectsIngresoEgreso();
  inicializarProveedorRapido();
  cargarTiposComprobante();
  inicializarTablaIngresoEgreso();
  inicializarFormularioIngresoEgreso();

  $('#btn-nuevo-ingreso-egreso').on('click', prepararNuevoIngresoEgreso);
  $('#btn-regresar-ingreso-egreso, #btn-cancelar-ingreso-egreso').on('click', function () {
    mostrarVistaIngresoEgreso('tabla');
  });
  $('#incluir-papelera-ingreso-egreso').on('change', recargarTablaIngresoEgreso);
  $('#ingreso_egreso_precio_sin_igv, #ingreso_egreso_precio_con_igv, #ingreso_egreso_val_igv').on('input', calcularTotalesIngresoEgreso);
  $('#btn-nueva-categoria-rapida').on('click', abrirModalCategoriaRapida);
  $('#btn-nuevo-proveedor-rapido').on('click', abrirModalProveedorRapido);
  $('#btn-guardar-categoria-rapida').on('click', guardarCategoriaRapida);
  $('#btn-guardar-proveedor-rapido').on('click', guardarProveedorRapido);
  $('#btn-buscar-proveedor-documento').on('click', buscarProveedorPorDocumento);
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

function inicializarSelectsIngresoEgreso() {
  inicializarSelect2Ajax('#ingreso_egreso_idproveedor', '/ingreso-egreso/proveedores', 'Buscar proveedor...');
  inicializarSelect2Ajax('#ingreso_egreso_idtrabajador', '/ingreso-egreso/trabajadores', 'Buscar trabajador...');
  inicializarSelect2Ajax('#ingreso_egreso_categoria', '/ingreso-egreso/categorias', 'Buscar categoria...');
  inicializarSelect2Simple('#ingreso_egreso_tipo_movimiento', 'Seleccione movimiento');
  inicializarSelect2Simple('#ingreso_egreso_tipo_comprobante', 'Seleccione tipo');
}

function inicializarProveedorRapido() {
  $('#proveedor_rapido_tipo_persona_sunat, #proveedor_rapido_tipo_documento, #proveedor_rapido_sexo, #proveedor_rapido_estado_civil').select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#modal-proveedor-rapido-ingreso-egreso'),
  });

  $('#proveedor_rapido_iddistrito').select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder: 'Buscar distrito...',
    allowClear: true,
    dropdownParent: $('#modal-proveedor-rapido-ingreso-egreso'),
    ajax: {
      url: apiUrl('/proveedores/distritos'),
      dataType: 'json',
      delay: 250,
      headers: ajaxHeaders(),
      data: function (params) {
        return { term: params.term || '', page: params.page || 1 };
      },
    },
  });

  $('#proveedor_rapido_tipo_persona_sunat').on('change', actualizarTipoProveedorRapido);
  $('#proveedor_rapido_iddistrito').on('select2:select', function (event) {
    const data = event.params.data || {};
    $('#proveedor_rapido_provincia').val(data.provincia || '');
    $('#proveedor_rapido_departamento').val(data.departamento || '');
    $('#proveedor_rapido_cod_ubigeo').val(data.cod_ubigeo || data.id || '');
  });
  $('#proveedor_rapido_iddistrito').on('select2:clear', function () {
    $('#proveedor_rapido_provincia, #proveedor_rapido_departamento, #proveedor_rapido_cod_ubigeo').val('');
  });

  actualizarTipoProveedorRapido();
}

function inicializarSelect2Ajax(selector, url, placeholder) {
  $(selector).select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder,
    allowClear: true,
    ajax: {
      url: apiUrl(url),
      dataType: 'json',
      delay: 250,
      headers: ajaxHeaders(),
      data: function (params) {
        return { term: params.term || '', page: params.page || 1 };
      },
    },
  });
}

function inicializarSelect2Simple(selector, placeholder) {
  $(selector).select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder,
    allowClear: true,
  });
}

function cargarTiposComprobante(selectedValue = '') {
  return $.ajax({
    url: apiUrl('/ingreso-egreso/tipos-comprobante'),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      const $select = $('#ingreso_egreso_tipo_comprobante');
      $select.empty().append(new Option('Seleccione tipo', '', true, true));

      if (!response.status) {
        mostrarError(response.message || 'No se pudo cargar tipos de comprobante.');
        return;
      }

      (response.data || []).forEach(function (tipo) {
        const label = [tipo.codigo, tipo.abreviatura || tipo.nombre].filter(Boolean).join(' - ');
        $select.append(new Option(label, tipo.codigo, false, String(tipo.codigo) === String(selectedValue)));
      });

      $select.val(selectedValue || '').trigger('change.select2');
    },
  });
}

function cargarSeriesComprobante(selectedValue = '') {
  $('#ingreso_egreso_serie_comprobante').val(selectedValue || '');
}

function inicializarTablaIngresoEgreso() {
  tablaIngresoEgreso = $('#tabla-ingreso-egreso').DataTable({
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
        title: 'Lista de ingreso y egreso',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/ingreso-egreso/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function (data) {
        data.incluir_trash = $('#incluir-papelera-ingreso-egreso').is(':checked') ? 1 : 0;
      },
      dataSrc: function (response) {
        if (response && response.status === false) {
          mostrarError(response.message || 'No se pudo cargar ingreso/egreso.');
          return [];
        }
        return Array.isArray(response.data) ? response.data : [];
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarError(response.message || 'Error al consultar ingreso/egreso.');
      },
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const acciones = [`
            <button type="button" class="btn btn-sm btn-icon btn-info btn-ver-ingreso-egreso" data-id="${row.idingreso_egreso}" data-bs-toggle="tooltip" title="Ver">
              <i class="ri-eye-line"></i>
            </button>
          `];

          if (puedeIngresoEgreso('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-ingreso-egreso" data-id="${row.idingreso_egreso}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '1' && puedeIngresoEgreso('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-ingreso-egreso" data-id="${row.idingreso_egreso}" data-bs-toggle="tooltip" title="Enviar a papelera">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '0' && puedeIngresoEgreso('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-ingreso-egreso" data-id="${row.idingreso_egreso}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('');
        },
      },
      { data: 'fecha_ingreso', render: renderFecha },
      { data: 'tipo_movimiento', className: 'text-center', render: renderTipoMovimiento },
      { data: 'categoria_nombre', render: renderTextoFuerte },
      {
        data: null,
        render: function (_data, _type, row) {
          return `
            <div class="fw-semibold">${escapeHtml(row.trabajador_nombre || '-')}</div>
            <div class="fs-12 text-muted">${escapeHtml(row.proveedor_nombre || '-')}</div>
          `;
        },
      },
      {
        data: null,
        render: function (_data, _type, row) {
          const comp = [row.serie_comprobante].filter(Boolean).join(' - ') || '-';
          const link = row.comprobante_url
            ? `<a href="${escapeAttr(row.comprobante_url)}" target="_blank" class="fs-12">Ver archivo</a>`
            : '<span class="fs-12 text-muted">Sin archivo</span>';
          return `<div>${escapeHtml(comp)}</div>${link}`;
        },
      },
      { data: 'precio_con_igv', className: 'text-end', render: renderMoneda },
      { data: 'estado_trash', className: 'text-center', render: renderEstado },
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

  $('#tabla-ingreso-egreso tbody').on('click', '.btn-ver-ingreso-egreso', function () {
    verIngresoEgreso($(this).data('id'));
  });
  $('#tabla-ingreso-egreso tbody').on('click', '.btn-editar-ingreso-egreso', function () {
    editarIngresoEgreso($(this).data('id'));
  });
  $('#tabla-ingreso-egreso tbody').on('click', '.btn-eliminar-ingreso-egreso', function () {
    eliminarIngresoEgreso($(this).data('id'));
  });
  $('#tabla-ingreso-egreso tbody').on('click', '.btn-restaurar-ingreso-egreso', function () {
    restaurarIngresoEgreso($(this).data('id'));
  });
}

function inicializarFormularioIngresoEgreso() {
  $('#form-ingreso-egreso').validate({
    ignore: '.select2-search__field, .select2-input, .select2-focusser',
    rules: {
      idotros_gastos_categoria: { required: true },
      tipo_movimiento: { required: true },
      fecha_ingreso: { required: true, date: true },
      precio_sin_igv: { required: true, number: true, min: 0 },
      val_igv: { number: true, min: 0, max: 100 },
      precio_igv: { number: true, min: 0 },
      precio_con_igv: { required: true, number: true, min: 0 },
    },
    messages: {
      idotros_gastos_categoria: { required: 'Seleccione una categoria.' },
      tipo_movimiento: { required: 'Seleccione si es ingreso o egreso.' },
      fecha_ingreso: { required: 'Seleccione la fecha.' },
      precio_sin_igv: { required: 'Campo requerido.', number: 'Ingrese un numero valido.' },
      precio_con_igv: { required: 'Campo requerido.', number: 'Ingrese un numero valido.' },
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
    submitHandler: guardarIngresoEgreso,
  });
}

function prepararNuevoIngresoEgreso() {
  if (!puedeIngresoEgreso('crear')) {
    mostrarError('No tienes permiso para crear ingreso/egreso.');
    return;
  }

  limpiarFormularioIngresoEgreso();
  $('#titulo-formulario-ingreso-egreso').text('Nuevo ingreso/egreso');
  $('#btn-guardar-ingreso-egreso').html('<i class="ti ti-device-floppy"></i> Guardar');
  $('#ingreso_egreso_fecha').val(new Date().toISOString().slice(0, 10));
  $('#ingreso_egreso_tipo_movimiento').val('EGRESO').trigger('change.select2');
  $('#ingreso_egreso_val_igv').val('18');
  mostrarVistaIngresoEgreso('formulario');
}

function editarIngresoEgreso(id) {
  if (!puedeIngresoEgreso('editar')) {
    mostrarError('No tienes permiso para editar ingreso/egreso.');
    return;
  }

  $.getJSON(apiUrl(`/ingreso-egreso/${id}`), function (response) {
    if (!response.status) {
      mostrarError(response.message || 'No se pudo cargar el registro.');
      return;
    }

    cargarIngresoEgresoEnFormulario(response.data);
    mostrarVistaIngresoEgreso('formulario');
  }).fail(function () {
    mostrarError('Error al cargar ingreso/egreso.');
  });
}

function cargarIngresoEgresoEnFormulario(data) {
  limpiarFormularioIngresoEgreso();

  seleccionarOpcion('#ingreso_egreso_idproveedor', data.idproveedor, data.proveedor_nombre);
  seleccionarOpcion('#ingreso_egreso_idtrabajador', data.idtrabajador, data.trabajador_nombre);
  seleccionarOpcion('#ingreso_egreso_categoria', data.idotros_gastos_categoria, data.categoria_nombre);

  $('#titulo-formulario-ingreso-egreso').text('Editar ingreso/egreso');
  $('#btn-guardar-ingreso-egreso').html('<i class="ti ti-device-floppy"></i> Actualizar');
  $('#idingreso_egreso').val(data.idingreso_egreso);
  $('#ingreso_egreso_fecha').val((data.fecha_ingreso || '').slice(0, 10));
  $('#ingreso_egreso_tipo_movimiento').val(data.tipo_movimiento || 'EGRESO').trigger('change.select2');
  $('#ingreso_egreso_precio_sin_igv').val(data.precio_sin_igv);
  $('#ingreso_egreso_precio_igv').val(data.precio_igv);
  $('#ingreso_egreso_val_igv').val(data.val_igv);
  $('#ingreso_egreso_precio_con_igv').val(data.precio_con_igv);
  $('#ingreso_egreso_descripcion_comprobante').val(data.descripcion_comprobante);
  $('#ingreso_egreso_comprobante_actual').val(data.comprobante || '');

  if (data.comprobante_url) {
    $('#link-comprobante-actual').attr('href', data.comprobante_url).removeClass('d-none');
  }

  cargarTiposComprobante(data.tipo_comprobante).then(function () {
    cargarSeriesComprobante(data.serie_comprobante);
  });
}

function seleccionarOpcion(selector, id, text) {
  if (!id) return;
  $(selector)
    .append(new Option(text || `Registro #${id}`, id, true, true))
    .trigger('change');
}

function guardarIngresoEgreso(form) {
  const id = $('#idingreso_egreso').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedeIngresoEgreso('editar')) {
    mostrarError('No tienes permiso para editar ingreso/egreso.');
    return;
  }

  if (!esEdicion && !puedeIngresoEgreso('crear')) {
    mostrarError('No tienes permiso para crear ingreso/egreso.');
    return;
  }

  const $button = $('#btn-guardar-ingreso-egreso');
  const formData = new FormData(form);
  if (esEdicion) formData.append('_method', 'PUT');

  cambiarEstadoBoton($button, true, 'Guardando...');

  $.ajax({
    url: esEdicion ? apiUrl(`/ingreso-egreso/${id}`) : apiUrl('/ingreso-egreso'),
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    processData: false,
    contentType: false,
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo guardar el registro.');
        return;
      }

      mostrarOk(response.message || 'Ingreso/Egreso guardado correctamente.');
      mostrarVistaIngresoEgreso('tabla');
      recargarTablaIngresoEgreso();
    },
    error: function (xhr) {
      const response = xhr.responseJSON || {};
      mostrarError(response.message || 'Error al guardar ingreso/egreso.');
    },
    complete: function () {
      cambiarEstadoBoton($button, false, esEdicion ? '<i class="ti ti-device-floppy"></i> Actualizar' : '<i class="ti ti-device-floppy"></i> Guardar');
    },
  });
}

function verIngresoEgreso(id) {
  $.getJSON(apiUrl(`/ingreso-egreso/${id}`), function (response) {
    if (!response.status) {
      mostrarError(response.message || 'No se pudo cargar el registro.');
      return;
    }

    const r = response.data || {};
    const comprobante = [r.tipo_comprobante, r.serie_comprobante].filter(Boolean).join(' - ') || '-';
    const archivo = r.comprobante_url
      ? `<a href="${escapeAttr(r.comprobante_url)}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
          <i class="ri-file-search-line me-1"></i>Ver archivo
        </a>`
      : '<span class="text-muted">Sin archivo</span>';

    mostrarDetalle('Detalle de ingreso/egreso', `
      <div class="text-start ingreso-egreso-detail">
        <div class="d-flex justify-content-between align-items-start gap-3 border-bottom pb-3 mb-3">
          <div>
            <div class="text-muted fs-12 mb-1">Fecha de registro</div>
            <div class="fs-18 fw-semibold">${escapeHtml(renderFechaTexto(r.fecha_ingreso))}</div>
          </div>
          <div>${renderTipoMovimiento(r.tipo_movimiento)}</div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="border rounded-2 p-3 h-100">
              <div class="text-muted fs-12 mb-1">Categoria</div>
              <div class="fw-semibold">${escapeHtml(r.categoria_nombre || '-')}</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded-2 p-3 h-100">
              <div class="text-muted fs-12 mb-1">Comprobante</div>
              <div class="fw-semibold">${escapeHtml(comprobante)}</div>
              ${archivo}
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded-2 p-3 h-100">
              <div class="text-muted fs-12 mb-1">Proveedor</div>
              <div class="fw-semibold">${escapeHtml(r.proveedor_nombre || '-')}</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="border rounded-2 p-3 h-100">
              <div class="text-muted fs-12 mb-1">Trabajador</div>
              <div class="fw-semibold">${escapeHtml(r.trabajador_nombre || '-')}</div>
            </div>
          </div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <div class="bg-light rounded-2 p-3 text-center">
              <div class="text-muted fs-12">Sin IGV</div>
              <div class="fw-semibold">${escapeHtml(renderMonedaTexto(r.precio_sin_igv))}</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="bg-light rounded-2 p-3 text-center">
              <div class="text-muted fs-12">IGV</div>
              <div class="fw-semibold">${escapeHtml(renderMonedaTexto(r.precio_igv))}</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="bg-primary-transparent rounded-2 p-3 text-center">
              <div class="text-muted fs-12">Total</div>
              <div class="fs-18 fw-bold">${escapeHtml(renderMonedaTexto(r.precio_con_igv))}</div>
            </div>
          </div>
        </div>

        <div class="border rounded-2 p-3">
          <div class="text-muted fs-12 mb-1">Descripcion</div>
          <div>${escapeHtml(r.descripcion_comprobante || '-')}</div>
        </div>
      </div>
    `);
  }).fail(function () {
    mostrarError('Error al cargar ingreso/egreso.');
  });
}

function eliminarIngresoEgreso(id) {
  if (!puedeIngresoEgreso('eliminar')) {
    mostrarError('No tienes permiso para eliminar ingreso/egreso.');
    return;
  }

  confirmarAccionIngresoEgreso('Eliminar registro', 'El registro se enviara a papelera.', 'Si, eliminar', function () {
    $.ajax({
      url: apiUrl(`/ingreso-egreso/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        mostrarOk(response.message || 'Ingreso/Egreso eliminado correctamente.');
        recargarTablaIngresoEgreso();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarError(response.message || 'Error al eliminar ingreso/egreso.');
      },
    });
  });
}

function restaurarIngresoEgreso(id) {
  if (!puedeIngresoEgreso('editar')) {
    mostrarError('No tienes permiso para restaurar ingreso/egreso.');
    return;
  }

  confirmarAccionIngresoEgreso('Restaurar registro', 'El registro volvera a estar activo.', 'Si, restaurar', function () {
    $.ajax({
      url: apiUrl(`/ingreso-egreso/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        mostrarOk(response.message || 'Ingreso/Egreso restaurado correctamente.');
        recargarTablaIngresoEgreso();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarError(response.message || 'Error al restaurar ingreso/egreso.');
      },
    });
  });
}

function abrirModalCategoriaRapida() {
  $('#categoria_rapida_nombre').val('');
  $('#categoria_rapida_descripcion').val('');
  $('#modal-categoria-rapida-ingreso-egreso').modal('show');
}

function abrirModalProveedorRapido() {
  limpiarProveedorRapido();
  $('#modal-proveedor-rapido-ingreso-egreso').modal('show');
}

function limpiarProveedorRapido() {
  $('#form-proveedor-rapido-ingreso-egreso')[0]?.reset();
  $('#proveedor_rapido_tipo_persona_sunat').val('JURIDICA').trigger('change');
  $('#proveedor_rapido_tipo_documento').val('6').trigger('change');
  $('#proveedor_rapido_sexo, #proveedor_rapido_estado_civil, #proveedor_rapido_iddistrito').val(null).trigger('change');
  $('#proveedor_rapido_nacionalidad').val('PERUANA');
  $('#proveedor_rapido_provincia, #proveedor_rapido_departamento, #proveedor_rapido_cod_ubigeo').val('');
  $('.valido_novalido_proveedor').html('<span class="badge bg-primary">Por verificar</span>');
}

function actualizarTipoProveedorRapido() {
  const esNatural = $('#proveedor_rapido_tipo_persona_sunat').val() === 'NATURAL';
  $('.proveedor-rapido-natural').toggle(esNatural);
  $('.proveedor-rapido-juridica').toggle(!esNatural);

  if (esNatural) {
    $('#proveedor_rapido_tipo_documento').val('1').trigger('change');
    return;
  }

  $('#proveedor_rapido_tipo_documento').val('6').trigger('change');
}

function buscarProveedorPorDocumento() {
  const tipoDocumento = String($('#proveedor_rapido_tipo_documento').val() || '');
  const numeroDocumento = String($('#proveedor_rapido_numero_documento').val() || '').trim();
  const tipoPersona = String($('#proveedor_rapido_tipo_persona_sunat').val() || '').toUpperCase();

  if (!tipoDocumento) {
    mostrarError('Seleccione el tipo de documento.');
    return;
  }

  if (tipoDocumento === '1' && numeroDocumento.length !== 8) {
    mostrarError('Asegurese de que el DNI tenga 8 digitos.');
    $('#proveedor_rapido_numero_documento').focus();
    return;
  }

  if (tipoDocumento === '6' && numeroDocumento.length !== 11) {
    mostrarError('Asegurese de que el RUC tenga 11 digitos.');
    $('#proveedor_rapido_numero_documento').focus();
    return;
  }

  if (!['1', '6'].includes(tipoDocumento)) {
    mostrarError('Este tipo de documento no necesita consulta RENIEC/SUNAT.');
    return;
  }

  $.ajax({
    url: apiUrl('/proveedores/buscar-por-documento'),
    type: 'GET',
    headers: ajaxHeaders(),
    data: {
      tipo_documento: tipoDocumento,
      numero_documento: numeroDocumento,
    },
    success: function (response) {
      if (!response.status) {
        consultarProveedorReniecSunat(tipoDocumento, numeroDocumento, tipoPersona);
        return;
      }

      const data = response.data || {};

      if (!data.existe_persona) {
        consultarProveedorReniecSunat(tipoDocumento, numeroDocumento, tipoPersona);
        return;
      }

      if (data.existe_persona && data.tiene_tipo_solicitado) {
        const persona = data.persona || {};
        seleccionarProveedorExistente(persona);
        $('.valido_novalido_proveedor').html('<span class="badge bg-warning">Ya existe</span>');
        mostrarError('Esta persona ya esta registrada como proveedor.');
        return;
      }

      confirmarAgregarPersonaComoProveedor(data);
    },
    error: function () {
      consultarProveedorReniecSunat(tipoDocumento, numeroDocumento, tipoPersona);
    },
  });
}

function consultarProveedorReniecSunat(tipoDocumento, numeroDocumento, tipoPersona) {
  cambiarEstadoBusquedaProveedor(true);

  if (tipoDocumento === '1') {
    $.getJSON(apiUrl('/reniec/dni'), { dni: numeroDocumento })
      .done(function (response) {
        procesarRespuestaReniecProveedor(response);
      })
      .fail(function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarError(response.message || 'No se pudo consultar RENIEC.');
      })
      .always(function () {
        cambiarEstadoBusquedaProveedor(false);
      });
    return;
  }

  const requests = [$.getJSON(apiUrl('/sunat/ruc'), { ruc: numeroDocumento })];
  const dniDesdeRuc = numeroDocumento.startsWith('10') ? numeroDocumento.substring(2, 10) : '';

  if (tipoPersona === 'NATURAL' && dniDesdeRuc.length === 8) {
    requests.push($.getJSON(apiUrl('/reniec/dni'), { dni: dniDesdeRuc }));
  }

  $.when(...requests)
    .done(function (sunatResp, reniecResp) {
      const sunat = Array.isArray(sunatResp) ? sunatResp[0] : sunatResp;
      const reniec = Array.isArray(reniecResp) ? reniecResp[0] : reniecResp;

      procesarRespuestaSunatProveedor(sunat);
      if (reniec) {
        procesarRespuestaReniecProveedor(reniec, false);
      }
    })
    .fail(function (xhr) {
      const response = xhr.responseJSON || {};
      mostrarError(response.message || 'No se pudo consultar SUNAT.');
    })
    .always(function () {
      cambiarEstadoBusquedaProveedor(false);
    });
}

function confirmarAgregarPersonaComoProveedor(data) {
  const persona = data.persona || {};
  const texto = persona.nombre_proveedor || persona.numero_documento || 'La persona encontrada';

  if (typeof Swal === 'undefined') {
    asociarPersonaComoProveedor(data.idpersona, persona);
    return;
  }

  Swal.fire({
    title: 'Persona encontrada',
    text: `${texto} existe pero no esta registrada como proveedor. Desea agregarla como proveedor?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Si, agregar',
    cancelButtonText: 'Cancelar',
  }).then(function (result) {
    if (result.isConfirmed) {
      asociarPersonaComoProveedor(data.idpersona, persona);
    }
  });
}

function asociarPersonaComoProveedor(idpersona, personaPrevia = {}) {
  $.ajax({
    url: apiUrl('/proveedores/asociar-tipo'),
    type: 'POST',
    headers: ajaxHeaders(),
    data: { idpersona },
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo agregar como proveedor.');
        return;
      }

      const proveedor = response.data || personaPrevia;
      seleccionarProveedorExistente(proveedor);
      $('#modal-proveedor-rapido-ingreso-egreso').modal('hide');
      mostrarOk(response.message || 'Proveedor asociado correctamente.');
    },
    error: function (xhr) {
      const response = xhr.responseJSON || {};
      mostrarError(response.message || 'Error al agregar como proveedor.');
    },
  });
}

function seleccionarProveedorExistente(proveedor) {
  if (!proveedor?.idpersona) return;
  const texto = [proveedor.numero_documento, proveedor.nombre_proveedor].filter(Boolean).join(' - ');
  seleccionarOpcion('#ingreso_egreso_idproveedor', proveedor.idpersona, texto || proveedor.nombre_proveedor);
}

function procesarRespuestaReniecProveedor(response, mostrarMensaje = true) {
  const data = response?.data?.data || response?.data || {};

  if (response?.status === false || data?.success === false) {
    $('.valido_novalido_proveedor').html('<span class="badge bg-danger">No encontrado</span>');
    if (mostrarMensaje) mostrarError(response?.message || 'No se encontraron datos en RENIEC.');
    return;
  }

  const nombres = data.nombres || '';
  const apellidoPaterno = data.apellido_paterno || '';
  const apellidoMaterno = data.apellido_materno || '';
  const nombreCompleto = [nombres, apellidoPaterno, apellidoMaterno].filter(Boolean).join(' ').trim();

  $('#proveedor_rapido_tipo_persona_sunat').val('NATURAL').trigger('change');
  $('#proveedor_rapido_nombre_persona_natural').val(nombres);
  $('#proveedor_rapido_apellido_paterno').val(apellidoPaterno);
  $('#proveedor_rapido_apellido_materno').val(apellidoMaterno);
  $('#proveedor_rapido_descripcion').val(nombreCompleto);

  if (data.direccion) $('#proveedor_rapido_direccion').val(data.direccion);
  if (data.distrito) seleccionarDistritoProveedorPorTexto(data.distrito);

  $('.valido_novalido_proveedor').html('<span class="badge bg-info">Activo</span>');
  if (mostrarMensaje) mostrarOk('Datos RENIEC encontrados.');
}

function procesarRespuestaSunatProveedor(response) {
  const data = response?.data?.data || response?.data || {};

  if (response?.status === false || data?.success === false) {
    $('.valido_novalido_proveedor').html('<span class="badge bg-danger">No activo</span>');
    mostrarError(response?.message || 'No se encontraron datos en SUNAT.');
    return;
  }

  const razonSocial = data.nombre_o_razon_social || data.razonSocial || '';
  const nombreComercial = data.nombreComercial || data.nombre_comercial || '';
  const direccion = data.direccion || '';
  const distrito = data.distrito || '';
  const estado = String(data.estado || '').toUpperCase();

  $('#proveedor_rapido_descripcion').val(razonSocial);
  $('#proveedor_rapido_nombre_comercial').val(nombreComercial);
  if (direccion) $('#proveedor_rapido_direccion').val(direccion);
  if (distrito) seleccionarDistritoProveedorPorTexto(distrito);

  $('.valido_novalido_proveedor').html(
    estado === 'ACTIVO'
      ? '<span class="badge bg-info">Activo</span>'
      : '<span class="badge bg-danger">No activo</span>'
  );

  if (estado === 'ACTIVO') {
    mostrarOk('Datos SUNAT encontrados.');
  } else {
    mostrarError('El RUC no figura como activo.');
  }
}

function seleccionarDistritoProveedorPorTexto(textoDistrito) {
  if (!textoDistrito) return;

  $.ajax({
    url: apiUrl('/proveedores/distritos'),
    type: 'GET',
    headers: ajaxHeaders(),
    data: { term: textoDistrito, page: 1 },
    success: function (response) {
      const distrito = (response.results || [])[0];
      if (!distrito) return;

      $('#proveedor_rapido_iddistrito')
        .append(new Option(distrito.text, distrito.id, true, true))
        .trigger('change');
      $('#proveedor_rapido_provincia').val(distrito.provincia || '');
      $('#proveedor_rapido_departamento').val(distrito.departamento || '');
      $('#proveedor_rapido_cod_ubigeo').val(distrito.cod_ubigeo || distrito.id || '');
    },
  });
}

function cambiarEstadoBusquedaProveedor(buscando) {
  $('#btn-buscar-proveedor-documento').prop('disabled', buscando);
  $('#search_proveedor_rapido').toggle(!buscando);
  $('#charge_proveedor_rapido').toggle(buscando);
}

function guardarCategoriaRapida() {
  const nombre = $('#categoria_rapida_nombre').val().trim();
  const descripcion = $('#categoria_rapida_descripcion').val().trim();

  if (!nombre) {
    mostrarError('Ingrese el nombre de la categoria.');
    $('#categoria_rapida_nombre').focus();
    return;
  }

  setEstadoBotonRapido($('#btn-guardar-categoria-rapida'), true);

  $.ajax({
    url: apiUrl('/ingreso-egreso/categorias'),
    type: 'POST',
    headers: ajaxHeaders(),
    data: { nombre, descripcion },
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo registrar la categoria.');
        return;
      }

      const categoria = response.data || {};
      seleccionarOpcion('#ingreso_egreso_categoria', categoria.idingreso_egreso_categoria, categoria.nombre);
      $('#modal-categoria-rapida-ingreso-egreso').modal('hide');
      mostrarOk(response.message || 'Categoria registrada correctamente.');
    },
    error: function (xhr) {
      mostrarError(extraerPrimerError(xhr) || xhr.responseJSON?.message || 'Error al registrar categoria.');
    },
    complete: function () {
      setEstadoBotonRapido($('#btn-guardar-categoria-rapida'), false);
    },
  });
}

function guardarProveedorRapido() {
  const descripcion = $('#proveedor_rapido_descripcion').val().trim();
  const numeroDocumento = $('#proveedor_rapido_numero_documento').val().trim();

  if (!descripcion) {
    mostrarError('Ingrese el nombre o razon social del proveedor.');
    $('#proveedor_rapido_descripcion').focus();
    return;
  }

  if (!numeroDocumento) {
    mostrarError('Ingrese el numero de documento del proveedor.');
    $('#proveedor_rapido_numero_documento').focus();
    return;
  }

  setEstadoBotonRapido($('#btn-guardar-proveedor-rapido'), true);

  $.ajax({
    url: apiUrl('/proveedores/rapido'),
    type: 'POST',
    headers: ajaxHeaders(),
    data: {
      tipo_persona_sunat: $('#proveedor_rapido_tipo_persona_sunat').val(),
      tipo_documento: $('#proveedor_rapido_tipo_documento').val(),
      numero_documento: numeroDocumento,
      descripcion,
      nombre_comercial: $('#proveedor_rapido_nombre_comercial').val().trim(),
      nombre_persona_natural: $('#proveedor_rapido_nombre_persona_natural').val().trim(),
      apellido_paterno_persona_natural: $('#proveedor_rapido_apellido_paterno').val().trim(),
      apellido_materno_persona_natural: $('#proveedor_rapido_apellido_materno').val().trim(),
      sexo: $('#proveedor_rapido_sexo').val(),
      fecha_nacimiento: $('#proveedor_rapido_fecha_nacimiento').val(),
      estado_civil: $('#proveedor_rapido_estado_civil').val(),
      nacionalidad: $('#proveedor_rapido_nacionalidad').val().trim(),
      celular: $('#proveedor_rapido_celular').val().trim(),
      correo: $('#proveedor_rapido_correo').val().trim(),
      direccion: $('#proveedor_rapido_direccion').val().trim(),
      direccion_referencia: $('#proveedor_rapido_direccion_referencia').val().trim(),
      iddistrito: $('#proveedor_rapido_iddistrito').val(),
      cod_ubigeo: $('#proveedor_rapido_cod_ubigeo').val().trim(),
    },
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo registrar el proveedor.');
        return;
      }

      const proveedor = response.data || {};
      const texto = [proveedor.numero_documento, proveedor.nombre_proveedor].filter(Boolean).join(' - ');
      seleccionarOpcion('#ingreso_egreso_idproveedor', proveedor.idpersona, texto || proveedor.nombre_proveedor);
      $('#modal-proveedor-rapido-ingreso-egreso').modal('hide');
      mostrarOk(response.message || 'Proveedor registrado correctamente.');
    },
    error: function (xhr) {
      mostrarError(extraerPrimerError(xhr) || xhr.responseJSON?.message || 'Error al registrar proveedor.');
    },
    complete: function () {
      setEstadoBotonRapido($('#btn-guardar-proveedor-rapido'), false);
    },
  });
}

function calcularTotalesIngresoEgreso(event) {
  const campo = event?.currentTarget?.id || '';
  const valIgv = Number($('#ingreso_egreso_val_igv').val() || 0);

  if (campo === 'ingreso_egreso_precio_sin_igv') {
    ingresoEgresoCalculoDesde = 'sin_igv';
  } else if (campo === 'ingreso_egreso_precio_con_igv') {
    ingresoEgresoCalculoDesde = 'con_igv';
  }

  if (ingresoEgresoCalculoDesde === 'con_igv') {
    const total = Number($('#ingreso_egreso_precio_con_igv').val() || 0);
    const divisor = 1 + (valIgv / 100);
    const sinIgv = divisor > 0 ? Number((total / divisor).toFixed(2)) : total;
    const igv = Number((total - sinIgv).toFixed(2));

    $('#ingreso_egreso_precio_sin_igv').val(sinIgv.toFixed(2));
    $('#ingreso_egreso_precio_igv').val(igv.toFixed(2));
    return;
  }

  const sinIgv = Number($('#ingreso_egreso_precio_sin_igv').val() || 0);
  const igv = Number((sinIgv * (valIgv / 100)).toFixed(2));
  const total = Number((sinIgv + igv).toFixed(2));

  $('#ingreso_egreso_precio_igv').val(igv.toFixed(2));
  $('#ingreso_egreso_precio_con_igv').val(total.toFixed(2));
}

function limpiarFormularioIngresoEgreso() {
  const form = $('#form-ingreso-egreso');
  ingresoEgresoCalculoDesde = 'sin_igv';
  form[0].reset();
  form.validate().resetForm();
  form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
  $('#idingreso_egreso').val('');
  $('#ingreso_egreso_idproveedor, #ingreso_egreso_idtrabajador, #ingreso_egreso_categoria').empty().val(null).trigger('change');
  $('#ingreso_egreso_tipo_movimiento').val('').trigger('change.select2');
  $('#ingreso_egreso_tipo_comprobante').val('').trigger('change.select2');
  $('#ingreso_egreso_serie_comprobante').val('');
  $('#ingreso_egreso_comprobante_actual').val('');
  $('#link-comprobante-actual').addClass('d-none').attr('href', 'javascript:void(0);');
}

function mostrarVistaIngresoEgreso(vista) {
  const esFormulario = vista === 'formulario';
  $('#div-tabla-ingreso-egreso').toggle(!esFormulario);
  $('#div-formulario-ingreso-egreso').toggle(esFormulario);
  $('#btn-regresar-ingreso-egreso').toggle(esFormulario);
  $('#div-btn-nuevo-ingreso-egreso').toggle(!esFormulario && puedeIngresoEgreso('crear'));
  $('#incluir-papelera-ingreso-egreso').closest('.form-check').toggle(!esFormulario);
}

function recargarTablaIngresoEgreso() {
  tablaIngresoEgreso?.ajax.reload(null, false);
}

function confirmarAccionIngresoEgreso(title, text, confirmButtonText, callback) {
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

function mostrarDetalle(title, html) {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title,
      html,
      icon: 'info',
      width: 760,
      customClass: {
        htmlContainer: 'text-start',
      },
    });
    return;
  }

  alert($(html).text());
}

function cambiarEstadoBoton($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function setEstadoBotonRapido($button, guardando) {
  const html = guardando
    ? '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...'
    : '<i class="ti ti-device-floppy me-1"></i> Guardar';
  $button.prop('disabled', guardando).html(html);
}

function extraerPrimerError(xhr) {
  const response = xhr?.responseJSON || {};
  const errors = response?.data || response?.errors || {};
  const first = Object.values(errors)[0];
  if (!first) return null;
  return Array.isArray(first) ? first[0] : first;
}

function renderTextoFuerte(data) {
  return `<span class="fw-semibold">${escapeHtml(data || '-')}</span>`;
}

function renderFecha(data) {
  return renderFechaTexto(data);
}

function renderFechaTexto(data) {
  if (!data) return '-';
  const date = new Date(`${String(data).slice(0, 10)}T00:00:00`);
  return Number.isNaN(date.getTime())
    ? String(data)
    : date.toLocaleDateString('es-PE', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

function renderMoneda(data) {
  return data !== null && data !== undefined && data !== ''
    ? `S/ ${Number(data).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
    : '<span class="text-muted">-</span>';
}

function renderMonedaTexto(data) {
  return data !== null && data !== undefined && data !== ''
    ? `S/ ${Number(data).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
    : '-';
}

function renderTipoMovimiento(data) {
  const value = String(data || '').toUpperCase();
  if (value === 'INGRESO') {
    return '<span class="badge bg-success-transparent"><i class="ri-arrow-up-circle-line me-1"></i>INGRESO</span>';
  }
  if (value === 'EGRESO') {
    return '<span class="badge bg-danger-transparent"><i class="ri-arrow-down-circle-line me-1"></i>EGRESO</span>';
  }
  return '<span class="text-muted">-</span>';
}

function renderEstado(estado) {
  return String(estado) === '1'
    ? '<span class="badge bg-success-transparent">Activo</span>'
    : '<span class="badge bg-danger-transparent">Eliminado</span>';
}

function mostrarOk(message) {
  if (typeof toastr !== 'undefined') toastr.success(message);
  else alert(message);
}

function mostrarError(message) {
  if (typeof toastr !== 'undefined') toastr.error(message);
  else alert(message);
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function escapeAttr(value) {
  return escapeHtml(value).replace(/`/g, '&#096;');
}
