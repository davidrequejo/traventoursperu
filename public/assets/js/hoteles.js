let tabla_hotel;
let hotelHabitacionIndice = 0;

const hotelUrl = (p) =>
    `${document.querySelector('meta[name="app-url"]')?.content || ""}${p}`;
const hotelHeaders = () => ({
    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content,
    Accept: "application/json",
});

$(async function () {
    await cargarTipos();
    inicializarSelectsHotel();
    inicializarTablaHotel();
    registrarEventosHotel();
});

function inicializarSelectsHotel() {
    $("#select_hotel_tipo,#estrella,#hotel_persona_tipo,#hotel_persona_tipo_documento").select2({
        theme: "bootstrap4",
        dropdownParent: $("#modal-hotel"),
        width: "100%",
    });

    $("#hotel_persona_iddistrito").select2({
        theme: "bootstrap4",
        width: "100%",
        dropdownParent: $("#modal-hotel"),
        ajax: {
            url: hotelUrl("/hoteles/distritos"),
            dataType: "json",
            delay: 250,
            headers: hotelHeaders(),
            data: (p) => ({ term: p.term || "" }),
            processResults: (d) => d,
        },
    });
}

function inicializarTablaHotel() {
    tabla_hotel = $("#tabla-hotel").DataTable({
        responsive: true,
        processing: true,
        deferRender: true,
        searchDelay: 350,
        dom: "<'row'<'col-md-8 pt-2'f><'col-md-4 pt-2 d-flex justify-content-end'<'length'l><'buttons'B>>>r t <'row'<'col-md-12'p>>",
        language: { lengthMenu: "_MENU_" },
        buttons: [
            { text: '<i class="bi bi-arrow-clockwise"></i>', className: 'buttons-reload btn btn-outline-info', action: (_e, dt) => dt.ajax.reload(null, false) },
            { extend: 'excel', exportOptions: { columns: [1,2,3,4,5,6,7] }, title: 'Listado de hoteles', text: '<i class="bi bi-file-earmark-excel"></i>', className: 'btn btn-outline-success' },
        ],
        ajax: {
            url: hotelUrl("/hoteles/listar"),
            dataSrc: (r) => r.data || [],
        },
        columns: [
            {
                data: null,
                render: (r) =>
                    `<button type="button" class="btn btn-sm btn-info ver-detalle" data-id="${r.idhotel}" title="Ver detalle" aria-label="Ver detalle"><i class="ri-eye-line"></i></button> <button type="button" class="btn btn-sm btn-primary editar" data-id="${r.idhotel}" title="Editar" aria-label="Editar"><i class="ri-edit-line"></i></button> <button type="button" class="btn btn-sm btn-danger eliminar" data-id="${r.idhotel}" title="Eliminar" aria-label="Eliminar"><i class="ri-delete-bin-line"></i></button>`,
            },
            { data: "persona.descripcion" },
            { data: "persona.numero_documento" },
            { data: "persona.direccion" },
            { data: "persona.celular" },
            { data: "persona.correo" },
            { data: "tipo.nombre" },
            { data: "persona.distrito.nombre", defaultContent: "-" },
        ],
    });
}

function registrarEventosHotel() {
    $("#btn-nuevo-hotel").on("click", abrirNuevoHotel);
    $("#btn-guardar-hotel").on("click", () => $("#form-agregar-hoteles").trigger("submit"));
    $("#btn-recargar-hoteles").on("click", () => tabla_hotel?.ajax.reload(null, false));
    $("#form-agregar-hoteles").on("submit", guardar);
    $("#btn-buscar-persona-hotel").on("click", buscarDocumentoPersonaHotel);
    $("#hotel_persona_numero_documento,#hotel_persona_tipo_documento").on("input change", () => {
        $("#hotel_idpersona").val("");
        ocultarAlertaBusquedaPersonaHotel();
    });

    $("#tabla-hotel").on("click", ".editar", function () {
        editar($(this).data("id"));
    });
    $("#tabla-hotel").on("click", ".ver-detalle", function () {
        verDetalleHotel($(this).data("id"));
    });
    $("#tabla-hotel").on("click", ".eliminar", eliminarHotel);

    $(document)
        .off("click.hotelHabitacion", "#agregar-habitacion")
        .on("click.hotelHabitacion", "#agregar-habitacion", () => agregarHabitacion())
        .off("click.hotelHabitacion", ".quitar-habitacion")
        .on("click.hotelHabitacion", ".quitar-habitacion", function () {
            $(this).closest(".hotel-habitacion").remove();
        });

    registrarEventosTiposHotel();
}

async function cargarTipos() {
    const r = await $.ajax({
        url: hotelUrl("/hoteles/catalogos"),
        headers: hotelHeaders(),
    });
    $("#select_hotel_tipo").html(
        r.data.tipos.map(
            (x) => `<option value="${x.idhotel_tipo}">${x.nombre}</option>`,
        ),
    ).val(null).trigger("change");
}

function abrirNuevoHotel() {
    $("#form-agregar-hoteles")[0].reset();
    $("#idhotel,#hotel_idpersona").val("");
    $("#estrella,#select_hotel_tipo,#hotel_persona_iddistrito").val(null).trigger("change");
    $("#hotel_persona_tipo").val("JURIDICA").trigger("change");
    $("#hotel_persona_tipo_documento").val("6").trigger("change");
    ocultarAlertaBusquedaPersonaHotel();
    $("#hotel-habitaciones").empty();
    hotelHabitacionIndice = 0;
    $("#modal-hotel-label").text("Nuevo Hotel");
    $("#modal-hotel").modal("show");
}

function editar(id) {
    $.get(hotelUrl(`/hoteles/${id}/show`), (r) => {
        const x = r.data;
        $("#idhotel").val(x.idhotel);
        $("#select_hotel_tipo").val(x.idhotel_tipo).trigger("change");
        $("#estrella").val(x.estrellas).trigger("change");
        $("#tarifa_x_pers_paq").val(x.tarifa_x_pers_paq);
        $("#check_in").val(x.check_in);
        $("#check_out").val(x.check_out);
        $("#descripcion").val(x.descripcion);
        $("#gogle_maps").val(x.gogle_maps);
        cargarPersonaHotel(x.persona);
        $("#hotel-habitaciones").empty();
        hotelHabitacionIndice = 0;
        (x.habitaciones || []).forEach(agregarHabitacion);
        ocultarAlertaBusquedaPersonaHotel();
        $("#modal-hotel-label").text("Editar Hotel");
        $("#modal-hotel").modal("show");
    });
}

function cargarPersonaHotel(persona) {
    if (!persona) return;
    $("#hotel_idpersona").val(persona.idpersona || "");
    $("#hotel_persona_tipo").val(persona.tipo_persona_sunat || "JURIDICA").trigger("change");
    $("#hotel_persona_tipo_documento").val(String(persona.tipo_documento || "6")).trigger("change");
    $("#hotel_persona_numero_documento").val(persona.numero_documento || "");
    $("#hotel_persona_descripcion").val(persona.descripcion || "");
    $("#hotel_persona_celular").val(persona.celular || "");
    $("#hotel_persona_correo").val(persona.correo || "");
    $("#hotel_persona_direccion").val(persona.direccion || "");

    if (persona.distrito) {
        $("#hotel_persona_iddistrito").empty().append(
            new Option(persona.distrito.nombre, persona.iddistrito, true, true),
        ).trigger("change");
    } else {
        $("#hotel_persona_iddistrito").val(null).trigger("change");
    }
}

function guardar(e) {
    e.preventDefault();
    const id = $("#idhotel").val();
    const $botonGuardar = $("#btn-guardar-hotel");

    if ($botonGuardar.prop("disabled")) return;

    const textoBotonGuardar = $botonGuardar.html();
    $botonGuardar.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Guardando...');

    $.ajax({
        url: hotelUrl(id ? `/hoteles/${id}/update` : "/hoteles/store"),
        type: id ? "PUT" : "POST",
        headers: hotelHeaders(),
        data: $(this).serialize(),
        success: (r) => {
            $("#modal-hotel").modal("hide");
            tabla_hotel.ajax.reload();
            Swal.fire("Correcto", r.message || "Hotel guardado", "success");
        },
        error: (x) => Swal.fire("Error", mensajeErrorHotel(x), "error"),
        complete: () => $botonGuardar.prop("disabled", false).html(textoBotonGuardar),
    });
}

function mensajeErrorHotel(xhr) {
    const errores = xhr.responseJSON?.errors || xhr.responseJSON?.data;
    if (errores && typeof errores === "object") {
        const primerError = Object.values(errores).flat()[0];
        if (primerError) return primerError;
    }

    return xhr.responseJSON?.message || "No se pudo guardar";
}

function eliminarHotel() {
    const id = $(this).data("id");
    Swal.fire({
        title: "Eliminar hotel?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Eliminar",
    }).then((r) => {
        if (!r.isConfirmed) return;
        $.ajax({
            url: hotelUrl("/hoteles/" + id),
            type: "DELETE",
            headers: hotelHeaders(),
            success: () => tabla_hotel.ajax.reload(),
        });
    });
}

function escaparHtmlHotel(valor) {
    return $("<div>").text(valor ?? "-").html();
}

function monedaHotel(valor) {
    const numero = Number(valor);
    return Number.isFinite(numero)
        ? new Intl.NumberFormat("es-PE", { style: "currency", currency: "PEN" }).format(numero)
        : "-";
}

function filaDetalleHotel(etiqueta, valor) {
    return `<div class="col-6 mb-3"><small class="text-body-secondary d-block">${escaparHtmlHotel(etiqueta)}</small><span class="fw-semibold">${escaparHtmlHotel(valor || "-")}</span></div>`;
}

function verDetalleHotel(id) {
    $.get(hotelUrl(`/hoteles/${id}/show`), (respuesta) => {
        const hotel = respuesta.data || {};
        const persona = hotel.persona || {};
        const habitaciones = hotel.habitaciones || [];
        $("#detalle-hotel-subtitulo").text(`${hotel.tipo?.nombre || "Hotel"} - ${hotel.estrellas || "Sin clasificacion"}`);
        $("#detalle-hotel-nombre").text(persona.descripcion || "Hotel sin persona asignada");
        $("#detalle-hotel-tipo-badge").text(hotel.tipo?.nombre || "Sin tipo");
        $("#detalle-hotel-tarifa-principal").text(monedaHotel(hotel.tarifa_x_pers_paq));
        $("#detalle-hotel-estrellas-resumen").text(hotel.estrellas || "-");
        $("#detalle-hotel-checkin-resumen").text(hotel.check_in || "-");
        $("#detalle-hotel-checkout-resumen").text(hotel.check_out || "-");
        $("#detalle-hotel-informacion").html(`
            <div class="row">
                ${filaDetalleHotel("Tipo", hotel.tipo?.nombre)}
                ${filaDetalleHotel("Estrellas", hotel.estrellas)}
                ${filaDetalleHotel("Check in", hotel.check_in)}
                ${filaDetalleHotel("Check out", hotel.check_out)}
                <div class="col-12 mb-3"><small class="text-body-secondary d-block">Tarifa x persona / paquete</small><span class="fw-semibold text-primary">${monedaHotel(hotel.tarifa_x_pers_paq)}</span></div>
                <div class="col-12"><small class="text-body-secondary d-block">Descripcion</small><span>${escaparHtmlHotel(hotel.descripcion || "Sin descripcion")}</span></div>
            </div>
        `);
        $("#detalle-hotel-persona").html(`
            <div class="row">
                ${filaDetalleHotel("Razon social / Nombre", persona.descripcion)}
                ${filaDetalleHotel("Documento", persona.numero_documento)}
                ${filaDetalleHotel("Celular", persona.celular)}
                ${filaDetalleHotel("Correo", persona.correo)}
                <div class="col-12 mb-3"><small class="text-body-secondary d-block">Direccion</small><span class="fw-semibold">${escaparHtmlHotel(persona.direccion || "-")}</span></div>
                <div class="col-12"><small class="text-body-secondary d-block">Distrito</small><span class="fw-semibold">${escaparHtmlHotel(persona.distrito?.nombre || "-")}</span></div>
            </div>
        `);
        $("#detalle-hotel-total-habitaciones").text(habitaciones.length);
        $("#detalle-hotel-habitaciones").html(habitaciones.length ? habitaciones.map((habitacion) => `
            <tr>
                <td class="fw-semibold">${escaparHtmlHotel(habitacion.nombre)}</td>
                <td>${escaparHtmlHotel(habitacion.cant_huespeds)}</td>
                <td>${monedaHotel(habitacion.precio_coorporativo)}</td>
                <td>${monedaHotel(habitacion.precio_normal)}</td>
                <td>${monedaHotel(habitacion.precio_temp_alta)}</td>
                <td>${escaparHtmlHotel(habitacion.descripcion || "-")}</td>
            </tr>
        `).join("") : '<tr><td colspan="6" class="text-center text-body-secondary py-4">No hay habitaciones registradas.</td></tr>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById("modal-detalle-hotel")).show();
    }).fail((xhr) => Swal.fire("Error", xhr.responseJSON?.message || "No se pudo obtener el detalle del hotel.", "error"));
}

function valorCampoHabitacion(valor) {
    return $("<div>").text(valor ?? "").html();
}

function agregarHabitacion(h = {}) {
    const indice = hotelHabitacionIndice++;
    $("#hotel-habitaciones").append(`
        <div class="hotel-habitacion border rounded p-3 mb-3">
            <input type="hidden" name="habitaciones[${indice}][idhotel_habitacion]" value="${valorCampoHabitacion(h.idhotel_habitacion)}">
            <div class="row g-2 align-items-end">
                <div class="col-md-4 col-xl">
                    <label class="form-label mb-1">Nombre</label>
                    <textarea class="form-control" name="habitaciones[${indice}][nombre]" rows="1" placeholder="Ej. Matrimonial">${valorCampoHabitacion(h.nombre)}</textarea>
                </div>
                <div class="col-md-4 col-xl">
                    <label class="form-label mb-1">Huespedes</label>
                    <input type="number" min="1" class="form-control" name="habitaciones[${indice}][cant_huespeds]" value="${valorCampoHabitacion(h.cant_huespeds)}">
                </div>
                <div class="col-md-4 col-xl">
                    <label class="form-label mb-1">Precio corporativo</label>
                    <input type="number" min="0" step="0.01" class="form-control" name="habitaciones[${indice}][precio_coorporativo]" value="${valorCampoHabitacion(h.precio_coorporativo)}">
                </div>
                <div class="col-md-4 col-xl">
                    <label class="form-label mb-1">Precio normal</label>
                    <input type="number" min="0" step="0.01" class="form-control" name="habitaciones[${indice}][precio_normal]" value="${valorCampoHabitacion(h.precio_normal)}">
                </div>
                <div class="col-md-4 col-xl">
                    <label class="form-label mb-1">Precio temp. alta</label>
                    <input type="number" min="0" step="0.01" class="form-control" name="habitaciones[${indice}][precio_temp_alta]" value="${valorCampoHabitacion(h.precio_temp_alta)}">
                </div>
                <div class="col-md-4 col-xl">
                    <label class="form-label mb-1">Observacion</label>
                    <textarea class="form-control" name="habitaciones[${indice}][descripcion]" rows="1">${valorCampoHabitacion(h.descripcion)}</textarea>
                </div>
                <div class="col-md-4 col-xl-auto">
                    <button type="button" class="btn btn-sm btn-outline-danger quitar-habitacion"><i class="ri-delete-bin-line"></i></button>
                </div>
            </div>
        </div>
    `);
}

function registrarEventosTiposHotel() {
    $(document).on("click", "#nuevo-hotel-tipo", function () {
        Swal.fire({
            target: document.getElementById("modal-hotel"),
            title: "Nuevo tipo de hotel",
            input: "text",
            showCancelButton: true,
            confirmButtonText: "Guardar",
            didOpen: () => Swal.getInput()?.focus(),
        }).then((r) => {
            if (!r.isConfirmed || !r.value.trim()) return;
            $.ajax({
                url: hotelUrl("/hoteles/tipos/store"),
                type: "POST",
                headers: hotelHeaders(),
                data: { nombre: r.value.trim() },
                success: async (d) => {
                    await cargarTipos();
                    $("#select_hotel_tipo").val(String(d.data.idhotel_tipo)).trigger("change");
                },
            });
        });
    });

    $(document).on("click", "#editar-hotel-tipo", function () {
        const id = $("#select_hotel_tipo").val();
        if (!id) return;
        const nombre = $("#select_hotel_tipo option:selected").text();
        Swal.fire({
            title: "Editar tipo",
            input: "text",
            inputValue: nombre,
            showCancelButton: true,
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: hotelUrl("/hoteles/tipos/" + id),
                type: "PUT",
                headers: hotelHeaders(),
                data: { nombre: r.value },
                success: async () => {
                    await cargarTipos();
                    $("#select_hotel_tipo").val(id).trigger("change");
                },
            });
        });
    });
}

function mostrarAlertaBusquedaPersonaHotel(mensaje, tipo = "info") {
    $("#hotel-persona-busqueda-alerta")
        .removeClass("d-none text-info text-success text-warning text-danger")
        .addClass(`text-${tipo}`)
        .text(mensaje);
}

function ocultarAlertaBusquedaPersonaHotel() {
    $("#hotel-persona-busqueda-alerta").addClass("d-none").text("");
}

function iniciarBusquedaPersonaHotel() {
    const $boton = $("#btn-buscar-persona-hotel");
    $boton.data("contenido-original", $boton.html()).prop("disabled", true)
        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
}

function finalizarBusquedaPersonaHotel() {
    const $boton = $("#btn-buscar-persona-hotel");
    $boton.prop("disabled", false).html($boton.data("contenido-original") || '<i class="ri-search-line"></i>');
}

function completarPersonaHotel(persona) {
    cargarPersonaHotel(persona);
}

function datosConsultaHotel(response) {
    let datos = response?.data;
    if (datos?.data && typeof datos.data === "object" && !Array.isArray(datos.data)) {
        datos = datos.data;
    }
    return datos && typeof datos === "object" && !Array.isArray(datos) ? datos : null;
}

function consultarDocumentoExternoHotel(numeroDocumento, tipoDocumento) {
    const esDni = tipoDocumento === "1";
    const url = esDni ? "/reniec/dni" : "/sunat/ruc";
    const parametro = esDni ? { dni: numeroDocumento } : { ruc: numeroDocumento };

    return $.getJSON(hotelUrl(url), parametro).done((response) => {
        const datos = datosConsultaHotel(response);
        if (!response?.status || !datos) {
            mostrarAlertaBusquedaPersonaHotel(response?.message || "No se encontraron datos para el documento.", "warning");
            return;
        }

        $("#hotel_idpersona").val("");
        if (esDni) {
            const nombre = [datos.nombres || datos.nombre, datos.apellido_paterno || datos.apellidoPaterno, datos.apellido_materno || datos.apellidoMaterno]
                .filter(Boolean)
                .join(" ");
            $("#hotel_persona_descripcion").val(nombre || datos.nombre_completo || datos.nombreCompleto || datos.nombre || "");
            $("#hotel_persona_tipo").val("NATURAL").trigger("change");
        } else {
            const razonSocial = datos.razonSocial || datos.nombre_o_razon_social || datos.razon_social || datos.nombre_completo || datos.nombre || "";
            $("#hotel_persona_descripcion").val(razonSocial);
        }

        $("#hotel_persona_direccion").val(datos.direccion || datos.direccion_completa || "");
        mostrarAlertaBusquedaPersonaHotel("Datos encontrados y cargados en el formulario.", "success");
    }).fail((xhr) => {
        mostrarAlertaBusquedaPersonaHotel(
            xhr?.responseJSON?.message || "No se pudieron consultar los datos del documento.",
            "danger",
        );
    });
}

function buscarDocumentoPersonaHotel() {
    const numeroDocumento = $("#hotel_persona_numero_documento").val().trim();
    const tipoDocumento = $("#hotel_persona_tipo_documento").val();
    const esDocumentoValido = tipoDocumento === "1" ? /^\d{8}$/.test(numeroDocumento) : /^\d{11}$/.test(numeroDocumento);

    if (!esDocumentoValido) {
        mostrarAlertaBusquedaPersonaHotel(
            tipoDocumento === "1" ? "Ingrese un DNI valido de 8 digitos." : "Ingrese un RUC valido de 11 digitos.",
            "warning",
        );
        return;
    }

    ocultarAlertaBusquedaPersonaHotel();
    iniciarBusquedaPersonaHotel();
    $.ajax({
        url: hotelUrl("/hoteles/personas/buscar-documento"),
        type: "POST",
        headers: hotelHeaders(),
        data: { numero_documento: numeroDocumento },
    }).done((respuesta) => {
        const persona = respuesta.data?.persona;
        if (persona) {
            completarPersonaHotel(persona);
            mostrarAlertaBusquedaPersonaHotel("Persona encontrada. Se actualizara y vinculara como hotel al guardar.", "success");
            return;
        }
        mostrarAlertaBusquedaPersonaHotel("No se encontro en la base de datos. Consultando SUNAT / RENIEC...", "info");
        consultarDocumentoExternoHotel(numeroDocumento, tipoDocumento);
    }).fail((xhr) => {
        mostrarAlertaBusquedaPersonaHotel(
            xhr?.responseJSON?.message || "No se pudo validar el documento en la base de datos.",
            "danger",
        );
    }).always(finalizarBusquedaPersonaHotel);
}
