<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Clientes'])

  <style>
    #tabla-clientes_filter {
      width: calc(100% - 10px) !important;
      display: flex !important;
      justify-content: space-between !important;
    }

    #tabla-clientes_filter label,
    #tabla-clientes_filter label input {
      width: 100% !important;
    }

    .cliente-persona {
      min-width: 220px;
    }

    #modal-registrar-conyuge .modal-content {
      max-height: calc(100vh - 2rem);
    }

    #modal-registrar-conyuge form {
      display: flex;
      flex: 1 1 auto;
      flex-direction: column;
      min-height: 0;
      overflow: hidden;
    }

    #modal-registrar-conyuge .modal-body {
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
    }

    @media (max-width: 575.98px) {
      #modal-registrar-conyuge .modal-dialog {
        margin: .5rem;
      }

      #modal-registrar-conyuge .modal-content {
        max-height: calc(100vh - 1rem);
      }
    }
  </style>
</head>

<body id="body-cliente">
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
              <p class="fw-semibold fs-18 mb-0 title-body-pagina">Clientes</p>
                <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Clientes</li>
                </ol>
                </nav>
            </div>

          </div>

          <div class="btn-list mt-md-0 mt-2">

            @permiso('clientes', 'crear')
            <button type="button" class="btn btn-primary label-btn m-r-10px btn-nuevo-persona">
              <i class="ri-user-add-line label-btn-icon me-2"></i>Agregar
            </button>
            @endpermiso
            <button type="button" class="btn btn-light label-btn m-r-10px btn-regresar-persona" style="display: none;">
              <i class="bi bi-arrow-left label-btn-icon me-2"></i>Regresar
            </button>

          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card custom-card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">Listado de clientes</div>
                <button type="button" class="btn btn-icon btn-sm btn-light" id="btn-recargar-clientes" data-bs-toggle="tooltip" title="Actualizar">
                  <i class="las la-sync-alt"></i>
                </button>
              </div>
              <div class="card-body">
                <div class="col-12 div_tabla_persona">
                    <div class="table-responsive">
                    <table id="tabla-clientes" class="table table-bordered table-striped w-100">
                        <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">OP</th>
                            <th>Persona</th>
                            <th>Contacto</th>
                            <th>Tipo Persona</th>                            
                            <th>Estado Civil</th>
                            <th>Nacionalidad</th>
                            <th class="text-center">Estado</th>
                            <th>Actualizado</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    </div>
                </div>
                <div class="col-12 div_formulario_persona" style="display: none;">

                    <div class="card custom-card p-0">

                        <div class="card-header border-bottom justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">Guardar/Editar Clientes</h5>
                                <p class="text-muted mb-0 fs-12">Gestiona de manera eficiente la información del Clientes.</p>
                            </div>
                            <div class="btn-list">
                                <button type="button" class="btn btn-outline-danger" onclick="show_hide_form(1);">
                                    <i class="ti ti-circle-dashed-x"></i> Cancelar
                                </button>
                                <button type="button" class="btn btn-primary guardar_registro_persona">
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
                                            id="account" data-bs-toggle="tab" data-bs-target="#account-pane" href="#personal-info"
                                            aria-selected="true" role="tab">
                                            <span class="avatar avatar-lg border avatar-rounded">
                                                <span class="avatar avatar-md avatar-rounded">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24"
                                                        width="24px" fill="#000">
                                                        <path d="M0 0h24v24H0V0z" fill="none" />
                                                        <path d="M8 16h12V4H8v12zm2-10h8v2h-8V6zm0 3h8v2h-8V9zm0 3h4v2h-4v-2z" opacity=".8"
                                                                fill="#fff" />
                                                        <path d="M4 22h14v-2H4V6H2v14c0 1.1.9 2 2 2zM6 4v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2H8c-1.1 0-2 .9-2 2zm14 12H8V4h12v12zM10 9h8v2h-8zm0 3h4v2h-4zm0-6h8v2h-8z" />
                                                    </svg>
                                                </span>
                                            </span>
                                            <div>
                                                <p class="mb-1 fs-15 fw-semibold">Información General</p>
                                                <span class="text-muted fs-13">Datos básicos del Proveedor</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li class="nav-item m-1" role="presentation">
                                        <a class="nav-link d-inline-flex w-100 gap-2 mb-3" id="images-tab" data-bs-toggle="tab"
                                            data-bs-target="#images-tab-pane" role="tab" aria-controls="images-tab-pane"
                                            href="#personal-info" aria-selected="false" tabindex="-1">
                                            <span class="avatar avatar-lg border avatar-rounded">
                                                <span class="avatar avatar-md avatar-rounded">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24"
                                                        width="24px" fill="#000">
                                                        <path d="M0 0h24v24H0V0z" fill="none" />
                                                        <path d="M6.26 9L12 13.47 17.74 9 12 4.53z" opacity=".8" fill="#fff" />
                                                        <path d="M19.37 12.8l-7.38 5.74-7.37-5.73L3 14.07l9 7 9-7zM12 2L3 9l1.63 1.27L12 16l7.36-5.73L21 9l-9-7zm0 11.47L6.26 9 12 4.53 17.74 9 12 13.47z" />
                                                    </svg>
                                                </span>
                                            </span>
                                            <div>
                                                <p class="mb-1 fs-15 fw-semibold">Contacto y Dirección</p>
                                                <span class="text-muted fs-13">Medios de contacto y dirección</span>
                                            </div>
                                        </a>
                                    </li>
                                    <!--<li class="nav-item m-1" role="presentation">
                                        <a class="nav-link d-inline-flex w-100 mb-3 gap-2" id="security-tab" data-bs-toggle="tab"
                                            data-bs-target="#security-tab-pane" role="tab" aria-controls="security-tab-pane"
                                            aria-selected="false" href="#personal-info" tabindex="-1">
                                            <span class="avatar avatar-lg border avatar-rounded">
                                                <span class="avatar avatar-md avatar-rounded">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24"
                                                        width="24px" fill="#000">
                                                        <path d="M0 0h24v24H0V0z" fill="none" />
                                                        <path d="M10.21 16.83l-1.96-2.36L5.5 18h11l-3.54-4.71z" />
                                                        <path d="M16.5 18h-11l2.75-3.53 1.96 2.36 2.75-3.54L16.5 18zM17 7h-3V6H4v14h14V10h-1V7z"
                                                                opacity=".8" fill="#fff" />
                                                        <path d="M20 4V1h-2v3h-3v2h3v2.99h2V6h3V4zm-2 16H4V6h10V4H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V10h-2v10z" />
                                                    </svg>
                                                </span>
                                            </span>
                                            <div>
                                                <p class="mb-1 fs-15 fw-semibold">Imagen</p>
                                                <span class="text-muted fs-13">Agregar imagen del Proveedor</span>
                                            </div>
                                        </a>
                                    </li>-->
                                </ul>

                                <div class="p-3 text-center">
                                    <div class="px-2 py-3 bg-primary-transparent rounded" id="indicador-campos-requeridos" title="Campos requeridos pendientes">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#989797"
                                            id="icono-campos-requeridos"
                                            viewBox="0 0 24 24">
                                            <path id="path-campos-requeridos" d="M20 3H6.69a2 2 0 0 0-1.87 1.3L2.06 11.65c-.04.11-.06.23-.06.35v2c0 1.1.9 2 2 2h5.61l-1.12 3.37c-.2.61-.1 1.28.27 1.8.38.52.98.83 1.62.83h1.61c.3 0 .58-.13.77-.36L17.46 16H20c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2ZM16 14.64 11.53 20h-1.15l1.56-4.68A1.01 1.01 0 0 0 10.99 14H4v-1.82L6.69 5H16v9.64ZM20 14h-2V5h2v9Z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div> 
                            <div class="col-xxl-9 col-xl-8 border-start">
                                <div class="card custom-card shadow-none">
                                    <form id="form-agregar-persona" name="form-agregar-persona" method="POST"
                                            enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="idpersona" id="idpersona" />
                                        <input type="hidden" name="idpersona_tipo" id="idpersona_tipo" value="3" />

                                        <div class="p-3 border-bottom border-block-end-dashed tab-content" id="cargando-1-formulario">
                                            <!-- Pestaña Información General -->
                                            <div class="tab-pane show active p-0 border-0 custom-products" id="account-pane"
                                                role="tabpanel" aria-labelledby="account" tabindex="0">
                                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                                    <div class="fw-semibold d-block fs-15">Información General :</div>
                                                </div>
                                                <div class="row gy-3">
                                                    <div class="col-xl-4 col-md-6">
                                                        <label class="form-label" for="tipo_persona_sunat">Tipo Persona</label>
                                                        <select class="form-control select2 js-states" name="tipo_persona_sunat"
                                                                id="tipo_persona_sunat" style="width: 100%;">
                                                            <option value="NATURAL">NATURAL</option>
                                                            <option value="JURIDICA">JURIDICA</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-xl-4 col-md-6">
                                                        <label class="form-label" for="tipo_documento">Tipo de documento</label>
                                                        <select name="tipo_documento" id="tipo_documento" class="form-control">
                                                            <option value="1">DNI</option>
                                                            <option value="6">RUC</option>
                                                            <option value="7">EXTRANJERO</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-xl-4 col-md-6">
                                                        <label class="form-label d-block" for="numero_documento">
                                                            Nro de documento <sup class="text-danger">*</sup>
                                                            <span class="d-inline-block text-danger" tabindex="0"
                                                                    data-toggle="tooltip" title="Estado Ruc">
                                                                <svg id="Capa_1" data-name="Capa 1"
                                                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 424.89 470.84"
                                                                    height="15px" width="15px">
                                                                    <defs><style>.cls-1 { fill: #0056bd; }.cls-2 { fill: #c70034; }</style></defs>
                                                                    <path class="cls-1"
                                                                            d="M316.23,295.78c-27.73-17.5-60.56-18.06-92.68-17.34L196,277.14h0c-45.82-6.24-69.94-32.37-63.93-80.46.83-6.57,1.44-13.16,2.75-25.29C106,198.54,80.49,222.18,55.37,246.2c-21.52,20.57-21.52,28.31-1,49.23C70.53,311.83,227.35,466.74,242,480.36c9.16,5.79,17.18,4.26,24.17-3.83h0c25.45-25.14,50.94-49,75.87-75.82C379.4,360.51,358.4,322.41,316.23,295.78Z"
                                                                            transform="translate(-39.11 -13)" />
                                                                    <path class="cls-2"
                                                                            d="M458.49,213.31C446,200.62,283.58,34.17,274.75,28c-18-19.19-27.07-19.83-44.87-2.58C217.89,37,205.82,48.64,194.56,61c-17.18,18.83-39.87,33.3-47.55,59.84-2.78,12.55-3.22,18.63-.57,32h0c4.78,38,56.65,64.81,104.83,64.13L291.5,218h0c31.49-1.29,85.43-.13,86.83,49.84-.7,16.58-8.28,50.89-7.18,51.55.84.49,14.64-9.88,21.35-16.32C405.1,291,445.18,252.46,459.19,238,465.36,231.66,466.08,221,458.49,213.31Z"
                                                                            transform="translate(-39.11 -13)" />
                                                                </svg>
                                                            </span>
                                                            <span class="valido_novalido"><span class="badge bg-primary">Por Verificar</span></span>
                                                        </label>
                                                        <div class="input-group">                            
                                                            <input type="number" class="form-control is-valid" name="numero_documento" id="numero_documento" placeholder="">
                                                            <button class="btn btn-primary" type="button" onclick="buscarEntidadExistentePorDocumento();">
                                                            <i class="bx bx-search-alt" id="search" style=""></i>
                                                            <div class="spinner-border spinner-border-sm" role="status" id="charge" style="display: none;"></div>
                                                            </button>
                                                            <input type="hidden" class="input_hidden_ss" id="estado_sunat" />
                                                        </div>


                                                        
                                                    </div>

                                                    <div class="col-xl-7 col-md-6 div_descripcion">
                                                        <label class="form-label lebel_name_descrip" for="descripcion">Descripción</label>
                                                        <input type="text" name="descripcion" id="descripcion" class="form-control"
                                                                placeholder="Ej. Juan" />
                                                    </div>
                                                    <div class="col-xl-5 col-md-6 div_nombre_comercial">
                                                        <label class="form-label" for="nombre_comercial">Nombre Comercial</label>
                                                        <input type="text" name="nombre_comercial" class="form-control"
                                                                id="nombre_comercial" placeholder="Ej. nombre comercial" />
                                                    </div>
                                                    <div class="col-xl-5 col-md-6 div_nombre_persona_natural">
                                                        <label class="form-label" for="nombre_persona_natural">Nombre (Persona Natural)</label>
                                                        <input type="text" name="nombre_persona_natural" class="form-control"
                                                                id="nombre_persona_natural" placeholder="Ej. Juan" />
                                                    </div>
                                                    <div class="col-xl-4 col-md-6 div_apellido_paterno_persona_natural">
                                                        <label class="form-label" for="apellido_paterno_persona_natural">Apellido Paterno</label>
                                                        <input type="text" name="apellido_paterno_persona_natural"
                                                                class="form-control" id="apellido_paterno_persona_natural"
                                                                placeholder="Ej. Pérez" />
                                                    </div>
                                                    <div class="col-xl-4 col-md-6 div_apellido_materno_persona_natural">
                                                        <label class="form-label" for="apellido_materno_persona_natural">Apellido Materno</label>
                                                        <input type="text" name="apellido_materno_persona_natural"
                                                                class="form-control" id="apellido_materno_persona_natural"
                                                                placeholder="Ej. Pérez" />
                                                    </div>
                                                    <div class="col-xl-4 col-md-6 div_sexo">
                                                        <label class="form-label" for="sexo">Sexo</label>
                                                        <select name="sexo" id="sexo" class="form-control">
                                                            <option value="M">Masculino</option>
                                                            <option value="F">Femenino</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-xl-4 col-md-6 div_fecha_nacimiento">
                                                        <label class="form-label" for="fecha_nacimiento">Fecha de Nacimiento</label>
                                                        <input type="date" name="fecha_nacimiento" class="form-control"
                                                                id="fecha_nacimiento" />
                                                    </div>
                                                    <!-- Nuevos campos según modelo Persona -->
                                                    <div class="col-xl-4 col-md-6 div_nacionalidad">
                                                        <label class="form-label" for="nacionalidad">Nacionalidad</label>
                                                        <select name="nacionalidad" id="nacionalidad" class="form-control">
                                                            <option value="PERUANO">PERUANO</option>
                                                            <option value="EXTRANJERO">EXTRANJERO</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-xl-4 col-md-6">
                                                        <label class="form-label" for="estado_civil">Estado Civil</label>
                                                        <select name="estado_civil" id="estado_civil" class="form-control">
                                                            <option value="SOLTERO">Soltero/a</option>
                                                            <option value="CASADO">Casado/a</option>
                                                            <option value="DIVORCIADO">Divorciado/a</option>
                                                            <option value="VIUDO">Viudo/a</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 div_datos_pareja" style="display: none;">
                                                        <div class="border rounded p-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <div>
                                                                    <div class="fw-semibold">Datos del c&oacute;nyuge</div>
                                                                    <div class="text-muted fs-12">Busca una persona existente o completa los datos para registrarla.</div>
                                                                </div>
                                                                <span class="badge bg-light text-muted" id="conyuge_estado_busqueda">Sin buscar</span>
                                                            </div>
                                                            <input type="hidden" name="idconyuge" id="idconyuge" />
                                                            <div class="row gy-3">
                                                                <div class="col-xl-3 col-md-6">
                                                                    <label class="form-label" for="conyuge_tipo_documento">Tipo documento <sup class="text-danger">*</sup></label>
                                                                    <select name="conyuge_tipo_documento" id="conyuge_tipo_documento" class="form-control">
                                                                        <option value="">Seleccionar</option>
                                                                        <option value="1">DNI</option>
                                                                        <option value="7">Documento extranjero</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-xl-4 col-md-6">
                                                                    <label class="form-label" for="conyuge_numero_documento">Nro. documento <sup class="text-danger">*</sup></label>
                                                                    <div class="input-group">
                                                                        <input type="text" name="conyuge_numero_documento" id="conyuge_numero_documento"
                                                                                class="form-control" maxlength="20" />
                                                                        <button type="button" class="btn btn-primary" id="btn-buscar-conyuge">
                                                                            <i class="bx bx-search-alt"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div class="col-xl-5 col-md-6">
                                                                    <label class="form-label" for="conyuge_descripcion">Nombres y apellidos</label>
                                                                    <input type="text" name="conyuge_descripcion" id="conyuge_descripcion"
                                                                            class="form-control" readonly />
                                                                </div>
                                                                <div class="col-xl-3 col-md-6">
                                                                    <label class="form-label" for="conyuge_celular">Celular</label>
                                                                    <input type="tel" name="conyuge_celular" id="conyuge_celular"
                                                                            class="form-control" maxlength="15" readonly />
                                                                </div>
                                                                <div class="col-xl-3 col-md-6 d-flex align-items-end">
                                                                    <button type="button" class="btn btn-light" id="btn-limpiar-conyuge">
                                                                        <i class="ri-close-line me-1"></i>Limpiar selecci&oacute;n
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Fin campos adicionales -->
                                                    <div class="col-md-5 col-lg-5 mt-4">
                                                        <span class=""> <b>Imagen de Perfil</b> </span>
                                                        <div class="mb-4 mt-2 d-sm-flex align-items-center">
                                                        <div class="mb-0 me-5">
                                                            <span class="avatar avatar-xxl avatar-rounded">
                                                            <img src="{{ asset('assets/modulo/persona/perfil/hombre.png') }}" alt="" id="imagenmuestra" onerror="this.src='{{ asset('assets/modulo/persona/perfil/hombre.png') }}';">
                                                            <a href="javascript:void(0);" class="badge rounded-pill bg-primary avatar-badge cursor-pointer">
                                                                <input type="file" class="position-absolute w-100 h-100 op-0" name="imagen" id="imagen" accept="image/*">
                                                                <input type="hidden" name="imagenactual" id="imagenactual">
                                                                <i class="fe fe-camera  "></i>
                                                            </a>
                                                            </span>
                                                        </div>
                                                        <div class="btn-group">
                                                            <a class="btn btn-primary" onclick="cambiarImagen()"><i class='bx bx-cloud-upload bx-tada fs-5'></i> Subir</a>
                                                            <a class="btn btn-light" onclick="removerImagen()"><i class="bi bi-trash fs-6"></i> Remover</a>
                                                        </div>
                                                        </div>
                                                    </div> 
                                                </div>
                                            </div>

                                            <!-- Pestaña Contacto y Dirección -->
                                            <div class="tab-pane p-0 border-0" id="images-tab-pane" role="tabpanel"
                                                aria-labelledby="images-tab" tabindex="0">
                                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                                    <div class="fw-semibold d-block fs-15">Contacto y Dirección:</div>
                                                </div>
                                                <div class="row gy-3">
                                                    <div class="col-xl-5 col-md-6">
                                                        <label class="form-label" for="celular">Celular</label>
                                                        <input type="tel" name="celular" class="form-control" id="celular"
                                                                onkeypress="return soloNumeros(event)" placeholder="Ej. 987654321" />
                                                    </div>
                                                    <div class="col-xl-7 col-md-6">
                                                        <label class="form-label" for="correo">Correo</label>
                                                        <input type="email" name="correo" class="form-control" id="correo"
                                                                placeholder="correo@ejemplo.com" />
                                                    </div>
                                                    <div class="col-xl-12">
                                                        <label class="form-label" for="direccion">Dirección</label>
                                                        <textarea class="form-control" name="direccion" id="direccion" rows="2"
                                                                    placeholder="Calle, número, urbanización"></textarea>
                                                    </div>
                                                    <div class="col-xl-12">
                                                        <label class="form-label" for="direccion_referencia">Referencia de Dirección</label>
                                                        <textarea class="form-control" name="direccion_referencia"
                                                                    id="direccion_referencia" rows="2"
                                                                    placeholder="Cerca de..."></textarea>
                                                    </div>
                                                    <div class="col-xl-4 col-md-6">
                                                        <label class="form-label" for="iddistrito">Distrito</label>
                                                        <select name="iddistrito" id="iddistrito" class="form-control select2"
                                                                style="width: 100%;"></select>

                                                        <input type="hidden" name="iddistrito_envio" id="iddistrito_envio" >
                                                    </div>
                                                    <div class="col-xl-3 col-md-6">
                                                        <label class="form-label" for="idprovincia">Provincia</label>
                                                        <input name="idprovincia" id="idprovincia" class="form-control "
                                                                style="width: 100%;"></input>
                                                    </div>
                                                    <div class="col-xl-3 col-md-6">
                                                        <label class="form-label" for="iddepartamento">Departamento</label>
                                                        <input name="iddepartamento" id="iddepartamento" class="form-control"
                                                                style="width: 100%;"></input>
                                                    </div>
                                                    <!-- Los campos idprovincia y iddepartamento se eliminaron porque no están en el modelo Persona -->
                                                    <div class="col-xl-2 col-md-6">
                                                        <label class="form-label" for="cod_ubigeo">Código Ubigeo</label>
                                                        <input type="text" name="cod_ubigeo" class="form-control" id="cod_ubigeo"
                                                                placeholder="Ej. 150101" />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Pestaña Imagen -->
                                            <div class="tab-pane p-0 border-0" id="security-tab-pane" role="tabpanel" aria-labelledby="security-tab" tabindex="0">
                                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                                    <div class="fw-semibold d-block fs-15">Imagen :</div>
                                                </div>
                                                <div class="row gy-3">

                                                    <!-- Imgen -->
                                                    <div class="col-md-4 col-lg-4 mt-4">
                                                    </div>                                                   

                                                </div>
                                            </div>
                                        </div>

                                        <div class="row text-center" id="cargando-2-formulario" style="display: none;">
                                            <div class="col-lg-12 text-center py-5">
                                                <i class="fas fa-spinner fa-pulse fa-3x"></i><br />
                                                <br />
                                                <h4>Cargando...</h4>    
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary hidden" id="submit-form-entidad">
                                            Guardar cliente
                                        </button>
                                    </form>
                                </div>
                                <div class="col-12 p-3 mb-3 border-bottom border-block-end-dashed">
                                    <label class="form-label d-block">Progreso del formulario</label>
                                    <div class="progress" id="barra_progress_entidad_div">
                                        <div id="barra_progress_entidad" class="progress-bar" role="progressbar" aria-valuenow="0"
                                            aria-valuemin="0" aria-valuemax="100" style="min-width: 2em; width: 0%;">0%
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
        </div>
      </div>
    </div>

    <!-- Menu contextual personalizado -->
    <div id="menu-contextual-cliente" style="display:none; position:absolute; z-index:1000;" class="bg-white border rounded shadow-sm shadow-0px-05rem-1rem-rgb-0-0-0-65">      
      <div class="card mb-0">
        <div class="card-header py-2"><span class="font-size-12px text-bold">MAS OPCIONES</span></div>
        <div class="card-body p-0">
          <ul class="nav nav-pills flex-column">
            @permiso('clientes', 'ver')
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-c-ver"><i class="ti ti-eye-cog"></i> Ver Detalle</a></li>
            @endpermiso
            @permiso('clientes', 'editar')
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-c-editar"><i class="ti ti-edit"></i> Editar</a></li>
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-c-restaurar"><i class="ti ti-restore"></i> Restaurar</a></li>
            @endpermiso
            @permiso('clientes', 'eliminar')
            <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-c-eliminar"><i class="ti ti-folder-bolt"></i> Enviar a Papelera</a></li>
            @endpermiso
            
          </ul>
        </div>
        <!-- /.card-body -->
      </div>
    </div>



    <div class="modal fade" id="modal-registrar-conyuge" tabindex="-1" aria-labelledby="modal-registrar-conyuge-label" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
          <div class="modal-header border-bottom">
            <div>
              <h5 class="modal-title fw-semibold mb-1" id="modal-registrar-conyuge-label">Registrar c&oacute;nyuge</h5>
              <p class="text-muted mb-0 fs-12">Completa la ficha de la persona. Se vincular&aacute; al cliente al guardar.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <form id="form-registrar-conyuge">
            @csrf
            <div class="modal-body">
              <div class="row gy-3">
                <div class="col-12">
                  <div class="fw-semibold border-bottom pb-2">Informaci&oacute;n general</div>
                </div>
                <div class="col-xl-3 col-md-6">
                  <label class="form-label" for="modal_conyuge_tipo_documento">Tipo documento <sup class="text-danger">*</sup></label>
                  <select name="tipo_documento" id="modal_conyuge_tipo_documento" class="form-control">
                    <option value="">Seleccionar</option>
                    <option value="1">DNI</option>
                    <option value="7">Documento extranjero</option>
                  </select>
                </div>
                <div class="col-xl-3 col-md-6">
                  <label class="form-label" for="modal_conyuge_numero_documento">Nro. documento <sup class="text-danger">*</sup></label>
                  <div class="input-group">
                    <input type="text" name="numero_documento" id="modal_conyuge_numero_documento" class="form-control" maxlength="20" />
                    <button class="btn btn-primary" type="button" id="btn-buscar-conyuge-modal">
                      <i class="bx bx-search-alt" id="search_modal_conyuge"></i>
                      <div class="spinner-border spinner-border-sm" role="status" id="charge_modal_conyuge" style="display: none;"></div>
                    </button>
                  </div>
                  <input type="hidden" id="modal_conyuge_tipo_persona_sunat" value="NATURAL" />
                  <input type="hidden" id="modal_conyuge_nombre_comercial" />
                </div>
                <div class="col-xl-6 col-md-12">
                  <label class="form-label" for="modal_conyuge_descripcion">Nombres y apellidos <sup class="text-danger">*</sup></label>
                  <input type="text" name="descripcion" id="modal_conyuge_descripcion" class="form-control"
                          placeholder="Ej. Mar&iacute;a P&eacute;rez G&oacute;mez" />
                </div>
                <div class="col-xl-4 col-md-6">
                  <label class="form-label" for="modal_conyuge_nombre">Nombres</label>
                  <input type="text" name="nombre_persona_natural" id="modal_conyuge_nombre" class="form-control" />
                </div>
                <div class="col-xl-4 col-md-6">
                  <label class="form-label" for="modal_conyuge_apellido_paterno">Apellido paterno</label>
                  <input type="text" name="apellido_paterno_persona_natural" id="modal_conyuge_apellido_paterno" class="form-control" />
                </div>
                <div class="col-xl-4 col-md-6">
                  <label class="form-label" for="modal_conyuge_apellido_materno">Apellido materno</label>
                  <input type="text" name="apellido_materno_persona_natural" id="modal_conyuge_apellido_materno" class="form-control" />
                </div>
                <div class="col-xl-3 col-md-6">
                  <label class="form-label" for="modal_conyuge_sexo">Sexo <sup class="text-danger">*</sup></label>
                  <select name="sexo" id="modal_conyuge_sexo" class="form-control">
                    <option value="">Seleccionar</option>
                    <option value="M">Masculino</option>
                    <option value="F">Femenino</option>
                  </select>
                </div>
                <div class="col-xl-3 col-md-6">
                  <label class="form-label" for="modal_conyuge_fecha_nacimiento">Fecha de nacimiento <sup class="text-danger">*</sup></label>
                  <input type="date" name="fecha_nacimiento" id="modal_conyuge_fecha_nacimiento" class="form-control" />
                </div>
                <div class="col-xl-3 col-md-6">
                  <label class="form-label" for="modal_conyuge_nacionalidad">Nacionalidad <sup class="text-danger">*</sup></label>
                  <select name="nacionalidad" id="modal_conyuge_nacionalidad" class="form-control">
                    <option value="">Seleccionar</option>
                    <option value="PERUANO">PERUANO</option>
                    <option value="EXTRANJERO">EXTRANJERO</option>
                  </select>
                </div>
                <div class="col-xl-3 col-md-6">
                  <label class="form-label" for="modal_conyuge_celular">Celular</label>
                  <input type="tel" name="celular" id="modal_conyuge_celular" class="form-control"
                          maxlength="15" onkeypress="return soloNumeros(event)" />
                </div>
                <div class="col-12 mt-4">
                  <div class="fw-semibold border-bottom pb-2">Contacto y direcci&oacute;n</div>
                </div>
                <div class="col-xl-5 col-md-6">
                  <label class="form-label" for="modal_conyuge_correo">Correo</label>
                  <input type="email" name="correo" id="modal_conyuge_correo" class="form-control" />
                </div>
                <div class="col-xl-7 col-md-6">
                  <label class="form-label" for="modal_conyuge_direccion">Direcci&oacute;n <sup class="text-danger">*</sup></label>
                  <input type="text" name="direccion" id="modal_conyuge_direccion" class="form-control" />
                </div>
                <div class="col-xl-6 col-md-6">
                  <label class="form-label" for="modal_conyuge_direccion_referencia">Referencia</label>
                  <input type="text" name="direccion_referencia" id="modal_conyuge_direccion_referencia" class="form-control" />
                </div>
                <div class="col-xl-6 col-md-6">
                  <label class="form-label" for="modal_conyuge_iddistrito">Distrito</label>
                  <select id="modal_conyuge_iddistrito" class="form-control" style="width: 100%;"></select>
                  <input type="hidden" name="iddistrito" id="modal_conyuge_iddistrito_envio" />
                  <input type="hidden" name="cod_ubigeo" id="modal_conyuge_cod_ubigeo" />
                </div>
              </div>
            </div>
            <div class="modal-footer border-top">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary" id="btn-guardar-conyuge">
                <i class="ti ti-device-floppy me-1"></i>Guardar c&oacute;nyuge
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>



    <div class="modal fade" id="modal-detalle-cliente" tabindex="-1" aria-labelledby="modal-detalle-cliente-label" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
          <div class="modal-header border-bottom">
            <div>
              <h5 class="modal-title fw-semibold mb-1" id="modal-detalle-cliente-label">Detalle del cliente</h5>
              <p class="text-muted mb-0 fs-12" id="detalle-cliente-subtitulo">Informaci&oacute;n registrada del cliente</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body p-0">
            <div class="row g-0">
              <div class="col-lg-4 border-end">
                <div class="p-4 h-100">
                  <div class="text-center mb-4">
                    <span class="avatar avatar-xxl avatar-rounded mb-3">
                      <img src="{{ asset('assets/modulo/persona/perfil/hombre.png') }}" alt="" id="detalle-cliente-imagen">
                    </span>
                    <h5 class="fw-semibold mb-1" id="detalle-cliente-nombre">-</h5>
                    <div class="text-muted fs-12" id="detalle-cliente-documento">-</div>
                    <div class="mt-3" id="detalle-cliente-estado"></div>
                  </div>

                  <div class="border rounded p-3 mb-3">
                    <div class="d-flex align-items-center mb-2">
                      <i class="ri-phone-line text-primary me-2"></i>
                      <span class="fw-semibold">Contacto</span>
                    </div>
                    <div class="small text-muted mb-1">Celular</div>
                    <div class="fw-semibold mb-2" id="detalle-cliente-celular">-</div>
                    <div class="small text-muted mb-1">Correo</div>
                    <div class="fw-semibold text-break" id="detalle-cliente-correo">-</div>
                  </div>

                  <div class="border rounded p-3">
                    <div class="d-flex align-items-center mb-2">
                      <i class="ri-calendar-line text-primary me-2"></i>
                      <span class="fw-semibold">Registro</span>
                    </div>
                    <div class="small text-muted mb-1">C&oacute;digo</div>
                    <div class="fw-semibold mb-2" id="detalle-cliente-codigo">-</div>
                    <div class="small text-muted mb-1">Actualizado</div>
                    <div class="fw-semibold" id="detalle-cliente-actualizado">-</div>
                  </div>
                </div>
              </div>

              <div class="col-lg-8">
                <div class="p-4">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h6 class="fw-semibold mb-0">Informaci&oacute;n general</h6>
                    <span class="badge bg-primary-transparent" id="detalle-cliente-tipo-persona">-</span>
                  </div>

                  <div class="row gy-3 mb-4">
                    <div class="col-md-6">
                      <div class="small text-muted mb-1">Raz&oacute;n social / Descripci&oacute;n</div>
                      <div class="fw-semibold" id="detalle-cliente-descripcion">-</div>
                    </div>
                    <div class="col-md-6">
                      <div class="small text-muted mb-1">Nombre comercial</div>
                      <div class="fw-semibold" id="detalle-cliente-nombre-comercial">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Nombre</div>
                      <div class="fw-semibold" id="detalle-cliente-nombre-natural">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Apellido paterno</div>
                      <div class="fw-semibold" id="detalle-cliente-apellido-paterno">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Apellido materno</div>
                      <div class="fw-semibold" id="detalle-cliente-apellido-materno">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Sexo</div>
                      <div class="fw-semibold" id="detalle-cliente-sexo">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Fecha de nacimiento</div>
                      <div class="fw-semibold" id="detalle-cliente-fecha-nacimiento">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Estado civil</div>
                      <div class="fw-semibold" id="detalle-cliente-estado-civil">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Nacionalidad</div>
                      <div class="fw-semibold" id="detalle-cliente-nacionalidad">-</div>
                    </div>
                  </div>

                  <div id="detalle-cliente-pareja-contenedor" class="mb-4" style="display: none;">
                    <h6 class="fw-semibold mb-3">Datos del c&oacute;nyuge</h6>
                    <div class="row gy-3">
                      <div class="col-md-5">
                        <div class="small text-muted mb-1">Nombres y apellidos</div>
                        <div class="fw-semibold" id="detalle-cliente-pareja-nombre">-</div>
                      </div>
                      <div class="col-md-4">
                        <div class="small text-muted mb-1">Documento</div>
                        <div class="fw-semibold" id="detalle-cliente-pareja-documento">-</div>
                      </div>
                      <div class="col-md-3">
                        <div class="small text-muted mb-1">Celular</div>
                        <div class="fw-semibold" id="detalle-cliente-pareja-celular">-</div>
                      </div>
                    </div>
                  </div>

                  <h6 class="fw-semibold mb-3">Direcci&oacute;n</h6>
                  <div class="row gy-3">
                    <div class="col-12">
                      <div class="small text-muted mb-1">Direcci&oacute;n</div>
                      <div class="fw-semibold" id="detalle-cliente-direccion">-</div>
                    </div>
                    <div class="col-12">
                      <div class="small text-muted mb-1">Referencia</div>
                      <div class="fw-semibold" id="detalle-cliente-referencia">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Distrito</div>
                      <div class="fw-semibold" id="detalle-cliente-distrito">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Provincia</div>
                      <div class="fw-semibold" id="detalle-cliente-provincia">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">Departamento</div>
                      <div class="fw-semibold" id="detalle-cliente-departamento">-</div>
                    </div>
                    <div class="col-md-4">
                      <div class="small text-muted mb-1">C&oacute;digo ubigeo</div>
                      <div class="fw-semibold" id="detalle-cliente-ubigeo">-</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            @permiso('clientes', 'editar')
            <button type="button" class="btn btn-primary" id="detalle-cliente-editar"><i class="ri-edit-line me-1"></i>Editar</button>
            @endpermiso
          </div>
        </div>
      </div>
    </div>



    @include('layouts.yn_search_modal')
    @include('layouts.yn_footer')
  </div>

  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')

  <script src="{{ asset('assets/js/cliente.js') }}?v={{ filemtime(public_path('assets/js/cliente.js')) }}"></script>
</body>

</html>
