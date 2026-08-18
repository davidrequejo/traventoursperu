<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Trabajadores'])

  <style>

    #tabla-trabajadores_filter { width: calc(100% - 10px) !important; display: flex !important; justify-content: space-between !important; }
    #tabla-trabajadores_filter label { width: 100% !important;  }
    #tabla-trabajadores_filter label input { width: 100% !important;   }

    .trabajador-persona {
      min-width: 260px;
    }

    .trabajador-form-shell {
      max-width: 1180px;
      margin: 0 auto;
    }

    .trabajador-section-title {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      margin-bottom: 1rem;
      font-weight: 700;
    }

    .trabajador-section-title .avatar {
      flex: 0 0 auto;
    }

    .trabajador-photo-preview {
      width: 7rem;
      height: 7rem;
      object-fit: cover;
    }
  </style>
</head>

<body id="body-trabajador">
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
          <div>
            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Trabajadores</p>
            <nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Trabajadores</li>
              </ol>
            </nav>
          </div>

          <div class="btn-list mt-md-0 mt-2">
            @permiso('trabajadores', 'crear')
            <button type="button" class="btn btn-primary label-btn m-r-10px" id="btn-nuevo-trabajador">
              <i class="ri-user-add-line label-btn-icon me-2"></i>Agregar
            </button>
            @endpermiso
            <button type="button" class="btn btn-light label-btn m-r-10px" id="btn-regresar-trabajador" style="display:none;">
              <i class="bi bi-arrow-left label-btn-icon me-2"></i>Regresar
            </button>
          </div>
        </div>

        <div id="div-tabla-trabajadores">
          <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div class="card-title mb-0">Listado de trabajadores</div>
              <button type="button" class="btn btn-icon btn-sm btn-light" id="btn-recargar-trabajadores" data-bs-toggle="tooltip" title="Actualizar">
                <i class="las la-sync-alt"></i>
              </button>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="tabla-trabajadores" class="table table-bordered table-striped w-100">
                  <thead>
                    <tr>
                      <th class="text-center">Codigo</th>
                      <th class="text-center">OP</th>
                      <th>Trabajador</th>
                      <th>Cargo</th>
                      <th>Contacto</th>
                      <th>Direccion</th>
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

        <div id="div-formulario-trabajador" style="display:none;">
          <div class="card custom-card p-0">
            <form id="form-trabajador" method="POST" enctype="multipart/form-data">
              @csrf
              <input type="hidden" name="idpersona" id="trabajador_idpersona">
              <input type="hidden" name="idpersona_tipo" id="trabajador_idpersona_tipo" value="2">
              <input type="hidden" name="iddistrito_envio" id="trabajador_iddistrito_envio">
              <input type="hidden" name="imagenactual" id="trabajador_imagenactual">

              <div class="card-header border-bottom justify-content-between flex-wrap gap-2">
                <div>
                  <h5 class="card-title mb-1" id="titulo-formulario-trabajador">Nuevo trabajador</h5>
                  <p class="text-muted mb-0 fs-12">Registra datos personales, contacto, direccion e informacion laboral.</p>
                </div>
                <div class="btn-list">
                  <button type="button" class="btn btn-outline-danger" id="btn-cancelar-trabajador">
                    <i class="ti ti-circle-dashed-x"></i> Cancelar
                  </button>
                  <button type="submit" class="btn btn-primary" id="btn-guardar-trabajador">
                    <i class="ti ti-device-floppy"></i> Guardar
                  </button>
                </div>
              </div>

              <div class="card-body py-0">
                <div class="row">
                  <div class="col-xxl-3 col-xl-4">
                    <ul class="nav flex-column nav-pills add-product-details-nav pt-3" role="tablist">
                      <li class="nav-item m-1" role="presentation">
                        <a class="nav-link d-inline-flex w-100 mb-3 gap-2 align-items-center active"
                          id="trabajador-tab-general" data-bs-toggle="tab" data-bs-target="#trabajador-pane-general"
                          href="#trabajador-pane-general" aria-selected="true" role="tab">
                          <span class="avatar avatar-lg border avatar-rounded">
                            <span class="avatar avatar-md avatar-rounded">
                              <i class="ti ti-id fs-20"></i>
                            </span>
                          </span>
                          <div>
                            <p class="mb-1 fs-15 fw-semibold">Informacion General</p>
                            <span class="text-muted fs-13">Datos basicos del trabajador</span>
                          </div>
                        </a>
                      </li>
                      <li class="nav-item m-1" role="presentation">
                        <a class="nav-link d-inline-flex w-100 gap-2 mb-3 align-items-center"
                          id="trabajador-tab-contacto" data-bs-toggle="tab" data-bs-target="#trabajador-pane-contacto"
                          href="#trabajador-pane-contacto" aria-selected="false" role="tab" tabindex="-1">
                          <span class="avatar avatar-lg border avatar-rounded">
                            <span class="avatar avatar-md avatar-rounded">
                              <i class="ti ti-map-pin fs-20"></i>
                            </span>
                          </span>
                          <div>
                            <p class="mb-1 fs-15 fw-semibold">Contacto y Direccion</p>
                            <span class="text-muted fs-13">Medios de contacto y ubicacion</span>
                          </div>
                        </a>
                      </li>
                      <li class="nav-item m-1" role="presentation">
                        <a class="nav-link d-inline-flex w-100 gap-2 mb-3 align-items-center"
                          id="trabajador-tab-laboral" data-bs-toggle="tab" data-bs-target="#trabajador-pane-laboral"
                          href="#trabajador-pane-laboral" aria-selected="false" role="tab" tabindex="-1">
                          <span class="avatar avatar-lg border avatar-rounded">
                            <span class="avatar avatar-md avatar-rounded">
                              <i class="ri-briefcase-line fs-20"></i>
                            </span>
                          </span>
                          <div>
                            <p class="mb-1 fs-15 fw-semibold">Datos Laborales</p>
                            <span class="text-muted fs-13">Cargo, licencia y foto</span>
                          </div>
                        </a>
                      </li>
                    </ul>

                    <div class="p-3 text-center">
                      <div class="px-2 py-3 bg-primary-transparent rounded" id="indicador-campos-requeridos-trabajador" title="Campos requeridos pendientes">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#989797"
                            id="icono-campos-requeridos-trabajador"
                            viewBox="0 0 24 24">
                            <path id="path-campos-requeridos-trabajador" d="M20 3H6.69a2 2 0 0 0-1.87 1.3L2.06 11.65c-.04.11-.06.23-.06.35v2c0 1.1.9 2 2 2h5.61l-1.12 3.37c-.2.61-.1 1.28.27 1.8.38.52.98.83 1.62.83h1.61c.3 0 .58-.13.77-.36L17.46 16H20c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2ZM16 14.64 11.53 20h-1.15l1.56-4.68A1.01 1.01 0 0 0 10.99 14H4v-1.82L6.69 5H16v9.64ZM20 14h-2V5h2v9Z"></path>
                        </svg>
                      </div>
                    </div>
                  </div>

                  <div class="col-xxl-9 col-xl-8 border-start">
                    <div class="card custom-card shadow-none">
                      <div class="p-3 border-bottom border-block-end-dashed tab-content">
                        <div class="tab-pane show active p-0 border-0 custom-products" id="trabajador-pane-general"
                          role="tabpanel" aria-labelledby="trabajador-tab-general" tabindex="0">
                          <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                            <div class="fw-semibold d-flex align-items-center gap-2 fs-15">
                              <i class="ti ti-id text-primary fs-18"></i>
                              <span>Informacion General :</span>
                            </div>
                          </div>
                          <div class="row gy-3">
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_tipo_persona_sunat" class="form-label">Tipo persona</label>
                              <select class="form-control" name="tipo_persona_sunat" id="trabajador_tipo_persona_sunat">
                                <option value="">Seleccione</option>
                                <option value="NATURAL" selected>Natural</option>
                                <option value="JURIDICA">Juridica</option>
                              </select>
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_tipo_documento" class="form-label">Tipo documento <sup class="text-danger">*</sup></label>
                              <select class="form-control" name="tipo_documento" id="trabajador_tipo_documento">
                                <option value="">Seleccione</option>
                                <option value="1">DNI</option>
                                <option value="4">Carnet extranjeria</option>
                                <option value="7">Pasaporte</option>
                                <option value="6">RUC</option>
                              </select>
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_numero_documento" class="form-label">Nro documento <sup class="text-danger">*</sup></label>
                              <div class="input-group">
                                <input type="text" class="form-control" name="numero_documento" id="trabajador_numero_documento">
                                <button type="button" class="btn btn-primary" id="btn-buscar-documento-trabajador" data-bs-toggle="tooltip" title="Buscar">
                                  <i class="bx bx-search-alt"></i>
                                </button>
                              </div>
                            </div>
                            <div class="col-xl-12">
                              <label for="trabajador_descripcion" class="form-label">Nombre completo <sup class="text-danger">*</sup></label>
                              <input type="text" class="form-control" name="descripcion" id="trabajador_descripcion" placeholder="Ej. Juan Perez Gomez">
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_nombre" class="form-label">Nombres</label>
                              <input type="text" class="form-control" name="nombre_persona_natural" id="trabajador_nombre">
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_apellido_paterno" class="form-label">Apellido paterno</label>
                              <input type="text" class="form-control" name="apellido_paterno_persona_natural" id="trabajador_apellido_paterno">
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_apellido_materno" class="form-label">Apellido materno</label>
                              <input type="text" class="form-control" name="apellido_materno_persona_natural" id="trabajador_apellido_materno">
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_sexo" class="form-label">Sexo</label>
                              <select class="form-control" name="sexo" id="trabajador_sexo">
                                <option value="">Seleccione</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                              </select>
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_fecha_nacimiento" class="form-label">Fecha nacimiento</label>
                              <input type="date" class="form-control" name="fecha_nacimiento" id="trabajador_fecha_nacimiento">
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_estado_civil" class="form-label">Estado civil</label>
                              <select class="form-control" name="estado_civil" id="trabajador_estado_civil">
                                <option value="">Seleccione</option>
                                <option value="SOLTERO">Soltero/a</option>
                                <option value="CASADO">Casado/a</option>
                                <option value="DIVORCIADO">Divorciado/a</option>
                                <option value="VIUDO">Viudo/a</option>
                              </select>
                            </div>
                          </div>
                        </div>

                        <div class="tab-pane p-0 border-0" id="trabajador-pane-contacto"
                          role="tabpanel" aria-labelledby="trabajador-tab-contacto" tabindex="0">
                          <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                            <div class="fw-semibold d-flex align-items-center gap-2 fs-15">
                              <i class="ti ti-map-pin text-primary fs-18"></i>
                              <span>Contacto y Direccion :</span>
                            </div>
                          </div>
                          <div class="row gy-3">
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_celular" class="form-label">Celular</label>
                              <input type="text" class="form-control" name="celular" id="trabajador_celular">
                            </div>
                            <div class="col-xl-8 col-md-6">
                              <label for="trabajador_correo" class="form-label">Correo personal</label>
                              <input type="email" class="form-control" name="correo" id="trabajador_correo">
                            </div>
                            <div class="col-xl-12">
                              <label for="trabajador_direccion" class="form-label">Direccion</label>
                              <input type="text" class="form-control" name="direccion" id="trabajador_direccion">
                            </div>
                            <div class="col-xl-6 col-md-6">
                              <label for="trabajador_direccion_referencia" class="form-label">Referencia</label>
                              <input type="text" class="form-control" name="direccion_referencia" id="trabajador_direccion_referencia">
                            </div>
                            <div class="col-xl-6 col-md-6">
                              <label for="trabajador_iddistrito" class="form-label">Distrito</label>
                              <select class="form-control select2" name="iddistrito" id="trabajador_iddistrito" style="width:100%;"></select>
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_departamento" class="form-label">Departamento</label>
                              <input type="text" class="form-control" id="trabajador_departamento" readonly>
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_provincia" class="form-label">Provincia</label>
                              <input type="text" class="form-control" id="trabajador_provincia" readonly>
                            </div>
                            <div class="col-xl-4 col-md-6">
                              <label for="trabajador_cod_ubigeo" class="form-label">Ubigeo</label>
                              <input type="text" class="form-control" name="cod_ubigeo" id="trabajador_cod_ubigeo">
                            </div>
                          </div>
                        </div>

                        <div class="tab-pane p-0 border-0" id="trabajador-pane-laboral"
                          role="tabpanel" aria-labelledby="trabajador-tab-laboral" tabindex="0">
                          <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                            <div class="fw-semibold d-block fs-15">Informacion laboral y otros :</div>
                          </div>
                          <div class="row gy-3">
                            <div class="col-xl-6 col-md-6">
                              <label for="trabajador_idcargo" class="form-label">Cargo</label>
                              <select class="form-control" name="idcargo_trabajador" id="trabajador_idcargo" style="width:100%;"></select>
                            </div>
                            <div class="col-xl-6 col-md-6">
                              <label for="trabajador_nacionalidad" class="form-label">Nacionalidad</label>
                              <select class="form-control" name="nacionalidad" id="trabajador_nacionalidad">
                                <option value="">Seleccione</option>
                                <option value="PERUANA" selected>PERUANA</option>
                                <option value="EXTRANJERA">EXTRANJERA</option>
                              </select>
                            </div>
                            <div class="col-xl-6 col-md-6">
                              <label for="trabajador_numero_licencia" class="form-label">Licencia de conducir</label>
                              <input type="text" class="form-control" name="numero_licencia" id="trabajador_numero_licencia">
                            </div>
                            <div class="col-xl-6 col-md-6">
                              <label for="trabajador_placa_vehiculo" class="form-label">Placa vehiculo</label>
                              <input type="text" class="form-control" name="placa_vehiculo" id="trabajador_placa_vehiculo">
                            </div>
                            <div class="col-md-5 col-lg-5 mt-4">
                              <span><b>Imagen de Perfil</b></span>
                              <div class="mb-4 mt-2 d-sm-flex align-items-center">
                                <div class="mb-0 me-5">
                                  <span class="avatar avatar-xxl avatar-rounded">
                                    <img src="{{ asset('assets/modulo/persona/perfil/hombre.png') }}" alt="" class="trabajador-photo-preview" id="trabajador_imagenmuestra" onerror="this.src='{{ asset('assets/modulo/persona/perfil/hombre.png') }}';">
                                  </span>
                                </div>
                                <div>
                                  <input type="file" class="form-control" name="imagen" id="trabajador_imagen" accept="image/*">
                                  <div class="btn-list mt-3">
                                    <button type="button" class="btn btn-light btn-sm" id="btn-remover-imagen-trabajador">
                                      <i class="bi bi-trash me-1"></i>Remover
                                    </button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="dropdown-menu shadow" id="menu-contextual-trabajador" style="display:none; position:absolute; z-index:1055;">
      <ul class="nav nav-pills flex-column">
        @permiso('trabajadores', 'ver')
        <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-t-ver"><i class="ti ti-eye-cog"></i> Ver detalle</a></li>
        @endpermiso
        @permiso('trabajadores', 'editar')
        <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-t-editar"><i class="ti ti-edit"></i> Editar</a></li>
        <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-t-restaurar"><i class="ti ti-restore"></i> Restaurar</a></li>
        @endpermiso
        @permiso('trabajadores', 'eliminar')
        <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-t-eliminar"><i class="ti ti-folder-bolt"></i> Enviar a papelera</a></li>
        @endpermiso
      </ul>
    </div>

    <div class="modal fade" id="modal-detalle-trabajador" tabindex="-1" aria-labelledby="modal-detalle-trabajador-title" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-detalle-trabajador-title">Detalle del trabajador</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body p-0" id="detalle-trabajador-body"></div>
        </div>
      </div>
    </div>

    @include('layouts.yn_search_modal')
    @include('layouts.yn_footer')
  </div>

  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')

  <script src="{{ asset('assets/js/trabajador.js') }}?v={{ filemtime(public_path('assets/js/trabajador.js')) }}"></script>
</body>

</html>
