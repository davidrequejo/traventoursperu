let tablaCuentasBancarias = null;

function puedeCuentaBancaria(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('cuentas_bancarias', accion);
}

$(function () {
  inicializarTablaCuentasBancarias();
  inicializarFormularioCuentaBancaria();
  inicializarFormularioBancoCuentaBancaria();
  cargarSelectores();

  $('#btn-nuevo-cuenta-bancaria').on('click', prepararNuevoCuentaBancaria);
  $('#btn-agregar-banco-cuenta').on('click', abrirModalBancoCuentaBancaria);
  $('#btn-guardar-banco-cuenta').on('click', function () {
    $('#form-banco-cuenta-bancaria').submit();
  });
  $('.guardar_cuenta_bancaria').on('click', function () {
    $('#form-cuenta-bancaria').submit();
  });
  $('#incluir-eliminados-cuenta-bancaria').on('change', recargarTablaCuentasBancarias);
  $('#btn-recargar-cuentas-bancarias').on('click', function () {
    tablaCuentasBancarias.ajax.reload(null, false);
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

function escapeHtmlCuentaBancaria(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function obtenerUrlIconoBancoCuentaBancaria(iconoNombre) {
  const nombre = String(iconoNombre || '').trim();
  if (nombre) {
    return apiUrl(`/assets/modulo/banco/icono/${encodeURIComponent(nombre)}`);
  }

  return apiUrl('/assets/modulo/banco/icono/logo-sin-banco.svg');
}

function plantillaOpcionBancoCuentaBancaria(state) {
  if (!state.id) {
    return state.text;
  }

  const icono = state.element ? $(state.element).data('icono') : '';
  const urlIcono = obtenerUrlIconoBancoCuentaBancaria(icono);
  const fallback = apiUrl('/ynex_admin/svg/empresa-logo.svg');
  const texto = escapeHtmlCuentaBancaria(state.text || '');

  return $(`
    <span class="d-flex align-items-center gap-2">
      <img src="${urlIcono}" alt="" style="width:22px;height:22px;object-fit:contain;border-radius:6px;border:1px solid #e5e7eb;padding:2px;background:#fff;" onerror="this.src='${fallback}';">
      <span>${texto}</span>
    </span>
  `);
}

function cargarSelectores() {
  lista_select2(apiUrl('/select2/select2banco'), '#cuenta_bancaria_banco');
  lista_select2(apiUrl('/select2/select2persona'), '#cuenta_bancaria_persona');

  $('#cuenta_bancaria_banco').select2({
    theme: 'bootstrap4',
    placeholder: 'Seleccione un banco',
    allowClear: true,
    dir: 'ltr',
    templateResult: plantillaOpcionBancoCuentaBancaria,
    templateSelection: plantillaOpcionBancoCuentaBancaria,
    escapeMarkup: function (markup) { return markup; },
    dropdownParent: $('#modal-nuevo-cuenta-bancaria')
  });

  $('#cuenta_bancaria_persona').select2({
    theme: 'bootstrap4',
    placeholder: 'Seleccione una persona',
    allowClear: true,
    dir: 'ltr',
    dropdownParent: $('#modal-nuevo-cuenta-bancaria')
  });

  $('#cuenta_bancaria_moneda').select2({
    theme: 'bootstrap4',
    placeholder: 'Seleccione moneda',
    allowClear: true,
    dir: 'ltr',
    dropdownParent: $('#modal-nuevo-cuenta-bancaria')
  });

  $('#cuenta_bancaria_tipo_cuenta').select2({
    theme: 'bootstrap4',
    placeholder: 'Seleccione tipo',
    allowClear: true,
    dir: 'ltr',
    dropdownParent: $('#modal-nuevo-cuenta-bancaria')
  });
}

function inicializarTablaCuentasBancarias() {
  tablaCuentasBancarias = $('#tabla-cuentas-bancarias').DataTable({
    responsive: true,
    processing: true,
    deferRender: true,
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
        exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7, 8, 9] },
        title: 'Lista de cuentas bancarias',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/cuentas-bancarias/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function (data) {
        data.incluir_trash = $('#incluir-eliminados-cuenta-bancaria').is(':checked') ? 1 : 0;
      },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarErrorCuentaBancaria(response.message || 'No se pudo cargar cuentas bancarias.');
          return [];
        }
        return response.data || [];
      },
      error: function () {
        mostrarErrorCuentaBancaria('Error al consultar cuentas bancarias.');
      },
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row, meta) {
          return meta.row + 1;
        }
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const acciones = [];

          if (puedeCuentaBancaria('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-cuenta-bancaria" data-id="${row.idcuenta_bancaria}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '1' && puedeCuentaBancaria('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-cuenta-bancaria" data-id="${row.idcuenta_bancaria}" data-bs-toggle="tooltip" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '0' && puedeCuentaBancaria('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-info btn-restaurar-cuenta-bancaria" data-id="${row.idcuenta_bancaria}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ri-refresh-line"></i>
              </button>
            `);
          }

          return acciones.join('');
        }
      },
      {
        data: 'banco.nombre',
        className: 'cuenta-bancaria-banco',
        render: function (data, _type, row) {
          return `${data} ${row.banco.alias ? `(${row.banco.alias})` : ''}`;
        }
      },
      {
        data: null,
        className: 'cuenta-bancaria-persona',
        render: function (_data, _type, row) {
          const persona = row.persona || {};
          return persona.descripcion ||
                 persona.nombre_persona_natural ||
                 persona.nombre_comercial ||
                 (persona.numero_documento ? `Doc: ${persona.numero_documento}` : '-');
        }
      },
      { data: 'cta_cte' },
      { data: 'cci' },
      {
        data: 'moneda',
        render: function (data) {
          return data === 'PEN' ? 'PEN - Soles' : data === 'USD' ? 'USD - Dólares' : data || '';
        }
      },
      {
        data: 'tipo_cuenta',
        render: function (data) {
          return data === 'AHORRO' ? 'Ahorro' : data === 'CORRIENTE' ? 'Corriente' : data || '';
        }
      },
      {
        data: 'estado_trash',
        className: 'text-center',
        render: function (data) {
          return String(data) === '1'
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Eliminado</span>';
        }
      },
      {
        data: 'updated_at',
        render: function (data) {
          return new Date(data).toLocaleString('es-ES');
        }
      }
    ],
    language: {
      //url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
    }
  });

  // Eventos de la tabla
  $('#tabla-cuentas-bancarias').on('click', '.btn-editar-cuenta-bancaria', function () {
    const id = $(this).data('id');
    editarCuentaBancaria(id);
  });

  $('#tabla-cuentas-bancarias').on('click', '.btn-eliminar-cuenta-bancaria', function () {
    const id = $(this).data('id');
    eliminarCuentaBancaria(id);
  });

  $('#tabla-cuentas-bancarias').on('click', '.btn-restaurar-cuenta-bancaria', function () {
    const id = $(this).data('id');
    restaurarCuentaBancaria(id);
  });
}

function inicializarFormularioCuentaBancaria() {
  $('#form-cuenta-bancaria').validate({
    ignore: '.select2-search__field, .select2-input, .select2-focusser',
    rules: {
      idbanco: { required: true },
      idpersona: { required: true },
      cta_cte: { maxlength: 45 },
      cci: { maxlength: 45 },
      moneda: { maxlength: 10 },
      tipo_cuenta: { maxlength: 20 },
    },
    messages: {
      idbanco: { required: 'Campo requerido.' },
      idpersona: { required: 'Campo requerido.' },
      cta_cte: { maxlength: 'Maximo {0} caracteres.' },
      cci: { maxlength: 'Maximo {0} caracteres.' },
      moneda: { maxlength: 'Maximo {0} caracteres.' },
      tipo_cuenta: { maxlength: 'Maximo {0} caracteres.' },
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
      marcarSelect2CuentaBancaria(element, true);
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid').addClass('is-valid');
      marcarSelect2CuentaBancaria(element, false);
    },
    submitHandler: function (form, event) {
      event.preventDefault();
      guardarCuentaBancaria(form);
    },
  });
}

function inicializarFormularioBancoCuentaBancaria() {
  $('#form-banco-cuenta-bancaria').validate({
    rules: {
      nombre: { required: true, minlength: 2, maxlength: 65 },
      alias: { maxlength: 65 },
      formato_cta: { maxlength: 50 },
      formato_cci: { maxlength: 50 },
    },
    messages: {
      nombre: {
        required: 'Campo requerido.',
        minlength: 'MINIMO {0} caracteres.',
        maxlength: 'MAXIMO {0} caracteres.',
      },
      alias: { maxlength: 'MAXIMO {0} caracteres.' },
      formato_cta: { maxlength: 'MAXIMO {0} caracteres.' },
      formato_cci: { maxlength: 'MAXIMO {0} caracteres.' },
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      error.insertAfter(element);
    },
    highlight: function (element) {
      $(element).addClass('is-invalid').removeClass('is-valid');
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid').addClass('is-valid');
    },
    submitHandler: function (form, event) {
      event.preventDefault();
      guardarBancoCuentaBancaria(form);
    },
  });
}

function marcarSelect2CuentaBancaria(element, invalid) {
  const $element = $(element);

  if (!$element.hasClass('select2-hidden-accessible')) {
    return;
  }

  $element.next('.select2-container')
    .find('.select2-selection')
    .toggleClass('is-invalid', invalid)
    .toggleClass('is-valid', !invalid);
}

function abrirModalBancoCuentaBancaria() {
  if (typeof puedePermiso === 'function' && !puedePermiso('catalogo_general', 'crear')) {
    mostrarErrorCuentaBancaria('No tienes permiso para crear bancos.');
    return;
  }

  limpiarFormularioBancoCuentaBancaria();
  $('#modal-banco-cuenta-bancaria').modal('show');
}

function guardarBancoCuentaBancaria(form) {
  if (typeof puedePermiso === 'function' && !puedePermiso('catalogo_general', 'crear')) {
    mostrarErrorCuentaBancaria('No tienes permiso para crear bancos.');
    return;
  }

  const $button = $('#btn-guardar-banco-cuenta');
  const buttonHtml = $button.html();
  const formData = new FormData(form);

  $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Guardando');

  $.ajax({
    url: apiUrl('/bancos/store'),
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status || !response.data) {
        mostrarErrorCuentaBancaria(response.message || 'No se pudo registrar el banco.');
        return;
      }

      const banco = response.data;
      $('#modal-banco-cuenta-bancaria').modal('hide');
      lista_select2(apiUrl('/select2/select2banco'), '#cuenta_bancaria_banco', banco.idbanco);
      mostrarExitoCuentaBancaria(response.message || 'Banco registrado correctamente.');
    },
    error: function (xhr) {
      mostrarErroresValidacionBancoCuentaBancaria(xhr);
    },
    complete: function () {
      $button.prop('disabled', false).html(buttonHtml);
    },
  });
}

function limpiarFormularioBancoCuentaBancaria() {
  const $form = $('#form-banco-cuenta-bancaria');
  $form[0]?.reset();
  $form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');

  if ($form.data('validator')) {
    $form.validate().resetForm();
  }
}

function mostrarErroresValidacionBancoCuentaBancaria(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const validator = $('#form-banco-cuenta-bancaria').data('validator');
    const normalizedErrors = {};

    Object.entries(errors).forEach(function ([key, value]) {
      normalizedErrors[key] = Array.isArray(value) ? value[0] : value;
    });

    if (validator) {
      validator.showErrors(normalizedErrors);
    }

    mostrarErrorCuentaBancaria(Object.values(normalizedErrors)[0] || 'Revise los campos del banco.');
    return;
  }

  mostrarErrorCuentaBancaria(response.message || 'Error al registrar el banco.');
}

function guardarCuentaBancaria(form) {
  const formData = new FormData(form);
  const id = formData.get('idcuenta_bancaria');
  const url = id ? apiUrl(`/cuentas-bancarias/${id}/update`) : apiUrl('/cuentas-bancarias/store');

  if (id) {
    formData.append('_method', 'PUT');
  }

  $.ajax({
    url: url,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    headers: ajaxHeaders(),
    success: function (response) {
      if (response.status) {
        $('#modal-nuevo-cuenta-bancaria').modal('hide');
        tablaCuentasBancarias.ajax.reload(null, false);
        mostrarExitoCuentaBancaria(response.message || 'Cuenta bancaria guardada correctamente.');
      } else {
        mostrarErrorCuentaBancaria(response.message || 'Error al guardar la cuenta bancaria.');
      }
    },
    error: function (xhr) {
      const response = xhr.responseJSON;
      if (response && response.errors) {
        mostrarErroresValidacionCuentaBancaria(response.errors);
      } else {
        mostrarErrorCuentaBancaria('Error al guardar la cuenta bancaria.');
      }
    }
  });
}

function prepararNuevoCuentaBancaria() {
  $('#modal-nuevo-cuenta-bancaria-label').text('Nueva Cuenta Bancaria');
  $('#cuenta_bancaria_id').val('');
  $('#form-cuenta-bancaria')[0].reset();
  $('#form-cuenta-bancaria').validate().resetForm();
  $('#form-cuenta-bancaria .is-invalid, #form-cuenta-bancaria .is-valid').removeClass('is-invalid is-valid');
  $('#form-cuenta-bancaria .select2-selection').removeClass('is-invalid is-valid');
  $('#cuenta_bancaria_banco, #cuenta_bancaria_persona, #cuenta_bancaria_moneda, #cuenta_bancaria_tipo_cuenta').val(null).trigger('change');
  $('#modal-nuevo-cuenta-bancaria').modal('show');
}

function editarCuentaBancaria(id) {
  $.ajax({
    url: apiUrl(`/cuentas-bancarias/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (response.status && response.data) {
        const cuenta = response.data;
        $('#modal-nuevo-cuenta-bancaria-label').text('Editar Cuenta Bancaria');
        $('#cuenta_bancaria_id').val(cuenta.idcuenta_bancaria);
        $('#cuenta_bancaria_banco').val(cuenta.idbanco).trigger('change');
        $('#cuenta_bancaria_persona').val(cuenta.idpersona).trigger('change');
        $('#cuenta_bancaria_cta_cte').val(cuenta.cta_cte);
        $('#cuenta_bancaria_cci').val(cuenta.cci);
        $('#cuenta_bancaria_moneda').val(cuenta.moneda).trigger('change');
        $('#cuenta_bancaria_tipo_cuenta').val(cuenta.tipo_cuenta).trigger('change');
        $('#modal-nuevo-cuenta-bancaria').modal('show');
      } else {
        mostrarErrorCuentaBancaria('No se pudo cargar la cuenta bancaria.');
      }
    },
    error: function () {
      mostrarErrorCuentaBancaria('Error al cargar la cuenta bancaria.');
    }
  });
}

function eliminarCuentaBancaria(id) {
  if (!confirm('¿Está seguro de eliminar esta cuenta bancaria?')) {
    return;
  }

  $.ajax({
    url: apiUrl(`/cuentas-bancarias/${id}`),
    type: 'DELETE',
    headers: ajaxHeaders(),
    success: function (response) {
      if (response.status) {
        tablaCuentasBancarias.ajax.reload(null, false);
        mostrarExitoCuentaBancaria(response.message || 'Cuenta bancaria eliminada correctamente.');
      } else {
        mostrarErrorCuentaBancaria(response.message || 'Error al eliminar la cuenta bancaria.');
      }
    },
    error: function () {
      mostrarErrorCuentaBancaria('Error al eliminar la cuenta bancaria.');
    }
  });
}

function restaurarCuentaBancaria(id) {
  if (!confirm('¿Está seguro de restaurar esta cuenta bancaria?')) {
    return;
  }

  $.ajax({
    url: apiUrl(`/cuentas-bancarias/${id}/restore`),
    type: 'POST',
    headers: ajaxHeaders(),
    success: function (response) {
      if (response.status) {
        tablaCuentasBancarias.ajax.reload(null, false);
        mostrarExitoCuentaBancaria(response.message || 'Cuenta bancaria restaurada correctamente.');
      } else {
        mostrarErrorCuentaBancaria(response.message || 'Error al restaurar la cuenta bancaria.');
      }
    },
    error: function () {
      mostrarErrorCuentaBancaria('Error al restaurar la cuenta bancaria.');
    }
  });
}

function recargarTablaCuentasBancarias() {
  tablaCuentasBancarias.ajax.reload(null, false);
}

function mostrarExitoCuentaBancaria(mensaje) {
  // Implementar notificación de éxito
  alert(mensaje);
}

function mostrarErrorCuentaBancaria(mensaje) {
  // Implementar notificación de error
  alert(mensaje);
}

function mostrarErroresValidacionCuentaBancaria(errores) {
  let mensaje = 'Errores de validación:\n';
  for (const campo in errores) {
    mensaje += `- ${errores[campo].join(', ')}\n`;
  }
  alert(mensaje);
}
// SweetAlert2 overrides for cuenta bancaria notifications and confirmations.
eliminarCuentaBancaria = function (id) {
  confirmarCuentaBancaria(
    'Eliminar cuenta bancaria',
    'Esta cuenta bancaria se enviara a la papelera.',
    'Si, eliminar',
    'warning'
  ).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    $.ajax({
      url: apiUrl(`/cuentas-bancarias/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (response.status) {
          tablaCuentasBancarias.ajax.reload(null, false);
          mostrarExitoCuentaBancaria(response.message || 'Cuenta bancaria eliminada correctamente.');
        } else {
          mostrarErrorCuentaBancaria(response.message || 'Error al eliminar la cuenta bancaria.');
        }
      },
      error: function () {
        mostrarErrorCuentaBancaria('Error al eliminar la cuenta bancaria.');
      }
    });
  });
};

restaurarCuentaBancaria = function (id) {
  confirmarCuentaBancaria(
    'Restaurar cuenta bancaria',
    'Esta cuenta bancaria volvera a estar activa.',
    'Si, restaurar',
    'question'
  ).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    $.ajax({
      url: apiUrl(`/cuentas-bancarias/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (response.status) {
          tablaCuentasBancarias.ajax.reload(null, false);
          mostrarExitoCuentaBancaria(response.message || 'Cuenta bancaria restaurada correctamente.');
        } else {
          mostrarErrorCuentaBancaria(response.message || 'Error al restaurar la cuenta bancaria.');
        }
      },
      error: function () {
        mostrarErrorCuentaBancaria('Error al restaurar la cuenta bancaria.');
      }
    });
  });
};

mostrarExitoCuentaBancaria = function (mensaje) {
  if (typeof Swal === 'undefined') {
    alert(mensaje);
    return;
  }

  Swal.fire({
    title: 'Exito',
    text: mensaje,
    icon: 'success',
    timer: 1800,
    showConfirmButton: false,
  });
};

mostrarErrorCuentaBancaria = function (mensaje) {
  if (typeof Swal === 'undefined') {
    alert(mensaje);
    return;
  }

  Swal.fire({
    title: 'Error',
    text: mensaje,
    icon: 'error',
    confirmButtonText: 'Entendido',
  });
};

mostrarErroresValidacionCuentaBancaria = function (errores) {
  let mensaje = '';
  for (const campo in errores) {
    mensaje += `- ${errores[campo].join(', ')}\n`;
  }

  if (typeof Swal === 'undefined') {
    alert(`Errores de validacion:\n${mensaje}`);
    return;
  }

  Swal.fire({
    title: 'Revise los campos',
    text: mensaje || 'Hay errores de validacion.',
    icon: 'warning',
    confirmButtonText: 'Corregir',
  });
};

function confirmarCuentaBancaria(title, text, confirmButtonText, icon = 'warning') {
  if (typeof Swal === 'undefined') {
    return Promise.resolve({ isConfirmed: confirm(text) });
  }

  return Swal.fire({
    title,
    text,
    icon,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  });
}
