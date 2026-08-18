let tablaLlegadaEmpresa;

const llegadaEmpresaConfig = {
    base: document.body.dataset.rutaBase || '',
    titulo: document.body.dataset.titulo || 'Empresas',
    singular: document.body.dataset.singular || 'Empresa',
};

const llegadaEmpresaUrl = (path = '') => `${document.querySelector('meta[name="app-url"]')?.content || ''}${llegadaEmpresaConfig.base}${path}`;
const llegadaEmpresaHeaders = () => ({
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
    Accept: 'application/json',
});

const escaparHtmlLlegadaEmpresa = (valor) => $('<div>').text(valor ?? '').html();

$(function () {
    tablaLlegadaEmpresa = $('#tabla-llegada-empresa').DataTable({
        responsive: true,
        processing: true,
        deferRender: true,
        searchDelay: 350,
        dom: "<'row'<'col-md-8 pt-2'f><'col-md-4 pt-2 d-flex justify-content-end'<'length'l><'buttons'B>>>r t <'row'<'col-md-6'i><'col-md-6'p>>",
        language: { lengthMenu: "_MENU_" },
        buttons: [
            { text: '<i class="bi bi-arrow-clockwise"></i>', className: 'buttons-reload btn btn-outline-info', action: (_event, table) => table.ajax.reload(null, false) },
            { extend: 'excel', exportOptions: { columns: [1, 2, 3] }, title: llegadaEmpresaConfig.titulo, text: '<i class="bi bi-file-earmark-excel"></i>', className: 'btn btn-outline-success' },
        ],
        ajax: {
            url: llegadaEmpresaUrl('/listar'),
            data: () => ({ incluir_trash: $('#incluir-eliminados-llegada-empresa').is(':checked') ? 1 : 0 }),
            dataSrc: (respuesta) => respuesta.data || [],
        },
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (fila) => {
                    if (String(fila.estado_trash) === '0') {
                        return `<button type="button" class="btn btn-sm btn-success js-restaurar-llegada-empresa" data-id="${fila.idllegada_por_empresa}" title="Restaurar"><i class="ri-refresh-line"></i></button>`;
                    }
                    return `<button type="button" class="btn btn-sm btn-primary js-editar-llegada-empresa" data-id="${fila.idllegada_por_empresa}" title="Editar"><i class="ri-edit-line"></i></button> <button type="button" class="btn btn-sm btn-danger js-eliminar-llegada-empresa" data-id="${fila.idllegada_por_empresa}" title="Eliminar"><i class="ri-delete-bin-line"></i></button>`;
                },
            },
            { data: 'descripcion', render: (valor) => `<span class="fw-semibold">${escaparHtmlLlegadaEmpresa(valor)}</span>` },
            { data: 'tipo.descripcion', defaultContent: '-' },
            { data: 'estado_trash', render: (estado) => String(estado) === '1' ? '<span class="badge text-bg-success">Activo</span>' : '<span class="badge text-bg-secondary">Eliminado</span>' },
        ],
    });

    $('#btn-nueva-llegada-empresa').on('click', prepararNuevaLlegadaEmpresa);
    $('#incluir-eliminados-llegada-empresa').on('change', () => tablaLlegadaEmpresa.ajax.reload());
    $('#form-llegada-empresa').on('submit', guardarLlegadaEmpresa);
    $('#tabla-llegada-empresa tbody').on('click', '.js-editar-llegada-empresa', function () { editarLlegadaEmpresa($(this).data('id')); });
    $('#tabla-llegada-empresa tbody').on('click', '.js-eliminar-llegada-empresa', function () { eliminarLlegadaEmpresa($(this).data('id')); });
    $('#tabla-llegada-empresa tbody').on('click', '.js-restaurar-llegada-empresa', function () { restaurarLlegadaEmpresa($(this).data('id')); });
});

function prepararNuevaLlegadaEmpresa() {
    const formulario = document.getElementById('form-llegada-empresa');
    formulario.reset();
    formulario.classList.remove('was-validated');
    $('#llegada_empresa_id').val('');
    $('#modal-llegada-empresa-label').text(`Nueva ${llegadaEmpresaConfig.singular}`);
    $('#btn-guardar-llegada-empresa').html('<i class="ri-save-line me-1"></i>Guardar');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-llegada-empresa')).show();
}

function editarLlegadaEmpresa(id) {
    $.get(llegadaEmpresaUrl(`/${id}/show`), (respuesta) => {
        const empresa = respuesta.data || {};
        $('#llegada_empresa_id').val(empresa.idllegada_por_empresa || '');
        $('#llegada_empresa_descripcion').val(empresa.descripcion || '');
        document.getElementById('form-llegada-empresa').classList.remove('was-validated');
        $('#modal-llegada-empresa-label').text(`Editar ${llegadaEmpresaConfig.singular}`);
        $('#btn-guardar-llegada-empresa').html('<i class="ri-save-line me-1"></i>Actualizar');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-llegada-empresa')).show();
    }).fail((xhr) => mostrarErrorLlegadaEmpresa(xhr));
}

function guardarLlegadaEmpresa(evento) {
    evento.preventDefault();
    const formulario = evento.currentTarget;
    if (!formulario.checkValidity()) {
        formulario.classList.add('was-validated');
        return;
    }

    const id = $('#llegada_empresa_id').val();
    const $boton = $('#btn-guardar-llegada-empresa');
    const contenidoOriginal = $boton.html();
    $boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Guardando...');

    $.ajax({
        url: llegadaEmpresaUrl(id ? `/${id}/update` : '/store'),
        type: id ? 'PUT' : 'POST',
        headers: llegadaEmpresaHeaders(),
        data: $(formulario).serialize(),
        success: (respuesta) => {
            bootstrap.Modal.getInstance(document.getElementById('modal-llegada-empresa'))?.hide();
            tablaLlegadaEmpresa.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Correcto', text: respuesta.message || 'Registro guardado.', timer: 1800, showConfirmButton: false });
        },
        error: (xhr) => mostrarErrorLlegadaEmpresa(xhr),
        complete: () => $boton.prop('disabled', false).html(contenidoOriginal),
    });
}

function eliminarLlegadaEmpresa(id) {
    Swal.fire({ title: `¿Eliminar ${llegadaEmpresaConfig.singular.toLowerCase()}?`, text: 'El registro se enviará a papelera.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' }).then((resultado) => {
        if (!resultado.isConfirmed) return;
        $.ajax({
            url: llegadaEmpresaUrl(`/${id}`), type: 'DELETE', headers: llegadaEmpresaHeaders(),
            success: (respuesta) => { tablaLlegadaEmpresa.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Eliminado', text: respuesta.message || 'Registro enviado a papelera.', timer: 1800, showConfirmButton: false }); },
            error: (xhr) => mostrarErrorLlegadaEmpresa(xhr),
        });
    });
}

function restaurarLlegadaEmpresa(id) {
    $.ajax({
        url: llegadaEmpresaUrl(`/${id}/restore`), type: 'POST', headers: llegadaEmpresaHeaders(),
        success: (respuesta) => { tablaLlegadaEmpresa.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Restaurado', text: respuesta.message || 'Registro restaurado.', timer: 1800, showConfirmButton: false }); },
        error: (xhr) => mostrarErrorLlegadaEmpresa(xhr),
    });
}

function mostrarErrorLlegadaEmpresa(xhr) {
    const errores = xhr?.responseJSON?.errors;
    const mensaje = errores ? Object.values(errores).flat().join('\n') : (xhr?.responseJSON?.message || 'No se pudo completar la operación.');
    Swal.fire('Error', mensaje, 'error');
}
