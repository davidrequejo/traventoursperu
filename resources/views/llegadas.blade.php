<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">
<head>
    @include('layouts.yn_head', ['title_page' => 'Llegadas'])
    <style>
        .llegadas-wrapper { max-width: 1180px; margin: 0 auto; }
        .llegadas-toolbar { display: grid; grid-template-columns: 132px minmax(180px, 270px) 1fr auto; gap: 12px; align-items: end; }
        .llegadas-day-nav { display: flex; gap: 8px; }
        .llegadas-heading { border: 1px solid rgba(var(--primary-rgb), .18); background: rgba(var(--primary-rgb), .12); color: var(--primary-color); border-radius: 8px; padding: 18px 20px; font-weight: 800; }
        .llegadas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 16px; }
        .llegada-card { border: 1px solid var(--default-border); border-radius: 8px; background: var(--custom-white); box-shadow: 0 8px 20px rgba(15, 23, 42, .06); }
        .llegada-card-body { padding: 18px; }
        .llegada-card-top { display: flex; justify-content: space-between; gap: 8px; align-items: center; margin-bottom: 12px; }
        .llegada-info { background: var(--default-background); border-radius: 8px; padding: 14px; margin-top: 12px; }
        .llegada-line { display: flex; gap: 8px; align-items: center; margin-bottom: 4px; color: var(--default-text-color); }
        .llegada-line i { color: rgb(var(--info-rgb)); }
        .llegada-empty { border: 1px dashed var(--default-border); border-radius: 8px; padding: 44px 16px; text-align: center; color: var(--text-muted); background: var(--custom-white); }
        @media (max-width: 767.98px) {
            .llegadas-toolbar { grid-template-columns: 1fr; }
            .llegadas-toolbar .btn-list { justify-content: flex-start !important; }
            .llegadas-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body id="body-llegadas" data-fecha-actual="{{ $fecha_actual }}">
    @include('layouts.yn_switcher')
    @include('layouts.yn_loader')

    <div class="page">
        @include('layouts.yn_header')
        @include('layouts.yn_sidebar')

        <div class="main-content app-content">
            <div class="container-fluid">
                <div class="llegadas-wrapper">
                    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                        <div>
                            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Llegadas</p>
                            <span class="fs-12 text-muted detalle-body-pagina">Reservas con llegada programada por fecha.</span>
                        </div>
                        <nav class="mt-md-0 mt-2">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Llegadas</li>
                            </ol>
                        </nav>
                    </div>

                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="llegadas-toolbar">
                                <div>
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-outline-info w-100" id="btn-llegadas-hoy" data-bs-toggle="tooltip" title="Hoy">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                                <div>
                                    <label for="llegadas-fecha" class="form-label">Fecha</label>
                                    <input type="date" class="form-control" id="llegadas-fecha" value="{{ $fecha_actual }}">
                                </div>
                                <div class="llegadas-day-nav">
                                    <button type="button" class="btn btn-light" id="btn-llegadas-anterior" data-bs-toggle="tooltip" title="Dia anterior"><i class="ri-arrow-left-line"></i></button>
                                    <button type="button" class="btn btn-light" id="btn-llegadas-siguiente" data-bs-toggle="tooltip" title="Dia siguiente"><i class="ri-arrow-right-line"></i></button>
                                </div>
                                <div class="btn-list justify-content-end">
                                    <button type="button" class="btn btn-outline-danger" id="btn-llegadas-pdf"><i class="ri-file-pdf-line me-1"></i>PDF</button>
                                    <button type="button" class="btn btn-outline-primary" id="btn-llegadas-copiar"><i class="ri-file-copy-line me-1"></i>Copiar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="llegadas-heading mb-2" id="llegadas-titulo">FECHA LLEGADA :</div>
                    <div class="llegadas-grid" id="llegadas-lista"></div>
                </div>
            </div>
        </div>

        @include('layouts.yn_footer')
    </div>

    <div class="modal fade" id="modal-llegada-recojo" tabindex="-1" aria-labelledby="modal-llegada-recojo-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-llegada-recojo">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-llegada-recojo-label">Asignar Recojo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="llegada-recojo-id">
                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            <input type="text" class="form-control" id="llegada-recojo-cliente" readonly>
                        </div>
                        <div>
                            <label for="llegada-recojo-observacion" class="form-label">Recojo</label>
                            <textarea class="form-control" id="llegada-recojo-observacion" name="observacion_recojo" rows="4" maxlength="1000" placeholder="Hotel, direccion, movilidad, responsable u otra referencia"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btn-guardar-llegada-recojo"><i class="ri-save-line me-1"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('layouts.yn_scripts')
    @include('layouts.yn_custom_switcherjs')
    <script src="{{ asset('assets/js/llegadas.js') }}?v={{ filemtime(public_path('assets/js/llegadas.js')) }}"></script>
</body>
</html>
