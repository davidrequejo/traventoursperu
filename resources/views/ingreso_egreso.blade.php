<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Ingreso y Egreso'])

  <style>
    #tabla-ingreso-egreso_filter {
      width: calc(100% - 10px) !important;
      display: flex !important;
      justify-content: space-between !important;
    }

    #tabla-ingreso-egreso_filter label,
    #tabla-ingreso-egreso_filter label input {
      width: 100% !important;
    }

    .ingreso-egreso-form-shell {
      max-width: 1120px;
      margin: 0 auto;
    }

    .ingreso-egreso-fieldset {
      border: 1px solid var(--default-border);
      border-radius: .5rem;
      padding: .85rem 1rem 1rem;
      margin: 0 0 .85rem;
      background: var(--custom-white);
    }

    .ingreso-egreso-fieldset legend {
      float: none;
      width: auto;
      margin: 0 0 .35rem;
      padding: .2rem .55rem;
      border-radius: .45rem;
      background: var(--primary01);
      color: var(--primary-color);
      font-size: .82rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
    }

    .ingreso-egreso-fieldset .form-label {
      font-weight: 600;
      margin-bottom: .35rem;
    }

    .label-action-btn {
      width: 24px;
      height: 24px;
      border-radius: 7px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }
  </style>
</head>

<body id="body-ingreso-egreso">
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
          <div>
            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Ingreso y Egreso</p>
            <nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item">Contabilidad</li>
                <li class="breadcrumb-item active" aria-current="page">Ingreso y Egreso</li>
              </ol>
            </nav>
          </div>

          <div class="btn-list mt-md-0 mt-2">
            <div class="form-check form-switch d-inline-flex align-items-center mb-0 me-2">
              <input class="form-check-input" type="checkbox" role="switch" id="incluir-papelera-ingreso-egreso">
              <label class="form-check-label fs-12 text-muted ms-2" for="incluir-papelera-ingreso-egreso">Incluir papelera</label>
            </div>
            @permiso('ingreso_y_egreso', 'crear')
            <div class="btn-group m-r-10px" id="div-btn-nuevo-ingreso-egreso">
              <button type="button" class="btn btn-primary me-0" id="btn-nuevo-ingreso-egreso">
                <i class="ri-add-circle-line me-2"></i>Agregar
              </button>
            </div>
            @endpermiso
            <button type="button" class="btn btn-light label-btn m-r-10px" id="btn-regresar-ingreso-egreso" style="display:none;">
              <i class="bi bi-arrow-left label-btn-icon me-2"></i>Regresar
            </button>
          </div>
        </div>

        <div id="div-tabla-ingreso-egreso">
          <div class="card custom-card">
            <div class="card-body">
              <div class="table-responsive">
                <table id="tabla-ingreso-egreso" class="table table-bordered table-striped w-100">
                  <thead>
                    <tr>
                      <th class="text-center">OP</th>
                      <th>Fecha</th>
                      <th class="text-center">Movimiento</th>
                      <th>Categoria</th>
                      <th>Trabajador/Proveedor</th>
                      <th>Comprobante</th>
                      <th class="text-end">Total</th>
                      <th class="text-center">Estado</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div id="div-formulario-ingreso-egreso" style="display:none;">
          <div class="ingreso-egreso-form-shell">
            <div class="card custom-card">
              <form id="form-ingreso-egreso" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="idingreso_egreso" id="idingreso_egreso">
                <input type="hidden" name="comprobante_actual" id="ingreso_egreso_comprobante_actual">

                <div class="card-header justify-content-between flex-wrap gap-2">
                  <div>
                    <h5 class="card-title mb-1" id="titulo-formulario-ingreso-egreso">Nuevo ingreso/egreso</h5>
                    <p class="text-muted mb-0 fs-12">Registra el comprobante, los responsables y los importes.</p>
                  </div>
                  <div class="btn-list">
                    <button type="button" class="btn btn-outline-danger" id="btn-cancelar-ingreso-egreso">
                      <i class="ti ti-circle-dashed-x"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-ingreso-egreso">
                      <i class="ti ti-device-floppy"></i> Guardar
                    </button>
                  </div>
                </div>

                <div class="card-body">
                  <div class="row gy-3">
                    <div class="col-md-6">
                      <fieldset class="ingreso-egreso-fieldset">
                        <legend><i class="ri-user-settings-line"></i>Personas</legend>
                        <div class="row gy-2">
                          <div class="col-12">
                            <label for="ingreso_egreso_idproveedor" class="form-label d-flex align-items-center gap-2">
                              Proveedor
                              @permiso('ingreso_y_egreso', 'crear')
                              <button type="button" class="btn btn-sm btn-primary label-action-btn" id="btn-nuevo-proveedor-rapido" title="Nuevo proveedor">
                                <i class="ti ti-plus"></i>
                              </button>
                              @endpermiso
                            </label>
                            <select class="form-control" name="idproveedor" id="ingreso_egreso_idproveedor" style="width:100%;"></select>
                          </div>
                          <div class="col-12">
                            <label for="ingreso_egreso_idtrabajador" class="form-label">Trabajador</label>
                            <select class="form-control" name="idtrabajador" id="ingreso_egreso_idtrabajador" style="width:100%;"></select>
                          </div>
                          <div class="col-12">
                            <label for="ingreso_egreso_categoria" class="form-label d-flex align-items-center gap-2">
                              Categoria <sup class="text-danger">*</sup>
                              @permiso('ingreso_y_egreso', 'crear')
                              <button type="button" class="btn btn-sm btn-primary label-action-btn" id="btn-nueva-categoria-rapida" title="Nueva categoria">
                                <i class="ti ti-plus"></i>
                              </button>
                              @endpermiso
                            </label>
                            <select class="form-control" name="idotros_gastos_categoria" id="ingreso_egreso_categoria" style="width:100%;"></select>
                          </div>
                        </div>
                      </fieldset>
                    </div>

                    <div class="col-md-6">
                      <fieldset class="ingreso-egreso-fieldset">
                        <legend><i class="ri-file-list-3-line"></i>Comprobante</legend>
                        <div class="row gy-2">
                          <div class="col-md-6">
                            <label for="ingreso_egreso_fecha" class="form-label">Fecha <sup class="text-danger">*</sup></label>
                            <input type="date" class="form-control" name="fecha_ingreso" id="ingreso_egreso_fecha" required>
                          </div>
                          <div class="col-md-6">
                            <label for="ingreso_egreso_tipo_movimiento" class="form-label">Movimiento <sup class="text-danger">*</sup></label>
                            <select class="form-control" name="tipo_movimiento" id="ingreso_egreso_tipo_movimiento" required>
                              <option value="">Seleccione</option>
                              <option value="INGRESO">INGRESO</option>
                              <option value="EGRESO">EGRESO</option>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label for="ingreso_egreso_tipo_comprobante" class="form-label">Tipo comprobante</label>
                            <select class="form-control" name="tipo_comprobante" id="ingreso_egreso_tipo_comprobante"></select>
                          </div>
                          <div class="col-md-6">
                            <label for="ingreso_egreso_serie_comprobante" class="form-label">Serie</label>
                            <input type="text" class="form-control" name="serie_comprobante" id="ingreso_egreso_serie_comprobante" maxlength="30">
                          </div>
                          <div class="col-md-6">
                            <label for="ingreso_egreso_comprobante_file" class="form-label">Archivo</label>
                            <input type="file" class="form-control" name="comprobante_file" id="ingreso_egreso_comprobante_file" accept=".pdf,image/*">
                            <a href="javascript:void(0);" class="fs-12 d-none" id="link-comprobante-actual" target="_blank">Ver comprobante actual</a>
                          </div>
                        </div>
                      </fieldset>
                    </div>

                    <div class="col-md-12">
                      <fieldset class="ingreso-egreso-fieldset">
                        <legend><i class="ri-money-dollar-circle-line"></i>Importes</legend>
                        <div class="row gy-2">
                          <div class="col-md-3">
                            <label for="ingreso_egreso_precio_sin_igv" class="form-label">Sin IGV <sup class="text-danger">*</sup></label>
                            <input type="number" class="form-control js-calcular-igv" name="precio_sin_igv" id="ingreso_egreso_precio_sin_igv" min="0" step="0.01" required>
                          </div>
                          <div class="col-md-3">
                            <label for="ingreso_egreso_val_igv" class="form-label">% IGV</label>
                            <input type="number" class="form-control js-calcular-igv" name="val_igv" id="ingreso_egreso_val_igv" min="0" max="100" step="0.01" value="18">
                          </div>
                          <div class="col-md-3">
                            <label for="ingreso_egreso_precio_igv" class="form-label">IGV</label>
                            <input type="number" class="form-control" name="precio_igv" id="ingreso_egreso_precio_igv" min="0" step="0.01">
                          </div>
                          <div class="col-md-3">
                            <label for="ingreso_egreso_precio_con_igv" class="form-label">Con IGV <sup class="text-danger">*</sup></label>
                            <input type="number" class="form-control" name="precio_con_igv" id="ingreso_egreso_precio_con_igv" min="0" step="0.01" required>
                          </div>
                        </div>
                      </fieldset>
                    </div>

                    <div class="col-md-12">
                      <fieldset class="ingreso-egreso-fieldset mb-0">
                        <legend><i class="ri-chat-1-line"></i>Descripcion</legend>
                        <textarea class="form-control" name="descripcion_comprobante" id="ingreso_egreso_descripcion_comprobante" rows="3"></textarea>
                      </fieldset>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal-categoria-rapida-ingreso-egreso" tabindex="-1" aria-labelledby="modal-categoria-rapida-ingreso-egreso-label" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-categoria-rapida-ingreso-egreso-label">Nueva categoria</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="form-categoria-rapida-ingreso-egreso" method="POST">
              @csrf
              <div class="mb-3">
                <label for="categoria_rapida_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                <input type="text" class="form-control" id="categoria_rapida_nombre" maxlength="100">
              </div>
              <div class="mb-0">
                <label for="categoria_rapida_descripcion" class="form-label">Descripcion</label>
                <textarea class="form-control" id="categoria_rapida_descripcion" rows="2" maxlength="250"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary" id="btn-guardar-categoria-rapida">
              <i class="ti ti-device-floppy me-1"></i> Guardar
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal-proveedor-rapido-ingreso-egreso" tabindex="-1" aria-labelledby="modal-proveedor-rapido-ingreso-egreso-label" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-proveedor-rapido-ingreso-egreso-label">Nuevo proveedor</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="form-proveedor-rapido-ingreso-egreso" method="POST">
              @csrf
              <fieldset class="ingreso-egreso-fieldset">
                <legend><i class="ri-profile-line"></i>Informacion general</legend>
                <div class="row gy-3">
                  <div class="col-xl-3 col-md-6">
                    <label for="proveedor_rapido_tipo_persona_sunat" class="form-label">Tipo persona <sup class="text-danger">*</sup></label>
                    <select class="form-control" id="proveedor_rapido_tipo_persona_sunat" style="width:100%;">
                      <option value="JURIDICA">JURIDICA</option>
                      <option value="NATURAL">NATURAL</option>
                    </select>
                  </div>
                  <div class="col-xl-3 col-md-6">
                    <label for="proveedor_rapido_tipo_documento" class="form-label">Tipo documento <sup class="text-danger">*</sup></label>
                    <select class="form-control" id="proveedor_rapido_tipo_documento" style="width:100%;">
                      <option value="6">RUC</option>
                      <option value="1">DNI</option>
                      <option value="7">EXTRANJERO</option>
                    </select>
                  </div>
                  <div class="col-xl-3 col-md-6">
                    <label for="proveedor_rapido_numero_documento" class="form-label">Nro documento <sup class="text-danger">*</sup></label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="proveedor_rapido_numero_documento" maxlength="20">
                      <button class="btn btn-primary" type="button" id="btn-buscar-proveedor-documento" title="Buscar RENIEC/SUNAT">
                        <i class="bx bx-search-alt" id="search_proveedor_rapido"></i>
                        <span class="spinner-border spinner-border-sm" role="status" id="charge_proveedor_rapido" style="display:none;"></span>
                      </button>
                    </div>
                    <div class="mt-1 valido_novalido_proveedor"><span class="badge bg-primary">Por verificar</span></div>
                  </div>
                  <div class="col-xl-3 col-md-6">
                    <label for="proveedor_rapido_descripcion" class="form-label">Descripcion <sup class="text-danger">*</sup></label>
                    <input type="text" class="form-control" id="proveedor_rapido_descripcion" maxlength="255">
                  </div>
                  <div class="col-xl-4 col-md-6 proveedor-rapido-juridica">
                    <label for="proveedor_rapido_nombre_comercial" class="form-label">Nombre comercial</label>
                    <input type="text" class="form-control" id="proveedor_rapido_nombre_comercial" maxlength="255">
                  </div>
                  <div class="col-xl-4 col-md-6 proveedor-rapido-natural">
                    <label for="proveedor_rapido_nombre_persona_natural" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="proveedor_rapido_nombre_persona_natural" maxlength="100">
                  </div>
                  <div class="col-xl-4 col-md-6 proveedor-rapido-natural">
                    <label for="proveedor_rapido_apellido_paterno" class="form-label">Apellido paterno</label>
                    <input type="text" class="form-control" id="proveedor_rapido_apellido_paterno" maxlength="100">
                  </div>
                  <div class="col-xl-4 col-md-6 proveedor-rapido-natural">
                    <label for="proveedor_rapido_apellido_materno" class="form-label">Apellido materno</label>
                    <input type="text" class="form-control" id="proveedor_rapido_apellido_materno" maxlength="100">
                  </div>
                  <div class="col-xl-3 col-md-6 proveedor-rapido-natural">
                    <label for="proveedor_rapido_sexo" class="form-label">Sexo</label>
                    <select class="form-control" id="proveedor_rapido_sexo" style="width:100%;">
                      <option value="">Seleccione</option>
                      <option value="M">Masculino</option>
                      <option value="F">Femenino</option>
                    </select>
                  </div>
                  <div class="col-xl-3 col-md-6 proveedor-rapido-natural">
                    <label for="proveedor_rapido_fecha_nacimiento" class="form-label">Fecha nacimiento</label>
                    <input type="date" class="form-control" id="proveedor_rapido_fecha_nacimiento">
                  </div>
                  <div class="col-xl-3 col-md-6 proveedor-rapido-natural">
                    <label for="proveedor_rapido_estado_civil" class="form-label">Estado civil</label>
                    <select class="form-control" id="proveedor_rapido_estado_civil" style="width:100%;">
                      <option value="">Seleccione</option>
                      <option value="SOLTERO">Soltero/a</option>
                      <option value="CASADO">Casado/a</option>
                      <option value="DIVORCIADO">Divorciado/a</option>
                      <option value="VIUDO">Viudo/a</option>
                    </select>
                  </div>
                  <div class="col-xl-3 col-md-6">
                    <label for="proveedor_rapido_nacionalidad" class="form-label">Nacionalidad</label>
                    <input type="text" class="form-control" id="proveedor_rapido_nacionalidad" maxlength="50" value="PERUANA">
                  </div>
                </div>
              </fieldset>

              <fieldset class="ingreso-egreso-fieldset mb-0">
                <legend><i class="ri-map-pin-line"></i>Contacto y direccion</legend>
                <div class="row gy-3">
                  <div class="col-xl-4 col-md-6">
                    <label for="proveedor_rapido_celular" class="form-label">Celular</label>
                    <input type="text" class="form-control" id="proveedor_rapido_celular" maxlength="15">
                  </div>
                  <div class="col-xl-8 col-md-6">
                    <label for="proveedor_rapido_correo" class="form-label">Correo</label>
                    <input type="email" class="form-control" id="proveedor_rapido_correo" maxlength="255">
                  </div>
                  <div class="col-xl-6">
                    <label for="proveedor_rapido_direccion" class="form-label">Direccion</label>
                    <textarea class="form-control" id="proveedor_rapido_direccion" rows="2"></textarea>
                  </div>
                  <div class="col-xl-6">
                    <label for="proveedor_rapido_direccion_referencia" class="form-label">Referencia de direccion</label>
                    <textarea class="form-control" id="proveedor_rapido_direccion_referencia" rows="2"></textarea>
                  </div>
                  <div class="col-xl-4 col-md-6">
                    <label for="proveedor_rapido_iddistrito" class="form-label">Distrito</label>
                    <select class="form-control" id="proveedor_rapido_iddistrito" style="width:100%;"></select>
                  </div>
                  <div class="col-xl-3 col-md-6">
                    <label for="proveedor_rapido_provincia" class="form-label">Provincia</label>
                    <input type="text" class="form-control" id="proveedor_rapido_provincia" readonly>
                  </div>
                  <div class="col-xl-3 col-md-6">
                    <label for="proveedor_rapido_departamento" class="form-label">Departamento</label>
                    <input type="text" class="form-control" id="proveedor_rapido_departamento" readonly>
                  </div>
                  <div class="col-xl-2 col-md-6">
                    <label for="proveedor_rapido_cod_ubigeo" class="form-label">Ubigeo</label>
                    <input type="text" class="form-control" id="proveedor_rapido_cod_ubigeo" maxlength="10">
                  </div>
                </div>
              </fieldset>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary" id="btn-guardar-proveedor-rapido">
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

  <script src="{{ asset('assets/js/ingreso_egreso.js') }}?v={{ filemtime(public_path('assets/js/ingreso_egreso.js')) }}"></script>
</body>

</html>
