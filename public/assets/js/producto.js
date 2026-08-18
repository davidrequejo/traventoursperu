let tablaProductos = null;
let catalogosProducto = {
  categorias: [],
  marcas: [],
  unidades_medida: [],
};
const CATALOGO_RAPIDO_ENDPOINTS = Object.freeze({
  marca: '/marcas/store',
  categoria: '/categorias/store',
});

function puedeProducto(accion) {
  return typeof puedePermiso !== 'function' || puedePermiso('productos', accion);
}

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
  inicializarPantallaProducto();
});

async function inicializarPantallaProducto() {
  try {
    await cargarCatalogosProducto();
    inicializarSelect2Producto();
    inicializarTablaProducto();
    inicializarFormularioProducto();
    inicializarFormularioCatalogoRapido();
    enlazarEventosProducto();
    mostrarVistaProducto('tabla');
  } catch (error) {
    mostrarErrorProducto('No se pudo inicializar el modulo de productos.');
    console.error(error);
  }
}

function enlazarEventosProducto() {
  $('#btn-nuevo-producto').on('click', prepararNuevoProducto);
  $('#btn-regresar-producto, #btn-cancelar-producto').on('click', () => mostrarVistaProducto('tabla'));
  $('#incluir-eliminados-producto').on('change', recargarTablaProducto);

  $(document).on('click', '.js-abrir-rapido-catalogo', function () {
    abrirModalCatalogoRapido($(this).data('tipo'));
  });
  $('#modal-catalogo-rapido').on('keydown', 'input, select', function (event) {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    $('#form-catalogo-rapido').trigger('submit');
  });
  $('#modal-catalogo-rapido').on('hidden.bs.modal', limpiarFormularioCatalogoRapido);
}

function inicializarSelect2Producto() {
  const optionsBase = {
    theme: 'bootstrap4',
    width: '100%',
    allowClear: true,
    placeholder: 'Seleccione',
  };

  $('#producto_tipo').select2(optionsBase);
  $('#producto_unidad').select2(optionsBase);
  $('#producto_marca').select2(optionsBase);
  $('#producto_categoria').select2(optionsBase);

  $('#producto_tipo, #producto_unidad, #producto_marca, #producto_categoria').on('change', function () {
    if ($('#form-producto').data('validator')) {
      $(this).valid();
    }

    actualizarEstadoSelect2($(this));
  });
}

async function cargarCatalogosProducto() {
  const response = await $.ajax({
    url: apiUrl('/productos/catalogos'),
    type: 'GET',
    headers: ajaxHeaders(),
  });

  if (!response?.status) {
    throw new Error(response?.message || 'No se pudieron cargar catalogos.');
  }

  catalogosProducto = response.data || catalogosProducto;
  renderCatalogosEnSelects();
}

function renderCatalogosEnSelects() {
  renderSelect(
    '#producto_categoria',
    catalogosProducto.categorias || [],
    'idcategoria',
    'nombre'
  );
  renderSelect(
    '#producto_marca',
    catalogosProducto.marcas || [],
    'idmarca',
    'nombre'
  );
  renderSelect(
    '#producto_unidad',
    catalogosProducto.unidades_medida || [],
    'idsunat_c03_unidad_medida',
    (item) => [item.nombre, item.abreviatura ? `(${item.abreviatura})` : ''].join(' ').trim()
  );
}

function renderSelect(selector, items, valueKey, labelKeyOrFn) {
  const $select = $(selector);
  const valorActual = $select.val();

  $select.empty().append('<option value="">Seleccione</option>');

  items.forEach((item) => {
    const valor = item[valueKey];
    const texto = typeof labelKeyOrFn === 'function' ? labelKeyOrFn(item) : item[labelKeyOrFn];
    $select.append(new Option(texto || '-', valor, false, false));
  });

  if (valorActual) {
    $select.val(String(valorActual));
  }

  $select.trigger('change.select2');
}

function inicializarTablaProducto() {
  tablaProductos = $('#tabla-productos').DataTable({
    responsive: true,
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
        exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8] },
        title: 'Listado de productos',
        text: '<i class="bi bi-file-earmark-excel"></i>',
        className: 'btn btn-outline-success',
      },
    ],
    ajax: {
      url: apiUrl('/productos/listar'),
      type: 'GET',
      headers: ajaxHeaders(),
      data: function (data) {
        data.incluir_trash = $('#incluir-eliminados-producto').is(':checked') ? 1 : 0;
      },
      dataSrc: function (response) {
        if (response && response.status === false) {
          mostrarErrorProducto(response?.message || 'No se pudo listar productos.');
          return [];
        }

        return response.data || [];
      },
      error: function () {
        mostrarErrorProducto('Error al consultar productos.');
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

          if (puedeProducto('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-warning btn-editar-producto" data-id="${row.idproducto}" title="Editar">
                <i class="ri-edit-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '1' && puedeProducto('eliminar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-danger btn-eliminar-producto" data-id="${row.idproducto}" title="Eliminar">
                <i class="ri-delete-bin-line"></i>
              </button>
            `);
          }

          if (String(row.estado_trash) === '0' && puedeProducto('editar')) {
            acciones.push(`
              <button type="button" class="btn btn-sm btn-icon btn-success btn-restaurar-producto" data-id="${row.idproducto}" title="Restaurar">
                <i class="ti ti-restore"></i>
              </button>
            `);
          }

          return acciones.join('') || '<span class="text-muted">-</span>';
        },
      },
      { data: 'codigo', render: renderPlano },
      { data: 'nombre', render: renderNegrita },
      {
        data: 'tipo',
        render: function (tipo) {
          if (tipo === 'PR') return '<span class="badge bg-primary-transparent">Producto</span>';
          if (tipo === 'SR') return '<span class="badge bg-info-transparent">Servicio</span>';
          return '<span class="text-muted">-</span>';
        },
      },
      { data: 'marca.nombre', defaultContent: '-', render: renderPlano },
      { data: 'categoria.nombre', defaultContent: '-', render: renderPlano },
      {
        data: 'unidad_medida',
        render: function (unidad) {
          if (!unidad) return '<span class="text-muted">-</span>';
          return renderPlano([unidad.nombre, unidad.abreviatura ? `(${unidad.abreviatura})` : ''].join(' ').trim());
        },
      },
      { data: 'stock', className: 'text-end', render: renderDecimal },
      { data: 'precio_venta', className: 'text-end', render: renderMoneda },
      {
        data: 'estado_trash',
        className: 'text-center',
        render: (estado) => String(estado) === '1'
          ? '<span class="badge bg-success-transparent">Activo</span>'
          : '<span class="badge bg-danger-transparent">Eliminado</span>',
      },
    ],
    order: [[1, 'desc']],
    language: {
      lengthMenu: '_MENU_',
      sLoadingRecords: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...',
      search: '',
    },
  });

  $('#tabla-productos tbody').on('click', '.btn-editar-producto', function () {
    editarProducto($(this).data('id'));
  });
  $('#tabla-productos tbody').on('click', '.btn-eliminar-producto', function () {
    eliminarProducto($(this).data('id'));
  });
  $('#tabla-productos tbody').on('click', '.btn-restaurar-producto', function () {
    restaurarProducto($(this).data('id'));
  });
}

function inicializarFormularioProducto() {
  $('#form-producto').validate({
    ignore: '.select2-search__field, .select2-input, .select2-focusser',
    rules: {
      tipo: { required: true },
      idsunat_unidad_medida: { required: true },
      idmarca: { required: true },
      idcategoria: { required: true },
      nombre: { required: true, maxlength: 250 },
      codigo_alterno: { maxlength: 50 },
      stock_minimo: { number: true, min: 0 },
      precio_compra: { number: true, min: 0 },
      precio_venta: { number: true, min: 0 },
      descripcion_adicional: { maxlength: 250 },
    },
    messages: {
      tipo: { required: 'Seleccione el tipo.' },
      idsunat_unidad_medida: { required: 'Seleccione la unidad de medida.' },
      idmarca: { required: 'Seleccione una marca.' },
      idcategoria: { required: 'Seleccione una categoria.' },
      nombre: { required: 'Campo requerido.', maxlength: 'MAXIMO {0} caracteres.' },
      codigo_alterno: { maxlength: 'MAXIMO {0} caracteres.' },
      stock_minimo: { number: 'Ingrese un numero valido.', min: 'Ingrese un numero mayor o igual a {0}.' },
      precio_compra: { number: 'Ingrese un numero valido.', min: 'Ingrese un numero mayor o igual a {0}.' },
      precio_venta: { number: 'Ingrese un numero valido.', min: 'Ingrese un numero mayor o igual a {0}.' },
      descripcion_adicional: { maxlength: 'MAXIMO {0} caracteres.' },
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
    submitHandler: function () {
      guardarProducto();
    },
  });
}

function inicializarFormularioCatalogoRapido() {
  $('#form-catalogo-rapido').validate({
    ignore: '.select2-search__field, .select2-input, .select2-focusser',
    rules: {
      nombre: { required: true, maxlength: 100 },
      descripcion: { maxlength: 250 },
    },
    messages: {
      nombre: {
        required: 'Ingrese el nombre para continuar.',
        maxlength: 'MAXIMO {0} caracteres.',
      },
      descripcion: { maxlength: 'MAXIMO {0} caracteres.' },
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
    submitHandler: function (_form, event) {
      if (event) event.preventDefault();
      guardarCatalogoRapido();
    },
  });
}

function prepararNuevoProducto() {
  if (!puedeProducto('crear')) {
    mostrarErrorProducto('No tienes permiso para crear productos.');
    return;
  }

  limpiarFormularioProducto();
  $('#form-producto-titulo').html('<i class="ti ti-package"></i> Nuevo producto');
  $('#btn-guardar-producto').html('<i class="ti ti-device-floppy me-1"></i> Guardar');
  mostrarVistaProducto('formulario');
}

function editarProducto(id) {
  if (!puedeProducto('editar')) {
    mostrarErrorProducto('No tienes permiso para editar productos.');
    return;
  }

  $.ajax({
    url: apiUrl(`/productos/${id}/show`),
    type: 'GET',
    headers: ajaxHeaders(),
    success: function (response) {
      if (!response?.status) {
        mostrarErrorProducto(response?.message || 'No se pudo cargar el producto.');
        return;
      }

      cargarProductoEnFormulario(response.data || {});
      $('#form-producto-titulo').html('<i class="ti ti-package"></i> Editar producto');
      $('#btn-guardar-producto').html('<i class="ti ti-device-floppy me-1"></i> Actualizar');
      mostrarVistaProducto('formulario');
    },
    error: function () {
      mostrarErrorProducto('Error al cargar producto.');
    },
  });
}

function cargarProductoEnFormulario(data) {
  limpiarFormularioProducto();

  $('#producto_idproducto').val(data.idproducto || '');
  $('#producto_tipo').val(data.tipo || '').trigger('change');
  $('#producto_unidad').val(String(data.idsunat_c03 || data.idsunat_unidad_medida || '')).trigger('change');
  $('#producto_marca').val(String(data.idmarca || '')).trigger('change');
  $('#producto_categoria').val(String(data.idcategoria || '')).trigger('change');
  $('#producto_codigo_alterno').val(data.codigo_alterno || '');
  $('#producto_nombre').val(data.nombre || '');
  $('#producto_stock_minimo').val(data.stock_minimo ?? '');
  $('#producto_precio_compra').val(data.precio_compra ?? '');
  $('#producto_precio_venta').val(data.precio_venta ?? '');
  $('#producto_descripcion_adicional').val(data.descripcion_adicional || '');
  $('#producto_imagen_actual').val(data.imagen || '');
}

function guardarProducto() {
  const id = $('#producto_idproducto').val();
  const esEdicion = Boolean(id);

  if (esEdicion && !puedeProducto('editar')) {
    mostrarErrorProducto('No tienes permiso para editar productos.');
    return;
  }
  if (!esEdicion && !puedeProducto('crear')) {
    mostrarErrorProducto('No tienes permiso para crear productos.');
    return;
  }

  const form = document.getElementById('form-producto');
  const formData = new FormData(form);
  const url = esEdicion ? apiUrl(`/productos/${id}/update`) : apiUrl('/productos/store');

  if (esEdicion) {
    formData.append('_method', 'PUT');
  }

  setEstadoGuardadoProducto(true);

  $.ajax({
    url,
    type: 'POST',
    headers: ajaxHeaders(),
    data: formData,
    processData: false,
    contentType: false,
    success: function (response) {
      if (!response?.status) {
        mostrarErrorProducto(response?.message || 'No se pudo guardar el producto.');
        return;
      }

      mostrarOkProducto(response?.message || 'Producto guardado correctamente.');
      mostrarVistaProducto('tabla');
      recargarTablaProducto();
    },
    error: function (xhr) {
      const message = extraerPrimerError(xhr) || xhr.responseJSON?.message || 'Error al guardar producto.';
      mostrarErrorProducto(message);
    },
    complete: function () {
      setEstadoGuardadoProducto(false);
    },
  });
}

function eliminarProducto(id) {
  if (!puedeProducto('eliminar')) {
    mostrarErrorProducto('No tienes permiso para eliminar productos.');
    return;
  }

  confirmarProducto('Eliminar producto', 'El producto se enviara a papelera.', 'Si, eliminar', function () {
    $.ajax({
      url: apiUrl(`/productos/${id}`),
      type: 'DELETE',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response?.status) {
          mostrarErrorProducto(response?.message || 'No se pudo eliminar el producto.');
          return;
        }
        mostrarOkProducto(response?.message || 'Producto eliminado correctamente.');
        recargarTablaProducto();
      },
      error: function (xhr) {
        mostrarErrorProducto(xhr.responseJSON?.message || 'Error al eliminar producto.');
      },
    });
  });
}

function restaurarProducto(id) {
  if (!puedeProducto('editar')) {
    mostrarErrorProducto('No tienes permiso para restaurar productos.');
    return;
  }

  confirmarProducto('Restaurar producto', 'El producto volvera a estar activo.', 'Si, restaurar', function () {
    $.ajax({
      url: apiUrl(`/productos/${id}/restore`),
      type: 'POST',
      headers: ajaxHeaders(),
      success: function (response) {
        if (!response?.status) {
          mostrarErrorProducto(response?.message || 'No se pudo restaurar el producto.');
          return;
        }
        mostrarOkProducto(response?.message || 'Producto restaurado correctamente.');
        recargarTablaProducto();
      },
      error: function (xhr) {
        mostrarErrorProducto(xhr.responseJSON?.message || 'Error al restaurar producto.');
      },
    });
  });
}

function abrirModalCatalogoRapido(tipo) {
  const endpoint = obtenerEndpointCatalogoRapido(tipo);
  if (!endpoint) {
    mostrarErrorProducto('No se pudo identificar el tipo de registro.');
    return;
  }

  limpiarFormularioCatalogoRapido();

  const esMarca = tipo === 'marca';
  $('#catalogo_rapido_tipo').val(tipo);
  $('#form-catalogo-rapido').attr('action', apiUrl(endpoint));
  $('#modal-catalogo-rapido-label').text(esMarca ? 'Nueva marca' : 'Nueva categoria');
  $('#modal-catalogo-rapido').modal('show');
  setTimeout(function () {
    $('#catalogo_rapido_nombre').trigger('focus');
  }, 120);
}

async function guardarCatalogoRapido() {
  if ($('#btn-guardar-catalogo-rapido').prop('disabled')) {
    return;
  }

  const tipo = String($('#catalogo_rapido_tipo').val() || '').trim();
  const endpoint = obtenerEndpointCatalogoRapido(tipo);

  if (!endpoint) {
    mostrarErrorProducto('No se pudo identificar el tipo de registro.');
    return;
  }

  const action = $('#form-catalogo-rapido').attr('action') || apiUrl(endpoint);
  const payload = {
    nombre: String($('#catalogo_rapido_nombre').val() || '').trim(),
    descripcion: String($('#catalogo_rapido_descripcion').val() || '').trim(),
  };

  setEstadoBotonRapido(true);

  try {
    const response = await $.ajax({
      url: action,
      type: 'POST',
      headers: ajaxHeaders(),
      data: payload,
    });

    if (!response?.status) {
      mostrarErrorProducto(response?.message || 'No se pudo guardar el registro.');
      return;
    }

    const nuevo = response.data || {};
    await cargarCatalogosProducto();

    if (tipo === 'marca') {
      $('#producto_marca').val(String(nuevo.idmarca)).trigger('change');
    } else {
      $('#producto_categoria').val(String(nuevo.idcategoria)).trigger('change');
    }

    $('#modal-catalogo-rapido').modal('hide');
    mostrarOkProducto(response?.message || 'Registro creado correctamente.');
  } catch (xhr) {
    const message = extraerPrimerError(xhr) || xhr.responseJSON?.message || 'Error al crear registro rapido.';
    mostrarErrorProducto(message);
  } finally {
    setEstadoBotonRapido(false);
  }
}

function obtenerEndpointCatalogoRapido(tipo) {
  return CATALOGO_RAPIDO_ENDPOINTS[tipo] || null;
}

function setEstadoGuardadoProducto(guardando) {
  $('#overlay-guardando-producto').toggleClass('show', guardando);
  $('#btn-guardar-producto, #btn-cancelar-producto, #btn-regresar-producto, #btn-nuevo-producto')
    .prop('disabled', guardando);
}

function setEstadoBotonRapido(guardando) {
  const html = guardando
    ? '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...'
    : '<i class="ti ti-device-floppy me-1"></i> Guardar';
  $('#btn-guardar-catalogo-rapido').prop('disabled', guardando).html(html);
  $('#catalogo_rapido_nombre, #catalogo_rapido_descripcion').prop('disabled', guardando);
}

function limpiarFormularioCatalogoRapido() {
  const form = document.getElementById('form-catalogo-rapido');
  if (!form) return;

  form.reset();
  $('#catalogo_rapido_tipo').val('');
  $('#catalogo_rapido_nombre, #catalogo_rapido_descripcion').removeClass('is-valid is-invalid');
  $('#form-catalogo-rapido .error.invalid-feedback').remove();

  if ($('#form-catalogo-rapido').data('validator')) {
    $('#form-catalogo-rapido').validate().resetForm();
  }

  setEstadoBotonRapido(false);
}

function mostrarVistaProducto(vista) {
  const esFormulario = vista === 'formulario';

  if (esFormulario) {
    $('#panel-productos-tabla').stop(true, true).fadeOut(120, function () {
      $('#panel-productos-form').fadeIn(160);
    });
  } else {
    $('#panel-productos-form').stop(true, true).fadeOut(120, function () {
      $('#panel-productos-tabla').fadeIn(160);
    });
  }

  $('#btn-regresar-producto').toggle(esFormulario);
  $('#incluir-eliminados-producto').closest('.form-check').toggle(!esFormulario);
  $('#btn-nuevo-producto').toggle(!esFormulario && puedeProducto('crear'));

  if (!esFormulario) {
    limpiarFormularioProducto();
  }
}

function limpiarFormularioProducto() {
  const form = document.getElementById('form-producto');
  if (!form) return;

  form.reset();
  $('#producto_idproducto').val('');
  $('#producto_imagen_actual').val('');
  $('#producto_tipo, #producto_marca, #producto_categoria').val('').trigger('change');
  $('#producto_unidad').val(58).trigger('change');

  // Limpiamos las validaciones
  $('#form-producto .form-control').removeClass('is-valid');
  $('#form-producto .form-control').removeClass('is-invalid');
  $('#form-producto .error.invalid-feedback').remove();
  $('#form-producto .select2-selection').removeClass('is-valid is-invalid');

  if ($('#form-producto').data('validator')) {
    $('#form-producto').validate().resetForm();
  }
}

function recargarTablaProducto() {
  tablaProductos?.ajax.reload(null, false);
}

function confirmarProducto(title, text, confirmButtonText, callback) {
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

function actualizarEstadoSelect2($element) {
  if (!$element?.hasClass('select2-hidden-accessible')) return;

  const $selection = $element.next('.select2-container').find('.select2-selection');
  const esValido = String($element.val() || '').trim() !== '';

  $selection.toggleClass('is-valid', esValido);
  $selection.toggleClass('is-invalid', !esValido);
}

function extraerPrimerError(xhr) {
  const response = xhr?.responseJSON || {};
  const errors = response?.data || response?.errors || {};
  const first = Object.values(errors)[0];
  if (!first) return null;
  return Array.isArray(first) ? first[0] : first;
}

function renderNegrita(data) {
  return `<span class="fw-semibold">${escapeHtml(data || '-')}</span>`;
}

function renderPlano(data) {
  if (data === null || data === undefined || data === '') return '<span class="text-muted">-</span>';
  return escapeHtml(data);
}

function renderDecimal(data) {
  if (data === null || data === undefined || data === '') return '<span class="text-muted">-</span>';
  return Number(data).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function renderMoneda(data) {
  if (data === null || data === undefined || data === '') return '<span class="text-muted">-</span>';
  return `S/ ${Number(data).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function mostrarOkProducto(message) {
  if (typeof toastr !== 'undefined') {
    toastr.success(message);
    return;
  }
  alert(message);
}

function mostrarErrorProducto(message) {
  if (typeof toastr !== 'undefined') {
    toastr.error(message);
    return;
  }
  alert(message);
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
