<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Usuarios'])

  <style>
    
    #tabla-usuarios_filter { width: calc(100% - 10px) !important; display: flex !important; justify-content: space-between !important; }
    #tabla-usuarios_filter label { width: 100% !important;  }
    #tabla-usuarios_filter label input { width: 100% !important;   }

    .usuario-persona {
      min-width: 220px;
    }

    #tabla-usuarios tbody tr.usuario-row-selected > * {
      background-color: rgba(var(--bs-primary-rgb), .22) !important;
      color: var(--bs-primary-text-emphasis, #052c65) !important;
      box-shadow: inset 4px 0 0 var(--bs-primary);
    }

    .select2-selection.is-invalid {
      border-color: var(--bs-danger) !important;
    }

    .select2-selection.is-valid {
      border-color: var(--bs-success) !important;
    }

    .usuario-permiso-check-cell {
      user-select: none;
    }

    .usuario-permiso-row-estructural > td {
      background-color: rgba(var(--bs-secondary-rgb), .08);
    }

    .usuario-permiso-info-inner {
      padding-left: calc(var(--usuario-permiso-indent, 0) * 1rem);
    }

    .usuario-permiso-modulo-header {
      padding-left: 2rem !important;
    }

    .usuario-permiso-row-estructural .usuario-permiso-nombre {
      letter-spacing: .02em;
    }

    .usuario-permiso-cell-disabled {
      color: var(--bs-secondary-color);
      user-select: none;
    }

    .usuario-permiso-check-cell.usuario-permiso-check-selected {
      background-color: rgba(var(--bs-primary-rgb), .16) !important;
      box-shadow: inset 0 0 0 2px rgba(var(--bs-primary-rgb), .45);
    }

    .usuario-permiso-check-cell.usuario-permiso-check-selected .form-check-input {
      border-color: var(--bs-primary);
      box-shadow: 0 0 0 .18rem rgba(var(--bs-primary-rgb), .18);
    }

    [data-theme-mode="dark"] .usuario-permiso-check-cell .form-check-input,
    [data-bs-theme="dark"] .usuario-permiso-check-cell .form-check-input,
    .dark .usuario-permiso-check-cell .form-check-input {
      background-color: rgba(255, 255, 255, .08);
      border-color: rgba(255, 255, 255, .72);
      box-shadow: 0 0 0 .12rem rgba(255, 255, 255, .16);
    }

    [data-theme-mode="dark"] .usuario-permiso-check-cell .form-check-input:checked,
    [data-bs-theme="dark"] .usuario-permiso-check-cell .form-check-input:checked,
    .dark .usuario-permiso-check-cell .form-check-input:checked {
      background-color: var(--bs-primary);
      border-color: rgba(255, 255, 255, .9);
      box-shadow: 0 0 0 .14rem rgba(255, 255, 255, .2);
    }

    [data-theme-mode="dark"] .usuario-permiso-check-cell.usuario-permiso-check-selected,
    [data-bs-theme="dark"] .usuario-permiso-check-cell.usuario-permiso-check-selected,
    .dark .usuario-permiso-check-cell.usuario-permiso-check-selected {
      background-color: rgba(255, 255, 255, .12) !important;
      box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .55);
    }

    .usuario-permiso-grupo-contadores {
      display: inline-flex;
      flex-wrap: wrap;
      gap: .35rem;
    }

    .usuario-permiso-grupo-contadores .badge {
      white-space: nowrap;
    }

    #menu-contextual-permisos {
      min-width: 180px;
    }

  </style>
</head>

<body id="body-usuario" data-auth-user-id="{{ auth()->id() }}">
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
              <p class="fw-semibold fs-18 mb-0 title-body-pagina">Usuarios del Sistema</p>
              <span class="fs-semibold text-muted detalle-body-pagina"><nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Usuarios</li>
              </ol>
            </nav></span>

            </div>
          </div>

          <div class="btn-list mt-md-0 mt-2">

            @permiso('usuarios_del_sistema', 'crear')
            <button type="button" class="btn btn-primary label-btn m-r-10px" id="btn-nuevo-usuario">
              <i class="ri-user-add-line label-btn-icon me-2"></i>Agregar
            </button>
            @endpermiso
            <button type="button" class="btn btn-light label-btn m-r-10px" id="btn-regresar-usuario" style="display: none;">
              <i class="bi bi-arrow-left label-btn-icon me-2"></i>Regresar
            </button>
            
          </div>
        </div>

        <div class="row">
          <div class="col-12" id="div-tabla-usuarios">
            <div class="card custom-card">
              
              <div class="card-body">
                <div class="table-responsive">
                  <table id="tabla-usuarios" class="table table-bordered table-striped w-100">
                    <thead>
                      <tr>
                        
                        <th class="text-center">OP</th>
                        <th>Persona</th>
                        <th>Login</th>
                        <th class="text-center">Permisos</th>
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

          <div class="col-xl-12" id="div-formulario-usuario" style="display: none;">
            <div class="card custom-card shadow-sm border-0">
              <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="card-title">
                  <span id="titulo-formulario-usuario">Registrar usuario</span>
                </div>
                <button type="submit" class="btn btn-primary btn-wave" form="form-agregar-usuario" id="btn-guardar-usuario-header">
                  <i class="bi bi-floppy-fill me-1"></i> Grabar datos
                </button>
              </div>
              <div class="card-body bg-opacity-25">
                <form name="form-agregar-usuario" id="form-agregar-usuario" method="POST" class="needs-validation" novalidate>
                  <input type="hidden" name="id" id="id">
                  <input type="hidden" name="permisos_json" id="permisos_json" value="[]">
                  <input type="hidden" name="series_json" id="series_json" value="[]">

                  <div class="row g-3">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="idpersona" class="form-label">Persona</label>
                        <select class="form-select" name="idpersona" id="idpersona" data-placeholder="Buscar persona sin usuario..."></select>
                        <label class="form-label mt-1 fs-12 text-muted mb-0">Solo aparecen personas que todavia no tienen usuario asignado.</label>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="email" class="form-label">Correo</label>
                        <input type="email" class="form-control" name="email" id="email" autocomplete="off">
                        <label class="form-label mt-1 fs-12 text-muted mb-0">Se valida en linea para evitar correos duplicados.</label>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="password" class="form-label">Clave</label>
                        <input type="password" class="form-control" name="password" id="password" autocomplete="new-password">
                        <label class="form-label mt-1 fs-12 text-muted mb-0">Minimo 8 caracteres.</label>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirmar clave</label>
                        <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" autocomplete="new-password">
                        <label class="form-label mt-1 fs-12 text-muted mb-0">Debe coincidir con la clave ingresada.</label>
                      </div>
                    </div>
                  </div>

                  <div class="mt-4 border rounded-4 p-3 bg-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                      <div class="flex-grow-1">
                        <div class="fw-semibold d-block fs-15">Permisos por modulo</div>
                        <span class="text-muted fs-13">Activa el acceso en cada pagina y activa <code>ver</code>, <code>crear</code>, <code>editar</code> o <code>eliminar</code>.</span>
                        
                      </div>
                      <div class="d-flex gap-2 flex-wrap align-items-center w-100">
                        <div class="input-group flex-grow-1" style="min-width: 260px; max-width: 360px;">
                          <span class="input-group-text"><i class="bi bi-search"></i></span>
                          <input type="text" class="form-control" id="perm-search" placeholder="Buscar permiso, codigo o nombre clave...">
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary btn-wave" id="btn-tenant-permisos-expandir" data-bs-toggle="tooltip" title="Expandir Level">
                          <i class="bi bi-arrows-expand me-1"></i>
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-wave" id="btn-tenant-permisos-contraer" data-bs-toggle="tooltip" title="Contraer Level">
                          <i class="bi bi-arrows-collapse me-1"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-wave" id="btn-tenant-permisos-todos" data-bs-toggle="tooltip" title="Activar todos los permisos">
                          <i class="bi bi-check2-square me-1"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-wave" id="btn-tenant-permisos-remover" data-bs-toggle="tooltip" title="Remover todos los permisos">
                          <i class="bi bi-x-square me-1"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-wave" id="btn-tenant-permisos-limpiar" data-bs-toggle="tooltip" title="Limpiar seleccion">
                          <i class="bi bi-eraser me-1"></i>
                        </button>
                        <span class="badge bg-secondary-transparent text-secondary" id="tenant-perm-counter-label">0 modulos activos</span>
                      </div>
                    </div>

                    <div id="contenedor-tenant-permisos" class="d-grid gap-2">
                      <div class="border rounded-4 p-4 text-center text-muted bg-light">
                        <div class="fw-semibold mb-1">Cargando catalogo de modulos...</div>
                        <div class="fs-13">Un momento mientras preparamos la estructura por niveles.</div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-4 border rounded-4 p-3 bg-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                      <div class="flex-grow-1">
                        <div class="fw-semibold d-block fs-15">Series de comprobante por usuario</div>
                        <span class="text-muted fs-13">Selecciona las series que el usuario podra usar al emitir documentos.</span>
                      </div>
                      <div class="d-flex gap-2 flex-wrap align-items-center w-100">
                        <div class="input-group flex-grow-1" style="min-width: 260px; max-width: 360px;">
                          <span class="input-group-text"><i class="bi bi-search"></i></span>
                          <input type="text" class="form-control" id="series-search" placeholder="Buscar por serie, tipo o codigo SUNAT...">
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-wave" id="btn-tenant-series-expandir" data-bs-toggle="tooltip" title="Expandir grupos">
                          <i class="bi bi-arrows-expand me-1"></i>
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-wave" id="btn-tenant-series-contraer" data-bs-toggle="tooltip" title="Contraer grupos">
                          <i class="bi bi-arrows-collapse me-1"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-wave" id="btn-tenant-series-todos" data-bs-toggle="tooltip" title="Activar todas las series">
                          <i class="bi bi-check2-square me-1"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-wave" id="btn-tenant-series-remover" data-bs-toggle="tooltip" title="Remover todas las series">
                          <i class="bi bi-x-square me-1"></i>
                        </button>
                        <span class="badge bg-secondary-transparent text-secondary" id="tenant-series-counter-label">0 series activas</span>
                      </div>
                    </div>

                    <div id="contenedor-tenant-series" class="d-grid gap-2">
                      <div class="border rounded-4 p-4 text-center text-muted bg-light">
                        <div class="fw-semibold mb-1">Cargando series de comprobantes...</div>
                        <div class="fs-13">Un momento mientras preparamos la lista por tipo de comprobante.</div>
                      </div>
                    </div>
                  </div>

                  <div class="card-footer border-top-0 px-0 pt-4 pb-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                      <div class="text-muted fs-13">
                        <i class="bi bi-info-circle me-1"></i>El usuario quedara vinculado a la persona seleccionada.
                      </div>
                      <div class="btn-list">
                        <button type="button" class="btn btn-light btn-wave" onclick="show_hide_form(1);">
                          <i class="bi bi-arrow-left me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary btn-wave" id="btn-guardar-usuario">
                          <i class="bi bi-floppy-fill me-1"></i> Grabar datos
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Menú contextual personalizado -->
    <div id="menu-contextual-usuario" style="display:none; position:absolute; z-index:1000;" class="bg-white border rounded shadow-sm shadow-0px-05rem-1rem-rgb-0-0-0-65">      
      <div class="card mb-0">
        <div class="card-header py-2"><span class="font-size-12px text-bold">MAS OPCIONES</span></div>
        <div class="card-body p-0">
          <ul class="nav nav-pills flex-column">
            @permiso('usuarios_del_sistema', 'editar')
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-p-editar"><i class="ti ti-edit"></i> Editar</a></li>
            @endpermiso
            @permiso('usuarios_del_sistema', 'ver')
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-p-ver-detalle"><i class="ti ti-eye-cog"></i> Ver Detalle</a></li>            
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-p-sesiones"><i class="ri-computer-line"></i> Sesiones activas</a></li>
            @endpermiso
            @permiso('usuarios_del_sistema', 'editar')
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-p-gestionar-2fa"><i class="ri-shield-keyhole-line"></i> Autenticación 2FA</a></li>
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-p-enviar-papelera"><i class="ti ti-folder-bolt"></i>  Enviar a Papelera</a></li>
            @endpermiso
            @permiso('usuarios_del_sistema', 'eliminar')
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-p-eliminar"><i class="ti ti-trash-x"></i> Eliminar Permanente</a></li>
            @endpermiso
            
          </ul>
        </div>
        <!-- /.card-body -->
      </div>
    </div>

    <!-- Menu contextual para permisos seleccionados -->
    <div id="menu-contextual-permisos" class="dropdown-menu shadow border" style="display:none; position:absolute; z-index:1080;">
      <button type="button" class="dropdown-item d-flex align-items-center gap-2" id="opcion-permisos-activar">
        <i class="bi bi-check2-square text-success"></i>
        <span>Activar</span>
      </button>
      <button type="button" class="dropdown-item d-flex align-items-center gap-2" id="opcion-permisos-desactivar">
        <i class="bi bi-square text-secondary"></i>
        <span>Desactivar</span>
      </button>
    </div>

    <div class="modal fade" id="modal-detalle-usuario" tabindex="-1" aria-labelledby="modal-detalle-usuario-title" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-detalle-usuario-title">Detalle del usuario</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body p-0" id="detalle-usuario-body">
            <div class="text-center text-muted py-5">
              <div class="spinner-border text-primary mb-3" role="status"></div>
              <div class="fw-semibold">Cargando detalle del usuario...</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal-2fa-usuario" tabindex="-1" aria-labelledby="modal-2fa-usuario-title" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-2fa-usuario-title">Autenticación de dos factores</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body p-0" id="modal-2fa-usuario-body">
            <div class="text-center text-muted py-5">
              <div class="spinner-border text-primary mb-3" role="status"></div>
              <div class="fw-semibold">Preparando configuración 2FA...</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal-sesiones-usuario" tabindex="-1" aria-labelledby="modal-sesiones-usuario-title" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="modal-sesiones-usuario-title">Sesiones activas</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body p-0" id="modal-sesiones-usuario-body">
            <div class="text-center text-muted py-5">
              <div class="spinner-border text-primary mb-3" role="status"></div>
              <div class="fw-semibold">Consultando sesiones activas...</div>
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

  <script src="{{ asset('assets/js/usuario.js') }}?v={{ filemtime(public_path('assets/js/usuario.js')) }}"></script>
</body>

</html>
