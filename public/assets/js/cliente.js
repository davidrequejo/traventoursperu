let tablaClientes = null;
let clienteSeleccionadoId = null;
let cargandoConyuge = false;
let documentoConyugeCargado = '';

function puedeCliente(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('clientes', accion);
}

$(function () {
  inicializarTablaClientes();
  inicializarMenuContextualCliente();

  $('#btn-recargar-clientes').on('click', function () {
    recargarTablaClientes();
  });

  $('#btn-nuevo-cliente').on('click', function () {
    if (!puedeCliente('crear')) {
      mostrarError('No tienes permiso para crear clientes.');
      return;
    }

    abrirFormularioCliente();
  });

  
  lista_select2(apiUrl(`/select2/select2distrito`), '#iddistrito');
  lista_select2(apiUrl(`/select2/select2distrito`), '#modal_conyuge_iddistrito');

  //$("").select2({  placeholder: "Seleccionar Tipo de Documento", allowClear: true, });
  $("#tipo_documento").select2({ theme: "bootstrap4", placeholder: "Seleccionar Tipo de Documento", allowClear: true,dir: "ltr"  });
  $("#tipo_persona_sunat").select2({ theme: "bootstrap4", placeholder: "Seleccionar Tipo Persona",allowClear: true, dir: "ltr" });
  $("#estado_civil").select2({ theme: "bootstrap4", placeholder: "Seleccionar Estado Civil", allowClear: true, dir: "ltr" });
  $("#conyuge_tipo_documento").select2({ theme: "bootstrap4", placeholder: "Seleccionar Tipo de Documento", allowClear: true, dir: "ltr" });
  $("#modal_conyuge_tipo_documento").select2({ theme: "bootstrap4", placeholder: "Seleccionar Tipo de Documento", allowClear: true, dir: "ltr", dropdownParent: $('#modal-registrar-conyuge') });
  $("#modal_conyuge_sexo").select2({ theme: "bootstrap4", placeholder: "Seleccionar Sexo", allowClear: true, dir: "ltr", dropdownParent: $('#modal-registrar-conyuge') });
  $("#modal_conyuge_nacionalidad").select2({ theme: "bootstrap4", placeholder: "Seleccionar Nacionalidad", allowClear: true, dir: "ltr", dropdownParent: $('#modal-registrar-conyuge') });
  $("#modal_conyuge_iddistrito").select2({ theme: "bootstrap4", placeholder: "Seleccionar Distrito", allowClear: true, dir: "ltr", dropdownParent: $('#modal-registrar-conyuge') });
  $("#nacionalidad").select2({ theme: "bootstrap4", placeholder: "Seleccionar Nacionalidad", allowClear: true, dir: "ltr" });
  $("#sexo").select2({ theme: "bootstrap4", placeholder: "Seleccionar Sexo", allowClear: true, dir: "ltr" });
  $("#sexo").select2({ theme: "bootstrap4", placeholder: "Seleccionar Sexo", allowClear: true, dir: "ltr" });

  $("#iddistrito").select2({ theme: "bootstrap4", placeholder: "Seleccionar Distrito", allowClear: true, dir: "ltr" });
  $("#identidad_tipo").select2({ theme: "bootstrap4", placeholder: "Seleccionar Tipo Entidad", allowClear: true, dir: "ltr" });

  $("#tipo_documento").val("").trigger('change');
  $("#tipo_persona_sunat").val("").trigger('change');

  $("#estado_civil").val("").trigger('change');
  $("#nacionalidad").val("").trigger('change');
  $("#sexo").val("").trigger('change');

  $("#iddistrito").val("").trigger('change');
  // Evento para el botón de guardar, editar y eliminar

  $('.btn-nuevo-persona').on('click', function () {
    if (!puedeCliente('crear')) {
      mostrarError('No tienes permiso para crear clientes.');
      return;
    }

    limpiarFormularioCliente();
    show_hide_form(2);
  });

  $('.btn-regresar-persona').on('click', function () {
    show_hide_form(1);
  });


});

/*  $(document).on('blur', '#numero_documento', function () {
  buscarEntidadExistentePorDocumento();
});

$('#tipo_documento').on('change', function () {
  buscarEntidadExistentePorDocumento();
});*/

function apiUrl(path) {
  const base = document.querySelector('meta[name="app-url"]')?.content || '';
  return base + path;
}

function csrf() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function obtenerContenedorSelect2(element) {
  const $element = $(element);
  return $element.nextAll('.select2-container').first();
}

function colocarErrorSelect2(error, element) {
  const $element = $(element);
  const $container = obtenerContenedorSelect2(element);
  const $column = $element.closest('[class*="col-"]');

  if ($column.length) {
    error.appendTo($column);
    return;
  }

  if ($container.length) {
    error.insertAfter($container);
    return;
  }

  error.insertAfter($element);
}

$(".guardar_registro_persona").on("click", function (e) { $("#submit-form-entidad").submit(); });   

function actualizarIndicadorCamposRequeridos() {
  const $form = $("#form-agregar-persona");
  const $indicador = $("#indicador-campos-requeridos");
  const $icono = $("#icono-campos-requeridos");
  const $path = $("#path-campos-requeridos");
  const iconoCompleto = "M20 8h-5.61l1.12-3.37c.2-.61.1-1.28-.27-1.8-.38-.52-.98-.83-1.62-.83h-1.61c-.3 0-.58.13-.77.36L6.54 8H4.01c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h13.31a2 2 0 0 0 1.87-1.3l2.76-7.35c.04-.11.06-.23.06-.35v-2c0-1.1-.9-2-2-2ZM6 19H4v-9h2zm14-7.18L17.31 19H8V9.36L12.47 4h1.15l-1.56 4.68a1.01 1.01 0 0 0 .95 1.32h7v1.82Z";
  const iconoPendiente = "M20 3H6.69a2 2 0 0 0-1.87 1.3L2.06 11.65c-.04.11-.06.23-.06.35v2c0 1.1.9 2 2 2h5.61l-1.12 3.37c-.2.61-.1 1.28.27 1.8.38.52.98.83 1.62.83h1.61c.3 0 .58-.13.77-.36L17.46 16H20c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2ZM16 14.64 11.53 20h-1.15l1.56-4.68A1.01 1.01 0 0 0 10.99 14H4v-1.82L6.69 5H16v9.64ZM20 14h-2V5h2v9Z";

  if (!$form.length || !$indicador.length || !$icono.length || !$path.length) return;

  const validator = $form.data('validator');
  const reglas = validator?.settings?.rules || {
    tipo_persona_sunat: { required: true },
    tipo_documento: { required: true },
    descripcion: { required: true },
    numero_documento: { required: true },
    nacionalidad: { required: true },
    direccion: { required: true },
  };

  const requeridos = Object.keys(reglas).filter(function (name) {
    const regla = reglas[name];
    const $campo = $form.find(`[name="${name}"]`);
    return regla === 'required'
      || regla?.required === true
      || (typeof regla?.required === 'function' && $campo.length && regla.required.call($campo[0]));
  });

  const completos = requeridos.length > 0 && requeridos.every(function (name) {
    const $campo = $form.find(`[name="${name}"]`);
    if (!$campo.length || $campo.prop('disabled')) return true;

    const $grupoDinamico = $campo.closest([
      '.div_nombre_comercial',
      '.div_nombre_persona_natural',
      '.div_apellido_paterno_persona_natural',
      '.div_apellido_materno_persona_natural',
      '.div_sexo',
      '.div_fecha_nacimiento',
    ].join(','));
    if ($grupoDinamico.length && $grupoDinamico.is(':hidden')) return true;

    if ($campo.is(':checkbox, :radio')) {
      return $form.find(`[name="${name}"]:checked`).length > 0;
    }

    const valor = $campo.val();
    return Array.isArray(valor)
      ? valor.length > 0
      : String(valor || '').trim() !== '';
  });

  $indicador
    .toggleClass('bg-primary-transparent', !completos)
    .toggleClass('bg-success-transparent', completos)
    .attr('title', completos ? 'Campos requeridos completos' : 'Campos requeridos pendientes');

  $icono.attr('fill', completos ? '#009551' : '#989797');
  $path.attr('d', completos ? iconoCompleto : iconoPendiente);
}

function show_hide_form(flag) {
  if ( flag == 1) {
    $('.div_tabla_persona').show();
    $('.div_formulario_persona').hide();
    $('.btn-nuevo-persona').show();
    $('.btn-regresar-persona').hide();
  } else if ( flag == 2) {
    $('.div_tabla_persona').hide();
    $('.div_formulario_persona').show();
    $('.btn-nuevo-persona').hide();
    $('.btn-regresar-persona').show();
  } else if ( flag == 3) {
  } else if ( flag == 4) {

  }
}

function limpiarFormularioCliente() {
  const $form = $("#form-agregar-persona");
  const imagenDefault = apiUrl('/assets/modulo/persona/perfil/hombre.png');

  if ($form.length && $form[0]) {
    $form[0].reset();
  }

  $("#idpersona").val("");
  $("#idpersona_tipo").val("3");
  $("#imagen").val("");
  $("#imagenactual").val("");
  $("#estado_sunat").val("");
  $("#idprovincia").val("");
  $("#iddepartamento").val("");
  $("#iddistrito_envio").val("");
  $("#cod_ubigeo").val("");
  $("#imagenmuestra").attr("src", imagenDefault);

  $("#tipo_documento").val("").trigger('change');
  $("#tipo_persona_sunat").val("").trigger('change');
  $("#estado_civil").val("").trigger('change');
  limpiarConyuge();
  $("#nacionalidad").val("").trigger('change');
  $("#sexo").val("").trigger('change');
  $("#iddistrito").val(null).trigger('change');

  $(".valido_novalido").html('<span class="badge bg-primary">Por Verificar</span>');
  $("#search").show();
  $("#charge").hide();
  $("#cargando-1-formulario").show();
  $("#cargando-2-formulario").hide();
  $("#barra_progress_entidad").css({ width: "0%" }).text("0%");

  $(".is-invalid, .is-valid").removeClass("is-invalid is-valid");
  $(".invalid-feedback").remove();
  $(".select2-selection").removeClass("is-invalid is-valid");

  const validator = $form.data('validator');
  if (validator) {
    validator.resetForm();
  }

  const firstTab = document.querySelector('[data-bs-target="#account-pane"]');
  if (firstTab && typeof bootstrap !== 'undefined') {
    bootstrap.Tab.getOrCreateInstance(firstTab).show();
  }

  actualizarCampos();
  actualizarIndicadorCamposRequeridos();
}

// Delegación de eventos por si las opciones se cargan dinámicamente
$('#iddistrito').on('change', function() {
    // Obtener la opción seleccionada
    var selectedOption = $(this).find('option:selected');
    
    // Capturar el valor (que es el nombre del distrito)
    var distrito = selectedOption.val();
    
    // Capturar los atributos personalizados provincia y departamento
    var provincia = selectedOption.attr('provincia');
    var departamento = selectedOption.attr('departamento');

    var distrito = selectedOption.attr('iddistrito');
    var idProvincia = selectedOption.attr('idprovincia');
    var idDepartamento = selectedOption.attr('iddepartamento');
    
    // Asignar a los inputs
    $('#idprovincia').val(provincia);
    $('#iddepartamento').val(departamento);
    $('#iddistrito_envio').val(distrito);
    actualizarIndicadorCamposRequeridos();
    
});

function inicializarTablaClientes() {
  tablaClientes = $('#tabla-clientes').DataTable({
    responsive: true,
    processing: true,
    serverSide: true,
    deferRender: true,
    searchDelay: 350,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    dom: "<'row'<'col-md-7 col-lg-8 col-xl-9 col-xxl-10 pt-2'f><'col-md-5 col-lg-4 col-xl-3 col-xxl-2 pt-2 d-flex justify-content-end align-items-center'<'length'l><'buttons'B>>>r t <'row'<'col-md-6'i><'col-md-6'p>>",
    buttons: [
      {
        text: '<i class="bi bi-arrow-clockwise"></i> ',
        className: 'buttons-reload btn btn-outline-info',
        action: function (_event, dt) {
          dt.ajax.reload(null, false);
        },
      },
      {
        extend: 'excel',
        exportOptions: { columns: [0, 2, 3, 4, 5, 6] },
        title: 'Lista de clientes',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
        footer: true,
      },
    ],
    ajax: {
      url: apiUrl('/clientes/listar'),
      type: 'GET',
      headers: { 'X-CSRF-TOKEN': csrf() },
      dataSrc: 'data',
      error: function () {
        mostrarError('Error al consultar clientes.');
      },
    },
    columns: [
      { data: 'codigo', className: 'text-center' },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const acciones = [`
            <button type="button" class="btn btn-sm btn-icon btn-info btn-ver-cliente" data-id="${row.idpersona}" data-bs-toggle="tooltip" title="Ver">
              <i class="ri-eye-line"></i>
            </button>`];

          if (puedeCliente('editar')) {
            acciones.push(`
            <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-cliente" data-id="${row.idpersona}" data-bs-toggle="tooltip" title="Editar">
              <i class="ri-edit-line"></i>
            </button>`);
          }

          if (puedeCliente('eliminar')) {
            acciones.push(`
            <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-cliente" data-id="${row.idpersona}" data-bs-toggle="tooltip" title="Eliminar">
              <i class="ri-delete-bin-line"></i>
            </button>`);
          }

          return acciones.join('');
        },
      },
      {
        data: null,
        name: 'persona_info',
        className: 'cliente-persona',
        render: function (row) {
          const documento = [row.tipo_documento_label || row.tipo_documento, row.numero_documento].filter(Boolean).join(' ');
          const fotoPerfil = row.foto_perfil || 'hombre.png';
          const fotoPerfilUrl = apiUrl(`/assets/modulo/persona/perfil/${fotoPerfil}`);
          const codigo = row.codigo ? ` | codigo: ${escapeHtml(row.codigo)}` : '';
          const nombreMostrar = row.nombre_cliente || row.descripcion || row.nombre_razonsocial || row.nombre_comercial || 'Sin nombre';

          return `
            <div class="d-flex flex-wrap align-items-center justify-content-between">
              <div class="d-flex align-items-center">
                <div class="me-2 lh-1">
                  <span class="avatar avatar-md">
                    <img src="${fotoPerfilUrl}" alt="" onerror="this.src='${apiUrl('/assets/modulo/persona/perfil/hombre.png')}';">
                  </span>
                </div>
                <div>
                  <p class="fw-semibold mb-0">${escapeHtml(nombreMostrar)}</p>
                  <span class="text-muted fs-12 d-inline-flex">${escapeHtml(documento || 'Sin documento')}${codigo}</span>
                  <p class="text-muted fs-12 mb-0">${escapeHtml(row.nombre_comercial || '')}</p>
                </div>
              </div>
            </div>
          `;
        },
      },
      {
        data: null,
        name: 'contacto',
        render: function (_data, _type, row) {
          const correo = row.correo ? `<div><i class="ri-mail-line me-1"></i>${escapeHtml(row.correo)}</div>` : '';
          const celular = row.celular ? `<div><i class="ri-phone-line me-1"></i>${escapeHtml(row.celular)}</div>` : '';
          return correo || celular ? `${correo}${celular}` : '<span class="text-muted">Sin contacto</span>';
        },
      },
      {
        data: null,
        name: 'tipo_persona_sunat',
        render: function (_data, _type, row) {
          const tipo_persona_sunat = [row.tipo_persona_sunat].filter(Boolean).join(' ');
          return `<span class="fw-semibold">${escapeHtml(tipo_persona_sunat || 'Sin Tipo Persona')}</span>`;
        },
      },
      {
        data: null,
        name: 'estado_civil',
        render: function (_data, _type, row) {
          const estado_civil = [row.estado_civil].filter(Boolean).join(' ');
          return `<span class="fw-semibold">${escapeHtml(estado_civil || 'Sin Estado Civil')}</span>`;
        },
      },
      {
        data: null,
        name: 'nacionalidad',
        render: function (_data, _type, row) {
          const nacionalidad = [row.nacionalidad].filter(Boolean).join(' ');
          return `<span class="fw-semibold">${escapeHtml(nacionalidad || 'Sin Nacionalidad')}</span>`;
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
      buttons: {
        copyTitle: 'Tabla Copiada',
        copySuccess: { _: '%d lineas copiadas', 1: '1 linea copiada' },
      },
      sLoadingRecords: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...',
      search: '',
    },
    drawCallback: function () {
      $('[data-bs-toggle="tooltip"]').tooltip();
    },
    order: [[0, 'desc']],
  });

  $('#tabla-clientes tbody').on('click', '.btn-ver-cliente', function () {
    verCliente($(this).data('id'));
  });

  $('#tabla-clientes tbody').on('click', '.btn-editar-cliente', function () {
    editarCliente($(this).data('id'));
  });

  $('#tabla-clientes tbody').on('click', '.btn-eliminar-cliente', function () {
    eliminarCliente($(this).data('id'));
  });

  $('#tabla-clientes tbody').on('contextmenu', 'tr', function (event) {
    event.preventDefault();

    const rowData = tablaClientes.row(this).data();
    if (rowData) {
      mostrarMenuContextualCliente(event, rowData.idpersona);
    }
  });
}

function inicializarMenuContextualCliente() {
  $(document).on('click', function (event) {
    if (!$(event.target).closest('#menu-contextual-cliente').length) {
      ocultarMenuContextualCliente();
    }
  });

  $(document).on('keydown', function (event) {
    if (event.key === 'Escape') {
      ocultarMenuContextualCliente();
    }
  });

  $('#opcion-c-ver').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuCliente(verCliente);
  });

  $('#opcion-c-editar').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuCliente(editarCliente);
  });

  $('#opcion-c-eliminar').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuCliente(eliminarCliente);
  });

  $('#opcion-c-restaurar').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuCliente(restaurarCliente);
  });

  $('#detalle-cliente-editar').on('click', function () {
    const id = $(this).data('id');
    const modalElement = document.getElementById('modal-detalle-cliente');

    if (modalElement && typeof bootstrap !== 'undefined') {
      bootstrap.Modal.getOrCreateInstance(modalElement).hide();
    }

    if (id) {
      editarCliente(id);
    }
  });
}

function mostrarMenuContextualCliente(event, clienteId) {
  const $menu = $('#menu-contextual-cliente');
  if (!$menu.length) return;

  clienteSeleccionadoId = clienteId;
  $menu.css({ display: 'block', left: '0px', top: '0px' });

  const menuWidth = $menu.outerWidth();
  const menuHeight = $menu.outerHeight();
  const windowWidth = $(window).width();
  const windowHeight = $(window).height();
  const scrollLeft = $(window).scrollLeft();
  const scrollTop = $(window).scrollTop();

  const left = Math.min(event.pageX, scrollLeft + windowWidth - menuWidth - 10);
  const top = Math.min(event.pageY, scrollTop + windowHeight - menuHeight - 10);

  $menu.css({
    left: `${Math.max(scrollLeft + 10, left)}px`,
    top: `${Math.max(scrollTop + 10, top)}px`,
  });
}

function ocultarMenuContextualCliente() {
  $('#menu-contextual-cliente').hide();
}

function ejecutarAccionMenuCliente(callback) {
  const id = clienteSeleccionadoId;
  ocultarMenuContextualCliente();

  if (!id) {
    mostrarError('Seleccione un cliente.');
    return;
  }

  callback(id);
}

function recargarTablaClientes() {
  if (tablaClientes) {
    tablaClientes.ajax.reload(null, false);
  }
}

function abrirFormularioCliente(id = null) {
  if (id) {
    editarCliente(id);
    return;
  }

  limpiarFormularioCliente();
  show_hide_form(2);
}

function verCliente(id) {
  if (!id) {
    mostrarError('Seleccione un cliente.');
    return;
  }

  const modalElement = document.getElementById('modal-detalle-cliente');
  if (!modalElement || typeof bootstrap === 'undefined') {
    mostrarError('No se encontrÃ³ el modal de detalle.');
    return;
  }

  limpiarModalDetalleCliente();
  $('#detalle-cliente-editar').data('id', id);
  bootstrap.Modal.getOrCreateInstance(modalElement).show();

  $.getJSON(apiUrl(`/clientes/${id}/show`), function (response) {
    if (response.status !== true || !response.data) {
      mostrarError(response.message || 'No se pudo cargar el detalle del cliente.');
      return;
    }

    renderDetalleCliente(response.data);
  }).fail(function (xhr) {
    bootstrap.Modal.getOrCreateInstance(modalElement).hide();
    ver_errores(xhr);
  });
}

function limpiarModalDetalleCliente() {
  const imagenDefault = apiUrl('/assets/modulo/persona/perfil/hombre.png');
  const campos = [
    'nombre', 'documento', 'celular', 'correo', 'codigo', 'actualizado',
    'tipo-persona', 'descripcion', 'nombre-comercial', 'nombre-natural',
    'apellido-paterno', 'apellido-materno', 'sexo', 'fecha-nacimiento',
    'estado-civil', 'nacionalidad', 'direccion', 'referencia', 'distrito',
    'provincia', 'departamento', 'ubigeo', 'pareja-nombre', 'pareja-documento',
    'pareja-celular'
  ];

  campos.forEach(function (campo) {
    $(`#detalle-cliente-${campo}`).text('-');
  });

  $('#detalle-cliente-subtitulo').text('Cargando informaciÃ³n del cliente...');
  $('#detalle-cliente-estado').html('<span class="badge bg-light text-muted">Cargando</span>');
  $('#detalle-cliente-imagen').attr('src', imagenDefault);
  $('#detalle-cliente-pareja-contenedor').hide();
}

function renderDetalleCliente(data) {
  const nombre = data.nombre_cliente || data.descripcion || data.nombre_comercial || data.nombre_persona_natural || 'Sin nombre';
  const tipoDocumento = data.tipo_documento_label || data.tipo_documento || '';
  const documento = [tipoDocumento, data.numero_documento].filter(Boolean).join(' ');
  const fotoPerfil = data.foto_perfil || 'hombre.png';
  const estadoActivo = String(data.estado_trash) === '1';

  $('#detalle-cliente-subtitulo').text(documento || 'InformaciÃ³n registrada del cliente');
  $('#detalle-cliente-imagen')
    .attr('src', apiUrl(`/assets/modulo/persona/perfil/${fotoPerfil}`))
    .attr('onerror', `this.src='${apiUrl('/assets/modulo/persona/perfil/hombre.png')}';`);
  $('#detalle-cliente-nombre').text(valorDetalle(nombre));
  $('#detalle-cliente-documento').text(valorDetalle(documento));
  $('#detalle-cliente-estado').html(
    estadoActivo
      ? '<span class="badge bg-success-transparent">Activo</span>'
      : '<span class="badge bg-danger-transparent">Eliminado</span>'
  );

  $('#detalle-cliente-celular').text(valorDetalle(data.celular));
  $('#detalle-cliente-correo').text(valorDetalle(data.correo));
  $('#detalle-cliente-codigo').text(valorDetalle(data.codigo));
  $('#detalle-cliente-actualizado').text(formatearFechaDetalle(data.updated_at));
  $('#detalle-cliente-tipo-persona').text(valorDetalle(data.tipo_persona_sunat));
  $('#detalle-cliente-descripcion').text(valorDetalle(data.descripcion));
  $('#detalle-cliente-nombre-comercial').text(valorDetalle(data.nombre_comercial));
  $('#detalle-cliente-nombre-natural').text(valorDetalle(data.nombre_persona_natural));
  $('#detalle-cliente-apellido-paterno').text(valorDetalle(data.apellido_paterno_persona_natural));
  $('#detalle-cliente-apellido-materno').text(valorDetalle(data.apellido_materno_persona_natural));
  $('#detalle-cliente-sexo').text(formatearSexoDetalle(data.sexo));
  $('#detalle-cliente-fecha-nacimiento').text(formatearFechaDetalle(data.fecha_nacimiento, 'DD/MM/YYYY'));
  $('#detalle-cliente-estado-civil').text(valorDetalle(data.estado_civil));
  $('#detalle-cliente-nacionalidad').text(valorDetalle(data.nacionalidad));
  $('#detalle-cliente-pareja-nombre').text(valorDetalle(data.conyuge_nombre));
  $('#detalle-cliente-pareja-documento').text(valorDetalle(
    [data.conyuge_tipo_documento_label, data.conyuge?.numero_documento].filter(Boolean).join(' ')
  ));
  $('#detalle-cliente-pareja-celular').text(valorDetalle(data.conyuge?.celular));
  $('#detalle-cliente-pareja-contenedor').toggle(data.estado_civil === 'CASADO' && Boolean(data.conyuge));
  $('#detalle-cliente-direccion').text(valorDetalle(data.direccion));
  $('#detalle-cliente-referencia').text(valorDetalle(data.direccion_referencia));
  $('#detalle-cliente-ubigeo').text(valorDetalle(data.cod_ubigeo));

  const distrito = data.distrito?.nombre || data.ubigeo_distrito?.nombre || data.iddistrito_nombre || '';
  const provincia = data.distrito?.provincia?.nombre || data.provincia_nombre || '';
  const departamento = data.distrito?.provincia?.departamento?.nombre || data.departamento_nombre || '';

  $('#detalle-cliente-distrito').text(valorDetalle(distrito || data.iddistrito));
  $('#detalle-cliente-provincia').text(valorDetalle(provincia));
  $('#detalle-cliente-departamento').text(valorDetalle(departamento));
}

function valorDetalle(value) {
  return value === null || value === undefined || value === '' ? '-' : String(value);
}

function formatearFechaDetalle(value, formato = 'DD/MM/YYYY HH:mm') {
  if (!value) return '-';
  if (typeof moment !== 'undefined') {
    return moment(value).isValid() ? moment(value).format(formato) : valorDetalle(value);
  }
  return valorDetalle(value);
}

function formatearSexoDetalle(value) {
  if (value === 'M') return 'Masculino';
  if (value === 'F') return 'Femenino';
  return valorDetalle(value);
}

function editarCliente(id) {
  if (!puedeCliente('editar')) {
    mostrarError('No tienes permiso para editar clientes.');
    return;
  }

  //abrirFormularioCliente(id);
  ver_editar_cliente(id);

}

function ver_editar_cliente(id) {

  $.getJSON(apiUrl(`/clientes/${id}/show`), function (e) {
      show_hide_form(2); // muestra el formulario (asumo que esta función existe)
      
      if (e.status == true) {
        //console.log(e);
        
          const data = e.data;

          // ========== CAMPOS OCULTOS Y BÁSICOS ==========
          $("#idpersona").val(data.idpersona);
          $("#codigo").val(data.codigo);
          $("#idcargo_trabajador").val(data.idcargo_trabajador); // si es un select, luego disparar cambio

          // ========== INFORMACIÓN GENERAL ==========
          $("#tipo_persona_sunat").val(data.tipo_persona_sunat).trigger('change');
          $("#tipo_documento").val(data.tipo_documento).trigger('change');
          $("#numero_documento").val(data.numero_documento);
          $("#descripcion").val(data.descripcion);
          $("#nombre_comercial").val(data.nombre_comercial);
          $("#nombre_persona_natural").val(data.nombre_persona_natural);
          $("#apellido_paterno_persona_natural").val(data.apellido_paterno_persona_natural);
          $("#apellido_materno_persona_natural").val(data.apellido_materno_persona_natural);
          $("#sexo").val(data.sexo).trigger('change');
          $("#fecha_nacimiento").val(data.fecha_nacimiento);
          $("#nacionalidad").val(data.nacionalidad);
          $("#estado_civil").val(data.estado_civil).trigger('change');
          cargarConyuge(data.conyuge);

          // ========== CONTACTO Y DIRECCIÓN ==========
          $("#celular").val(data.celular);
          $("#correo").val(data.correo);
          $("#direccion").val(data.direccion);
          $("#direccion_referencia").val(data.direccion_referencia);
          $("#cod_ubigeo").val(data.cod_ubigeo);
          console.log(data.iddistrito);
          
          // 🔁 Manejo especial del distrito
          if (data.iddistrito) {
              // Buscar el option cuyo atributo 'iddistrito' coincida
              const idDistritoNum = data.iddistrito;
              let optionEncontrado = false;

              $('#iddistrito option').each(function() {
                  if ($(this).attr('iddistrito') == idDistritoNum) {
                      $(this).prop('selected', true);
                      optionEncontrado = true;
                      return false; // salir del each
                  }
              });

              if (optionEncontrado) {
                  // Disparar el evento 'change' para que se ejecute la delegación que llena 
                  // los campos: iddistrito_envio, idprovincia, iddepartamento, etc.
                  $('#iddistrito').trigger('change');
              } else {
                  console.warn('No se encontró distrito con ID:', idDistritoNum);
              }
          }


          // ========== IMAGEN DE PERFIL ==========
          if (data.foto_perfil) {
              $("#imagenmuestra").attr('src', `assets/modulo/persona/perfil/${data.foto_perfil}`);
              // Si usas FilePond, puedes agregar el archivo existente:
              // const pond = FilePond.find('#imagen');
              // pond.addFile(data.foto_perfil);
          } else {
              $("#imagenmuestra").attr('src', 'assets/modulo/persona/perfil/hombre.png');
          }
          // Guardar el nombre actual para saber si se borra después
          $("#imagenactual").val(data.foto_perfil);


          // Mostrar contenedor del formulario, ocultar loading
          $("#cargando-1-formulario").show();
          $("#cargando-2-formulario").hide();

          // ========== REINICIALIZAR SELECTS (Choices.js / Select2) ==========
          // Si usas Choices.js, después de asignar valores debes actualizar
          if (typeof Choices !== 'undefined') {
              document.querySelectorAll('select').forEach(select => {
                  if (select.choicesInstance) {
                      select.choicesInstance.setChoiceByValue(select.value);
                  }
              });
          }
          // Si usas Select2
          $('select').trigger('change');
          actualizarIndicadorCamposRequeridos();

      } else {
          alert("No se encontró el cliente");
      }
  }).fail(function (xhr) { ver_errores(xhr); });
}

function eliminarCliente(id) {
  if (!puedeCliente('eliminar')) {
    mostrarError('No tienes permiso para eliminar clientes.');
    return;
  }

  if (typeof Swal === 'undefined') return;

  Swal.fire({
    title: 'Eliminar cliente',
    text: 'El cliente se marcara como eliminado. Desea continuar?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, eliminar',
    cancelButtonText: 'Cancelar',
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: apiUrl(`/clientes/${id}`),
      type: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf() },
      success: function (response) {
        if (response.status) {
          mostrarOk(response.message || 'Cliente eliminado correctamente.');
          recargarTablaClientes();
          return;
        }

        mostrarError(response.message || 'No se pudo eliminar el cliente.');
      },
      error: function () {
        mostrarError('Error al eliminar cliente.');
      },
    });
  });
}

function restaurarCliente(id) {
  if (!puedeCliente('editar')) {
    mostrarError('No tienes permiso para restaurar clientes.');
    return;
  }

  if (typeof Swal === 'undefined') return;

  Swal.fire({
    title: 'Restaurar cliente',
    text: 'El cliente volvera a estar activo. Desea continuar?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Si, restaurar',
    cancelButtonText: 'Cancelar',
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: apiUrl(`/clientes/${id}/restore`),
      type: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf() },
      success: function (response) {
        if (response.status) {
          mostrarOk(response.message || 'Cliente restaurado correctamente.');
          recargarTablaClientes();
          return;
        }

        mostrarError(response.message || 'No se pudo restaurar el cliente.');
      },
      error: function () {
        mostrarError('Error al restaurar cliente.');
      },
    });
  });
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


function guardar_y_editar_persona(e) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();

    var formData = new FormData($("#form-agregar-persona")[0]);

    var id = $("#idpersona").val();
    var accion = id === '' ? 'crear' : 'editar';

    if (!puedeCliente(accion)) {
        mostrarError(`No tienes permiso para ${accion} clientes.`);
        return;
    }

    var url_editar_crear = id === '' 
        ? apiUrl(`/clientes/store`) 
        : apiUrl(`/clientes/${id}/update`);
    
    if (id !== '') formData.append('_method', 'PUT');

    $.ajax({
        url: url_editar_crear,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (resp) {
            // Solo se ejecuta para códigos 2xx (200, 201, etc.)
            try {
                if (resp.status == true) {
                    tablaClientes.ajax.reload(null, false);
                    show_hide_form(1);
                    Swal.fire("Correcto!", resp.message || "Proveedor guardado correctamente", "success");
                    //limpiar_form_personal();
                } else {
                    // Si por alguna razón el servidor devuelve 200 pero status false (poco común)
                    ver_errores(resp);
                }
            } catch (err) {
                console.log('Error: ', err.message);
                toastr.error('Error temporal, contacte a soporte');
            }
            $(".guardar_registro_entidad").html('Guardar Cambios').removeClass('disabled');
        },
        error: function (jqXhr) {
            // Aquí se manejan errores HTTP (4xx, 5xx)
            try {
                // Intentar parsear la respuesta JSON (puede venir en jqXhr.responseJSON)
                const resp = jqXhr.responseJSON || {};
                
                // Verificar si es error de validación 422 y tiene el campo 'codigo'
                if (jqXhr.status === 422 && resp.data && resp.data.codigo) {
                    let errorMsg = resp.data.codigo;
                    if (Array.isArray(errorMsg)) errorMsg = errorMsg[0];
                    
                    if (errorMsg && errorMsg.includes("ya está en uso")) {
                        Swal.fire({
                            title: 'Código Duplicado',
                            text: '¿Desea que el sistema asigne automáticamente el siguiente código disponible?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, Asignar otro Código',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Obtener siguiente código
                                $.ajax({
                                    url: apiUrl('/entidad/siguiente-codigo'),
                                    type: 'GET',
                                    dataType: 'json',
                                    success: function(respuesta) {
                                        if (respuesta.status === true && respuesta.data) {
                                            $('#codigo').val(respuesta.data);
                                            // Habilitar botón y reintentar
                                            $("#guardar_registro_proyecto").html('Guardar Cambios').removeClass('disabled');
                                            guardar_y_editar_persona(); // Llamada recursiva
                                        } else {
                                            toastr.error('No se pudo obtener un código disponible');
                                            $("#guardar_registro_proyecto").html('Guardar Cambios').removeClass('disabled');
                                        }
                                    },
                                    error: function() {
                                        toastr.error('Error al obtener el nuevo código');
                                        $("#guardar_registro_proyecto").html('Guardar Cambios').removeClass('disabled');
                                    }
                                });
                            } else {
                                $("#guardar_registro_proyecto").html('Guardar Cambios').removeClass('disabled');
                            }
                        });
                        return; // Salir para no ejecutar ver_errores
                    }
                } else if (jqXhr.status === 422 && (
                    resp?.data?.numero_documento ||
                    resp?.errors?.numero_documento ||
                    resp?.message
                )) {

                      let errorMsg = resp?.data?.numero_documento || resp?.errors?.numero_documento || resp?.message;
                      if (Array.isArray(errorMsg)) errorMsg = errorMsg[0];

                      if (errorMsg) {
                          Swal.fire({
                              title: 'Documento ya registrado',
                              text: errorMsg,
                              icon: 'warning',
                              confirmButtonText: 'Aceptar'
                          }).then(() => {
                              $('#numero_documento').addClass('is-invalid').trigger('focus');
                          });
                          $(".guardar_registro_entidad").html('Guardar Cambios').removeClass('disabled');
                          return; // Salir para no ejecutar ver_errores
                      }

                } else {
                  
                }
                // Si no es error de código duplicado, mostrar errores normales
                ver_errores(jqXhr);
            } catch (err) {
                console.log('Error en bloque error:', err);
                ver_errores(jqXhr);
            }
            $(".guardar_registro_entidad").html('Guardar Cambios').removeClass('disabled');
        },
        xhr: function () {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    var percentComplete = (evt.loaded / evt.total) * 100;
                    $("#barra_progress_entidad").css({ "width": percentComplete + '%' }).text(percentComplete.toFixed(2) + " %");
                }
            }, false);
            return xhr;
        },
        beforeSend: function () {
            $(".guardar_registro_entidad").html('Guardando <i class="ri-loop-left-line"></i>').addClass('disabled');
            $("#barra_progress_entidad").css({ width: "0%" }).text("0%");
        },
        complete: function () {
            $("#barra_progress_entidad").css({ width: "0%" }).text("0%");
        }
    });
}

function escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function soloNumeros(event) {
    var key = event.keyCode || event.which;
    var teclasPermitidas = [8, 9, 46, 37, 38, 39, 40];
    if (teclasPermitidas.includes(key)) return true;
    var char = String.fromCharCode(key);
    return /^[0-9]$/.test(char);
}

// Función que actualiza según ambos selects
function actualizarCampos() {
    var tipoSunat = $('#tipo_persona_sunat').val();   // "NATURAL" o "JURIDICA"
    var tipoDoc = $('#tipo_documento').val();         // "1", "6" o "7"
    var $selectipoDoc = $('#tipo_documento');


    // Habilitar todas primero
    $selectipoDoc.find('option').prop('disabled', false);
    
    // Decidir si mostrar persona natural o jurídica
    // Puedes priorizar tipoSunat o combinarlo con $selectipoDoc. Por ejemplo:
    var esNatural = false;
    
    if (tipoSunat === 'NATURAL') {
        esNatural = true;
    } else if (tipoSunat === 'JURIDICA') {
        esNatural = false;
    } else {
        // Si no hay selección en Sunat, usar $selectipoDoc como respaldo
        esNatural = (tipoDoc === '1' || tipoDoc === '7');
    }
    
    // Aplicar visibilidad
    if (esNatural) {

        if (tipoSunat==='NATURAL' && ( $selectipoDoc.val() == null || $selectipoDoc.val() == undefined)) {
          $selectipoDoc.val('1').trigger('change');
        }

        $('.div_nombre_comercial').hide();
        $('.div_nombre_persona_natural').show();
        $('.div_apellido_paterno_persona_natural').show();
        $('.div_apellido_materno_persona_natural').show();
        $('.div_sexo').show();
        $('.div_fecha_nacimiento').show();
        $('.div_nacionalidad').show();

        //$('#nombre_comercial').val('');

        $('.lebel_name_descrip').text('Nombres y Apellidos');
        $('#descripcion').attr('placeholder', 'Ej. Juan Pérez Gómez');
        // Aplica validación dinámica
        //actualizarReglasValidacion(true);
    } else {

        // Persona Jurídica: solo RUC
        $selectipoDoc.find('option[value="1"]').prop('disabled', true);
        $selectipoDoc.find('option[value="7"]').prop('disabled', true);

         if (tipoSunat==='JURIDICA' && ($selectipoDoc.val() != '6' || $selectipoDoc.val() == null || $selectipoDoc.val() == undefined)) {
          $selectipoDoc.val('6').trigger('change');
        }

        $('.lebel_name_descrip').text('Razon Social');
        $('#descripcion').attr('placeholder', 'Ej. Empresa SAC');


        $('.div_nombre_comercial').show();
        $('.div_nombre_persona_natural').hide();
        $('.div_apellido_paterno_persona_natural').hide();
        $('.div_apellido_materno_persona_natural').hide();
        $('.div_sexo').hide();
        $('.div_fecha_nacimiento').hide();
        $('.div_nacionalidad').show();


        $('#nombre_persona_natural').val('');
        $('#apellido_paterno_persona_natural').val('');
        $('#apellido_materno_persona_natural').val('');
        $('#sexo').val('');
        $('#fecha_nacimiento').val('');

        
        $('#tratamiento_pers_natural').val('');

        //actualizarReglasValidacion(false);
    }

    actualizarIndicadorCamposRequeridos();
}

function actualizarCamposPareja() {
    const esCasado = $('#estado_civil').val() === 'CASADO';

    $('.div_datos_pareja').toggle(esCasado);

    if (!esCasado) {
        limpiarConyuge();
    }

    actualizarIndicadorCamposRequeridos();
}

function limpiarConyuge() {
    documentoConyugeCargado = '';
    $('#idconyuge').val('');
    $('#conyuge_tipo_documento').val('').trigger('change.select2');
    $('#conyuge_numero_documento').val('');
    $('#conyuge_descripcion').val('').prop('readonly', true);
    $('#conyuge_celular').val('').prop('readonly', true);
    $('#conyuge_estado_busqueda')
      .removeClass('bg-success-transparent bg-warning-transparent')
      .addClass('bg-light text-muted')
      .text('Sin buscar');
}

function cargarConyuge(conyuge) {
    if (!conyuge) {
        limpiarConyuge();
        return;
    }

    cargandoConyuge = true;

    try {
        $('#conyuge_tipo_documento').val(conyuge.tipo_documento || '').trigger('change');
        $('#conyuge_numero_documento').val(conyuge.numero_documento || '');
        $('#conyuge_descripcion').val(conyuge.descripcion || conyuge.nombre_persona_natural || '').prop('readonly', true);
        $('#conyuge_celular').val(conyuge.celular || '').prop('readonly', true);
        $('#idconyuge').val(conyuge.idpersona || '');
        documentoConyugeCargado = `${conyuge.tipo_documento || ''}|${conyuge.numero_documento || ''}`;
        $('#conyuge_estado_busqueda')
          .removeClass('bg-light text-muted bg-warning-transparent')
          .addClass('bg-success-transparent')
          .text('Persona encontrada');
    } finally {
        cargandoConyuge = false;
    }
}

function buscarConyugePorDocumento() {
    const tipoDocumento = ($('#conyuge_tipo_documento').val() || '').trim();
    const numeroDocumento = ($('#conyuge_numero_documento').val() || '').trim();

    if (!tipoDocumento || numeroDocumento.length < 4) {
        mostrarError('Ingrese el tipo y número de documento del cónyuge.');
        return;
    }

    $.getJSON(apiUrl('/clientes/buscar-por-documento'), {
        tipo_documento: tipoDocumento,
        numero_documento: numeroDocumento,
    }).done(function (response) {
        const resultado = response?.data;

        if (response?.status !== true || !resultado?.existe_persona) {
            $('#idconyuge').val('');
            $('#conyuge_descripcion').val('').prop('readonly', true);
            $('#conyuge_celular').val('').prop('readonly', true);
            $('#conyuge_estado_busqueda')
              .removeClass('bg-light text-muted bg-success-transparent')
              .addClass('bg-warning-transparent')
              .text('Registrar nueva persona');
            abrirModalRegistrarConyuge(tipoDocumento, numeroDocumento);
            mostrarInfo('La persona no existe. Complete sus datos para registrarla como cónyuge.');
            return;
        }

        const persona = resultado.persona;
        const idCliente = Number($('#idpersona').val() || 0);

        if (Number(persona.idpersona) === idCliente) {
            mostrarError('Una persona no puede registrarse como su propio cónyuge.');
            return;
        }

        if (persona.idconyuge && Number(persona.idconyuge) !== idCliente) {
            mostrarError('La persona encontrada ya tiene otro cónyuge registrado.');
            return;
        }

        cargarConyuge(persona);
    }).fail(function (xhr) {
        ver_errores(xhr);
    });
}

function limpiarModalRegistrarConyuge() {
    const $form = $('#form-registrar-conyuge');

    if ($form.length && $form[0]) {
        $form[0].reset();
    }

    $form.data('validator')?.resetForm();
    $('#modal_conyuge_tipo_documento, #modal_conyuge_sexo, #modal_conyuge_nacionalidad').val('').trigger('change');
    $('#modal_conyuge_iddistrito').val(null).trigger('change');
    $('#modal_conyuge_iddistrito_envio, #modal_conyuge_cod_ubigeo').val('');
    $('#form-registrar-conyuge .is-invalid').removeClass('is-invalid');
    $('#form-registrar-conyuge .is-valid').removeClass('is-valid');
    $('#form-registrar-conyuge .select2-selection').removeClass('is-invalid is-valid');
    $('#form-registrar-conyuge .invalid-feedback').remove();
}

function abrirModalRegistrarConyuge(tipoDocumento, numeroDocumento) {
    limpiarModalRegistrarConyuge();
    $('#modal_conyuge_tipo_documento').val(tipoDocumento).trigger('change');
    $('#modal_conyuge_numero_documento').val(numeroDocumento);

    const modalElement = document.getElementById('modal-registrar-conyuge');

    if (modalElement && window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
        return;
    }

    $('#modal-registrar-conyuge').modal('show');
}

function cerrarModalRegistrarConyuge() {
    const modalElement = document.getElementById('modal-registrar-conyuge');

    if (modalElement && window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
        return;
    }

    $('#modal-registrar-conyuge').modal('hide');
}

function buscarSunatReniecConyugeModal() {
    buscar_sunat_reniec(
      '#form-registrar-conyuge',
      '_modal_conyuge',
      '#modal_conyuge_tipo_documento',
      '#modal_conyuge_numero_documento',
      '#modal_conyuge_descripcion',
      '#modal_conyuge_nombre_comercial',
      '#modal_conyuge_nombre',
      '#modal_conyuge_apellido_paterno',
      '#modal_conyuge_apellido_materno',
      '#modal_conyuge_direccion',
      '#modal_conyuge_iddistrito',
      '#modal_conyuge_cod_ubigeo',
      '#modal_conyuge_tipo_persona_sunat'
    );
}

function buscarEntidadExistentePorDocumentoConyugeModal() {
    const tipoDocumento = ($('#modal_conyuge_tipo_documento').val() || '').trim();
    const numeroDocumento = ($('#modal_conyuge_numero_documento').val() || '').trim();

    if (!tipoDocumento || numeroDocumento.length < 4) {
        mostrarError('Ingrese el tipo y n\u00famero de documento del c\u00f3nyuge.');
        return;
    }

    $.getJSON(apiUrl('/clientes/buscar-por-documento'), {
        tipo_documento: tipoDocumento,
        numero_documento: numeroDocumento,
    }).done(function (response) {
        const resultado = response?.data;

        if (response?.status !== true || !resultado?.existe_persona) {
            buscarSunatReniecConyugeModal();
            return;
        }

        const persona = resultado.persona;
        const idCliente = Number($('#idpersona').val() || 0);

        if (Number(persona.idpersona) === idCliente) {
            mostrarError('Una persona no puede registrarse como su propio c\u00f3nyuge.');
            return;
        }

        if (persona.idconyuge && Number(persona.idconyuge) !== idCliente) {
            mostrarError('La persona encontrada ya tiene otro c\u00f3nyuge registrado.');
            return;
        }

        cargarConyuge(persona);
        cerrarModalRegistrarConyuge();
        mostrarOk('Persona existente seleccionada como c\u00f3nyuge.');
    }).fail(function (xhr) {
        ver_errores(xhr);
    });
}

function mostrarErroresModalConyuge(xhr) {
    const errores = xhr.responseJSON?.errors || {};
    const mensaje = xhr.responseJSON?.message || 'No se pudo registrar el c\u00f3nyuge.';

    $('#form-registrar-conyuge .is-invalid').removeClass('is-invalid');
    $('#form-registrar-conyuge .invalid-feedback').remove();

    Object.entries(errores).forEach(function ([campo, mensajes]) {
        const $campo = $(`#modal_conyuge_${campo}`);
        const texto = Array.isArray(mensajes) ? mensajes[0] : mensajes;

        $campo.addClass('is-invalid');
        $('<div class="invalid-feedback"></div>').text(texto).insertAfter($campo);
    });

    mostrarError(mensaje);
}

function inicializarValidacionModalConyuge() {
    const $form = $('#form-registrar-conyuge');

    $form.validate({
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        ignore: '.select2-search__field, .select2-input, .select2-focusser',
        rules: {
            tipo_documento: { required: true },
            numero_documento: { required: true, minlength: 4, maxlength: 20 },
            descripcion: { required: true, minlength: 4, maxlength: 255 },
            sexo: { required: true },
            fecha_nacimiento: { required: true },
            nacionalidad: { required: true },
            direccion: { required: true },
        },
        messages: {
            tipo_documento: { required: 'Seleccione el tipo de documento.' },
            numero_documento: {
                required: 'Ingrese el documento.',
                minlength: 'Ingrese al menos {0} caracteres.',
                maxlength: 'Ingrese como m\u00e1ximo {0} caracteres.',
            },
            descripcion: {
                required: 'Ingrese los nombres y apellidos.',
                minlength: 'Ingrese al menos {0} caracteres.',
                maxlength: 'Ingrese como m\u00e1ximo {0} caracteres.',
            },
            sexo: { required: 'Seleccione el sexo.' },
            fecha_nacimiento: { required: 'Ingrese la fecha de nacimiento.' },
            nacionalidad: { required: 'Seleccione la nacionalidad.' },
            direccion: { required: 'Ingrese la direcci\u00f3n.' },
        },
        errorElement: 'div',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');

            if ($(element).hasClass('select2-hidden-accessible')) {
                colocarErrorSelect2(error, element);
                return;
            }

            if ($(element).closest('.input-group').length) {
                error.insertAfter($(element).closest('.input-group'));
                return;
            }

            error.insertAfter(element);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
            obtenerContenedorSelect2(element).find('.select2-selection').addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
            obtenerContenedorSelect2(element).find('.select2-selection').removeClass('is-invalid').addClass('is-valid');
        },
    });

    $('#modal_conyuge_tipo_documento, #modal_conyuge_sexo, #modal_conyuge_nacionalidad').on('change', function () {
        if ($form.data('validator')) {
            $form.validate().element(this);
        }
    });
}

// Escuchar cambios en ambos selects
$('#tipo_persona_sunat, #tipo_documento').on('change', function() {
    actualizarCampos();
});

$('#estado_civil').on('change', function() {
    actualizarCamposPareja();
});

$('#btn-buscar-conyuge').on('click', buscarConyugePorDocumento);
$('#btn-limpiar-conyuge').on('click', limpiarConyuge);
$('#btn-buscar-conyuge-modal').on('click', buscarEntidadExistentePorDocumentoConyugeModal);
$('#modal_conyuge_iddistrito').on('change', function () {
    const $option = $(this).find('option:selected');

    $('#modal_conyuge_iddistrito_envio').val($option.attr('iddistrito') || '');
    $('#modal_conyuge_cod_ubigeo').val($option.attr('iddistrito') || '');
});
$('#modal-registrar-conyuge').on('hidden.bs.modal', limpiarModalRegistrarConyuge);
$('#form-registrar-conyuge').on('submit', function (event) {
    event.preventDefault();

    const $form = $(this);
    const $button = $('#btn-guardar-conyuge');

    if (!$form.valid()) {
        return;
    }

    $button.prop('disabled', true);

    $.ajax({
        url: apiUrl('/clientes/registrar-conyuge'),
        method: 'POST',
        data: $form.serialize(),
    }).done(function (response) {
        cargarConyuge(response.data);
        cerrarModalRegistrarConyuge();
        mostrarOk(response.message || 'C\u00f3nyuge registrado correctamente.');
    }).fail(function (xhr) {
        mostrarErroresModalConyuge(xhr);
    }).always(function () {
        $button.prop('disabled', false);
    });
});
inicializarValidacionModalConyuge();
$('#conyuge_tipo_documento, #conyuge_numero_documento').on('change input', function () {
    if (cargandoConyuge) {
        return;
    }

    const documentoActual = `${$('#conyuge_tipo_documento').val() || ''}|${$('#conyuge_numero_documento').val() || ''}`;

    if ($('#idconyuge').val() && documentoActual !== documentoConyugeCargado) {
        documentoConyugeCargado = '';
        $('#idconyuge').val('');
        $('#conyuge_descripcion, #conyuge_celular').val('').prop('readonly', true);
        $('#conyuge_estado_busqueda')
          .removeClass('bg-success-transparent')
          .addClass('bg-warning-transparent')
          .text('Documento modificado');
    }
});

// Ejecutar al inicio
actualizarCampos();
actualizarCamposPareja();


function buscarEntidadExistentePorDocumento() {
    const idPersona = ($('#idpersona').val() || '').trim();
    const tipoDocumento = ($('#tipo_documento').val() || '').trim();
    const numeroDocumento = ($('#numero_documento').val() || '').trim();

    if (tipoDocumento === '' || numeroDocumento.length < 4) return;

    if (idPersona !== '') {
        buscarSunatReniecCliente();
        return;
    }

    $.ajax({
        url: apiUrl('/clientes/buscar-por-documento'),
        type: 'GET',
        dataType: 'json',
        data: {
            tipo_documento: tipoDocumento,
            numero_documento: numeroDocumento,
            idpersona_tipo: '3'   // tipo que nos interesa (cliente)
        }
    }).done(function (res) {
        if (res?.status !== true) return;

        const data = res.data;

        // Caso 1: Persona no existe
        if (!data.existe_persona) {
            //Swal.fire('Sin resultados', 'No se encontró ninguna persona con ese documento.', 'info');
            buscarSunatReniecCliente();
            return;
        }

        // Caso 2: Persona existe pero NO tiene el tipo 3
        if (data.existe_persona && !data.tiene_tipo_solicitado) {
            Swal.fire({
                title: 'Persona encontrada',
                text: 'La persona existe pero no está registrada como cliente. ¿Desea agregarlo como cliente?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, agregar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    asociarTipoPersona(data.idpersona, '3');
                }
            });
            return;
        }

        // Caso 3: Persona existe y YA tiene tipo 3
        if (data.existe_persona && data.tiene_tipo_solicitado) {
            Swal.fire('Cliente existente', 'Esta persona ya está registrada como cliente.', 'warning');
            llenarFormularioCliente(data.persona);
            show_hide_form(2);
        }
    });
}

function buscarSunatReniecCliente() {
    buscar_sunat_reniec(
      '#form-agregar-persona',
      '',
      '#tipo_documento',
      '#numero_documento',
      '#descripcion',
      '#nombre_comercial',
      '#nombre_persona_natural',
      '#apellido_paterno_persona_natural',
      '#apellido_materno_persona_natural',
      '#direccion',
      '#iddistrito',
      '#cod_ubigeo',
      '#tipo_persona_sunat'
    );
}

function llenarFormularioCliente(data) {
    if (!data) return;

    $("#idpersona").val('');
    $("#codigo").val(data.codigo || '');
    $("#idcargo_trabajador").val(data.idcargo_trabajador || '');

    $("#tipo_persona_sunat").val(data.tipo_persona_sunat || '').trigger('change');
    $("#tipo_documento").val(data.tipo_documento || '').trigger('change');
    $("#numero_documento").val(data.numero_documento || '');
    $("#descripcion").val(data.descripcion || '');
    $("#nombre_comercial").val(data.nombre_comercial || '');
    $("#nombre_persona_natural").val(data.nombre_persona_natural || '');
    $("#apellido_paterno_persona_natural").val(data.apellido_paterno_persona_natural || '');
    $("#apellido_materno_persona_natural").val(data.apellido_materno_persona_natural || '');
    $("#sexo").val(data.sexo || '').trigger('change');
    $("#fecha_nacimiento").val(data.fecha_nacimiento || '');
    $("#nacionalidad").val(data.nacionalidad || '').trigger('change');
    $("#estado_civil").val(data.estado_civil || '').trigger('change');
    cargarConyuge(data.conyuge);

    $("#celular").val(data.celular || '');
    $("#correo").val(data.correo || '');
    $("#direccion").val(data.direccion || '');
    $("#direccion_referencia").val(data.direccion_referencia || '');
    $("#cod_ubigeo").val(data.cod_ubigeo || '');

    if (data.iddistrito) {
        let optionEncontrado = false;

        $('#iddistrito option').each(function() {
            if ($(this).attr('iddistrito') == data.iddistrito) {
                $(this).prop('selected', true);
                optionEncontrado = true;
                return false;
            }
        });

        if (optionEncontrado) {
            $('#iddistrito').trigger('change');
        }
    } else {
        $("#iddistrito").val(null).trigger('change');
        $("#iddistrito_envio").val('');
        $("#idprovincia").val('');
        $("#iddepartamento").val('');
    }

    if (data.foto_perfil) {
        $("#imagenmuestra").attr('src', apiUrl(`/assets/modulo/persona/perfil/${data.foto_perfil}`));
    } else {
        $("#imagenmuestra").attr('src', apiUrl('/assets/modulo/persona/perfil/hombre.png'));
    }

    $("#imagen").val("");
    $("#imagenactual").val(data.foto_perfil || '');
    $("#cargando-1-formulario").show();
    $("#cargando-2-formulario").hide();
    $("#barra_progress_entidad").css({ width: "0%" }).text("0%");
    $(".valido_novalido").html('<span class="badge bg-warning">Cliente existente</span>');

    actualizarCampos();
    actualizarIndicadorCamposRequeridos();
}

function asociarTipoPersona(idpersona, idpersona_tipo) {
    $.ajax({
        url: apiUrl('/personas/asociar-tipo'),
        type: 'POST',
        data: {
            idpersona: idpersona,
            idpersona_tipo: idpersona_tipo,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status) {
                Swal.fire('Éxito', 'Se asignó el tipo Cliente correctamente.', 'success');
                // Aquí puedes volver a cargar los datos o limpiar el formulario
                  tablaClientes.ajax.reload(null, false);
                  show_hide_form(1);
            } else {
                Swal.fire('Error', 'No se pudo asignar el tipo.', 'error');
            }
        }
    });
}

/*function cargarDatosPersona(persona) {
    $('#nombres').val(persona.nombres);
    $('#apellido_paterno').val(persona.apellido_paterno);
    $('#apellido_materno').val(persona.apellido_materno);
    // ... otros campos
}*/


// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// ═══════                                       J Q   F O R M   V A L I D A T I O N S                                                              ═══════
// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
$(function () {    

  // validamos el formulario  

  function activarPestanaConError(element) {
    const tabPane = $(element).closest('.tab-pane');
    if (!tabPane.length) return;

    const paneId = tabPane.attr('id');
    if (!paneId) return;

    const tabTrigger = document.querySelector(`[data-bs-target="#${paneId}"]`);
    if (!tabTrigger) return;

    bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
  }

  function marcarEstadoSelect2(element, isInvalid) {
    const $element = $(element);
    if (!$element.hasClass('select2-hidden-accessible')) return;

    const $selection = obtenerContenedorSelect2(element).find('.select2-selection');
    $selection.toggleClass('is-invalid', isInvalid);
    $selection.toggleClass('is-valid', !isInvalid);
  }

  $('#tipo_persona_sunat').on('change', function() { $(this).trigger('blur'); });
  $('#tipo_documento').on('change', function() { $(this).trigger('blur'); });
  $('#nacionalidad').on('change', function() { $(this).trigger('blur'); });

  $("#form-agregar-persona").validate({ 
    // opciones globales, si las necesitas
    errorClass: 'is-invalid',
    validClass: 'is-valid',
    ignore: ".select2-search__field, .select2-input, .select2-focusser",
    rules: {
      tipo_persona_sunat: { required: true },
      tipo_documento:     { required: true},
      descripcion:        { required: true, minlength: 4, maxlength: 300 },
      numero_documento:   { required: true, minlength: 4, maxlength: 300 },
      nacionalidad:        { required: true, },
      direccion:          { required: true, },  
      conyuge_tipo_documento: {
        required: function () { return $('#estado_civil').val() === 'CASADO'; }
      },
      conyuge_numero_documento: {
        required: function () { return $('#estado_civil').val() === 'CASADO'; }
      },
      conyuge_descripcion: {
        required: function () { return $('#estado_civil').val() === 'CASADO'; }
      },

    },
    messages: {
      tipo_persona_sunat:  { required: "Campo requerido." },
      tipo_documento:      { required: "Campo requerido.", minlength: "MÍNIMO {0} caracteres.", maxlength: "MÁXIMO {0} caracteres.", },
      descripcion:         { required: "Campo requerido.", minlength: "MÍNIMO {0} caracteres.", maxlength: "MÁXIMO {0} caracteres.", },
      numero_documento:    { required: "Campo requerido." },
      nacionalidad:        { required: "Campo requerido.", },
      direccion:           { required: "Campo requerido.", },
      conyuge_tipo_documento: { required: "Seleccione el tipo de documento del cónyuge." },
      conyuge_numero_documento: { required: "Ingrese el documento del cónyuge." },
      conyuge_descripcion: { required: "Ingrese los nombres y apellidos del cónyuge." },
    },
    
    errorElement: "div",

    errorPlacement: function (error, element) {
      error.addClass("invalid-feedback");

      if ($(element).hasClass('select2-hidden-accessible')) {
        colocarErrorSelect2(error, element);
        return;
      }

      const $inputGroup = $(element).closest('.input-group');
      if ($inputGroup.length) {
        error.insertAfter($inputGroup);
        return;
      }

      const $target = element.closest(".form-group");
      if ($target.length) {
        $target.append(error);
        return;
      }

      error.insertAfter(element);
    },

    highlight: function (element, errorClass, validClass) {
      $(element).addClass("is-invalid").removeClass("is-valid");
      marcarEstadoSelect2(element, true);
    },

    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass("is-invalid").addClass("is-valid");
      marcarEstadoSelect2(element, false);
    },

    invalidHandler: function (event, validator) {
      if (!validator.errorList.length) return;

      const firstInvalid = validator.errorList[0].element;
      if ($(firstInvalid).attr('id') === 'cantidad_tipos_stakeholder') {
        const notificationPane = document.getElementById('notification-tab-pane');
        if (notificationPane) {
          activarPestanaConError(notificationPane);
        }
      }
      activarPestanaConError(firstInvalid);

      setTimeout(function () {
        $(firstInvalid).trigger('focus');
      }, 150);
    },

    submitHandler: function (e) {
      $(".modal-body").animate({ scrollTop: $(document).height() }, 600); // Scrollea hasta abajo de la página
      guardar_y_editar_persona(e);       
    },
  });


  $('#form-agregar-persona').on('input change keyup', 'input, select, textarea', function () {
    actualizarIndicadorCamposRequeridos();
  });

  $('#tipo_persona_sunat').rules('add', { required: true, messages: {  required: "Campo requerido" } });
  $('#tipo_documento').rules('add', { required: true, messages: {  required: "Campo requerido" } });
  $('#nacionalidad').rules('add', { required: true, messages: {  required: "Campo requerido" } });
  actualizarIndicadorCamposRequeridos();
  
  // 3. Finalmente, aplicar visibilidad de campos y reglas dinámicas
  //actualizarCampos();  // <-- aquí dentro
});

function actualizarReglasValidacion(esNatural) {
    var $form = $('#form-agregar-persona');
    var validator = $form.validate();
    
    // Si el validador no está inicializado, salir silenciosamente
    if (!validator) return;

    // Función auxiliar para remover regla de un campo (si existe)
    function removerRegla(id) {
        var $campo = $form.find(id);
        if ($campo.length) {
            $campo.rules('remove', 'required');
        }
    }

    // Función auxiliar para agregar regla required a un campo (si existe)
    function agregarRegla(id, mensaje) {
        var $campo = $form.find(id);
        if ($campo.length) {
            $campo.rules('add', {
                required: true,
                messages: { required: mensaje }
            });
        }
    }

    // 1. Limpiar reglas previas de los campos dinámicos
    removerRegla('#sexo');
    removerRegla('#fecha_nacimiento');
    removerRegla('#nacionalidad');
    removerRegla('#nombre_comercial');

    // 2. Aplicar reglas según el tipo de persona
    if (esNatural) {
        agregarRegla('#sexo', 'Seleccione el sexo');
        agregarRegla('#fecha_nacimiento', 'Ingrese fecha de nacimiento');
        agregarRegla('#nacionalidad', 'Seleccione nacionalidad');
        // nombre_comercial no es requerido → ya se removió
    } else {
        agregarRegla('#nombre_comercial', 'Ingrese el nombre comercial');
    }
}

function cambiarImagen() {
  var imagenInput = document.getElementById('imagen');
  imagenInput.click();
}

function removerImagen() {
  // var imagenMuestra = document.getElementById('imagenmuestra');
  // var imagenActualInput = document.getElementById('imagenactual');
  // var imagenInput = document.getElementById('imagen');
  // imagenMuestra.src = '../assets/images/faces/9.jpg';
  $("#imagenmuestra").attr("src", "../assets/modulo/persona/perfil/no-perfil.jpg");
  // imagenActualInput.value = '';
  // imagenInput.value = '';
  $("#imagen").val("");
  $("#imagenactual").val("");
}

// Esto se encarga de mostrar la imagen cuando se selecciona una nueva
document.addEventListener('DOMContentLoaded', function () {
  var imagenMuestra = document.getElementById('imagenmuestra');
  var imagenInput = document.getElementById('imagen');

  imagenInput.addEventListener('change', function () {
    if (imagenInput.files && imagenInput.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) { imagenMuestra.src = e.target.result; }
      reader.readAsDataURL(imagenInput.files[0]);
    }
  });
});



function ver_img(img, nombre) {
  $(".title-ver-imgenes").html(`- ${nombre}`);
  $('#modal-ver-imgenes').modal("show");
  $('.html_modal_ver_imgenes').html(doc_view_extencion(img, 'assets/modulo/persona/perfil/hombre.png', '100%', '550'));
  $(`.jq_image_zoom`).zoom({ on: 'grab' });
}
