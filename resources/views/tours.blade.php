<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Tours'])

  <style>
    #tabla-tours_filter {
      width: calc(100% - 10px) !important;
      display: flex !important;
      justify-content: space-between !important;
    }

    #tabla-tours_filter label,
    #tabla-tours_filter label input {
      width: 100% !important;
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
      line-height: 1;
    }
  </style>
</head>

<body id="body-tours">
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
          <div>
            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Tours</p>
            <nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tours</li>
              </ol>
            </nav>
          </div>

          <div class="btn-list mt-md-0 mt-2">
            <div class="form-check form-switch d-inline-flex align-items-center mb-0 me-2">
              <input class="form-check-input" type="checkbox" role="switch" id="incluir-eliminados-tours">
              <label class="form-check-label fs-12 text-muted ms-2" for="incluir-eliminados-tours">Papelera</label>
            </div>

            @permiso('tours', 'crear')
            <button type="button" class="btn btn-primary m-r-10px" id="btn-nuevo-tour">
              <i class="ri-add-line me-2"></i>Agregar
            </button>
            @endpermiso

            <button type="button" class="btn btn-light label-btn m-r-10px" id="btn-recargar-tours">
              <i class="bi bi-arrow-repeat label-btn-icon me-2"></i>Actualizar
            </button>
          </div>
        </div>

        <div class="card custom-card">
          <div class="card-body">
            <div class="table-responsive">
              <table id="tabla-tours" class="table table-bordered table-striped w-100">
                <thead>
                  <tr>
                    <th class="text-center">OP</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Turno</th>
                    <th>Distrito</th>
                    <th class="text-end">Precio Publico</th>
                    <th class="text-end">Precio Corporativo</th>
                    <th class="text-end">Precio Tour</th>
                    <th class="text-end">Precio Web</th>
                    <th class="text-center">Estado</th>
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

  <div class="modal fade" id="modal-nuevo-tour" tabindex="-1" aria-labelledby="modal-nuevo-tour-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title" id="modal-nuevo-tour-label">Nuevo Tour</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="form-tour" method="POST">
            @csrf
            <input type="hidden" name="idtours" id="tour_idtours">

            <div class="row gy-3">
              <div class="col-xl-4">
                <label for="tour_codigo" class="form-label">Código</label>
                <input type="text" class="form-control" name="codigo" id="tour_codigo" maxlength="10" readonly placeholder="Se genera automáticamente">
              </div>

              <div class="col-xl-8">
                <label for="tour_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                <input type="text" class="form-control" name="nombre" id="tour_nombre" maxlength="250" required>
              </div>

              <div class="col-xl-6">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <label for="tour_idtours_turno" class="form-label mb-0">Turno <sup class="text-danger">*</sup>
                    @permiso('tours', 'crear')
                  <span class="badge bg-success m-r-4px cursor-pointer" id="btn-nuevo-turno"
                    data-bs-toggle="tooltip" aria-label="Agregar"
                    data-bs-original-title="Agregar"><i class="las la-plus"></i></span> @endpermiso
                  </label>
                </div>
                <select class="form-control select2" name="idtours_turno" id="tour_idtours_turno" style="width: 100%;" required>
                  <option value="">Seleccione un turno</option>
                </select>
              </div>

              <div class="col-xl-6">
                <label for="tour_idubigeo_distrito" class="form-label">Distrito <sup class="text-danger">*</sup></label>
                <select class="form-control select2" name="idubigeo_distrito" id="tour_idubigeo_distrito" style="width: 100%;" required>
                  <option value="">Seleccione un distrito</option>
                </select>
              </div>

              <div class="col-xl-3">
                <label for="tour_precio_publico" class="form-label">Precio Público</label>
                <input type="number" class="form-control" name="precio_publico" id="tour_precio_publico" min="0" step="0.01">
              </div>
              <div class="col-xl-3">
                <label for="tour_precio_corporativo" class="form-label">Precio Corporativo</label>
                <input type="number" class="form-control" name="precio_corporativo" id="tour_precio_corporativo" min="0" step="0.01">
              </div>
              <div class="col-xl-3">
                <label for="tour_precio_tours" class="form-label">Precio Tour</label>
                <input type="number" class="form-control" name="precio_tours" id="tour_precio_tours" min="0" step="0.01">
              </div>
              <div class="col-xl-3">
                <label for="tour_precio_web" class="form-label">Precio Web</label>
                <input type="number" class="form-control" name="precio_web" id="tour_precio_web" min="0" step="0.01">
              </div>

              <div class="col-xl-4">
                <label for="tour_duracion" class="form-label">Duración</label>
                <input type="text" class="form-control" name="duracion" id="tour_duracion" maxlength="225">
              </div>
              <div class="col-xl-4">
                <label for="tour_hora_recojo" class="form-label">Hora recojo</label>
                <input type="time" class="form-control" name="hora_recojo" id="tour_hora_recojo">
              </div>
              <div class="col-xl-4">
                <label for="tour_hora_retorno" class="form-label">Hora retorno</label>
                <input type="time" class="form-control" name="hora_retorno" id="tour_hora_retorno">
              </div>

              <div class="col-12">
                <label for="tour_descripcion_inicial" class="form-label">Descripción inicial</label>
                <textarea class="form-control" name="descripcion_inicial" id="tour_descripcion_inicial" rows="3"></textarea>
              </div>

              <div class="col-12">
                <label for="tour_descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" name="descripcion" id="tour_descripcion" rows="3"></textarea>
              </div>

              <div class="col-12">
                <label for="tour_descripcion_momento_destacados" class="form-label">Momentos destacados</label>
                <textarea class="form-control" name="descripcion_momento_destacados" id="tour_descripcion_momento_destacados" rows="3"></textarea>
              </div>

              <div class="col-12">
                <label for="tour_informacion_importante" class="form-label">Información importante</label>
                <textarea class="form-control" name="informacion_importante" id="tour_informacion_importante" rows="3"></textarea>
              </div>

              <div class="col-12">
                <label for="tour_descripcion_incluye_noincluye" class="form-label">Incluye / No incluye</label>
                <textarea class="form-control" name="descripcion_incluye_noincluye" id="tour_descripcion_incluye_noincluye" rows="3"></textarea>
              </div>

              <div class="col-12">
                <label for="tour_ubicacion_maps" class="form-label">Ubicación Maps</label>
                <textarea class="form-control" name="ubicacion_maps" id="tour_ubicacion_maps" rows="2"></textarea>
              </div>

              <div class="col-xl-12">
                <label for="tour_brochure" class="form-label">Brochure</label>
                <input type="text" class="form-control" name="brochure" id="tour_brochure" maxlength="255" placeholder="URL o nombre de archivo">
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="btn-guardar-tour">Guardar</button>
        </div>
      </div>
    </div>
  </div>

  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')

  <script src="{{ asset('assets/js/tours.js') }}?v={{ filemtime(public_path('assets/js/tours.js')) }}"></script>
</body>

</html>
