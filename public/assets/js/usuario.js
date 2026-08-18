let tablaUsuarios = null;
let usuarioSeleccionadoId = null;
let usuariosSeleccionadosIds = new Set();
let usuariosSeleccionadosCorreos = new Map();
let authUserId = null;
let twoFactorActivationPending = false;
let permisosCatalogo = [];
let seriesCatalogo = [];
let emailValidationTimer = null;

function puedeUsuarioSistema(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('usuarios_del_sistema', accion);
}
let seleccionDragPermisos = {
  active: false,
  moved: false,
  startX: 0,
  startY: 0,
};
let seleccionDragUsuarios = {
  active: false,
  moved: false,
  startX: 0,
  startY: 0,
  startRow: null,
  lastRow: null,
  previousIds: new Set(),
  previousCorreos: new Map(),
};

// ============================================================================
// INICIO
// ============================================================================

$(function () {
  authUserId = Number($('#body-usuario').data('auth-user-id') || 0) || null;

  inicializarTablaUsuarios();
  inicializarMenuContextualUsuario();
  inicializarFormularioUsuario();

  $(document).on('hidden.bs.modal', '#modal-2fa-usuario', function () {
    if (!twoFactorActivationPending) {
      return;
    }

    twoFactorActivationPending = false;
    deshabilitar2FA().always(function () {
      recargarTablaUsuarios();
    });
  });

  $('#btn-nuevo-usuario').on('click', function () {
    if (!puedeUsuarioSistema('crear')) {
      mostrarError('No tienes permiso para crear usuarios.');
      return;
    }

    prepararNuevoUsuario();
  });

  $('#btn-regresar-usuario').on('click', function () {
    show_hide_form(1);
  });
});

// ============================================================================
// HELPERS DE RUTAS Y SEGURIDAD
// ============================================================================

function apiUrl(path) {
  const base = document.querySelector('meta[name="app-url"]')?.content || '';
  return base + path;
}

function csrf() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function fortifyHeaders() {
  return {
    'X-CSRF-TOKEN': csrf(),
    Accept: 'application/json',
  };
}

function show_hide_form(flag) {
  if ( flag == 1) {
    $('#div-tabla-usuarios').show();
    $('#div-formulario-usuario').hide();
    $('#btn-nuevo-usuario').toggle(puedeUsuarioSistema('crear'));
    $('#btn-regresar-usuario').hide();
  } else if ( flag == 2) {
    $('#div-tabla-usuarios').hide();
    $('#div-formulario-usuario').show();
    $('#btn-nuevo-usuario').hide();
    $('#btn-regresar-usuario').show();
  } else if ( flag == 3) {
  } else if ( flag == 4) {

  }
}

// ============================================================================
// DATATABLE DE USUARIOS
// ============================================================================

function inicializarTablaUsuarios() {
  tablaUsuarios = $('#tabla-usuarios').DataTable({
    responsive: true,
    processing: true,
    serverSide: true,
    deferRender: true,
    searchDelay: 400,
    pageLength: 10,
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
        exportOptions: { columns: [1, 2, 3, 4, 5] },
        title: 'Lista de usuarios',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
        footer: true,
      },
    ],
    ajax: {
      url: apiUrl('/usuarios/listar'),
      type: 'GET',
      headers: { 'X-CSRF-TOKEN': csrf() },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarError(response.message || 'No se pudo cargar la lista de usuarios.');
          return [];
        }

        return response.data || [];
      },
      error: function () {
        mostrarError('Error al consultar usuarios.');
      },
    },
    columns: [      
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const esUsuarioSesion = esUsuarioAutenticado(row.id);
          const eliminarDisabled = esUsuarioSesion ? 'disabled' : '';
          const eliminarTitle = esUsuarioSesion
            ? 'No puedes eliminar tu usuario de sesion'
            : 'Eliminar';

          const acciones = [`
            <button type="button" class="btn btn-sm btn-icon btn-info btn-ver-usuario" data-id="${row.id}" data-bs-toggle="tooltip" title="Ver">
              <i class="ri-eye-line"></i>
            </button>`];

          if (puedeUsuarioSistema('editar')) {
            acciones.push(`
            <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-usuario" data-id="${row.id}" data-bs-toggle="tooltip" title="Editar">
              <i class="ri-edit-line"></i>
            </button>`);
          }

          if (puedeUsuarioSistema('eliminar')) {
            acciones.push(`
            <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-usuario" data-id="${row.id}" data-bs-toggle="tooltip" title="${eliminarTitle}" ${eliminarDisabled}>
              <i class="ri-delete-bin-line"></i>
            </button>`);
          }

          return acciones.join('');
        },
      },
      {
        data: null,
        className: 'usuario-persona',
        render: function (_data, _type, row) {
          if (!row.persona_descripcion) {
            return '<span class="text-muted">Sin persona vinculada</span>';
          }

          const documento = [row.persona_tipo_documento, row.persona_numero_documento].filter(Boolean).join(' ');
          const fotoPerfil = row.persona_foto_perfil || 'hombre.png';
          const fotoPerfilUrl = apiUrl(`/assets/modulo/persona/perfil/${fotoPerfil}`);
          const codigo = row.persona_codigo ? ` | codigo: ${escapeHtml(row.persona_codigo)}` : '';

          return `
            <div class="d-flex flex-wrap align-items-center justify-content-between">
              <div class="d-flex align-items-center">
                <div class="me-2 lh-1">
                  <span class="avatar avatar-md">
                    <img src="${fotoPerfilUrl}" alt="" onerror="this.src='${apiUrl('/assets/modulo/persona/perfil/hombre.png')}';">
                  </span>
                </div>
                <div>
                  <p class="fw-semibold mb-0">${escapeHtml(row.persona_descripcion || '-')}</p>
                  <span class="text-muted fs-12 d-inline-flex">${escapeHtml(documento || 'Sin documento')}${codigo}</span>
                </div>
              </div>
            </div>
          `;
        },
      },
      {
        data: 'email',
        render: function (data, _type, row) {
          const badgeSesion = esUsuarioAutenticado(row.id)
            ? '<span class="badge bg-info-transparent ms-2">Tu sesion</span>'
            : '';
          const badge2fa = tiene2FAActivo(row)
            ? '<span class="badge bg-success-transparent ms-2">2FA</span>'
            : '<span class="badge bg-light text-muted ms-2">Sin 2FA</span>';

          return `<span class="fw-semibold">${escapeHtml(data || '')}</span>${badgeSesion}${badge2fa}`;
        },
      },
      {
        data: 'permisos_total',
        className: 'text-center',
        render: function (total) {
          return `<span class="badge bg-primary-transparent">${total || 0}</span>`;
        },
      },
      {
        data: 'estado_trash',
        className: 'text-center',
        render: function (estado) {
          return estado === '1'
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
      aplicarSeleccionUsuariosEnTabla();
    },
    order: [[5, 'desc']],
  });

  $('#tabla-usuarios tbody').on('click', '.btn-ver-usuario', function () {
    verUsuario($(this).data('id'));
  });

  $('#tabla-usuarios tbody').on('click', '.btn-editar-usuario', function () {
    editarUsuario($(this).data('id'));
  });

  $('#tabla-usuarios tbody').on('click', '.btn-eliminar-usuario', function () {
    eliminarUsuario($(this).data('id'));
  });

  inicializarSeleccionPorArrastreUsuarios();

  $('#tabla-usuarios tbody').on('contextmenu', 'tr', function (event) {
    event.preventDefault();

    const rowData = tablaUsuarios.row(this).data();

    if (!rowData) {
      return;
    }

    mostrarMenuContextualUsuario(event, rowData.id);
  });
}

// ============================================================================
// MENU CONTEXTUAL DE USUARIOS
// ============================================================================

function inicializarMenuContextualUsuario() {
  $(document).on('click', function (event) {
    if (!$(event.target).closest('#menu-contextual-usuario').length) {
      ocultarMenuContextualUsuario();
    }
  });

  $(document).on('keydown', function (event) {
    if (event.key === 'Escape') {
      ocultarMenuContextualUsuario();
    }
  });

  $('#opcion-p-editar').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuUsuario(editarUsuario);
  });

  $('#opcion-p-ver-detalle').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuUsuario(verUsuario);
  });

  $('#opcion-p-gestionar-2fa').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuUsuario(gestionar2FAUsuario);
  });

  $('#opcion-p-sesiones').on('click', function (event) {
    event.preventDefault();
    ejecutarAccionMenuUsuario(verSesionesUsuario);
  });

  $('#opcion-p-enviar-papelera').on('click', function (event) {
    event.preventDefault();
    if ($(this).hasClass('disabled')) {
      mostrarError('No puedes eliminar ni desactivar tu propio usuario mientras tienes la sesion iniciada.');
      return;
    }
    ejecutarAccionMenuUsuario(eliminarUsuario);
  });

  $('#opcion-p-eliminar').on('click', function (event) {
    event.preventDefault();
    if ($(this).hasClass('disabled')) {
      mostrarError('No puedes eliminar permanentemente tu propio usuario mientras tienes la sesion iniciada.');
      return;
    }
    ejecutarAccionMenuUsuario(eliminarUsuarioPermanente);
  });

}

function mostrarMenuContextualUsuario(event, usuarioId) {
  const $menu = $('#menu-contextual-usuario');

  if (!$menu.length) {
    return;
  }

  usuarioSeleccionadoId = usuarioId;
  configurarOpcionesPeligrosasUsuario(esUsuarioAutenticado(usuarioId));

  $menu.css({
    display: 'block',
    left: '0px',
    top: '0px',
  });

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

function ocultarMenuContextualUsuario() {
  $('#menu-contextual-usuario').hide();
}

function configurarOpcionesPeligrosasUsuario(disabled) {
  $('#opcion-p-enviar-papelera, #opcion-p-eliminar')
    .toggleClass('disabled text-muted', disabled)
    .attr('aria-disabled', disabled ? 'true' : 'false')
    .attr('title', disabled ? 'No puedes eliminar tu usuario de sesion' : '');
}

function ejecutarAccionMenuUsuario(callback) {
  const id = usuarioSeleccionadoId;

  ocultarMenuContextualUsuario();

  if (!id) {
    mostrarError('Seleccione un usuario.');
    return;
  }

  callback(id);
}

// ============================================================================
// ACCIONES DE TABLA
// ============================================================================

function recargarTablaUsuarios() {
  if (tablaUsuarios) {
    tablaUsuarios.ajax.reload(null, false);
  }
}

// ============================================================================
// SELECCION MULTIPLE POR ARRASTRE
// ============================================================================
// La tabla no selecciona filas con un clic simple. La seleccion se activa solo
// cuando el usuario arrastra sobre filas y no hay texto seleccionado para copiar.

function inicializarSeleccionPorArrastreUsuarios() {
  const $tbody = $('#tabla-usuarios tbody');

  $tbody.on('mousedown', 'tr', function (event) {
    if (!esInicioValidoSeleccionUsuarios(event, this)) {
      return;
    }

    seleccionDragUsuarios = {
      active: true,
      moved: false,
      startX: event.pageX,
      startY: event.pageY,
      startRow: this,
      lastRow: this,
      previousIds: new Set(usuariosSeleccionadosIds),
      previousCorreos: new Map(usuariosSeleccionadosCorreos),
    };
  });

  $tbody.on('mouseenter', 'tr', function (event) {
    if (!seleccionDragUsuarios.active || event.buttons !== 1) {
      return;
    }

    seleccionDragUsuarios.lastRow = this;
    actualizarSeleccionArrastreUsuarios(event);
  });

  $(document).on('mousemove.usuarioSeleccion', function (event) {
    if (!seleccionDragUsuarios.active || event.buttons !== 1) {
      return;
    }

    actualizarSeleccionArrastreUsuarios(event);
  });

  $(document).on('mouseup.usuarioSeleccion', function () {
    if (!seleccionDragUsuarios.active) {
      return;
    }

    finalizarSeleccionArrastreUsuarios();
  });
}

function esInicioValidoSeleccionUsuarios(event, row) {
  if (event.button !== 0 || !tablaUsuarios?.row(row).data()) {
    return false;
  }

  return !$(event.target).closest('button, a, input, select, textarea, label, .btn, .dropdown-menu').length;
}

function actualizarSeleccionArrastreUsuarios(event) {
  const distanciaX = Math.abs(event.pageX - seleccionDragUsuarios.startX);
  const distanciaY = Math.abs(event.pageY - seleccionDragUsuarios.startY);

  if (!seleccionDragUsuarios.moved && distanciaX < 6 && distanciaY < 6) {
    return;
  }

  seleccionDragUsuarios.moved = true;
  seleccionarRangoUsuarios(seleccionDragUsuarios.startRow, seleccionDragUsuarios.lastRow);
}

function finalizarSeleccionArrastreUsuarios() {
  const textoSeleccionado = String(window.getSelection?.().toString() || '').trim();
  const cancelarPorCopia = textoSeleccionado.length > 0;

  if (!seleccionDragUsuarios.moved || cancelarPorCopia) {
    restaurarSeleccionUsuarios(
      seleccionDragUsuarios.previousIds,
      seleccionDragUsuarios.previousCorreos
    );
  }

  seleccionDragUsuarios.active = false;
  seleccionDragUsuarios.moved = false;
  seleccionDragUsuarios.startRow = null;
  seleccionDragUsuarios.lastRow = null;
}

function seleccionarRangoUsuarios(startRow, endRow) {
  const rows = $('#tabla-usuarios tbody tr').toArray();
  const startIndex = rows.indexOf(startRow);
  const endIndex = rows.indexOf(endRow);

  if (startIndex < 0 || endIndex < 0) {
    return;
  }

  const from = Math.min(startIndex, endIndex);
  const to = Math.max(startIndex, endIndex);

  usuariosSeleccionadosIds.clear();
  usuariosSeleccionadosCorreos.clear();
  $('#tabla-usuarios tbody tr').removeClass('usuario-row-selected');

  rows.slice(from, to + 1).forEach(function (row) {
    const rowData = tablaUsuarios.row(row).data();

    if (rowData) {
      seleccionarUsuario(rowData, row);
    }
  });
}

function restaurarSeleccionUsuarios(ids, correos) {
  usuariosSeleccionadosIds = new Set(ids || []);
  usuariosSeleccionadosCorreos = new Map(correos || []);
  aplicarSeleccionUsuariosEnTabla();
}

function seleccionarUsuario(rowData, row) {
  const id = Number(rowData.id);

  usuariosSeleccionadosIds.add(id);
  usuariosSeleccionadosCorreos.set(id, rowData.email || `Usuario #${id}`);
  $(row).addClass('usuario-row-selected');
}

function limpiarSeleccionUsuarios() {
  usuariosSeleccionadosIds.clear();
  usuariosSeleccionadosCorreos.clear();
  $('#tabla-usuarios tbody tr').removeClass('usuario-row-selected');
}

function aplicarSeleccionUsuariosEnTabla() {
  $('#tabla-usuarios tbody tr').each(function () {
    const rowData = tablaUsuarios.row(this).data();
    const selected = rowData && usuariosSeleccionadosIds.has(Number(rowData.id));

    $(this).toggleClass('usuario-row-selected', Boolean(selected));
  });
}

function obtenerIdsParaAccionContextual() {
  if (usuariosSeleccionadosIds.size > 0) {
    return Array.from(usuariosSeleccionadosIds);
  }

  return usuarioSeleccionadoId ? [Number(usuarioSeleccionadoId)] : [];
}

function obtenerIdsEliminables(ids) {
  return (ids || []).filter(function (id) {
    return !esUsuarioAutenticado(id);
  });
}

function obtenerCorreosParaAccionContextual(ids) {
  return ids.map(function (id) {
    return usuariosSeleccionadosCorreos.get(Number(id)) || `Usuario #${id}`;
  });
}

function renderCorreosSeleccionados(correos) {
  const items = correos
    .map(function (correo) {
      return `<li class="text-start">${escapeHtml(correo)}</li>`;
    })
    .join('');

  return `
    <div class="text-start">
      <p class="mb-2">Usuarios seleccionados:</p>
      <ul class="mb-0 ps-3">${items}</ul>
    </div>
  `;
}

function obtenerNombrePersonaSeleccionada() {
  const data = $('#idpersona').select2('data');
  const persona = Array.isArray(data) ? data[0] : null;

  return persona?.descripcion || persona?.text || $('#email').val();
}

function inicializarPermisosUsuario() {
  cargarPermisosUsuario();
  inicializarSeleccionPermisosPorArrastre();
  inicializarMenuContextualPermisos();

  $('#perm-search').on('input', function () {
    filtrarPermisosUsuario($(this).val());
  });

  $('#btn-tenant-permisos-expandir').on('click', function () {
    $('.usuario-permiso-grupo-body').show();
  });

  $('#btn-tenant-permisos-contraer').on('click', function () {
    $('.usuario-permiso-grupo-body').hide();
  });

  $('#btn-tenant-permisos-todos').on('click', function () {
    $('.usuario-permiso-ver').prop('checked', true).trigger('change');
    $('.usuario-permiso-accion').prop('disabled', false).prop('checked', true);
    actualizarContadorPermisosUsuario();
  });

  $('#btn-tenant-permisos-remover').on('click', function () {
    limpiarPermisosUsuario();
  });

  $('#btn-tenant-permisos-limpiar').on('click', function () {
    limpiarPermisosUsuario();
  });

  $('#contenedor-tenant-permisos').on('change', '.usuario-permiso-ver', function () {
    const $row = $(this).closest('.usuario-permiso-row');
    const activo = $(this).is(':checked');

    $row.find('.usuario-permiso-accion').prop('disabled', !activo);

    if (!activo) {
      $row.find('.usuario-permiso-accion').prop('checked', false);
    }

    actualizarContadorPermisosUsuario($row.closest('.usuario-permiso-grupo'));
  });

  $('#contenedor-tenant-permisos').on('change', '.usuario-permiso-accion', function () {
    const $row = $(this).closest('.usuario-permiso-row');

    if ($(this).is(':checked')) {
      $row.find('.usuario-permiso-ver').prop('checked', true);
      $row.find('.usuario-permiso-accion').prop('disabled', false);
    }

    actualizarContadorPermisosUsuario($row.closest('.usuario-permiso-grupo'));
  });

  $('#contenedor-tenant-permisos').on('click', '.usuario-permiso-toggle', function () {
    $(this).closest('.usuario-permiso-grupo').find('.usuario-permiso-grupo-body').toggle();
  });

  $('#contenedor-tenant-permisos').on('contextmenu', '.usuario-permiso-columna', function (event) {
    event.preventDefault();
    alternarColumnaPermisos($(this).data('permiso-columna'), $(this).closest('table'));
  });

  $('#contenedor-tenant-permisos').on('contextmenu', '.usuario-permiso-info', function (event) {
    event.preventDefault();
    alternarFilaPermiso($(this).closest('.usuario-permiso-row'));
  });
}

// ============================================================================
// SELECCION MULTIPLE DE CHECKBOXES DE PERMISOS
// ============================================================================
// Permite resaltar checkboxes como un pincel: solo se seleccionan las celdas por
// donde el cursor pasa directamente. El estado checked se aplica luego desde el
// menu contextual.

function inicializarSeleccionPermisosPorArrastre() {
  const $contenedor = $('#contenedor-tenant-permisos');

  $contenedor.on('mousedown', '.usuario-permiso-check-cell', function (event) {
    if (!esInicioValidoSeleccionPermisos(event, this)) {
      return;
    }

    ocultarMenuContextualPermisos();

    seleccionDragPermisos = {
      active: true,
      moved: false,
      startX: event.pageX,
      startY: event.pageY,
    };

    seleccionarCeldaPermiso(this);
  });

  $contenedor.on('mouseenter', '.usuario-permiso-check-cell', function (event) {
    if (!seleccionDragPermisos.active || event.buttons !== 1) {
      return;
    }

    actualizarSeleccionArrastrePermisos(event, this);
  });

  $(document).on('mousemove.usuarioPermisosSeleccion', function (event) {
    if (!seleccionDragPermisos.active || event.buttons !== 1) {
      return;
    }

    actualizarSeleccionArrastrePermisos(event, obtenerCeldaPermisoDesdePunto(event));
  });

  $(document).on('mouseup.usuarioPermisosSeleccion', function () {
    finalizarSeleccionArrastrePermisos();
  });

  $contenedor.on('contextmenu', '.usuario-permiso-check-cell', function (event) {
    event.preventDefault();

    if (!$(this).hasClass('usuario-permiso-check-selected')) {
      limpiarSeleccionPermisos();
      seleccionarCeldaPermiso(this);
    }

    mostrarMenuContextualPermisos(event);
  });
}

function esInicioValidoSeleccionPermisos(event, cell) {
  return event.button === 0 && $(cell).find('.form-check-input').length > 0;
}

function actualizarSeleccionArrastrePermisos(event, cell) {
  const distanciaX = Math.abs(event.pageX - seleccionDragPermisos.startX);
  const distanciaY = Math.abs(event.pageY - seleccionDragPermisos.startY);

  if (!seleccionDragPermisos.moved && distanciaX < 4 && distanciaY < 4) {
    return;
  }

  seleccionDragPermisos.moved = true;
  seleccionarCeldaPermiso(cell);
}

function finalizarSeleccionArrastrePermisos() {
  if (!seleccionDragPermisos.active) {
    return;
  }

  seleccionDragPermisos.active = false;
  seleccionDragPermisos.moved = false;
}

function seleccionarCeldaPermiso(cell) {
  if (!cell || !$(cell).hasClass('usuario-permiso-check-cell')) {
    return;
  }

  $(cell).addClass('usuario-permiso-check-selected');
}

function obtenerCeldaPermisoDesdePunto(event) {
  const element = document.elementFromPoint(event.clientX, event.clientY);

  return $(element).closest('.usuario-permiso-check-cell').get(0);
}

function limpiarSeleccionPermisos() {
  $('.usuario-permiso-check-selected').removeClass('usuario-permiso-check-selected');
}

function obtenerChecksPermisosSeleccionados() {
  return $('.usuario-permiso-check-selected .form-check-input');
}

// ============================================================================
// MENU CONTEXTUAL DE PERMISOS
// ============================================================================
// Muestra acciones Bootstrap para marcar o desmarcar el lote de checkboxes que
// el usuario resalto con clic y arrastre.

function inicializarMenuContextualPermisos() {
  $(document).on('click.usuarioMenuPermisos', function (event) {
    if (!$(event.target).closest('#menu-contextual-permisos, .usuario-permiso-check-cell').length) {
      ocultarMenuContextualPermisos();
    }
  });

  $(document).on('keydown.usuarioMenuPermisos', function (event) {
    if (event.key === 'Escape') {
      ocultarMenuContextualPermisos();
      limpiarSeleccionPermisos();
    }
  });

  $('#opcion-permisos-activar').on('click', function () {
    aplicarEstadoPermisosSeleccionados(true);
  });

  $('#opcion-permisos-desactivar').on('click', function () {
    aplicarEstadoPermisosSeleccionados(false);
  });
}

function mostrarMenuContextualPermisos(event) {
  const $menu = $('#menu-contextual-permisos');

  if (!$menu.length || obtenerChecksPermisosSeleccionados().length === 0) {
    return;
  }

  $menu.css({
    display: 'block',
    left: '0px',
    top: '0px',
  });

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

function ocultarMenuContextualPermisos() {
  $('#menu-contextual-permisos').hide();
}

function aplicarEstadoPermisosSeleccionados(activar) {
  const $checks = obtenerChecksPermisosSeleccionados();

  if (!$checks.length) {
    ocultarMenuContextualPermisos();
    return;
  }

  const $grupos = obtenerGruposUnicosDesdeChecks($checks);

  $checks.each(function () {
    aplicarEstadoPermisoCheckbox($(this), activar);
  });

  ocultarMenuContextualPermisos();
  limpiarSeleccionPermisos();
  actualizarContadorPermisosUsuario($grupos);
}

function aplicarEstadoPermisoCheckbox($checkbox, activar) {
  const $row = $checkbox.closest('.usuario-permiso-row');

  if ($checkbox.hasClass('usuario-permiso-ver')) {
    $checkbox.prop('checked', activar);
    $row.find('.usuario-permiso-accion').prop('disabled', !activar);

    if (!activar) {
      $row.find('.usuario-permiso-accion').prop('checked', false);
    }

    return;
  }

  if (activar) {
    $row.find('.usuario-permiso-ver').prop('checked', true);
    $row.find('.usuario-permiso-accion').prop('disabled', false);
  }

  $checkbox.prop('checked', activar);
}

function obtenerGruposUnicosDesdeChecks($checks) {
  const grupos = [];

  $checks.each(function () {
    const grupo = $(this).closest('.usuario-permiso-grupo').get(0);

    if (grupo && !grupos.includes(grupo)) {
      grupos.push(grupo);
    }
  });

  return $(grupos);
}

function cargarPermisosUsuario() {
  $.ajax({
    url: apiUrl('/permisos'),
    type: 'GET',
    headers: { 'X-CSRF-TOKEN': csrf() },
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo cargar permisos.');
        return;
      }

      permisosCatalogo = response.data || [];
      renderPermisosUsuario(permisosCatalogo);
    },
    error: function () {
      mostrarError('Error al cargar catalogo de permisos.');
    },
  });
}

function renderPermisosUsuario(permisos) {
  const $contenedor = $('#contenedor-tenant-permisos');
  const padres = permisos.filter((permiso) => String(permiso.nivel) === '1');
  const hijos = permisos.filter((permiso) => String(permiso.nivel) !== '1');

  if (!permisos.length) {
    $contenedor.html(`
      <div class="border rounded-4 p-4 text-center text-muted bg-light">
        <div class="fw-semibold mb-1">No hay permisos registrados.</div>
      </div>
    `);
    return;
  }

  const grupos = padres.map(function (padre) {
    const prefijo = String(padre.codigo || '');
    const permisosHijos = hijos
      .filter((permiso) => String(permiso.codigo || '').startsWith(prefijo))
      .sort(ordenarPermisosPorCodigo);
    const permisosGrupo = esPermisoPagina(padre) ? [padre, ...permisosHijos] : permisosHijos;
    const filas = permisosGrupo.map(renderPermisoRow).join('');

    return `
      <div class="usuario-permiso-grupo border rounded-3 overflow-hidden" data-search="${escapeHtml(textoBusquedaPermiso([padre, ...permisosHijos]))}">
        <button type="button" class="usuario-permiso-toggle btn btn-light w-100 rounded-0 d-flex justify-content-between align-items-center gap-2">
          <span class="d-flex align-items-center flex-wrap gap-2 text-start">
            <span class="fw-semibold">${escapeHtml(padre.codigo || '')} - ${escapeHtml(padre.descripcion || '')}</span>
            <span class="usuario-permiso-grupo-contadores">
              <span class="badge bg-secondary-transparent text-secondary" data-contador-permiso="ver" data-contador-activo-class="bg-primary-transparent text-primary">Ver: 0</span>
              <span class="badge bg-secondary-transparent text-secondary" data-contador-permiso="crear" data-contador-activo-class="bg-success-transparent text-success">Crear: 0</span>
              <span class="badge bg-secondary-transparent text-secondary" data-contador-permiso="editar" data-contador-activo-class="bg-warning-transparent text-warning">Editar: 0</span>
              <span class="badge bg-secondary-transparent text-secondary" data-contador-permiso="eliminar" data-contador-activo-class="bg-danger-transparent text-danger">Eliminar: 0</span>
            </span>
          </span>
          <i class="bi bi-chevron-down"></i>
        </button>
        <div class="usuario-permiso-grupo-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="usuario-permiso-modulo-header">Modulo</th>
                  <th class="text-center usuario-permiso-columna cursor-pointer" data-permiso-columna="ver">Ver</th>
                  <th class="text-center usuario-permiso-columna cursor-pointer" data-permiso-columna="crear">Crear</th>
                  <th class="text-center usuario-permiso-columna cursor-pointer" data-permiso-columna="editar">Editar</th>
                  <th class="text-center usuario-permiso-columna cursor-pointer" data-permiso-columna="eliminar">Eliminar</th>
                </tr>
              </thead>
              <tbody>${filas}</tbody>
            </table>
          </div>
        </div>
      </div>
    `;
  });

  $contenedor.html(grupos.join(''));
  limpiarSeleccionPermisos();
  $('.usuario-permiso-accion').prop('disabled', true);
  actualizarContadorPermisosUsuario();
}

function renderPermisoRow(permiso) {
  const id = permiso.idpermiso;
  const tipo = String(permiso.tipo || '').toLowerCase();
  const esPagina = esPermisoPagina(permiso);
  const nivel = Number(permiso.nivel || 1);
  const indent = Math.max(nivel - 1, 0);
  const rowClass = esPagina ? 'usuario-permiso-row-pagina' : 'usuario-permiso-row-estructural table-active';

  return `
    <tr class="usuario-permiso-row ${rowClass}" data-idpermiso="${id}" data-tipo="${escapeHtml(tipo)}" data-search="${escapeHtml(textoBusquedaPermiso([permiso]))}">
      <td class="usuario-permiso-info ${esPagina ? 'cursor-pointer' : ''}">
        <div class="usuario-permiso-info-inner" style="--usuario-permiso-indent: ${indent};">
          <div class="d-flex align-items-center gap-2">
            <i class="bi ${esPagina ? 'bi-file-earmark-check' : 'bi-folder2-open'} ${esPagina ? 'text-primary' : 'text-secondary'}"></i>
            <div class="fw-semibold usuario-permiso-nombre ${esPagina ? 'cursor-pointer' : 'text-uppercase text-secondary'}">${escapeHtml(permiso.descripcion || '')}</div>
            <span class="badge ${esPagina ? 'bg-primary-transparent text-primary' : 'bg-secondary-transparent text-secondary'}">${esPagina ? 'Pagina' : 'Contenedor'}</span>
          </div>
          <div class="text-muted fs-12">${escapeHtml(permiso.codigo || '')} | ${escapeHtml(permiso.nombre_clave || '')}</div>
        </div>
      </td>
      ${esPagina ? renderPermisoCheckboxCells() : renderPermisoEstructuralCells()}
    </tr>
  `;
}

function esPermisoPagina(permiso) {
  return String(permiso?.tipo || '').toLowerCase() === 'pagina';
}

function renderPermisoCheckboxCells() {
  return `
    <td class="text-center usuario-permiso-check-cell">
      <input class="form-check-input usuario-permiso-ver" type="checkbox" value="1">
    </td>
    <td class="text-center usuario-permiso-check-cell">
      <input class="form-check-input usuario-permiso-accion usuario-permiso-crear" type="checkbox" value="1">
    </td>
    <td class="text-center usuario-permiso-check-cell">
      <input class="form-check-input usuario-permiso-accion usuario-permiso-editar" type="checkbox" value="1">
    </td>
    <td class="text-center usuario-permiso-check-cell">
      <input class="form-check-input usuario-permiso-accion usuario-permiso-eliminar" type="checkbox" value="1">
    </td>
  `;
}

function renderPermisoEstructuralCells() {
  return `
    <td class="text-center usuario-permiso-cell-disabled">-</td>
    <td class="text-center usuario-permiso-cell-disabled">-</td>
    <td class="text-center usuario-permiso-cell-disabled">-</td>
    <td class="text-center usuario-permiso-cell-disabled">-</td>
  `;
}

function ordenarPermisosPorCodigo(a, b) {
  return String(a.codigo || '').localeCompare(String(b.codigo || ''), undefined, { numeric: true });
}

function textoBusquedaPermiso(permisos) {
  return permisos
    .map((permiso) => [permiso.codigo, permiso.descripcion, permiso.nombre_clave, permiso.tipo].join(' '))
    .join(' ')
    .toLowerCase();
}

function filtrarPermisosUsuario(valor) {
  const texto = String(valor || '').toLowerCase().trim();

  ocultarMenuContextualPermisos();
  limpiarSeleccionPermisos();

  $('.usuario-permiso-grupo').each(function () {
    const coincide = !texto || String($(this).data('search') || '').includes(texto);
    $(this).toggle(coincide);

    if (coincide && texto) {
      $(this).find('.usuario-permiso-grupo-body').show();
    }
  });
}

function limpiarPermisosUsuario() {
  ocultarMenuContextualPermisos();
  limpiarSeleccionPermisos();
  $('.usuario-permiso-ver, .usuario-permiso-accion').prop('checked', false);
  $('.usuario-permiso-accion').prop('disabled', true);
  actualizarContadorPermisosUsuario();
}

function alternarColumnaPermisos(columna, $table) {
  const selector = {
    ver: '.usuario-permiso-ver',
    crear: '.usuario-permiso-crear',
    editar: '.usuario-permiso-editar',
    eliminar: '.usuario-permiso-eliminar',
  }[columna];

  if (!selector || !$table?.length) {
    return;
  }

  const $checks = $table.find(selector);
  const activar = $checks.filter(':checked').length !== $checks.length;

  if (columna === 'ver') {
    $checks.each(function () {
      const $row = $(this).closest('.usuario-permiso-row');

      $(this).prop('checked', activar);
      $row.find('.usuario-permiso-accion').prop('disabled', !activar);

      if (!activar) {
        $row.find('.usuario-permiso-accion').prop('checked', false);
      }
    });

    actualizarContadorPermisosUsuario($table.closest('.usuario-permiso-grupo'));
    return;
  }

  $checks.each(function () {
    const $row = $(this).closest('.usuario-permiso-row');

    if (activar) {
      $row.find('.usuario-permiso-ver').prop('checked', true);
      $row.find('.usuario-permiso-accion').prop('disabled', false);
    }

    $(this).prop('checked', activar);
  });

  actualizarContadorPermisosUsuario($table.closest('.usuario-permiso-grupo'));
}

function alternarFilaPermiso($row) {
  if (!esFilaPermisoSeleccionable($row)) {
    return;
  }

  const activar = !$row.find('.usuario-permiso-ver').is(':checked')
    || $row.find('.usuario-permiso-accion:checked').length !== $row.find('.usuario-permiso-accion').length;

  $row.find('.usuario-permiso-ver').prop('checked', activar);
  $row.find('.usuario-permiso-accion').prop('disabled', !activar).prop('checked', activar);
  actualizarContadorPermisosUsuario($row.closest('.usuario-permiso-grupo'));
}

// ============================================================================
// CONTADORES DE PERMISOS
// ============================================================================
// Mantiene sincronizado el total general y el badge de cada bloque, incluso si
// el acordeon esta contraido. El conteo considera todos los checkboxes marcados.

function actualizarContadorPermisosUsuario($grupos) {
  const total = $('.usuario-permiso-ver:checked').length;
  $('#tenant-perm-counter-label').text(`${total} modulo(s) activos`);
  $('#permisos_json').val(JSON.stringify(obtenerPermisosSeleccionados()));
  actualizarContadoresBloquesPermisos($grupos);
}

function actualizarContadoresBloquesPermisos($grupos) {
  const $targets = $grupos?.length ? $grupos : $('.usuario-permiso-grupo');

  $targets.each(function () {
    actualizarContadorBloquePermisos($(this));
  });
}

function actualizarContadorBloquePermisos($grupo) {
  const totales = obtenerTotalesPermisosPorTipo($grupo);

  Object.keys(totales).forEach(function (tipo) {
    const etiqueta = tipo.charAt(0).toUpperCase() + tipo.slice(1);
    actualizarBadgeContadorPermiso($grupo.find(`[data-contador-permiso="${tipo}"]`), etiqueta, totales[tipo]);
  });
}

function actualizarBadgeContadorPermiso($badge, etiqueta, total) {
  const activeClass = String($badge.data('contador-activo-class') || '');
  const inactiveClass = 'bg-secondary-transparent text-secondary';

  $badge
    .removeClass(`${activeClass} ${inactiveClass}`)
    .addClass(total > 0 ? activeClass : inactiveClass)
    .text(`${etiqueta}: ${total}`);
}

function obtenerTotalesPermisosPorTipo($grupo) {
  return {
    ver: $grupo.find('.usuario-permiso-ver:checked').length,
    crear: $grupo.find('.usuario-permiso-crear:checked').length,
    editar: $grupo.find('.usuario-permiso-editar:checked').length,
    eliminar: $grupo.find('.usuario-permiso-eliminar:checked').length,
  };
}

function obtenerPermisosSeleccionados() {
  const permisos = [];

  $('.usuario-permiso-row').each(function () {
    const $row = $(this);

    if (!esFilaPermisoSeleccionable($row) || !$row.find('.usuario-permiso-ver').is(':checked')) {
      return;
    }

    permisos.push({
      idpermiso: Number($row.data('idpermiso')),
      crear: $row.find('.usuario-permiso-crear').is(':checked') ? '1' : null,
      editar: $row.find('.usuario-permiso-editar').is(':checked') ? '1' : null,
      eliminar: $row.find('.usuario-permiso-eliminar').is(':checked') ? '1' : null,
    });
  });

  return permisos;
}

function esFilaPermisoSeleccionable($row) {
  return String($row.data('tipo') || '').toLowerCase() === 'pagina'
    && $row.find('.usuario-permiso-ver').length > 0;
}

// ============================================================================
// SERIES POR USUARIO
// ============================================================================

function inicializarSeriesUsuario() {
  cargarSeriesUsuario();

  $('#series-search').on('input', function () {
    filtrarSeriesUsuario($(this).val());
  });

  $('#btn-tenant-series-expandir').on('click', function () {
    $('.usuario-serie-grupo-body').show();
  });

  $('#btn-tenant-series-contraer').on('click', function () {
    $('.usuario-serie-grupo-body').hide();
  });

  $('#btn-tenant-series-todos').on('click', function () {
    $('.usuario-serie-check').prop('checked', true);
    actualizarContadorSeriesUsuario();
  });

  $('#btn-tenant-series-remover').on('click', function () {
    limpiarSeriesUsuario();
  });

  $('#contenedor-tenant-series').on('change', '.usuario-serie-check', function () {
    actualizarContadorSeriesUsuario($(this).closest('.usuario-serie-grupo'));
  });

  $('#contenedor-tenant-series').on('click', '.usuario-serie-toggle', function () {
    $(this).closest('.usuario-serie-grupo').find('.usuario-serie-grupo-body').toggle();
  });
}

function cargarSeriesUsuario() {
  $.ajax({
    url: apiUrl('/usuarios/series-disponibles'),
    type: 'GET',
    headers: { 'X-CSRF-TOKEN': csrf() },
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo cargar las series.');
        return;
      }

      seriesCatalogo = response.data || [];
      renderSeriesUsuario(seriesCatalogo);
    },
    error: function () {
      mostrarError('Error al cargar catalogo de series.');
    },
  });
}

function renderSeriesUsuario(series) {
  const $contenedor = $('#contenedor-tenant-series');

  if (!series.length) {
    $contenedor.html(`
      <div class="border rounded-4 p-4 text-center text-muted bg-light">
        <div class="fw-semibold mb-1">No hay series registradas.</div>
      </div>
    `);
    return;
  }

  const gruposMap = new Map();

  series.forEach(function (serieItem) {
    const tipo = serieItem.tipo_comprobante || {};
    const idTipo = Number(serieItem.idsunat_c01_tipo_comprobante || 0);
    const codigoTipo = String(tipo.codigo || '').trim();
    const nombreTipo = String(tipo.nombre || '').trim() || 'Tipo sin nombre';
    const claveGrupo = idTipo || codigoTipo || 'sin-tipo';

    if (!gruposMap.has(claveGrupo)) {
      gruposMap.set(claveGrupo, {
        codigo: codigoTipo,
        nombre: nombreTipo,
        series: [],
      });
    }

    gruposMap.get(claveGrupo).series.push(serieItem);
  });

  const gruposHtml = Array.from(gruposMap.values())
    .sort(function (a, b) {
      return String(a.codigo || a.nombre).localeCompare(String(b.codigo || b.nombre), undefined, { numeric: true });
    })
    .map(function (grupo) {
      const seriesHtml = grupo.series
        .sort(function (a, b) {
          const serieA = String(a.serie || '');
          const serieB = String(b.serie || '');

          if (serieA !== serieB) {
            return serieA.localeCompare(serieB, undefined, { numeric: true });
          }

          return Number(a.numero || 0) - Number(b.numero || 0);
        })
        .map(renderSerieRow)
        .join('');

      const etiquetaGrupo = [grupo.codigo, grupo.nombre].filter(Boolean).join(' - ');
      const textosGrupo = grupo.series
        .map(function (item) {
          const numero = item.numero !== null && item.numero !== undefined ? item.numero : '-';
          return `${item.serie || ''} ${numero} ${grupo.codigo || ''} ${grupo.nombre || ''}`;
        })
        .join(' ')
        .toLowerCase();

      return `
        <div class="usuario-serie-grupo border rounded-3 overflow-hidden" data-search="${escapeHtml(textosGrupo)}">
          <button type="button" class="usuario-serie-toggle btn btn-light w-100 rounded-0 d-flex justify-content-between align-items-center gap-2">
            <span class="d-flex align-items-center flex-wrap gap-2 text-start">
              <span class="fw-semibold">${escapeHtml(etiquetaGrupo || 'Tipo sin definir')}</span>
              <span class="badge bg-light text-default">${grupo.series.length} serie(s)</span>
              <span class="badge bg-secondary-transparent text-secondary" data-contador-series-grupo>Activas: 0</span>
            </span>
            <i class="bi bi-chevron-down"></i>
          </button>
          <div class="usuario-serie-grupo-body">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Serie</th>
                    <th>Correlativo</th>
                    <th class="text-center">Predet.</th>
                    <th class="text-center">Asignar</th>
                  </tr>
                </thead>
                <tbody>${seriesHtml}</tbody>
              </table>
            </div>
          </div>
        </div>
      `;
    });

  $contenedor.html(gruposHtml.join(''));
  actualizarContadorSeriesUsuario();
}

function renderSerieRow(serieItem) {
  const idSerie = Number(serieItem.idserie_comprobante || 0);
  const serie = String(serieItem.serie || '').trim() || '-';
  const numero = serieItem.numero !== null && serieItem.numero !== undefined ? serieItem.numero : '-';
  const predeterminado = String(serieItem.predeterminado || '0') === '1';
  const tipo = serieItem.tipo_comprobante || {};
  const textoBusqueda = [serie, numero, tipo.codigo || '', tipo.nombre || '', tipo.abreviatura || '']
    .join(' ')
    .toLowerCase();

  return `
    <tr class="usuario-serie-row" data-idserie-comprobante="${idSerie}" data-search="${escapeHtml(textoBusqueda)}">
      <td class="fw-semibold">${escapeHtml(serie)}</td>
      <td>${escapeHtml(String(numero))}</td>
      <td class="text-center">${predeterminado ? '<span class="badge bg-success-transparent">Si</span>' : '<span class="badge bg-light text-muted">No</span>'}</td>
      <td class="text-center">
        <input class="form-check-input usuario-serie-check" type="checkbox" value="1">
      </td>
    </tr>
  `;
}

function filtrarSeriesUsuario(valor) {
  const texto = String(valor || '').toLowerCase().trim();

  $('.usuario-serie-grupo').each(function () {
    const $grupo = $(this);
    const coincideGrupo = !texto || String($grupo.data('search') || '').includes(texto);

    if (coincideGrupo) {
      $grupo.find('.usuario-serie-row').show();
      $grupo.show();
      if (texto) {
        $grupo.find('.usuario-serie-grupo-body').show();
      }

      return;
    }

    let filasVisibles = 0;
    $grupo.find('.usuario-serie-row').each(function () {
      const coincideFila = String($(this).data('search') || '').includes(texto);
      $(this).toggle(coincideFila);
      filasVisibles += coincideFila ? 1 : 0;
    });

    $grupo.toggle(filasVisibles > 0);
    if (filasVisibles > 0) {
      $grupo.find('.usuario-serie-grupo-body').show();
    }
  });
}

function limpiarSeriesUsuario() {
  $('.usuario-serie-check').prop('checked', false);
  actualizarContadorSeriesUsuario();
}

function actualizarContadorSeriesUsuario($grupo) {
  const total = $('.usuario-serie-check:checked').length;
  $('#tenant-series-counter-label').text(`${total} series activas`);
  $('#series_json').val(JSON.stringify(obtenerSeriesSeleccionadas()));

  const $targets = $grupo?.length ? $grupo : $('.usuario-serie-grupo');
  $targets.each(function () {
    const activas = $(this).find('.usuario-serie-check:checked').length;
    $(this).find('[data-contador-series-grupo]').text(`Activas: ${activas}`);
  });
}

function obtenerSeriesSeleccionadas() {
  const series = [];

  $('.usuario-serie-row').each(function () {
    const $row = $(this);

    if (!$row.find('.usuario-serie-check').is(':checked')) {
      return;
    }

    series.push({
      idserie_comprobante: Number($row.data('idserie-comprobante')),
    });
  });

  return series;
}

function aplicarSeriesUsuario(seriesAsignadas) {
  if (!$('#contenedor-tenant-series .usuario-serie-row').length) {
    setTimeout(function () {
      aplicarSeriesUsuario(seriesAsignadas);
    }, 150);
    return;
  }

  const idsAsignados = new Set(
    (seriesAsignadas || [])
      .map(function (item) {
        return Number(item.idserie_comprobante || item.serie_comprobante?.idserie_comprobante || 0);
      })
      .filter(function (id) {
        return id > 0;
      }),
  );

  $('.usuario-serie-row').each(function () {
    const $row = $(this);
    const idSerie = Number($row.data('idserie-comprobante'));
    $row.find('.usuario-serie-check').prop('checked', idsAsignados.has(idSerie));
  });

  actualizarContadorSeriesUsuario();
}

// ============================================================================
// ACCIONES DE USUARIO
// ============================================================================

function inicializarFormularioUsuario() {
  inicializarSelectPersonaUsuario();
  inicializarValidacionUsuario();
  inicializarPermisosUsuario();
  inicializarSeriesUsuario();
}

function inicializarSelectPersonaUsuario() {
  const $persona = $('#idpersona');

  if (!$persona.length || typeof $persona.select2 !== 'function') {
    return;
  }

  $persona.select2({
    theme: 'bootstrap4',
    width: '100%',
    placeholder: $persona.data('placeholder') || 'Buscar persona...',
    allowClear: true,
    minimumInputLength: 0,
    ajax: {
      url: apiUrl('/usuarios/personas-disponibles'),
      dataType: 'json',
      delay: 300,
      headers: { 'X-CSRF-TOKEN': csrf() },
      data: function (params) {
        return {
          term: params.term || '',
          page: params.page || 1,
        };
      },
      processResults: function (response) {
        return {
          results: response.results || [],
          pagination: {
            more: Boolean(response.pagination && response.pagination.more),
          },
        };
      },
      error: function () {
        mostrarError('No se pudo buscar personas disponibles.');
      },
    },
  });

  $persona.on('select2:select', function (event) {
    $(this).trigger('blur');
  });

  $persona.on('select2:clear change', function () {
    $(this).trigger('blur');
  });
}

function inicializarValidacionUsuario() {
  const $form = $('#form-agregar-usuario');

  if (!$form.length || typeof $form.validate !== 'function') {
    return;
  }

  $form.validate({
    ignore: [],
    rules: {
      idpersona: { required: true },
      email: {
        required: true,
        email: true,
        maxlength: 255,
        remote: {
          url: apiUrl('/usuarios/validar-email'),
          type: 'GET',
          headers: { 'X-CSRF-TOKEN': csrf() },
          data: {
            email: function () {
              return $('#email').val();
            },
            id: function () {
              return $('#id').val();
            },
          },
          dataFilter: function (response) {
            const data = JSON.parse(response || '{}');
            return data.available ? 'true' : JSON.stringify(data.message || 'Este correo ya esta registrado.');
          },
        },
      },
      password: {
        required: function () {
          return !$('#id').val();
        },
        minlength: 8,
      },
      password_confirmation: {
        required: function () {
          return Boolean($('#password').val());
        },
        equalTo: '#password',
      },
    },
    messages: {
      idpersona: { required: 'Seleccione una persona.' },
      email: {
        required: 'Campo requerido.',
        email: 'Ingrese un correo valido.',
        maxlength: 'MAXIMO {0} caracteres.',
        remote: 'Este correo ya esta registrado.',
      },
      password: {
        required: 'Campo requerido.',
        minlength: 'MINIMO {0} caracteres.',
      },
      password_confirmation: {
        required: 'Campo requerido.',
        equalTo: 'La clave no coincide.',
      },
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');

      if (element.hasClass('select2-hidden-accessible')) {
        element.closest('.form-group').append(error);
        return;
      }

      element.closest('.form-group').append(error);
    },
    highlight: function (element) {
      const $element = $(element);

      $element.addClass('is-invalid').removeClass('is-valid');

      if ($element.hasClass('select2-hidden-accessible')) {
        $element.next('.select2-container').find('.select2-selection').addClass('is-invalid').removeClass('is-valid');
      }
    },
    unhighlight: function (element) {
      const $element = $(element);

      $element.removeClass('is-invalid').addClass('is-valid');

      if ($element.hasClass('select2-hidden-accessible')) {
        $element.next('.select2-container').find('.select2-selection').removeClass('is-invalid').addClass('is-valid');
      }
    },
    submitHandler: function () {
      guardarUsuario();
    },
  });

  $('#email').on('keyup', function () {
    clearTimeout(emailValidationTimer);

    emailValidationTimer = setTimeout(function () {
      if ($('#email').val()) {
        $('#email').valid();
      }
    }, 500);
  });
}

function prepararNuevoUsuario() {
  if (!puedeUsuarioSistema('crear')) {
    mostrarError('No tienes permiso para crear usuarios.');
    return;
  }

  const $form = $('#form-agregar-usuario');

  $form[0]?.reset();
  $('#id').val('');
  $('#titulo-formulario-usuario').text('Registrar usuario');
  $('#btn-guardar-usuario, #btn-guardar-usuario-header').html('<i class="bi bi-floppy-fill me-1"></i> Grabar datos');
  $('#idpersona').val(null).trigger('change');
  $('#idpersona').data('placeholder', 'Buscar persona sin usuario...');
  $('#idpersona').next('.select2-container').find('.select2-selection__placeholder').text('Buscar persona sin usuario...');
  limpiarPermisosUsuario();
  limpiarSeriesUsuario();
  limpiarValidacionUsuario();
  show_hide_form(2);
}

function limpiarValidacionUsuario() {
  const $form = $('#form-agregar-usuario');
  const validator = $form.data('validator');

  if (validator) {
    validator.resetForm();
  }

  $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
  $form.find('.select2-selection').removeClass('is-valid is-invalid');
}

function guardarUsuario() {
  const $form = $('#form-agregar-usuario');
  const $button = $('#btn-guardar-usuario, #btn-guardar-usuario-header');
  const id = $('#id').val();
  const editando = Boolean(id);
  const accion = editando ? 'editar' : 'crear';
  const permisosJson = JSON.stringify(obtenerPermisosSeleccionados());
  const seriesJson = JSON.stringify(obtenerSeriesSeleccionadas());

  if (!puedeUsuarioSistema(accion)) {
    mostrarError(`No tienes permiso para ${accion} usuarios.`);
    return;
  }

  $('#permisos_json').val(permisosJson);
  $('#series_json').val(seriesJson);

  const data = {
    idpersona: $('#idpersona').val(),
    name: obtenerNombrePersonaSeleccionada(),
    email: $('#email').val(),
    password: $('#password').val(),
    password_confirmation: $('#password_confirmation').val(),
    estado_trash: '1',
    user_update_sistema: '1',
    permisos_json: permisosJson,
    series_json: seriesJson,
  };

  if (!data.password) {
    delete data.password;
    delete data.password_confirmation;
  }

  $button.prop('disabled', true);

  $.ajax({
    url: editando ? apiUrl(`/usuarios/${id}`) : apiUrl('/usuarios'),
    type: editando ? 'PUT' : 'POST',
    headers: { 'X-CSRF-TOKEN': csrf() },
    data,
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo guardar el usuario.');
        return;
      }

      mostrarOk(response.message || (editando ? 'Usuario actualizado correctamente.' : 'Usuario creado correctamente.'));
      show_hide_form(1);
      recargarTablaUsuarios();
      $form[0]?.reset();
      $('#id').val('');
      $('#idpersona').val(null).trigger('change');
      limpiarValidacionUsuario();
    },
    error: function (xhr) {
      const response = xhr.responseJSON || {};
      mostrarError(response.message || 'Error al guardar usuario.');
    },
    complete: function () {
      $button.prop('disabled', false);
    },
  });
}

function verUsuario(id) {
  if (!id) {
    mostrarError('Seleccione un usuario.');
    return;
  }

  abrirModalDetalleUsuario(id);

  $.ajax({
    url: apiUrl(`/usuarios/${id}`),
    type: 'GET',
    headers: { 'X-CSRF-TOKEN': csrf() },
    success: function (response) {
      if (!response.status || !response.data) {
        mostrarError(response.message || 'No se pudo cargar el detalle del usuario.');
        cerrarModalDetalleUsuario();
        return;
      }

      renderDetalleUsuario(response.data);
    },
    error: function (xhr) {
      cerrarModalDetalleUsuario();
      mostrarError(obtenerMensajeAjaxError(xhr, 'Error al cargar detalle del usuario.'));
    },
  });
}

function abrirModalDetalleUsuario(id) {
  $('#btn-detalle-editar-usuario')
    .data('id', id)
    .prop('disabled', false);

  $('#modal-detalle-usuario-title').text('Detalle del usuario');
  $('#detalle-usuario-body').html(`
    <div class="text-center text-muted py-5">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <div class="fw-semibold">Cargando detalle del usuario...</div>
    </div>
  `);

  const modalElement = document.getElementById('modal-detalle-usuario');

  if (modalElement && typeof bootstrap !== 'undefined') {
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
  }
}

function cerrarModalDetalleUsuario() {
  const modalElement = document.getElementById('modal-detalle-usuario');

  if (modalElement && typeof bootstrap !== 'undefined') {
    bootstrap.Modal.getOrCreateInstance(modalElement).hide();
  }
}

$(document).on('click', '#btn-detalle-editar-usuario', function () {
  const id = $(this).data('id');

  cerrarModalDetalleUsuario();
  editarUsuario(id);
});

function renderDetalleUsuario(usuario) {
  const persona = usuario.persona || {};
  const permisos = (usuario.permisos_usuario || [])
    .filter((permisoUsuario) => String(permisoUsuario.estado_trash || '1') === '1');
  const series = (usuario.series_usuario || []).filter(function (serieUsuario) {
    return Boolean(serieUsuario.serie_comprobante);
  });
  const nombre = persona.descripcion || usuario.name || 'Usuario sin nombre';
  const fotoPerfil = obtenerFotoPerfilPersona(persona);
  const estadoActivo = String(usuario.estado_trash || '1') === '1';
  const esSesion = esUsuarioAutenticado(usuario.id);
  const accionesTotal = contarAccionesPermiso(permisos);

  $('#modal-detalle-usuario-title').text('Detalle del usuario');

  $('#detalle-usuario-body').html(`
    <div class="card custom-card mb-0 shadow-none border-0">
      <div class="card-body p-0">
        <div class="d-sm-flex align-items-top p-4 border-bottom border-block-end-dashed main-profile-cover">
          <div>
            <span class="avatar avatar-xxl avatar-rounded online me-3">
              <img src="${fotoPerfil}" alt="${escapeHtml(nombre)}" onerror="this.src='${apiUrl('/assets/modulo/persona/perfil/hombre.png')}';">
            </span>
          </div>
          <div class="flex-fill main-profile-info">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <h6 class="fw-semibold mb-1 text-fixed-white">${escapeHtml(nombre)}</h6>
              <div class="btn-list mb-0">
                ${puedeUsuarioSistema('editar') ? `<button type="button" class="btn btn-light btn-wave btn-sm" id="btn-detalle-editar-usuario" data-id="${usuario.id}">
                  <i class="ri-edit-line me-1 align-middle d-inline-block"></i>Editar
                </button>` : ''}
                <button type="button" class="btn btn-icon btn-light btn-wave btn-sm" data-bs-dismiss="modal" aria-label="Cerrar">
                  <i class="ri-close-line"></i>
                </button>
              </div>
            </div>
            <p class="mb-1 text-muted text-fixed-white op-7">${escapeHtml(usuario.email || 'Sin correo registrado')}</p>
            <p class="fs-12 text-fixed-white mb-4 op-5">
              <span class="me-3"><i class="ri-id-card-line me-1 align-middle"></i>${escapeHtml(obtenerDocumentoPersona(persona))}</span>
              <span><i class="ri-briefcase-line me-1 align-middle"></i>${escapeHtml(persona.cargo?.nombre || 'Sin cargo')}</span>
            </p>
            <div class="d-flex flex-wrap mb-0">
              <div class="me-4">
                <p class="fw-bold fs-20 text-fixed-white text-shadow mb-0">${permisos.length}</p>
                <p class="mb-0 fs-11 op-5 text-fixed-white">Modulos</p>
              </div>
              <div class="me-4">
                <p class="fw-bold fs-20 text-fixed-white text-shadow mb-0">${accionesTotal}</p>
                <p class="mb-0 fs-11 op-5 text-fixed-white">Acciones</p>
              </div>
              <div class="me-4">
                <p class="fw-bold fs-20 text-fixed-white text-shadow mb-0">${estadoActivo ? 'Activo' : 'Inactivo'}</p>
                <p class="mb-0 fs-11 op-5 text-fixed-white">Estado</p>
              </div>
              <div class="me-4">
                <p class="fw-bold fs-20 text-fixed-white text-shadow mb-0">${series.length}</p>
                <p class="mb-0 fs-11 op-5 text-fixed-white">Series</p>
              </div>
            </div>
          </div>
        </div>

        <div class="p-4 border-bottom border-block-end-dashed">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <p class="fs-15 mb-0 fw-semibold">Informacion del usuario</p>
            <div class="d-flex align-items-center flex-wrap gap-1">
              <span class="badge ${estadoActivo ? 'bg-success-transparent' : 'bg-danger-transparent'}">${estadoActivo ? 'Activo' : 'Eliminado'}</span>
              ${esSesion ? '<span class="badge bg-info-transparent">Tu sesion</span>' : ''}
            </div>
          </div>
          <div class="row gy-3">
            ${renderDetalleItem('Correo de acceso', usuario.email || '-', 'ri-mail-line')}
            ${renderDetalleItem('Persona vinculada', nombre, 'ri-user-line')}
            ${renderDetalleItem('Documento', obtenerDocumentoPersona(persona), 'ri-id-card-line')}
            ${renderDetalleItem('Codigo interno', persona.codigo || '-', 'ri-barcode-line')}
            ${renderDetalleItem('Cargo', persona.cargo?.nombre || '-', 'ri-briefcase-line')}
            ${renderDetalleItem('Autenticacion 2FA', tiene2FAActivo(usuario) ? 'Activo' : 'No configurado', 'ri-shield-keyhole-line')}
            ${renderDetalleItem('Celular', persona.celular || '-', 'ri-phone-line')}
            ${renderDetalleItem('Correo personal', persona.correo || '-', 'ri-at-line')}
            ${renderDetalleItem('Actualizado', formatearFechaUsuario(usuario.updated_at), 'ri-calendar-check-line')}
          </div>
        </div>

        <div class="p-4 border-bottom border-block-end-dashed">
          <p class="fs-15 mb-2 fw-semibold">Direccion</p>
          <div class="d-flex align-items-start">
            <span class="avatar avatar-sm avatar-rounded bg-light text-muted me-2">
              <i class="ri-map-pin-line align-middle fs-14"></i>
            </span>
            <div>
              <p class="mb-1">${escapeHtml(persona.direccion || 'Sin direccion registrada')}</p>
              ${persona.direccion_referencia ? `<span class="fs-12 text-muted">${escapeHtml(persona.direccion_referencia)}</span>` : ''}
            </div>
          </div>
        </div>

        <div class="p-4">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <p class="fs-15 mb-0 fw-semibold">Permisos activos</p>
            <span class="badge bg-primary-transparent">${accionesTotal} acciones</span>
          </div>
          ${renderDetallePermisos(permisos)}
        </div>

        <div class="p-4 pt-0">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <p class="fs-15 mb-0 fw-semibold">Series asignadas</p>
            <span class="badge bg-info-transparent">${series.length} serie(s)</span>
          </div>
          ${renderDetalleSeries(series)}
        </div>
      </div>
    </div>
  `);
}

function renderDetalleItem(label, value, icon) {
  return `
    <div class="col-md-6 col-xl-3">
      <div class="d-flex align-items-center">
        <span class="avatar avatar-sm avatar-rounded bg-light text-muted me-2">
          <i class="${escapeHtml(icon)} align-middle fs-14"></i>
        </span>
        <div class="flex-fill">
          <p class="mb-0 fw-semibold text-break">${escapeHtml(value || '-')}</p>
          <span class="fs-12 text-muted">${escapeHtml(label)}</span>
        </div>
      </div>
    </div>
  `;
}

function renderDetallePermisos(permisos) {
  if (!permisos.length) {
    return `
      <div class="text-center text-muted py-4 border rounded">
        <i class="ri-lock-line fs-24 d-block mb-1"></i>
        No tiene permisos activos registrados.
      </div>
    `;
  }

  const grupos = agruparPermisosDetalle(permisos);
  const cards = grupos
    .map(renderDetalleGrupoPermisos)
    .join('');

  return `<div class="row g-3">${cards}</div>`;
}

function renderDetalleSeries(series) {
  if (!series.length) {
    return `
      <div class="text-center text-muted py-4 border rounded">
        <i class="ri-file-list-3-line fs-24 d-block mb-1"></i>
        No tiene series asignadas.
      </div>
    `;
  }

  const filas = series
    .map(function (serieUsuario) {
      const serie = serieUsuario.serie_comprobante || {};
      const tipo = serie.tipo_comprobante || {};
      const etiquetaTipo = [tipo.codigo, tipo.nombre].filter(Boolean).join(' - ') || 'Tipo sin definir';

      return `
        <li class="list-group-item py-2">
          <div class="d-sm-flex align-items-center justify-content-between gap-2">
            <div>
              <p class="mb-0 fs-12 fw-semibold">${escapeHtml(serie.serie || '-')}</p>
              <span class="fs-11 text-muted">${escapeHtml(etiquetaTipo)}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-light text-default">Nro: ${escapeHtml(String(serie.numero ?? '-'))}</span>
              ${String(serie.predeterminado || '0') === '1' ? '<span class="badge bg-success-transparent text-success">Predeterminada</span>' : ''}
            </div>
          </div>
        </li>
      `;
    })
    .join('');

  return `<ul class="list-group list-group-flush border rounded">${filas}</ul>`;
}

function renderDetalleGrupoPermisos(grupo) {
  const permisosHtml = grupo.permisos.map(renderDetallePermiso).join('');
  const resumen = obtenerResumenAccionesGrupo(grupo.permisos);

  return `
    <div class="col-xl-4 col-lg-6 col-md-12">
      <div class="card custom-card shadow-none border mb-0 h-100">
        <div class="card-header justify-content-between">
          <div class="card-title mb-0">
            <span class="avatar avatar-sm avatar-rounded bg-primary-transparent text-primary me-2">
              <i class="ri-folder-shield-2-line fs-14"></i>
            </span>
            ${escapeHtml(grupo.nombre)}
          </div>
          <span class="badge bg-light text-default">${grupo.permisos.length} modulo(s)</span>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            ${permisosHtml}
          </ul>
        </div>
        <div class="card-footer border-top border-block-start-dashed">
          <div class="d-flex flex-wrap gap-1">
            ${renderBadgeResumenPermiso('Ver', resumen.ver, 'primary')}
            ${renderBadgeResumenPermiso('Crear', resumen.crear, 'success')}
            ${renderBadgeResumenPermiso('Editar', resumen.editar, 'warning')}
            ${renderBadgeResumenPermiso('Eliminar', resumen.eliminar, 'danger')}
          </div>
        </div>
      </div>
    </div>
  `;
}

function renderDetallePermiso(permisoUsuario) {
  const permiso = permisoUsuario.permiso || {};
  const acciones = [
    renderAccionPermiso('Ver', true, 'primary'),
    renderAccionPermiso('Crear', String(permisoUsuario.crear || '') === '1', 'success'),
    renderAccionPermiso('Editar', String(permisoUsuario.editar || '') === '1', 'warning'),
    renderAccionPermiso('Eliminar', String(permisoUsuario.eliminar || '') === '1', 'danger'),
  ].join('');

  return `
    <li class="list-group-item py-1">
      <div class="d-sm-flex align-items-start justify-content-between gap-2">
        <div class="mb-sm-0 mb-2">
          <p class="mb-0 fs-11 fw-semibold">${escapeHtml(permiso.descripcion || 'Permiso sin nombre')}</p>
          <span class="fs-10 text-muted">${escapeHtml([permiso.codigo, permiso.nombre_clave].filter(Boolean).join(' | ') || '-')}</span>
        </div>
        <div class="d-flex flex-wrap gap-1 justify-content-sm-end">${acciones}</div>
      </div>
    </li>
  `;
}

function agruparPermisosDetalle(permisos) {
  const padres = permisosCatalogo
    .filter((permiso) => String(permiso.nivel) === '1')
    .sort(ordenarPermisosPorCodigo);
  const gruposMap = new Map();

  permisos
    .slice()
    .sort(function (a, b) {
      return String(a.permiso?.codigo || '').localeCompare(String(b.permiso?.codigo || ''), undefined, { numeric: true });
    })
    .forEach(function (permisoUsuario) {
      const permiso = permisoUsuario.permiso || {};
      const padre = padres.find(function (item) {
        return String(permiso.codigo || '').startsWith(String(item.codigo || ''));
      });
      const codigoGrupo = padre?.codigo || obtenerPrefijoPermiso(permiso.codigo);
      const nombreGrupo = padre
        ? `${padre.codigo} - ${padre.descripcion}`
        : `Modulo ${codigoGrupo || 'General'}`;

      if (!gruposMap.has(codigoGrupo)) {
        gruposMap.set(codigoGrupo, {
          codigo: codigoGrupo,
          nombre: nombreGrupo,
          permisos: [],
        });
      }

      gruposMap.get(codigoGrupo).permisos.push(permisoUsuario);
    });

  return Array.from(gruposMap.values()).sort(function (a, b) {
    return String(a.codigo || '').localeCompare(String(b.codigo || ''), undefined, { numeric: true });
  });
}

function obtenerPrefijoPermiso(codigo) {
  const value = String(codigo || '');

  return value.length >= 2 ? value.slice(0, 2) : value;
}

function obtenerResumenAccionesGrupo(permisos) {
  return (permisos || []).reduce(function (resumen, permisoUsuario) {
    resumen.ver += 1;
    resumen.crear += String(permisoUsuario.crear || '') === '1' ? 1 : 0;
    resumen.editar += String(permisoUsuario.editar || '') === '1' ? 1 : 0;
    resumen.eliminar += String(permisoUsuario.eliminar || '') === '1' ? 1 : 0;

    return resumen;
  }, {
    ver: 0,
    crear: 0,
    editar: 0,
    eliminar: 0,
  });
}

function renderBadgeResumenPermiso(label, total, color) {
  return total > 0
    ? `<span class="badge bg-${color}-transparent">${label}: ${total}</span>`
    : `<span class="badge bg-light text-muted">${label}: 0</span>`;
}

function renderAccionPermiso(label, active, color) {
  return active
    ? `<span class="badge bg-${color}-transparent text-${color}">${label}</span>`
    : `<span class="badge bg-light text-muted">${label}</span>`;
}

function obtenerFotoPerfilPersona(persona) {
  const foto = persona.foto_perfil || (persona.sexo === 'F' ? 'mujer.png' : 'hombre.png');

  return apiUrl(`/assets/modulo/persona/perfil/${foto}`);
}

function obtenerDocumentoPersona(persona) {
  const tipo = persona.doc_identidad?.abreviatura
    || persona.doc_identidad?.nombre
    || persona.tipo_documento
    || '';
  const numero = persona.numero_documento || '';

  return [tipo, numero].filter(Boolean).join(' ') || '-';
}

function formatearFechaUsuario(value) {
  if (!value) {
    return '-';
  }

  if (typeof moment !== 'undefined') {
    return moment(value).format('DD/MM/YYYY HH:mm');
  }

  return value;
}

function contarAccionesPermiso(permisos) {
  return (permisos || []).reduce(function (total, permisoUsuario) {
    return total
      + 1
      + (String(permisoUsuario.crear || '') === '1' ? 1 : 0)
      + (String(permisoUsuario.editar || '') === '1' ? 1 : 0)
      + (String(permisoUsuario.eliminar || '') === '1' ? 1 : 0);
  }, 0);
}

function gestionar2FAUsuario(id) {
  if (!id) {
    mostrarError('Seleccione un usuario.');
    return;
  }

  if (!esUsuarioAutenticado(id)) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Autenticación 2FA',
        html: `
          <div class="text-start">
            <p class="mb-2">La autenticacion de dos factores debe activarla el propio usuario desde su sesion.</p>
            <p class="mb-0 text-muted">Esto es necesario porque debe escanear su codigo QR y confirmar el codigo de su aplicacion autenticadora.</p>
          </div>
        `,
        icon: 'info',
        confirmButtonText: 'Entendido',
      });
      return;
    }

    mostrarError('El usuario debe activar 2FA desde su propia sesion.');
    return;
  }

  abrirModal2FAUsuario(id);
}

function abrirModal2FAUsuario(id) {
  twoFactorActivationPending = false;
  $('#modal-2fa-usuario-title').text('Autenticación de dos factores');
  $('#modal-2fa-usuario-body').html(`
    <div class="text-center text-muted py-5">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <div class="fw-semibold">Preparando configuración 2FA...</div>
    </div>
  `);

  const modalElement = document.getElementById('modal-2fa-usuario');

  if (modalElement && typeof bootstrap !== 'undefined') {
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
  }

  $.ajax({
    url: apiUrl(`/usuarios/${id}`),
    type: 'GET',
    headers: { 'X-CSRF-TOKEN': csrf() },
    success: function (response) {
      if (!response.status || !response.data) {
        mostrarError(response.message || 'No se pudo consultar el estado 2FA.');
        cerrarModal2FAUsuario();
        return;
      }

      if (tiene2FAActivo(response.data)) {
        render2FAActivo();
        return;
      }

      render2FAPasoPassword();
    },
    error: function (xhr) {
      cerrarModal2FAUsuario();
      mostrarError(obtenerMensajeAjaxError(xhr, 'Error al consultar el estado 2FA.'));
    },
  });
}

function cerrarModal2FAUsuario() {
  const modalElement = document.getElementById('modal-2fa-usuario');

  if (modalElement && typeof bootstrap !== 'undefined') {
    bootstrap.Modal.getOrCreateInstance(modalElement).hide();
  }
}

function render2FAPasoPassword() {
  $('#modal-2fa-usuario-body').html(`
    <div class="card custom-card mb-0 shadow-none border-0">
      <div class="card-body p-0">
        <div class="p-4 border-bottom border-block-end-dashed">
          <div class="d-flex align-items-start">
            <span class="avatar avatar-lg avatar-rounded bg-primary-transparent text-primary me-3">
              <i class="ri-shield-keyhole-line fs-24"></i>
            </span>
            <div>
              <p class="fs-15 mb-1 fw-semibold">Activar autenticación 2FA</p>
              <p class="text-muted mb-0">Confirma tu contraseña para generar el código QR de configuración.</p>
            </div>
          </div>
        </div>
        <div class="p-4">
          <div class="form-group mb-3">
            <label for="two_factor_password" class="form-label">Contraseña actual</label>
            <div class="input-group">
              <input type="password" class="form-control" id="two_factor_password" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" autocapitalize="off" spellcheck="false">
              <button type="button" class="btn btn-light border usuario-toggle-password" data-target="#two_factor_password" aria-label="Mostrar contraseña">
                <i class="ri-eye-line"></i>
              </button>
            </div>
            <div class="invalid-feedback" id="two_factor_password_error"></div>
          </div>
          <div class="btn-list text-end">
            <button type="button" class="btn btn-light btn-wave" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary btn-wave" id="btn-2fa-generar">
              <i class="ri-qr-code-line me-1"></i> Generar QR
            </button>
          </div>
        </div>
      </div>
    </div>
  `);

}

$(document).on('click', '#btn-2fa-generar', function () {
  const password = $('#two_factor_password').val();

  if (!password) {
    mostrarErrorCampo2FA('Ingrese su contraseña actual.');
    return;
  }

  cambiarEstadoBoton2FA($(this), true, 'Generando...');
  confirmarPassword2FA(password)
    .then(habilitar2FA)
    .then(cargarDatosConfiguracion2FA)
    .done(render2FAPasoConfirmacion)
    .fail(function (xhr) {
      mostrarErrorCampo2FA(obtenerMensajeValidacion2FA(xhr, 'No se pudo activar 2FA.'));
    })
    .always(function () {
      cambiarEstadoBoton2FA($('#btn-2fa-generar'), false, '<i class="ri-qr-code-line me-1"></i> Generar QR');
    });
});

$(document).on('keydown', '#two_factor_password', function (event) {
  if (event.key !== 'Enter') {
    return;
  }

  event.preventDefault();
  $('#btn-2fa-generar').trigger('click');
});

function confirmarPassword2FA(password) {
  return $.ajax({
    url: apiUrl('/user/confirm-password'),
    type: 'POST',
    headers: fortifyHeaders(),
    data: { password },
  });
}

function habilitar2FA() {
  return $.ajax({
    url: apiUrl('/user/two-factor-authentication'),
    type: 'POST',
    headers: fortifyHeaders(),
    data: { force: true },
  });
}

function cargarDatosConfiguracion2FA() {
  const qrRequest = $.ajax({
    url: apiUrl('/user/two-factor-qr-code'),
    type: 'GET',
    headers: fortifyHeaders(),
  });
  const secretRequest = $.ajax({
    url: apiUrl('/user/two-factor-secret-key'),
    type: 'GET',
    headers: fortifyHeaders(),
  });

  return $.when(qrRequest, secretRequest).then(function (qrResponse, secretResponse) {
    return {
      qr: qrResponse[0] || {},
      secret: secretResponse[0] || {},
    };
  });
}

function render2FAPasoConfirmacion(data) {
  twoFactorActivationPending = true;

  $('#modal-2fa-usuario-body').html(`
    <div class="card custom-card mb-0 shadow-none border-0">
      <div class="card-body p-0">
        <div class="p-4 border-bottom border-block-end-dashed">
          <p class="fs-15 mb-1 fw-semibold">Escanea el código QR</p>
          <p class="text-muted mb-0">Usa Google Authenticator, Microsoft Authenticator, Authy o una app compatible.</p>
        </div>
        <div class="p-4 border-bottom border-block-end-dashed">
          <div class="row g-4 align-items-center">
            <div class="col-md-5 text-center">
              <div class="d-inline-block bg-white border rounded p-2">${data.qr.svg || ''}</div>
            </div>
            <div class="col-md-7">
              <p class="fs-13 text-muted mb-2">También puedes ingresar esta clave manualmente:</p>
              <div class="alert alert-light border text-break mb-0">
                <code>${escapeHtml(data.secret.secretKey || '')}</code>
              </div>
            </div>
          </div>
        </div>
        <div class="p-4">
          <div class="form-group mb-3">
            <label for="two_factor_code" class="form-label">Código de verificación</label>
            <input type="text" class="form-control" id="two_factor_code" inputmode="numeric" autocomplete="one-time-code" placeholder="000000">
            <div class="invalid-feedback" id="two_factor_code_error"></div>
          </div>
          <div class="btn-list text-end">
            <button type="button" class="btn btn-light btn-wave" id="btn-2fa-cancelar">Cancelar activación</button>
            <button type="button" class="btn btn-primary btn-wave" id="btn-2fa-confirmar">
              <i class="ri-shield-check-line me-1"></i> Confirmar 2FA
            </button>
          </div>
        </div>
      </div>
    </div>
  `);

  $('#two_factor_code').trigger('focus');
}

$(document).on('click', '#btn-2fa-confirmar', function () {
  const code = $('#two_factor_code').val();

  if (!code) {
    mostrarErrorCodigo2FA('Ingrese el código generado por su aplicación autenticadora.');
    return;
  }

  cambiarEstadoBoton2FA($(this), true, 'Confirmando...');

  $.ajax({
    url: apiUrl('/user/confirmed-two-factor-authentication'),
    type: 'POST',
    headers: fortifyHeaders(),
    data: { code },
    success: function () {
      twoFactorActivationPending = false;
      cargarCodigosRecuperacion2FA()
        .then(render2FACompletado)
        .always(function () {
          recargarTablaUsuarios();
        });
    },
    error: function (xhr) {
      mostrarErrorCodigo2FA(obtenerMensajeValidacion2FA(xhr, 'El código ingresado no es válido.'));
    },
    complete: function () {
      cambiarEstadoBoton2FA($('#btn-2fa-confirmar'), false, '<i class="ri-shield-check-line me-1"></i> Confirmar 2FA');
    },
  });
});

$(document).on('click', '#btn-2fa-cancelar', function () {
  deshabilitar2FA().always(function () {
    twoFactorActivationPending = false;
    recargarTablaUsuarios();
    render2FAPasoPassword();
  });
});

function cargarCodigosRecuperacion2FA() {
  return $.ajax({
    url: apiUrl('/user/two-factor-recovery-codes'),
    type: 'GET',
    headers: fortifyHeaders(),
  });
}

function deshabilitar2FA() {
  return $.ajax({
    url: apiUrl('/user/two-factor-authentication'),
    type: 'DELETE',
    headers: fortifyHeaders(),
  });
}

function render2FACompletado(codes) {
  const codigos = Array.isArray(codes) ? codes : [];
  const codigosHtml = codigos
    .map((code) => `<div>${escapeHtml(code)}</div>`)
    .join('');

  $('#modal-2fa-usuario-body').html(`
    <div class="card custom-card mb-0 shadow-none border-0">
      <div class="card-body p-0">
        <div class="p-4 border-bottom border-block-end-dashed">
          <div class="d-flex align-items-start">
            <span class="avatar avatar-lg avatar-rounded bg-success-transparent text-success me-3">
              <i class="ri-shield-check-line fs-24"></i>
            </span>
            <div>
              <p class="fs-15 mb-1 fw-semibold">2FA activado correctamente</p>
              <p class="text-muted mb-0">Guarda estos códigos de recuperación en un lugar seguro.</p>
            </div>
          </div>
        </div>
        <div class="p-4">
          <div class="bg-light rounded p-3 font-monospace fs-13 mb-3">
            ${codigosHtml || '<div class="text-muted">No se pudieron cargar los códigos de recuperación.</div>'}
          </div>
          <div class="alert alert-warning mb-3">
            Estos códigos permiten recuperar el acceso si pierdes tu aplicación autenticadora.
          </div>
          <div class="text-end">
            <button type="button" class="btn btn-primary btn-wave" data-bs-dismiss="modal">Finalizar</button>
          </div>
        </div>
      </div>
    </div>
  `);

  mostrarOk('Autenticación de dos factores activada correctamente.');
}

function render2FAActivo() {
  $('#modal-2fa-usuario-body').html(`
    <div class="card custom-card mb-0 shadow-none border-0">
      <div class="card-body p-0">
        <div class="p-4 border-bottom border-block-end-dashed">
          <div class="d-flex align-items-start">
            <span class="avatar avatar-lg avatar-rounded bg-success-transparent text-success me-3">
              <i class="ri-shield-check-line fs-24"></i>
            </span>
            <div>
              <p class="fs-15 mb-1 fw-semibold">2FA ya está activo</p>
              <p class="text-muted mb-0">Tu cuenta ya solicita un segundo paso de verificación al iniciar sesión.</p>
            </div>
          </div>
        </div>
        <div class="p-4 border-bottom border-block-end-dashed">
          <div class="form-group mb-3">
            <label for="two_factor_disable_password" class="form-label">Contraseña actual</label>
            <div class="input-group">
              <input type="password" class="form-control" id="two_factor_disable_password" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" autocapitalize="off" spellcheck="false">
              <button type="button" class="btn btn-light border usuario-toggle-password" data-target="#two_factor_disable_password" aria-label="Mostrar contraseña">
                <i class="ri-eye-line"></i>
              </button>
            </div>
            <div class="invalid-feedback" id="two_factor_disable_password_error"></div>
          </div>
          <div class="alert alert-warning mb-0">
            Al desactivar 2FA, tu cuenta volverá a iniciar sesión solo con correo y contraseña.
          </div>
        </div>
        <div class="p-4 text-end">
          <button type="button" class="btn btn-light btn-wave" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-danger btn-wave" id="btn-2fa-desactivar">
            <i class="ri-shield-cross-line me-1"></i> Desactivar 2FA
          </button>
        </div>
      </div>
    </div>
  `);

}

$(document).on('click', '#btn-2fa-desactivar', function () {
  const password = $('#two_factor_disable_password').val();

  if (!password) {
    mostrarErrorPasswordDesactivar2FA('Ingrese su contraseña actual.');
    return;
  }

  cambiarEstadoBoton2FA($(this), true, 'Desactivando...');

  confirmarPassword2FA(password)
    .then(deshabilitar2FA)
    .done(function () {
      twoFactorActivationPending = false;
      recargarTablaUsuarios();
      render2FADesactivado();
      mostrarOk('Autenticación de dos factores desactivada correctamente.');
    })
    .fail(function (xhr) {
      mostrarErrorPasswordDesactivar2FA(obtenerMensajeValidacion2FA(xhr, 'No se pudo desactivar 2FA.'));
    })
    .always(function () {
      cambiarEstadoBoton2FA($('#btn-2fa-desactivar'), false, '<i class="ri-shield-cross-line me-1"></i> Desactivar 2FA');
    });
});

$(document).on('keydown', '#two_factor_disable_password', function (event) {
  if (event.key !== 'Enter') {
    return;
  }

  event.preventDefault();
  $('#btn-2fa-desactivar').trigger('click');
});

$(document).on('click', '.usuario-toggle-password', function () {
  const $button = $(this);
  const $input = $($button.data('target'));
  const visible = $input.attr('type') === 'text';

  $input.attr('type', visible ? 'password' : 'text');
  $button.attr('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
  $button.find('i').toggleClass('ri-eye-line', visible).toggleClass('ri-eye-off-line', !visible);
});

function render2FADesactivado() {
  $('#modal-2fa-usuario-body').html(`
    <div class="card custom-card mb-0 shadow-none border-0">
      <div class="card-body p-0">
        <div class="p-4 border-bottom border-block-end-dashed">
          <div class="d-flex align-items-start">
            <span class="avatar avatar-lg avatar-rounded bg-danger-transparent text-danger me-3">
              <i class="ri-shield-cross-line fs-24"></i>
            </span>
            <div>
              <p class="fs-15 mb-1 fw-semibold">2FA desactivado</p>
              <p class="text-muted mb-0">La cuenta ya no solicitará código adicional al iniciar sesión.</p>
            </div>
          </div>
        </div>
        <div class="p-4 text-end">
          <button type="button" class="btn btn-light btn-wave" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary btn-wave" id="btn-2fa-activar-nuevamente">
            <i class="ri-shield-keyhole-line me-1"></i> Activar nuevamente
          </button>
        </div>
      </div>
    </div>
  `);
}

$(document).on('click', '#btn-2fa-activar-nuevamente', function () {
  render2FAPasoPassword();
});

function mostrarErrorCampo2FA(message) {
  $('#two_factor_password')
    .addClass('is-invalid')
    .removeClass('is-valid');
  $('#two_factor_password_error').addClass('d-block').text(message);
}

function mostrarErrorCodigo2FA(message) {
  $('#two_factor_code')
    .addClass('is-invalid')
    .removeClass('is-valid');
  $('#two_factor_code_error').text(message);
}

function mostrarErrorPasswordDesactivar2FA(message) {
  $('#two_factor_disable_password')
    .addClass('is-invalid')
    .removeClass('is-valid');
  $('#two_factor_disable_password_error').addClass('d-block').text(message);
}

function obtenerMensajeValidacion2FA(xhr, fallback) {
  const response = xhr?.responseJSON || {};
  const errors = response.errors || {};

  return errors.password?.[0]
    || errors.code?.[0]
    || response.message
    || fallback;
}

function cambiarEstadoBoton2FA($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function verSesionesUsuario(id) {
  if (!id) {
    mostrarError('Seleccione un usuario.');
    return;
  }

  abrirModalSesionesUsuario(id);
  cargarSesionesUsuario(id);
}

function abrirModalSesionesUsuario(id) {
  $('#modal-sesiones-usuario')
    .data('id', id);
  $('#modal-sesiones-usuario-title').text('Sesiones activas');
  $('#modal-sesiones-usuario-body').html(`
    <div class="text-center text-muted py-5">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <div class="fw-semibold">Consultando sesiones activas...</div>
    </div>
  `);

  const modalElement = document.getElementById('modal-sesiones-usuario');

  if (modalElement && typeof bootstrap !== 'undefined') {
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
  }
}

function cargarSesionesUsuario(id) {
  $.ajax({
    url: apiUrl(`/usuarios/${id}/sesiones`),
    type: 'GET',
    headers: { 'X-CSRF-TOKEN': csrf() },
    success: function (response) {
      if (!response.status || !response.data) {
        mostrarError(response.message || 'No se pudo cargar sesiones.');
        return;
      }

      renderSesionesUsuario(response.data);
    },
    error: function (xhr) {
      mostrarError(obtenerMensajeAjaxError(xhr, 'Error al consultar sesiones activas.'));
    },
  });
}

function renderSesionesUsuario(data) {
  const user = data.user || {};
  const sessions = data.sessions || [];
  const currentUser = esUsuarioAutenticado(user.id);
  const cierreTexto = currentUser ? 'Cerrar otras sesiones' : 'Cerrar todas';
  const cierreDisabled = sessions.filter((session) => !session.is_current_device).length === 0 ? 'disabled' : '';

  $('#modal-sesiones-usuario-title').text(`Sesiones activas - ${user.email || 'Usuario'}`);
  $('#modal-sesiones-usuario-body').html(`
    <div class="card custom-card mb-0 shadow-none border-0">
      <div class="card-body p-0">
        <div class="p-4 border-bottom border-block-end-dashed">
          <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-start">
              <span class="avatar avatar-lg avatar-rounded bg-primary-transparent text-primary me-3">
                <i class="ri-computer-line fs-24"></i>
              </span>
              <div>
                <p class="fs-15 mb-1 fw-semibold">${escapeHtml(user.name || 'Usuario')}</p>
                <p class="text-muted mb-0">${escapeHtml(user.email || '')}</p>
              </div>
            </div>
            <button type="button" class="btn btn-danger-light btn-wave" id="btn-cerrar-sesiones-usuario" ${cierreDisabled}>
              <i class="ri-logout-box-r-line me-1"></i>${cierreTexto}
            </button>
          </div>
        </div>
        <div class="p-4">
          ${renderListaSesionesUsuario(sessions)}
        </div>
      </div>
    </div>
  `);
}

function renderListaSesionesUsuario(sessions) {
  if (!sessions.length) {
    return `
      <div class="text-center text-muted py-4 border rounded">
        <i class="ri-computer-line fs-24 d-block mb-1"></i>
        No hay sesiones activas para este usuario.
      </div>
    `;
  }

  const cards = sessions.map(renderSesionUsuarioCard).join('');

  return `<div class="row g-3">${cards}</div>`;
}

function renderSesionUsuarioCard(session) {
  const icon = session.is_desktop ? 'ri-computer-line' : 'ri-smartphone-line';
  const closeDisabled = session.is_current_device ? 'disabled' : '';
  const closeTitle = session.is_current_device
    ? 'No puedes cerrar la sesion actual desde este panel'
    : 'Cerrar sesion';

  return `
    <div class="col-md-6">
      <div class="card custom-card shadow-none border mb-0 h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div class="d-flex align-items-start">
              <span class="avatar avatar-md avatar-rounded bg-light text-muted me-3">
                <i class="${icon} fs-20"></i>
              </span>
              <div>
                <p class="mb-1 fw-semibold">${escapeHtml(session.platform)} - ${escapeHtml(session.browser)}</p>
                <p class="mb-1 fs-12 text-muted">
                  <i class="ri-map-pin-line me-1"></i>${escapeHtml(session.ip_address || 'IP desconocida')}
                </p>
                <p class="mb-0 fs-12 text-muted">
                  <i class="ri-time-line me-1"></i>${escapeHtml(session.last_active || '-')}
                </p>
              </div>
            </div>
            ${session.is_current_device ? '<span class="badge bg-success-transparent">Este dispositivo</span>' : ''}
          </div>
        </div>
        <div class="card-footer border-top border-block-start-dashed text-end">
          <button type="button" class="btn btn-sm btn-danger-light btn-wave btn-cerrar-sesion-usuario"
            data-session-id="${escapeHtml(session.id)}" title="${closeTitle}" ${closeDisabled}>
            <i class="ri-logout-box-r-line me-1"></i>Cerrar
          </button>
        </div>
      </div>
    </div>
  `;
}

$(document).on('click', '.btn-cerrar-sesion-usuario', function () {
  const $button = $(this);
  const userId = $('#modal-sesiones-usuario').data('id');
  const sessionId = $button.data('session-id');

  if (!userId || !sessionId || $button.prop('disabled')) {
    return;
  }

  confirmarCierreSesionUsuario('Cerrar sesión', 'Esta sesión se cerrará inmediatamente.', function () {
    cambiarEstadoBoton2FA($button, true, 'Cerrando...');

    $.ajax({
      url: apiUrl(`/usuarios/${userId}/sesiones/${sessionId}`),
      type: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf() },
      success: function (response) {
        mostrarOk(response.message || 'Sesion cerrada correctamente.');
        cargarSesionesUsuario(userId);
      },
      error: function (xhr) {
        mostrarError(obtenerMensajeAjaxError(xhr, 'No se pudo cerrar la sesion.'));
      },
      complete: function () {
        cambiarEstadoBoton2FA($button, false, '<i class="ri-logout-box-r-line me-1"></i>Cerrar');
      },
    });
  });
});

$(document).on('click', '#btn-cerrar-sesiones-usuario', function () {
  const $button = $(this);
  const userId = $('#modal-sesiones-usuario').data('id');

  if (!userId || $button.prop('disabled')) {
    return;
  }

  const message = esUsuarioAutenticado(userId)
    ? 'Se cerrarán tus otras sesiones. Tu sesión actual permanecerá activa.'
    : 'Se cerrarán todas las sesiones activas de este usuario.';

  confirmarCierreSesionUsuario('Cerrar sesiones', message, function () {
    cambiarEstadoBoton2FA($button, true, 'Cerrando...');

    $.ajax({
      url: apiUrl(`/usuarios/${userId}/sesiones`),
      type: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf() },
      success: function (response) {
        mostrarOk(response.message || 'Sesiones cerradas correctamente.');
        cargarSesionesUsuario(userId);
      },
      error: function (xhr) {
        mostrarError(obtenerMensajeAjaxError(xhr, 'No se pudieron cerrar las sesiones.'));
      },
      complete: function () {
        cambiarEstadoBoton2FA($button, false, '<i class="ri-logout-box-r-line me-1"></i>Cerrar sesiones');
      },
    });
  });
});

function confirmarCierreSesionUsuario(title, message, callback) {
  if (typeof Swal === 'undefined') {
    callback();
    return;
  }

  Swal.fire({
    title,
    text: message,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, cerrar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
  }).then(function (result) {
    if (result.isConfirmed) {
      callback();
    }
  });
}

function editarUsuario(id) {
  if (!puedeUsuarioSistema('editar')) {
    mostrarError('No tienes permiso para editar usuarios.');
    return;
  }

  if (!id) {
    mostrarError('Seleccione un usuario.');
    return;
  }

  $.ajax({
    url: apiUrl(`/usuarios/${id}`),
    type: 'GET',
    headers: { 'X-CSRF-TOKEN': csrf() },
    success: function (response) {
      if (!response.status || !response.data) {
        mostrarError(response.message || 'No se pudo cargar el usuario.');
        return;
      }

      cargarUsuarioEnFormulario(response.data);
    },
    error: function () {
      mostrarError('Error al cargar usuario para editar.');
    },
  });
}

function cargarUsuarioEnFormulario(usuario) {
  const persona = usuario.persona || {};
  const personaTexto = [
    persona.descripcion || usuario.name || usuario.email,
    [persona.codigo, persona.numero_documento].filter(Boolean).join(' | '),
  ].filter(Boolean).join(' (') + ([persona.codigo, persona.numero_documento].filter(Boolean).length ? ')' : '');
  const option = new Option(personaTexto, persona.idpersona || usuario.idpersona, true, true);

  $('#form-agregar-usuario')[0]?.reset();
  $('#id').val(usuario.id);
  $('#titulo-formulario-usuario').text('Editar usuario');
  $('#btn-guardar-usuario, #btn-guardar-usuario-header').html('<i class="bi bi-floppy-fill me-1"></i> Actualizar datos');
  $('#email').val(usuario.email || '');
  $('#password, #password_confirmation').val('');

  $('#idpersona')
    .empty()
    .append(option)
    .trigger('change');

  limpiarPermisosUsuario();
  limpiarSeriesUsuario();
  aplicarPermisosUsuario(usuario.permisos_usuario || []);
  aplicarSeriesUsuario(usuario.series_usuario || []);
  limpiarValidacionUsuario();
  show_hide_form(2);
}

function aplicarPermisosUsuario(permisos) {
  if (!$('#contenedor-tenant-permisos .usuario-permiso-row').length) {
    setTimeout(function () {
      aplicarPermisosUsuario(permisos);
    }, 150);
    return;
  }

  (permisos || [])
    .filter((permisoUsuario) => String(permisoUsuario.estado_trash || '1') === '1')
    .forEach(function (permisoUsuario) {
      const $row = $(`.usuario-permiso-row[data-idpermiso="${permisoUsuario.idpermiso}"]`);

      if (!$row.length) {
        return;
      }

      $row.find('.usuario-permiso-ver').prop('checked', true);
      $row.find('.usuario-permiso-accion').prop('disabled', false);
      $row.find('.usuario-permiso-crear').prop('checked', String(permisoUsuario.crear || '') === '1');
      $row.find('.usuario-permiso-editar').prop('checked', String(permisoUsuario.editar || '') === '1');
      $row.find('.usuario-permiso-eliminar').prop('checked', String(permisoUsuario.eliminar || '') === '1');
    });

  actualizarContadorPermisosUsuario();
}

function eliminarUsuario(id) {
  if (!puedeUsuarioSistema('eliminar')) {
    mostrarError('No tienes permiso para eliminar usuarios.');
    return;
  }

  if (typeof Swal === 'undefined') {
    return;
  }

  if (esUsuarioAutenticado(id)) {
    mostrarError('No puedes eliminar ni desactivar tu propio usuario mientras tienes la sesion iniciada.');
    return;
  }

  Swal.fire({
    title: 'Eliminar usuario',
    text: 'El usuario se marcara como eliminado.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, eliminar',
    cancelButtonText: 'Cancelar',
  }).then(function (result) {
    if (!result.isConfirmed) {
      return;
    }

    $.ajax({
      url: apiUrl(`/usuarios/${id}`),
      type: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf() },
      success: function (response) {
        if (response.status) {
          mostrarOk(response.message || 'Usuario eliminado correctamente.');
          recargarTablaUsuarios();
          return;
        }

        mostrarError(response.message || 'No se pudo eliminar el usuario.');
      },
      error: function (xhr) {
        mostrarError(obtenerMensajeAjaxError(xhr, 'Error al eliminar usuario.'));
      },
    });
  });
}

function eliminarUsuarioPermanente(id) {
  if (!puedeUsuarioSistema('eliminar')) {
    mostrarError('No tienes permiso para eliminar usuarios.');
    return;
  }

  if (typeof Swal === 'undefined') {
    return;
  }

  const ids = obtenerIdsParaAccionContextual();
  const idsEliminables = obtenerIdsEliminables(ids);
  const total = ids.length;
  const totalEliminables = idsEliminables.length;
  const correos = obtenerCorreosParaAccionContextual(idsEliminables);

  if (total === 0) {
    mostrarError('Seleccione uno o mas usuarios.');
    return;
  }

  if (totalEliminables === 0) {
    mostrarError('No puedes eliminar permanentemente tu propio usuario mientras tienes la sesion iniciada.');
    return;
  }

  Swal.fire({
    title: totalEliminables > 1 ? `Eliminar ${totalEliminables} usuarios permanentemente` : 'Eliminar permanentemente',
    html: `
      ${renderCorreosSeleccionados(correos)}
      ${totalEliminables < total ? '<p class="text-start text-warning mt-3 mb-0">Tu usuario de sesion sera omitido por seguridad.</p>' : ''}
      <p class="text-start text-danger mt-3 mb-0">
        Esta accion borrara ${totalEliminables > 1 ? 'estos usuarios' : 'este usuario'} de la base de datos y no se podra recuperar.
      </p>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, eliminar permanente',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
  }).then(function (result) {
    if (!result.isConfirmed) {
      return;
    }

    $.ajax({
      url: totalEliminables > 1 ? apiUrl('/usuarios/permanente') : apiUrl(`/usuarios/${idsEliminables[0] || id}/permanente`),
      type: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf() },
      data: totalEliminables > 1 ? { ids: idsEliminables } : {},
      success: function (response) {
        if (response.status) {
          const deleted = response.data?.deleted;
          const selected = response.data?.selected || totalEliminables;
          const message = Number.isInteger(deleted)
            ? `${deleted} de ${selected} usuario(s) eliminado(s) exitosamente.`
            : (response.message || 'Usuario eliminado permanentemente.');

          mostrarOk(message);
          limpiarSeleccionUsuarios();
          recargarTablaUsuarios();
          return;
        }

        mostrarError(response.message || 'No se pudo eliminar permanentemente el usuario.');
      },
      error: function (xhr) {
        mostrarError(obtenerMensajeAjaxError(xhr, 'Error al eliminar permanentemente usuario.'));
      },
    });
  });
}

// ============================================================================
// NOTIFICACIONES Y UTILIDADES
// ============================================================================

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

function obtenerMensajeAjaxError(xhr, fallback) {
  const response = xhr?.responseJSON || {};

  return response.message || fallback;
}

function esUsuarioAutenticado(id) {
  return authUserId !== null && Number(id) === authUserId;
}

function tiene2FAActivo(usuario) {
  return Boolean(usuario?.two_factor_confirmed_at);
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}


// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// ═══════                                       J Q   F O R M   V A L I D A T I O N S                                                              ═══════
// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
$(function () {
  return;

  // validamos el formulario  

  $('#tipo_entidad_sunat').on('change', function() { $(this).trigger('blur'); });
  $('#tipo_documento').on('change', function() { $(this).trigger('blur'); });

  $("#form-agregar-entidad").validate({
    //ignore: '.select2-input, .select2-focusser',
    rules: {
      tipo_entidad_sunat: { required: true },
      tipo_documento:     { required: true},
      descripcion:        { required: true, minlength: 4, maxlength: 300 },
      numero_documento:   { required: true, minlength: 4, maxlength: 300 },
      direccion:          { required: true, },
      fecha_fin:          { required: true, },      
    },
    messages: {
      tipo_entidad_sunat:  { required: "Campo requerido." },
      tipo_documento:      { required: "Campo requerido.", minlength: "MÍNIMO {0} caracteres.", maxlength: "MÁXIMO {0} caracteres.", },
      descripcion:         { required: "Campo requerido.", minlength: "MÍNIMO {0} caracteres.", maxlength: "MÁXIMO {0} caracteres.", },
      numero_documento:    { required: "Campo requerido." },
      direccion:           { required: "Campo requerido.", },
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
      $(".modal-body").animate({ scrollTop: $(document).height() }, 600); // Scrollea hasta abajo de la página
      guardar_y_editar_personal(e);       
    },
  });

  $('#tipo_entidad_sunat').rules('add', { required: true, messages: {  required: "Campo requerido" } });
  $('#tipo_documento').rules('add', { required: true, messages: {  required: "Campo requerido" } });

});
