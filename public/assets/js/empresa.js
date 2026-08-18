let tablaEmpresa = null;
let empresaRegistrada = $('#btn-nueva-empresa').data('empresa-registrada') == 1;
let empresaContextual = null;
const EMPRESA_LOGO_BASE = '/assets/modulo/empresa/logo';
const EMPRESA_SUNAT_ICON = '/assets/images/company-logos/ico-sunat.svg';

function puedeEmpresa(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('empresa', accion);
}

$(function () {
  inicializarSelectsEmpresa();
  inicializarTablaEmpresa();
  inicializarFormularioEmpresa();

  $('#btn-nueva-empresa').on('click', function () {
    if (!puedeEmpresa('crear')) {
      mostrarError('No tienes permiso para crear empresa.');
      return;
    }

    prepararNuevaEmpresa();
  });
  $('#btn-regresar-empresa, #btn-cancelar-empresa').on('click', function () {
    mostrarVistaEmpresa('tabla');
  });
  $('#btn-recargar-empresa').on('click', recargarTablaEmpresa);
  $('#btn-remover-logo-empresa').on('click', removerLogoEmpresa);
  $('#empresa_logo_file').on('change', previsualizarLogoEmpresa);
  $('#empresa_fe_certificado_file').on('change', validarCertificadoEmpresa);
  $('.btn-toggle-password-empresa').on('click', alternarPasswordEmpresa);
  $('#empresa_ubigueo').on('change', actualizarUbigeoEmpresa);
  $('#btn-buscar-documento-empresa').on('click', buscarDocumentoEmpresa);
  $('#opcion-empresa-toggle-sunat').on('click', alternarEstadoSunatEmpresa);
  $('#opcion-empresa-toggle-ambiente').on('click', alternarAmbienteSunatEmpresa);
  $(document).on('click scroll', ocultarMenuContextualEmpresa);
  $(document).on('keydown', function (event) {
    if (event.key === 'Escape') ocultarMenuContextualEmpresa();
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

function inicializarSelectsEmpresa() {
  $('#empresa_tipo_documento, #empresa_logo_c_r, #empresa_fe_activo, #empresa_fe_ambiente, #empresa_fe_certificado_tipo').select2({
    theme: 'bootstrap4',
    allowClear: false,
    width: '100%',
  });

  $('#empresa_idpersona').select2({
    theme: 'bootstrap4',
    placeholder: 'Buscar representante legal (opcional)',
    allowClear: true,
    width: '100%',
    ajax: {
      url: apiUrl('/empresa/personas-disponibles'),
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          term: params.term || '',
          page: params.page || 1,
        };
      },
    },
  });

  lista_select2(apiUrl('/select2/select2distrito'), '#empresa_ubigueo');
  $('#empresa_ubigueo').select2({
    theme: 'bootstrap4',
    allowClear: true,
    placeholder: 'Seleccionar distrito',
    width: '100%',
  });
}

function inicializarTablaEmpresa() {
  tablaEmpresa = $('#tabla-empresa').DataTable({
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
        exportOptions: { columns: [0, 2, 3, 4, 5, 6, 7] },
        title: 'Lista de empresas',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/empresa/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      dataSrc: 'data',
      error: function () {
        mostrarError('Error al consultar empresas.');
      },
    },
    columns: [
      { data: 'idempresa', className: 'text-center' },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap',
        render: function (_data, _type, row) {
          const acciones = [];

          if (puedeEmpresa('editar')) {
            acciones.push(`
            <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-empresa" data-id="${row.idempresa}" data-bs-toggle="tooltip" title="Editar">
              <i class="ri-edit-line"></i>
            </button>`);
          }

          if (puedeEmpresa('eliminar')) {
            acciones.push(`
            <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-empresa" data-id="${row.idempresa}" data-bs-toggle="tooltip" title="Eliminar">
              <i class="ri-delete-bin-line"></i>
            </button>`);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      {
        data: null,
        render: function (_data, _type, row) {
          const logo = row.logo ? apiUrl(`${EMPRESA_LOGO_BASE}/${row.logo}`) : apiUrl('/ynex_admin/svg/empresa-logo.svg');
          return `
            <div class="d-flex align-items-center">
              <span class="avatar avatar-md me-2 bg-light">
                <img src="${logo}" alt="" style="object-fit:contain;" onerror="this.src='${apiUrl('/ynex_admin/svg/empresa-logo.svg')}';">
              </span>
              <div>
                <p class="fw-semibold mb-0">${escapeHtml(row.nombre_razon_social || 'Sin razon social')}</p>
                <span class="text-muted fs-12">${escapeHtml(row.nombre_comercial || 'Sin nombre comercial')}</span>
              </div>
            </div>
          `;
        },
      },
      {
        data: null,
        render: function (_data, _type, row) {
          const abreviatura = row.doc_identidad?.abreviatura || '';
          return escapeHtml([abreviatura, row.numero_documento].filter(Boolean).join(' ') || '-');
        },
      },
      {
        data: null,
        render: function (_data, _type, row) {
          const correo = row.correo ? `<div><i class="ri-mail-line me-1"></i>${escapeHtml(row.correo)}</div>` : '';
          const telefono = row.telefono1 ? `<div><i class="ri-phone-line me-1"></i>${escapeHtml(row.telefono1)}</div>` : '';

          return correo || telefono || '<span class="text-muted">Sin contacto</span>';
        },
      },
      {
        data: null,
        render: function (_data, _type, row) {
          return escapeHtml([row.distrito, row.provincia, row.departamento].filter(Boolean).join(' / ') || row.domicilio_fiscal || '-');
        },
      },
      {
        data: null,
        className: 'text-center',
        render: function (_data, type, row) {
          const activo = String(row.fe_activo || '0') === '1';
          const ambiente = String(row.fe_ambiente || 'beta');
          const ambienteTexto = ambiente === 'production' ? 'Produccion' : 'Beta';
          const estadoTexto = activo ? 'Activa' : 'Inactiva';

          if (type !== 'display') {
            return `${estadoTexto} ${ambienteTexto}`;
          }

          const estadoClass = activo ? 'bg-success-transparent' : 'bg-danger-transparent';
          const ambienteClass = ambiente === 'production' ? 'bg-primary-transparent' : 'bg-warning-transparent';

          return `
            <div class="d-inline-flex align-items-center justify-content-center gap-2 text-nowrap">
              <img src="${apiUrl(EMPRESA_SUNAT_ICON)}" alt="SUNAT" class="empresa-sunat-icon">
              <div class="d-flex flex-column align-items-start gap-1">
                <span class="badge ${estadoClass}">${estadoTexto}</span>
                <span class="badge ${ambienteClass}">${ambienteTexto}</span>
              </div>
            </div>
          `;
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

  $('#tabla-empresa tbody').on('click', '.btn-editar-empresa', function () {
    editarEmpresa($(this).data('id'));
  });

  $('#tabla-empresa tbody').on('click', '.btn-eliminar-empresa', function () {
    eliminarEmpresa($(this).data('id'));
  });

  $('#tabla-empresa tbody').on('contextmenu', 'tr', function (event) {
    const data = tablaEmpresa.row(this).data();
    if (!data || !puedeEmpresa('editar')) return;

    event.preventDefault();
    abrirMenuContextualEmpresa(event, data);
  });
}

function inicializarFormularioEmpresa() {
  $('#form-empresa').validate({
    ignore: '.select2-search__field, .select2-input, .select2-focusser',
    rules: {
      nombre_razon_social: { required: true, minlength: 3, maxlength: 200 },
      numero_documento: { required: true, minlength: 8, maxlength: 15 },
      correo: { email: true },
      pie_impresion: { maxlength: 300 },
      venta: { number: true, min: 0, max: 100 },
      fe_sol_usuario: { maxlength: 80 },
      fe_sol_clave: { maxlength: 255 },
      fe_certificado_password: { maxlength: 255 },
      fe_codigo_local: { maxlength: 10 },
    },
    messages: {
      nombre_razon_social: {
        required: 'Campo requerido.',
        minlength: 'MINIMO {0} caracteres.',
        maxlength: 'MAXIMO {0} caracteres.',
      },
      numero_documento: {
        required: 'Campo requerido.',
        minlength: 'MINIMO {0} caracteres.',
        maxlength: 'MAXIMO {0} caracteres.',
      },
      correo: { email: 'Ingrese un correo valido.' },
      pie_impresion: { maxlength: 'MAXIMO {0} caracteres.' },
      venta: {
        number: 'Ingrese un numero valido.',
        min: 'Debe ser mayor o igual a {0}.',
        max: 'Debe ser menor o igual a {0}.',
      },
      fe_sol_usuario: { maxlength: 'MAXIMO {0} caracteres.' },
      fe_sol_clave: { maxlength: 'MAXIMO {0} caracteres.' },
      fe_certificado_password: { maxlength: 'MAXIMO {0} caracteres.' },
      fe_codigo_local: { maxlength: 'MAXIMO {0} caracteres.' },
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
      marcarSelect2Empresa(element, true);
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid').addClass('is-valid');
      marcarSelect2Empresa(element, false);
    },
    submitHandler: function (form, event) {
      event.preventDefault();
      guardarEmpresa();
    },
  });
}

function marcarSelect2Empresa(element, invalid) {
  const $element = $(element);
  if (!$element.hasClass('select2-hidden-accessible')) return;

  $element.next('.select2-container')
    .find('.select2-selection')
    .toggleClass('is-invalid', invalid)
    .toggleClass('is-valid', !invalid);
}

function mostrarVistaEmpresa(vista) {
  const form = vista === 'formulario';
  $('#div-tabla-empresa').toggle(!form);
  $('#div-formulario-empresa').toggle(form);
  $('#btn-nueva-empresa').toggle(!form && !empresaRegistrada);
  $('#btn-regresar-empresa').toggle(form);
}

function prepararNuevaEmpresa() {
  if (!puedeEmpresa('crear')) {
    mostrarError('No tienes permiso para crear empresa.');
    return;
  }

  if (empresaRegistrada) {
    mostrarInfo('Ya existe una empresa registrada. Solo puedes editarla o restaurarla desde Papelera.');
    return;
  }

  limpiarFormularioEmpresa();
  $('#titulo-formulario-empresa').text('Nueva empresa');
  botonesGuardarEmpresa().html('<i class="ti ti-device-floppy"></i> Guardar');
  mostrarVistaEmpresa('formulario');
}

function limpiarFormularioEmpresa() {
  const tipoDocumentoRuc = $('#empresa_tipo_documento option:first').val() || '';
  $('#form-empresa')[0]?.reset();
  $('#empresa_idempresa, #empresa_logo_actual').val('');
  $('#empresa_ubigueo_val').val('');
  $('#empresa_tipo_documento').val(tipoDocumentoRuc).trigger('change');
  $('#empresa_logo_c_r').val('1').trigger('change');
  $('#empresa_fe_activo').val('0').trigger('change');
  $('#empresa_fe_ambiente').val('beta').trigger('change');
  $('#empresa_fe_certificado_tipo').val('pem').trigger('change');
  $('#empresa_idpersona, #empresa_ubigueo').val(null).trigger('change');
  $('#empresa_logo_preview').attr('src', apiUrl('/ynex_admin/svg/empresa-logo.svg'));
  $('#empresa_codigo_pais').val('PE');
  $('#empresa_venta').val('18.00');
  $('#empresa_fe_codigo_local').val('0000');
  $('#empresa_fe_certificado_actual_text').text('Sin certificado cargado');
  actualizarDescargasCertificadoEmpresa({});
  $('#form-empresa .is-invalid, #form-empresa .is-valid').removeClass('is-invalid is-valid');
  $('#form-empresa').validate().resetForm();
}

function guardarEmpresa() {
  const id = $('#empresa_idempresa').val();
  const accion = id ? 'editar' : 'crear';

  if (!puedeEmpresa(accion)) {
    mostrarError(`No tienes permiso para ${accion} empresa.`);
    return;
  }

  const formData = new FormData($('#form-empresa')[0]);
  const url = id ? apiUrl(`/empresa/${id}/update`) : apiUrl('/empresa/store');
  const finalButtonHtml = id
    ? '<i class="ti ti-device-floppy"></i> Actualizar'
    : '<i class="ti ti-device-floppy"></i> Guardar';

  if (id) {
    formData.append('_method', 'PUT');
  }

  cambiarEstadoBoton(botonesGuardarEmpresa(), true, 'Guardando...');

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo guardar la empresa.');
        return;
      }

      mostrarOk(response.message || 'Empresa guardada correctamente.');
      empresaRegistrada = true;
      mostrarVistaEmpresa('tabla');
      recargarTablaEmpresa();
    },
    error: function (xhr) {
      mostrarErroresValidacionEmpresa(xhr);
    },
    complete: function () {
      cambiarEstadoBoton(botonesGuardarEmpresa(), false, finalButtonHtml);
    },
  });
}

function editarEmpresa(id) {
  if (!puedeEmpresa('editar')) {
    mostrarError('No tienes permiso para editar empresa.');
    return;
  }

  $.ajax({
    url: apiUrl(`/empresa/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo cargar la empresa.');
        return;
      }

      cargarEmpresaEnFormulario(response.data);
      mostrarVistaEmpresa('formulario');
    },
    error: function () {
      mostrarError('Error al cargar empresa.');
    },
  });
}

function cargarEmpresaEnFormulario(data) {
  limpiarFormularioEmpresa();
  $('#titulo-formulario-empresa').text('Editar empresa');
  botonesGuardarEmpresa().html('<i class="ti ti-device-floppy"></i> Actualizar');

  Object.entries(data).forEach(function ([key, value]) {
    const $input = $(`#empresa_${key}`);
    if ($input.length && !$input.is(':file')) {
      $input.val(value);
    }
  });

  $('#empresa_idempresa').val(data.idempresa);
  $('#empresa_tipo_documento').val(data.tipo_documento || $('#empresa_tipo_documento option:first').val() || '').trigger('change');
  $('#empresa_logo_c_r').val(data.logo_c_r || '1').trigger('change');
  $('#empresa_fe_activo').val(data.fe_activo || '0').trigger('change');
  $('#empresa_fe_ambiente').val(data.fe_ambiente || 'beta').trigger('change');
  $('#empresa_fe_certificado_tipo').val(data.fe_certificado_tipo || 'pem').trigger('change');
  $('#empresa_logo_actual').val(data.logo || '');
  $('#empresa_venta').val(data.igv ?? '');
  $('#empresa_fe_sol_clave, #empresa_fe_certificado_password, #empresa_fe_certificado_file').val('');
  $('#empresa_fe_certificado_actual_text').text(data.fe_certificado_archivo_base ? `Actual: ${data.fe_certificado_archivo_base}` : 'Sin certificado cargado');
  actualizarDescargasCertificadoEmpresa(data);

  if (data.persona) {
    const option = new Option(data.persona.descripcion || data.nombre_razon_social, data.persona.idpersona, true, true);
    $('#empresa_idpersona').append(option).trigger('change');
  }

  seleccionarDistritoEmpresa(data.ubigueo, data.distrito);

  if (data.logo) {
    $('#empresa_logo_preview').attr('src', apiUrl(`${EMPRESA_LOGO_BASE}/${data.logo}`));
  }
}

function eliminarEmpresa(id) {
  if (!puedeEmpresa('eliminar')) {
    mostrarError('No tienes permiso para eliminar empresa.');
    return;
  }

  confirmarAccionEmpresa('Eliminar empresa', 'La empresa se enviara a papelera.', function () {
    $.ajax({
      url: apiUrl(`/empresa/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (response.status) {
          mostrarOk(response.message || 'Empresa eliminada correctamente.');
          recargarTablaEmpresa();
          return;
        }

        mostrarError(response.message || 'No se pudo eliminar la empresa.');
      },
      error: function () {
        mostrarError('Error al eliminar empresa.');
      },
    });
  });
}

function abrirMenuContextualEmpresa(event, data) {
  empresaContextual = data;
  const activo = String(data.fe_activo || '0') === '1';
  const ambiente = String(data.fe_ambiente || 'beta');
  const siguienteAmbiente = ambiente === 'production' ? 'beta' : 'production';
  const $menu = $('#menu-contextual');

  $('#opcion-empresa-toggle-sunat span').text(activo ? 'Desactivar estado SUNAT' : 'Activar estado SUNAT');
  $('#opcion-empresa-toggle-ambiente span').text(
    siguienteAmbiente === 'production' ? 'Cambiar a produccion' : 'Cambiar a beta'
  );

  $menu.css({ display: 'block', left: event.pageX, top: event.pageY });

  const menuWidth = $menu.outerWidth();
  const menuHeight = $menu.outerHeight();
  const maxLeft = $(window).scrollLeft() + $(window).width() - menuWidth - 8;
  const maxTop = $(window).scrollTop() + $(window).height() - menuHeight - 8;

  $menu.css({
    left: Math.max(8, Math.min(event.pageX, maxLeft)),
    top: Math.max(8, Math.min(event.pageY, maxTop)),
  });
}

function ocultarMenuContextualEmpresa() {
  $('#menu-contextual').hide();
}

function alternarEstadoSunatEmpresa(event) {
  event.preventDefault();
  event.stopPropagation();

  if (!empresaContextual) return;

  const activo = String(empresaContextual.fe_activo || '0') === '1';
  const nuevoEstado = activo ? '0' : '1';
  const textoEstado = nuevoEstado === '1' ? 'activar' : 'desactivar';

  confirmarAccionEmpresa('Confirmar SUNAT', `Se va a ${textoEstado} la facturacion electronica SUNAT.`, function () {
    actualizarSunatEmpresa(empresaContextual.idempresa, {
      fe_activo: nuevoEstado,
    });
  });
}

function alternarAmbienteSunatEmpresa(event) {
  event.preventDefault();
  event.stopPropagation();

  if (!empresaContextual) return;

  const ambiente = String(empresaContextual.fe_ambiente || 'beta');
  const nuevoAmbiente = ambiente === 'production' ? 'beta' : 'production';
  const textoAmbiente = nuevoAmbiente === 'production' ? 'produccion' : 'beta';

  confirmarAccionEmpresa('Confirmar ambiente SUNAT', `Se va a cambiar el ambiente SUNAT a ${textoAmbiente}.`, function () {
    actualizarSunatEmpresa(empresaContextual.idempresa, {
      fe_ambiente: nuevoAmbiente,
    });
  });
}

function actualizarSunatEmpresa(id, cambios) {
  if (!puedeEmpresa('editar')) {
    mostrarError('No tienes permiso para editar empresa.');
    return;
  }

  ocultarMenuContextualEmpresa();

  $.ajax({
    url: apiUrl(`/empresa/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo cargar la empresa.');
        return;
      }

      guardarSunatEmpresa(response.data, cambios);
    },
    error: function () {
      mostrarError('Error al cargar empresa.');
    },
  });
}

function guardarSunatEmpresa(data, cambios) {
  const formData = new FormData();
  const campos = [
    'idpersona',
    'tipo_documento',
    'nombre_razon_social',
    'nombre_comercial',
    'domicilio_fiscal',
    'numero_documento',
    'telefono1',
    'telefono2',
    'correo',
    'web',
    'web_consulta_cp',
    'logo',
    'logo_c_r',
    'ubigueo',
    'codubigueo',
    'distrito',
    'provincia',
    'departamento',
    'codigo_pais',
    'pie_impresion',
    'fe_sol_usuario',
    'fe_certificado_tipo',
    'fe_codigo_local',
  ];

  campos.forEach(function (campo) {
    if (data[campo] !== null && data[campo] !== undefined) {
      formData.append(campo, data[campo]);
    }
  });

  formData.append('venta', data.igv ?? '');
  formData.append('fe_activo', cambios.fe_activo ?? data.fe_activo ?? '0');
  formData.append('fe_ambiente', cambios.fe_ambiente ?? data.fe_ambiente ?? 'beta');
  formData.append('_method', 'PUT');

  $.ajax({
    url: apiUrl(`/empresa/${data.idempresa}/update`),
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      if (!response.status) {
        mostrarError(response.message || 'No se pudo actualizar SUNAT.');
        return;
      }

      mostrarOk(response.message || 'Configuracion SUNAT actualizada.');
      recargarTablaEmpresa();
    },
    error: function (xhr) {
      mostrarErroresValidacionEmpresa(xhr);
    },
  });
}

function actualizarUbigeoEmpresa() {
  const selected = $('#empresa_ubigueo').find('option:selected');
  $('#empresa_ubigueo_val').val(selected.attr('iddistrito') || '');
  $('#empresa_distrito').val(selected.text() || '');
  $('#empresa_provincia').val(selected.attr('provincia') || '');
  $('#empresa_departamento').val(selected.attr('departamento') || '');
  $('#empresa_codubigueo').val(selected.attr('codubigueo') || '');
}

function seleccionarDistritoEmpresa(ubigueo, distritoTexto) {
  if (!ubigueo && !distritoTexto) return;

  $('#empresa_ubigueo option').each(function () {
    if ($(this).val() == distritoTexto || $(this).attr('iddistrito') == ubigueo) {
      $(this).prop('selected', true);
      return false;
    }

    return true;
  });

  $('#empresa_ubigueo').trigger('change');
}

function buscarDocumentoEmpresa() {
  const ruc = String($('#empresa_numero_documento').val() || '').trim();

  if (!/^\d{11}$/.test(ruc)) {
    $('#empresa_numero_documento').addClass('is-invalid');
    mostrarInfo('Ingrese un RUC valido de 11 digitos.');
    return;
  }

  $('#empresa_numero_documento').removeClass('is-invalid');
  $('#search').hide();
  $('#charge').show();
  $('#btn-buscar-documento-empresa').prop('disabled', true);

  $.getJSON(apiUrl('/sunat/ruc'), { ruc })
    .done(function (response) {
      if (!response?.status || !response?.data) {
        mostrarError(response?.message || 'No se pudieron obtener los datos de SUNAT.');
        return;
      }

      const datosSunat = normalizarDatosSunatEmpresa(response.data);
      if (!datosSunat) {
        mostrarError(response?.data?.message || response?.message || 'SUNAT no devolvio datos validos para el RUC consultado.');
        return;
      }

      cargarDatosSunatEmpresa(datosSunat);
      $('#form-empresa').valid();
      mostrarOk(response?.message || 'Datos de SUNAT cargados correctamente.');
    })
    .fail(function (xhr) {
      mostrarError(xhr?.responseJSON?.message || 'No se pudieron consultar los datos de SUNAT.');
    })
    .always(function () {
      $('#search').show();
      $('#charge').hide();
      $('#btn-buscar-documento-empresa').prop('disabled', false);
    });
}

function normalizarDatosSunatEmpresa(data) {
  let fuente = data;

  if (fuente?.success === false) return null;
  if (fuente?.data && typeof fuente.data === 'object' && !Array.isArray(fuente.data)) {
    fuente = fuente.data;
  }
  if (!fuente || typeof fuente !== 'object' || Array.isArray(fuente)) return null;

  const ubigeoLista = Array.isArray(fuente.ubigeo) ? fuente.ubigeo : [];
  const ubigeo = String(fuente.ubigeo_sunat || ubigeoLista[ubigeoLista.length - 1] || fuente.ubigeo || '').trim();

  return {
    ruc: String(fuente.ruc || fuente.numero || '').trim(),
    razonSocial: String(fuente.razonSocial || fuente.nombre_o_razon_social || '').trim(),
    nombreComercial: String(fuente.nombreComercial || fuente.nombre_comercial || '').trim(),
    direccion: String(fuente.direccion || fuente.direccion_completa || '').trim(),
    departamento: String(fuente.departamento || '').trim(),
    provincia: String(fuente.provincia || '').trim(),
    distrito: String(fuente.distrito || '').trim(),
    ubigeo,
    telefonos: Array.isArray(fuente.telefonos) ? fuente.telefonos : [],
    tipo: String(fuente.tipo || fuente.tipo_contribuyente || '').trim(),
  };
}

function cargarDatosSunatEmpresa(data) {
  const telefonos = Array.isArray(data?.telefonos) ? data.telefonos : [];
  const ubigeo = String(data?.ubigeo || '').trim();
  const distrito = String(data?.distrito || '').trim();

  $('#empresa_numero_documento').val(data?.ruc || '');
  $('#empresa_nombre_razon_social').val(data?.razonSocial || '');
  $('#empresa_nombre_comercial').val(data?.nombreComercial || '');
  $('#empresa_domicilio_fiscal').val(data?.direccion || '');
  $('#empresa_departamento').val(data?.departamento || '');
  $('#empresa_provincia').val(data?.provincia || '');
  $('#empresa_distrito').val(distrito);
  $('#empresa_codubigueo').val(ubigeo);
  $('#empresa_ubigueo_val').val('');
  $('#empresa_telefono1').val(telefonos[0] || '');
  $('#empresa_telefono2').val(telefonos[1] || '');

  if (data?.tipo) {
    $('#empresa_tipo_persona_sunat').val(data.tipo);
  }

  seleccionarDistritoSunatEmpresa(ubigeo, distrito);
}

function seleccionarDistritoSunatEmpresa(ubigeo, distritoTexto) {
  let seleccionado = false;

  $('#empresa_ubigueo option').each(function () {
    if ($(this).attr('iddistrito') == ubigeo || $(this).val() == distritoTexto) {
      $(this).prop('selected', true);
      seleccionado = true;
      return false;
    }

    return true;
  });

  if (seleccionado) {
    $('#empresa_ubigueo').trigger('change');
  }
}

function previsualizarLogoEmpresa() {
  const input = this;
  if (!input.files || !input.files[0]) return;

  const reader = new FileReader();
  reader.onload = function (event) {
    $('#empresa_logo_preview').attr('src', event.target.result);
  };
  reader.readAsDataURL(input.files[0]);
}

function validarCertificadoEmpresa() {
  const file = this.files?.[0];
  if (!file) return;

  const nombre = String(file.name || '').toLowerCase();
  const esValido = ['.pem', '.p12', '.pfx'].some(function (extension) {
    return nombre.endsWith(extension);
  });

  if (!esValido) {
    this.value = '';
    mostrarError('El certificado debe ser un archivo .pem, .p12 o .pfx.');
  }
}

function alternarPasswordEmpresa() {
  const target = $(this).data('target');
  const $input = $(target);
  const visible = $input.attr('type') === 'text';

  $input.attr('type', visible ? 'password' : 'text');
  $(this)
    .attr('title', visible ? 'Ver clave' : 'Ocultar clave')
    .find('i')
    .toggleClass('ri-eye-line', visible)
    .toggleClass('ri-eye-off-line', !visible);
}

function actualizarDescargasCertificadoEmpresa(data) {
  const pemUrl = data?.fe_certificado_pem_url || '';
  const cerUrl = data?.fe_certificado_cer_url || '';

  $('#empresa_fe_descargar_pem')
    .attr('href', pemUrl || '#')
    .toggle(Boolean(pemUrl));
  $('#empresa_fe_descargar_cer')
    .attr('href', cerUrl || '#')
    .toggle(Boolean(cerUrl));
  $('#empresa_fe_certificado_descargas').toggle(Boolean(pemUrl || cerUrl));
}

function removerLogoEmpresa() {
  $('#empresa_logo_file').val('');
  $('#empresa_logo_actual').val('');
  $('#empresa_logo_preview').attr('src', apiUrl('/ynex_admin/svg/empresa-logo.svg'));
}

function recargarTablaEmpresa() {
  tablaEmpresa?.ajax.reload(null, false);
}

function mostrarErroresValidacionEmpresa(xhr) {
  const response = xhr.responseJSON || {};
  const errors = response.data || response.errors || {};

  if (xhr.status === 422 && Object.keys(errors).length) {
    const firstMessage = Object.values(errors).flat()[0] || 'Revise los campos del formulario.';
    mostrarError(firstMessage);
    return;
  }

  mostrarError(response.message || 'Error al guardar empresa.');
}

function confirmarAccionEmpresa(title, message, callback) {
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

function botonesGuardarEmpresa() {
  return $('#btn-guardar-empresa, #btn-guardar-empresa-bottom');
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

