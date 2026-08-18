<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Empresa'])

  <style>
    #tabla-empresa_filter { width: calc(100% - 10px) !important; display: flex !important; justify-content: space-between !important; }
    #tabla-empresa_filter label { width: 100% !important;  }
    #tabla-empresa_filter label input { width: 100% !important;   }
    
    .empresa-form-shell {
      max-width: 1120px;
      margin: 0 auto;
    }

    .empresa-fieldset {
      border: 1px dashed var(--default-border);
      border-radius: .5rem;
      padding: .85rem 1rem 1rem;
      margin: 0 0 .85rem;
      background: var(--custom-white);
      position: relative;
    }

    .empresa-fieldset legend {
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

    .empresa-fieldset .form-label {
      font-weight: 600;
      margin-bottom: .35rem;
    }

    .empresa-logo-preview {
      width: 8rem;
      height: 8rem;
      object-fit: contain;
      background: var(--default-background);
    }

    .empresa-sunat-icon {
      width: 1.65rem;
      height: 1.65rem;
      object-fit: contain;
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
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
          <div>
            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Empresa</p>
            <nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Empresa</li>
              </ol>
            </nav>
          </div>

          <div class="btn-list mt-md-0 mt-2">
            @permiso('empresa', 'crear')
            <button type="button" class="btn btn-primary label-btn m-r-10px" id="btn-nueva-empresa" data-empresa-registrada="{{ $empresaRegistrada ? '1' : '0' }}" @if ($empresaRegistrada) style="display:none;" @endif>
              <i class="ri-building-4-line label-btn-icon me-2"></i>Agregar
            </button>
            @endpermiso
            <button type="button" class="btn btn-light label-btn m-r-10px" id="btn-regresar-empresa" style="display:none;">
              <i class="bi bi-arrow-left label-btn-icon me-2"></i>Regresar
            </button>
          </div>
        </div>

        <div id="div-tabla-empresa">
          <div class="card custom-card">
            
            <div class="card-body">
              <div class="table-responsive">
                <table id="tabla-empresa" class="table table-bordered table-striped w-100">
                  <thead>
                    <tr>
                      <th class="text-center">#</th>
                      <th class="text-center">OP</th>
                      <th>Empresa</th>
                      <th>Documento</th>
                      <th>Contacto</th>
                      <th>Ubicacion</th>
                      <th class="text-center">SUNAT</th>
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

        <div id="div-formulario-empresa" style="display:none;">
          <div class="empresa-form-shell">
            <form id="form-empresa" method="POST" enctype="multipart/form-data">
              @csrf
              <input type="hidden" name="idempresa" id="empresa_idempresa">
              <input type="hidden" name="logo_actual" id="empresa_logo_actual">
              <input type="hidden" id="empresa_tipo_persona_sunat" value="JURIDICA">
              <input type="hidden" name="ubigueo" id="empresa_ubigueo_val">

              <div class="card custom-card">
                <div class="card-header justify-content-between flex-wrap gap-2">
                  <div>
                    <h5 class="card-title mb-1" id="titulo-formulario-empresa">Nueva empresa</h5>
                    <p class="text-muted mb-0 fs-12">Configura la informacion principal usada en el sistema.</p>
                  </div>
                  <div class="btn-list">
                    <button type="button" class="btn btn-outline-danger" id="btn-cancelar-empresa">
                      <i class="ti ti-circle-dashed-x"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-empresa">
                      <i class="ti ti-device-floppy"></i> Guardar
                    </button>
                  </div>
                </div>

                <div class="card-body">
                  <div class="row gy-4">
                    <div class="col-xl-8">
                      <fieldset class="empresa-fieldset">
                        <legend><i class="ri-building-4-line"></i>Identidad fiscal</legend>
                        <div class="row gy-1">
                          <div class="col-md-12">
                            <label for="empresa_idpersona" class="form-label">Persona vinculada</label>
                            <select class="form-control" name="idpersona" id="empresa_idpersona" style="width:100%;"></select>
                            <label class="form-label mt-1 fs-12 text-muted mb-0">Opcional. Si seleccionas una persona, se vinculara como representante legal y no reemplazara los datos fiscales de la empresa.</label>
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_tipo_documento" class="form-label">Tipo documento</label>
                            <select class="form-control" name="tipo_documento" id="empresa_tipo_documento">
                              <option value="{{ $tipoDocumentoRucId }}">RUC</option>
                            </select>
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_numero_documento" class="form-label">Numero documento <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                              <input type="text" class="form-control" name="numero_documento" id="empresa_numero_documento">
                              <button type="button" class="btn btn-primary" id="btn-buscar-documento-empresa" data-bs-toggle="tooltip" title="Buscar">
                                <i class="bx bx-search-alt" id="search" ></i>    
                                <div class="spinner-border spinner-border-sm" role="status" id="charge" style="display: none;">
                                  <span class="visually-hidden">Loading...</span>
                                </div>                                 
                              </button>
                            </div>
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_codigo_pais" class="form-label">Codigo pais</label>
                            <input type="text" class="form-control" name="codigo_pais" id="empresa_codigo_pais" value="PE">
                          </div>
                          <div class="col-md-8">
                            <label for="empresa_nombre_razon_social" class="form-label">Razon social <sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="nombre_razon_social" id="empresa_nombre_razon_social">
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_nombre_comercial" class="form-label">Nombre comercial</label>
                            <input type="text" class="form-control" name="nombre_comercial" id="empresa_nombre_comercial">
                          </div>
                        </div>
                      </fieldset>

                      <fieldset class="empresa-fieldset">
                        <legend><i class="ri-map-pin-line"></i>Ubicacion y contacto</legend>
                        <div class="row gy-1">
                          <div class="col-md-12">
                            <label for="empresa_domicilio_fiscal" class="form-label">Domicilio fiscal</label>
                            <input type="text" class="form-control" name="domicilio_fiscal" id="empresa_domicilio_fiscal">
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_ubigueo" class="form-label">Distrito</label>
                            <select class="form-control" id="empresa_ubigueo" style="width:100%;"></select>
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_provincia" class="form-label">Provincia</label>
                            <input type="text" class="form-control" name="provincia" id="empresa_provincia">
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_departamento" class="form-label">Departamento</label>
                            <input type="text" class="form-control" name="departamento" id="empresa_departamento">
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_distrito" class="form-label">Distrito texto</label>
                            <input type="text" class="form-control" name="distrito" id="empresa_distrito">
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_codubigueo" class="form-label">Cod. ubigeo</label>
                            <input type="text" class="form-control" name="codubigueo" id="empresa_codubigueo">
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_correo" class="form-label">Correo</label>
                            <input type="email" class="form-control" name="correo" id="empresa_correo">
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_telefono1" class="form-label">Telefono 1</label>
                            <input type="text" class="form-control" name="telefono1" id="empresa_telefono1">
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_telefono2" class="form-label">Telefono 2</label>
                            <input type="text" class="form-control" name="telefono2" id="empresa_telefono2">
                          </div>
                          <div class="col-md-4">
                            <label for="empresa_web" class="form-label">Web</label>
                            <input type="text" class="form-control" name="web" id="empresa_web">
                          </div>
                        </div>
                      </fieldset>

                      <div class="alert alert-light mb-0">
                        Las cuentas bancarias ahora se gestionan en su propio modulo y ya no se guardan en la tabla <strong>empresa</strong>.
                      </div>
                    </div>

                    <div class="col-xl-4">
                      <div class="card custom-card shadow-none border mb-3">
                        <div class="card-body text-center">
                          <p class="fw-semibold mb-3">Logo</p>
                          <span class="avatar avatar-xxl avatar-rounded d-inline-flex mb-3">
                            <img src="{{ asset('ynex_admin/svg/empresa-logo.svg') }}" alt="" class="empresa-logo-preview" id="empresa_logo_preview">
                          </span>
                          <input type="file" class="form-control" name="logo_file" id="empresa_logo_file" accept="image/*">
                          <div class="btn-list mt-3">
                            <button type="button" class="btn btn-light btn-sm" id="btn-remover-logo-empresa">
                              <i class="bi bi-trash me-1"></i>Remover
                            </button>
                          </div>
                        </div>
                      </div>

                      <div class="card custom-card shadow-none border mb-0">
                        <div class="card-body">
                          <fieldset class="empresa-fieldset mb-0">
                            <legend><i class="ri-printer-line"></i>Comprobantes</legend>
                            <div class="row gy-3">
                              <div class="col-md-12">
                                <label for="empresa_logo_c_r" class="form-label">Formato logo</label>
                                <select class="form-control" name="logo_c_r" id="empresa_logo_c_r">
                                  <option value="1">Cuadrado</option>
                                  <option value="0">Rectangular</option>
                                </select>
                              </div>
                              <div class="col-md-12">
                                <label for="empresa_venta" class="form-label">IGV (%)</label>
                                <input type="number" class="form-control" name="venta" id="empresa_venta" min="0" max="100" step="0.01" placeholder="18.00">
                              </div>
                              <div class="col-md-12">
                                <label for="empresa_web_consulta_cp" class="form-label">Web consulta comprobante</label>
                                <input type="text" class="form-control" name="web_consulta_cp" id="empresa_web_consulta_cp">
                              </div>
                              <div class="col-md-12">
                                <label for="empresa_pie_impresion" class="form-label">Pie de impresion</label>
                                <textarea class="form-control" name="pie_impresion" id="empresa_pie_impresion" rows="4" maxlength="300"></textarea>
                              </div>
                            </div>
                          </fieldset>
                        </div>
                      </div>
                    </div>

                    <div class="col-xl-12">
                      <fieldset class="empresa-fieldset mb-0">
                        <legend><i class="ri-file-shield-2-line"></i>Facturacion electronica</legend>
                        <div class="row gy-3">
                          <div class="col-md-3">
                            <label for="empresa_fe_activo" class="form-label">Estado</label>
                            <select class="form-control" name="fe_activo" id="empresa_fe_activo">
                              <option value="0">Inactiva</option>
                              <option value="1">Activa</option>
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label for="empresa_fe_ambiente" class="form-label">Ambiente SUNAT</label>
                            <select class="form-control" name="fe_ambiente" id="empresa_fe_ambiente">
                              <option value="beta">Beta(prueba)</option>
                              <option value="production">Produccion</option>
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label for="empresa_fe_sol_usuario" class="form-label">Usuario SOL</label>
                            <input type="text" class="form-control" name="fe_sol_usuario" id="empresa_fe_sol_usuario" placeholder="Sin RUC">
                          </div>
                          <div class="col-md-3">
                            <label for="empresa_fe_sol_clave" class="form-label">Clave SOL</label>
                            <div class="input-group">
                              <input type="password" class="form-control" name="fe_sol_clave" id="empresa_fe_sol_clave" autocomplete="new-password">
                              <button type="button" class="btn btn-light btn-toggle-password-empresa" data-target="#empresa_fe_sol_clave" title="Ver clave">
                                <i class="ri-eye-line"></i>
                              </button>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label for="empresa_fe_certificado_tipo" class="form-label">Tipo certificado</label>
                            <select class="form-control" name="fe_certificado_tipo" id="empresa_fe_certificado_tipo">
                              <option value="pem">PEM</option>
                              <option value="p12">P12</option>
                              <!-- <option value="pfx">PFX</option> -->
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label for="empresa_fe_certificado_password" class="form-label">Password certificado</label>
                            <div class="input-group">
                              <input type="password" class="form-control" name="fe_certificado_password" id="empresa_fe_certificado_password" autocomplete="new-password">
                              <button type="button" class="btn btn-light btn-toggle-password-empresa" data-target="#empresa_fe_certificado_password" title="Ver password">
                                <i class="ri-eye-line"></i>
                              </button>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <label for="empresa_fe_codigo_local" class="form-label">Codigo local</label>
                            <input type="text" class="form-control" name="fe_codigo_local" id="empresa_fe_codigo_local" value="0000">
                          </div>
                          <div class="col-md-3">
                            <label for="empresa_fe_certificado_file" class="form-label">Certificado digital</label>
                            <input type="file" class="form-control" name="fe_certificado_file" id="empresa_fe_certificado_file">
                            <div class="form-text" id="empresa_fe_certificado_actual_text">Sin certificado cargado</div>
                            <div class="btn-list mt-2" id="empresa_fe_certificado_descargas" style="display:none;">
                              <a href="#" class="btn btn-sm btn-outline-primary" id="empresa_fe_descargar_pem" target="_blank">
                                <i class="ri-download-2-line me-1"></i>PEM sistema
                              </a>
                              <a href="#" class="btn btn-sm btn-outline-success" id="empresa_fe_descargar_cer" target="_blank">
                                <i class="ri-download-2-line me-1"></i>CER SUNAT
                              </a>
                            </div>
                          </div>
                        </div>
                      </fieldset>
                    </div>
                  </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                  <button type="submit" class="btn btn-primary" id="btn-guardar-empresa-bottom">
                    <i class="ti ti-device-floppy"></i> Guardar
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <div class="dropdown-menu shadow" id="menu-contextual" style="display:none; position:absolute; z-index:1055; min-width: 260px;">
          <ul class="nav nav-pills flex-column">
            @permiso('empresa', 'editar')
            <li class="dropdown-header">SUNAT</li>
            <li class="nav-item">
              <a href="#" class="nav-link py-1 d-flex align-items-center gap-2" id="opcion-empresa-toggle-sunat">
                <img src="{{ asset('assets/images/company-logos/ico-sunat.svg') }}" alt="" class="empresa-sunat-icon">
                <span>Activar estado SUNAT</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link py-1 d-flex align-items-center gap-2" id="opcion-empresa-toggle-ambiente">
                <img src="{{ asset('assets/images/company-logos/ico-sunat.svg') }}" alt="" class="empresa-sunat-icon">
                <span>Cambiar a produccion</span>
              </a>
            </li>
            @endpermiso
          </ul>
        </div>

      </div>
    </div>

    @include('layouts.yn_search_modal')
    @include('layouts.yn_footer')
  </div>

  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')

  <script src="{{ asset('assets/js/empresa.js') }}?v={{ filemtime(public_path('assets/js/empresa.js')) }}"></script>
</body>

</html>
