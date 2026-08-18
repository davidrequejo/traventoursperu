<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Catalogo General'])

  <style>
    #tabla-bancos_filter { width: calc(100% - 10px) !important; display: flex !important; justify-content: space-between !important; }
    #tabla-bancos_filter label { width: 100% !important; }
    #tabla-bancos_filter label input { width: 100% !important; }

    .catalogo-wrapper {
      max-width: 1180px;
      margin: 0 auto;
    }

    .catalogo-layout {
      display: grid;
      grid-template-columns: 290px minmax(0, 1fr);
      gap: 1.5rem;
    }

    .catalogo-side-tabs {
      position: sticky;
      top: 5rem;
    }

    .catalogo-side-tabs .nav-link {
      width: 100%;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.85rem 1rem;
      margin-bottom: 0.5rem;
      border: 1px solid var(--default-border);
      border-radius: 0.5rem;
      background: var(--custom-white);
      color: var(--default-text-color);
      font-weight: 600;
      text-align: start;
    }

    .catalogo-side-tabs .nav-link i {
      color: var(--text-muted);
    }

    .catalogo-side-tabs .nav-link.active {
      color: #fff;
      background: var(--primary-color);
      border-color: var(--primary-color);
      box-shadow: 0 0.65rem 1.2rem var(--primary02);
    }

    .catalogo-side-tabs .nav-link.active i,
    .catalogo-side-tabs .nav-link.active .text-muted {
      color: rgba(255, 255, 255, 0.85) !important;
    }

    .catalogo-table th:first-child,
    .catalogo-table td:first-child {
      width: 110px;
      white-space: nowrap;
      text-align: center;
    }

    .catalogo-table td {
      vertical-align: middle;
    }

    .catalogo-datatable-wrapper .dataTables_filter {
      width: 100%;
    }

    .catalogo-datatable-wrapper .dataTables_filter label {
      width: 100%;
      margin-bottom: .75rem;
    }

    .catalogo-datatable-wrapper .dataTables_filter input {
      width: 100% !important;
      margin-left: 0 !important;
    }

    @media (max-width: 991.98px) {
      .catalogo-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .catalogo-side-tabs {
        position: static;
      }
    }
  </style>
</head>

<body id="body-empresa">
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
              <p class="fw-semibold fs-18 mb-0 title-body-pagina">Catalogo General</p>
              <span class="fs-semibold text-muted detalle-body-pagina">
                <nav>
                  <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Catalogo General</li>
                  </ol>
                </nav>
              </span>
            </div>
          </div>

          <div class="catalogo-layout">
            <div>
              <div class="catalogo-side-tabs mb-3" id="catalogo-tabs" role="tablist" aria-orientation="vertical">
                <button class="nav-link active" id="tab-bancos" data-bs-toggle="tab" data-bs-target="#pane-bancos" type="button" role="tab" aria-controls="pane-bancos" aria-selected="true">
                  <i class="ri-bank-line fs-18"></i>
                  <span>
                    <span class="d-block">Bancos</span>
                  </span>
                </button>
                <button class="nav-link" id="tab-tipospersona" data-bs-toggle="tab" data-bs-target="#pane-tipospersona" type="button" role="tab" aria-controls="pane-tipospersona" aria-selected="false">
                  <i class="ri-user-3-line fs-18"></i>
                  <span>
                    <span class="d-block">Tipo Persona</span>
                  </span>
                </button>
                <button class="nav-link" id="tab-cargos" data-bs-toggle="tab" data-bs-target="#pane-cargos" type="button" role="tab" aria-controls="pane-cargos" aria-selected="false">
                  <i class="ri-briefcase-line fs-18"></i>
                  <span>
                    <span class="d-block">Cargos</span>
                  </span>
                </button>
                <button class="nav-link" id="tab-ingreso-egreso-categorias" data-bs-toggle="tab" data-bs-target="#pane-ingreso-egreso-categorias" type="button" role="tab" aria-controls="pane-ingreso-egreso-categorias" aria-selected="false">
                  <i class="ri-exchange-dollar-line fs-18"></i>
                  <span>
                    <span class="d-block">Ingreso/Egreso Categorias</span>
                  </span>
                </button>
                <button class="nav-link" id="tab-departamentos" data-bs-toggle="tab" data-bs-target="#pane-departamentos" type="button" role="tab" aria-controls="pane-departamentos" aria-selected="false">
                  <i class="ri-map-pin-line fs-18"></i>
                  <span>
                    <span class="d-block">Departamentos</span>
                  </span>
                </button>
                <button class="nav-link" id="tab-provincias" data-bs-toggle="tab" data-bs-target="#pane-provincias" type="button" role="tab" aria-controls="pane-provincias" aria-selected="false">
                  <i class="ri-map-pin-2-line fs-18"></i>
                  <span>
                    <span class="d-block">Provincias</span>
                  </span>
                </button>
                <button class="nav-link" id="tab-distritos" data-bs-toggle="tab" data-bs-target="#pane-distritos" type="button" role="tab" aria-controls="pane-distritos" aria-selected="false">
                  <i class="ri-map-pin-3-line fs-18"></i>
                  <span>
                    <span class="d-block">Distritos</span>
                  </span>
                </button>
              </div>
            </div>

            <div class="tab-content">
              <div class="tab-pane fade show active border-0" id="pane-bancos" role="tabpanel" aria-labelledby="tab-bancos" tabindex="0">
                <div class="card custom-card">
                  <div class="card-header justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="card-title mb-0"><i class="ri-bank-line me-1"></i> Bancos</div>
                      <p class="text-muted mb-0 fs-12">Lista de bancos registrados en el sistema.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-banco" data-bs-toggle="modal" data-bs-target="#modal-nuevo-banco"><i class="ri-add-line me-1 align-middle fs-14 fw-semibold d-inline-block"></i>Agregar Banco</button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive catalogo-datatable-wrapper">
                      <table id="tabla-bancos" class="table table-bordered table-striped w-100 catalogo-table">
                        <thead>
                          <tr>
                            <th class="text-center">OP</th>
                            <th>Nombre</th>
                            <th>Alias</th>
                            <th class="text-center">Estado</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade border-0" id="pane-tipospersona" role="tabpanel" aria-labelledby="tab-tipospersona" tabindex="0">
                <div class="card custom-card">
                  <div class="card-header justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="card-title mb-0"><i class="ri-user-3-line me-1"></i> Tipo Persona</div>
                      <p class="text-muted mb-0 fs-12">Lista de tipos de persona configurados.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-persona-tipo" data-bs-toggle="modal" data-bs-target="#modal-nuevo-persona-tipo"><i class="ri-add-line me-1 align-middle fs-14 fw-semibold d-inline-block"></i>Agregar Tipo de Persona</button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive catalogo-datatable-wrapper">
                      <table id="tabla-personas-tipos" class="table table-bordered table-striped w-100 catalogo-table">
                        <thead>
                          <tr>
                            <th class="text-center">OP</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-center">Estado</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade border-0" id="pane-cargos" role="tabpanel" aria-labelledby="tab-cargos" tabindex="0">
                <div class="card custom-card">
                  <div class="card-header justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="card-title mb-0"><i class="ri-briefcase-line me-1"></i> Cargos</div>
                      <p class="text-muted mb-0 fs-12">Lista de cargos de personas.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-persona-cargo" data-bs-toggle="modal" data-bs-target="#modal-nuevo-persona-cargo"><i class="ri-add-line me-1 align-middle fs-14 fw-semibold d-inline-block"></i>Agregar Cargo</button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive catalogo-datatable-wrapper">
                      <table id="tabla-personas-cargos" class="table table-bordered table-striped w-100 catalogo-table">
                        <thead>
                          <tr>
                            <th class="text-center">OP</th>
                            <th>Nombre</th>
                            <th class="text-center">Estado</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade border-0" id="pane-ingreso-egreso-categorias" role="tabpanel" aria-labelledby="tab-ingreso-egreso-categorias" tabindex="0">
                <div class="card custom-card">
                  <div class="card-header justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="card-title mb-0"><i class="ri-exchange-dollar-line me-1"></i> Ingreso/Egreso Categorias</div>
                      <p class="text-muted mb-0 fs-12">Lista de categorias para ingresos y egresos.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-ingreso-egreso-categoria" data-bs-toggle="modal" data-bs-target="#modal-nuevo-ingreso-egreso-categoria"><i class="ri-add-line me-1 align-middle fs-14 fw-semibold d-inline-block"></i>Agregar Categoria</button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive catalogo-datatable-wrapper">
                      <table id="tabla-ingreso-egreso-categorias" class="table table-bordered table-striped w-100 catalogo-table">
                        <thead>
                          <tr>
                            <th class="text-center">OP</th>
                            <th>Nombre</th>
                            <th>Descripcion</th>
                            <th class="text-center">Estado</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade border-0" id="pane-departamentos" role="tabpanel" aria-labelledby="tab-departamentos" tabindex="0">
                <div class="card custom-card">
                  <div class="card-header justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="card-title mb-0"><i class="ri-map-pin-line me-1"></i> Departamentos</div>
                      <p class="text-muted mb-0 fs-12">Lista de departamentos del país.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-departamento" data-bs-toggle="modal" data-bs-target="#modal-nuevo-departamento"><i class="ri-add-line me-1 align-middle fs-14 fw-semibold d-inline-block"></i>Agregar Departamento</button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive catalogo-datatable-wrapper">
                      <table id="tabla-departamentos" class="table table-bordered table-striped w-100 catalogo-table">
                        <thead>
                          <tr>
                            <th class="text-center">OP</th>
                            <th>Nombre</th>
                            <th class="text-center">Estado</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade border-0" id="pane-provincias" role="tabpanel" aria-labelledby="tab-provincias" tabindex="0">
                <div class="card custom-card">
                  <div class="card-header justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="card-title mb-0"><i class="ri-map-pin-2-line me-1"></i> Provincias</div>
                      <p class="text-muted mb-0 fs-12">Lista de provincias por departamento.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-provincia" data-bs-toggle="modal" data-bs-target="#modal-nuevo-provincia"><i class="ri-add-line me-1 align-middle fs-14 fw-semibold d-inline-block"></i>Agregar Provincia</button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive catalogo-datatable-wrapper">
                      <table id="tabla-provincias" class="table table-bordered table-striped w-100 catalogo-table">
                        <thead>
                          <tr>
                            <th class="text-center">OP</th>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th class="text-center">Estado</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade border-0" id="pane-distritos" role="tabpanel" aria-labelledby="tab-distritos" tabindex="0">
                <div class="card custom-card">
                  <div class="card-header justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="card-title mb-0"><i class="ri-map-pin-3-line me-1"></i> Distritos</div>
                      <p class="text-muted mb-0 fs-12">Lista de distritos con su provincia y departamento.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-distrito" data-bs-toggle="modal" data-bs-target="#modal-nuevo-distrito"><i class="ri-add-line me-1 align-middle fs-14 fw-semibold d-inline-block"></i>Agregar Distrito</button>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive catalogo-datatable-wrapper">
                      <table id="tabla-distritos" class="table table-bordered table-striped w-100 catalogo-table">
                        <thead>
                          <tr>
                            <th class="text-center">OP</th>
                            <th>Nombre</th>
                            <th>Provincia</th>
                            <th>Departamento</th>
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
        </div>
      </div>
    </div>

    <!-- Modal Nuevo Banco -->
    <div class="modal fade" id="modal-nuevo-banco" tabindex="-1" aria-labelledby="modal-nuevo-banco-label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-nuevo-banco-label">Nuevo Banco</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="form-banco" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="row gy-3">

                  <input type="hidden" name="idbanco" id="banco_idbanco">
                  <input type="hidden" name="icono_actual" id="banco_icono_actual">

                  <div class="col-xl-8">
                    <label for="banco_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="nombre" id="banco_nombre" maxlength="65" placeholder="Ej. Banco de Credito del Peru" required>
                  </div>

                  <div class="col-xl-4">
                    <label for="banco_alias" class="form-label">Alias</label>
                    <input type="text" class="form-control" name="alias" id="banco_alias" maxlength="65" placeholder="Ej. BCP">
                  </div>

                  <div class="col-xl-4">
                    <label for="banco_formato_cta" class="form-label">Formato cuenta</label>
                    <input type="text" class="form-control" name="formato_cta" id="banco_formato_cta" maxlength="50" placeholder="Ej. 000-0000000000">
                  </div>

                  <div class="col-xl-4">
                    <label for="banco_formato_cci" class="form-label">Formato CCI</label>
                    <input type="text" class="form-control" name="formato_cci" id="banco_formato_cci" maxlength="50" placeholder="Ej. 000-000-0000000000-00">
                  </div>

                  <div class="col-xl-4">
                    <label for="banco_formato_detracciones" class="form-label">Formato detracciones</label>
                    <input type="text" class="form-control" name="formato_detracciones" id="banco_formato_detracciones" maxlength="50" placeholder="Ej. 00-0000000000">
                  </div>

                  <div class="col-xl-8">
                    <label for="banco_icono_file" class="form-label">Icono</label>
                    <input type="file" class="form-control" name="icono_file" id="banco_icono_file" accept="image/*">
                    <label class="form-label mt-1 fs-12 text-muted mb-0">Imagen opcional para identificar el banco.</label>
                  </div>

                  <div class="col-xl-4">
                    <div class="border rounded p-3 text-center h-100 d-flex flex-column justify-content-center">
                      <span class="avatar avatar-xl avatar-rounded d-inline-flex mx-auto mb-2">
                        <img src="{{ asset('ynex_admin/svg/empresa-logo.svg') }}" alt="" class="banco-icono-preview" id="banco_icono_preview">
                      </span>
                      <button type="button" class="btn btn-light btn-sm" id="btn-remover-icono-banco">
                        <i class="bi bi-trash me-1"></i>Remover
                      </button>
                    </div>
                  </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success guardar_banco">Guardar </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal tipo persona -->
    <div class="modal fade" id="modal-nuevo-persona-tipo" tabindex="-1" aria-labelledby="modal-nuevo-persona-tipo-label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-nuevo-persona-tipo-label">Nuevo Tipo de Persona</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="form-persona-tipo" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="row gy-3">
                  <input type="hidden" name="idpersona_tipo" id="persona_tipo_id">

                  <div class="col-xl-12">
                    <label for="persona_tipo_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="nombre" id="persona_tipo_nombre" maxlength="100" placeholder="Ej. Cliente, Proveedor, Empleado" required>
                  </div>

                  <div class="col-xl-12">
                    <label for="persona_tipo_descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion" id="persona_tipo_descripcion" rows="3" maxlength="500" placeholder="Descripción opcional del tipo de persona"></textarea>
                  </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success guardar_persona_tipo">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal cargo persona -->
    <div class="modal fade" id="modal-nuevo-persona-cargo" tabindex="-1" aria-labelledby="modal-nuevo-persona-cargo-label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-nuevo-persona-cargo-label">Nuevo Cargo</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="form-persona-cargo" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="row gy-3">
                  <input type="hidden" name="idpersona_cargo" id="persona_cargo_id">

                  <div class="col-xl-12">
                    <label for="persona_cargo_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="nombre" id="persona_cargo_nombre" maxlength="45" placeholder="Ej. Supervisor, Vendedor" required>
                  </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success guardar_persona_cargo">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal ingreso egreso categoria -->
    <div class="modal fade" id="modal-nuevo-ingreso-egreso-categoria" tabindex="-1" aria-labelledby="modal-nuevo-ingreso-egreso-categoria-label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-nuevo-ingreso-egreso-categoria-label">Nueva Categoria Ingreso/Egreso</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="form-ingreso-egreso-categoria" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="row gy-3">
                  <input type="hidden" name="idingreso_egreso_categoria" id="ingreso_egreso_categoria_id">

                  <div class="col-xl-12">
                    <label for="ingreso_egreso_categoria_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="nombre" id="ingreso_egreso_categoria_nombre" maxlength="100" placeholder="Ej. Pago de lote, Mantenimiento, Comision" required>
                  </div>

                  <div class="col-xl-12">
                    <label for="ingreso_egreso_categoria_descripcion" class="form-label">Descripcion</label>
                    <textarea class="form-control" name="descripcion" id="ingreso_egreso_categoria_descripcion" rows="3" maxlength="250" placeholder="Descripcion opcional de la categoria"></textarea>
                  </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success guardar_ingreso_egreso_categoria">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal departamento -->
    <div class="modal fade" id="modal-nuevo-departamento" tabindex="-1" aria-labelledby="modal-nuevo-departamento-label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-nuevo-departamento-label">Nuevo Departamento</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="form-departamento" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="row gy-3">
                  <input type="hidden" name="idubigeo_departamento" id="departamento_id">

                  <div class="col-xl-12">
                    <label for="departamento_idubigeo_departamento" class="form-label">ID Departamento <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="idubigeo_departamento" id="departamento_idubigeo_departamento" maxlength="2" placeholder="Ej. 01, 02" required>
                  </div>

                  <div class="col-xl-12">
                    <label for="departamento_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="nombre" id="departamento_nombre" maxlength="100" placeholder="Ej. Lima, Arequipa" required>
                  </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success guardar_departamento">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal provincia -->
    <div class="modal fade" id="modal-nuevo-provincia" tabindex="-1" aria-labelledby="modal-nuevo-provincia-label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-nuevo-provincia-label">Nueva Provincia</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="form-provincia" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="row gy-3">
                  <input type="hidden" name="idubigeo_provincia" id="provincia_id">

                  <div class="col-xl-12">
                    <label for="provincia_idubigeo_departamento" class="form-label">Departamento <sup class="text-danger">*</sup></label>
                    <select class="form-control" name="idubigeo_departamento" id="provincia_idubigeo_departamento" required>
                      <option value="">Seleccione un departamento</option>
                    </select>
                  </div>

                  <div class="col-xl-12">
                    <label for="provincia_idubigeo_provincia" class="form-label">ID Provincia <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="idubigeo_provincia" id="provincia_idubigeo_provincia" maxlength="4" placeholder="Ej. 0101, 0201" required>
                  </div>

                  <div class="col-xl-12">
                    <label for="provincia_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="nombre" id="provincia_nombre" maxlength="100" placeholder="Ej. Lima, Cercado" required>
                  </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success guardar_provincia">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal distrito -->
    <div class="modal fade" id="modal-nuevo-distrito" tabindex="-1" aria-labelledby="modal-nuevo-distrito-label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-nuevo-distrito-label">Nuevo Distrito</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="form-distrito" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="row gy-3">
                  <input type="hidden" name="idubigeo_distrito" id="distrito_id">

                  <div class="col-xl-6">
                    <label for="distrito_idubigeo_departamento" class="form-label">Departamento <sup class="text-danger">*</sup></label>
                    <select class="form-control" name="idubigeo_departamento" id="distrito_idubigeo_departamento" required>
                      <option value="">Seleccione un departamento</option>
                    </select>
                  </div>

                  <div class="col-xl-6">
                    <label for="distrito_idubigeo_provincia" class="form-label">Provincia <sup class="text-danger">*</sup></label>
                    <select class="form-control" name="idubigeo_provincia" id="distrito_idubigeo_provincia" required>
                      <option value="">Seleccione una provincia</option>
                    </select>
                  </div>

                  <div class="col-xl-12">
                    <label for="distrito_idubigeo_distrito" class="form-label">ID Distrito <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="idubigeo_distrito" id="distrito_idubigeo_distrito" maxlength="6" placeholder="Ej. 010101, 020201" required>
                  </div>

                  <div class="col-xl-12">
                    <label for="distrito_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" name="nombre" id="distrito_nombre" maxlength="100" placeholder="Ej. Miraflores, San Isidro" required>
                  </div>

                  <div class="col-xl-6">
                    <label for="distrito_codigo_postal" class="form-label">Código Postal</label>
                    <input type="text" class="form-control" name="codigo_postal" id="distrito_codigo_postal" maxlength="10" placeholder="Ej. 15001">
                  </div>

                  <div class="col-xl-6">
                    <label for="distrito_ubigeo_reniec" class="form-label">Ubigeo RENIEC</label>
                    <input type="text" class="form-control" name="ubigeo_reniec" id="distrito_ubigeo_reniec" maxlength="10" placeholder="Ej. 150101">
                  </div>

                  <div class="col-xl-6">
                    <label for="distrito_ubigeo_inei" class="form-label">Ubigeo INEI</label>
                    <input type="text" class="form-control" name="ubigeo_inei" id="distrito_ubigeo_inei" maxlength="10" placeholder="Ej. 150101">
                  </div>

                  <div class="col-xl-6">
                    <label for="distrito_superficie" class="form-label">Superficie (km²)</label>
                    <input type="number" class="form-control" name="superficie" id="distrito_superficie" step="0.01" min="0" placeholder="Ej. 72.12">
                  </div>

                  <div class="col-xl-6">
                    <label for="distrito_altitud" class="form-label">Altitud (msnm)</label>
                    <input type="number" class="form-control" name="altitud" id="distrito_altitud" step="0.01" min="0" placeholder="Ej. 154">
                  </div>

                  <div class="col-xl-6">
                    <label for="distrito_latitud" class="form-label">Latitud</label>
                    <input type="number" class="form-control" name="latitud" id="distrito_latitud" step="0.000001" min="-90" max="90" placeholder="Ej. -12.0464">
                  </div>

                  <div class="col-xl-6">
                    <label for="distrito_longitud" class="form-label">Longitud</label>
                    <input type="number" class="form-control" name="longitud" id="distrito_longitud" step="0.000001" min="-180" max="180" placeholder="Ej. -77.0428">
                  </div>

                  <div class="col-xl-12">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="frontera" id="distrito_frontera" value="1">
                      <label class="form-check-label" for="distrito_frontera">
                        Distrito fronterizo
                      </label>
                    </div>
                  </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success guardar_distrito">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    @include('layouts.yn_search_modal')
    @include('layouts.yn_footer')
  </div>

  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')

  <script src="{{ asset('assets/js/empresa.js') }}?v={{ filemtime(public_path('assets/js/empresa.js')) }}"></script>
  <script src="{{ asset('assets/js/banco.js') }}?v={{ filemtime(public_path('assets/js/banco.js')) }}"></script>
  <script src="{{ asset('assets/js/persona_tipo.js') }}?v={{ filemtime(public_path('assets/js/persona_tipo.js')) }}"></script>
  <script src="{{ asset('assets/js/persona_cargo.js') }}?v={{ filemtime(public_path('assets/js/persona_cargo.js')) }}"></script>
  <script src="{{ asset('assets/js/ingreso_egreso_categoria.js') }}?v={{ filemtime(public_path('assets/js/ingreso_egreso_categoria.js')) }}"></script>
  <script src="{{ asset('assets/js/ubigeo_departamento.js') }}?v={{ filemtime(public_path('assets/js/ubigeo_departamento.js')) }}"></script>
  <script src="{{ asset('assets/js/ubigeo_provincia.js') }}?v={{ filemtime(public_path('assets/js/ubigeo_provincia.js')) }}"></script>
  <script src="{{ asset('assets/js/ubigeo_distrito.js') }}?v={{ filemtime(public_path('assets/js/ubigeo_distrito.js')) }}"></script>

</body>

</html>
