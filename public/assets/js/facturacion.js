let tablaFacturacion = null;
let tablaProductosFacturacionModal = null;
let calendarioRangoFacturacion = null;
let calendarioMesFacturacion = null;
let filtroFechasActivoFacturacion = true;
let documentoContextualFacturacion = null;
let notaCreditoEditandoFacturacion = null;
let comprobanteEditandoFacturacion = null;
let offcanvasDetalleFacturacion = null;
let facturaArchivosNuevos = [];
let facturaArchivosExistentes = [];
let facturaArchivosEliminar = [];
let facturaArchivosConfiguracion = {};
let catalogoFiltrosFacturacion = {
  tipos_documento: [],
  estados: [],
  anios: [],
};
let catalogoCreacionFacturacion = {
  igv: 0,
  tipos_documento: [],
  series: [],
  motivos_nota_credito: [],
  series_nota_credito: [],
  cuentas_bancarias: [],
};

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
  inicializarFiltrosFacturacion();
  inicializarFormularioFacturaFacturacion();
  inicializarSelectsAnulacionSunatFacturacion();
  inicializarTablaFacturacion();
  inicializarModalProductosFacturacion();
  inicializarBuscadorDirectoProductosFacturacion();
  $('#btn-nueva-factura').on('click', abrirFormularioFacturaFacturacion);
  $('#btn-regresar-facturacion, #btn-cancelar-facturacion').on('click', function () {
    mostrarVistaFacturacion('tabla');
  });
  $('#incluir-eliminados-facturacion').on('change', function () {
    tablaFacturacion?.ajax.reload(null, true);
  });
  $('#form-anular-sunat-facturacion').on('submit', enviarAnulacionSunatFacturacion);
  $('#opcion-facturacion-imprimir').on('click', function (event) {
    event.preventDefault();
    ocultarMenuContextualFacturacion();
    abrirImpresionDocumentoFacturacion(documentoContextualFacturacion);
  });
  $('#opcion-facturacion-detalle').on('click', function (event) {
    event.preventDefault();
    ocultarMenuContextualFacturacion();
    abrirModalDetalleSunatFacturacion(documentoContextualFacturacion);
  });
  $('#opcion-facturacion-anular').on('click', function (event) {
    event.preventDefault();
    if ($(this).hasClass('disabled')) return;
    ocultarMenuContextualFacturacion();
    abrirModalAnularSunatFacturacion(documentoContextualFacturacion);
  });
  $('#opcion-facturacion-desactivar-ticket').on('click', function (event) {
    event.preventDefault();
    if ($(this).hasClass('disabled')) return;
    ocultarMenuContextualFacturacion();
    confirmarDesactivacionTicketFacturacion(documentoContextualFacturacion);
  });
  $('.js-imprimir-formato-facturacion').on('click', function () {
    imprimirFormatoFacturacion(String($(this).data('formato') || 'a4'));
  });
  $(document).on('click scroll', function (event) {
    if (!$(event.target).closest('#menu-contextual').length) {
      ocultarMenuContextualFacturacion();
    }
  });
});

function inicializarSelectsAnulacionSunatFacturacion() {
  const $modal = $('#modal-anular-sunat-facturacion');

  $('#anular_facturacion_motivo').select2({
    theme: 'bootstrap4',
    width: '100%',
    allowClear: true,
    placeholder: 'Seleccione el motivo SUNAT',
    dropdownParent: $modal,
  });

  $('#anular_facturacion_serie').select2({
    theme: 'bootstrap4',
    width: '100%',
    allowClear: false,
    minimumResultsForSearch: Infinity,
    dropdownParent: $modal,
  });
}

function inicializarFiltrosFacturacion() {
  establecerRangoFechasInicialFacturacion();
  inicializarCalendariosFacturacion();
  actualizarModoFiltroFechaFacturacion();

  const baseConfig = {
    theme: 'bootstrap4',
    width: '100%',
    allowClear: true,
  };

  $('#filtro_facturacion_cliente').select2({
    ...baseConfig,
    placeholder: 'Todos',
    ajax: {
      url: apiUrl('/facturacion/clientes'),
      dataType: 'json',
      delay: 250,
      headers: ajaxHeaders(),
      cache: false,
      data: function (params) {
        return {
          term: params.term || '',
          page: params.page || 1,
          fecha_inicio: obtenerFechaInicioFacturacion(),
          fecha_fin: obtenerFechaFinFacturacion(),
        };
      },
      processResults: function (response) {
        const data = response?.data || {};
        const items = Array.isArray(data.items) ? data.items : [];

        return {
          results: items.map(function (item) {
            return {
              id: item.id,
              text: item.text,
            };
          }),
          pagination: {
            more: Boolean(data.pagination?.more),
          },
        };
      },
    },
  });

  $('#filtro_facturacion_tipo_documento').select2({
    ...baseConfig,
    placeholder: 'Todos',
    closeOnSelect: false,
  });

  $('#filtro_facturacion_estado').select2({
    ...baseConfig,
    placeholder: 'Todos',
    closeOnSelect: false,
    minimumResultsForSearch: Infinity,
  });

  $('input[name="filtro_facturacion_tipo_fecha"]').on('change', function () {
    actualizarModoFiltroFechaFacturacion();
    if (filtroFechasActivoFacturacion) {
      recargarFacturacionPorCambioFecha();
    }
  });

  $('#filtro_facturacion_fecha_anio').on('change', function () {
    filtroFechasActivoFacturacion = true;
    recargarFacturacionPorCambioFecha();
  });

  $('#btn_limpiar_fechas_facturacion').on('click', function () {
    limpiarFiltroFechasFacturacion();
  });

  $('#filtro_facturacion_cliente, #filtro_facturacion_tipo_documento, #filtro_facturacion_estado').on('change', function () {
    tablaFacturacion?.ajax.reload(null, true);
  });

  cargarFiltrosFacturacion();
}

function recargarFacturacionPorCambioFecha() {
  $('#filtro_facturacion_cliente').val(null).trigger('change.select2');
  tablaFacturacion?.ajax.reload(null, true);
}

function limpiarFiltroFechasFacturacion() {
  $('#filtro_facturacion_fecha_inicio').val('');
  $('#filtro_facturacion_fecha_fin').val('');
  $('#filtro_facturacion_fecha_mes').val('');
  $('#filtro_facturacion_fecha_anio').val('');

  calendarioRangoFacturacion?.clear(false);
  calendarioMesFacturacion?.clear(false);
  filtroFechasActivoFacturacion = false;
  recargarFacturacionPorCambioFecha();
}

function establecerRangoFechasInicialFacturacion() {
  const hoy = new Date();
  const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

  $('#filtro_facturacion_fecha_inicio').val(formatearFechaInputFacturacion(primerDiaMes));
  $('#filtro_facturacion_fecha_fin').val(formatearFechaInputFacturacion(hoy));
  $('#filtro_facturacion_fecha_mes').val(formatearMesInputFacturacion(hoy));
}

function inicializarCalendariosFacturacion() {
  if (typeof flatpickr === 'undefined') {
    return;
  }

  const localeEs = flatpickr.l10ns?.es || 'es';
  const fechaInicioDefault = crearFechaLocalFacturacion(obtenerFechaInicioFacturacion());
  const fechaFinDefault = crearFechaLocalFacturacion(obtenerFechaFinFacturacion());

  calendarioRangoFacturacion = flatpickr('#filtro_facturacion_fecha_rango_input', {
    mode: 'range',
    locale: localeEs,
    dateFormat: 'd/m/Y',
    conjunction: ' a ',
    defaultDate: [fechaInicioDefault, fechaFinDefault].filter(Boolean),
    onChange: function (selectedDates) {
      if (selectedDates.length !== 2) {
        return;
      }

      $('#filtro_facturacion_fecha_inicio').val(formatearFechaInputFacturacion(selectedDates[0]));
      $('#filtro_facturacion_fecha_fin').val(formatearFechaInputFacturacion(selectedDates[1]));
      filtroFechasActivoFacturacion = true;
      recargarFacturacionPorCambioFecha();
    },
    onOpen: function (_selectedDates, _dateStr, instance) {
      instalarSelectorAnioFlatpickrFacturacion(instance);
    },
    onReady: function (_selectedDates, _dateStr, instance) {
      instalarSelectorAnioFlatpickrFacturacion(instance);
    },
    onMonthChange: function (_selectedDates, _dateStr, instance) {
      instalarSelectorAnioFlatpickrFacturacion(instance);
    },
    onYearChange: function (_selectedDates, _dateStr, instance) {
      instalarSelectorAnioFlatpickrFacturacion(instance);
    },
  });

  const pluginsMes = typeof monthSelectPlugin !== 'undefined'
    ? [
      new monthSelectPlugin({
        shorthand: false,
        dateFormat: 'Y-m',
        altFormat: 'F Y',
      }),
    ]
    : [];

  calendarioMesFacturacion = flatpickr('#filtro_facturacion_fecha_mes', {
    locale: localeEs,
    dateFormat: 'Y-m',
    defaultDate: crearFechaLocalFacturacion(`${$('#filtro_facturacion_fecha_mes').val()}-01`),
    plugins: pluginsMes,
    onChange: function (_selectedDates, dateStr) {
      $('#filtro_facturacion_fecha_mes').val(dateStr);
      filtroFechasActivoFacturacion = true;
      recargarFacturacionPorCambioFecha();
    },
    onOpen: function (_selectedDates, _dateStr, instance) {
      instalarSelectorAnioFlatpickrFacturacion(instance);
    },
    onReady: function (_selectedDates, _dateStr, instance) {
      instalarSelectorAnioFlatpickrFacturacion(instance);
    },
    onMonthChange: function (_selectedDates, _dateStr, instance) {
      instalarSelectorAnioFlatpickrFacturacion(instance);
    },
    onYearChange: function (_selectedDates, _dateStr, instance) {
      instalarSelectorAnioFlatpickrFacturacion(instance);
    },
  });
}

function instalarSelectorAnioFlatpickrFacturacion(instance) {
  if (!instance?.currentYearElement) {
    return;
  }

  const contenedor = instance.currentYearElement.parentElement;
  if (!contenedor) {
    return;
  }

  const anios = obtenerAniosCalendarioFacturacion();
  if (!anios.length) {
    return;
  }

  let select = contenedor.querySelector('.facturacion-flatpickr-year-select');
  if (!select) {
    contenedor.classList.add('facturacion-flatpickr-year-wrapper');
    select = document.createElement('select');
    select.className = 'facturacion-flatpickr-year-select';
    select.setAttribute('aria-label', 'Año');

    select.addEventListener('change', function () {
      const anio = Number(this.value);
      if (!Number.isFinite(anio)) {
        return;
      }
      instance.changeYear(anio);
      this.value = String(instance.currentYear);
    });

    contenedor.insertBefore(select, instance.currentYearElement);
    instance.currentYearElement.classList.add('d-none');
    instance.currentYearElement.setAttribute('tabindex', '-1');
    contenedor.querySelectorAll('.arrowUp, .arrowDown').forEach(function (arrow) {
      arrow.classList.add('d-none');
    });
  }

  const valorActual = String(instance.currentYear || new Date().getFullYear());
  const opcionesActuales = Array.from(select.options).map(function (option) {
    return option.value;
  });

  if (opcionesActuales.join('|') !== anios.join('|')) {
    select.innerHTML = '';
    anios.forEach(function (anio) {
      select.append(new Option(String(anio), String(anio), false, false));
    });
  }

  if (anios.includes(valorActual)) {
    select.value = valorActual;
  } else {
    select.value = anios[0];
    instance.changeYear(Number(anios[0]));
  }
}

function obtenerAniosCalendarioFacturacion() {
  const anios = (catalogoFiltrosFacturacion.anios || [])
    .map(function (item) {
      return String(item.id ?? item.text ?? '').trim();
    })
    .filter(Boolean);

  return anios.length ? anios : [String(new Date().getFullYear())];
}

function formatearFechaInputFacturacion(fecha) {
  const year = fecha.getFullYear();
  const month = String(fecha.getMonth() + 1).padStart(2, '0');
  const day = String(fecha.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

function crearFechaLocalFacturacion(fecha) {
  const partes = String(fecha || '').split('-').map(function (parte) {
    return Number(parte);
  });

  if (partes.length !== 3 || partes.some(function (parte) { return !Number.isFinite(parte); })) {
    return null;
  }

  return new Date(partes[0], partes[1] - 1, partes[2]);
}

function formatearMesInputFacturacion(fecha) {
  const year = fecha.getFullYear();
  const month = String(fecha.getMonth() + 1).padStart(2, '0');

  return `${year}-${month}`;
}

function obtenerTipoFechaFacturacion() {
  return String($('input[name="filtro_facturacion_tipo_fecha"]:checked').val() || 'fecha');
}

function actualizarModoFiltroFechaFacturacion() {
  const tipoFecha = obtenerTipoFechaFacturacion();

  $('#filtro_facturacion_fecha_rango').toggleClass('d-none', tipoFecha !== 'fecha');
  $('#filtro_facturacion_fecha_mes').toggleClass('d-none', tipoFecha !== 'mes');
  $('#filtro_facturacion_fecha_anio').toggleClass('d-none', tipoFecha !== 'anio');
}

function obtenerFechaInicioFacturacion() {
  if (!filtroFechasActivoFacturacion) {
    return '';
  }

  const tipoFecha = obtenerTipoFechaFacturacion();

  if (tipoFecha === 'mes') {
    const mes = String($('#filtro_facturacion_fecha_mes').val() || '').trim();
    return mes ? `${mes}-01` : '';
  }

  if (tipoFecha === 'anio') {
    const anio = String($('#filtro_facturacion_fecha_anio').val() || '').trim();
    return anio ? `${anio}-01-01` : '';
  }

  return String($('#filtro_facturacion_fecha_inicio').val() || '').trim();
}

function obtenerFechaFinFacturacion() {
  if (!filtroFechasActivoFacturacion) {
    return '';
  }

  const tipoFecha = obtenerTipoFechaFacturacion();

  if (tipoFecha === 'mes') {
    const mes = String($('#filtro_facturacion_fecha_mes').val() || '').trim();
    const partes = mes.split('-');

    if (partes.length !== 2) return '';

    const year = Number(partes[0]);
    const month = Number(partes[1]);

    if (!Number.isFinite(year) || !Number.isFinite(month)) return '';

    return formatearFechaInputFacturacion(new Date(year, month, 0));
  }

  if (tipoFecha === 'anio') {
    const anio = String($('#filtro_facturacion_fecha_anio').val() || '').trim();
    return anio ? `${anio}-12-31` : '';
  }

  return String($('#filtro_facturacion_fecha_fin').val() || '').trim();
}

function cargarFiltrosFacturacion() {
  $.ajax({
    url: apiUrl('/facturacion/filtros'),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response?.status) {
        mostrarErrorFacturacion(response?.message || 'No se pudieron cargar los filtros de facturacion.');
        return;
      }

      const data = response?.data || {};
      catalogoFiltrosFacturacion = {
        tipos_documento: Array.isArray(data.tipos_documento) ? data.tipos_documento : [],
        estados: Array.isArray(data.estados) ? data.estados : [],
        anios: Array.isArray(data.anios) ? data.anios : [],
      };

      renderOpcionesFiltroFacturacion('#filtro_facturacion_tipo_documento', catalogoFiltrosFacturacion.tipos_documento, 'Todos');
      renderOpcionesFiltroFacturacion('#filtro_facturacion_estado', catalogoFiltrosFacturacion.estados, 'Todos');
      renderOpcionesAnioFacturacion(catalogoFiltrosFacturacion.anios);
      actualizarSelectsAnioFlatpickrFacturacion();
    },
    error: function () {
      mostrarErrorFacturacion('Error al cargar filtros de facturacion.');
    },
  });
}

function actualizarSelectsAnioFlatpickrFacturacion() {
  [calendarioRangoFacturacion, calendarioMesFacturacion].forEach(function (instance) {
    instalarSelectorAnioFlatpickrFacturacion(instance);
  });
}

function renderOpcionesAnioFacturacion(opciones) {
  const $select = $('#filtro_facturacion_fecha_anio');
  const anioActual = String(new Date().getFullYear());
  const valorActual = String($select.val() || anioActual);
  const items = (opciones || []).map(function (item) {
    return {
      id: String(item.id ?? '').trim(),
      text: String(item.text ?? item.id ?? '').trim(),
    };
  }).filter(function (item) {
    return item.id !== '' && item.text !== '';
  });

  $select.empty();
  items.forEach(function (item) {
    $select.append(new Option(item.text, item.id, false, false));
  });

  if (items.some(function (item) { return item.id === valorActual; })) {
    $select.val(valorActual);
  } else if (items.length) {
    $select.val(items[0].id);
  }
}

function renderOpcionesFiltroFacturacion(selector, opciones, textoTodos) {
  const $select = $(selector);
  const esMultiple = $select.prop('multiple');
  const valorActual = esMultiple
    ? ($select.val() || []).map(function (valor) { return String(valor); })
    : [String($select.val() || '')].filter(Boolean);
  const items = (opciones || []).map(function (item) {
    return {
      id: String(item.id ?? '').trim(),
      text: String(item.text ?? '').trim(),
    };
  }).filter(function (item) {
    return item.id !== '' && item.text !== '';
  });

  $select.empty();
  if (!esMultiple) {
    $select.append(new Option(textoTodos, '', false, false));
  }

  items.forEach(function (item) {
    $select.append(new Option(item.text, item.id, false, false));
  });

  const valoresDisponibles = items.map(function (item) { return item.id; });
  const valoresSeleccionados = valorActual.filter(function (valor) {
    return valoresDisponibles.includes(valor);
  });

  if (esMultiple) {
    $select.val(valoresSeleccionados).trigger('change.select2');
  } else if (valoresSeleccionados.length) {
    $select.val(valoresSeleccionados[0]).trigger('change.select2');
  } else {
    $select.val('').trigger('change.select2');
  }
}

function inicializarFormularioFacturaFacturacion() {
  $('#factura_cliente').select2({
    theme: 'bootstrap4',
    width: '100%',
    allowClear: true,
    placeholder: 'Buscar cliente',
    ajax: {
      url: apiUrl('/facturacion/clientes-factura'),
      dataType: 'json',
      delay: 250,
      headers: ajaxHeaders(),
      data: function (params) {
        return {
          term: params.term || '',
          page: params.page || 1,
          idsunat_c01_tipo_comprobante: obtenerIdTipoDocumentoCreacionFacturacion(),
        };
      },
      processResults: function (response) {
        const data = response?.data || {};
        const items = Array.isArray(data.items) ? data.items : [];

        return {
          results: items.map(function (item) {
            return {
              id: item.id,
              text: item.text,
            };
          }),
          pagination: {
            more: Boolean(data.pagination?.more),
          },
        };
      },
    },
  });

  $('#factura_serie').select2({
    theme: 'bootstrap4',
    width: '100%',
    allowClear: false,
    minimumResultsForSearch: Infinity,
  });

  configurarValidacionFormularioFacturaFacturacion();

  $('#facturacion_tipo_documento_group').on('change', 'input[name="factura_tipo_documento_radio"]', function () {
    if ($('#form-factura-facturacion').data('validator')) {
      $(this).valid();
    }
    actualizarFormularioPorTipoDocumentoFacturacion();
  });

  $('#btn-agregar-item-factura').on('click', function () {
    abrirModalProductosFacturacion();
  });

  $('#factura_cliente, #factura_serie').on('change', function () {
    if ($('#form-factura-facturacion').data('validator')) {
      $(this).valid();
    }
  });

  $('#factura_detalle_body').on('input', '.facturacion-item-cantidad, .facturacion-item-precio', function () {
    calcularTotalesFacturaFacturacion();
    revalidarDetalleFacturaFacturacion();
  });
  $('#factura_detalle_body').on('input', '.facturacion-item-descuento-porcentaje', function () {
    $(this).closest('tr').find('.facturacion-item-descuento').val('0.00');
    calcularTotalesFacturaFacturacion();
    revalidarDetalleFacturaFacturacion();
  });
  $('#factura_detalle_body').on('input', '.facturacion-item-descuento', function () {
    $(this).closest('tr').find('.facturacion-item-descuento-porcentaje').val('0.00');
    calcularTotalesFacturaFacturacion();
    revalidarDetalleFacturaFacturacion();
  });
  $('#factura_detalle_body').on('click', '.btn-quitar-item-factura', function () {
    $(this).closest('tr').remove();
    calcularTotalesFacturaFacturacion();
    revalidarDetalleFacturaFacturacion();
  });
  $('#factura_detalle_body').on('dblclick', '.facturacion-item-descripcion-texto', function () {
    const $textarea = $(this).closest('.facturacion-item-descripcion-wrap').find('.facturacion-item-descripcion');
    $(this).addClass('d-none');
    $textarea.removeClass('d-none').trigger('focus').trigger('select');
  });
  $('#factura_detalle_body').on('input', '.facturacion-item-descripcion', function () {
    const descripcion = String($(this).val() || '');
    $(this).closest('.facturacion-item-descripcion-wrap').find('.facturacion-item-descripcion-texto').text(descripcion || 'Sin descripcion');
    revalidarDetalleFacturaFacturacion();
  });
  $('#factura_detalle_body').on('blur', '.facturacion-item-descripcion', function () {
    $(this).addClass('d-none');
    $(this).closest('.facturacion-item-descripcion-wrap').find('.facturacion-item-descripcion-texto').removeClass('d-none');
  });

  $('#btn-cc-agregar-metodo-pago').on('click', function () {
    agregarFilaMetodoPagoFacturacion();
    revalidarDetalleFacturaFacturacion();
  });

  $('#cc_pago_metodos_body').on('click', '.js-cc-eliminar-metodo', function () {
    $(this).closest('tr').remove();
    sincronizarMetodosPagoFacturacion();
    revalidarDetalleFacturaFacturacion();
  });

  $('#cc_pago_metodos_body').on('input change', '.js-cc-metodo-cuenta, .js-cc-metodo-monto', function () {
    if ($('#form-factura-facturacion').data('validator')) {
      $(this).valid();
      $('#cc_pago_metodos_body .js-cc-metodo-monto').each(function () {
        $(this).valid();
      });
    }
    revalidarDetalleFacturaFacturacion();
  });

  $('#cc_pago_documentos').on('change', function () {
    agregarArchivosNuevosFacturacion(this.files);
    this.value = '';
    renderArchivosFacturacion();
    revalidarDetalleFacturaFacturacion();
  });

  $('#cc-pago-archivos-body').on('click', '.js-cc-eliminar-archivo-nuevo', function () {
    const key = String($(this).data('file-key') || '');
    if (!key) return;
    facturaArchivosNuevos = facturaArchivosNuevos.filter(function (file) {
      return obtenerClaveArchivoFacturacion(file) !== key;
    });
    delete facturaArchivosConfiguracion[key];
    renderArchivosFacturacion();
    revalidarDetalleFacturaFacturacion();
  });

  $('#cc-pago-archivos-body').on('click', '.js-cc-eliminar-archivo-existente', function () {
    const idArchivo = Number($(this).data('idrdocumento-archivo') || 0);
    if (!Number.isFinite(idArchivo) || idArchivo <= 0) return;
    if (!facturaArchivosEliminar.includes(idArchivo)) {
      facturaArchivosEliminar.push(idArchivo);
    }
    renderArchivosFacturacion();
  });

  $('#cc-pago-archivos-body').on('change', '.js-cc-archivo-modo-nombre', function () {
    const key = String($(this).data('file-key') || '');
    if (!key) return;
    const $input = $(this).closest('tr').find('.js-cc-archivo-nombre-visible');
    const nombreBase = String($input.data('base-name') || '').trim();
    const modo = String($(this).val() || 'archivo') === 'otro' ? 'otro' : 'archivo';
    const config = facturaArchivosConfiguracion[key] || { modo: 'archivo', nombreVisible: nombreBase };
    config.modo = modo;
    if (modo === 'archivo') {
      config.nombreVisible = nombreBase;
      $input.val(nombreBase).addClass('d-none').removeClass('is-invalid');
    } else {
      $input.val(config.nombreVisible || '').removeClass('d-none').trigger('focus');
    }
    facturaArchivosConfiguracion[key] = config;
    revalidarDetalleFacturaFacturacion();
  });

  $('#cc-pago-archivos-body').on('input change', '.js-cc-archivo-nombre-visible', function () {
    const key = String($(this).data('file-key') || '');
    if (!key) return;
    const config = facturaArchivosConfiguracion[key] || { modo: 'otro', nombreVisible: '' };
    config.nombreVisible = String($(this).val() || '');
    facturaArchivosConfiguracion[key] = config;
    $(this).removeClass('is-invalid');
    revalidarDetalleFacturaFacturacion();
  });
}

function configurarValidacionFormularioFacturaFacturacion() {
  asegurarCampoValidacionDetalleFacturaFacturacion();

  if (!$.validator) {
    $('#form-factura-facturacion').on('submit', function (event) {
      event.preventDefault();
      confirmarGuardadoFacturaFacturacion();
    });
    return;
  }

  if (!$.validator.methods.detalleFacturaValido) {
    $.validator.addMethod('detalleFacturaValido', function (_value, element) {
      const mensaje = validarBloquesFacturaFacturacion();
      $(element).data('detalle-error', mensaje || '');
      return !mensaje;
    }, 'Revise el detalle de la factura.');
  }

  if (!$.validator.methods.facturaMetodoMontoLinea) {
    $.validator.addMethod('facturaMetodoMontoLinea', function (value, element) {
      const monto = Number(value || 0);
      if (!Number.isFinite(monto) || monto <= 0) return false;
      const diferencia = calcularBalanceMetodosPagoFacturacion().diferencia;
      if (Math.abs(diferencia) <= 0.01) return true;
      $(element).data('metodo-error', diferencia > 0
        ? `Falta S/ ${formatearNumeroFacturacion(diferencia)} para completar el total.`
        : `Excede S/ ${formatearNumeroFacturacion(Math.abs(diferencia))} del total.`);
      return false;
    }, function (_params, element) {
      return $(element).data('metodo-error') || 'La suma de metodos de pago debe coincidir con el total.';
    });
  }

  $.validator.addClassRules('js-cc-metodo-cuenta', { required: true });
  $.validator.addClassRules('js-cc-metodo-monto', {
    required: true,
    number: true,
    min: 0.01,
    facturaMetodoMontoLinea: true,
  });

  $('#form-factura-facturacion').validate({
    ignore: '.select2-search__field, .select2-input, .select2-focusser',
    rules: {
      factura_tipo_documento_radio: { required: true },
      idpersona_cliente: { required: true, digits: true },
      idserie_comprobante: { required: true, digits: true },
      fecha_emision: { required: true },
      detalle_validacion: { detalleFacturaValido: true },
    },
    messages: {
      factura_tipo_documento_radio: {
        required: 'Seleccione el tipo de comprobante.',
      },
      idpersona_cliente: {
        required: function () {
          return obtenerTipoDocumentoCreacionFacturacion() === '01'
            ? 'Seleccione el cliente RUC.'
            : 'Seleccione el cliente.';
        },
        digits: 'Cliente invalido.',
      },
      idserie_comprobante: {
        required: 'Seleccione la serie.',
        digits: 'Serie invalida.',
      },
      fecha_emision: {
        required: 'Ingrese la fecha de emision.',
      },
      detalle_validacion: {
        detalleFacturaValido: function () {
          return $('#factura_detalle_validacion').data('detalle-error') || 'Revise el detalle de la factura.';
        },
      },
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback d-block');

      if ($(element).attr('name') === 'detalle_validacion') {
        error.insertAfter($('#tabla-detalle-facturacion').closest('.table-responsive'));
        return;
      }

      if ($(element).attr('name') === 'factura_tipo_documento_radio') {
        error.insertAfter($('#facturacion_tipo_documento_group'));
        return;
      }

      if ($(element).hasClass('select2-hidden-accessible')) {
        error.insertAfter($(element).next('.select2-container'));
        return;
      }

      error.insertAfter(element);
    },
    highlight: function (element) {
      $(element).addClass('is-invalid').removeClass('is-valid');
      marcarSelect2FacturaFacturacion(element, true);
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid').addClass('is-valid');
      marcarSelect2FacturaFacturacion(element, false);
    },
    submitHandler: function (_form, event) {
      event?.preventDefault();
      confirmarGuardadoFacturaFacturacion();
    },
  });
}

function asegurarCampoValidacionDetalleFacturaFacturacion() {
  if ($('#factura_detalle_validacion').length) return;

  $('#form-factura-facturacion').append('<input type="hidden" id="factura_detalle_validacion" name="detalle_validacion" value="1">');
}

function marcarSelect2FacturaFacturacion(element, invalid) {
  const $element = $(element);
  if (!$element.hasClass('select2-hidden-accessible')) return;

  $element.next('.select2-container')
    .find('.select2-selection')
    .toggleClass('is-invalid', invalid)
    .toggleClass('is-valid', !invalid);
}

function confirmarGuardadoFacturaFacturacion() {
  const tipo = obtenerTipoDocumentoCreacionFacturacion();
  const nombre = tipo === '12' ? 'nota de venta' : (tipo === '03' ? 'boleta' : 'factura');
  const estadoSunat = tipo === '12' ? 'ACEPTADA' : 'POR ENVIAR';
  confirmarFacturacion('Guardar comprobante', `Se registrara la ${nombre} con estado ${estadoSunat}.`, 'Guardar', guardarFacturaFacturacion);
}

function revalidarDetalleFacturaFacturacion() {
  if (!$('#form-factura-facturacion').data('validator')) return;
  $('#factura_detalle_validacion').valid();
}

function limpiarValidacionFormularioFacturaFacturacion() {
  const $form = $('#form-factura-facturacion');
  $form.find('.error.invalid-feedback').remove();
  $form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
  $form.find('.select2-selection').removeClass('is-invalid is-valid');

  if ($form.data('validator')) {
    $form.validate().resetForm();
  }
}

function inicializarModalProductosFacturacion() {
  tablaProductosFacturacionModal = $('#tabla-productos-facturacion-modal').DataTable({
    responsive: false,
    processing: true,
    serverSide: true,
    paging: true,
    ordering: true,
    searching: true,
    deferRender: true,
    searchDelay: 250,
    pageLength: 10,
    lengthMenu: [[10, 25, 50], [10, 25, 50]],
    dom: "<'row'<'col-md-8 pt-2'f><'col-md-4 pt-2 d-flex justify-content-end'<'length'l><'buttons'B>>>r t <'row'<'col-md-6'i><'col-md-6'p>>",
    buttons: [
      {
        text: '<i class="bi bi-arrow-clockwise"></i>',
        className: 'buttons-reload btn btn-outline-info',
        action: function (_event, dt) {
          dt.ajax.reload(null, false);
        },
      },
    ],
    ajax: {
      url: apiUrl('/facturacion/productos'),
      type: 'GET',
      headers: ajaxHeaders(),
      dataSrc: function (response) {
        if (response && response.status === false) {
          mostrarErrorFacturacion(response?.message || 'No se pudieron cargar productos.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorFacturacion('Error al cargar productos.');
      },
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center',
        render: function (_data, _type, row) {
          const payload = encodeURIComponent(JSON.stringify(row || {}));
          return `
            <button type="button" class="btn btn-sm btn-primary py-0 js-seleccionar-producto-facturacion" data-payload="${payload}" title="Agregar producto">
              <i class="ri-add-line"></i>
            </button>
          `;
        },
      },
      {
        data: 'codigo',
        render: function (value) {
          return escapeHtmlFacturacion(value || '-');
        },
      },
      {
        data: null,
        render: function (row) {
          return `<div class="fw-semibold text-wrap">${escapeHtmlFacturacion(row?.nombre || row?.text || '-')}</div>`;
        },
      },
      {
        data: 'precio_venta',
        className: 'text-end',
        render: renderMonedaFacturacion,
      },
    ],
    order: [[2, 'asc']],
    language: {
      lengthMenu: '_MENU_',
      search: '',
      sLoadingRecords: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando productos...',
    },
  });

  $('#modal-productos-facturacion').on('shown.bs.modal', function () {
    tablaProductosFacturacionModal?.columns.adjust();
    tablaProductosFacturacionModal?.ajax.reload(null, false);
  });

  $('#tabla-productos-facturacion-modal tbody').on('click', '.js-seleccionar-producto-facturacion', function () {
    const raw = String($(this).attr('data-payload') || '');
    let producto = null;
    try {
      producto = JSON.parse(decodeURIComponent(raw));
    } catch (_error) {
      producto = null;
    }
    if (!producto) return;

    agregarProductoADetalleFacturacion(producto);
    $('#modal-productos-facturacion').modal('hide');
  });
}

function abrirModalProductosFacturacion() {
  $('#modal-productos-facturacion').modal('show');
}

function inicializarBuscadorDirectoProductosFacturacion() {
  let debounce = null;

  $('#buscar_producto_directo_factura').on('input', function () {
    const termino = String($(this).val() || '').trim();
    clearTimeout(debounce);

    if (termino.length < 2) {
      ocultarResultadosBuscadorProductoFacturacion();
      return;
    }

    debounce = setTimeout(async function () {
      try {
        const resultados = await buscarProductosDirectoFacturacion(termino, 30);
        renderResultadosBuscadorProductoFacturacion(resultados);
      } catch (error) {
        console.error(error);
        ocultarResultadosBuscadorProductoFacturacion();
      }
    }, 220);
  });

  $('#buscar_producto_directo_factura').on('keydown', function (event) {
    if (event.key === 'Escape') {
      ocultarResultadosBuscadorProductoFacturacion();
      return;
    }
    if (event.key !== 'Enter') return;
    event.preventDefault();
    $('#lista_buscar_producto_directo_factura .js-item-buscar-producto-facturacion').first().trigger('click');
  });

  $(document).on('click', function (event) {
    if ($(event.target).closest('#buscar_producto_directo_factura, #lista_buscar_producto_directo_factura').length) return;
    ocultarResultadosBuscadorProductoFacturacion();
  });

  $('#lista_buscar_producto_directo_factura').on('click', '.js-item-buscar-producto-facturacion', function () {
    const raw = String($(this).attr('data-payload') || '');
    let producto = null;
    try {
      producto = JSON.parse(decodeURIComponent(raw));
    } catch (_error) {
      producto = null;
    }
    if (!producto) return;

    agregarProductoADetalleFacturacion(producto);
  });
}

async function buscarProductosDirectoFacturacion(termino, limite = 30) {
  const response = await $.ajax({
    url: apiUrl('/facturacion/productos'),
    type: 'GET',
    headers: ajaxHeaders(),
    data: {
      search: termino,
      limit: limite,
    },
  });

  if (!response?.status) {
    throw new Error(response?.message || 'No se pudo buscar productos.');
  }

  return response.data?.items || [];
}

function renderResultadosBuscadorProductoFacturacion(items) {
  const $lista = $('#lista_buscar_producto_directo_factura');
  $lista.empty();

  if (!items?.length) {
    $lista.append('<div class="list-group-item small text-muted">Sin coincidencias</div>');
    $lista.removeClass('d-none');
    return;
  }

  items.forEach(function (item) {
    const titulo = escapeHtmlFacturacion(item.nombre || item.text || `Producto #${item.id}`);
    const codigo = escapeHtmlFacturacion(item.codigo || '-');
    const precio = renderMonedaTextoFacturacion(item.precio_venta || 0);
    const payload = encodeURIComponent(JSON.stringify(item || {}));

    $lista.append(`
      <button type="button" class="bg-white list-group-item list-group-item-action js-item-buscar-producto-facturacion py-1" data-payload="${payload}">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="fw-semibold"><span class="fs-12 text-muted">${codigo}</span> ${titulo}</div>
          </div>
          <div class="small text-nowrap">${precio}</div>
        </div>
      </button>
    `);
  });

  $lista.removeClass('d-none');
}

function ocultarResultadosBuscadorProductoFacturacion() {
  $('#lista_buscar_producto_directo_factura').addClass('d-none').empty();
}

function agregarProductoADetalleFacturacion(producto) {
  const idproducto = Number(producto?.idproducto || producto?.id || 0);
  if (!Number.isFinite(idproducto) || idproducto <= 0) {
    mostrarErrorFacturacion('No se pudo identificar el producto.');
    return;
  }

  const codigo = String(producto?.codigo || '').trim();
  const nombre = String(producto?.nombre || producto?.text || '').trim();

  agregarItemFacturaFacturacion({
    idproducto,
    descripcion: nombre || producto?.text || 'Producto',
    codigo,
    cantidad: 1,
    precio_venta: Number(producto?.precio_venta || 0),
    descuento: 0,
    descuento_porcentaje: 0,
  });

  $('#buscar_producto_directo_factura').val('').trigger('focus');
  ocultarResultadosBuscadorProductoFacturacion();
  revalidarDetalleFacturaFacturacion();
}

function abrirFormularioFacturaFacturacion() {
  cargarCatalogosCreacionFacturacion(function () {
    limpiarFormularioFacturaFacturacion();
    mostrarVistaFacturacion('formulario');
  });
}

function mostrarVistaFacturacion(vista) {
  const formulario = vista === 'formulario';
  $('#div-tabla').toggle(!formulario);
  $('#div-formulario-facturacion').toggle(formulario);
  $('#div-filtro-principal-facturacion').toggle(!formulario);
  $('#btn-nueva-factura').toggle(!formulario);
  $('#btn-regresar-facturacion').toggle(formulario);
  $('#btn-guardar-factura-superior').toggle(formulario);
}

function cargarCatalogosCreacionFacturacion(callback) {
  $.ajax({
    url: apiUrl('/facturacion/catalogos-creacion'),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response?.status) {
        mostrarErrorFacturacion(response?.message || 'No se pudieron cargar los catalogos de facturacion.');
        return;
      }

      const data = response.data || {};
      catalogoCreacionFacturacion = {
        igv: Number(data.igv || 0),
        tipos_documento: Array.isArray(data.tipos_documento) ? data.tipos_documento : [],
        series: Array.isArray(data.series) ? data.series : [],
        motivos_nota_credito: Array.isArray(data.motivos_nota_credito) ? data.motivos_nota_credito : [],
        series_nota_credito: Array.isArray(data.series_nota_credito) ? data.series_nota_credito : [],
        cuentas_bancarias: Array.isArray(data.cuentas_bancarias) ? data.cuentas_bancarias : [],
      };
      renderTiposDocumentoCreacionFacturacion();
      renderSeriesFacturaFacturacion();

      if (typeof callback === 'function') callback();
    },
    error: function (xhr) {
      mostrarErrorFacturacion(extraerPrimerErrorFacturacion(xhr) || 'Error al cargar catalogos de facturacion.');
    },
  });
}

function renderTiposDocumentoCreacionFacturacion() {
  const $group = $('#facturacion_tipo_documento_group');
  const tipos = catalogoCreacionFacturacion.tipos_documento || [];
  const actual = obtenerIdTipoDocumentoCreacionFacturacion();
  $group.empty();

  tipos.forEach(function (tipo, index) {
    const codigo = String(tipo.codigo || '').trim();
    const idTipo = String(tipo.id || '').trim();
    const inputId = `factura_tipo_documento_${idTipo || codigo || index}`;
    const texto = codigo === '12'
      ? 'Nota de venta'
      : String(tipo.abreviatura || tipo.nombre || codigo).trim();
    const checked = actual === idTipo || (!actual && index === 0);

    $group.append(`
      <input type="radio" class="btn-check" name="factura_tipo_documento_radio" id="${inputId}" value="${escapeHtmlFacturacion(idTipo)}" data-codigo="${escapeHtmlFacturacion(codigo)}" ${checked ? 'checked' : ''}>
      <label class="btn btn-outline-primary" for="${inputId}">
        <i class="ri-file-list-3-line me-1 align-middle d-inline-block"></i>${escapeHtmlFacturacion(texto)}
      </label>
    `);
  });
}

function renderSeriesFacturaFacturacion() {
  const $select = $('#factura_serie');
  const idTipo = obtenerIdTipoDocumentoCreacionFacturacion();
  const series = (catalogoCreacionFacturacion.series || []).filter(function (serie) {
    return String(serie.idsunat_c01_tipo_comprobante || '').trim() === idTipo;
  });
  $select.empty();

  series.forEach(function (serie) {
    const texto = `${serie.serie} (sig: ${serie.siguiente})`;
    $select.append(new Option(texto, serie.idserie_comprobante, false, false));
  });

  const predeterminada = series.find(function (serie) {
    return String(serie.predeterminado || '0') === '1';
  }) || series[0];

  $select.val(predeterminada ? String(predeterminada.idserie_comprobante) : '').trigger('change.select2');
}

function limpiarFormularioFacturaFacturacion() {
  comprobanteEditandoFacturacion = null;
  $('#form-factura-facturacion')[0]?.reset();
  $('input[name="factura_tipo_documento_radio"]').prop('disabled', false);
  $('#factura_serie').prop('disabled', false);
  $('#factura_fecha_emision').prop('readonly', true);
  $('#factura_cliente').val(null).trigger('change');
  $('#buscar_producto_directo_factura').val('');
  ocultarResultadosBuscadorProductoFacturacion();
  $('#factura_fecha_emision').val(formatearFechaInputFacturacion(new Date()));
  $('#factura_igv_label').val(`${Number(catalogoCreacionFacturacion.igv || 0).toFixed(2)}%`);
  $('#factura_label_igv').text(`IGV ${Number(catalogoCreacionFacturacion.igv || 0).toFixed(2)}% :`);
  const tipoInicial = String((catalogoCreacionFacturacion.tipos_documento || [])[0]?.id || '');
  $(`input[name="factura_tipo_documento_radio"][value="${tipoInicial}"]`).prop('checked', true);
  actualizarFormularioPorTipoDocumentoFacturacion(false);
  $('#factura_detalle_body').empty();
  calcularTotalesFacturaFacturacion();
  resetMetodosPagoFacturacion();
  facturaArchivosNuevos = [];
  facturaArchivosExistentes = [];
  facturaArchivosEliminar = [];
  facturaArchivosConfiguracion = {};
  $('#cc_pago_documentos').val('');
  renderArchivosFacturacion();
  limpiarValidacionFormularioFacturaFacturacion();
}

function abrirEdicionComprobanteFacturacion(row) {
  if (!puedeEditarDocumentoFacturacion(row)) {
    mostrarErrorFacturacion('El comprobante no se puede editar en su estado actual.');
    return;
  }

  cargarDetalleComprobanteFacturacion(row?.idrdocumento, function (detalle) {
    cargarCatalogosCreacionFacturacion(function () {
    limpiarFormularioFacturaFacturacion();
    comprobanteEditandoFacturacion = detalle;

    const idTipo = String(detalle?.idsunat_c01 || '').trim();
    asegurarTipoDocumentoEdicionFacturacion(detalle);
    $(`input[name="factura_tipo_documento_radio"][value="${idTipo}"]`).prop('checked', true);
    actualizarFormularioPorTipoDocumentoFacturacion(false);

    let serie = (catalogoCreacionFacturacion.series || []).find(function (item) {
      return String(item.idsunat_c01_tipo_comprobante || '').trim() === idTipo
        && String(item.serie || '').trim() === String(detalle?.serie_comprobante || '').trim();
    });
    if (!serie && Number(detalle?.idserie_comprobante_edicion || 0) > 0) {
      serie = {
        idserie_comprobante: Number(detalle.idserie_comprobante_edicion),
        idsunat_c01_tipo_comprobante: Number(detalle.idsunat_c01),
        serie: String(detalle.serie_comprobante || ''),
        siguiente: Number(detalle.numero_comprobante || 0),
      };
      $('#factura_serie').append(new Option(serie.serie, serie.idserie_comprobante, false, false));
    }
    if (!serie) {
      mostrarErrorFacturacion('No se pudo identificar la serie original del comprobante.');
      return;
    }

    const clienteId = String(detalle?.idpersona_cliente || '').trim();
    const clienteTexto = String(detalle?.cliente_descripcion || detalle?.cliente_nombre || 'Cliente').trim();
    $('#factura_cliente')
      .append(new Option(clienteTexto, clienteId, true, true))
      .trigger('change.select2');
    $('#factura_serie').val(String(serie.idserie_comprobante)).trigger('change.select2');
    $('#factura_fecha_emision').val(String(detalle?.fecha_emision || '').slice(0, 10));
    aplicarBloqueoCamposLegalesFacturacion(detalle);
    $('#factura_observacion').val(detalle?.observacion_documento || '');
    $('#factura_detalle_body').empty();
    (detalle?.detalles || []).forEach(function (item) {
      const producto = item?.producto || {};
      agregarItemFacturaFacturacion({
        idproducto: item?.idproducto,
        descripcion: item?.descripcion,
        codigo: producto?.codigo,
        cantidad: item?.cantidad,
        precio_venta: item?.precio_venta,
        descuento: item?.descuento,
        descuento_porcentaje: item?.descuento_porcentaje,
      });
    });
    calcularTotalesFacturaFacturacion();
    cargarMetodosPagoEdicionFacturacion(detalle?.metodos_pago || []);
    facturaArchivosExistentes = Array.isArray(detalle?.archivos) ? detalle.archivos : [];
    facturaArchivosEliminar = [];
    renderArchivosFacturacion();
    $('#titulo-formulario-facturacion').text(`Editar ${obtenerTipoDocumentoFacturacion(detalle).toLowerCase()} ${detalle?.comprobante || ''}`.trim());
    mostrarVistaFacturacion('formulario');
    });
  });
}

function asegurarTipoDocumentoEdicionFacturacion(detalle) {
  const idTipo = String(detalle?.idsunat_c01 || '').trim();
  if (!idTipo || $(`input[name="factura_tipo_documento_radio"][value="${idTipo}"]`).length) return;
  const codigo = String(detalle?.tipo_documento_codigo || '').trim();
  const texto = String(detalle?.tipo_documento_abreviatura || detalle?.tipo_documento_nombre || codigo).trim();
  const inputId = `factura_tipo_documento_${idTipo}`;
  $('#facturacion_tipo_documento_group').append(`
    <input type="radio" class="btn-check" name="factura_tipo_documento_radio" id="${inputId}" value="${escapeHtmlFacturacion(idTipo)}" data-codigo="${escapeHtmlFacturacion(codigo)}">
    <label class="btn btn-outline-primary" for="${inputId}">
      <i class="ri-file-list-3-line me-1 align-middle d-inline-block"></i>${escapeHtmlFacturacion(texto)}
    </label>
  `);
}

function aplicarBloqueoCamposLegalesFacturacion(detalle) {
  const codigo = String(detalle?.tipo_documento_codigo || '').trim();
  const esLegal = ['01', '03', '07', '08'].includes(codigo);
  $('input[name="factura_tipo_documento_radio"]').prop('disabled', esLegal);
  $('#factura_serie').prop('disabled', esLegal).trigger('change.select2');
  $('#factura_fecha_emision').prop('readonly', esLegal);
}

function actualizarFormularioPorTipoDocumentoFacturacion(limpiarCliente = true) {
  const tipo = obtenerTipoDocumentoCreacionFacturacion();
  const idTipo = obtenerIdTipoDocumentoCreacionFacturacion();
  const tipoInfo = (catalogoCreacionFacturacion.tipos_documento || []).find(function (item) {
    return String(item.id || '') === idTipo;
  });
  const titulo = tipo === '12' ? 'Nueva nota de venta' : (tipo === '03' ? 'Nueva boleta' : 'Nueva factura');

  $('#titulo-formulario-facturacion').text(titulo);
  $('#factura_cliente_label').html('Cliente <sup class="text-danger">*</sup>');
  if (limpiarCliente) {
    $('#factura_cliente').val(null).trigger('change');
  }
  renderSeriesFacturaFacturacion();
}

function obtenerTipoDocumentoCreacionFacturacion() {
  const $radio = $('input[name="factura_tipo_documento_radio"]:checked');
  const codigo = String($radio.data('codigo') || '').trim();
  if (codigo) return codigo;

  const idTipo = obtenerIdTipoDocumentoCreacionFacturacion();
  const tipoInfo = (catalogoCreacionFacturacion.tipos_documento || []).find(function (item) {
    return String(item.id || '') === idTipo;
  });

  return String(tipoInfo?.codigo || '01').trim();
}

function obtenerIdTipoDocumentoCreacionFacturacion() {
  const seleccionado = String($('input[name="factura_tipo_documento_radio"]:checked').val() || '').trim();
  if (seleccionado) return seleccionado;

  return String((catalogoCreacionFacturacion.tipos_documento || [])[0]?.id || '').trim();
}

function agregarItemFacturaFacturacion(item = {}) {
  const idproducto = Number(item.idproducto || 0);
  const descripcion = String(item.descripcion || item.texto || '').trim();
  const codigo = String(item.codigo || '').trim();
  const cantidad = Number(item.cantidad || 1);
  const precio = Number(item.precio_venta || 0);
  const descuento = Number(item.descuento || 0);
  const descuentoPorcentaje = Number(item.descuento_porcentaje || 0);
  const $itemExistente = $('#factura_detalle_body .facturacion-item-row').filter(function () {
    return Number($(this).data('idproducto') || 0) === idproducto;
  }).first();

  if ($itemExistente.length) {
    const $cantidad = $itemExistente.find('.facturacion-item-cantidad');
    const cantidadActual = Number($cantidad.val() || 0);
    $cantidad.val(redondearFacturacion(cantidadActual + cantidad));
    calcularTotalesFacturaFacturacion();
    return;
  }

  $('#factura_detalle_body').append(`
    <tr class="facturacion-item-row" data-idproducto="${idproducto}">
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-icon btn-light btn-quitar-item-factura" title="Quitar">
          <i class="ri-delete-bin-line"></i>
        </button>
      </td>
      <td>
        <div class="facturacion-item-descripcion-wrap">
          ${codigo ? `<span class="badge bg-light text-dark me-1">${escapeHtmlFacturacion(codigo)}</span>` : ''}
          <span class="fw-semibold text-wrap facturacion-item-descripcion-texto" role="button" title="Doble clic para editar">${escapeHtmlFacturacion(descripcion || 'Sin descripcion')}</span>
          <textarea class="form-control form-control-sm d-none mt-1 facturacion-item-descripcion" rows="2" maxlength="250">${escapeHtmlFacturacion(descripcion)}</textarea>
        </div>
      </td>
      <td>
        <input type="number" class="form-control form-control-sm w-m-100px facturacion-item-cantidad" value="${cantidad}" min="0.01" step="0.01">
      </td>
      <td>
        <input type="number" class="form-control form-control-sm w-m-100px facturacion-item-precio" value="${precio.toFixed(2)}" min="0.01" step="0.01">
      </td>
      <td>
        <input type="number" class="form-control form-control-sm w-m-100px facturacion-item-descuento-porcentaje" value="${descuentoPorcentaje.toFixed(2)}" min="0" max="100" step="0.01">
      </td>
      <td>
        <input type="number" class="form-control form-control-sm w-m-100px facturacion-item-descuento" value="${descuento.toFixed(2)}" min="0" step="0.01">
      </td>
      <td class="text-end fw-semibold facturacion-item-subtotal">S/ 0.00</td>
    </tr>
  `);

  calcularTotalesFacturaFacturacion();
}

function leerItemsFacturaFacturacion() {
  return $('#factura_detalle_body .facturacion-item-row').map(function () {
    const $row = $(this);
    return {
      idproducto: Number($row.data('idproducto') || 0),
      descripcion: String($row.find('.facturacion-item-descripcion').val() || '').trim(),
      cantidad: Number($row.find('.facturacion-item-cantidad').val() || 0),
      precio_venta: Number($row.find('.facturacion-item-precio').val() || 0),
      descuento: Number($row.find('.facturacion-item-descuento').val() || 0),
      descuento_porcentaje: Number($row.find('.facturacion-item-descuento-porcentaje').val() || 0),
    };
  }).get();
}

function calcularTotalesFacturaFacturacion() {
  const igvPorcentaje = Number(catalogoCreacionFacturacion.igv || 0);
  let subtotal = 0;
  let descuento = 0;

  $('#factura_detalle_body .facturacion-item-row').each(function () {
    const $row = $(this);
    const cantidad = Number($row.find('.facturacion-item-cantidad').val() || 0);
    const precio = Number($row.find('.facturacion-item-precio').val() || 0);
    let descuentoItem = Math.max(Number($row.find('.facturacion-item-descuento').val() || 0), 0);
    const descuentoPorcentaje = Math.max(Number($row.find('.facturacion-item-descuento-porcentaje').val() || 0), 0);
    const bruto = Math.max(cantidad * precio, 0);
    if (descuentoItem <= 0 && descuentoPorcentaje > 0) {
      descuentoItem = redondearFacturacion(bruto * (Math.min(descuentoPorcentaje, 100) / 100));
      $row.find('.facturacion-item-descuento').val(descuentoItem.toFixed(2));
    }
    const descuentoFinal = Math.min(descuentoItem, bruto);
    const subtotalItem = Math.max(bruto - descuentoFinal, 0);

    subtotal += subtotalItem;
    descuento += descuentoFinal;
    $row.find('.facturacion-item-subtotal').text(renderMonedaTextoFacturacion(subtotalItem));
  });

  subtotal = redondearFacturacion(subtotal);
  descuento = redondearFacturacion(descuento);
  const igv = redondearFacturacion(subtotal * (igvPorcentaje / 100));
  const total = redondearFacturacion(subtotal + igv);

  $('#factura_total_subtotal').text(renderMonedaTextoFacturacion(subtotal));
  $('#factura_total_descuento').text(renderMonedaTextoFacturacion(descuento));
  $('#factura_total_igv').text(renderMonedaTextoFacturacion(igv));
  $('#factura_total_general').text(renderMonedaTextoFacturacion(total));
  $('#factura_total_items').text($('#factura_detalle_body .facturacion-item-row').length);
  actualizarMensajeDetalleFacturaFacturacion();
  sincronizarMontoMetodoUnicoFacturacion();
  revalidarDetalleFacturaFacturacion();
}

function actualizarMensajeDetalleFacturaFacturacion() {
  const $body = $('#factura_detalle_body');
  const tieneProductos = $body.find('.facturacion-item-row').length > 0;
  let $mensaje = $body.find('.facturacion-detalle-vacio');

  if (!$mensaje.length) {
    $mensaje = $(`
      <tr class="facturacion-detalle-vacio">
        <td colspan="7" class="text-center text-muted py-4">
          No hay productos seleccionados.
        </td>
      </tr>
    `);
    $body.append($mensaje);
  }

  $mensaje.toggle(!tieneProductos);
}

function resetMetodosPagoFacturacion() {
  $('#cc_pago_metodos_body').empty();
  agregarFilaMetodoPagoFacturacion(extraerTotalFacturaFacturacion());
}

function cargarMetodosPagoEdicionFacturacion(metodos) {
  $('#cc_pago_metodos_body').empty();
  (Array.isArray(metodos) ? metodos : []).forEach(function (metodo) {
    agregarFilaMetodoPagoFacturacion(Number(metodo?.monto || 0), metodo);
  });
  if (!$('#cc_pago_metodos_body .js-cc-metodo-row').length) {
    agregarFilaMetodoPagoFacturacion(extraerTotalFacturaFacturacion());
  }
  sincronizarMetodosPagoFacturacion();
}

function agregarFilaMetodoPagoFacturacion(montoSugerido = 0, metodo = {}) {
  const idx = `${Date.now()}_${Math.floor(Math.random() * 1000)}`;
  const options = (catalogoCreacionFacturacion.cuentas_bancarias || []).map(function (cuenta) {
    const id = Number(cuenta?.idcuenta_bancaria || 0);
    return `<option value="${id > 0 ? id : ''}">${escapeHtmlFacturacion(cuenta?.text || cuenta?.nombre || '-')}</option>`;
  }).join('');

  $('#cc_pago_metodos_body .js-cc-metodo-empty-row').remove();
  $('#cc_pago_metodos_body').append(`
    <tr class="js-cc-metodo-row">
      <td>
        <select class="form-control form-control-sm js-cc-metodo-cuenta" name="cc_pago_metodos[${idx}][idcuenta_bancaria]" required data-msg-required="Seleccione cuenta bancaria / metodo.">
          <option value="">Seleccione</option>
          ${options}
        </select>
      </td>
      <td>
        <input type="number" class="form-control form-control-sm js-cc-metodo-monto" name="cc_pago_metodos[${idx}][monto]" min="0.01" step="0.01" value="${Number(montoSugerido || 0).toFixed(2)}" required>
      </td>
      <td>
        <input type="text" class="form-control form-control-sm js-cc-metodo-voucher" name="cc_pago_metodos[${idx}][codigo_voucher]" maxlength="60" placeholder="Codigo voucher" value="${escapeHtmlFacturacion(metodo?.codigo_voucher || '')}">
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger js-cc-eliminar-metodo" title="Quitar">
          <i class="ri-delete-bin-line"></i>
        </button>
      </td>
    </tr>
  `);

  const $row = $('#cc_pago_metodos_body .js-cc-metodo-row').last();
  if (metodo?.idcuenta_bancaria) {
    $row.find('.js-cc-metodo-cuenta').val(String(metodo.idcuenta_bancaria));
  }
  sincronizarMetodosPagoFacturacion();
}

function sincronizarMetodosPagoFacturacion() {
  const $body = $('#cc_pago_metodos_body');
  if (!$body.find('.js-cc-metodo-row').length) {
    $body.html('<tr class="js-cc-metodo-empty-row"><td colspan="4" class="text-center text-muted py-3">Ningun metodo de pago agregado.</td></tr>');
    return;
  }
  sincronizarMontoMetodoUnicoFacturacion();
}

function sincronizarMontoMetodoUnicoFacturacion() {
  const $rows = $('#cc_pago_metodos_body .js-cc-metodo-row');
  if ($rows.length !== 1) return;
  $rows.find('.js-cc-metodo-monto').val(extraerTotalFacturaFacturacion().toFixed(2));
}

function extraerTotalFacturaFacturacion() {
  const texto = String($('#factura_total_general').text() || '0').replace(/[^\d.,-]/g, '').replace(/,/g, '');
  const total = Number(texto);
  return Number.isFinite(total) ? redondearFacturacion(total) : 0;
}

function calcularBalanceMetodosPagoFacturacion() {
  const totalEsperado = extraerTotalFacturaFacturacion();
  const totalMetodos = redondearFacturacion(
    $('#cc_pago_metodos_body .js-cc-metodo-monto').toArray().reduce(function (acumulado, input) {
      const monto = Number($(input).val() || 0);
      return acumulado + (Number.isFinite(monto) ? monto : 0);
    }, 0)
  );
  return {
    totalEsperado,
    totalMetodos,
    diferencia: redondearFacturacion(totalEsperado - totalMetodos),
  };
}

function leerMetodosPagoFacturacion() {
  return $('#cc_pago_metodos_body .js-cc-metodo-row').map(function () {
    const $row = $(this);
    return {
      idcuenta_bancaria: Number($row.find('.js-cc-metodo-cuenta').val() || 0),
      monto: Number($row.find('.js-cc-metodo-monto').val() || 0),
      codigo_voucher: String($row.find('.js-cc-metodo-voucher').val() || '').trim(),
    };
  }).get();
}

function validarMetodosPagoFacturacion() {
  const metodos = leerMetodosPagoFacturacion();
  if (!metodos.length) return 'Agregue al menos un metodo de pago.';
  for (const metodo of metodos) {
    if (!Number.isFinite(metodo.idcuenta_bancaria) || metodo.idcuenta_bancaria <= 0) return 'Seleccione una cuenta bancaria / metodo en cada fila.';
    if (!Number.isFinite(metodo.monto) || metodo.monto <= 0) return 'Cada metodo de pago requiere un monto mayor a cero.';
  }
  if (Math.abs(calcularBalanceMetodosPagoFacturacion().diferencia) > 0.01) {
    return 'La suma de los metodos de pago debe coincidir con el total del comprobante.';
  }
  return null;
}

function agregarArchivosNuevosFacturacion(fileList) {
  const existentes = new Set(facturaArchivosNuevos.map(obtenerClaveArchivoFacturacion));
  Array.from(fileList || []).forEach(function (file) {
    const key = obtenerClaveArchivoFacturacion(file);
    if (!key || existentes.has(key)) return;
    existentes.add(key);
    facturaArchivosNuevos.push(file);
  });
}

function obtenerClaveArchivoFacturacion(file) {
  return file ? `${file.name}__${file.size}__${file.lastModified}` : '';
}

function obtenerNombreBaseArchivoFacturacion(nombre) {
  const texto = String(nombre || '').trim();
  const indice = texto.lastIndexOf('.');
  return indice > 0 ? texto.substring(0, indice) : (texto || 'Documento');
}

function renderArchivosFacturacion() {
  const $body = $('#cc-pago-archivos-body');
  $body.empty();
  const existentes = facturaArchivosExistentes.filter(function (archivo) {
    return !facturaArchivosEliminar.includes(Number(archivo?.idrdocumento_archivo || 0));
  });

  if (!existentes.length && !facturaArchivosNuevos.length) {
    $body.append('<tr class="js-cc-archivo-empty-row"><td colspan="3" class="text-center text-muted py-3">Ningun documento adjunto.</td></tr>');
    return;
  }

  existentes.forEach(function (archivo) {
    $body.append(`
      <tr>
        <td><span class="fw-semibold">${escapeHtmlFacturacion(archivo?.nombre_visible || archivo?.nombre_original || 'Documento')}</span> <span class="badge bg-light text-dark ms-1">Guardado</span></td>
        <td class="text-end">${formatearTamanoBytesFacturacion(archivo?.peso_bytes || 0)}</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger js-cc-eliminar-archivo-existente" data-idrdocumento-archivo="${Number(archivo?.idrdocumento_archivo || 0)}" title="Quitar">
            <i class="ri-delete-bin-line"></i>
          </button>
        </td>
      </tr>
    `);
  });

  facturaArchivosNuevos.forEach(function (file, index) {
    const key = obtenerClaveArchivoFacturacion(file);
    const nombreBase = obtenerNombreBaseArchivoFacturacion(file.name);
    const config = facturaArchivosConfiguracion[key] || { modo: 'archivo', nombreVisible: nombreBase };
    facturaArchivosConfiguracion[key] = config;
    const otroNombre = config.modo === 'otro';
    $body.append(`
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtmlFacturacion(file.name)} <span class="badge bg-info ms-1">Nuevo</span></div>
          <div class="mt-2">
            <div class="form-check form-check-inline mb-0">
              <input class="form-check-input js-cc-archivo-modo-nombre" type="radio" id="factura_archivo_${index}_archivo" name="factura_archivo_${index}" value="archivo" data-file-key="${escapeHtmlFacturacion(key)}" ${otroNombre ? '' : 'checked'}>
              <label class="form-check-label fs-12" for="factura_archivo_${index}_archivo">Usar nombre de archivo</label>
            </div>
            <div class="form-check form-check-inline mb-0">
              <input class="form-check-input js-cc-archivo-modo-nombre" type="radio" id="factura_archivo_${index}_otro" name="factura_archivo_${index}" value="otro" data-file-key="${escapeHtmlFacturacion(key)}" ${otroNombre ? 'checked' : ''}>
              <label class="form-check-label fs-12" for="factura_archivo_${index}_otro">Usar otro nombre</label>
            </div>
            <input type="text" class="form-control form-control-sm mt-2 js-cc-archivo-nombre-visible ${otroNombre ? '' : 'd-none'}" data-file-key="${escapeHtmlFacturacion(key)}" data-base-name="${escapeHtmlFacturacion(nombreBase)}" maxlength="190" placeholder="Escribe el nombre visible" value="${escapeHtmlFacturacion(config.nombreVisible || nombreBase)}">
          </div>
        </td>
        <td class="text-end">${formatearTamanoBytesFacturacion(file.size)}</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger js-cc-eliminar-archivo-nuevo" data-file-key="${escapeHtmlFacturacion(key)}" title="Quitar">
            <i class="ri-delete-bin-line"></i>
          </button>
        </td>
      </tr>
    `);
  });
}

function leerNombresArchivosFacturacion() {
  return facturaArchivosNuevos.map(function (file) {
    const key = obtenerClaveArchivoFacturacion(file);
    const config = facturaArchivosConfiguracion[key] || {};
    const modo = config.modo === 'otro' ? 'otro' : 'archivo';
    return {
      modo,
      nombre_visible: modo === 'otro' ? String(config.nombreVisible || '').trim() : obtenerNombreBaseArchivoFacturacion(file.name),
    };
  });
}

function validarArchivosFacturacion() {
  const nombres = leerNombresArchivosFacturacion();
  for (let i = 0; i < nombres.length; i += 1) {
    if (nombres[i].modo !== 'otro' || nombres[i].nombre_visible) continue;
    $('#cc-pago-archivos-body .js-cc-archivo-nombre-visible').eq(i).removeClass('d-none').addClass('is-invalid').trigger('focus');
    return `El archivo ${i + 1} requiere un nombre visible.`;
  }
  return null;
}

function formatearTamanoBytesFacturacion(bytes) {
  const valor = Number(bytes || 0);
  if (valor < 1024) return `${valor} B`;
  if (valor < 1024 * 1024) return `${(valor / 1024).toFixed(1)} KB`;
  return `${(valor / (1024 * 1024)).toFixed(1)} MB`;
}

function validarBloquesFacturaFacturacion(items = leerItemsFacturaFacturacion()) {
  return validarDetalleFacturaFacturacion(items)
    || validarMetodosPagoFacturacion()
    || validarArchivosFacturacion();
}

function guardarFacturaFacturacion() {
  const items = leerItemsFacturaFacturacion();
  const error = validarPayloadFacturaFacturacion(items);
  if (error) {
    mostrarErrorFacturacion(error);
    return;
  }

  const formData = new FormData();
  if (comprobanteEditandoFacturacion) formData.append('_method', 'PUT');
  formData.append('idsunat_c01_tipo_comprobante', obtenerIdTipoDocumentoCreacionFacturacion());
  formData.append('idpersona_cliente', $('#factura_cliente').val() || '');
  formData.append('idserie_comprobante', $('#factura_serie').val() || '');
  formData.append('fecha_emision', $('#factura_fecha_emision').val() || '');
  formData.append('observacion_documento', $('#factura_observacion').val() || '');
  formData.append('detalles', JSON.stringify(items));
  formData.append('metodos_pago', JSON.stringify(leerMetodosPagoFacturacion()));
  formData.append('documentos_nombres', JSON.stringify(leerNombresArchivosFacturacion()));
  formData.append('documentos_eliminar', JSON.stringify(facturaArchivosEliminar));
  facturaArchivosNuevos.forEach(function (file) {
    formData.append('documentos[]', file);
  });
  const mostrarImpresion = $('#factura_imprimir_automatico').is(':checked');
  const editando = Boolean(comprobanteEditandoFacturacion);

  $.ajax({
    url: apiUrl(comprobanteEditandoFacturacion
      ? `/facturacion/${comprobanteEditandoFacturacion.idrdocumento}/comprobante`
      : '/facturacion/factura'),
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    processData: false,
    contentType: false,
    beforeSend: function () {
      $('.btn-guardar-factura').prop('disabled', true).html('<i class="fas fa-spinner fa-pulse"></i> Guardando...');
    },
    success: function (response) {
      if (!response?.status) {
        mostrarErrorFacturacion(response?.message || 'No se pudo registrar la factura.');
        return;
      }

      mostrarOkFacturacion(response?.message || (comprobanteEditandoFacturacion
        ? 'Comprobante actualizado correctamente.'
        : 'Comprobante registrado correctamente.'));
      const documentoGuardado = response?.data || {};
      const idDocumentoGuardado = Number(documentoGuardado?.idrdocumento || comprobanteEditandoFacturacion?.idrdocumento || 0);
      comprobanteEditandoFacturacion = null;
      mostrarVistaFacturacion('tabla');
      tablaFacturacion?.ajax.reload(null, false);
      cargarFiltrosFacturacion();
      if (mostrarImpresion && idDocumentoGuardado > 0) {
        abrirModalImpresionFacturacion(idDocumentoGuardado, documentoGuardado, editando);
      }
    },
    error: function (xhr) {
      mostrarErrorFacturacion(extraerPrimerErrorFacturacion(xhr) || xhr?.responseJSON?.message || 'Error al registrar factura.');
    },
    complete: function () {
      $('.btn-guardar-factura').prop('disabled', false).html('<i class="ti ti-device-floppy"></i> Guardar comprobante');
    },
  });
}

function abrirModalImpresionFacturacion(idDocumento, documento = {}, editando = false) {
  const id = Number(idDocumento || 0);
  if (!Number.isFinite(id) || id <= 0) {
    mostrarErrorFacturacion('No se pudo identificar el comprobante para imprimir.');
    return;
  }

  const urls = {
    a4: apiUrl(`/facturacion/${id}/impresion/a4`),
    ticket: apiUrl(`/facturacion/${id}/impresion/ticket`),
  };
  const comprobante = String(documento?.serie_comprobante || '').trim() && String(documento?.numero_comprobante || '').trim()
    ? `${documento.serie_comprobante}-${documento.numero_comprobante}`
    : String(documento?.comprobante || '').trim();
  const tipo = String(documento?.tipo_comprobante || documento?.tipo_documento_codigo || '').trim();

  $('#modal-impresion-facturacion-subtitle').text([
    editando ? 'Comprobante actualizado' : 'Comprobante registrado',
    [tipo, comprobante].filter(Boolean).join(' '),
  ].filter(Boolean).join(' - '));
  $('#facturacion-print-a4-frame').attr('src', urls.a4);
  $('#facturacion-print-ticket-frame').attr('src', urls.ticket);
  $('.js-abrir-formato-facturacion[data-formato="a4"]').attr('href', urls.a4);
  $('.js-abrir-formato-facturacion[data-formato="ticket"]').attr('href', urls.ticket);
  $('#facturacion-print-a4-tab').trigger('click');

  const modal = document.getElementById('modal-impresion-facturacion');
  if (modal && typeof bootstrap !== 'undefined') {
    bootstrap.Modal.getOrCreateInstance(modal).show();
    return;
  }

  $('#modal-impresion-facturacion').modal('show');
}

function imprimirFormatoFacturacion(formato) {
  const frameId = formato === 'ticket' ? 'facturacion-print-ticket-frame' : 'facturacion-print-a4-frame';
  const frame = document.getElementById(frameId);
  const frameWindow = frame?.contentWindow;
  if (!frameWindow) {
    mostrarErrorFacturacion('No se pudo cargar el formato de impresion.');
    return;
  }

  frameWindow.focus();
  frameWindow.print();
}

function validarPayloadFacturaFacturacion(items) {
  const tipo = obtenerTipoDocumentoCreacionFacturacion();
  if (!$('#factura_cliente').val()) return tipo === '01' ? 'Seleccione el cliente RUC.' : 'Seleccione el cliente.';
  if (!$('#factura_serie').val()) return 'Seleccione una serie.';
  return validarBloquesFacturaFacturacion(items);
}

function validarDetalleFacturaFacturacion(items = leerItemsFacturaFacturacion()) {
  if (!items.length) return 'Debe agregar al menos un item.';

  for (const item of items) {
    if (!Number.isFinite(item.idproducto) || item.idproducto <= 0) return 'Cada item requiere producto.';
    if (!item.descripcion) return 'Cada item requiere producto.';
    if (!Number.isFinite(item.cantidad) || item.cantidad <= 0) return 'Cada item requiere cantidad mayor a cero.';
    if (!Number.isFinite(item.precio_venta) || item.precio_venta <= 0) return 'Cada item requiere precio mayor a cero.';
    if (!Number.isFinite(item.descuento) || item.descuento < 0) return 'El descuento no puede ser negativo.';
    if (!Number.isFinite(item.descuento_porcentaje) || item.descuento_porcentaje < 0 || item.descuento_porcentaje > 100) return 'El descuento porcentual debe estar entre 0 y 100.';
  }

  return null;
}

function inicializarTablaFacturacion() {
  tablaFacturacion = $('#tabla-facturacion').DataTable({
    responsive: false,
    processing: true,
    serverSide: true,
    paging: true,
    ordering: true,
    searching: true,
    deferRender: true,
    searchDelay: 350,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
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
        exportOptions: { columns: [1, 2, 3, 4, 6] },
        title: 'Listado de facturacion',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/facturacion/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function (data) {
        const idCliente = Number($('#filtro_facturacion_cliente').val() || 0);
        const idsTipoDocumento = ($('#filtro_facturacion_tipo_documento').val() || [])
          .map(function (id) { return Number(id); })
          .filter(function (id) { return Number.isFinite(id) && id > 0; });
        const estados = ($('#filtro_facturacion_estado').val() || [])
          .map(function (estado) { return String(estado || '').trim(); })
          .filter(Boolean);

        data.incluir_trash = $('#incluir-eliminados-facturacion').is(':checked') ? 1 : 0;
        data.fecha_inicio = obtenerFechaInicioFacturacion();
        data.fecha_fin = obtenerFechaFinFacturacion();
        data.idpersona_cliente = Number.isFinite(idCliente) && idCliente > 0 ? idCliente : '';
        data.idsunat_c01 = idsTipoDocumento;
        data.sunat_estado = estados;
      },
      dataSrc: function (response) {
        if (response && response.status === false) {
          mostrarErrorFacturacion(response?.message || 'No se pudo listar facturacion.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorFacturacion('Error al consultar facturacion.');
      },
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const botonEditar = puedeEditarDocumentoFacturacion(row)
            ? `<button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-facturacion" data-id="${row.idrdocumento}" title="Editar comprobante">
                <i class="ri-edit-line"></i>
              </button>`
            : '';

          return `
            <div class="hstack gap-2 justify-content-center">
              ${botonEditar}
              ${renderMenuAccionesRegistroFacturacion(row)}
            </div>
          `;
        },
      },
      {
        data: 'fecha_emision',
        render: function (value, type) {
          if (type !== 'display') {
            return value || '';
          }

          return renderFechaFacturacion(value);
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          const descripcion = String(row?.cliente_descripcion || row?.cliente_nombre || '-').trim();
          const numeroDocumento = String(row?.cliente_documento || '').trim();
          const abreviaturaDoc = String(row?.cliente_documento_abreviatura || 'DOC').trim().toUpperCase();
          const sexo = String(row?.cliente_sexo || '').trim().toUpperCase();
          const fotoPerfil = String(row?.cliente_foto_perfil || '').trim();
          const fotoDefault = sexo === 'F' ? 'mujer.png' : 'hombre.png';
          const fotoNombre = fotoPerfil || fotoDefault;
          const fotoUrl = apiUrl(`/assets/modulo/persona/perfil/${fotoNombre}`);
          const fotoFallback = apiUrl(`/assets/modulo/persona/perfil/${fotoDefault}`);

          if (type !== 'display') {
            return `${descripcion} ${abreviaturaDoc}: ${numeroDocumento}`.trim();
          }

          const detalleDocumento = numeroDocumento
            ? `${abreviaturaDoc}: ${numeroDocumento}`
            : abreviaturaDoc;

          return `
            <div class="d-flex align-items-center">
              <div class="me-2 lh-1">
                <span class="avatar avatar-md avatar-rounded">
                  <img src="${fotoUrl}" alt="Cliente" onerror="this.src='${fotoFallback}';">
                </span>
              </div>
              <div>
                <p class="mb-0 fw-semibold">
                  <a href="javascript:void(0);" class="mb-0 fw-semibold text-nowrap js-open-detalle-comprobante-facturacion">${escapeHtmlFacturacion(descripcion)}</a>
                </p>
                <span class="fs-12 text-muted fw-normal">${escapeHtmlFacturacion(detalleDocumento)}</span>
              </div>
            </div>
          `;
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          const comprobante = String(row?.comprobante || '').trim();
          const abreviatura = String(row?.tipo_documento_abreviatura || '').trim();

          if (type !== 'display') {
            return `${abreviatura} ${comprobante}`.trim();
          }

          const badge = abreviatura
            ? `<span class="badge bg-primary me-2">${escapeHtmlFacturacion(abreviatura)}</span>`
            : '';

          return `
            <div class="text-nowrap">${badge}<a href="javascript:void(0);" class="fw-semibold js-open-detalle-comprobante-facturacion">${escapeHtmlFacturacion(comprobante)}</a></div>
          `;
        },
      },
      {
        data: 'venta_total',
        className: 'text-end',
        render: renderMonedaFacturacion,
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, type, row) {
          if (type !== 'display') {
            return String(row?.sunat_estado || '');
          }

          return renderAccionesSunatFacturacion(row);
        },
      },
      {
        data: 'sunat_estado',
        className: 'text-center',
        render: function (estado, type, row) {
          const estadoSunat = String(estado || '').trim();

          if (type !== 'display') {
            return estadoSunat;
          }

          return estadoSunat
            ? `<button type="button" class="btn p-0 border-0 bg-transparent js-open-sunat-detalle" data-id="${row.idrdocumento}" title="Ver detalle SUNAT">${renderBadgeEstadoSunatFacturacion(estadoSunat)}</button>`
            : '<span class="text-muted">-</span>';
        },
      },
    ],
    order: [[1, 'desc']],
    language: {
      lengthMenu: '_MENU_',
      sLoadingRecords: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...',
      search: '',
    },
  });

  $('#tabla-facturacion tbody').on('click', '.btn-editar-facturacion', function () {
    const row = tablaFacturacion.row($(this).closest('tr')).data();
    abrirEdicionComprobanteFacturacion(row);
  });

  $('#tabla-facturacion tbody').on('click', '.js-open-sunat-detalle', function () {
    const row = tablaFacturacion.row($(this).closest('tr')).data();
    abrirModalDetalleSunatFacturacion(row);
  });

  $('#tabla-facturacion tbody').on('click', '.js-open-detalle-comprobante-facturacion', function () {
    abrirDetalleComprobanteFacturacion(tablaFacturacion.row($(this).closest('tr')).data());
  });

  $('#tabla-facturacion tbody').on('click', '.btn-enviar-sunat-facturacion', function () {
    const $button = $(this);
    const row = tablaFacturacion.row($(this).closest('tr')).data();
    confirmarFacturacion(
      'Enviar a SUNAT',
      `Se enviara el comprobante ${obtenerTipoDocumentoFacturacion(row)} ${row?.comprobante || ''}.`,
      'Enviar',
      function () {
        enviarSunatFacturacion(row?.idrdocumento, $button);
      }
    );
  });

  $('#tabla-facturacion tbody').on('click', '.js-detalle-registro-facturacion', function () {
    abrirModalDetalleSunatFacturacion(tablaFacturacion.row($(this).closest('tr')).data());
  });

  $('#tabla-facturacion tbody').on('click', '.js-imprimir-registro-facturacion', function () {
    abrirImpresionDocumentoFacturacion(tablaFacturacion.row($(this).closest('tr')).data());
  });

  $('#tabla-facturacion tbody').on('click', '.js-anular-registro-facturacion', function () {
    abrirModalAnularSunatFacturacion(tablaFacturacion.row($(this).closest('tr')).data());
  });

  $('#tabla-facturacion tbody').on('click', '.js-desactivar-ticket-facturacion', function () {
    confirmarDesactivacionTicketFacturacion(tablaFacturacion.row($(this).closest('tr')).data());
  });

  $('#tabla-facturacion tbody').on('contextmenu', 'tr', function (event) {
    const row = tablaFacturacion.row(this).data();
    if (!row) return;

    event.preventDefault();
    abrirMenuContextualFacturacion(event, row);
  });
}

function renderMenuAccionesRegistroFacturacion(row) {
  const puedeAnular = puedeAnularSunatFacturacion(row);
  const esTicket = String(row?.tipo_documento_codigo || '').trim() === '12';
  const puedeDesactivarTicket = puedeDesactivarTicketFacturacion(row);
  const titulo = puedeAnular
    ? 'Anular con nota de credito'
    : 'Solo factura o boleta aceptada por SUNAT';
  const accionDocumento = esTicket
    ? `<li><a class="dropdown-item text-danger js-desactivar-ticket-facturacion ${puedeDesactivarTicket ? '' : 'disabled'}" href="javascript:void(0);" title="Desactivar ticket"><i class="ri-delete-bin-line"></i> Desactivar ticket</a></li>`
    : `<li><a class="dropdown-item text-danger js-anular-registro-facturacion ${puedeAnular ? '' : 'disabled'}" href="javascript:void(0);" title="${escapeHtmlFacturacion(titulo)}"><i class="ri-file-reduce-line"></i> Anular con nota de credito</a></li>`;

  return `
    <div class="btn-group">
      <button type="button" class="btn btn-info btn-sm dropdown-toggle py-1 px-1" data-bs-toggle="dropdown" aria-expanded="false" title="Acciones">
        <i class="ri-settings-4-line"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item js-imprimir-registro-facturacion" href="javascript:void(0);"><i class="ri-printer-line"></i> Imprimir</a></li>
        <li><a class="dropdown-item js-detalle-registro-facturacion" href="javascript:void(0);"><i class="bi bi-eye"></i> Ver detalle SUNAT</a></li>
        ${accionDocumento}
      </ul>
    </div>
  `;
}

function puedeAnularSunatFacturacion(row) {
  const codigo = String(row?.tipo_documento_codigo || '').trim();
  const estado = String(row?.sunat_estado || '').trim().toUpperCase();
  const tieneNotaCredito = [true, 1, '1'].includes(row?.tiene_nota_credito);
  return ['01', '03'].includes(codigo) && estado === 'ACEPTADA' && !tieneNotaCredito;
}

function puedeEditarNotaCreditoFacturacion(row) {
  const codigo = String(row?.tipo_documento_codigo || '').trim();
  const estado = String(row?.sunat_estado || '').trim().toUpperCase();
  const estadoTrash = String(row?.estado_trash ?? '1').trim();
  return codigo === '07' && estado !== 'ACEPTADA' && estadoTrash === '1';
}

function puedeEditarDocumentoFacturacion(row) {
  const codigo = String(row?.tipo_documento_codigo || '').trim();
  const estado = String(row?.sunat_estado || '').trim().toUpperCase();
  const estadoTrash = String(row?.estado_trash ?? '1').trim();
  if (codigo === '12') return estadoTrash === '1';
  return ['01', '03', '07', '08'].includes(codigo) && estado !== 'ACEPTADA' && estadoTrash === '1';
}

function puedeDesactivarTicketFacturacion(row) {
  const codigo = String(row?.tipo_documento_codigo || '').trim();
  const estadoTrash = String(row?.estado_trash ?? '1').trim();
  return codigo === '12' && estadoTrash === '1';
}

function abrirImpresionDocumentoFacturacion(row) {
  const idDocumento = Number(row?.idrdocumento || 0);
  if (!Number.isFinite(idDocumento) || idDocumento <= 0) {
    mostrarErrorFacturacion('No se pudo identificar el comprobante para imprimir.');
    return;
  }

  abrirModalImpresionFacturacion(idDocumento, row, false);
}

function abrirMenuContextualFacturacion(event, row) {
  documentoContextualFacturacion = row;
  const puedeAnular = puedeAnularSunatFacturacion(row);
  const puedeDesactivarTicket = puedeDesactivarTicketFacturacion(row);
  const esTicket = String(row?.tipo_documento_codigo || '').trim() === '12';
  const $menu = $('#menu-contextual');

  $('#menu-contextual-facturacion-titulo').text(`${obtenerTipoDocumentoFacturacion(row)} ${row?.comprobante || ''}`.trim());
  $('#opcion-facturacion-anular')
    .toggle(!esTicket)
    .toggleClass('disabled', !puedeAnular)
    .attr('aria-disabled', puedeAnular ? 'false' : 'true')
    .attr('title', puedeAnular ? '' : 'Solo disponible para factura o boleta aceptada por SUNAT.');
  $('#opcion-facturacion-desactivar-ticket')
    .toggle(esTicket)
    .toggleClass('disabled', !puedeDesactivarTicket)
    .attr('aria-disabled', puedeDesactivarTicket ? 'false' : 'true')
    .attr('title', puedeDesactivarTicket ? '' : 'El ticket ya se encuentra desactivado.');
  $menu.css({ display: 'block', left: event.pageX, top: event.pageY });

  const maxLeft = $(window).scrollLeft() + $(window).width() - $menu.outerWidth() - 8;
  const maxTop = $(window).scrollTop() + $(window).height() - $menu.outerHeight() - 8;
  $menu.css({
    left: Math.max(8, Math.min(event.pageX, maxLeft)),
    top: Math.max(8, Math.min(event.pageY, maxTop)),
  });
}

function ocultarMenuContextualFacturacion() {
  $('#menu-contextual').hide();
}

function confirmarDesactivacionTicketFacturacion(row) {
  if (!puedeDesactivarTicketFacturacion(row)) {
    mostrarErrorFacturacion('Solo se puede desactivar directamente un ticket activo (12).');
    return;
  }

  confirmarFacturacion(
    'Desactivar ticket',
    `Se desactivara el ticket ${row?.comprobante || ''}.`,
    'Si, desactivar',
    function () {
      desactivarTicketFacturacion(row?.idrdocumento);
    }
  );
}

function desactivarTicketFacturacion(idDocumento) {
  $.ajax({
    url: apiUrl(`/facturacion/${idDocumento}/desactivar-ticket`),
    type: 'POST',
    headers: ajaxHeaders(),
    success: function (response) {
      mostrarOkFacturacion(response?.message || 'Ticket desactivado correctamente.');
      tablaFacturacion?.ajax.reload(null, false);
    },
    error: function (xhr) {
      mostrarErrorFacturacion(extraerPrimerErrorFacturacion(xhr) || xhr?.responseJSON?.message || 'No se pudo desactivar el ticket.');
    },
  });
}

function abrirModalAnularSunatFacturacion(row) {
  if (!puedeAnularSunatFacturacion(row)) {
    mostrarErrorFacturacion('Solo se puede anular una factura o boleta aceptada por SUNAT.');
    return;
  }

  const plazoAnulacion = obtenerPlazoAnulacionNotaCreditoFacturacion(row?.fecha_emision);
  if (plazoAnulacion?.vencido) {
    mostrarAlertaPlazoAnulacionFacturacion(plazoAnulacion);
    return;
  }

  if (!Array.isArray(row?.detalle_documento)) {
    cargarDetalleComprobanteFacturacion(row?.idrdocumento, abrirModalAnularSunatFacturacion);
    return;
  }

  cargarCatalogosCreacionFacturacion(function () {
    notaCreditoEditandoFacturacion = null;
    const motivos = catalogoCreacionFacturacion.motivos_nota_credito || [];
    const codigoDocumentoAfectado = String(row?.tipo_documento_codigo || '').trim();
    const series = (catalogoCreacionFacturacion.series_nota_credito || []).filter(function (serie) {
      return String(serie.tipo_comprobante_adicional_codigo || '').trim() === codigoDocumentoAfectado;
    });
    const $motivo = $('#anular_facturacion_motivo');
    const $serie = $('#anular_facturacion_serie');

    cargarMotivosNotaCreditoSelectFacturacion($motivo, motivos, motivos[0]?.id);
    $serie.empty();
    series.forEach(function (serie) {
      $serie.append(new Option(serie.text, serie.id, false, false));
    });

    if (!motivos.length) {
      mostrarErrorFacturacion('No existen motivos SUNAT C09 activos para emitir la nota de credito.');
      return;
    }
    if (!series.length) {
      mostrarErrorFacturacion(`No tienes una serie activa de nota de credito (07) asignada para el comprobante ${codigoDocumentoAfectado}.`);
      return;
    }

    const seriePredeterminada = series.find(function (serie) {
      return String(serie.predeterminado || '0') === '1';
    }) || series[0];
    $('#anular_facturacion_iddocumento').val(row.idrdocumento);
    $('#anular_facturacion_observacion').val('');
    $serie.val(String(seriePredeterminada.id)).trigger('change.select2');
    $('#anular_facturacion_serie_group').show();
    $('#anular_facturacion_serie').prop('required', true);
    $('#modal-anular-sunat-facturacion-label').text('Registrar nota de credito');
    $('#btn-confirmar-anular-sunat-facturacion').html('<i class="ri-file-reduce-line me-1"></i> Registrar nota de credito');
    $('#modal-anular-sunat-facturacion-subtitle').text(`${obtenerTipoDocumentoFacturacion(row)} ${row?.comprobante || ''}`.trim());
    mostrarResumenAnulacionSunatFacturacion(row);

    const modal = document.getElementById('modal-anular-sunat-facturacion');
    bootstrap.Modal.getOrCreateInstance(modal).show();
  });
}

function obtenerPlazoAnulacionNotaCreditoFacturacion(fechaEmisionValor) {
  const fechaEmision = obtenerFechaLocalFacturacion(fechaEmisionValor);
  if (!fechaEmision) return null;

  const fechaLimite = new Date(fechaEmision.getFullYear(), fechaEmision.getMonth() + 1, 1);
  let diasHabiles = 0;
  while (diasHabiles < 15) {
    if (esDiaHabilSunatFacturacion(fechaLimite)) diasHabiles++;
    if (diasHabiles < 15) fechaLimite.setDate(fechaLimite.getDate() + 1);
  }

  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const milisegundosDia = 24 * 60 * 60 * 1000;
  return {
    fechaEmision,
    fechaLimite,
    diasTranscurridos: Math.max(0, Math.floor((hoy - fechaEmision) / milisegundosDia)),
    vencido: hoy > fechaLimite,
  };
}

function obtenerFechaLocalFacturacion(valor) {
  const partes = String(valor || '').slice(0, 10).split('-').map(Number);
  if (partes.length !== 3 || partes.some((parte) => !Number.isFinite(parte))) return null;
  const fecha = new Date(partes[0], partes[1] - 1, partes[2]);
  fecha.setHours(0, 0, 0, 0);
  return Number.isNaN(fecha.getTime()) ? null : fecha;
}

function esDiaHabilSunatFacturacion(fecha) {
  if ([0, 6].includes(fecha.getDay())) return false;

  const feriados = [
    '01-01', '05-01', '06-07', '06-29', '07-23', '07-28', '07-29',
    '08-06', '08-30', '10-08', '11-01', '12-08', '12-09', '12-25',
  ];
  const pascua = obtenerDomingoPascuaFacturacion(fecha.getFullYear());
  feriados.push(
    formatearMesDiaFacturacion(new Date(pascua.getFullYear(), pascua.getMonth(), pascua.getDate() - 3)),
    formatearMesDiaFacturacion(new Date(pascua.getFullYear(), pascua.getMonth(), pascua.getDate() - 2))
  );

  return !feriados.includes(formatearMesDiaFacturacion(fecha));
}

function obtenerDomingoPascuaFacturacion(anio) {
  const a = anio % 19;
  const b = Math.floor(anio / 100);
  const c = anio % 100;
  const d = Math.floor(b / 4);
  const e = b % 4;
  const f = Math.floor((b + 8) / 25);
  const g = Math.floor((b - f + 1) / 3);
  const h = (19 * a + b - d - g + 15) % 30;
  const i = Math.floor(c / 4);
  const k = c % 4;
  const l = (32 + 2 * e + 2 * i - h - k) % 7;
  const m = Math.floor((a + 11 * h + 22 * l) / 451);
  const mes = Math.floor((h + l - 7 * m + 114) / 31);
  const dia = ((h + l - 7 * m + 114) % 31) + 1;
  return new Date(anio, mes - 1, dia);
}

function formatearMesDiaFacturacion(fecha) {
  return `${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;
}

function formatearFechaCortaFacturacion(fecha) {
  return fecha.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function mostrarAlertaPlazoAnulacionFacturacion(plazo) {
  const mensaje = `El comprobante fue emitido el ${formatearFechaCortaFacturacion(plazo.fechaEmision)}. Han transcurrido ${plazo.diasTranscurridos} dias desde su emision. El plazo permitido por SUNAT es hasta el decimo quinto (15) dia habil del mes siguiente a la emision. Ya no se puede anular mediante nota de credito. Fecha limite: ${formatearFechaCortaFacturacion(plazo.fechaLimite)}.`;
  if (typeof Swal === 'undefined') {
    mostrarErrorFacturacion(mensaje);
    return;
  }

  Swal.fire({
    title: 'Plazo de anulacion vencido',
    html: `
      <div class="text-start">
        <p class="mb-1"><strong>Fecha del comprobante:</strong> ${escapeHtmlFacturacion(formatearFechaCortaFacturacion(plazo.fechaEmision))}</p>
        <p class="mb-1"><strong>Dias transcurridos:</strong> ${escapeHtmlFacturacion(plazo.diasTranscurridos)}</p>
        <p class="mb-1"><strong>Plazo permitido por SUNAT:</strong> Hasta el decimo quinto (15) dia habil del mes siguiente a la emision.</p>
        <p class="mb-2"><strong>Fecha limite SUNAT:</strong> ${escapeHtmlFacturacion(formatearFechaCortaFacturacion(plazo.fechaLimite))}</p>
        <div class="alert alert-danger mb-0">Ya paso el plazo permitido por SUNAT. El comprobante ya no se puede anular mediante nota de credito.</div>
      </div>
    `,
    icon: 'warning',
    confirmButtonText: 'Entendido',
  });
}

function abrirModalEditarNotaCreditoFacturacion(row) {
  if (!puedeEditarNotaCreditoFacturacion(row)) {
    mostrarErrorFacturacion('Solo se puede editar una nota de credito activa que aun no fue aceptada por SUNAT.');
    return;
  }

  if (!Array.isArray(row?.detalle_documento)) {
    cargarDetalleComprobanteFacturacion(row?.idrdocumento, abrirModalEditarNotaCreditoFacturacion);
    return;
  }

  cargarCatalogosCreacionFacturacion(function () {
    const motivos = catalogoCreacionFacturacion.motivos_nota_credito || [];
    const codigoDocumentoAfectado = String(row?.nc_tipo_comprobante || '').trim();
    const series = (catalogoCreacionFacturacion.series_nota_credito || []).filter(function (serie) {
      return String(serie.tipo_comprobante_adicional_codigo || '').trim() === codigoDocumentoAfectado;
    });
    if (!motivos.length) {
      mostrarErrorFacturacion('No existen motivos SUNAT C09 activos para editar la nota de credito.');
      return;
    }
    if (!series.length) {
      mostrarErrorFacturacion(`No tienes una serie activa de nota de credito (07) asignada para el comprobante ${codigoDocumentoAfectado}.`);
      return;
    }

    notaCreditoEditandoFacturacion = row;
    $('#anular_facturacion_iddocumento').val(row.idrdocumento);
    $('#anular_facturacion_observacion').val(row?.observacion_documento || '');
    cargarMotivosNotaCreditoSelectFacturacion($('#anular_facturacion_motivo'), motivos, row?.idsunat_c09);
    const $serie = $('#anular_facturacion_serie');
    $serie.empty();
    series.forEach(function (serie) {
      $serie.append(new Option(serie.text, serie.id, false, false));
    });
    const serieActual = series.find(function (serie) {
      return String(serie.serie || '').trim() === String(row?.serie_comprobante || '').trim();
    }) || series[0];
    $serie.val(String(serieActual.id)).trigger('change.select2').prop('required', true);
    $('#anular_facturacion_serie_group').show();
    $('#modal-anular-sunat-facturacion-label').text('Editar nota de credito');
    $('#modal-anular-sunat-facturacion-subtitle').text(`${obtenerTipoDocumentoFacturacion(row)} ${row?.comprobante || ''}`.trim());
    $('#btn-confirmar-anular-sunat-facturacion').html('<i class="ri-save-line me-1"></i> Guardar cambios');
    mostrarResumenAnulacionSunatFacturacion(row);

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-anular-sunat-facturacion')).show();
  });
}

function cargarMotivosNotaCreditoSelectFacturacion($select, motivos, seleccionado) {
  $select.empty().append(new Option('', '', false, false));
  motivos.forEach(function (motivo) {
    $select.append(new Option(motivo.text, motivo.id, false, false));
  });
  $select.val(String(seleccionado || '')).trigger('change.select2');
}

function mostrarResumenAnulacionSunatFacturacion(row) {
  const cliente = String(row?.cliente_descripcion || row?.cliente_nombre || 'Sin cliente').trim();
  const abreviaturaDocumento = String(row?.cliente_documento_abreviatura || 'DOC').trim().toUpperCase();
  const numeroDocumento = String(row?.cliente_documento || '').trim();
  const comprobante = `${obtenerTipoDocumentoFacturacion(row)} ${row?.comprobante || ''}`.trim();
  const productos = Array.isArray(row?.productos_asignados) ? row.productos_asignados : [];
  const detalles = Array.isArray(row?.detalle_documento) ? row.detalle_documento : [];
  const cantidadItems = Number(row?.items_count || detalles.length || productos.length || 0);
  const detalleProductos = detalles.length
    ? `<div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 bg-white">
          <thead>
            <tr>
              <th>Producto</th>
              <th class="text-end">Cantidad</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            ${detalles.map((detalle) => `
              <tr>
                <td>${escapeHtmlFacturacion(detalle?.descripcion || 'Item sin descripcion')}</td>
                <td class="text-end">${escapeHtmlFacturacion(detalle?.cantidad ?? '0')}</td>
                <td class="text-end">${renderMonedaTextoFacturacion(detalle?.subtotal)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>`
    : '<span class="text-muted">Sin detalle disponible.</span>';

  $('#anular_facturacion_cliente').text(cliente || 'Sin cliente');
  $('#anular_facturacion_cliente_documento').text(numeroDocumento ? `${abreviaturaDocumento}: ${numeroDocumento}` : abreviaturaDocumento);
  $('#anular_facturacion_comprobante').text(comprobante || '-');
  $('#anular_facturacion_total').text(renderMonedaTextoFacturacion(row?.venta_total));
  $('#anular_facturacion_detalle').html(`
    <div class="text-muted mb-1">${cantidadItems} item(s)</div>
    ${detalleProductos}
  `);
}

function enviarAnulacionSunatFacturacion(event) {
  event.preventDefault();
  const idDocumento = Number($('#anular_facturacion_iddocumento').val() || 0);
  const $button = $('#btn-confirmar-anular-sunat-facturacion');

  $.ajax({
    url: apiUrl(notaCreditoEditandoFacturacion
      ? `/facturacion/${idDocumento}/nota-credito`
      : `/facturacion/${idDocumento}/anular-sunat`),
    type: notaCreditoEditandoFacturacion ? 'PUT' : 'POST',
    headers: ajaxHeaders(),
    data: {
      idsunat_c09: $('#anular_facturacion_motivo').val(),
      idserie_comprobante: $('#anular_facturacion_serie').val(),
      observacion_documento: $('#anular_facturacion_observacion').val(),
    },
    beforeSend: function () {
      $button.prop('disabled', true);
    },
    success: function (response) {
      bootstrap.Modal.getInstance(document.getElementById('modal-anular-sunat-facturacion'))?.hide();
      mostrarOkFacturacion(response?.message || (notaCreditoEditandoFacturacion
        ? 'Nota de credito actualizada con estado POR ENVIAR.'
        : 'Nota de credito registrada con estado POR ENVIAR.'));
      notaCreditoEditandoFacturacion = null;
      tablaFacturacion?.ajax.reload(null, false);
    },
    error: function (xhr) {
      mostrarErrorFacturacion(extraerPrimerErrorFacturacion(xhr) || xhr?.responseJSON?.message || 'No se pudo registrar la nota de credito.');
      tablaFacturacion?.ajax.reload(null, false);
    },
    complete: function () {
      $button.prop('disabled', false);
    },
  });
}

function renderAccionesSunatFacturacion(row) {
  const id = Number(row?.idrdocumento || 0);
  const estadoSunat = String(row?.sunat_estado || '').trim().toUpperCase();
  const codigoDocumento = String(row?.tipo_documento_codigo || '').trim();
  const permiteAccionSunat = id > 0 && ['01', '03', '07', '08'].includes(codigoDocumento);
  const tieneRespuestaSunat = ['ACEPTADA', 'RECHAZADA', 'ENVIADA', 'ENVIADO'].includes(estadoSunat);
  const estaAnulado = estadoSunat === 'ANULADO';

  if (!permiteAccionSunat) {
    return '<span class="text-muted">-</span>';
  }

  if (estaAnulado) {
    return `
      <div class="btn-list justify-content-center">
        <a href="${apiUrl(`/facturacion/${id}/sunat/xml/descargar`)}" class="btn btn-sm btn-icon btn-outline-primary" title="Descargar XML">
          <i class="ri-file-code-line"></i>
        </a>
      </div>
    `;
  }

  if (tieneRespuestaSunat) {
    return `
      <div class="btn-list justify-content-center">
        <a href="${apiUrl(`/facturacion/${id}/sunat/xml/descargar`)}" class="btn btn-sm btn-icon btn-outline-primary" title="Descargar XML">
          <i class="ri-file-code-line"></i>
        </a>
        <a href="${apiUrl(`/facturacion/${id}/sunat/cdr/descargar`)}" class="btn btn-sm btn-icon btn-outline-success" title="Descargar CDR">
          <i class="ri-file-zip-line"></i>
        </a>
      </div>
    `;
  }

  return `
    <button type="button" class="btn btn-sm btn-icon btn-info btn-enviar-sunat-facturacion" data-id="${id}" title="Enviar a SUNAT">
      <i class="ri-send-plane-line"></i>
    </button>
  `;
}

function enviarSunatFacturacion(id, $button = $()) {
  const idDocumento = Number(id || 0);
  if (!Number.isFinite(idDocumento) || idDocumento <= 0) {
    mostrarErrorFacturacion('No se pudo identificar el comprobante.');
    return;
  }

  $.ajax({
    url: apiUrl(`/facturacion/${idDocumento}/enviar-sunat`),
    type: 'POST',
    headers: ajaxHeaders(),
    beforeSend: function () {
      $('.btn-enviar-sunat-facturacion').prop('disabled', true);
      $button
        .removeClass('btn-icon')
        .html(`
          <span class="spinner-border spinner-border-sm me-2" role="status">
            <span class="visually-hidden">Cargando...</span>
          </span>
          Enviando...
        `);
    },
    success: function (response) {
      if (!response?.status) {
        mostrarErrorFacturacion(response?.message || 'No se pudo enviar a SUNAT.');
        return;
      }

      const data = response?.data || {};
      mostrarOkFacturacion(data?.mensaje || response?.message || 'Comprobante enviado a SUNAT.');
      tablaFacturacion?.ajax.reload(null, false);
    },
    error: function (xhr) {
      mostrarErrorFacturacion(extraerPrimerErrorFacturacion(xhr) || xhr?.responseJSON?.message || 'Error al enviar a SUNAT.');
    },
    complete: function () {
      $('.btn-enviar-sunat-facturacion').prop('disabled', false);
      $button
        .addClass('btn-icon')
        .html('<i class="ri-send-plane-line"></i>');
    },
  });
}

function obtenerOffcanvasDetalleFacturacion() {
  const elemento = document.getElementById('offcanvas-detalle-facturacion');
  if (!elemento || typeof bootstrap === 'undefined' || !bootstrap.Offcanvas) {
    return null;
  }

  if (!offcanvasDetalleFacturacion) {
    offcanvasDetalleFacturacion = bootstrap.Offcanvas.getOrCreateInstance(elemento);
  }

  return offcanvasDetalleFacturacion;
}

function abrirDetalleComprobanteFacturacion(row) {
  const offcanvas = obtenerOffcanvasDetalleFacturacion();
  if (!offcanvas || !row) {
    mostrarErrorFacturacion('No se pudo abrir el detalle del comprobante.');
    return;
  }

  $('#offcanvas-detalle-facturacion-label').text(`Detalle ${row?.comprobante || 'de comprobante'}`);
  const $body = $('#offcanvas-detalle-facturacion-body');
  $body.html(`
    <div class="p-3 text-center text-muted">
      <span class="spinner-border spinner-border-sm me-2" role="status">
        <span class="visually-hidden">Cargando...</span>
      </span>
      Cargando detalle...
    </div>
  `);
  offcanvas.show();

  cargarDetalleComprobanteFacturacion(row?.idrdocumento, function (detalle) {
    $('#offcanvas-detalle-facturacion-label').text(`Detalle ${detalle?.comprobante || 'de comprobante'}`);
    $body.html(renderDetalleComprobanteFacturacion(detalle));
  }, function (mensaje) {
    $body.html(`<div class="p-2 text-danger">${escapeHtmlFacturacion(mensaje)}</div>`);
  });
}

function cargarDetalleComprobanteFacturacion(idDocumento, callback, errorCallback = null) {
  const id = Number(idDocumento || 0);
  if (!Number.isFinite(id) || id <= 0) {
    const mensaje = 'No se pudo identificar el comprobante.';
    if (typeof errorCallback === 'function') errorCallback(mensaje);
    else mostrarErrorFacturacion(mensaje);
    return;
  }

  $.ajax({
    url: apiUrl(`/facturacion/${id}/detalle`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response?.status) {
        const mensaje = response?.message || 'No se pudo cargar el detalle del comprobante.';
        if (typeof errorCallback === 'function') errorCallback(mensaje);
        else mostrarErrorFacturacion(mensaje);
        return;
      }
      if (typeof callback === 'function') callback(response.data || {});
    },
    error: function (xhr) {
      const mensaje = extraerPrimerErrorFacturacion(xhr) || xhr?.responseJSON?.message || 'Error al cargar el detalle del comprobante.';
      if (typeof errorCallback === 'function') errorCallback(mensaje);
      else mostrarErrorFacturacion(mensaje);
    },
  });
}

function renderDetalleComprobanteFacturacion(row) {
  const cliente = String(row?.cliente_descripcion || row?.cliente_nombre || 'Cliente sin nombre').trim();
  const documentoCliente = String(row?.cliente_documento || '').trim();
  const abreviaturaCliente = String(row?.cliente_documento_abreviatura || 'DOC').trim().toUpperCase();
  const sexo = String(row?.cliente_sexo || '').trim().toUpperCase();
  const fotoDefault = sexo === 'F' ? 'mujer.png' : 'hombre.png';
  const fotoNombre = String(row?.cliente_foto_perfil || fotoDefault).trim();
  const fotoUrl = apiUrl(`/assets/modulo/persona/perfil/${fotoNombre}`);
  const fotoFallback = apiUrl(`/assets/modulo/persona/perfil/${fotoDefault}`);
  const detalles = Array.isArray(row?.detalles) ? row.detalles : [];
  const metodosPago = Array.isArray(row?.metodos_pago) ? row.metodos_pago : [];
  const archivos = Array.isArray(row?.archivos) ? row.archivos : [];

  const detalleHtml = detalles.length
    ? detalles.map(function (item) {
      const producto = item?.producto || {};
      const codigo = String(producto?.codigo || '').trim();
      const descripcion = String(item?.descripcion || producto?.nombre || 'Item').trim();
      return `
        <li class="list-group-item px-0">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <p class="mb-1 fw-semibold">${codigo ? `<span class="badge bg-light text-dark me-1">${escapeHtmlFacturacion(codigo)}</span>` : ''}${escapeHtmlFacturacion(descripcion)}</p>
              <span class="fs-12 text-muted">Cantidad: ${escapeHtmlFacturacion(item?.cantidad ?? 0)} | Precio: ${renderMonedaTextoFacturacion(item?.precio_venta ?? 0)}</span>
            </div>
            <span class="badge bg-primary">${renderMonedaTextoFacturacion(item?.subtotal ?? 0)}</span>
          </div>
        </li>
      `;
    }).join('')
    : '<li class="list-group-item px-0 text-muted">Sin productos registrados.</li>';

  const metodosHtml = metodosPago.length
    ? metodosPago.map(function (metodo) {
      const cuenta = obtenerTextoCuentaBancariaFacturacion(metodo);
      const voucher = String(metodo?.codigo_voucher || '').trim();
      return `
        <li class="list-group-item px-0 d-flex justify-content-between align-items-start gap-2">
          <div>
            <p class="mb-0 fw-semibold">${escapeHtmlFacturacion(cuenta)}</p>
            <span class="fs-12 text-muted">${escapeHtmlFacturacion(voucher ? `Voucher: ${voucher}` : 'Sin voucher')}</span>
          </div>
          <span class="fw-semibold">${renderMonedaTextoFacturacion(metodo?.monto ?? 0)}</span>
        </li>
      `;
    }).join('')
    : '<li class="list-group-item px-0 text-muted">Sin metodos de pago registrados.</li>';

  const archivosHtml = archivos.length
    ? archivos.map(function (archivo) {
      const nombre = String(archivo?.nombre_visible || archivo?.nombre_original || 'Documento').trim();
      const extension = String(archivo?.extension || 'archivo').trim().toUpperCase();
      const url = obtenerUrlArchivoFacturacion(archivo);
      return `
        <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
          <div>
            <p class="mb-0 fw-semibold">${escapeHtmlFacturacion(nombre)}</p>
            <span class="fs-12 text-muted">${escapeHtmlFacturacion(extension)} - ${formatearTamanoBytesFacturacion(archivo?.peso_bytes || 0)}</span>
          </div>
          ${url ? `<a href="${escapeHtmlFacturacion(url)}" class="btn btn-sm btn-icon btn-outline-primary" target="_blank" rel="noopener noreferrer" title="Abrir documento"><i class="ri-external-link-line"></i></a>` : ''}
        </li>
      `;
    }).join('')
    : '<li class="list-group-item px-0 text-muted">Sin documentos adjuntos.</li>';

  return `
    <div class="p-2 border-bottom">
      <div class="d-flex align-items-center gap-2">
        <span class="avatar avatar-lg avatar-rounded">
          <img src="${fotoUrl}" alt="Cliente" onerror="this.src='${fotoFallback}';">
        </span>
        <div>
          <p class="mb-0 fw-semibold">${escapeHtmlFacturacion(cliente)}</p>
          <span class="fs-12 text-muted">${escapeHtmlFacturacion(documentoCliente ? `${abreviaturaCliente}: ${documentoCliente}` : abreviaturaCliente)}</span>
        </div>
      </div>
    </div>
    <div class="p-2 border-bottom">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">${escapeHtmlFacturacion(obtenerTipoDocumentoFacturacion(row))}</h6>
        ${renderBadgeEstadoSunatFacturacion(row?.sunat_estado || '-')}
      </div>
      <div class="row g-2">
        <div class="col-6"><span class="fs-11 text-muted d-block">Comprobante</span><span class="fw-semibold">${escapeHtmlFacturacion(row?.comprobante || '-')}</span></div>
        <div class="col-6"><span class="fs-11 text-muted d-block">Fecha emision</span><span class="fw-semibold">${renderFechaFacturacion(row?.fecha_emision)}</span></div>
      </div>
    </div>
    <div class="p-2 border-bottom">
      <h6 class="mb-1">Totales</h6>
      <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><span>${renderMonedaTextoFacturacion(row?.venta_subtotal ?? 0)}</span></div>
      <div class="d-flex justify-content-between"><span class="text-muted">Descuento</span><span>${renderMonedaTextoFacturacion(row?.venta_descuento ?? 0)}</span></div>
      <div class="d-flex justify-content-between"><span class="text-muted">IGV</span><span>${renderMonedaTextoFacturacion(row?.venta_igv ?? 0)}</span></div>
      <div class="d-flex justify-content-between fw-bold text-primary"><span>Total</span><span>${renderMonedaTextoFacturacion(row?.venta_total ?? 0)}</span></div>
    </div>
    <div class="p-2 border-bottom"><h6 class="mb-1">Productos</h6><ul class="list-group list-group-flush">${detalleHtml}</ul></div>
    <div class="p-2 border-bottom"><h6 class="mb-1">Metodos de pago</h6><ul class="list-group list-group-flush">${metodosHtml}</ul></div>
    <div class="p-2 border-bottom"><h6 class="mb-1">Documentos</h6><ul class="list-group list-group-flush">${archivosHtml}</ul></div>
    <div class="p-2"><h6 class="mb-1">Observacion</h6><p class="mb-0 text-muted">${escapeHtmlFacturacion(row?.observacion_documento || 'Sin observacion registrada.')}</p></div>
  `;
}

function obtenerTextoCuentaBancariaFacturacion(metodo) {
  const id = Number(metodo?.idcuenta_bancaria || 0);
  const cuentaRelacion = metodo?.cuenta_bancaria || {};
  const banco = cuentaRelacion?.banco || {};
  const nombreBanco = String(banco?.alias || banco?.nombre || '').trim();
  const numeroCuenta = String(cuentaRelacion?.cta_cte || cuentaRelacion?.cci || '').trim();
  const moneda = String(cuentaRelacion?.moneda || '').trim().toUpperCase();
  if (nombreBanco || numeroCuenta) {
    return `${nombreBanco || 'Cuenta'} - ${numeroCuenta || 'Sin numero'}${moneda ? ` (${moneda})` : ''}`;
  }

  const cuenta = (catalogoCreacionFacturacion.cuentas_bancarias || []).find(function (item) {
    return Number(item?.idcuenta_bancaria || 0) === id;
  });
  return String(cuenta?.text || `Cuenta bancaria #${id || '-'}`);
}

function obtenerUrlArchivoFacturacion(archivo) {
  const nombre = String(archivo?.nombre_guardado || '').trim().split(/[\\/]/).pop();
  return nombre ? apiUrl(`/assets/modulo/rdocumento/facturacion/${encodeURIComponent(nombre)}`) : '';
}

function abrirModalDetalleSunatFacturacion(row) {
  if (!row) {
    mostrarErrorFacturacion('No se pudo obtener el detalle SUNAT.');
    return;
  }

  const tipoDocumento = obtenerTipoDocumentoFacturacion(row);
  const numeroDocumento = String(row?.comprobante || '').trim();
  const estadoSunat = String(row?.sunat_estado || '').trim();
  const titulo = [tipoDocumento, numeroDocumento].filter(Boolean).join(' ');

  $('#modal-detalle-sunat-facturacion-label').text('Detalle SUNAT');
  $('#modal-detalle-sunat-facturacion-subtitle').text(titulo || '-');
  $('#detalle-sunat-tipo-documento').html(renderValorDetalleSunatFacturacion(tipoDocumento));
  $('#detalle-sunat-numero-documento').html(renderValorDetalleSunatFacturacion(numeroDocumento));
  $('#detalle-sunat-estado').html(estadoSunat ? renderBadgeEstadoSunatFacturacion(estadoSunat) : '<span class="text-muted">-</span>');
  $('#detalle-sunat-code').html(renderValorDetalleSunatFacturacion(row?.sunat_code));
  $('#detalle-sunat-hash').html(renderValorDetalleSunatFacturacion(row?.sunat_hash));
  $('#detalle-sunat-mensaje').html(renderValorDetalleSunatFacturacion(row?.sunat_mensaje));
  $('#detalle-sunat-observacion').html(renderValorDetalleSunatFacturacion(row?.sunat_observacion));
  $('#detalle-sunat-error').html(renderValorDetalleSunatFacturacion(row?.sunat_error));

  const modalElement = document.getElementById('modal-detalle-sunat-facturacion');
  if (modalElement && typeof bootstrap !== 'undefined') {
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
    return;
  }

  $('#modal-detalle-sunat-facturacion').modal('show');
}

function obtenerTipoDocumentoFacturacion(row) {
  const codigo = String(row?.tipo_documento_codigo || '').trim();
  const abreviatura = String(row?.tipo_documento_abreviatura || '').trim();
  const nombre = String(row?.tipo_documento_nombre || '').trim();
  const descripcion = abreviatura || nombre;

  return [codigo, descripcion].filter(Boolean).join(' - ');
}

function renderBadgeEstadoSunatFacturacion(estado) {
  const estadoSunat = String(estado || '').trim();
  const estadoSunatNormalizado = estadoSunat.toUpperCase();
  const badgeClass = estadoSunatNormalizado === 'ACEPTADA'
    ? 'bg-success-transparent'
    : estadoSunatNormalizado === 'POR ENVIAR'
      ? 'bg-warning-transparent'
      : 'bg-danger-transparent';

  return `<span class="badge ${badgeClass}">${escapeHtmlFacturacion(estadoSunat)}</span>`;
}

function renderValorDetalleSunatFacturacion(value) {
  const texto = String(value ?? '').trim();
  return texto ? escapeHtmlFacturacion(texto).replace(/\n/g, '<br>') : '<span class="text-muted">-</span>';
}

function renderFechaFacturacion(value) {
  if (!value) return '<span class="text-muted">-</span>';

  const fecha = new Date(value);
  if (Number.isNaN(fecha.getTime())) {
    return renderPlanoFacturacion(value);
  }

  const anioActual = new Date().getFullYear();
  const mes = fecha.toLocaleDateString('es-PE', {
    month: 'short',
  }).replace(/\./g, '');
  const mesCorto = mes.charAt(0).toUpperCase() + mes.slice(1).toLowerCase();
  const anio = fecha.getFullYear() !== anioActual ? ` ${fecha.getFullYear()}` : '';
  const horas = fecha.getHours();
  const hora = `${horas % 12 || 12}:${String(fecha.getMinutes()).padStart(2, '0')} ${horas < 12 ? 'am' : 'pm'}`;

  return `${fecha.getDate()} ${mesCorto}${anio} &bull; ${hora}`;
}

function renderPlanoFacturacion(data) {
  if (data === null || data === undefined || data === '') return '<span class="text-muted">-</span>';
  return escapeHtmlFacturacion(data);
}

function renderMonedaFacturacion(data) {
  if (data === null || data === undefined || data === '') return '<span class="text-muted">-</span>';
  return `S/ ${Number(data).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function renderMonedaTextoFacturacion(data) {
  return `S/ ${Number(data || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatearNumeroFacturacion(data) {
  return Number(data || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function redondearFacturacion(value) {
  return Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;
}

function confirmarFacturacion(title, text, confirmButtonText, callback) {
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

function mostrarOkFacturacion(message) {
  if (typeof toastr !== 'undefined') {
    toastr.success(message);
    return;
  }
  alert(message);
}

function mostrarErrorFacturacion(message) {
  if (typeof toastr !== 'undefined') {
    toastr.error(message);
    return;
  }
  alert(message);
}

function extraerPrimerErrorFacturacion(xhr) {
  const response = xhr?.responseJSON || {};
  const errors = response?.data || response?.errors || {};
  const first = Object.values(errors)[0];
  if (!first) return null;
  return Array.isArray(first) ? first[0] : first;
}

function escapeHtmlFacturacion(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
