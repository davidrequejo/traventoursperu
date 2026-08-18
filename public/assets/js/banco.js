let tablaBancos = null;

function puedeBanco(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('catalogo_general', accion);
}

$(function () {
  inicializarTablaBancos();
  inicializarFormularioBanco();

  $('#btn-nuevo-banco').on('click', prepararNuevoBanco);
  $('.guardar_banco').on('click', function () {
    $('#form-banco').submit();
  });
  $('#incluir-eliminados-banco').on('change', recargarTablaBancos);
  $('#banco_icono_file').on('change', previsualizarIconoBanco);
  $('#btn-remover-icono-banco').on('click', removerIconoBanco);
  $('#modal-nuevo-banco').on('hidden.bs.modal', limpiarFormularioBanco);
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

function inicializarTablaBancos() {
  tablaBancos = $('#tabla-bancos').DataTable({
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
        exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7] },
        title: 'Lista de bancos',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/bancos/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function (data) {
        data.incluir_trash = $('#incluir-eliminados-banco').is(':checked') ? 1 : 0;
      },
      dataSrc: function (response) {
        if (!response.status) {
          mostrarErrorBanco(response.message || 'No se pudo cargar bancos.');
          return [];
        }

        return response.data || [];
      },
      error: function () {
        mostrarErrorBanco('Error al consultar bancos.');
      },
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const acciones = [];

          if (puedeBanco('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-banco" data-id="${row.idbanco}" data-bs-toggle="tooltip" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '1' && puedeBanco('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-banco" data-id="${row.idbanco}" data-bs-toggle="tooltip" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '0' && puedeBanco('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-banco" data-id="${row.idbanco}" data-bs-toggle="tooltip" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      {
        data: null,
        render: function (_data, _type, row) {
          const icono = row.icono
            ? apiUrl(`/assets/modulo/banco/icono/${row.icono}`)
            : apiUrl('/ynex_admin/svg/empresa-logo.svg');

          return `
            <div class="d-flex align-items-center">
              <span class="avatar avatar-md me-2 bg-light">
                <img src="${icono}" alt="" style="object-fit:contain;" onerror="this.src='${apiUrl('/ynex_admin/svg/empresa-logo.svg')}';">
              </span>
              <div>
                <p class="fw-semibold mb-0">${escapeHtmlBanco(row.nombre || 'Sin nombre')}</p>
                <span class="text-muted fs-12">${escapeHtmlBanco(row.alias || 'Sin alias')}</span>
              </div>
            </div>
          `;
        },
      },
      {
        data: 'alias',
        render: function (data) {
          return escapeHtmlBanco(data || '-');
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

  $('#tabla-bancos tbody').on('click', '.btn-editar-banco', function () {
    editarBanco($(this).data('id'));
  });

  $('#tabla-bancos tbody').on('click', '.btn-eliminar-banco', function () {
    eliminarBanco($(this).data('id'));
  });

  $('#tabla-bancos tbody').on('click', '.btn-restaurar-banco', function () {
    restaurarBanco($(this).data('id'));
  });
}

function inicializarFormularioBanco() {
  if ($.validator && !$.validator.methods.filesizeBanco) {
    $.validator.addMethod('filesizeBanco', function (value, element, maxSize) {
      if (!element.files || !element.files.length) return true;
      return element.files[0].size <= maxSize;
    }, 'El archivo supera el tamano permitido.');
  }

  if ($.validator && !$.validator.methods.imagenBanco) {
    $.validator.addMethod('imagenBanco', function (value, element) {
      if (!element.files || !element.files.length) return true;
      const nombre = element.files[0].name || '';
      return /\.(jpe?g|png|webp|gif|svg)$/i.test(nombre);
    }, 'Seleccione una imagen valida.');
  }

  $('#form-banco').validate({
    ignore: [],
    rules: {
      nombre: { required: true, minlength: 2, maxlength: 65 },
      alias: { maxlength: 65 },
      formato_cta: { maxlength: 50 },
      formato_cci: { maxlength: 50 },
      formato_detracciones: { maxlength: 50 },
      icono_file: { imagenBanco: true, filesizeBanco: 2048 * 1024 },
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
      formato_detracciones: { maxlength: 'MAXIMO {0} caracteres.' },
      icono_file: {
        imagenBanco: 'Seleccione una imagen valida.',
        filesizeBanco: 'La imagen no debe superar 2 MB.',
      },
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
      guardarBanco(form);
    },
  });
}

function prepararNuevoBanco(event) {
  if (!puedeBanco('crear')) {
    event?.preventDefault();
    mostrarErrorBanco('No tienes permiso para crear bancos.');
    return;
  }

  limpiarFormularioBanco();
  $('#modal-nuevo-banco-label').text('Nuevo Banco');
  $('.guardar_banco').html('<i class="ti ti-device-floppy"></i> Guardar');
}

function guardarBanco(form) {
  const id = $('#banco_idbanco').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedeBanco('editar')) {
    mostrarErrorBanco('No tienes permiso para editar bancos.');
    return;
  }

  if (!esEdicion && !puedeBanco('crear')) {
    mostrarErrorBanco('No tienes permiso para crear bancos.');
    return;
  }

  const formData = new FormData(form);
  const url = esEdicion ? apiUrl(`/bancos/${id}/update`) : apiUrl('/bancos/store');
  const finalButtonHtml = esEdicion
    ? '<i class="ti ti-device-floppy"></i> Actualizar'
    : '<i class="ti ti-device-floppy"></i> Guardar';

  if (esEdicion) {
    formData.append('_method', 'PUT');
  }

  cambiarEstadoBotonBanco($('.guardar_banco'), true, 'Guardando...');

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarErrorBanco(response.message || 'No se pudo guardar el banco.');
        return;
      }

      mostrarOkBanco(response.message || 'Banco guardado correctamente.');
      $('#modal-nuevo-banco').modal('hide');
      recargarTablaBancos();
    },
    error: function (xhr) {
      mostrarErroresValidacionBanco(xhr);
    },
    complete: function () {
      cambiarEstadoBotonBanco($('.guardar_banco'), false, finalButtonHtml);
    },
  });
}

function editarBanco(id) {
  if (!puedeBanco('editar')) {
    mostrarErrorBanco('No tienes permiso para editar bancos.');
    return;
  }

  $.ajax({
    url: apiUrl(`/bancos/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarErrorBanco(response.message || 'No se pudo cargar el banco.');
        return;
      }

      cargarBancoEnFormulario(response.data);
      $('#modal-nuevo-banco').modal('show');
    },
    error: function () {
      mostrarErrorBanco('Error al cargar banco.');
    },
  });
}

function cargarBancoEnFormulario(data) {
  limpiarFormularioBanco();
  $('#modal-nuevo-banco-label').text('Editar Banco');
  $('.guardar_banco').html('<i class="ti ti-device-floppy"></i> Actualizar');

  $('#banco_idbanco').val(data.idbanco || '');
  $('#banco_nombre').val(data.nombre || '');
  $('#banco_alias').val(data.alias || '');
  $('#banco_formato_cta').val(data.formato_cta || '');
  $('#banco_formato_cci').val(data.formato_cci || '');
  $('#banco_formato_detracciones').val(data.formato_detracciones || '');
  $('#banco_icono_actual').val(data.icono || '');

  if (data.icono) {
    $('#banco_icono_preview').attr('src', apiUrl(`/assets/modulo/banco/icono/${data.icono}`));
  }
}

function eliminarBanco(id) {
  if (!puedeBanco('eliminar')) {
    mostrarErrorBanco('No tienes permiso para eliminar bancos.');
    return;
  }

  confirmarAccionBanco('Eliminar banco', 'El banco se enviara a papelera.', 'Si, eliminar', function () {
    $.ajax({
      url: apiUrl(`/bancos/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorBanco(response.message || 'No se pudo eliminar el banco.');
          return;
        }

        mostrarOkBanco(response.message || 'Banco eliminado correctamente.');
        recargarTablaBancos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorBanco(response.message || 'Error al eliminar banco.');
      },
    });
  });
}

function restaurarBanco(id) {
  if (!puedeBanco('editar')) {
    mostrarErrorBanco('No tienes permiso para restaurar bancos.');
    return;
  }

  confirmarAccionBanco('Restaurar banco', 'El banco volvera a estar activo.', 'Si, restaurar', function () {
    $.ajax({
      url: apiUrl(`/bancos/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response.status) {
          mostrarErrorBanco(response.message || 'No se pudo restaurar el banco.');
          return;
        }

        mostrarOkBanco(response.message || 'Banco restaurado correctamente.');
        recargarTablaBancos();
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        mostrarErrorBanco(response.message || 'Error al restaurar banco.');
      },
    });
  });
}

function limpiarFormularioBanco() {
  const form = $('#form-banco');
  form[0]?.reset();
  $('#banco_idbanco, #banco_icono_actual').val('');
  $('#banco_icono_preview').attr('src', apiUrl('/ynex_admin/svg/empresa-logo.svg'));
  form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');

  if (form.data('validator')) {
    form.validate().resetForm();
  }
}

function previsualizarIconoBanco() {
  const input = this;
  if (!input.files || !input.files[0]) return;

  const reader = new FileReader();
  reader.onload = function (event) {
    $('#banco_icono_preview').attr('src', event.target.result);
  };
  reader.readAsDataURL(input.files[0]);
}

function removerIconoBanco() {
  $('#banco_icono_file').val('');
  $('#banco_icono_actual').val('');
  $('#banco_icono_preview').attr('src', apiUrl('/ynex_admin/svg/empresa-logo.svg'));
}

function recargarTablaBancos() {
  tablaBancos?.ajax.reload(null, false);
}

function mostrarErroresValidacionBanco(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const validator = $('#form-banco').data('validator');
    const normalizedErrors = {};

    Object.entries(errors).forEach(function ([key, value]) {
      normalizedErrors[key] = Array.isArray(value) ? value[0] : value;
    });

    if (validator) {
      validator.showErrors(normalizedErrors);
    }

    mostrarErrorBanco(Object.values(normalizedErrors)[0] || 'Revise los campos del formulario.');
    return;
  }

  mostrarErrorBanco(response.message || 'Error al guardar banco.');
}

function confirmarAccionBanco(title, text, confirmButtonText, callback) {
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
    if (result.isConfirmed) {
      callback();
    }
  });
}

function cambiarEstadoBotonBanco($button, disabled, html) {
  $button.prop('disabled', disabled).html(html);
}

function mostrarOkBanco(message) {
  if (typeof toastr !== 'undefined') {
    toastr.success(message);
    return;
  }

  alert(message);
}

function mostrarErrorBanco(message) {
  if (typeof toastr !== 'undefined') {
    toastr.error(message);
    return;
  }

  alert(message);
}

function escapeHtmlBanco(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
