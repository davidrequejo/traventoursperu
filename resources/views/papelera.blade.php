<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Papelera'])

  <style>
    #tabla-papelera_filter { width: calc(100% - 10px) !important; display: flex !important; justify-content: space-between !important; }
    #tabla-papelera_filter label { width: 100% !important;  }
    #tabla-papelera_filter label input { width: 100% !important;   }
  </style>
</head>

<body id="body-papelera">
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
          <div>
            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Papelera</p>
            <nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Papelera</li>
              </ol>
            </nav>
          </div>

          <div class="btn-list mt-md-0 mt-2">
            <button type="button" class="btn btn-icon btn-sm btn-light" id="btn-recargar-papelera" data-bs-toggle="tooltip" title="Actualizar">
              <i class="las la-sync-alt"></i>
            </button>
          </div>
        </div>

        <div class="card custom-card">
          <div class="card-header justify-content-between flex-wrap gap-2">
            <div>
              <div class="card-title mb-0">Registros desactivados</div>
              <p class="text-muted mb-0 fs-12">Restaura registros que han sido enviado a pepelera.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
              <label for="filtro-modulo-papelera" class="form-label mb-0 text-muted fs-12">Modulo</label>
              <select class="form-select form-select-sm" id="filtro-modulo-papelera" style="width: 170px;">
                <option value="todos">Todos</option>
                <option value="personas">Personas</option>
                <option value="trabajadores">Trabajadores</option>
                <option value="clientes">Clientes</option>
                <option value="usuarios">Usuarios</option>
                <option value="empresas">Empresas</option>
              </select>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tabla-papelera" class="table table-bordered table-striped w-100">
                <thead>
                  <tr>
                    <th>Modulo</th>
                    <th>Registro</th>
                    <th>Detalle</th>
                    <th>Extra</th>
                    <th>Actualizado</th>
                    <th class="text-center">OP</th>
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

  <script src="{{ asset('assets/js/papelera.js') }}?v={{ filemtime(public_path('assets/js/papelera.js')) }}"></script>
</body>

</html>
