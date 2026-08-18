let perfilAuthUserId = null;
let perfilTwoFactorPending = false;

$(function () {
  perfilAuthUserId = Number($('#body-perfil').data('auth-user-id') || 0) || null;

  cargarPerfilUsuario();
  cargarSesionesPerfil();
});

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

function cargarPerfilUsuario() {
  $.ajax({
    url: apiUrl(`/usuarios/${perfilAuthUserId}`),
    type: 'GET',
    headers: { 'X-CSRF-TOKEN': csrf() },
    success: function (response) {
      if (!response.status || !response.data) {
        mostrarPerfilError('No se pudo cargar tu perfil.');
        return;
      }

      renderPerfilResumen(response.data);
      renderPerfil2FA(response.data);
    },
    error: function (xhr) {
      mostrarPerfilError(obtenerMensajeAjaxError(xhr, 'Error al cargar perfil.'));
    },
  });
}

function renderPerfilResumen(usuario) {
  const activo = tiene2FAActivo(usuario);

  $('#perfil-nombre').text(usuario.persona?.descripcion || usuario.name || 'Usuario');
  $('#perfil-email').text(usuario.email || '');
  $('#perfil-foto').attr('src', usuario.profile_photo_url || apiUrl('/assets/modulo/persona/perfil/hombre.png'));
  $('#perfil-actualizado').text(formatearFechaPerfil(usuario.updated_at));
  $('#perfil-estado-2fa')
    .removeClass('bg-light text-dark bg-success bg-warning text-white')
    .addClass(activo ? 'bg-success' : 'bg-warning text-white')
    .text(activo ? '2FA activo' : '2FA pendiente');
  $('#perfil-2fa-resumen').text(activo ? 'Activo' : 'No');
  $('#perfil-2fa-badge')
    .removeClass('bg-secondary-transparent bg-success-transparent bg-warning-transparent')
    .addClass(activo ? 'bg-success-transparent' : 'bg-warning-transparent')
    .text(activo ? 'Activo' : 'No configurado');
}

function renderPerfil2FA(usuario) {
  if (tiene2FAActivo(usuario)) {
    renderPerfil2FAActivo();
    return;
  }

  renderPerfil2FAPassword();
}

function renderPerfil2FAPassword() {
  perfilTwoFactorPending = false;

  $('#perfil-2fa-body').html(`
    <div class="p-4 border-bottom border-block-end-dashed">
      <div class="d-flex align-items-start">
        <span class="avatar avatar-lg avatar-rounded bg-primary-transparent text-primary me-3">
          <i class="ri-shield-keyhole-line fs-24"></i>
        </span>
        <div>
          <p class="fs-15 mb-1 fw-semibold">Protege tu cuenta con 2FA</p>
          <p class="text-muted mb-0">Confirma tu contraseña para generar el QR de configuración.</p>
        </div>
      </div>
    </div>
    <div class="p-4">
      <div class="row gy-3 align-items-end">
        <div class="col-lg-8">
          <label for="perfil_two_factor_password" class="form-label">Contraseña actual</label>
          <div class="input-group">
            <input type="password" class="form-control" id="perfil_two_factor_password" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" autocapitalize="off" spellcheck="false">
            <button type="button" class="btn btn-light border perfil-toggle-password" data-target="#perfil_two_factor_password" aria-label="Mostrar contraseña">
              <i class="ri-eye-line"></i>
            </button>
          </div>
          <div class="invalid-feedback" id="perfil_two_factor_password_error"></div>
        </div>
        <div class="col-lg-4 text-lg-end">
          <button type="button" class="btn btn-primary btn-wave w-100" id="perfil-btn-2fa-generar">
            <i class="ri-qr-code-line me-1"></i> Generar QR
          </button>
        </div>
      </div>
    </div>
  `);
}

$(document).on('click', '#perfil-btn-2fa-generar', function () {
  const $button = $(this);
  const password = $('#perfil_two_factor_password').val();

  if (!password) {
    mostrarErrorCampoPerfil2FA('Ingrese su contraseña actual.');
    return;
  }

  cambiarEstadoBoton($button, true, 'Generando...');
  confirmarPassword2FA(password)
    .then(habilitar2FA)
    .then(cargarDatosConfiguracion2FA)
    .done(renderPerfil2FAConfirmacion)
    .fail(function (xhr) {
      mostrarErrorCampoPerfil2FA(obtenerMensajeValidacion2FA(xhr, 'No se pudo activar 2FA.'));
    })
    .always(function () {
      cambiarEstadoBoton($button, false, '<i class="ri-qr-code-line me-1"></i> Generar QR');
    });
});

function renderPerfil2FAConfirmacion(data) {
  perfilTwoFactorPending = true;

  $('#perfil-2fa-body').html(`
    <div class="p-4 border-bottom border-block-end-dashed">
      <p class="fs-15 mb-1 fw-semibold">Escanea el código QR</p>
      <p class="text-muted mb-0">Usa una app autenticadora y confirma el código generado.</p>
    </div>
    <div class="p-4 border-bottom border-block-end-dashed">
      <div class="row g-4 align-items-center">
        <div class="col-md-5 text-center">
          <div class="d-inline-block bg-white border rounded p-2">${data.qr.svg || ''}</div>
        </div>
        <div class="col-md-7">
          <p class="fs-13 text-muted mb-2">Clave manual:</p>
          <div class="alert alert-light border text-break mb-0">
            <code>${escapeHtml(data.secret.secretKey || '')}</code>
          </div>
        </div>
      </div>
    </div>
    <div class="p-4">
      <div class="row gy-3 align-items-end">
        <div class="col-lg-7">
          <label for="perfil_two_factor_code" class="form-label">Código de verificación</label>
          <input type="text" class="form-control" id="perfil_two_factor_code" inputmode="numeric" autocomplete="one-time-code" placeholder="000000">
          <div class="invalid-feedback" id="perfil_two_factor_code_error"></div>
        </div>
        <div class="col-lg-5">
          <div class="btn-list d-grid gap-2 d-sm-flex">
            <button type="button" class="btn btn-light btn-wave" id="perfil-btn-2fa-cancelar">Cancelar</button>
            <button type="button" class="btn btn-primary btn-wave" id="perfil-btn-2fa-confirmar">
              <i class="ri-shield-check-line me-1"></i> Confirmar
            </button>
          </div>
        </div>
      </div>
    </div>
  `);

  $('#perfil_two_factor_code').trigger('focus');
}

$(document).on('click', '#perfil-btn-2fa-confirmar', function () {
  const $button = $(this);
  const code = $('#perfil_two_factor_code').val();

  if (!code) {
    mostrarErrorCodigoPerfil2FA('Ingrese el código generado por su aplicación autenticadora.');
    return;
  }

  cambiarEstadoBoton($button, true, 'Confirmando...');

  $.ajax({
    url: apiUrl('/user/confirmed-two-factor-authentication'),
    type: 'POST',
    headers: fortifyHeaders(),
    data: { code },
    success: function () {
      perfilTwoFactorPending = false;
      cargarCodigosRecuperacion2FA()
        .then(renderPerfil2FACompletado)
        .always(function () {
          cargarPerfilUsuario();
        });
    },
    error: function (xhr) {
      mostrarErrorCodigoPerfil2FA(obtenerMensajeValidacion2FA(xhr, 'El código ingresado no es válido.'));
    },
    complete: function () {
      cambiarEstadoBoton($button, false, '<i class="ri-shield-check-line me-1"></i> Confirmar');
    },
  });
});

$(document).on('click', '#perfil-btn-2fa-cancelar', function () {
  deshabilitar2FA().always(function () {
    perfilTwoFactorPending = false;
    cargarPerfilUsuario();
  });
});

$(document).on('click', '#perfil-btn-2fa-recargar', function () {
  cargarPerfilUsuario();
});

function renderPerfil2FACompletado(codes) {
  const codigos = Array.isArray(codes) ? codes : [];
  const codigosHtml = codigos.map((code) => `<div>${escapeHtml(code)}</div>`).join('');

  $('#perfil-2fa-body').html(`
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
      <button type="button" class="btn btn-primary btn-wave" id="perfil-btn-2fa-recargar">
        <i class="ri-refresh-line me-1"></i> Actualizar estado
      </button>
    </div>
  `);

  mostrarOk('Autenticación de dos factores activada correctamente.');
}

function renderPerfil2FAActivo() {
  $('#perfil-2fa-body').html(`
    <div class="p-4 border-bottom border-block-end-dashed">
      <div class="d-flex align-items-start">
        <span class="avatar avatar-lg avatar-rounded bg-success-transparent text-success me-3">
          <i class="ri-shield-check-line fs-24"></i>
        </span>
        <div>
          <p class="fs-15 mb-1 fw-semibold">2FA está activo</p>
          <p class="text-muted mb-0">Tu cuenta solicita un segundo paso de verificación al iniciar sesión.</p>
        </div>
      </div>
    </div>
    <div class="p-4 border-bottom border-block-end-dashed">
      <div class="row gy-3 align-items-end">
        <div class="col-lg-8">
          <label for="perfil_two_factor_disable_password" class="form-label">Contraseña actual</label>
          <div class="input-group">
            <input type="password" class="form-control" id="perfil_two_factor_disable_password" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');" autocapitalize="off" spellcheck="false">
            <button type="button" class="btn btn-light border perfil-toggle-password" data-target="#perfil_two_factor_disable_password" aria-label="Mostrar contraseña">
              <i class="ri-eye-line"></i>
            </button>
          </div>
          <div class="invalid-feedback" id="perfil_two_factor_disable_password_error"></div>
        </div>
        <div class="col-lg-4 text-lg-end">
          <button type="button" class="btn btn-danger btn-wave w-100" id="perfil-btn-2fa-desactivar">
            <i class="ri-shield-cross-line me-1"></i> Desactivar 2FA
          </button>
        </div>
      </div>
    </div>
    <div class="p-4">
      <button type="button" class="btn btn-secondary-light btn-wave" id="perfil-btn-codigos-recuperacion">
        <i class="ri-key-2-line me-1"></i> Ver códigos de recuperación
      </button>
    </div>
  `);
}

$(document).on('click', '#perfil-btn-2fa-desactivar', function () {
  const $button = $(this);
  const password = $('#perfil_two_factor_disable_password').val();

  if (!password) {
    mostrarErrorPasswordDesactivarPerfil2FA('Ingrese su contraseña actual.');
    return;
  }

  confirmarAccionPerfil('Desactivar 2FA', 'Tu cuenta volverá a iniciar sesión solo con correo y contraseña.', function () {
    cambiarEstadoBoton($button, true, 'Desactivando...');
    confirmarPassword2FA(password)
      .then(deshabilitar2FA)
      .done(function () {
        mostrarOk('Autenticación de dos factores desactivada correctamente.');
        cargarPerfilUsuario();
      })
      .fail(function (xhr) {
        mostrarErrorPasswordDesactivarPerfil2FA(obtenerMensajeValidacion2FA(xhr, 'No se pudo desactivar 2FA.'));
      })
      .always(function () {
        cambiarEstadoBoton($button, false, '<i class="ri-shield-cross-line me-1"></i> Desactivar 2FA');
      });
  });
});

$(document).on('click', '.perfil-toggle-password', function () {
  const $button = $(this);
  const $input = $($button.data('target'));
  const visible = $input.attr('type') === 'text';

  $input.attr('type', visible ? 'password' : 'text');
  $button.attr('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
  $button.find('i').toggleClass('ri-eye-line', visible).toggleClass('ri-eye-off-line', !visible);
});

$(document).on('click', '#perfil-btn-codigos-recuperacion', function () {
  const $button = $(this);

  cambiarEstadoBoton($button, true, 'Cargando...');
  cargarCodigosRecuperacion2FA()
    .done(renderPerfilCodigosRecuperacion)
    .fail(function (xhr) {
      mostrarError(obtenerMensajeAjaxError(xhr, 'No se pudieron cargar los códigos.'));
    })
    .always(function () {
      cambiarEstadoBoton($button, false, '<i class="ri-key-2-line me-1"></i> Ver códigos de recuperación');
    });
});

function renderPerfilCodigosRecuperacion(codes) {
  const codigos = Array.isArray(codes) ? codes : [];
  const codigosHtml = codigos.map((code) => `<div>${escapeHtml(code)}</div>`).join('');

  Swal.fire({
    title: 'Códigos de recuperación',
    html: `
      <div class="text-start">
        <p class="text-muted">Guárdalos en un lugar seguro.</p>
        <div class="bg-light rounded p-3 font-monospace fs-13">${codigosHtml}</div>
      </div>
    `,
    icon: 'info',
    confirmButtonText: 'Entendido',
  });
}

function cargarSesionesPerfil() {
  $.ajax({
    url: apiUrl(`/usuarios/${perfilAuthUserId}/sesiones`),
    type: 'GET',
    headers: { 'X-CSRF-TOKEN': csrf() },
    success: function (response) {
      if (!response.status || !response.data) {
        mostrarError(response.message || 'No se pudo cargar sesiones.');
        return;
      }

      renderSesionesPerfil(response.data.sessions || []);
    },
    error: function (xhr) {
      mostrarError(obtenerMensajeAjaxError(xhr, 'Error al consultar sesiones activas.'));
    },
  });
}

function renderSesionesPerfil(sessions) {
  $('#perfil-sesiones-total').text(sessions.length);
  $('#perfil-cerrar-otras-sesiones').prop('disabled', sessions.filter((session) => !session.is_current_device).length === 0);

  if (!sessions.length) {
    $('#perfil-sesiones-body').html(`
      <div class="text-center text-muted py-4 border rounded">
        <i class="ri-computer-line fs-24 d-block mb-1"></i>
        No hay sesiones activas.
      </div>
    `);
    return;
  }

  $('#perfil-sesiones-body').html(`
    <div class="row g-3">
      ${sessions.map(renderSesionPerfilCard).join('')}
    </div>
  `);
}

function renderSesionPerfilCard(session) {
  const icon = session.is_desktop ? 'ri-computer-line' : 'ri-smartphone-line';
  const disabled = session.is_current_device ? 'disabled' : '';

  return `
    <div class="col-xl-6">
      <div class="card custom-card shadow-none border mb-0 h-100">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div class="d-flex align-items-start">
              <span class="avatar avatar-md avatar-rounded bg-light text-muted me-3">
                <i class="${icon} fs-20"></i>
              </span>
              <div>
                <p class="mb-1 fw-semibold">${escapeHtml(session.platform)} - ${escapeHtml(session.browser)}</p>
                <p class="mb-1 fs-12 text-muted"><i class="ri-map-pin-line me-1"></i>${escapeHtml(session.ip_address || 'IP desconocida')}</p>
                <p class="mb-0 fs-12 text-muted"><i class="ri-time-line me-1"></i>${escapeHtml(session.last_active || '-')}</p>
              </div>
            </div>
            ${session.is_current_device ? '<span class="badge bg-success-transparent">Este dispositivo</span>' : ''}
          </div>
        </div>
        <div class="card-footer border-top border-block-start-dashed text-end">
          <button type="button" class="btn btn-sm btn-danger-light btn-wave perfil-cerrar-sesion" data-session-id="${escapeHtml(session.id)}" ${disabled}>
            <i class="ri-logout-box-r-line me-1"></i>Cerrar
          </button>
        </div>
      </div>
    </div>
  `;
}

$(document).on('click', '.perfil-cerrar-sesion', function () {
  const $button = $(this);
  const sessionId = $button.data('session-id');

  if (!sessionId || $button.prop('disabled')) {
    return;
  }

  confirmarAccionPerfil('Cerrar sesión', 'Esta sesión se cerrará inmediatamente.', function () {
    cambiarEstadoBoton($button, true, 'Cerrando...');

    $.ajax({
      url: apiUrl(`/usuarios/${perfilAuthUserId}/sesiones/${sessionId}`),
      type: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf() },
      success: function (response) {
        mostrarOk(response.message || 'Sesión cerrada correctamente.');
        cargarSesionesPerfil();
      },
      error: function (xhr) {
        mostrarError(obtenerMensajeAjaxError(xhr, 'No se pudo cerrar la sesión.'));
      },
    });
  });
});

$(document).on('click', '#perfil-cerrar-otras-sesiones', function () {
  const $button = $(this);

  if ($button.prop('disabled')) {
    return;
  }

  confirmarAccionPerfil('Cerrar otras sesiones', 'Tu sesión actual permanecerá activa.', function () {
    cambiarEstadoBoton($button, true, 'Cerrando...');

    $.ajax({
      url: apiUrl(`/usuarios/${perfilAuthUserId}/sesiones`),
      type: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf() },
      success: function (response) {
        mostrarOk(response.message || 'Sesiones cerradas correctamente.');
        cargarSesionesPerfil();
      },
      error: function (xhr) {
        mostrarError(obtenerMensajeAjaxError(xhr, 'No se pudieron cerrar las sesiones.'));
      },
      complete: function () {
        cambiarEstadoBoton($button, false, '<i class="ri-logout-box-r-line me-1"></i>Cerrar otras sesiones');
      },
    });
  });
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

function deshabilitar2FA() {
  return $.ajax({
    url: apiUrl('/user/two-factor-authentication'),
    type: 'DELETE',
    headers: fortifyHeaders(),
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

function cargarCodigosRecuperacion2FA() {
  return $.ajax({
    url: apiUrl('/user/two-factor-recovery-codes'),
    type: 'GET',
    headers: fortifyHeaders(),
  });
}

function confirmarAccionPerfil(title, message, callback) {
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

function mostrarErrorCampoPerfil2FA(message) {
  $('#perfil_two_factor_password').addClass('is-invalid').removeClass('is-valid');
  $('#perfil_two_factor_password_error').addClass('d-block').text(message);
}

function mostrarErrorCodigoPerfil2FA(message) {
  $('#perfil_two_factor_code').addClass('is-invalid').removeClass('is-valid');
  $('#perfil_two_factor_code_error').text(message);
}

function mostrarErrorPasswordDesactivarPerfil2FA(message) {
  $('#perfil_two_factor_disable_password').addClass('is-invalid').removeClass('is-valid');
  $('#perfil_two_factor_disable_password_error').addClass('d-block').text(message);
}

function obtenerMensajeValidacion2FA(xhr, fallback) {
  const response = xhr?.responseJSON || {};
  const errors = response.errors || {};

  return errors.password?.[0]
    || errors.code?.[0]
    || response.message
    || fallback;
}

function obtenerMensajeAjaxError(xhr, fallback) {
  const response = xhr?.responseJSON || {};

  return response.message || fallback;
}

function cambiarEstadoBoton($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function tiene2FAActivo(usuario) {
  return Boolean(usuario?.two_factor_confirmed_at);
}

function formatearFechaPerfil(value) {
  if (!value) {
    return '-';
  }

  if (typeof moment !== 'undefined') {
    return moment(value).format('DD/MM/YYYY HH:mm');
  }

  return value;
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

function mostrarPerfilError(message) {
  $('#perfil-2fa-body').html(`
    <div class="text-center text-danger py-5">
      <i class="ri-error-warning-line fs-24 d-block mb-1"></i>
      ${escapeHtml(message)}
    </div>
  `);
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
