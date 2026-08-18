(function (window, document) {
  function normalizar(valor) {
    return String(valor || '').trim().toLowerCase();
  }

  function obtenerPermiso(modulo) {
    const permisos = window.AppPermisos || {};
    return permisos[normalizar(modulo)] || null;
  }

  window.puedePermiso = function (modulo, accion = 'ver') {
    const modulos = Array.isArray(modulo) ? modulo : String(modulo || '').split('|');
    const accionNormalizada = normalizar(accion || 'ver');

    return modulos.some(function (item) {
      const permiso = obtenerPermiso(item);
      return Boolean(permiso && permiso[accionNormalizada] === true);
    });
  };

  window.aplicarPermisosVista = function () {
    document.querySelectorAll('[data-permiso-modulo][data-permiso-accion]').forEach(function (elemento) {
      const modulo = elemento.getAttribute('data-permiso-modulo');
      const accion = elemento.getAttribute('data-permiso-accion');

      if (!window.puedePermiso(modulo, accion)) {
        elemento.remove();
      }
    });
  };

  document.addEventListener('DOMContentLoaded', function () {
    window.aplicarPermisosVista();
  });
})(window, document);
