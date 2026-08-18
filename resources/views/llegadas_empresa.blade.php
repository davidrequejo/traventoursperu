<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">
<head>
    @include('layouts.yn_head', ['title_page' => $titulo])
    <style>
        #tabla-llegada-empresa_filter {
            width: calc(100% - 10px) !important;
            display: flex !important;
            justify-content: space-between !important;
        }

        #tabla-llegada-empresa_filter label,
        #tabla-llegada-empresa_filter label input {
            width: 100% !important;
        }
    </style>
    <style>

        .catalogo-wrapper {
        max-width: 1180px;
        margin: 0 auto;
        }
    </style>
</head>
<body id="body-llegadas-empresa" data-ruta-base="{{ $ruta_base }}" data-titulo="{{ $titulo }}" data-singular="{{ $singular }}">
    @include('layouts.yn_switcher')
    @include('layouts.yn_loader')

    <div class="page">
        @include('layouts.yn_header')
        @include('layouts.yn_sidebar')

        <div class="main-content app-content">
            
            <div class="container-fluid">
                <div class="catalogo-wrapper">
                    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                        <div>
                            <p class="fw-semibold fs-18 mb-0">{{ $titulo }}</p>
                            <span class="fs-12 text-muted">Empresas registradas para llegadas por {{ strtolower($tipo_descripcion) }}.</span>
                        </div>
                        <div class="btn-list mt-md-0 mt-2">
                            @permiso($modulo, 'crear')
                            <button type="button" class="btn btn-primary" id="btn-nueva-llegada-empresa"><i class="ri-add-line me-1"></i>Nueva {{ $singular }}</button>
                            @endpermiso
                        </div>
                    </div>

                    <div class="card custom-card">
                        <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2"><i class="{{ $icono }} fs-20 text-primary"></i><strong>Listado de {{ $titulo }}</strong></div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="incluir-eliminados-llegada-empresa">
                                <label class="form-check-label" for="incluir-eliminados-llegada-empresa">Ver eliminados</label>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle w-100" id="tabla-llegada-empresa">
                                    <thead><tr><th>Acciones</th><th>Empresa</th><th>Tipo de llegada</th><th>Estado</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.yn_footer')
    </div>

    <div class="modal fade" id="modal-llegada-empresa" tabindex="-1" aria-labelledby="modal-llegada-empresa-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-llegada-empresa" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-llegada-empresa-label">Nueva {{ $singular }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="llegada_empresa_id">
                        <div class="alert alert-primary d-flex align-items-center gap-2 py-2" role="status"><i class="{{ $icono }} fs-18"></i><span>Tipo asignado: <strong>{{ $tipo_descripcion }}</strong></span></div>
                        <div>
                            <label for="llegada_empresa_descripcion" class="form-label">Nombre de la {{ strtolower($singular) }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="llegada_empresa_descripcion" name="descripcion" maxlength="225" autocomplete="off" required>
                            <div class="invalid-feedback">Ingrese el nombre.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btn-guardar-llegada-empresa"><i class="ri-save-line me-1"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('layouts.yn_scripts')
    @include('layouts.yn_custom_switcherjs')
    <script src="{{ asset('assets/js/llegadas_empresa.js') }}?v={{ filemtime(public_path('assets/js/llegadas_empresa.js')) }}"></script>
</body>
</html>