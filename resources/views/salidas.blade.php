<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">
<head>
    @include('layouts.yn_head', ['title_page' => 'Salidas'])
    <style>
        .salidas-toolbar { display: grid; grid-template-columns: auto 142px 1fr auto; gap: 8px; align-items: center; background: var(--custom-white); border-radius: 8px; padding: 18px; }
        .salidas-title { text-align: right; }
        .salidas-heading { text-align: center; font-size: 18px; font-weight: 800; margin: 18px 0; }
        .salidas-heading span { color: var(--primary-color); }
        .salidas-grid { display: grid; grid-template-columns: repeat(2, minmax(280px, 440px)); justify-content: center; gap: 18px; }
        .salida-card { border: 1px solid var(--default-border); border-radius: 8px; background: var(--custom-white); min-height: 98px; box-shadow: 0 7px 18px rgba(15, 23, 42, .06); }
        .salida-card-body { padding: 15px; text-align: center; }
        .salida-tour { font-weight: 800; color: var(--default-text-color); margin-bottom: 6px; }
        .salida-pax { color: rgb(var(--danger-rgb)); font-weight: 800; font-size: 12px; }
        .salida-shared { color: var(--text-muted); font-size: 11px; margin-top: 4px; }
        .salida-link { font-size: 11px; color: var(--primary-color); display: inline-block; margin-top: 6px; }
        .salida-empty { max-width: 520px; margin: 0 auto; border: 1px dashed var(--default-border); border-radius: 8px; background: var(--custom-white); padding: 38px 18px; text-align: center; color: var(--text-muted); }
        .salida-detail-row { border: 1px solid var(--default-border); border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        @media (max-width: 991.98px) {
            .salidas-toolbar { grid-template-columns: 1fr; }
            .salidas-title { text-align: left; }
            .salidas-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body id="body-salidas" data-fecha-actual="{{ $fecha_actual }}">
    @include('layouts.yn_switcher')
    @include('layouts.yn_loader')

    <div class="page">
        @include('layouts.yn_header')
        @include('layouts.yn_sidebar')

        <div class="main-content app-content">
            <div class="container-fluid py-3">
                <div class="salidas-toolbar">
                    <button type="button" class="btn btn-info-light btn-icon" id="btn-salidas-hoy" data-bs-toggle="tooltip" title="Hoy"><i class="bi bi-arrow-clockwise"></i></button>
                    <input type="date" class="form-control" id="salidas-fecha" value="{{ $fecha_actual }}">
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-light btn-icon" id="btn-salidas-anterior" data-bs-toggle="tooltip" title="Dia anterior"><i class="ri-arrow-left-line"></i></button>
                        <button type="button" class="btn btn-light btn-icon" id="btn-salidas-siguiente" data-bs-toggle="tooltip" title="Dia siguiente"><i class="ri-arrow-right-line"></i></button>
                    </div>
                    <div class="salidas-title">
                        <div class="fw-bold">Cronograma de salidas</div>
                        <div class="text-muted fs-11">Seguimiento de salida de tours por dia.</div>
                    </div>
                </div>

                <div class="salidas-heading" id="salidas-titulo">Cronograma de salidas: <span>{{ $fecha_actual }}</span></div>
                <div class="salidas-grid" id="salidas-lista"></div>
            </div>
        </div>

        @include('layouts.yn_footer')
    </div>

    <div class="modal fade" id="modal-salida-detalles" tabindex="-1" aria-labelledby="modal-salida-detalles-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="modal-salida-detalles-label">Detalle de salida</h5>
                        <div class="text-muted fs-12" id="modal-salida-detalles-subtitle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="salida-detalles-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.yn_scripts')
    @include('layouts.yn_custom_switcherjs')
    <script src="{{ asset('assets/js/salidas.js') }}?v={{ filemtime(public_path('assets/js/salidas.js')) }}"></script>
</body>
</html>
