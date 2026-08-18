function apiUrlReserva(path) {
  const base = document.querySelector('meta[name="app-url"]')?.content || '';
  return base + path;
}

function csrfReserva() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function ajaxHeadersReserva() {
  return {
    'X-CSRF-TOKEN': csrfReserva(),
    Accept: 'application/json',
  };
}

$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': csrfReserva(),
    Accept: 'application/json',
  }
});


const ReservaSelectors = Object.freeze({
  tabla: '#tabla-reserva',
  filtroCliente: '#filtro_cliente',
  formReserva: '#form-agregar-reserva',
  formCliente: '#form-agregar-nuevo-cliente',
  formPago: '#form-amortizar-reserva',
  modalCliente: '#modal-agregar-nuevo-cliente',
  modalPago: '#modal-amortizar-reserva',
  searchTours: '#search_tours',
  searchResults: '#searchResults',
});

const ReservaEndpoints = Object.freeze({
  listar: '/reservas/listar',
  codigo: '/reservas/codigo',
  validarCodigo: '/reservas/validar-codigo',
  clientes: '/reservas/catalogos/clientes',
  origenes: '/reservas/catalogos/origenes',
  llegadaTipos: '/reservas/catalogos/llegada-tipos',
  llegadaEmpresas: '/reservas/catalogos/llegada-empresas',
  hoteles: '/reservas/catalogos/hoteles',
  trabajadores: '/reservas/catalogos/trabajadores',
  bancos: '/reservas/catalogos/bancos',
  distritos: '/reservas/catalogos/distritos',
  toursBuscar: '/reservas/tours/buscar',
  store: '/reservas/store',
  clientesStore: '/reservas/clientes/store',
  pagosStore: '/reservas/pagos/store',
  pagos: '/reservas/pagos',
  tours: '/reservas/tours',
  habitaciones: '/reservas/catalogos/habitaciones',
  habitacion: '/reservas/habitaciones',
  impresionComprobante: function (documentoId, formato) {
    return `/facturacion/${documentoId}/impresion/${formato}`;
  },
  comprobantesAsociables: function (reservaId) {
    return `/reservas/${reservaId}/comprobantes-asociables`;
  },
  asociarComprobante: function (reservaId) {
    return `/reservas/${reservaId}/asociar-comprobante`;
  },
  detalleComprobante: function (reservaId) {
    return `/reservas/${reservaId}/detalle-comprobante`;
  },
});
const ReservaUxSelectors = Object.freeze({
  stepTab: '.reserva-step-tab',
  stepPanel: '[data-reserva-step-panel]',
  resumenTours: '#resumen_total_tours',
  resumenHotel: '#resumen_total_hotel',
  resumenVuelo: '#resumen_total_vuelo',
  resumenReserva: '#resumen_total_reserva',
  paxError: '#reserva_pax_error',
});

const ReservaStepsOrden = ['general', 'tours', 'hotel', 'vuelo'];
let reservaTotalManual = false;

function reservaNumero(valor) {
  if (valor === null || valor === undefined) return 0;
  const limpio = String(valor).replace(/[^0-9.-]/g, '');
  const numero = parseFloat(limpio);
  return Number.isFinite(numero) ? numero : 0;
}

function reservaMoneda(valor) {
  return 'S/ ' + reservaNumero(valor).toFixed(2);
}

function reservaPaxActual() {
  const pax = parseInt($("#numero_pasajero").val(), 10);
  return Number.isFinite(pax) && pax > 0 ? pax : 0;
}
function esCampoAdicionalReserva(element) {
  const nombre = String($(element).attr('name') || '').toLowerCase();
  const id = String($(element).attr('id') || '').toLowerCase();
  return nombre === 'adicional[]' || id.startsWith('adicional_');
}

function normalizarNumeroReserva(element) {
  const $input = $(element);
  if (!$input.is('input[type="number"]')) return;

  if (esCampoAdicionalReserva(element)) {
    $input.removeAttr('min');
    return;
  }

  $input.attr('min', '0');
  const valor = $input.val();
  if (valor === '') return;

  const numero = parseFloat(valor);
  if (!Number.isFinite(numero) || numero < 0) {
    $input.val('0').trigger('change');
  }
}

function mostrarPasoReserva(step) {
  const $tab = $(`${ReservaUxSelectors.stepTab}[data-reserva-step="${step}"]`);
  if (!$tab.length || $tab.hasClass('is-disabled')) return;

  $(ReservaUxSelectors.stepTab).removeClass('active');
  $tab.addClass('active');
  $(ReservaUxSelectors.stepPanel).removeClass('active');
  $(`${ReservaUxSelectors.stepPanel}[data-reserva-step-panel="${step}"]`).addClass('active');
  cargarSeccionReserva(step);
}

function pasoDeCampoReserva(element) {
  const $panel = $(element).closest(ReservaUxSelectors.stepPanel);
  return $panel.data('reserva-step-panel') || 'general';
}

function actualizarResumenReserva() {
  const totalTours = reservaNumero($('.subtotaltours').first().text());
  const totalHotel = reservaNumero($('.total_hab').first().text());
  const totalVuelo = reservaNumero($('#monto_compra_vuelo').val());
  const totalReserva = reservaNumero($('#total_general_i').val() || $('.total_general').first().text());

  setResumenInputReserva(ReservaUxSelectors.resumenTours, totalTours);
  setResumenInputReserva(ReservaUxSelectors.resumenHotel, totalHotel);
  setResumenInputReserva(ReservaUxSelectors.resumenVuelo, totalVuelo);
  setResumenInputReserva(ReservaUxSelectors.resumenReserva, totalReserva);
}

function setResumenInputReserva(selector, valor) {
  const $input = $(selector);
  if (!$input.length || $input.is(':focus')) return;
  $input.val(reservaNumero(valor).toFixed(2));
}

function aplicarResumenEditableReserva(field) {
  const tours = reservaNumero($(ReservaUxSelectors.resumenTours).val());
  const hotel = reservaNumero($(ReservaUxSelectors.resumenHotel).val());
  const vuelo = reservaNumero($(ReservaUxSelectors.resumenVuelo).val());
  const total = reservaNumero($(ReservaUxSelectors.resumenReserva).val());

  if (field === 'total') {
    reservaTotalManual = true;
    $('.total_general').text(total.toFixed(2));
    $('#total_general_i').val(total.toFixed(2));
    return;
  }

  reservaTotalManual = false;
  $('.subtotaltours').text(tours.toFixed(2));
  $('.total_hab').text(hotel.toFixed(2));
  $('#monto_compra_vuelo').val(vuelo.toFixed(2));
  total_general();
}
function asegurarLoaderSeccionReserva($panel) {
  if (!$panel.length || $panel.find('> .reserva-section-loader').length) return;
  $panel.append('<div class="reserva-section-loader"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span class="reserva-section-loader-text">Cargando...</span></div>');
}

function setReservaSectionLoading(step, loading, text = 'Cargando...') {
  const $panel = step === 'summary'
    ? $('.reserva-summary-card').first()
    : $(`${ReservaUxSelectors.stepPanel}[data-reserva-step-panel="${step}"]`).first();
  asegurarLoaderSeccionReserva($panel);
  $panel.find('.reserva-section-loader-text').text(text);
  $panel.toggleClass('is-loading', !!loading);
}

function colocarErrorReserva(error, element) {
  const $element = $(element);
  error.addClass('invalid-feedback reserva-field-error');
  $element.closest('.form-group').find('> .reserva-field-error, > .invalid-feedback').remove();

  if ($element.hasClass('select2-hidden-accessible')) {
    error.insertAfter($element.next('.select2-container'));
    return;
  }

  const $inputGroup = $element.closest('.input-group');
  if ($inputGroup.length) {
    error.insertAfter($inputGroup);
    return;
  }

  $element.closest('.form-group').append(error);
}

function marcarCampoReserva(element, invalid) {
  const $element = $(element);
  $element.toggleClass('is-invalid', invalid).toggleClass('is-valid', !invalid);
  if ($element.hasClass('select2-hidden-accessible')) {
    $element.next('.select2-container').toggleClass('is-invalid', invalid);
  }
}

function reservaResourceUrl(id, suffix = '') {
  return apiUrlReserva(`/reservas/${id}${suffix}`);
}

function reservaCatalogoUrl(endpoint, params = {}) {
  const query = new URLSearchParams(params).toString();
  return apiUrlReserva(query ? `${endpoint}?${query}` : endpoint);
}
function normalizarRespuestaReserva(response) {
  return typeof response === 'string' ? JSON.parse(response) : response;
}

if (typeof lista_selectChoice !== 'function') {
  function lista_selectChoice(url, choiceInstance, selected = null) {
    return $.get(url, function (response) {
      response = normalizarRespuestaReserva(response);
      if (response.status !== true) { ver_errores(response); return; }
      choiceInstance.clearStore();
      choiceInstance.setChoices(response.data || [], 'value', 'label', true);
      if (selected) choiceInstance.setChoiceByValue(String(selected));
    }).fail(function (e) { ver_errores(e); });
  }
}

if (typeof add_tooltip_custom !== 'function') {
  function add_tooltip_custom(selector, title) {
    $(selector).attr('data-bs-toggle', 'tooltip').attr('data-bs-original-title', title);
    $('[data-bs-toggle="tooltip"]').tooltip();
  }
}

if (typeof no_select_yesterday !== 'function') {
  function no_select_yesterday(selector) {
    $(selector).attr('min', moment().format('YYYY-MM-DD'));
  }
}

if (typeof FilePond_Facturacion_LabelsES === 'undefined') {
  var FilePond_Facturacion_LabelsES = {
    labelIdle: 'Arrastra o selecciona un archivo',
    labelFileProcessing: 'Cargando',
    labelFileProcessingComplete: 'Carga completa',
    labelFileProcessingAborted: 'Carga cancelada',
    labelTapToCancel: 'toca para cancelar',
    labelTapToRetry: 'toca para reintentar',
    labelTapToUndo: 'toca para deshacer',
  };
}
var tabla_reserva;
let detalle_incluye;
let file_pond_brochure;
let file_pond_brochure_pago;
var array_data_filas = [];
var array_data_filas_habit = [];
var valid_fechadetalle = "";

var idreserva_para_amortizar = "";
var nombre_cliente_para_amortizar = "";
var apellido_cliente_para_amortizar = "";
var documento_cliente_para_amortizar = "";
var doc_cliente_para_amortizar = "";

var TipoClienteGlobal = ""; // 1: cliente Pago ClientePago, 2: vista adda reserva Clienteadd

function abrirModalNuevoClienteDesdeBoton(event) {
  const boton = event.target.closest('.js-agregar-nuevo-cliente');
  if (!boton) return;

  event.preventDefault();
  event.stopPropagation();
  modal_add_nuevo_cliente(boton.dataset.clienteTipo || 'Clienteadd');
}

document.addEventListener('click', abrirModalNuevoClienteDesdeBoton, true);

/* quill snow editor */
var toolbarOptions = [
  [{ header: [1, 2, 3, 4, 5, 6, false] }],
  [{ font: [] }],
  ["bold", "italic", "underline", "strike"], // toggled buttons
  ["blockquote", "code-block"],

  [{ header: 1 }, { header: 2 }], // custom button values
  [{ list: "ordered" }, { list: "bullet" }],
  [{ script: "sub" }, { script: "super" }], // superscript/subscript
  [{ indent: "-1" }, { indent: "+1" }], // outdent/indent
  [{ direction: "rtl" }], // text direction

  [{ size: ["small", false, "large", "huge"] }], // custom dropdown

  [{ color: [] }, { background: [] }], // dropdown with defaults from theme
  [{ align: [] }],

  ["image", "video"],
  ["clean"], // remove formatting button
];

let f_metodo_pago_1 = null;
let reservaFormularioControlesInicializados = false;
let reservaFormularioInicializado = false;
let reservaFormularioCarga = null;
let reservaClientesInicializado = false;
let reservaClientesCarga = null;
let reservaHotelInicializado = false;
let reservaHotelCarga = null;
let reservaClienteModalInicializado = false;
let reservaPagoInicializado = false;
let reservaPagoCarga = null;

function cargarSelect2Reserva(url, selector, selected = null, spanCharge = null, triggerChange = true) {
  if (spanCharge) $(spanCharge).html('<i class="fas fa-spinner fa-pulse fa-lg text-danger"></i>');

  return $.get(url, function (response) {
    response = normalizarRespuestaReserva(response);
    if (response.status !== true) { ver_errores(response); return; }

    const $select = $(selector);
    $select.html(response.data);

    if (selected !== null && selected !== undefined && selected !== '' && selected !== 'NaN' && selected !== 'Infinity') {
      $select.val(selected);
    } else {
      $select.val(null);
    }

    if (triggerChange) $select.trigger('change');
    if (spanCharge) $(spanCharge).html('');
  }).fail(function (e) {
    if (spanCharge) $(spanCharge).html('');
    ver_errores(e);
  });
}

function inicializarDistritoClienteReserva() {
  const $distrito = $('#cli_distrito');
  if (!$distrito.length || $distrito.hasClass('select2-hidden-accessible')) return;

  $distrito.select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#modal-agregar-nuevo-cliente'),
    placeholder: 'Buscar distrito...',
    allowClear: true,
    ajax: {
      url: apiUrlReserva(ReservaEndpoints.distritos),
      dataType: 'json',
      delay: 250,
      headers: ajaxHeadersReserva(),
      data: function (params) {
        return {
          term: params.term || '',
          page: params.page || 1,
          select2: 1,
        };
      },
      processResults: function (response) {
        response = normalizarRespuestaReserva(response);
        return {
          results: response.data?.results || response.results || [],
          pagination: response.data?.pagination || response.pagination || { more: false },
        };
      },
    },
  });
}

function seleccionarDistritoClientePorTexto(textoDistrito) {
  const texto = (textoDistrito || '').toString().trim();
  const $distrito = $('#cli_distrito');
  if (!texto || !$distrito.length) return;

  $.get(apiUrlReserva(ReservaEndpoints.distritos), { term: texto, select2: 1 }, function (response) {
    response = normalizarRespuestaReserva(response);
    const resultados = response.data?.results || [];
    const distrito = resultados[0];
    if (!distrito) return;

    const option = new Option(distrito.text, distrito.id, true, true);
    $distrito.append(option).trigger('change');
  }).fail(function (e) {
    ver_errores(e);
  });
}

function inicializarEventosReserva() {
  $(document).on('click', ReservaUxSelectors.stepTab, function () { mostrarPasoReserva($(this).data('reserva-step')); });
  $(document).on('input change', '#monto_compra_vuelo, #numero_pasajero, #cant_ninos, #cant_adultos, #cant_ancianos', function () { actualizarResumenReserva(); });
  $(document).on('input change', '#numero_pasajero', function () { number_pax(); });
  $(document).on('input change', '#form-agregar-reserva input[type="number"]', function () { normalizarNumeroReserva(this); });
  $(document).on('input change', '.reserva-summary-input', function () { aplicarResumenEditableReserva($(this).data('summary-field')); });
  $(document).on('click', '.js-imprimir-formato-reserva', function () { imprimirFormatoReserva(String($(this).data('formato') || 'a4')); });
  $(document).on('click', '.btn-guardar', function (event) {
    event.preventDefault();
    if ($(this).hasClass('send-data')) {
      toastr_info('Espere!!', 'La reserva se esta guardando...');
      return;
    }
    $('#form-agregar-reserva').trigger('submit');
  });
  $('#guardar_registro_turno').on('click', function () { if ($(this).hasClass('send-data') == false) { $('#submit-form_turno').submit(); } });
  $('#btn-recargar-reservas').on('click', function () { cargando_search(); filtros(); });
  $('#guardar_registro_nuevo_cliente').on('click', function () { if ($(this).hasClass('send-data') == false) { $('#submit-form-nuevo-cliente').submit(); } else { toastr_info('Espere!!', 'Por favor sea pasiente se estan procesando los datos...'); } });
  $('#guardar_registro_nuevo_pago_reserva').on('click', function () { if ($(this).hasClass('send-data') == false) { $('#submit-form-reserva_pago').submit(); } else { toastr_info('Espere!!', 'Por favor sea pasiente se estan procesando los datos...'); } });
  $('#btn-guardar-asociar-comprobante').on('click', guardarAsociacionComprobanteReserva);
  $('#asociar_mostrar_todos_documentos').on('change', function () {
    const idreserva = Number($('#asociar_reserva_id').val() || 0);
    $('#asociar_idrdocumento').val('');
    $('#monto_comprobante_asociar').val('');
    $('#texto_comprobante_asociar').text('-');
    inicializarSelectComprobanteAsociarReserva(idreserva);
    $('#select_comprobante_asociar').val(null).trigger('change');
  });
  $('#select_comprobante_asociar').on('select2:select', function (event) {
    seleccionarComprobanteAsociarReserva(event.params.data);
  });

  $(ReservaSelectors.filtroCliente).select2({ templateResult: templateCliente, theme: 'bootstrap4', placeholder: 'Seleccione', allowClear: true });
  cargarSelect2Reserva(apiUrlReserva(ReservaEndpoints.clientes), ReservaSelectors.filtroCliente, null, '.charge_filtro_cliente', false).always(function () {
    filtros();
  });
}

function inicializarControlesFormularioReserva() {
  if (reservaFormularioControlesInicializados) return;

  if (!detalle_incluye && document.querySelector('#detalle_incluye')) {
    detalle_incluye = new Quill('#detalle_incluye', { modules: { toolbar: toolbarOptions }, theme: 'snow' });
  }

  $('#idpersona_cliente').select2({ theme: 'bootstrap4', placeholder: 'Seleccione', allowClear: true });
  $('#idpersona_cliente').on('select2:opening', function () { cargarClientesReserva(); });
  $('#idorigenreserva').select2({ theme: 'bootstrap4', placeholder: 'Seleccione', allowClear: true });
  $('#idllegada_por').select2({ theme: 'bootstrap4', placeholder: 'Seleccione', allowClear: true });
  $('#select_idhotel').select2({ theme: 'bootstrap4', placeholder: 'Seleccione', allowClear: true });
  $('#select_habitacion').select2({ theme: 'bootstrap4', placeholder: 'Seleccione', allowClear: true });
  $('#idasesorreserva').select2({ theme: 'bootstrap4', placeholder: 'Seleccione', allowClear: true });
  $('#llegada_por_empresa').select2({ theme: 'bootstrap4', placeholder: 'Seleccione', allowClear: true });

  reservaFormularioControlesInicializados = true;
}

function cargarClientesReserva(callback) {
  if (reservaClientesInicializado) {
    if (typeof callback === 'function') callback();
    return;
  }

  if (reservaClientesCarga) {
    if (typeof callback === 'function') reservaClientesCarga.always(callback);
    return;
  }

  reservaClientesCarga = cargarSelect2Reserva(apiUrlReserva(ReservaEndpoints.clientes), '#idpersona_cliente', null, '.charge_cliente_reserva', false).always(function () {
    reservaClientesInicializado = true;
    reservaClientesCarga = null;
    if (typeof callback === 'function') callback();
  });
}
function inicializarFormularioReserva(callback) {
  if (reservaFormularioInicializado) {
    if (typeof callback === 'function') callback();
    return;
  }

  if (reservaFormularioCarga) {
    if (typeof callback === 'function') reservaFormularioCarga.always(callback);
    return;
  }

  inicializarControlesFormularioReserva();
  setReservaSectionLoading('general', true, 'Cargando catalogos...');

  reservaFormularioCarga = $.when(
    cargarSelect2Reserva(apiUrlReserva(ReservaEndpoints.origenes), '#idorigenreserva', null, null, false),
    cargarSelect2Reserva(apiUrlReserva(ReservaEndpoints.llegadaTipos), '#idllegada_por', null, null, false),
    cargarSelect2Reserva(apiUrlReserva(ReservaEndpoints.trabajadores), '#idasesorreserva', null, null, false),
    cargarSelect2Reserva(reservaCatalogoUrl(ReservaEndpoints.llegadaEmpresas, { idllegada_por: 1 }), '#llegada_por_empresa', '1', null, false)
  ).always(function () {
    setReservaSectionLoading('general', false);
    reservaFormularioInicializado = true;
    reservaFormularioCarga = null;
    if (typeof callback === 'function') callback();
  });
}

function inicializarHotelReserva(callback) {
  if (reservaHotelInicializado) {
    if (typeof callback === 'function') callback();
    return;
  }

  if (reservaHotelCarga) {
    if (typeof callback === 'function') reservaHotelCarga.always(callback);
    return;
  }

  inicializarControlesFormularioReserva();
  setReservaSectionLoading('hotel', true, 'Cargando hoteles...');

  reservaHotelCarga = cargarSelect2Reserva(apiUrlReserva(ReservaEndpoints.hoteles), '#select_idhotel', null, null, false).always(function () {
    reservaHotelInicializado = true;
    reservaHotelCarga = null;
    setReservaSectionLoading('hotel', false);
    if (typeof callback === 'function') callback();
  });
}

function cargarSeccionReserva(step, callback) {
  if (step === 'general') {
    inicializarFormularioReserva(callback);
    return;
  }

  if (step === 'hotel') {
    inicializarHotelReserva(callback);
    return;
  }

  if (typeof callback === 'function') callback();
}
function inicializarClienteReserva() {
  if (reservaClienteModalInicializado) return;
  inicializarDistritoClienteReserva();
  $('#cli_tipo_persona_sunat_select').select2({ theme: 'bootstrap4', dropdownParent: $('#modal-agregar-nuevo-cliente'), placeholder: 'Seleccionar Tipo Persona', allowClear: true });
  $('#cli_tipo_documento').select2({ theme: 'bootstrap4', dropdownParent: $('#modal-agregar-nuevo-cliente'), placeholder: 'Seleccionar Tipo de Documento', allowClear: true });
  $('#cli_estado_civil').select2({ theme: 'bootstrap4', dropdownParent: $('#modal-agregar-nuevo-cliente'), placeholder: 'Seleccionar Estado Civil', allowClear: true });
  $('#cli_nacionalidad').select2({ theme: 'bootstrap4', dropdownParent: $('#modal-agregar-nuevo-cliente'), placeholder: 'Seleccionar Nacionalidad', allowClear: true });
  $('#cli_sexo').select2({ theme: 'bootstrap4', dropdownParent: $('#modal-agregar-nuevo-cliente'), placeholder: 'Seleccionar Sexo', allowClear: true });
  reservaClienteModalInicializado = true;
}

function inicializarPagoReserva(callback) {
  if (!$('#p_idpersona_cliente').hasClass('select2-hidden-accessible')) {
    $('#p_idpersona_cliente').select2({ theme: 'bootstrap4', dropdownParent: $('#modal-amortizar-reserva'), placeholder: 'Seleccione', allowClear: true });
  }

  if (!$('#f_serie_comprobante').hasClass('select2-hidden-accessible')) {
    $('#f_serie_comprobante').select2({ theme: 'bootstrap4', dropdownParent: $('#modal-amortizar-reserva'), placeholder: 'Seleccione', allowClear: true });
  }
if (reservaPagoInicializado) {
    if (typeof callback === 'function') callback();
    return;
  }

  if (reservaPagoCarga) {
    reservaPagoCarga.always(function () {
      if (typeof callback === 'function') callback();
    });
    return;
  }

  f_metodo_pago_1 = new Choices('#f_metodo_pago_1', { removeItemButton: true, noResultsText: 'No hay resultados.' });
  reservaPagoCarga = lista_selectChoice(apiUrlReserva(ReservaEndpoints.bancos), f_metodo_pago_1, null).always(function () {
    reservaPagoInicializado = true;
    reservaPagoCarga = null;
    if (typeof callback === 'function') callback();
  });
}

function nueva_reserva() {
  try {
    inicializarControlesFormularioReserva();
    show_hide_form(2);
    limpiar_form_reserva();
    permitirFechaLlegadaPasadaReserva(false);
    create_code_tours('RE');
  } catch (error) {
    console.error('No se pudo preparar el formulario de reserva.', error);
    show_hide_form(2);
  }

  setTimeout(function () {
    inicializarFormularioReserva(function () {
      $('#idasesorreserva').val('3').trigger('change.select2');
      $('#idllegada_por').val('1').trigger('change.select2');
      $('#llegada_por_empresa').val('1').trigger('change.select2');
    });
  }, 50);
}

window.nueva_reserva = nueva_reserva;
function templateCliente (state) {
  if (!state.id) { return state.text; }
  var baseUrl = state.title != '' ? `../dist/docs/persona/perfil/${state.title}`: '../dist/svg/user_default.svg'; 
  var onerror = `onerror="this.src='../dist/svg/user_default.svg';"`;
  var $state = $(`<span class="fs-11" > ${state.text}</span>`);
  return $state;
}

function templateBanco (state) {
  if (!state.id) { return state.text; }
  var baseUrl = state.title != '' ? `../assets/modulo/bancos/${state.title}`: '../assets/modulo/bancos/logo-sin-banco.svg'; 
  var onerror = `onerror="this.src='../assets/modulo/bancos/logo-sin-banco.svg';"`;
  var $state = $(`<span><img src="${baseUrl}" class="img-circle mr-2 w-25px" ${onerror} />${state.text}</span>`);
  return $state;
};

//:::::::::::::::: T O U R S ::::::::::::::::

function limpiar_form_reserva() {

  //$("#ubigeo_distrito").val("").trigger("change");
  //$("#tours_turno").val("58").trigger("change"); // por defecto: NIU
    array_data_filas = [];
    array_data_filas_habit = [];

    $("#idreserva").val("");
    $("#idasesorreserva").val("3").trigger("change.select2");
    $("#idpersona_cliente").val("").trigger("change.select2");
    $("#idllegada_por").val("1").trigger("change.select2");
    $("#llegada_por_empresa").val("1").trigger("change.select2");
    $("#idorigenreserva").val("").trigger("change.select2");
    $("#codigo").val(""); 
    $("#numero_pasajero").val("");
    $("#llegada_fecha").val("");
    $("#llegada_hora").val("");
    $("#salida_fecha").val("");
    $("#reserva_solo_hotel").val("");
    $("#itinerario_reserva").val("");
    $("#vuelo_ticket").val("");
    $("#monto_compra_vuelo").val("");
    $("#obs_vuelo").val("");
    $("#total_general_i").val("");
    $("#nro_referencia").val("");
    $("#observaciones").val("");
    $("#detalle_ubicacion_r").val("");
    

    $(".subtotaltours").text("");
    $(".total_hab").text("");
    $(".total_general").text("");
    $("#total_general_i").val("");

    $("#es_tour_solo").prop("checked", false);  // lo desmarca
    toggleModoReserva();
    mostrarPasoReserva('general');
    actualizarResumenReserva();
    validar_cant_pax();

   $("#tabla-productos-seleccionados tbody").empty();
    $("#tabla-habitaciones-seleccionadas tbody").empty();
  if (detalle_incluye) detalle_incluye.setContents([]);

  if (file_pond_brochure) file_pond_brochure.removeFiles();

  $("#brochure_old").val("");

  // Limpiamos las validaciones
  $(".form-control").removeClass("is-valid");
  $(".form-control").removeClass("is-invalid");
  $(".error.invalid-feedback").remove();
}

function show_hide_form(flag) {
  if (flag == 1) {
    $(".card-header").show();
    $("#div-tabla").show();
    $(".div-form").hide();
    $(".detalle_reserva_ver").hide();
    $(".div_filtros").show();

    $(".btn-agregar").show();
    $("#btn-recargar-reservas").show();
    $(".btn-guardar").hide();
    $(".btn-cancelar").hide();

    $('.btns_option_detalles').hide();

  } else if (flag == 2) {
    $(".card-header").hide();
    $("#div-tabla").hide();
    $(".div-form").show();
    $(".detalle_reserva_ver").hide();
    $(".div_filtros").hide();

    $(".btn-agregar").hide();
    $("#btn-recargar-reservas").hide();
    $(".btn-guardar").show();
    $(".btn-cancelar").show();
  } else if(flag == 3){
    $(".div_filtros").hide();
    $(".card-header").hide();
    $("#div-tabla").hide();
    $(".div-form").hide();
    $(".detalle_reserva_ver").show();

    $(".btn-agregar").hide();
    $("#btn-recargar-reservas").hide();
    $(".btn-guardar").hide();
    $(".btn-cancelar").show();
  }
}

function listar_tabla(filtro_fecha_i, filtro_fecha_f, filtro_cliente) {
  tabla_reserva = $("#tabla-reserva")
    .dataTable({
      lengthMenu: [
        [-1, 5, 10, 25, 75, 100, 200],
        ["Todos", 5, 10, 25, 75, 100, 200],
      ], //mostramos el menú de registros a revisar
      aProcessing: true, //Activamos el procesamiento del datatables
      aServerSide: true, //Paginación y filtrado realizados por el servidor
      dom: "<'row align-items-center mb-2'<'col-md-10 pt-2'f><'col-md-2 pt-2 d-flex justify-content-end gap-1'<'length'l><'buttons'B>>>r t <'row align-items-center mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>", //Definimos los elementos del control de tabla
      buttons: [
        {
          text: '<i class="bi bi-arrow-clockwise"></i>',
          className: "buttons-reload btn btn-outline-info btn-sm",
          action: function (e, dt, node, config) { if (tabla_reserva) { tabla_reserva.ajax.reload(null, false); }},
        },
        {
          extend: "excel",
          exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7, 8, 9, 10] },
          title: "Lista de Reservas",
          text: '<i class="bi bi-file-earmark-excel"></i>',
          className: "btn btn-outline-success btn-sm",
          footer: true,
        },
      ],
      ajax: {
        url: reservaCatalogoUrl(ReservaEndpoints.listar, { filtro_fecha_i, filtro_fecha_f, filtro_cliente }),
        type: "get",
        dataType: "json",
        error: function (e) {
        },
        complete: function () {
          $(".buttons-reload")
            .attr("data-bs-toggle", "tooltip")
            .attr("data-bs-original-title", "Recargar");
          $(".buttons-excel")
            .attr("data-bs-toggle", "tooltip")
            .attr("data-bs-original-title", "Excel");
          $('[data-bs-toggle="tooltip"]').tooltip();
          $(".buscando_tabla").hide();
        },
        dataSrc: function (e) {
          if (e.status != true) {
            ver_errores(e);
          }
                    $('#tabla-reserva-total-general').html('<span class="badge bg-primary-transparent text-primary fw-semibold">' + (e.total_general_formateado || 'S/ 0.00') + '</span>');
          $("#tabla-reserva-deuda-general").html('<span class="badge bg-warning-transparent text-warning fw-semibold">' + (e.deuda_general_formateada || 'S/ 0.00') + '</span>');
          return e.aaData;
        },
      },
      createdRow: function (row, data, ixdex) {
        // columna: #
        if (data[0] != "") {
          $("td", row).eq(0).addClass("text-center");
        }
        // columna: #
        if (data[1] != "") {
          $("td", row).eq(1).addClass("text-nowrap text-center");
        }
        // columna: #
        if (data[2] != "") {
          $("td", row).eq(2).addClass("text-nowrap");
        }
        // columna: #
        if (data[3] != "") {
          $("td", row).eq(3).addClass("text-nowrap");
        }
        // columna: 5
        if (data[15] == 1) {
          $("td", row)
            .eq(1)
            .attr("data-bs-toggle", "tooltip")
            .attr("data-bs-original-title", "No tienes opcion a modificar");
        }
      },
      language: {
        lengthMenu: "_MENU_",
        search: "",
        searchPlaceholder: "Buscar registro",
        buttons: {
          copyTitle: "Tabla Copiada",
          copySuccess: { _: "%d lineas copiadas", 1: "1 linea copiada" },
        },
        sLoadingRecords:
          '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...',
      },
      bDestroy: true,
      iDisplayLength: 10,
      order: [[0, "asc"]],
      columnDefs: [
        {
          targets: 3,
          render: function (data, type) {
            const valor = (data || "").toString().trim().toUpperCase();
            const texto = valor === "SI" ? "Tours" : "Paquete";

            if (type !== "display") return texto;

            const badgeClass = valor === "SI" ? "bg-info-transparent" : "bg-primary-transparent";
            return `<span class="badge ${badgeClass}">${texto}</span>`;
          },
        },
      ],
    })
    .DataTable();
}

function valorDetalleReserva($row, selector) {
  return ($row.find(selector).val() || '').toString().trim();
}

function serializarDetallesToursReserva() {
  return $('#tabla-productos-seleccionados tbody tr').map(function () {
    const $row = $(this);
    return {
      idtours: valorDetalleReserva($row, '[name="id_select_tours[]"]'),
      nombre_tours: valorDetalleReserva($row, '[name="nombre_tours[]"]'),
      vehiculo: valorDetalleReserva($row, '[name="vehiculo[]"]'),
      idtours_turno: valorDetalleReserva($row, '[name="selecc_idtours_turno[]"]'),
      nro_pax: valorDetalleReserva($row, '[name="nro_pax_fila[]"]'),
      fecha_tours: valorDetalleReserva($row, '[name="fechaDetalle[]"]'),
      observacion: valorDetalleReserva($row, '[name="desc_detalle[]"]'),
      precio: valorDetalleReserva($row, '[name="precio_tours[]"]'),
      subtotal: valorDetalleReserva($row, '[name="subtotal_fila[]"]'),
    };
  }).get().filter(function (detalle) { return detalle.idtours; });
}

function serializarDetallesHotelReserva() {
  return $('#tabla-habitaciones-seleccionadas tbody tr').map(function () {
    const $row = $(this);
    return {
      idhotel: valorDetalleReserva($row, '[name="idhotel[]"]'),
      idhotel_habitacion: valorDetalleReserva($row, '[name="idhotel_habitacion[]"]'),
      nombre_habitacion: valorDetalleReserva($row, '[name="nombre_hotel_habitacion[]"]'),
      nro_pax: valorDetalleReserva($row, '[name="nro_pax[]"]'),
      cantidad_habitacion: valorDetalleReserva($row, '[name="cant_hab[]"]'),
      fecha_check_in: valorDetalleReserva($row, '[name="fechallegada_hotel[]"]'),
      fecha_check_out: valorDetalleReserva($row, '[name="fechasalida_hotel[]"]'),
      check_in: valorDetalleReserva($row, '[name="check_in[]"]'),
      nro_noches: valorDetalleReserva($row, '[name="noches[]"]'),
      precio: valorDetalleReserva($row, '[name="precio_coorporativo[]"]'),
      adicional: valorDetalleReserva($row, '[name="adicional[]"]'),
      observacion: valorDetalleReserva($row, '[name="observacion[]"]'),
      subtotal: valorDetalleReserva($row, '[name="subtotal_hab[]"]'),
    };
  }).get().filter(function (detalle) { return detalle.idhotel_habitacion; });
}

function quitarArraysDetalleReserva(formData) {
  [
    'id_select_tours[]', 'nombre_tours[]', 'vehiculo[]', 'selecc_idtours_turno[]', 'nro_pax_fila[]', 'fechaDetalle[]', 'desc_detalle[]', 'precio_tours[]', 'subtotal_fila[]',
    'idhotel[]', 'idhotel_habitacion[]', 'nombre_hotel_habitacion[]', 'nro_pax[]', 'cant_hab[]', 'fechallegada_hotel[]', 'fechasalida_hotel[]', 'check_in[]', 'noches[]', 'precio_coorporativo[]', 'adicional[]', 'observacion[]', 'subtotal_hab[]'
  ].forEach(function (name) { formData.delete(name); });
}

function prepararDetallesJsonReserva(formData) {
  formData.append('detalles_tours_json', JSON.stringify(serializarDetallesToursReserva()));
  formData.append('detalles_hotel_json', JSON.stringify(serializarDetallesHotelReserva()));
  quitarArraysDetalleReserva(formData);
}
function guardar_editar_reserva(e) {
  var formData = new FormData(e);
  var idreserva = $("#idreserva").val();
  var finalButtonHtml = idreserva
    ? '<i class="ri-save-2-line label-btn-icon me-2" ></i> Actualizar'
    : '<i class="ri-save-2-line label-btn-icon me-2" ></i> Guardar';

  prepararDetallesJsonReserva(formData);
  formData.append("itinerario_reserva", detalle_incluye ? detalle_incluye.root.innerHTML : "");

  if (idreserva) {
    formData.append("_method", "PUT");
  }

  $.ajax({
    url: idreserva ? reservaResourceUrl(idreserva, '/update') : apiUrlReserva(ReservaEndpoints.store),
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (e) {
      try {
        e = normalizarRespuestaReserva(e);
        if (e.status == true) {
          sw_success("Exito", e.message || "Reserva guardada correctamente.");
          tabla_reserva.ajax.reload(null, false);
          show_hide_form(1);
          limpiar_form_reserva();
        } else {
          ver_errores(e);
        }
      } catch (err) {
        toastr_error(
          "Error temporal!!",
          'Puede intentarlo mas tarde, o comuniquese con:<br> <i><a href="tel:+51921305769" >921-305-769</a></i> - <i><a href="tel:+51921487276" >921-487-276</a></i>',
          700
        );
      }
      $(".btn-guardar")
        .html(finalButtonHtml)
        .removeClass("disabled send-data");
    },
    xhr: function () {
      var xhr = new window.XMLHttpRequest();
      xhr.upload.addEventListener(
        "progress",
        function (evt) {
          if (evt.lengthComputable) {
            var percentComplete = (evt.loaded / evt.total) * 100;
            $("#barra_progress_tours").css({ width: percentComplete + "%" });
            $("#barra_progress_tours div").text(
              percentComplete.toFixed(2) + " %"
            );
          }
        },
        false
      );
      return xhr;
    },
    beforeSend: function () {
      setReservaSectionLoading('summary', true, 'Guardando reserva...');
      $(".btn-guardar")
        .html('<i class="fas fa-spinner fa-pulse fa-lg me-2"></i> Guardando...')
        .addClass("disabled send-data");
      $("#barra_progress_tours").css({ width: "0%" });
      $("#barra_progress_tours div").text("0%");
      $("#barra_progress_tours_div").show();
    },
    complete: function () {
      setReservaSectionLoading('summary', false);
      $("#barra_progress_tours").css({ width: "0%" });
      $("#barra_progress_tours div").text("0%");
      $("#barra_progress_tours_div").hide();
    },
    error: function (jqXhr, ajaxOptions, thrownError) {
      ver_errores(jqXhr);
    },
  });
}

function editarReserva(idreserva) {
  inicializarFormularioReserva(function () {
limpiar_form_reserva()
  permitirFechaLlegadaPasadaReserva(true);
  show_hide_form(2);
  $("#cargando-1-fomulario").hide();
  $("#cargando-2-fomulario").show();
  $.get(reservaResourceUrl(idreserva, '/show'), function (e, status) {
    
    e = normalizarRespuestaReserva(e);

      const turnos_tours = e.data.turnos_tours;

      $("#idreserva").val(e.data.idreserva);
      $("#codigo").val(e.data.numero_serie);
      cargarClientesReserva(function () { $("#idpersona_cliente").val(e.data.idpersona_cliente).trigger("change"); });
      $("#idorigenreserva").val(e.data.idorigen_reserva).trigger("change");
      $("#numero_pasajero").val(e.data.numero_pasajero);

      $("#llegada_fecha").val(e.data.llegada_fecha);
      $("#llegada_hora").val(e.data.llegadahora);
      $("#salida_fecha").val(e.data.salida_fecha);
      fecha_a_partir_fecha_llegada();

      $("#llegada_por_empresa").val(e.data.idllegada_por_empresa).trigger("change");
      $("#idasesorreserva").val(e.data.idpersona_trabajador).trigger("change");
      $("#detalle_ubicacion_r").val(e.data.observacion_recojo);
     
          if (detalle_incluye) detalle_incluye.root.innerHTML = e.data.itinerario_general;

      
      $("#idorigenreserva").val(e.data.idorigen_reserva).trigger("change");
      
      $("#vuelo_ticket").val(e.data.vuelo_ticket);
      $("#monto_compra_vuelo").val(e.data.vuelo_costo);
      actualizarResumenReserva();
      $("#obs_vuelo").val(e.data.vuelo_observacion);

      if (e.data.tours_reserva== "SI") {
        $("#es_tour_solo").prop("checked", true); // lo marca
      }else if (e.data.tours_reserva == "NO") {
        $("#es_tour_solo").prop("checked", false); // lo desmarca
      }

      // Variable para almacenar las filas HTML
      let filasHTML = '';

      // Iterar sobre cada elemento de reserva_detalle
      e.data.reserva_detalle.forEach((e, index) => {

        // Construye las opciones
        let opciones_turno = '<option value="">Seleccionar</option>';     

        turnos_tours.forEach(turno => {
          opciones_turno += `<option value="${turno.idtours_turno}" ${turno.idtours_turno == e.idtours_turno ? 'selected' : ''}>${turno.nombre}</option>`;
        });

        // Construir la fila HTML
        const fila = `
          <tr class="filas fila_validacion_${e.idreserva_detalle} fila_validacion_selecionado" id="fila_${e.idreserva_detalle}">
            <td><span class="btn btn-icon btn-sm btn-danger-light border-danger product-btn" onclick="eliminar_fila(${e.idreserva_detalle});"><i class="ri-delete-bin-line"></i></span></td>
            <td>
              <input type="hidden" class="form-control form-control-sm w-80" name="id_select_tours[]" id="id_select_tours[]" value="${e.idtours}">
              <input type="hidden" class="form-control form-control-sm w-80" name="nombre_tours[]" id="nombre_tours[]" value="${e.nombre_tours}">
              ${e.nombre_tours}
            </td>
            <td>
              <select class="form-select form-select-sm w-100 fs-11" name="vehiculo[]" id="valid_vehiculo_${e.idreserva_detalle}" required>
                <option value="Compartido" ${e.vehiculo === 'Compartido' ? 'selected' : ''}>Compartido</option>
                <option value="Privado" ${e.vehiculo === 'Privado' ? 'selected' : ''}>Privado</option>
              </select>
            </td>
            <td>
              <select class="form-select form-select-sm w-100 fs-11" name="selecc_idtours_turno[]" id="selecc_idtours_turno_${e.idreserva_detalle}" required>
                ${opciones_turno}
                <!-- Aquí podrías llenar opciones dinámicamente si es necesario -->
              </select>
            </td>
            <td><input type="text" class="form-control form-control-sm w-80 cantpax_xtours cantpax_xtours_${e.idreserva_detalle}" name="nro_pax_fila[]" id="nro_pax_fila_${e.idreserva_detalle}" value="${e.nro_pax}" onkeyup="subtotal_fila_xuno(${e.idreserva_detalle});"></td>
            <td><input type="date" class="form-control form-control-sm w-80" name="fechaDetalle[]" id="fechaDetalle_${e.idreserva_detalle}" value="${e.fecha_tours}"></td>
            <td><textarea class="textarea_datatable bg-light" name="desc_detalle[]" id="desc_detalle_${e.idreserva_detalle}">${e.observacion}</textarea></td>
            <td><input type="number" class="form-control form-control-sm w-120" name="precio_tours[]" id="precio_tours_${e.idreserva_detalle}" value="${e.precio}" readonly></td>
            <td><input type="number" class="form-control form-control-sm w-80" id="subtotal_fila_${e.idreserva_detalle}" name="subtotal_fila[]" value="${e.subtotal}"></td>
          </tr>
        `;
        

        array_data_filas.push({ id_cont: e.idreserva_detalle });
        
        // Agregar la fila al HTML acumulado
        filasHTML += fila;


      });

      // Ahora puedes insertar las filas generadas en tu tabla (por ejemplo, asumiendo que tu tabla tiene el id "tabla_tours")
      $("#tabla-productos-seleccionados tbody").append(filasHTML);

      //--------------------------------------------------
      //--------------------------------------------------
      //--------------------------------------------------

      let filasHotelHTML = '';

      e.data.hotel_detalle.forEach(e => {
        const idFila = e.idreserva_hotel;
        const idhotel = e.idhotel; 
        const noches = e.nro_noches;
        const llegada = e.fecha_check_in;
        const salida = e.fecha_check_out;
        const precio = e.precio;
        const sub_total = (e.precio * e.nro_noches * e.cantidad_habitacion) + Number(e.adicional || 0);

        filasHotelHTML += `
          <tr class="fila-hotel-${idhotel}_${e.idhotel_habitacion}">
            <td>
              <span class="btn btn-icon btn-sm btn-danger-light border-danger product-btn"
                onclick="eliminar_fila_habitacion('${idhotel}_${e.idhotel_habitacion}');">
                <i class="ri-delete-bin-line"></i>
              </span>
            </td>
            <td>
              <input type="hidden" name="idhotel[]" value="${idhotel}">
              <input type="hidden" name="idhotel_habitacion[]" value="${e.idhotel_habitacion}">
              <input type="hidden" name="nombre_hotel_habitacion[]" value="${e.nombre_habitacion}">
              <div class="d-flex flex-fill align-items-center">
                <div>
                  <h6 class="d-block fw-semibold fs-11 text-primary">Hotel</h6>
                  <span class="d-block fs-10 text-muted">Tipo Hab.: <b>${e.nombre_habitacion}</b></span>
                </div>
              </div>
            </td>
            <td><input type="number" class="form-control cantpax_hotel" name="nro_pax[]" min="0" value="${e.nro_pax || reservaPaxActual()}" readonly></td>
            <td><input type="number" class="form-control" id="cant_hab_${idhotel}_${e.idhotel_habitacion}" name="cant_hab[]" min="0" value="${e.cantidad_habitacion}" onkeyup="modificarsubtotalxfilahab('${idhotel}_${e.idhotel_habitacion}');" onchange="modificarsubtotalxfilahab('${idhotel}_${e.idhotel_habitacion}');"></td>
            <td><input type="date" class="form-control" value="${llegada}" id="fechallegada_hotel_${idhotel}_${e.idhotel_habitacion}" name="fechallegada_hotel[]" onchange="update_salida('${idhotel}_${e.idhotel_habitacion}');"></td>
            <td><input type="date" class="form-control" value="${salida}" id="fechasalida_hotel_${idhotel}_${e.idhotel_habitacion}" name="fechasalida_hotel[]" onchange="update_salida('${idhotel}_${e.idhotel_habitacion}');"></td>
            <td><input type="time" class="form-control" name="check_in[]" value="12:00"></td>
            <td><input type="number" class="form-control" name="noches[]" id="noches_${idhotel}_${e.idhotel_habitacion}" min="0" value="${noches}" readonly></td>
            <td><input type="number" class="form-control" id="precio_coorporativo_${idhotel}_${e.idhotel_habitacion}" name="precio_coorporativo[]" min="0" value="${precio}" step="0.01" readonly></td>
            <td><input type="number" class="form-control" id="adicional_${idhotel}_${e.idhotel_habitacion}" name="adicional[]" value="${e.adicional}" step="0.01" onkeyup="modificarsubtotalxfilahab('${idhotel}_${e.idhotel_habitacion}');" onchange="modificarsubtotalxfilahab('${idhotel}_${e.idhotel_habitacion}');"></td>
            <td><textarea class="textarea_datatable bg-light" name="observacion[]" id="observacion_${idhotel}_${e.idhotel_habitacion}">${e.observacion}</textarea></td>
            <td><input type="number" class="form-control subtotal" id="subtotal_hab_${idhotel}_${e.idhotel_habitacion}" name="subtotal_hab[]" min="0" value="${sub_total}" step="0.01"></td>
          </tr>
        `;

        array_data_filas_habit.push({ id_cont_h: `${idhotel}_${e.idhotel_habitacion}`});
        
      });

      // Insertar las filas generadas en el cuerpo de tu tabla de hoteles
      $("#tabla-habitaciones-seleccionadas tbody").append(filasHotelHTML);
    
      
      modificarSubtotales();
      modificarsubtotaleshab();
      toggleModoReserva();
      // Mostrar el total formateado
      $(".total_general").text(e.data.total_reserva);
      reservaTotalManual = true;
      $("#total_general_i").val(e.data.total_reserva);
      
      // detalle_incluye.setContents([]);
      if (e.data.brochure == null || e.data.brochure == "") {
      } else {
        if (
          UrlExists(`../assets/modulo/facturacion/ticket/${e.data.brochure}`) ==
          200
        ) {
          file_pond_brochure.addFile(
            `../assets/modulo/facturacion/ticket/${e.data.brochure}`,
            { index: 0 }
          );
        } else {
          toastr_error(
            "Erro de carga!!",
            `Hubo un error en la carga de tu comprobante de pago. <br> ${e.data.brochure}: ${e.data.brochure}`
          );
        }
      }

      $("#cargando-1-fomulario").show();
      $("#cargando-2-fomulario").hide();
      $("#form-agregar-reserva").valid();
    }
  );

  });
}

function mostrar_detalle_tours(idtours) {
  $("#modal-ver-detalle-producto").modal("show");
  $.get(
    apiUrlReserva(`${ReservaEndpoints.tours}/${idtours}/detalle`),
    function (e, status) {
      e = normalizarRespuestaReserva(e);
      if (e.status == true) {
        $(".detalle_tours").html(e.data);
        $(".detalle_tours").html(e.data);
        file_pond_brochure.addFile(
          `../assets/modulo/facturacion/ticket/${e.data.brochure}`,
          { index: 0 }
        );
      } else {
        ver_errores(e);
      }
    }
  ).fail(function (e) {
    ver_errores(e);
  });
}

function eliminarReserva(idreserva, nombre = "esta reserva") {
  nombre = nombre || "esta reserva";
  $(".tooltip").remove();

  const confirmar = function () {
    $.ajax({
      url: reservaResourceUrl(idreserva),
      type: "DELETE",
      headers: ajaxHeadersReserva(),
      success: function (response) {
        response = normalizarRespuestaReserva(response);

        if (response.status == true) {
          sw_success("Reserva eliminada", response.message || "Reserva enviada a papelera.");
          tabla_reserva.ajax.reload(null, false);
          return;
        }

        ver_errores(response);
      },
      error: function (xhr) {
        ver_errores(xhr);
      },
    });
  };

  if (typeof Swal === "undefined") {
    confirmar();
    return;
  }

  Swal.fire({
    title: "Eliminar reserva",
    html: `<b class="text-danger"><del>${nombre}</del></b><br>La reserva se enviara a papelera.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Si, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc3545",
  }).then(function (result) {
    if (result.isConfirmed) confirmar();
  });
}

function mostrar_reserva(idreserva) {
  return editarReserva(idreserva);
}

function eliminar_papelera_producto(idreserva, nombre = "esta reserva") {
  return eliminarReserva(idreserva, nombre);
}
function inicializarModuloReserva() {
  inicializarEventosReserva();
  mostrarPasoReserva('general');
  actualizarResumenReserva();
  inicializarValidacionesReserva();
  inicializarBuscadorTours();
  inicializarEdicionTotalReserva();
}

$(inicializarModuloReserva);
function init() {
  return inicializarEventosReserva();
}

function mayus(e) {
  e.value = e.value.toUpperCase();
}

function mostrarModalReserva(selector) {
  const element = document.querySelector(selector);
  if (!element) return;

  if (window.bootstrap?.Modal) {
    bootstrap.Modal.getOrCreateInstance(element).show();
    return;
  }

  if (typeof $ === 'function' && typeof $(element).modal === 'function') {
    $(element).modal('show');
    return;
  }

  element.classList.add('show');
  element.removeAttribute('aria-hidden');
  element.setAttribute('aria-modal', 'true');
  element.setAttribute('role', 'dialog');
  element.style.display = 'block';
  document.body.classList.add('modal-open');

  if (!document.querySelector('.modal-backdrop')) {
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    document.body.appendChild(backdrop);
  }
}

function ocultarModalReserva(selector) {
  const element = document.querySelector(selector);
  if (!element) return;

  if (window.bootstrap?.Modal) {
    bootstrap.Modal.getOrCreateInstance(element).hide();
    return;
  }

  if (typeof $ === 'function' && typeof $(element).modal === 'function') {
    $(element).modal('hide');
    return;
  }

  element.classList.remove('show');
  element.setAttribute('aria-hidden', 'true');
  element.removeAttribute('aria-modal');
  element.removeAttribute('role');
  element.style.display = 'none';
  document.body.classList.remove('modal-open');
  document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
}

function actualizarReglaNuevoCliente(selector, action, rules) {
  const $field = $(selector);
  const validatorReady = $('#form-agregar-nuevo-cliente').data('validator');
  if (!$field.length || !validatorReady || typeof $field.rules !== 'function') return;

  if (action === 'remove') {
    $field.rules('remove', rules);
    return;
  }

  $field.rules('add', rules);
}

function generarcodigonarti() {
  var name_producto =
    $("#nombre").val() == null || $("#nombre").val() == ""
      ? ""
      : $("#nombre").val();
  if (name_producto == "") {
    toastr_warning(
      "Vacio!!",
      "El nombre esta vacio, digita para completar el codigo aletarorio.",
      700
    );
  }
  name_producto = name_producto.substring(-3, 3);
  var cod_letra = Math.random().toString(36).substring(2, 5);
  var cod_number =
    Math.floor(Math.random() * 10) + "" + Math.floor(Math.random() * 10);
  $("#codigo_alterno").val(
    `${name_producto.toUpperCase()}${cod_number}${cod_letra.toUpperCase()}`
  );
}

function create_code_tours(pre_codigo) {
  $(".charge_codigo").html(
    `<div class="spinner-border spinner-border-sm" role="status"></div>`
  );

  $.getJSON(
    reservaCatalogoUrl(ReservaEndpoints.codigo, { pre_codigo }),
    function (e, textStatus, jqXHR) {
      if (e.status == true) {
        $("#codigo").val(e.data.nombre_codigo);
        $("#codigo").attr("readonly", "readonly").addClass("bg-light"); // Mantiene el codigo solo lectura
        add_tooltip_custom("#codigo", "No se puede editar"); //  Agrega tooltip personalizado a un element
        $(".charge_codigo").html(""); // limpiamos la carga
      } else {
        ver_errores(e);
      }
    }
  ).fail(function (jqxhr, textStatus, error) {
    ver_errores(jqxhr);
  });
}


function limpiar_nuevo_cliente() {
  $('#cli_tipo_persona_sunat').val('NATURAL');
  $('#cli_tipo_persona_sunat_select').val('NATURAL').trigger('change');
  $('#cli_tipo_documento').val('1').trigger('change');
  $('#cli_numero_documento').val('').removeClass('bg-dark-transparent').prop('readonly', false);
  $('#cli_nombre_razonsocial').val('');
  $('#cli_apellidos_nombrecomercial').val('');
  $('#cli_nombre_persona_natural').val('');
  $('#cli_apellido_paterno_persona_natural').val('');
  $('#cli_apellido_materno_persona_natural').val('');
  $('#cli_sexo').val('M').trigger('change');
  $('#cli_fecha_nacimiento').val('');
  $('#cli_nacionalidad').val('PERUANO').trigger('change');
  $('#cli_estado_civil').val('SOLTERO').trigger('change');
  $('#cli_correo').val('');
  $('#cli_celular').val('');
  $('#cli_direccion').val('');
  $('#cli_direccion_referencia').val('');
  $('#cli_departamento').val('');
  $('#cli_provincia').val('');
  $('#cli_ubigeo').val('');
  $('#cli_distrito').val(null).trigger('change');
  actualizarCamposNuevoCliente();
  $('#form-agregar-nuevo-cliente .is-invalid, #form-agregar-nuevo-cliente .is-valid').removeClass('is-invalid is-valid');
  $('#form-agregar-nuevo-cliente .invalid-feedback').remove();
  $('#form-agregar-nuevo-cliente .select2-selection').removeClass('is-invalid is-valid');
  if ($('#form-agregar-nuevo-cliente').data('validator')) {
    $('#form-agregar-nuevo-cliente').validate().resetForm();
  }
}
function modal_add_nuevo_cliente(tipo) {
  inicializarClienteReserva();
  TipoClienteGlobal = tipo; // 1: cliente Pago ClientePago, 2: vista adda reserva Clienteadd
  mostrarModalReserva('#modal-agregar-nuevo-cliente');

  try {
    limpiar_nuevo_cliente();
  } catch (error) {
    console.error('No se pudo limpiar el formulario de cliente.', error);
  }
}

window.modal_add_nuevo_cliente = modal_add_nuevo_cliente;
window.limpiar_nuevo_cliente = limpiar_nuevo_cliente;

function guardar_editar_nuevo_cliente(e){ 
  var formData = new FormData($("#form-agregar-nuevo-cliente")[0]);
  $.ajax({
    url: apiUrlReserva(ReservaEndpoints.clientesStore),
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (e) {
      try {
        e = normalizarRespuestaReserva(e); 
        if (e.status == true) {
          
          Swal.fire("Correcto!", "El registro se guardo exitosamente.", "success"); 

          if (TipoClienteGlobal == 'Clienteadd') {
            lista_select2(apiUrlReserva(ReservaEndpoints.clientes), "#idpersona_cliente", e.data,'.charge_cliente_reserva' );
          } else {
            lista_select2(apiUrlReserva(ReservaEndpoints.clientes), "#p_idpersona_cliente", e.data,'.charge_cliente_reserva' );
          }

           
          limpiar_nuevo_cliente();
          ocultarModalReserva('#modal-agregar-nuevo-cliente');
        } else { ver_errores(e); }
      } catch (err) { toastr.error('<h5 class="font-size-16px">Error temporal!!</h5> puede intentarlo mas tarde, o comuniquese con <i><a href="tel:+51921305769" >921-305-769</a></i> - <i><a href="tel:+51921487276" >921-487-276</a></i>'); }
      $("#guardar_registro_nuevo_cliente").html('Guardar Cambios').removeClass('disabled send-data');
    },
    xhr: function () {
			var xhr = new window.XMLHttpRequest();
			xhr.upload.addEventListener("progress", function (evt) {
				if (evt.lengthComputable) {
					var percentComplete = (evt.loaded / evt.total) * 100;
					$("#barra_progress_nuevo_cliente").css({ "width": percentComplete + '%' });
					$("#barra_progress_nuevo_cliente div").text(percentComplete.toFixed(2) + " %");
				}
			}, false);
			return xhr;
		},
    beforeSend: function () {
      $("#guardar_registro_nuevo_cliente").html('<i class="fas fa-spinner fa-pulse fa-lg"></i>').addClass('disabled send-data');
      $("#barra_progress_nuevo_cliente").css({ width: "0%", });
      $("#barra_progress_nuevo_cliente div").text("0%");
      $("#barra_progress_nuevo_cliente_div").show();
    },
    complete: function () {
      $("#barra_progress_nuevo_cliente").css({ width: "0%", });
      $("#barra_progress_nuevo_cliente div").text("0%");
      $("#barra_progress_nuevo_cliente_div").hide();
    },
    error: function (jqXhr, ajaxOptions, thrownError) {
      const resp = jqXhr.responseJSON || {};
      let errorDocumento = resp?.errors?.cli_numero_documento || resp?.data?.cli_numero_documento;
      if (Array.isArray(errorDocumento)) errorDocumento = errorDocumento[0];
      if (jqXhr.status === 422 && errorDocumento) {
        Swal.fire('Documento ya registrado', errorDocumento, 'warning').then(() => {
          $('#cli_numero_documento').addClass('is-invalid').trigger('focus');
        });
        return;
      }
      ver_errores(jqXhr);
    }
  });
}

let sincronizandoNuevoCliente = false;

function setValorSelect2NuevoCliente(selector, value) {
  const $select = $(selector);
  $select.val(value);
  if ($select.hasClass('select2-hidden-accessible')) {
    $select.trigger('change.select2');
  }
}

function actualizarCamposNuevoCliente() {
  const tipoPersona = ($('#cli_tipo_persona_sunat').val() || 'NATURAL').toUpperCase();
  const tipoDocumento = ($('#cli_tipo_documento').val() || '').toString();
  const esJuridica = tipoPersona === 'JURIDICA';

  $('.div_nombre_comercial').toggle(esJuridica);
  $('.div_nombre_persona_natural').toggle(!esJuridica);
  $('.div_apellido_paterno_persona_natural').toggle(!esJuridica);
  $('.div_apellido_materno_persona_natural').toggle(!esJuridica);
  $('.div_sexo').toggle(!esJuridica);
  $('.div_fecha_nacimiento').toggle(!esJuridica);
  $('.div_nacionalidad').toggle(!esJuridica);

  $('.label-nom-raz').html(esJuridica ? 'Razon Social <sup class="text-danger">*</sup>' : 'Descripcion <sup class="text-danger">*</sup>');
  $('.label-ape-come').html('Nombre comercial');

  actualizarReglaNuevoCliente('#cli_apellidos_nombrecomercial', 'remove', 'required');
  $('#cli_apellidos_nombrecomercial').removeClass('is-invalid is-valid');

  $('#cli_numero_documento').removeClass('bg-dark-transparent').prop('readonly', false);
  if (tipoDocumento === '1') {
    actualizarReglaNuevoCliente('#cli_numero_documento', 'add', {
      required: true,
      minlength: 8,
      maxlength: 8,
      messages: { required: 'Campo requerido', minlength: 'Minimo {0}', maxlength: 'Maximo {0}' }
    });
  }

  if ($('#form-agregar-nuevo-cliente').data('validator')) {
    $('#form-agregar-nuevo-cliente').valid();
  }
}

$('#cli_tipo_persona_sunat_select').on('change', function () {
  if (sincronizandoNuevoCliente) return;
  sincronizandoNuevoCliente = true;

  const tipoPersona = ($(this).val() || 'NATURAL').toUpperCase();
  $('#cli_tipo_persona_sunat').val(tipoPersona);

  if (tipoPersona === 'JURIDICA') {
    setValorSelect2NuevoCliente('#cli_tipo_documento', '6');
  } else if ($('#cli_tipo_documento').val() === '6') {
    setValorSelect2NuevoCliente('#cli_tipo_documento', '1');
  }

  sincronizandoNuevoCliente = false;
  actualizarCamposNuevoCliente();
});

$('#cli_tipo_documento').on('change', function () {
  if (sincronizandoNuevoCliente) return;
  sincronizandoNuevoCliente = true;

  const tipoDocumento = ($(this).val() || '').toString();
  const tipoPersona = tipoDocumento === '6' ? 'JURIDICA' : 'NATURAL';
  $('#cli_tipo_persona_sunat').val(tipoPersona);
  setValorSelect2NuevoCliente('#cli_tipo_persona_sunat_select', tipoPersona);

  sincronizandoNuevoCliente = false;
  actualizarCamposNuevoCliente();
});
$('#cli_distrito').on('change', async function () {  

  $(".chargue-pro").html(`<div class="spinner-border spinner-border-sm" role="status" ></div>`);
  $(".chargue-dep").html(`<div class="spinner-border spinner-border-sm" role="status" ></div>`);
  $(".chargue-ubi").html(`<div class="spinner-border spinner-border-sm" role="status" ></div>`);

  await esperar(1000);  // Espera 5 segundos  

  if ($('#cli_distrito').val()== null || $('#cli_distrito').val() == '') {
    $("#cli_departamento").val("");
    $("#cli_provincia").val("");
    $("#cli_ubigeo").val("");
    $(".chargue-pro").html(''); $(".chargue-dep").html(''); $(".chargue-ubi").html('');
  } else {
    var iddistrito = $('#cli_distrito').val();
    $.get(apiUrlReserva(`${ReservaEndpoints.distritos}/${iddistrito}`), function (e) {
      e = normalizarRespuestaReserva(e); 
      $("#cli_departamento").val(e.data.departamento);
      $("#cli_provincia").val(e.data.provincia);
      $("#cli_ubigeo").val(e.data.ubigeo_inei);

      $(".chargue-pro").html(''); $(".chargue-dep").html(''); $(".chargue-ubi").html('');
      
      $("#form-agregar-nuevo-cliente").valid();
    });
  }
});



function limpiar_nuevo_pago() {
  $('#idrdocumento_pago').val('');
  $('#f_metodo_pago_1').val('').trigger('change');
  if (f_metodo_pago_1 && typeof f_metodo_pago_1.removeActiveItems === 'function') {
    f_metodo_pago_1.removeActiveItems();
  }
  $('#monto_amortizar').val('');
  $('#saldo_amortizar').val('');
  $('#observacion_amortizar').val('');
  $('#detalle_comprobante_amortizar').val('');
  $('#guardar_registro_nuevo_pago_reserva').html('<i class="bx bx-check"></i> AMORTIZAR');
}

function abrirModalImpresionReserva(idDocumento, comprobante = '') {
  const id = Number(idDocumento || 0);
  if (!Number.isFinite(id) || id <= 0) {
    toastr_error('Error', 'No se pudo identificar el comprobante para imprimir.');
    return;
  }

  const urls = {
    a4: apiUrlReserva(ReservaEndpoints.impresionComprobante(id, 'a4')),
    ticket: apiUrlReserva(ReservaEndpoints.impresionComprobante(id, 'ticket')),
  };

  $('#modal-impresion-reserva-subtitle').text(['Comprobante de pago', comprobante].filter(Boolean).join(' - '));
  $('#reserva-print-a4-frame').attr('src', urls.a4);
  $('#reserva-print-ticket-frame').attr('src', urls.ticket);
  $('.js-abrir-formato-reserva[data-formato="a4"]').attr('href', urls.a4);
  $('.js-abrir-formato-reserva[data-formato="ticket"]').attr('href', urls.ticket);
  $('#reserva-print-a4-tab').trigger('click');

  const modal = document.getElementById('modal-impresion-reserva');
  if (modal && typeof bootstrap !== 'undefined') {
    bootstrap.Modal.getOrCreateInstance(modal).show();
    return;
  }

  $('#modal-impresion-reserva').modal('show');
}

function imprimirFormatoReserva(formato) {
  const frameId = formato === 'ticket' ? 'reserva-print-ticket-frame' : 'reserva-print-a4-frame';
  const frameWindow = document.getElementById(frameId)?.contentWindow;
  if (!frameWindow) {
    toastr_error('Error', 'No se pudo cargar el formato de impresion.');
    return;
  }

  frameWindow.focus();
  frameWindow.print();
}

function guardar_editar_nuevo_pago(e){ 
  var idPago = $("#idrdocumento_pago").val();
  var formData = new FormData($("#form-amortizar-reserva")[0]);
  var urlPago = idPago ? apiUrlReserva(`${ReservaEndpoints.pagos}/${idPago}`) : apiUrlReserva(ReservaEndpoints.pagosStore);

  if (idPago) {
    formData.append("_method", "PUT");
  }

  $.ajax({
    url: urlPago,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (e) {
      try {
        e = normalizarRespuestaReserva(e); 
        if (e.status == true) {
          Swal.fire("Correcto!", e.message || (idPago ? "El pago se actualizo exitosamente." : "La amortizacion se registro exitosamente."), "success"); 
          ocultarModalReserva('#modal-amortizar-reserva');
          limpiar_nuevo_pago();
          if (e.data && e.data.idreserva) { idreserva_para_amortizar = e.data.idreserva; }
          mostrar_detalle(idreserva_para_amortizar,nombre_cliente_para_amortizar,apellido_cliente_para_amortizar,documento_cliente_para_amortizar,doc_cliente_para_amortizar);
          if (typeof tabla_reserva !== 'undefined' && tabla_reserva) {
            tabla_reserva.ajax.reload(null, false);
          }
          if (!idPago && e.data && e.data.idrdocumento) {
            abrirModalImpresionReserva(e.data.idrdocumento, e.data.comprobante || '');
          }
        } else { ver_errores(e); }
      } catch (err) { toastr.error('<h5 class="font-size-16px">Error temporal!!</h5> puede intentarlo mas tarde, o comuniquese con <i><a href="tel:+51921305769" >921-305-769</a></i> - <i><a href="tel:+51921487276" >921-487-276</a></i>'); }
    },
    xhr: function () {
      var xhr = new window.XMLHttpRequest();
      xhr.upload.addEventListener("progress", function (evt) {
        if (evt.lengthComputable) {
          var percentComplete = (evt.loaded / evt.total) * 100;
          $("#barra_progress_nuevo_cliente").css({ "width": percentComplete + '%' });
          $("#barra_progress_nuevo_cliente div").text(percentComplete.toFixed(2) + " %");
        }
      }, false);
      return xhr;
    },
    beforeSend: function () {
      $("#guardar_registro_nuevo_pago_reserva").html('<i class="fas fa-spinner fa-pulse fa-lg me-1"></i> Guardando...').addClass('disabled send-data');
      $("#barra_progress_nuevo_cliente").css({ width: "0%", });
      $("#barra_progress_nuevo_cliente div").text("0%");
      $("#barra_progress_nuevo_cliente_div").show();
    },
    complete: function () {
      var textoBotonPago = idPago ? '<i class="bx bx-check"></i> ACTUALIZAR' : '<i class="bx bx-check"></i> AMORTIZAR';
      $("#guardar_registro_nuevo_pago_reserva").html(textoBotonPago).removeClass('disabled send-data');
      $("#barra_progress_nuevo_cliente").css({ width: "0%", });
      $("#barra_progress_nuevo_cliente div").text("0%");
      $("#barra_progress_nuevo_cliente_div").hide();
    },
    error: function (jqXhr, ajaxOptions, thrownError) {
      ver_errores(jqXhr);
    }
  });
}

function inicializarValidacionesReserva() {
  $("#idpersona_cliente").on("change", function () { $(this).trigger("blur"); });
  $("#idorigenreserva").on("change", function () { $(this).trigger("blur"); });
  $("#idllegada_por").on("change", function () { $(this).trigger("blur"); });
  $("#llegada_por_empresa").on("change", function () { $(this).trigger("blur"); });
  $("#idasesorreserva").on("change", function () { $(this).trigger("blur"); });

  //  :::::::::::::::::::: F O R M U L A R I O   T O U R S ::::::::::::::::::::
  $("#form-agregar-reserva").validate({
    ignore: ".ql-editor",
    rules: {

      idpersona_cliente: { required: true },
      idorigenreserva: { required: true },
      numero_pasajero: { required: true },
      llegada_fecha: {
        required: function () {
          return !$("#es_tour_solo").is(":checked");
        },
      },
      llegada_hora: {
        required: function () {
          return !$("#es_tour_solo").is(":checked");
        },
      },
      salida_fecha: {
        required: function () {
          return !$("#es_tour_solo").is(":checked");
        },
      },
      idllegada_por: { required: true },
      llegada_por_empresa: { required: true },
      idasesorreserva: { required: true },
      codigo: {
        required: true,
        minlength: 4,
        maxlength: 20,
        remote: {
          url: apiUrlReserva(ReservaEndpoints.validarCodigo),
          type: "get",
          data: {
            action: function () {
              return "validar_codigo";
            },
            idreserva: function () {
              var idreserva = $("#idreserva").val();
              return idreserva;
            },
          },
        },
      },
    },
    messages: {
      idpersona_cliente: { required: "Campo requerido" },
      idorigenreserva: { required: "Campo requerido" },
      numero_pasajero: { required: "Campo requerido" },
      llegada_fecha: { required: "Campo requerido", },
      llegada_hora: { required: "Campo requerido", },
      salida_fecha: { required: "Campo requerido", },
      idllegada_por: { required: "Campo requerido", },
      llegada_por_empresa: { required: "Campo requerido", },
      idasesorreserva: { required: "Campo requerido", },

      codigo: { required: "Campo requerido", remote: "Codigo en uso." },
    },

    errorElement: "span",

    errorPlacement: function (error, element) {
      colocarErrorReserva(error, element);
    },

    highlight: function (element, errorClass, validClass) {
      marcarCampoReserva(element, true);
    },

    unhighlight: function (element, errorClass, validClass) {
      marcarCampoReserva(element, false);
    },
    invalidHandler: function (event, validator) {
      if (validator.errorList.length) {
        var $field = $(validator.errorList[0].element);
        mostrarPasoReserva(pasoDeCampoReserva($field));
        setTimeout(function () {
          $("html, body").animate({ scrollTop: $field.closest(".form-group").offset().top - 120 }, 300);
          $field.trigger("focus");
        }, 80);
      }
    },
    submitHandler: function (e) {
      const soloTours = $("#es_tour_solo").is(":checked");

        if (soloTours) {
          // Remover validaciones innecesarias
          $("#llegada_fecha").rules("remove");
          $("#llegada_hora").rules("remove");
          $("#salida_fecha").rules("remove");
          $("#select_idhotel").rules("remove");
          //$("#vuelo_ticket").rules("remove");
          $("#monto_compra_vuelo").rules("remove");

        } else {
          // Agregar validaciones
          $("#salida_fecha").rules("add", { required: true, messages: { required: "Campo requerido" } });
          $("#select_idhotel").rules("add", { required: true, messages: { required: "Campo requerido" } });
          //$("#vuelo_ticket").rules("add", { required: true, messages: { required: "Campo requerido" } });
          $("#monto_compra_vuelo").rules("add", {number: true, min: 0, messages: { required: "Minimo 0" } });
        }

        if (!validar_cant_pax()) { return false; }
        guardar_editar_reserva(e);
    },
  });

  //  :::::::::::::::::::: F O R M U L A R I O   C L I E N T E ::::::::::::::::::::
  $("#form-agregar-nuevo-cliente").validate({
    errorClass: 'is-invalid',
    validClass: 'is-valid',
    ignore: ".select2-search__field, .select2-input, .select2-focusser",
    rules: {
      cli_tipo_persona_sunat:        { required: true },
      cli_tipo_documento:            { required: true, minlength: 1, maxlength: 2 },
      cli_numero_documento:          { required: true, minlength: 4, maxlength: 20 },
      cli_nombre_razonsocial:        { required: true, minlength: 4, maxlength: 200 },
      cli_correo:                    { minlength: 4, maxlength: 100 },
      cli_celular:                   { minlength: 8, maxlength: 9 },
      cli_direccion:                 { required: true, minlength: 4, maxlength: 200 },
      cli_direccion_referencia:      { minlength: 4, maxlength: 200 },
      cli_fecha_nacimiento:          { date: true }
    },
    messages: {
      cli_tipo_persona_sunat:        { required: "Campo requerido" },
      cli_tipo_documento:            { required: "Campo requerido" },
      cli_numero_documento:          { required: "Campo requerido" },
      cli_nombre_razonsocial:        { required: "Campo requerido" },
      cli_correo:                    { minlength: "Minimo {0} caracteres." },
      cli_celular:                   { minlength: "Minimo {0} caracteres." },
      cli_direccion:                 { required: "Campo requerido", minlength: "Minimo {0} caracteres.", maxlength: "Maximo {0} caracteres." },
      cli_direccion_referencia:      { minlength: "Minimo {0} caracteres.", maxlength: "Maximo {0} caracteres." }
    },
    errorElement: "div",

    errorPlacement: function (error, element) {
      error.addClass("invalid-feedback");

      if ($(element).hasClass('select2-hidden-accessible')) {
        const $container = $(element).nextAll('.select2-container').first();
        const $column = $(element).closest('[class*="col-"]');
        if ($column.length) {
          error.appendTo($column);
        } else {
          error.insertAfter($container.length ? $container : element);
        }
        return;
      }

      const $inputGroup = $(element).closest('.input-group');
      if ($inputGroup.length) {
        error.insertAfter($inputGroup);
        return;
      }

      element.closest(".form-group").append(error);
    },

    highlight: function (element, errorClass, validClass) {
      $(element).addClass("is-invalid").removeClass("is-valid");
      if ($(element).hasClass('select2-hidden-accessible')) {
        $(element).nextAll('.select2-container').first().find('.select2-selection').addClass('is-invalid').removeClass('is-valid');
      }
    },

    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass("is-invalid").addClass("is-valid");
      if ($(element).hasClass('select2-hidden-accessible')) {
        $(element).nextAll('.select2-container').first().find('.select2-selection').removeClass('is-invalid').addClass('is-valid');
      }
    },

    invalidHandler: function (event, validator) {
      if (!validator.errorList.length) return;

      const firstInvalid = validator.errorList[0].element;
      const tabPane = $(firstInvalid).closest('.tab-pane');
      if (tabPane.length) {
        const tabTrigger = document.querySelector(`[data-bs-target="#${tabPane.attr('id')}"]`);
        if (tabTrigger) bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
      }

      setTimeout(function () {
        $(firstInvalid).trigger('focus');
      }, 150);
    },

    submitHandler: function (e) {
      $(".modal-body").animate({ scrollTop: $(document).height() }, 600); // Desplaza el modal hasta abajo
      guardar_editar_nuevo_cliente(e);
    },
  });

  $("#form-amortizar-reserva").validate({
    ignore: "",
    rules: {           
      f_serie_comprobante:           { required: true },       
      p_idpersona_cliente:           { required: true },       
      f_metodo_pago_1:    			     { required:true  },       
      monto_amortizar:    	         { required: true, },       			
     
    },
    messages: {     
      f_serie_comprobante:    			  { required: "Campo requerido", },
      p_idpersona_cliente:          { required: "Campo requerido", },
      f_metodo_pago_1:    		      	{ required: "Campo requerido", }, 
      monto_amortizar:    	        	{ required: "Campo requerido", }, 
    },
        
    errorElement: "span",

    errorPlacement: function (error, element) {
      error.addClass("invalid-feedback");
      element.closest(".form-group").append(error);
    },

    highlight: function (element, errorClass, validClass) {
      $(element).addClass("is-invalid").removeClass("is-valid");
    },

    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass("is-invalid").addClass("is-valid");   
    },
    submitHandler: function (e) {
      $(".modal-body").animate({ scrollTop: $(document).height() }, 600); // Desplaza el modal hasta abajo
      guardar_editar_nuevo_pago(e);      
    },
  });




  $("#idpersona_cliente").rules("add", { required: true, messages: { required: "Campo requerido" },  });
  $("#idorigenreserva").rules("add", { required: true, messages: { required: "Campo requerido" },});
  $("#idllegada_por").rules("add", { required: true, messages: { required: "Campo requerido" },});
  $("#llegada_por_empresa").rules("add", { required: true, messages: { required: "Campo requerido" },});
  $("#idasesorreserva").rules("add", { required: true, messages: { required: "Campo requerido" },});


}

// .....::::::::::::::::::::::::::::::::::::: F U N C I O N E S    A L T E R N A S  :::::::::::::::::::::::::::::::::::::::..

function select_cuidad() {
  var distrito = $("#idpersona_cliente").select2("val");

  // filtro de fechas
  if (distrito == "" || distrito == 0 || distrito == null) {
    $("#prov_dep").val(distrito);
  } else {
    $("#prov_dep").val(distrito);
  }
}

// .....::::::::::::::::::::::::::::::::::::: F U N C I O N  L L E G A D A  P O R  :::::::::::::::::::::::::::::::::::::::..

function llegada_por() {
  
  var idllegada_por = $("#idllegada_por").select2("val");

  if (idllegada_por == "" || idllegada_por == 0 || idllegada_por == null) {
    lista_select2( reservaCatalogoUrl(ReservaEndpoints.llegadaEmpresas, { idllegada_por }), "#llegada_por_empresa", "1" );
  } else {
    if (idllegada_por ==1) {
      lista_select2( reservaCatalogoUrl(ReservaEndpoints.llegadaEmpresas, { idllegada_por }), "#llegada_por_empresa", "1" );
      

    } else if (idllegada_por ==2) {
      lista_select2( reservaCatalogoUrl(ReservaEndpoints.llegadaEmpresas, { idllegada_por }), "#llegada_por_empresa", "2" );
    } else {
      
      lista_select2( reservaCatalogoUrl(ReservaEndpoints.llegadaEmpresas, { idllegada_por }), "#llegada_por_empresa", null );
      $(".llegada_por_empresas").val("1").trigger("change");
     }
  }

  $(".llegada_por_empresas").val("1").trigger("change");
}



function cambiarImagen() {
  var imagenInput = document.getElementById("imagenProducto");
  imagenInput.click();
}

function removerImagen() {
  $("#imagenmuestraProducto").attr(
    "src",
    "../assets/modulo/productos/no-producto.png"
  );
  $("#imagenProducto").val("");
  $("#imagenactualProducto").val("");
}

function ver_img(img, nombre) {
  $(".title-modal-img").html(`-${nombre}`);
  $("#modal-ver-img").modal("show");
  $(".html_ver_img").html(
    doc_view_extencion(img, "assets/modulo/productos", "100%", "550")
  );
  $(`.jq_image_zoom`).zoom({ on: "grab" });
}

function reload_idubigeo_distrito() {
  lista_select2(
    apiUrlReserva('/select2/select2distrito'),
    "#ubigeo_distrito",
    null,
    ".charge_idubigeo_distrito"
  );
}

function reload_cliente_reserva() {

  lista_select2(apiUrlReserva(ReservaEndpoints.clientes), "#idpersona_cliente", null , ".charge_cliente_reserva");
}

function reload_llegadapor_reserva() {

  lista_select2( apiUrlReserva(ReservaEndpoints.llegadaTipos), "#idllegada_por", null, ".charge_idllegada_por" );

}

function reload_llegada_por_empresa_reserva() {

  lista_select2( reservaCatalogoUrl(ReservaEndpoints.llegadaEmpresas, { idllegada_por: 1 }), "#llegada_por_empresa", null, ".charge_llegada_por_empresa" );

}

function setDefaultLlegadaPorEmpresa(valorPorDefecto = "1") {
  const targetNode = document.getElementById("llegada_por_empresa");

  if (!targetNode) return;

  const observer = new MutationObserver(function (mutationsList, observer) {
    const tieneOpcion = Array.from(targetNode.options).some(opt => opt.value === valorPorDefecto);

    if (tieneOpcion) {
      $("#llegada_por_empresa").val(valorPorDefecto).trigger("change");
      observer.disconnect(); // Detiene la observacion una vez aplicado
    }
  });

  observer.observe(targetNode, {
    childList: true,
    subtree: true,
  });
}

(function () {
  "use strict";

  // UPLOADS ===================================

  /* filepond */
  FilePond.registerPlugin(
    FilePondPluginImagePreview,
    FilePondPluginImageExifOrientation,
    FilePondPluginFileValidateSize,
    FilePondPluginFileEncode,
    FilePondPluginImageEdit,
    FilePondPluginFileValidateType,
    FilePondPluginImageCrop,
    FilePondPluginImageResize,
    FilePondPluginImageTransform
  );

  // Configura opciones globales para FilePond
  FilePond.setOptions({
    allowMultiple: false, // Permite un solo archivo
    maxFiles: 1, // Numero maximo de archivos permitidos
    maxFileSize: "3MB", // Tamano maximo por archivo
    acceptedFileTypes: ["image/*", "application/pdf"], // Tipos permitidos
    // server: {
    //     revert: null, // URL para revertir la subida (opcional)
    //     headers: {
    //     }
    // }
  });

  /* multiple upload */
  const MultipleElement = document.querySelector(".multiple-filepond");
  file_pond_brochure = FilePond.create(
    MultipleElement,
    FilePond_Facturacion_LabelsES
  );


    /* multiple upload */
  const MultipleElement_pago = document.querySelector(".multiple-filepond_pago");
  file_pond_brochure_pago = FilePond.create(
    MultipleElement_pago,
    FilePond_Facturacion_LabelsES
  );
  //filePondInstances.push(file_pond_brochure); // Guarda la instancia en el arreglo
  // Ensure mediumZoom is available before using it
  // document.addEventListener("DOMContentLoaded", function() {
  //   file_pond_brochure.on('addfile', (error, file) => {
  //     if (!error) {
  //       setTimeout(() => {
  //         mediumZoom('.filepond--image-preview');
  //       }, 100); // Delay to ensure image is rendered
  //     }
  //   });
  // });
})();


// ::::::::::::::::::::::::::::::::::::::::::::: CLIC PARA MOSTRAR ITINERARIO :::::::::::::::::::::::::::::::::::::::::::::
function esp_itinerario_valid() {

  if ($(".esp_itinerario").hasClass("on") == true) {

    $("#esp_itinerario").val("SI");
    $(".datos-itinerario").show("slow");

  } else {

    $("#esp_itinerario").val("NO");
    $(".datos-itinerario").hide("slow");

  }
}

// ::::::::::::::::::::::::::::::::::::::::::::: mostrar inputs de referencia personas :::::::::::::::::::::::::::::::::::::::::::::
function referencia_personas() {
  if ($("#checkboxReferencia").prop("checked")) {
    $(".div_referencia_pers").show("slow");
    $(".class_llegada_por").addClass("col-xl-3");
    $(".class_llegada_por").removeClass("col-xl-4");
  } else {
    $(".div_referencia_pers").hide("slow");
    $(".class_llegada_por").addClass("col-xl-4");
    $(".class_llegada_por").removeClass("col-xl-3");
  }
}

function only_room() {
  if ($(".reserva_solo_hotel").hasClass("on") == true) {
    $(".div_datos_tours").hide("slow");
    $(".div_datos_compravuelo").hide("slow");
  } else {
    $(".div_datos_tours").show("slow");
    $(".div_datos_compravuelo").show("slow");
  }
}

// ::::::::::::::::::::::::::::::::::::::::::::: numero de pax(pasajeros):::::::::::::::::::::::::::::::::::::::::::::

function number_pax() {
  var total_pax = $("#numero_pasajero").val();
  var numero_ingresado = total_pax == "" || total_pax == null ? 0 : total_pax;

  $(".cantpax_xtours").val(numero_ingresado);
  $(".cantpax_hotel").val(numero_ingresado);

  //verificar si el array no esta vacio
  if (array_data_filas.length > 0) {
    array_data_filas.forEach(function (item) {
      $(`.cantpax_xtours_${item.id_cont}`).val(numero_ingresado);
    });
    modificarSubtotales_fila();
  }
  validar_cant_pax();
}

function validar_cant_pax() {
  var totalPax = parseInt($('#numero_pasajero').val(), 10) || 0;
  var cantNinos = parseInt($('#cant_ninos').val(), 10) || 0;
  var cantAdultos = parseInt($('#cant_adultos').val(), 10) || 0;
  var cantAncianos = parseInt($('#cant_ancianos').val(), 10) || 0;
  var totalDetalle = cantNinos + cantAdultos + cantAncianos;
  var invalido = totalPax > 0 && totalDetalle > totalPax;
  var mensaje = `La suma de ninos, adultos y ancianos (${totalDetalle}) no puede superar el total de pasajeros (${totalPax}).`;

  $('#cant_ninos, #cant_adultos, #cant_ancianos, #numero_pasajero').toggleClass('is-invalid', invalido);
  $(ReservaUxSelectors.paxError).toggleClass('is-visible', invalido).text(invalido ? mensaje : '');
  $('.btn-guardar').toggleClass('disabled', invalido).prop('disabled', invalido);

  if (invalido) {
    mostrarPasoReserva('general');
  }

  actualizarResumenReserva();
  return !invalido;
}

//::::::::::::::::::... validar el numero de celular del cliente ::::::::::::::::::::::::::::::::::::

function valid_nro_cel() {
  var id_cliente = $("#idpersona_cliente").val() == "" || $("#idpersona_cliente").val() == null ? "" : $("#idpersona_cliente").val();

  if (!id_cliente) {
    $("#nro_celular").val(""); // Limpiar el campo si no hay cliente seleccionado
  } else {
    var selectedAttribute = $("#idpersona_cliente option:selected").attr( "data-celular");

    $("#nro_celular").val(selectedAttribute); // Asignar el valor al input correspondiente
  }
}

//:::::::::::::::::::::::::::FECHAS:::::::::::::::::::::::::::::::::::::::::::::::

no_select_yesterday("#llegada_fecha");

function permitirFechaLlegadaPasadaReserva(permitir) {
  if (permitir) {
    $("#llegada_fecha").removeAttr("min");
    return;
  }

  no_select_yesterday("#llegada_fecha");
}


function fecha_a_partir_fecha_llegada() {
  // Obtener la fecha seleccionada en el input de llegada
  var fecha_llegada = $("#llegada_fecha").val();

  // Si ya se ha seleccionado una fecha de llegada, usarla como min en salida
  if (fecha_llegada) {
    $("#salida_fecha").attr("min", fecha_llegada);
  } else {
    var hoy = moment().format("YYYY-MM-DD");
    $("#salida_fecha").attr("min", hoy);
  }
}

// .....::::::::::::::::::::::::::::::::::::: BUSCADOR DE TOURS PARA LLENAR EL DETALLE DE TOURS  :::::::::::::::::::::::::::::::::::::::..
var origenReserva ='';

function habilitarsearch() {
  // Si es solo tours, permitir siempre
  const soloTours = $("#es_tour_solo").is(":checked");
  if (soloTours) {
    return "si";
  }

  // Restricción normal para reservas completas
  var nPax = $('#numero_pasajero').val();
  var fechaLlegada = $('#llegada_fecha').val();
  var llegada = $('#salida_fecha').val();
  var fechaSalida = $('#llegada_fecha').val();
  origenReserva = $('#idorigenreserva').val();

  if (nPax && fechaLlegada && llegada && fechaSalida && origenReserva) {
    return "si";
  } else {
    return "no";
  }
}

var searchToursTimer = null;
var searchToursRequest = null;
var searchToursWarningShown = false;

function inicializarBuscadorTours() {
  $(ReservaSelectors.searchTours).on("input", function () {
    const $input = $(this);
    const query = $input.val().trim().toUpperCase();
    const $resultsList = $(ReservaSelectors.searchResults);

    clearTimeout(searchToursTimer);

    if (searchToursRequest && searchToursRequest.readyState !== 4) {
      searchToursRequest.abort();
    }

    if (query.length < 2) {
      $resultsList.hide().empty();
      searchToursWarningShown = false;
      return;
    }

    if (habilitarsearch() == "no") {
      if (!searchToursWarningShown) {
        toastr_warning(
          "Advertencia!!",
          "Por favor, completa todos los campos necesarios antes de buscar.",
          700
        );
        searchToursWarningShown = true;
      }
      $resultsList.hide().empty();
      return;
    }

    searchToursWarningShown = false;
    $resultsList
      .html('<li class="list-group-item text-muted bg-light">Buscando...</li>')
      .show();

    searchToursTimer = setTimeout(function () {
      searchToursRequest = $.getJSON(
        apiUrlReserva(ReservaEndpoints.toursBuscar),
        { search: query },
        function (e) {
          if ($input.val().trim().toUpperCase() !== query) return;

          const data = Array.isArray(e.data) ? e.data : [];
          $resultsList.empty();

          if (data.length > 0) {
            const items = data.map(function (val) {
              return `<li class="list-group-item hover-text-success list-group-item-action bg-light cursor-pointer py-1" onclick="agregarDetalletblregistertours(${val.idtours},null, false)">
                <span class="fs-12">${val.nombre} S/. ${formato_miles(val.precio_tours)}</span> <br>
                <span class="fs-10">Cod: ${val.codigo} | Turno: ${val.turno}</span>
              </li>`;
            }).join("");
            $resultsList.html(items);
          } else {
            $resultsList.html('<li class="list-group-item text-muted bg-light">No se encontraron resultados</li>');
          }

          $resultsList.show();
        }
      ).fail(function (jqXHR, textStatus) {
        if (textStatus === "abort") return;
        $resultsList.html('<li class="list-group-item text-muted bg-light">No se pudo buscar tours</li>').show();
      });
    }, 300);
  });

  $(document).on("click", function (e) {
    if (!$(e.target).closest(`${ReservaSelectors.searchTours}, ${ReservaSelectors.searchResults}`).length) {
      $(ReservaSelectors.searchResults).hide();
    }
  });
}
var impuesto = 18;
var cont = 0;
var detalles = 0;
var conNO = 1;

// .....::::::::::::::::::::::::::::::::::::: ADD A LA TBL LO QUE BUSCAMOS Y CREAMOS UN FILA   :::::::::::::::::::::::::::::::::::::::..

function agregarDetalletblregistertours(idtours, tipo_producto, individual) {
  var precio_sin_igv = 0;
  var cantidad = 1;
  var descuento = 0;
  var precio_igv = 0;

  if (idtours != "") {
    
    if ( $(`.fila_validacion_${idtours}`).hasClass("fila_validacion_selecionado") && individual == false ) {

      // Producto ya existe, mostrar alerta warning
      toastr_warning(
        "Tours agregado",
        `El tours ${$(`.nombre_fila_validacion_${idtours}`).text()} ya está en la lista.`,
        700
      );


    } else {
      toastr_success( "Agregado!!", `Tours: ${$(`.nombre_fila_validacion_${idtours}`).text()} agregado !!`, 700 );

      var total_pax = $("#numero_pasajero").val();
      var numero_ingresado = total_pax == "" || total_pax == null ? 0 : total_pax;

      $.get(
        apiUrlReserva(`${ReservaEndpoints.tours}/${idtours}`),
        function (e, textStatus, jqXHR) {
          e = normalizarRespuestaReserva(e);
          if (e.status == true) {

            var fila = `<tr class="filas fila_validacion_${idtours} fila_validacion_selecionado" id="fila_${cont}"> 
            <td><span class="btn btn-icon btn-sm btn-danger-light border-danger product-btn" onclick="eliminar_fila(${cont});" ><i class="ri-delete-bin-line"></i></span> </td>
            <td>
            <input type="hidden" class="form-control form-control-sm w-80" name="id_select_tours[]" id="id_select_tours[]" value="${e.data.idtours}"> 
            <input type="hidden" class="form-control form-control-sm w-80" name="nombre_tours[]" id="nombre_tours[]" value="${e.data.nombre}"> 
             ${e.data.nombre} 
            </td>
            <td>
              <select class="form-select form-select-sm w-100 fs-11 " name="vehiculo[]" id="valid_vehiculo_${cont}" required>
                <option value="Compartido">Compartido</option>
                <option value="Privado">Privado</option>
              </select>
            </td>
            <td>
              <select class="form-select form-select-sm w-100 fs-11" name="selecc_idtours_turno[]" id="selecc_idtours_turno_${cont}" required> 
                <option value="">Seleccionar</option>
              </select>
            </td>
            <td><input type="text" class="form-control form-control-sm w-80 cantpax_xtours cantpax_xtours_${cont}" name="nro_pax_fila[]" id="nro_pax_fila_${cont}" onkeyup="subtotal_fila_xuno(${cont});" ></td>
            <td><input type="date" class="form-control form-control-sm w-80" name="fechaDetalle[]" id="fechaDetalle_${cont}" ></td>
            <td><textarea class="textarea_datatable bg-light" name="desc_detalle[]" id="desc_detalle_${cont}" ></textarea></td>
            <td><input type="number" class="form-control form-control-sm w-120" name="precio_tours[]" id="precio_tours_${cont}" value="${e.data.precio_tours}" readonly></td>
            <td><input type="number" class="form-control form-control-sm w-80" id="subtotal_fila_${cont}" name="subtotal_fila[]" value=""></td>

          </tr>`;

            detalles = detalles + 1;
            valid_fechadetalle = cont;
            $("#tabla-productos-seleccionados tbody").append(fila);

            fecha_a_partir_fecha_llegada_salida(valid_fechadetalle);
            // Poblar el select con las opciones de turno
            var turnoSelect = $(`#selecc_idtours_turno_${cont}`);

            e.turno.forEach(function (t) { turnoSelect.append( `<option value="${t.idtours_turno}">${t.nombre}</option>` ); });

            // Establecer el valor por defecto del turno
            if (e.data.idtours_turno) { turnoSelect.val(e.data.idtours_turno).trigger("change"); }

           $(`.cantpax_xtours_${cont}`).val(numero_ingresado);

            array_data_filas.push({ id_cont: cont });
            modificarSubtotales_fila();
            toastr_success( "Agregado!!", `Tour: ${e.data.nombre} agregado !!`, 700 );

            // reglas de validación
            $(".valid_precio_con_igv").each(function (e) {
              $(this).rules("add", { required: true, messages: { required: "Campo requerido" }, });
              $(this).rules("add", { min: 0, messages: { min: "Minimo {0}" } });
            });
            $(".valid_cantidad").each(function (e) {
              $(this).rules("add", { required: true, messages: { required: "Campo requerido" }, });
              $(this).rules("add", { min: 0, messages: { min: "Minimo {0}" } });
            });
            $(".valid_descuento").each(function (e) {
              $(this).rules("add", { required: true, messages: { required: "Campo requerido" }, });
              $(this).rules("add", { min: 0, messages: { min: "Minimo {0}" } });
            });

            cont++;
            //evaluar();
          } else {
            ver_errores(e);
          }
        }
      ).fail(function (e) {
        ver_errores(e);
      });
    }
  } else {
    // alert("Error al ingresar el detalle, revisar los datos del artí­culo");
    toastr_error(
      "Error!!",
      `Error al ingresar el detalle, revisar los datos del tours.`,
      700
    );
  }
}

// Función para validar rango de fechas para un área índice dado
function fecha_a_partir_fecha_llegada_salida(valid_fechadetalle) {

  const llegadaFecha = $("#llegada_fecha").val();
  const salidaFecha = $("#salida_fecha").val();

  const selector = `#fechaDetalle_${valid_fechadetalle}`;

  if (llegadaFecha && salidaFecha) {
    $(selector).attr("min", llegadaFecha);
    $(selector).attr("max", salidaFecha);

    const fechaDetalle = $(selector).val();
    if (
      fechaDetalle &&
      (new Date(fechaDetalle) < new Date(llegadaFecha) ||
        new Date(fechaDetalle) > new Date(salidaFecha))
    ) {
      $(selector).val("");
    }
  } else if (llegadaFecha) {
    $(selector).attr("min", llegadaFecha);
    $(selector).removeAttr("max");

    const fechaDetalle = $(selector).val();
    if (fechaDetalle && new Date(fechaDetalle) < new Date(llegadaFecha)) {
      $(selector).val("");
    }
  } else {
    $(selector).removeAttr("min");
    $(selector).removeAttr("max");
  }
}

// Función para actualizar todas las filas al cambiar fechas llegada/salida
function actualizarFechasDetalle() {

  array_data_filas.forEach(function (item) {
    fecha_a_partir_fecha_llegada_salida(item.id_cont);
  });
}

// Listener para cambios en llegada y salida
$("#llegada_fecha, #salida_fecha").on("change", actualizarFechasDetalle);

function modificarSubtotales_fila() {

  array_data_filas.forEach(function (item) {
    var p_ftours = parseFloat($(`#precio_tours_${item.id_cont}`).val());
    var fcantidad = parseFloat($(`#nro_pax_fila_${item.id_cont}`).val());

    if (isNaN(p_ftours)) p_ftours = 0;
    if (isNaN(fcantidad)) fcantidad = 0;

    var subtotalxfila = p_ftours * fcantidad;

    $(`#subtotal_fila_${item.id_cont}`).val(subtotalxfila.toFixed(2));

  });
  modificarSubtotales();

}

function subtotal_fila_xuno(cont) {

  var p_ftours = parseFloat($(`#precio_tours_${cont}`).val());
  var fcantidad = parseFloat($(`#nro_pax_fila_${cont}`).val());

  if (isNaN(p_ftours)) p_ftours = 0;
  if (isNaN(fcantidad)) fcantidad = 0;

  subtotalxfila = p_ftours * fcantidad;

  $(`#subtotal_fila_${cont}`).val(subtotalxfila.toFixed(2));
  modificarSubtotales();
};


function modificarSubtotales() {

  var total = 0;
    array_data_filas.forEach(function (item) {
    var subtotal = parseFloat($(`#subtotal_fila_${item.id_cont}`).val());

    if (!isNaN(subtotal)) {
      total += subtotal;
    }

  });

  $(".subtotaltours").text(total.toFixed(2));

  total_general();
}

function eliminar_fila(index) {
  $(`#fila_${index}`).remove();
  array_data_filas = array_data_filas.filter(function (item) {
    return item.id_cont !== index;
  });
  modificarSubtotales();
}

//=========================================================================================
//::::::::::::::::::::::::::::::::Registro de datos alojamineto::::::::::::::::::::::::::::::.
//===========================================================================================

function seleccion_hotel() {
  var idhotel = $("#select_idhotel").select2("val");

  // filtro de idhotel
  if (idhotel == "" || idhotel == 0 || idhotel == null) {
    //desahbilitar
  } else {
    //habilitar
  lista_select2( reservaCatalogoUrl(ReservaEndpoints.habitaciones, { idhotel }), "#select_habitacion", null );
    $("#select_habitacion").select2({ theme: "bootstrap4", placeholder: "Seleccione", allowClear: true,  });
  }
}

function agregar_habitacion(){
  var idhabitacion = $("#select_habitacion").val();
  var idhotel = $("#select_idhotel").val();
  var llegada = $('#llegada_fecha').val();
  var salida  = $('#salida_fecha').val();
  var noches = calcularNoches(llegada, salida);
  var paxHotel = reservaPaxActual();

  if (!paxHotel) {
    toastr_warning("Ingrese Nro Pax", "Primero ingrese el Nro Pax en Datos generales.", 700);
    mostrarPasoReserva('general');
    return;
  }

  if (idhabitacion == "" || idhabitacion == null) {
    toastr_warning("Seleccione una habitacion", "Por favor, seleccione una habitacion para agregar.", 700);
    return;
  }

  $.get(
    apiUrlReserva(`${ReservaEndpoints.habitacion}/${idhabitacion}`),
    { idhabitacion: idhabitacion, idhotel: idhotel },
    function (e, textStatus, jqXHR) {
      e = normalizarRespuestaReserva(e);

      if (e.status == true) {
        toastr_success("Habitacion agregada", `La habitacion ha sido agregada correctamente.`, 700);

       

        $("#tabla-habitaciones-seleccionadas tbody").append(`
          <tr class="fila-hotel-${e.data.idhotel}_${e.data.idhotel_habitacion}">
            <td>
            <span class="btn btn-icon btn-sm btn-danger-light border-danger product-btn" onclick="eliminar_fila_habitacion('${e.data.idhotel}_${e.data.idhotel_habitacion}');" ><i class="ri-delete-bin-line"></i></span> 
            </td>
            <td>
              <input type="hidden" name="idhotel[]" value="${e.data.idhotel}">
              <input type="hidden" name="idhotel_habitacion[]" value="${e.data.idhotel_habitacion}">
              <input type="hidden" name="nombre_hotel_habitacion[]" value="${e.data.nombre_habitacion}">
              

              <div class="d-flex flex-fill align-items-center">
                <div>
                  <h6 class="d-block fw-semibold fs-11 text-primary">${e.data.nombre_hotel}</h6>
                  <span class="d-block fs-10 text-muted">Tipo Hab. : <b>${e.data.nombre_habitacion}</b> </span> 
                </div>
              </div>
            </td>

            <td>
            <input type="number" class="form-control cantpax_hotel" name="nro_pax[]" min="0" value="${paxHotel}" readonly>
            </td>
            <td><input type="number" class="form-control" id="cant_hab_${e.data.idhotel}_${e.data.idhotel_habitacion}" name="cant_hab[]" min="0" value="1" onkeyup="modificarsubtotalxfilahab('${e.data.idhotel}_${e.data.idhotel_habitacion}');" onchange="modificarsubtotalxfilahab('${e.data.idhotel}_${e.data.idhotel_habitacion}');"></td>
            <td> <input type="date" class="form-control" value="${llegada}" id="fechallegada_hotel_${e.data.idhotel}_${e.data.idhotel_habitacion}"  name="fechallegada_hotel[]" onchange="update_salida('${e.data.idhotel}_${e.data.idhotel_habitacion}');"> </td>
            <td> <input type="date" class="form-control" value="${salida}" id="fechasalida_hotel_${e.data.idhotel}_${e.data.idhotel_habitacion}" name="fechasalida_hotel[]" onchange="update_salida('${e.data.idhotel}_${e.data.idhotel_habitacion}');" > </td>
            <td><input type="time" class="form-control" name="check_in[]" value="${e.data.check_in}"></td>
            <td><input type="number" class="form-control " name="noches[]"  id="noches_${e.data.idhotel}_${e.data.idhotel_habitacion}" min="0" value="${noches}" readonly></td>
            <td><input type="number" class="form-control"  id="precio_coorporativo_${e.data.idhotel}_${e.data.idhotel_habitacion}" name="precio_coorporativo[]" min="0" value="${e.data.precio_normal}" step="0.01" readonly></td>
            <td><input type="number" class="form-control"  id="adicional_${e.data.idhotel}_${e.data.idhotel_habitacion}" name="adicional[]" value="0" step="0.01" onkeyup="modificarsubtotalxfilahab('${e.data.idhotel}_${e.data.idhotel_habitacion}');" onchange="modificarsubtotalxfilahab('${e.data.idhotel}_${e.data.idhotel_habitacion}');"></td>
            <td><textarea class="textarea_datatable bg-light" name="observacion[]" id="observacion_${e.data.idhotel}_${e.data.idhotel_habitacion}" ></textarea></td>
            <td><input type="number" class="form-control subtotal" id="subtotal_hab_${e.data.idhotel}_${e.data.idhotel_habitacion}" name="subtotal_hab[]" min="0" value="${e.data.precio_coorporativo}" step="0.01"></td>
          </tr>
        `);

        var cant_hab = parseFloat($(`#cant_hab_${e.data.idhotel}_${e.data.idhotel_habitacion}`).val());
        var nochesoperacion = parseFloat($(`#noches_${e.data.idhotel}_${e.data.idhotel_habitacion}`).val());
        var precio_coorporativo = parseFloat($(`#precio_coorporativo_${e.data.idhotel}_${e.data.idhotel_habitacion}`).val());
        var adicional = parseFloat($(`#adicional_${e.data.idhotel}_${e.data.idhotel_habitacion}`).val());

        if (!isNaN(cant_hab) && !isNaN(nochesoperacion) && !isNaN(precio_coorporativo) && !isNaN(adicional) ) {
            var subtotal = (cant_hab*noches*precio_coorporativo)+adicional;
          $(`#subtotal_hab_${e.data.idhotel}_${e.data.idhotel_habitacion}`).val(subtotal.toFixed(2));
        }


          
        $("#select_habitacion").val(null).trigger("change");
        array_data_filas_habit.push({ id_cont_h: `${e.data.idhotel}_${e.data.idhotel_habitacion}`});

        modificarsubtotaleshab();
      } else {
        ver_errores(e);
      }
    }
  ).fail(function (e) {
    ver_errores(e);
  });

}

function eliminar_fila_habitacion(index) {
  $(`.fila-hotel-${index}`).remove();
  array_data_filas_habit = array_data_filas_habit.filter(function (item) {
    return item.id_cont_h !== index;
  });
  modificarsubtotaleshab();
}

function update_salida(iditem) {
  var llegada =  $(`#fechallegada_hotel_${iditem}`).val(); 
  var salida  = $(`#fechasalida_hotel_${iditem}`).val();
  var noches = calcularNoches(llegada, salida);

  $(`#noches_${iditem}`).val(noches);

 modificarsubtotalxfilahab(iditem,noches);
}

function modificarsubtotalxfilahab(iditem,noches) {  

  var cant_hab = parseFloat($(`#cant_hab_${iditem}`).val());
  var nochesoperacion = parseFloat($(`#noches_${iditem}`).val());
  var precio_coorporativo = parseFloat($(`#precio_coorporativo_${iditem}`).val());
  var adicional = parseFloat($(`#adicional_${iditem}`).val());
  noches = !isNaN(parseFloat(noches)) ? parseFloat(noches) : nochesoperacion;

  if (!isNaN(cant_hab) && !isNaN(noches) && !isNaN(precio_coorporativo) && !isNaN(adicional) ) {
      var subtotal = (cant_hab*noches*precio_coorporativo)+adicional;
    $(`#subtotal_hab_${iditem}`).val(subtotal.toFixed(2));
  }
  modificarsubtotaleshab();
}

function modificarsubtotaleshab() {

  var total = 0;
    array_data_filas_habit.forEach(function (item) {
      var subtotal_hab = parseFloat($(`#subtotal_hab_${item.id_cont_h}`).val());

      if (!isNaN(subtotal_hab)  ) {
        total += subtotal_hab;
      }
      
    });


    $(`.total_hab`).text(total.toFixed(2));
     total_general();
  }

  function total_general() { 

      var subtotaltours = parseFloat($(`.subtotaltours`).text());
      var total_hab = parseFloat($(`.total_hab`).text());
      var monto_compra_vuelo = parseFloat($(`#monto_compra_vuelo`).val());

      subtotaltours = isNaN(subtotaltours) ? 0 : subtotaltours;
      total_hab = isNaN(total_hab) ? 0 : total_hab;
      monto_compra_vuelo = isNaN(monto_compra_vuelo) ? 0 : monto_compra_vuelo;

      // Calcular el total
      var t = subtotaltours + total_hab + monto_compra_vuelo;
      if (!reservaTotalManual) {
        $(".total_general").text(t.toFixed(2));
        $("#total_general_i").val(t.toFixed(2));
      }
      actualizarResumenReserva();
  }
/**============================= */

function calcularNoches(fechaInicioStr, fechaFinStr) {
    if (!fechaInicioStr || !fechaFinStr) return 0;

    var fechaInicio = new Date(fechaInicioStr);
    var fechaFin = new Date(fechaFinStr);

    // Calcular diferencia en milisegundos
    var diferencia = fechaFin.getTime() - fechaInicio.getTime();

    var noches = Math.ceil(diferencia / (1000 * 3600 * 24));

    // Si es negativa o cero, retornamos 0
    return noches > 0 ? noches : 0;
}


/**dblclick para editar */
function inicializarEdicionTotalReserva() {

    $(document).on('dblclick', '#total_general', function() {
        const valorTexto = $(this).text().replace(/[^\d.]/g, ''); // Extrae solo el numero
        const input = $('<input type="text" id="input_temp">').val(valorTexto);

        // Aplicamos estilos para que no parezca input
        input.css({
            'font-size': '26px',
            'text-align': 'center',
            'width': '150px',
            'border': 'none',
            'outline': 'none',
            'background': 'transparent',
            'color': 'inherit',
            'font-weight': 'bold'
        });

        $(this).replaceWith(input);
        input.focus();

        input.on('blur', function() {
            let nuevoValor = parseFloat($(this).val());
            if (isNaN(nuevoValor)) nuevoValor = 0;

            const valorFormateado = nuevoValor.toFixed(2);
            const nuevoStrong = $('<strong class="total_general" id="total_general">S/ ' + valorFormateado + '</strong>');

            $(this).replaceWith(nuevoStrong);
            $('#total_general_i').val(valorFormateado);
        });
    });
}

//mostrar detalle

function mostrar_detalle(idreserva,nombre_cliente = "",apellido_cliente = "",documento_cliente = "",doc_cliente = "") {
  idreserva_para_amortizar = idreserva;
  nombre_cliente_para_amortizar = nombre_cliente;
  apellido_cliente_para_amortizar = apellido_cliente;
  documento_cliente_para_amortizar = documento_cliente;
  doc_cliente_para_amortizar = doc_cliente;

  $('.btns_option_detalles').show();
  $('.detalle_reserva_ver').html(`
    <div class="card custom-card">
      <div class="card-body text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <div class="text-muted">Cargando detalle de reserva...</div>
      </div>
    </div>
  `);
  show_hide_form(3);

  $.get(
    reservaResourceUrl(idreserva, '/detalle'),
    { idreserva: idreserva },
    function (e, textStatus, jqXHR) {
      e = normalizarRespuestaReserva(e);

      if (e.status == true && e.data) {
        $(".detalle_reserva_ver").html(e.data);
      } else {
        $(".detalle_reserva_ver").html(`
          <div class="alert alert-warning mb-0">
            No se pudo cargar el detalle de la reserva.
          </div>
        `);
        ver_errores(e);
      }
    }
  ).fail(function (e) {
    $(".detalle_reserva_ver").html(`
      <div class="alert alert-danger mb-0">
        No se pudo cargar el detalle. Revisa la respuesta del servidor e intenta nuevamente.
      </div>
    `);
    ver_errores(e);
  });
}



// .....::::::::::::::::::::::::::::::::::::: V A L I D A C I O N E S    SOLO TOURS :::::::::::::::::::::::::::::::::::::::..
function toggleModoReserva() {
  const soloTours = $('#es_tour_solo').is(':checked');

  $('.reserva-tab-hotel, .reserva-tab-vuelo').toggleClass('is-disabled', soloTours);
  $('.div_datos_alojamiento, .div_datos_compravuelo').toggleClass('is-disabled', soloTours);
  $('.reserva-summary-hotel, .reserva-summary-vuelo').toggle(!soloTours);

  if (soloTours) {
    $('#salida_fecha').val('');
    $('#vuelo_ticket').val('');
    $('#monto_compra_vuelo').val('');
    $('#select_idhotel').val('').trigger('change');
    $('#tabla-habitaciones-seleccionadas tbody').empty();
    array_data_filas_habit = [];

    const pasoActivo = $('.reserva-step-tab.active').data('reserva-step');
    if (pasoActivo === 'hotel' || pasoActivo === 'vuelo') {
      mostrarPasoReserva('tours');
    }
  }

  modificarsubtotaleshab();
  actualizarResumenReserva();
}


function esperar(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**PAGOS */

function cargarDetalleComprobanteAmortizar(idreserva, fallback = '') {
  const $detalle = $('#detalle_comprobante_amortizar');
  $detalle.val(fallback || 'Cargando detalle de comprobante...');

  if (!idreserva) {
    $detalle.val(fallback || '');
    return;
  }

  $.get(apiUrlReserva(ReservaEndpoints.detalleComprobante(idreserva)), function (response) {
    response = normalizarRespuestaReserva(response);
    $detalle.val(response.data?.detalle || fallback || '');
  }).fail(function () {
    $detalle.val(fallback || '');
  });
}

function amortizar(idpersona_cliente,saldo_pendiente,html_detalle_pago) {
  inicializarPagoReserva(function () {
var pendiente = parseFloat(saldo_pendiente) || 0;
  var montoInicial = pendiente > 0 ? pendiente.toFixed(2) : '';

  limpiar_nuevo_pago();
  $('#cod-reserva').text('Reserva #' + idreserva_para_amortizar);
  $('#idreserva_amortizar').val(idreserva_para_amortizar);
  cargarDetalleComprobanteAmortizar(idreserva_para_amortizar, html_detalle_pago || '');
  $('#total_amortizar').val(pendiente.toFixed(2));
  $('#monto_amortizar').val(montoInicial);
  $('#saldo_amortizar').val(pendiente > 0 ? '0.00' : '');
  $('#f_venta_subtotal').val(montoInicial);

  $('#f_tipo_comprobante12').prop('checked', true);
  $('#f_tipo_comprobante_hidden').val('12');
  $('#f_idsunat_c01').val('12');
  ver_series_comprobante('#f_tipo_comprobante12');

  lista_select2(apiUrlReserva(ReservaEndpoints.clientes), "#p_idpersona_cliente", idpersona_cliente,'.charge_p_idpersona_cliente' );
  capturar_pago_venta(1);
  $("#modal-amortizar-reserva").modal("show");
  });
}

function seleccionarMetodoPagoReserva(value, label = null) {
  if (!value) return;

  var valueString = String(value);
  if (f_metodo_pago_1 && typeof f_metodo_pago_1.setChoiceByValue === 'function') {
    f_metodo_pago_1.removeActiveItems();
    f_metodo_pago_1.setChoiceByValue(valueString);

    if ($('#f_metodo_pago_1').val()) {
      capturar_pago_venta(1);
      return;
    }

    if (label && typeof f_metodo_pago_1.setChoices === 'function') {
      f_metodo_pago_1.setChoices([{ value: valueString, label: label, selected: true, disabled: false }], 'value', 'label', false);
    }
  }

  $('#f_metodo_pago_1').val(valueString).trigger('change');
  capturar_pago_venta(1);
}
function editarPagoReserva(idrdocumento) {
  inicializarPagoReserva(function () {
    $.getJSON(apiUrlReserva(`${ReservaEndpoints.pagos}/${idrdocumento}`), function (e) {
      e = normalizarRespuestaReserva(e);
      if (e.status !== true) {
        ver_errores(e);
        return;
      }

      var pago = e.data || {};
      limpiar_nuevo_pago();
      idreserva_para_amortizar = pago.idreserva_amortizar || idreserva_para_amortizar;

      $('#cod-reserva').text('Editando pago - Reserva #' + pago.idreserva_amortizar);
      $('#idrdocumento_pago').val(pago.idrdocumento || '');
      $('#idreserva_amortizar').val(pago.idreserva_amortizar || '');
      $('#detalle_comprobante_amortizar').val(pago.detalle_comprobante_amortizar || '');
      $('#total_amortizar').val(pago.total_amortizar || '0.00');
      $('#monto_amortizar').val(pago.monto_amortizar || '');
      $('#saldo_amortizar').val(pago.saldo_amortizar || '0.00');
      $('#f_venta_subtotal').val(pago.monto_amortizar || '');
      $('#observacion_amortizar').val(pago.observacion_amortizar || '');

      var tipoComprobante = pago.f_tipo_comprobante || '12';
      $(`#f_tipo_comprobante${tipoComprobante}`).prop('checked', true);
      $('#f_tipo_comprobante_hidden').val(tipoComprobante);
      $('#f_idsunat_c01').val(pago.f_idsunat_c01 || tipoComprobante);
      ver_series_comprobante(`#f_tipo_comprobante${tipoComprobante}`, pago.f_serie_comprobante || null, pago.f_serie_comprobante_label || null);

      lista_select2(apiUrlReserva(ReservaEndpoints.clientes), '#p_idpersona_cliente', pago.p_idpersona_cliente, '.charge_p_idpersona_cliente');

      seleccionarMetodoPagoReserva(pago.f_metodo_pago_1, pago.f_metodo_pago_1_label);

      $('#guardar_registro_nuevo_pago_reserva').html('<i class="bx bx-check"></i> ACTUALIZAR');
      $('#modal-amortizar-reserva').modal('show');
    }).fail(function (jqXhr) {
      ver_errores(jqXhr);
    });
  });
}

function eliminarPagoReserva(idrdocumento) {
  Swal.fire({
    title: 'Eliminar pago',
    text: 'Esta accion quitara el pago de la reserva.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#d33'
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: apiUrlReserva(`${ReservaEndpoints.pagos}/${idrdocumento}`),
      type: 'POST',
      data: { _method: 'DELETE' },
      success: function (e) {
        e = normalizarRespuestaReserva(e);
        if (e.status === true) {
          Swal.fire('Correcto!', e.message || 'Pago eliminado correctamente.', 'success');
          var idreserva = e.data && e.data.idreserva ? e.data.idreserva : idreserva_para_amortizar;
          if (idreserva) {
            idreserva_para_amortizar = idreserva;
            mostrar_detalle(idreserva_para_amortizar, nombre_cliente_para_amortizar, apellido_cliente_para_amortizar, documento_cliente_para_amortizar, doc_cliente_para_amortizar);
          }
          if (typeof tabla_reserva !== 'undefined' && tabla_reserva) {
            tabla_reserva.ajax.reload(null, false);
          }
        } else {
          ver_errores(e);
        }
      },
      error: function (jqXhr) {
        ver_errores(jqXhr);
      }
    });
  });
}

function abrirModalAsociarComprobanteReserva(idreserva, saldoPendiente) {
  const reservaId = Number(idreserva || 0);
  const saldo = Number.parseFloat(saldoPendiente || 0) || 0;

  if (!reservaId) {
    toastr_error('Error', 'No se pudo identificar la reserva.');
    return;
  }

  $('#asociar_reserva_id').val(reservaId);
  $('#asociar_reserva_saldo').val(saldo.toFixed(2));
  $('#asociar_idrdocumento').val('');
  $('#monto_comprobante_asociar').val('');
  $('#texto_comprobante_asociar').text('-');
  $('#asociar_mostrar_todos_documentos').prop('checked', false);
  actualizarHelpComprobanteAsociarReserva();
  $('#cod-reserva-asociar').text('Reserva #' + reservaId);

  inicializarSelectComprobanteAsociarReserva(reservaId);
  $('#select_comprobante_asociar').val(null).trigger('change');
  $('#modal-asociar-comprobante-reserva').modal('show');
}

function inicializarSelectComprobanteAsociarReserva(idreserva) {
  const $select = $('#select_comprobante_asociar');
  const mostrarTodos = $('#asociar_mostrar_todos_documentos').is(':checked');

  actualizarHelpComprobanteAsociarReserva();

  if ($select.hasClass('select2-hidden-accessible')) {
    $select.select2('destroy');
  }

  $select.empty().select2({
    theme: 'bootstrap4',
    width: '100%',
    allowClear: true,
    dropdownParent: $('#modal-asociar-comprobante-reserva'),
    placeholder: 'Seleccione comprobante suelto',
    ajax: {
      url: apiUrlReserva(ReservaEndpoints.comprobantesAsociables(idreserva)),
      dataType: 'json',
      delay: 250,
      cache: false,
      data: function (params) {
        return {
          term: params.term || '',
          todos: mostrarTodos ? 1 : 0,
        };
      },
      processResults: function (response) {
        response = normalizarRespuestaReserva(response);
        const rows = response.data || [];

        return {
          results: rows.map(function (row) {
            const total = Number.parseFloat(row.disponible || row.total || 0) || 0;
            const comprobante = row.comprobante || '-';
            const tipo = row.tipo || '-';
            const fecha = row.fecha_emision || '-';

            return {
              id: row.idrdocumento,
              text: `${comprobante} | ${tipo} | ${fecha} | ${row.cliente || '-'} | S/ ${total.toFixed(2)}`,
              comprobante: comprobante,
              disponible: total,
              cliente: row.cliente || '-',
              sunat_estado: row.sunat_estado || '-',
            };
          }),
        };
      },
    },
    templateResult: templateComprobanteAsociarReserva,
    templateSelection: templateComprobanteAsociarSeleccionReserva,
  });
}

function actualizarHelpComprobanteAsociarReserva() {
  const mostrarTodos = $('#asociar_mostrar_todos_documentos').is(':checked');
  $('#asociar_comprobante_help').text(mostrarTodos
    ? 'Aparecen todos los documentos activos en rdocumento que aun no estan asociados a una reserva.'
    : 'Solo aparecen documentos activos del cliente que estan en rdocumento y aun no estan asociados a una reserva.');
}

function templateComprobanteAsociarReserva(item) {
  if (!item.id) return item.text;

  return $(`
    <div>
      <div class="fw-semibold">${escapeHtmlReserva(item.comprobante || item.text)}</div>
      <div class="fs-11 text-muted">${escapeHtmlReserva(item.cliente || '-')} | ${escapeHtmlReserva(item.sunat_estado || '-')}</div>
    </div>
  `);
}

function templateComprobanteAsociarSeleccionReserva(item) {
  return item.comprobante || item.text || '';
}

function seleccionarComprobanteAsociarReserva(item) {
  const saldo = Number.parseFloat($('#asociar_reserva_saldo').val() || 0) || 0;
  const idrdocumento = Number(item?.id || 0);
  const totalDisponible = Number.parseFloat(item?.disponible || 0) || 0;
  const monto = Math.min(saldo > 0 ? saldo : totalDisponible, totalDisponible);

  $('#asociar_idrdocumento').val(idrdocumento);
  $('#texto_comprobante_asociar').text(item?.text || item?.comprobante || '-');
  $('#monto_comprobante_asociar').val(monto > 0 ? monto.toFixed(2) : '');
}

function guardarAsociacionComprobanteReserva() {
  const idreserva = Number($('#asociar_reserva_id').val() || 0);
  const idrdocumento = Number($('#asociar_idrdocumento').val() || 0);
  const monto = Number.parseFloat($('#monto_comprobante_asociar').val() || 0) || 0;
  const $button = $('#btn-guardar-asociar-comprobante');

  if (!idreserva || !idrdocumento) {
    toastr_error('Error', 'Seleccione un comprobante para asociar.');
    return;
  }

  if (monto <= 0) {
    toastr_error('Error', 'Ingrese un monto valido.');
    return;
  }

  $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Asociando...');

  $.ajax({
    url: apiUrlReserva(ReservaEndpoints.asociarComprobante(idreserva)),
    type: 'POST',
    data: {
      idrdocumento: idrdocumento,
      monto_cuota: monto,
    },
    success: function (response) {
      response = normalizarRespuestaReserva(response);

      if (!response.status) {
        ver_errores(response);
        return;
      }

      Swal.fire('Correcto!', response.message || 'Comprobante asociado correctamente.', 'success');
      $('#modal-asociar-comprobante-reserva').modal('hide');
      mostrar_detalle(idreserva, nombre_cliente_para_amortizar, apellido_cliente_para_amortizar, documento_cliente_para_amortizar, doc_cliente_para_amortizar);

      if (typeof tabla_reserva !== 'undefined' && tabla_reserva) {
        tabla_reserva.ajax.reload(null, false);
      }
    },
    error: function (jqXhr) {
      ver_errores(jqXhr);
    },
    complete: function () {
      $button.prop('disabled', false).html('<i class="ri-link-m me-1"></i> Asociar');
    }
  });
}

function escapeHtmlReserva(value) {
  return String(value ?? '').replace(/[&<>"']/g, function (character) {
    return {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    }[character];
  });
}
// .....::::::::::::::::::::::::::::::::::::: F U N C I O N E S    A L T E R N A S  :::::::::::::::::::::::::::::::::::::::..
function reload_filtro_fecha_i(){ $('#filtro_fecha_i').val("").trigger("change") } 
function reload_filtro_fecha_f(){ $('#filtro_fecha_f').val("").trigger("change") } 
function reload_filtro_cliente(){ lista_select2(apiUrlReserva(ReservaEndpoints.clientes), ReservaSelectors.filtroCliente, null,'.charge_filtro_cliente' ); } 

function cargando_search() {
  $('.buscando_tabla').show().html(`<i class="fas fa-spinner fa-pulse fa-sm"></i> Buscando ...`);
}

function filtros() {  

  var filtro_fecha_i      = $("#filtro_fecha_i").val();
  var filtro_fecha_f      = $("#filtro_fecha_f").val();  
  var filtro_cliente      = $(ReservaSelectors.filtroCliente).select2('val');

  var nombre_filtro_fecha_i     = $('#filtro_fecha_i').val();
  var nombre_filtro_fecha_f     = ' - ' + $('#filtro_fecha_f').val();
  var nombre_filtro_cliente     = ' - ' + $(ReservaSelectors.filtroCliente).find(':selected').text();

  // filtro de fechas
  if (filtro_fecha_i == '' || filtro_fecha_i == 0 || filtro_fecha_i == null) { filtro_fecha_i = ""; nombre_filtro_fecha_i = ""; }
  if (filtro_fecha_f == '' || filtro_fecha_f == 0 || filtro_fecha_f == null) { filtro_fecha_f = ""; nombre_filtro_fecha_f = ""; }

  // filtro de cliente
  if (filtro_cliente == '' || filtro_cliente == 0 || filtro_cliente == null) { filtro_cliente = ""; nombre_filtro_cliente = ""; }


  $('.buscando_tabla').show().html(`<i class="fas fa-spinner fa-pulse fa-sm"></i> Buscando ${filtro_fecha_i} ${filtro_fecha_f} ${nombre_filtro_cliente}...`);

  listar_tabla(filtro_fecha_i, filtro_fecha_f, filtro_cliente);
}
