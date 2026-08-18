<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Series de Comprobantes'])

  <style>
    #tabla-series-comprobantes_filter { width: calc(100% - 10px) !important; display: flex !important; justify-content: space-between !important; }
    #tabla-series-comprobantes_filter label,
    #tabla-series-comprobantes_filter label input { width: 100% !important; }
    .serie-comprobante-form-shell { max-width: 980px; margin: 0 auto; }
  </style>
</head>

<body id="body-series-comprobantes">
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
          <div>
            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Series de Comprobantes</p>
            <nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item">SUNAT</li>
                <li class="breadcrumb-item active" aria-current="page">Series de Comprobantes</li>
              </ol>
            </nav>
          </div>

          <div class="btn-list mt-md-0 mt-2">
            @permiso('series_de_comprobantes', 'crear')
            <button type="button" class="btn btn-primary label-btn m-r-10px" id="btn-nueva-serie-comprobante">
              <i class="ri-file-add-line label-btn-icon me-2"></i>Agregar
            </button>
            @endpermiso
            <button type="button" class="btn btn-light label-btn m-r-10px" id="btn-regresar-serie-comprobante" style="display:none;">
              <i class="bi bi-arrow-left label-btn-icon me-2"></i>Regresar
            </button>
          </div>
        </div>

        <div id="div-tabla-series-comprobantes">
          <div class="card custom-card">
            <div class="card-header justify-content-between flex-wrap gap-2">
              <div>
                <div class="card-title mb-0">Series asignadas</div>
                <p class="text-muted mb-0 fs-12">Registra y administra las series por tipo de comprobante.</p>
              </div>
              <div class="d-flex align-items-center gap-3">
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" role="switch" id="incluir-desactivados-serie-comprobante">
                  <label class="form-check-label fs-12 text-muted" for="incluir-desactivados-serie-comprobante">Incluir desactivados</label>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-light" id="btn-recargar-serie-comprobante" data-bs-toggle="tooltip" title="Actualizar">
                  <i class="las la-sync-alt"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="tabla-series-comprobantes" class="table table-bordered table-striped w-100">
                  <thead>
                    <tr>
                      <th class="text-center">OP</th>
                      <th class="text-center">#</th>
                      <th>Comprobante</th>
                      <th class="text-center">Serie</th>
                      <th class="text-center">Numero</th>
                      <th>Adicional</th>
                      <th class="text-center">Pred.</th>
                      <th class="text-center">Estado</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div id="div-formulario-serie-comprobante" style="display:none;">
          <div class="serie-comprobante-form-shell">
            <div class="card custom-card">
              <form id="form-serie-comprobante" method="POST">
                @csrf
                <input type="hidden" name="idserie_comprobante" id="idserie_comprobante">

                <div class="card-header justify-content-between flex-wrap gap-2">
                  <div>
                    <h5 class="card-title mb-1" id="titulo-formulario-serie-comprobante">Nueva serie</h5>
                    <p class="text-muted mb-0 fs-12">Asigna una serie a un tipo de comprobante SUNAT.</p>
                  </div>
                  <div class="btn-list">
                    <button type="button" class="btn btn-outline-danger" id="btn-cancelar-serie-comprobante">
                      <i class="ti ti-circle-dashed-x"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-serie-comprobante">
                      <i class="ti ti-device-floppy"></i> Guardar
                    </button>
                  </div>
                </div>

                <div class="card-body">
                  <div class="row gy-3">
                    <div class="col-md-7">
                      <label for="serie_idsunat_c01_tipo_comprobante" class="form-label">Tipo de comprobante <sup class="text-danger">*</sup></label>
                      <select class="form-control" name="idsunat_c01_tipo_comprobante" id="serie_idsunat_c01_tipo_comprobante" style="width:100%;"></select>
                    </div>
                    <div class="col-md-5">
                      <label for="serie_tipo_comprobante_adicional" class="form-label">Tipo adicional</label>
                      <select class="form-control" name="tipo_comprobante_adicional" id="serie_tipo_comprobante_adicional" style="width:100%;"></select>
                    </div>
                    <div class="col-md-4">
                      <label for="serie_comprobante_serie" class="form-label">Serie <sup class="text-danger">*</sup></label>
                      <input type="text" class="form-control" name="serie" id="serie_comprobante_serie" maxlength="10">
                    </div>
                    <div class="col-md-4">
                      <label for="serie_comprobante_numero" class="form-label">Numero</label>
                      <input type="number" class="form-control" name="numero" id="serie_comprobante_numero" min="0">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                      <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" name="predeterminado" id="serie_comprobante_predeterminado" value="1">
                        <label class="form-check-label" for="serie_comprobante_predeterminado">Predeterminado</label>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
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

  <script src="{{ asset('assets/js/serie_comprobante.js') }}?v={{ filemtime(public_path('assets/js/serie_comprobante.js')) }}"></script>
</body>

</html>
