<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Cuentas Bancarias'])

  <style>
    #tabla-cuentas-bancarias_filter {
      width: calc(100% - 10px) !important;
      display: flex !important;
      justify-content: space-between !important;
    }

    #tabla-cuentas-bancarias_filter label,
    #tabla-cuentas-bancarias_filter label input {
      width: 100% !important;
    }

    .cuenta-bancaria-persona {
      min-width: 220px;
    }

    .cuenta-bancaria-banco {
      min-width: 150px;
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

    #modal-banco-cuenta-bancaria .modal-content {
      background: #1f2937;
      color: #f8fafc;
      border: 1px solid rgba(255, 255, 255, .12);
      box-shadow: 0 1rem 3rem rgba(0, 0, 0, .35);
    }

    #modal-banco-cuenta-bancaria .modal-header,
    #modal-banco-cuenta-bancaria .modal-footer {
      border-color: rgba(255, 255, 255, .12);
      background: rgba(15, 23, 42, .45);
    }

    #modal-banco-cuenta-bancaria .modal-title,
    #modal-banco-cuenta-bancaria .form-label {
      color: #f8fafc;
    }

    #modal-banco-cuenta-bancaria .form-control {
      background: #111827;
      color: #f8fafc;
      border-color: rgba(255, 255, 255, .18);
    }

    #modal-banco-cuenta-bancaria .form-control::placeholder {
      color: rgba(248, 250, 252, .55);
    }

    #modal-banco-cuenta-bancaria .form-control:focus {
      background: #111827;
      color: #fff;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 .2rem var(--primary02);
    }

    #modal-banco-cuenta-bancaria .btn-close {
      filter: invert(1) grayscale(100%) brightness(200%);
    }
  </style>
</head>

<body id="body-cuenta-bancaria">
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
          <div class="d-md-flex d-block align-items-center">

            <div>
              <p class="fw-semibold fs-18 mb-0 title-body-pagina">Cuentas Bancarias</p>
                <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cuentas Bancarias</li>
                </ol>
                </nav>
            </div>

          </div>

          <div class="btn-list mt-md-0 mt-2">
            @permiso('cuentas_bancarias', 'crear')
            <button type="button" class="btn btn-primary label-btn m-r-10px btn-nuevo-cuenta-bancaria" id="btn-nuevo-cuenta-bancaria">
              <i class="ri-bank-card-line label-btn-icon me-2"></i>Agregar Cuenta
            </button>
            @endpermiso
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card custom-card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">Listado de cuentas bancarias</div>
                <div class="d-flex align-items-center gap-2">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="incluir-eliminados-cuenta-bancaria">
                    <label class="form-check-label" for="incluir-eliminados-cuenta-bancaria">Incluir eliminados</label>
                  </div>
                  <button type="button" class="btn btn-icon btn-sm btn-light" id="btn-recargar-cuentas-bancarias" data-bs-toggle="tooltip" title="Actualizar">
                    <i class="las la-sync-alt"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table id="tabla-cuentas-bancarias" class="table table-bordered table-striped w-100">
                    <thead>
                      <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">OP</th>
                        <th>Banco</th>
                        <th>Persona</th>
                        <th>Cta. Cte.</th>
                        <th>CCI</th>
                        <th>Moneda</th>
                        <th>Tipo Cta.</th>
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
      </div>
    </div>
  </div>

  <!-- Modal Nueva Cuenta Bancaria -->
  <div class="modal fade" id="modal-nuevo-cuenta-bancaria" tabindex="-1" aria-labelledby="modal-nuevo-cuenta-bancaria-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title" id="modal-nuevo-cuenta-bancaria-label">Nueva Cuenta Bancaria</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="form-cuenta-bancaria" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row gy-3">
              <input type="hidden" name="idcuenta_bancaria" id="cuenta_bancaria_id">

              <div class="col-xl-6">
                <label for="cuenta_bancaria_banco" class="form-label d-flex align-items-center gap-2">
                  Banco <sup class="text-danger">*</sup>
                  @permiso('catalogo_general', 'crear')
                  <button type="button" class="badge bg-primary label-action-btn" id="btn-agregar-banco-cuenta" data-bs-toggle="tooltip" title="Agregar banco">
                    <i class="ri-add-line"></i>
                  </button>
                  @endpermiso
                </label>
                <select class="form-control select2" name="idbanco" id="cuenta_bancaria_banco" style="width: 100%;" required>
                  <option value="">Seleccione un banco</option>
                </select>
              </div>

              <div class="col-xl-6">
                <label for="cuenta_bancaria_persona" class="form-label">Persona <sup class="text-danger">*</sup></label>
                <select class="form-control select2" name="idpersona" id="cuenta_bancaria_persona" style="width: 100%;" required>
                  <option value="">Seleccione una persona</option>
                </select>
              </div>

              <div class="col-xl-6">
                <label for="cuenta_bancaria_cta_cte" class="form-label">Cuenta Corriente</label>
                <input type="text" class="form-control" name="cta_cte" id="cuenta_bancaria_cta_cte" maxlength="45" placeholder="Ej. 123-4567890">
              </div>

              <div class="col-xl-6">
                <label for="cuenta_bancaria_cci" class="form-label">CCI</label>
                <input type="text" class="form-control" name="cci" id="cuenta_bancaria_cci" maxlength="45" placeholder="Ej. 002-123-4567890-12">
              </div>

              <div class="col-xl-6">
                <label for="cuenta_bancaria_moneda" class="form-label">Moneda</label>
                <select class="form-control" name="moneda" id="cuenta_bancaria_moneda">
                  <option value="">Seleccione moneda</option>
                  <option value="PEN">PEN - Soles</option>
                  <option value="USD">USD - Dólares</option>
                </select>
              </div>

              <div class="col-xl-6">
                <label for="cuenta_bancaria_tipo_cuenta" class="form-label">Tipo de Cuenta</label>
                <select class="form-control" name="tipo_cuenta" id="cuenta_bancaria_tipo_cuenta">
                  <option value="">Seleccione tipo</option>
                  <option value="AHORRO">Ahorro</option>
                  <option value="CORRIENTE">Corriente</option>
                </select>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-success guardar_cuenta_bancaria">Guardar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Nuevo Banco -->
  <div class="modal fade" id="modal-banco-cuenta-bancaria" tabindex="-1" aria-labelledby="modal-banco-cuenta-bancaria-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title" id="modal-banco-cuenta-bancaria-label">Nuevo Banco</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="form-banco-cuenta-bancaria" method="POST">
            @csrf
            <div class="row gy-3">
              <div class="col-12">
                <label for="banco_cuenta_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                <input type="text" class="form-control" name="nombre" id="banco_cuenta_nombre" maxlength="65" placeholder="Ej. Banco de Credito del Peru" required>
              </div>

              <div class="col-12">
                <label for="banco_cuenta_alias" class="form-label">Alias</label>
                <input type="text" class="form-control" name="alias" id="banco_cuenta_alias" maxlength="65" placeholder="Ej. BCP">
              </div>

              <div class="col-md-6">
                <label for="banco_cuenta_formato_cta" class="form-label">Formato cuenta</label>
                <input type="text" class="form-control" name="formato_cta" id="banco_cuenta_formato_cta" maxlength="50" placeholder="Ej. 000-0000000000">
              </div>

              <div class="col-md-6">
                <label for="banco_cuenta_formato_cci" class="form-label">Formato CCI</label>
                <input type="text" class="form-control" name="formato_cci" id="banco_cuenta_formato_cci" maxlength="50" placeholder="Ej. 000-000-0000000000-00">
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-success" id="btn-guardar-banco-cuenta">
            <i class="ti ti-device-floppy me-1"></i> Guardar
          </button>
        </div>
      </div>
    </div>
  </div>

    @include('layouts.yn_search_modal')
    @include('layouts.yn_footer')
  </div>

  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')

  <script src="{{ asset('assets/js/cuenta_bancaria.js') }}?v={{ filemtime(public_path('assets/js/cuenta_bancaria.js')) }}"></script>
</body>
</html>
