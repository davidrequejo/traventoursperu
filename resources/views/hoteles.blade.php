<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
    @include('layouts.yn_head', ['title_page' => 'Hoteles'])
    <style>
        #tabla-hotel_filter {
            width: calc(100% - 10px) !important;
            display: flex !important;
            justify-content: space-between !important;
        }

        #tabla-hotel_filter label,
        #tabla-hotel_filter label input {
            width: 100% !important;
        }

        [data-theme-mode="dark"] #modal-detalle-hotel .modal-content {
            background-color: rgb(var(--body-bg-rgb)) !important;
            color: var(--default-text-color) !important;
            border-color: var(--default-border) !important;
        }

        [data-theme-mode="dark"] #modal-detalle-hotel .modal-body {
            background-color: var(--default-body-bg-color) !important;
        }

        [data-theme-mode="dark"] #modal-detalle-hotel .card,
        [data-theme-mode="dark"] #modal-detalle-hotel .modal-footer {
            background-color: rgb(var(--body-bg-rgb)) !important;
            color: var(--default-text-color) !important;
            border-color: var(--default-border) !important;
        }

        [data-theme-mode="dark"] #modal-detalle-hotel .card-header,
        [data-theme-mode="dark"] #modal-detalle-hotel thead {
            background-color: var(--default-background) !important;
            color: var(--default-text-color) !important;
            border-color: var(--default-border) !important;
        }

        [data-theme-mode="dark"] #modal-detalle-hotel .text-body-secondary {
            color: var(--text-muted) !important;
        }

        [data-theme-mode="dark"] #modal-detalle-hotel .table {
            --bs-table-color: var(--default-text-color);
            --bs-table-bg: rgb(var(--body-bg-rgb));
            --bs-table-striped-bg: rgba(255, 255, 255, 0.04);
            --bs-table-hover-bg: rgba(255, 255, 255, 0.07);
            --bs-table-border-color: var(--default-border);
        }

        .label-action-btn {
            width: 24px;
            height: 24px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 0;
        }
    </style>
</head>

<body id="body-hoteles">
    @include('layouts.yn_switcher')
    @include('layouts.yn_loader')
    <div class="page">
        @include('layouts.yn_header')
        @include('layouts.yn_sidebar')
        <div class="main-content app-content">
            <div class="container-fluid">
                <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                    <div>
                        <p class="fw-semibold fs-18 mb-0 title-body-pagina">Hoteles</p>
                        <nav>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                                <li class="breadcrumb-item active">Hoteles</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="btn-list mt-md-0 mt-2">
                        @permiso('hoteles', 'crear')
                            <button type="button" class="btn btn-primary m-r-10px" id="btn-nuevo-hotel"><i
                                    class="ri-add-line me-2"></i>Agregar</button>
                        @endpermiso
                        <button type="button" class="btn btn-light label-btn m-r-10px" id="btn-recargar-hoteles"><i
                                class="bi bi-arrow-repeat label-btn-icon me-2"></i>Actualizar</button>
                    </div>
                </div>
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-hotel" class="table table-bordered table-striped w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center">OP</th>
                                        <th>Nombre / Razón social</th>
                                        <th>RUC</th>
                                        <th>Direccion</th>
                                        <th>Celular</th>
                                        <th>Correo</th>
                                        <th>Tipo</th>
                                        <th>Distrito</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade backdrop-filter-3px" id="modal-hotel" tabindex="-1" aria-labelledby="modal-hotel-label"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="modal-hotel-label">Nuevo Hotel</h6><button type="button"
                        class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="form-agregar-hoteles" method="POST"><input type="hidden" id="idhotel">
                        <div class="row gy-3">
                            <input type="hidden" name="idpersona" id="hotel_idpersona">
                            <div class="col-12">
                                <h6 class="fw-semibold mb-1">Datos comerciales del hotel</h6>
                            </div>
                            <div class="col-md-3"><label class="form-label">Tipo persona <sup class="text-danger">*</sup></label><select
                                    class="form-control" name="persona[tipo_persona_sunat]" id="hotel_persona_tipo" required>
                                    <option value="JURIDICA">Juridica</option>
                                    <option value="NATURAL">Natural</option>
                                </select></div>
                            <div class="col-md-3"><label class="form-label">Tipo documento <sup class="text-danger">*</sup></label><select
                                    class="form-control" name="persona[tipo_documento]" id="hotel_persona_tipo_documento" required>
                                    <option value="6">RUC</option>
                                    <option value="1">DNI</option>
                                </select></div>
                            <div class="col-md-6"><label class="form-label">Documento / RUC <sup class="text-danger">*</sup></label>
                                <div class="input-group"><input class="form-control" name="persona[numero_documento]"
                                        id="hotel_persona_numero_documento" required><button class="btn btn-primary"
                                        type="button" id="btn-buscar-persona-hotel"><i
                                            class="ri-search-line"></i></button></div>
                                <div id="hotel-persona-busqueda-alerta" class="form-text mt-1 d-none" role="status"></div>
                            </div>
                            <div class="col-md-12"><label class="form-label">Razon social / Nombre <sup class="text-danger">*</sup></label><input
                                    class="form-control" name="persona[descripcion]" id="hotel_persona_descripcion" required></div>
                            <div class="col-md-3"><label class="form-label">Celular</label><input
                                    class="form-control" name="persona[celular]" id="hotel_persona_celular"></div>
                            <div class="col-md-3"><label class="form-label">Correo</label><input class="form-control"
                                    name="persona[correo]" id="hotel_persona_correo"></div>
                            <div class="col-md-3"><label class="form-label">Direccion</label><input
                                    class="form-control" name="persona[direccion]" id="hotel_persona_direccion"></div>
                            <div class="col-md-3"><label class="form-label">Distrito</label><select
                                    class="form-control" name="persona[iddistrito]" id="hotel_persona_iddistrito"
                                    style="width:100%"></select></div>
                            <div class="col-12 pt-2">
                                <h6 class="fw-semibold mb-1">Datos operativos</h6>
                            </div>
                            <div class="col-xl-4"><label class="form-label"><span
                                        class="badge bg-success m-r-4px cursor-pointer" id="nuevo-hotel-tipo"
                                        data-bs-toggle="tooltip" aria-label="Agregar"
                                        data-bs-original-title="Agregar"><i class="las la-plus"></i></span>
                                    @permiso('hoteles', 'editar')
                                        <span class="badge bg-warning m-r-4px cursor-pointer" id="editar-hotel-tipo"
                                            data-bs-toggle="tooltip" aria-label="Editar" data-bs-original-title="Editar"><i
                                                class="ri-edit-line"></i></span>
                                    @endpermiso Tipo de hotel <sup
                                        class="text-danger">*</sup></label><select class="form-control"
                                    name="idhotel_tipo" id="select_hotel_tipo" style="width:100%" required></select>
                            </div>
                            <div class="col-xl-2"><label class="form-label">Tarifa x persona / paquete</label><input type="number" min="0" step="0.01" class="form-control" name="tarifa_x_pers_paq" id="tarifa_x_pers_paq"></div>
                            <div class="col-xl-2"><label class="form-label">Estrellas</label><select
                                    class="form-control" name="estrellas" id="estrella">
                                    <option>1 Estrella</option>
                                    <option>2 Estrellas</option>
                                    <option>3 Estrellas</option>
                                    <option>4 Estrellas</option>
                                    <option>5 Estrellas</option>
                                </select></div>
                            <div class="col-xl-3"><label class="form-label">Check in</label><input type="time"
                                    class="form-control" name="check_in" id="check_in"></div>
                            <div class="col-xl-3"><label class="form-label">Check out</label><input type="time"
                                    class="form-control" name="check_out" id="check_out"></div>
                            <div class="col-12"><label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" id="descripcion" rows="3"></textarea>
                            </div>
                            <div class="col-12"><label class="form-label">Google Maps</label>
                                <textarea class="form-control" name="gogle_maps" id="gogle_maps" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center"><label
                                        class="form-label mb-0">Habitaciones</label><button type="button"
                                        class="btn btn-sm btn-primary" id="agregar-habitacion"><i
                                            class="ri-add-line"></i> Agregar</button></div>
                                <div id="hotel-habitaciones" class="mt-2"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light"
                        data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary"
                        id="btn-guardar-hotel">Guardar</button></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal-detalle-hotel" tabindex="-1" aria-labelledby="modal-detalle-hotel-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-body text-body border shadow-sm">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25 p-2"><i class="ri-hotel-line fs-4"></i></span>
                        <div>
                            <h5 class="modal-title mb-0" id="modal-detalle-hotel-label">Detalle del hotel</h5>
                            <small id="detalle-hotel-subtitulo" class="text-white text-opacity-75"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body bg-body-tertiary p-3 p-lg-4">
                    <div class="card bg-body border border-primary-subtle shadow-sm mb-3">
                        <div class="card-body p-3 p-lg-4">
                            <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary p-3"><i class="ri-building-4-line fs-3"></i></span>
                                    <div>
                                        <div class="text-body-secondary small">Hotel registrado</div>
                                        <h4 class="mb-1" id="detalle-hotel-nombre">-</h4>
                                        <span class="badge text-bg-primary" id="detalle-hotel-tipo-badge">-</span>
                                    </div>
                                </div>
                                <div class="text-md-end">
                                    <div class="text-body-secondary small">Tarifa por persona / paquete</div>
                                    <div class="fs-4 fw-bold text-primary" id="detalle-hotel-tarifa-principal">-</div>
                                </div>
                            </div>
                            <hr class="my-3">
                            <div class="row g-3 text-center">
                                <div class="col-4"><div class="text-body-secondary small">Estrellas</div><div class="fw-semibold" id="detalle-hotel-estrellas-resumen">-</div></div>
                                <div class="col-4 border-start border-end"><div class="text-body-secondary small">Check in</div><div class="fw-semibold" id="detalle-hotel-checkin-resumen">-</div></div>
                                <div class="col-4"><div class="text-body-secondary small">Check out</div><div class="fw-semibold" id="detalle-hotel-checkout-resumen">-</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="card h-100 bg-body border shadow-sm">
                                <div class="card-header bg-body-tertiary border-bottom d-flex align-items-center gap-2"><i class="ri-building-line text-primary"></i><strong>Información del hotel</strong></div>
                                <div class="card-body" id="detalle-hotel-informacion"></div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card h-100 bg-body border shadow-sm">
                                <div class="card-header bg-body-tertiary border-bottom d-flex align-items-center gap-2"><i class="ri-user-line text-primary"></i><strong>Persona / razón social</strong></div>
                                <div class="card-body" id="detalle-hotel-persona"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card bg-body border shadow-sm overflow-hidden">
                                <div class="card-header bg-body-tertiary border-bottom d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center gap-2"><i class="ri-hotel-bed-line text-primary"></i><strong>Habitaciones</strong></div>
                                    <span class="badge rounded-pill text-bg-primary" id="detalle-hotel-total-habitaciones">0</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped align-middle mb-0">
                                        <thead>
                                            <tr><th class="ps-3">Nombre</th><th>Huéspedes</th><th>Corporativo</th><th>Normal</th><th>Temp. alta</th><th class="pe-3">Observación</th></tr>
                                        </thead>
                                        <tbody id="detalle-hotel-habitaciones"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-body border-top"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="ri-close-line me-1"></i>Cerrar</button></div>
            </div>
        </div>
    </div>
    @include('layouts.yn_scripts')
    @include('layouts.yn_custom_switcherjs')
    <script src="{{ asset('assets/js/hoteles.js') }}?v={{ filemtime(public_path('assets/js/hoteles.js')) }}"></script>
</body>

</html>
