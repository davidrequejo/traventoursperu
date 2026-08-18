<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Tipos de Comprobantes'])

  <style>
    #tabla-tipo-comprobantes_filter { width: calc(100% - 10px) !important; display: flex !important; justify-content: space-between !important; }
    #tabla-tipo-comprobantes_filter label,
    #tabla-tipo-comprobantes_filter label input { width: 100% !important; }
    .tipo-comprobante-nombre { min-width: 260px; }
    .series-comprobante-list { min-width: 220px; }
  </style>
</head>

<body id="body-tipo-comprobantes">
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
          <div>
            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Tipos de Comprobantes</p>
            <nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item">SUNAT</li>
                <li class="breadcrumb-item active" aria-current="page">Tipos de Comprobantes</li>
              </ol>
            </nav>
          </div>

          <div class="btn-list mt-md-0 mt-2">
            <button type="button" class="btn btn-icon btn-sm btn-light" id="btn-recargar-tipo-comprobante" data-bs-toggle="tooltip" title="Actualizar">
              <i class="las la-sync-alt"></i>
            </button>
          </div>
        </div>

        <div class="card custom-card">
          <div class="card-header justify-content-between flex-wrap gap-2">
            <div>
              <div class="card-title mb-0">Catalogo SUNAT C01</div>
              <p class="text-muted mb-0 fs-12">Consulta los tipos de comprobantes y las series asignadas.</p>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tabla-tipo-comprobantes" class="table table-bordered table-striped w-100">
                <thead>
                  <tr>
                    <th class="text-center">Codigo</th>
                    <th>Comprobante</th>
                    <th>Series asignadas</th>
                    <th class="text-center">Estado</th>
                    <th>Actualizado</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    @include('layouts.yn_search_modal')
    @include('layouts.yn_footer')
  </div>

  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')

  <script src="{{ asset('assets/js/tipo_comprobante.js') }}?v={{ filemtime(public_path('assets/js/tipo_comprobante.js')) }}"></script>
</body>

</html>
