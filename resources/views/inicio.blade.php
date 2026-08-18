<!DOCTYPE html>
  <html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

  <head>

    @include('layouts.yn_head', ['title_page' => 'Inicio'])    

    <style> 
      
      .welcome-panel {
        background: linear-gradient(135deg, #0d6efd 0%, #20c997 100%);
        border-radius: 8px;
        color: #fff;
        overflow: hidden;
        position: relative;
      }

      .welcome-panel::after {
        content: "";
        position: absolute;
        width: 220px;
        height: 220px;
        right: -70px;
        bottom: -90px;
        background: rgba(255, 255, 255, 0.16);
        border-radius: 50%;
      }

      .welcome-panel .welcome-content {
        position: relative;
        z-index: 1;
      }

      .welcome-icon {
        width: 64px;
        height: 64px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 8px;
      }
    </style>

  </head>

  <body id="body-usuario">

    @include('layouts.yn_switcher')
    @include('layouts.yn_loader')

    <div class="page">
      @include('layouts.yn_header')
      @include('layouts.yn_sidebar')      

      <!-- Start::app-content -->
      <div class="main-content app-content">
        <div class="container-fluid">
            
          <!-- Start::page-header -->
          <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
              <div class="d-md-flex d-block align-items-center ">
                <div>
                  <p class="fw-semibold fs-18 mb-0 title-body-pagina">Bienvenido</p>
                  <span class="fs-semibold text-muted detalle-body-pagina">Panel principal del sistema Traventours Peru.</span>
                </div>
              </div>
            </div>

            <div class="btn-list mt-md-0 mt-2">
              <nav>
                <ol class="breadcrumb mb-0">
                  <li class="breadcrumb-item"><a href="javascript:void(0);">Inicio</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Bienvenida</li>
                </ol>
              </nav>
            </div>
          </div>
          <!-- End::page-header -->

          <!-- Start::row-1 -->          
          <div class="row">

            <!-- ::::::::::::::::::: VER TABLA PRINCIPAL ::::::::::::::::::: -->
            <div class="col-xxl-12 col-xl-12 " id="div-tabla-principal">          
              <div class="card custom-card">
                
                <div class="card-body">                      
                  <div class="welcome-panel p-4 p-lg-5">
                    <div class="welcome-content row align-items-center g-4">
                      <div class="col-lg-8">
                        <span class="badge bg-light text-primary mb-3">Traventours Peru</span>
                        <h1 class="fw-bold mb-3">Bienvenido, {{ Auth::user()?->display_name ?? 'Usuario' }}</h1>
                        <p class="fs-16 mb-4 opacity-75">
                          Nos alegra tenerte de vuelta. Desde aqui puedes administrar tus modulos,
                          revisar informacion importante y continuar con tus actividades del dia.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                          <a href="javascript:void(0);" class="btn btn-light text-primary fw-semibold">
                            <i class="ri-dashboard-line me-1"></i> Ir al panel
                          </a>
                          <a href="javascript:void(0);" class="btn btn-outline-light">
                            <i class="ri-user-settings-line me-1"></i> Mi perfil
                          </a>
                          <a href="{{ route('inicio.limpiar_cache') }}" class="btn btn-warning">
                            <i class="ri-refresh-line me-1"></i> Limpiar cache sistema
                          </a>
                          <a href="{{ route('inicio.limpiar_cache_navegador') }}" class="btn btn-info text-white">
                            <i class="ri-delete-bin-line me-1"></i> Limpiar cache navegador
                          </a>
                        </div>
                      </div>
                      <div class="col-lg-4 text-lg-end text-start">
                        <div class="welcome-icon mb-3">
                          <i class="ri-home-heart-line fs-1"></i>
                        </div>
                        <h5 class="fw-semibold mb-2">Gestion rapida y ordenada</h5>
                        <p class="mb-0 opacity-75">
                          Usa el menu lateral para navegar por clientes, zonas y demas opciones disponibles.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card-footer border-top-0">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <div class="d-flex align-items-center p-3 border rounded-2 h-100">
                        <span class="avatar avatar-md bg-primary-transparent me-3">
                          <i class="ri-team-line fs-20"></i>
                        </span>
                        <div>
                          <p class="fw-semibold mb-0">Clientes</p>
                          <small class="text-muted">Administra la informacion registrada.</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="d-flex align-items-center p-3 border rounded-2 h-100">
                        <span class="avatar avatar-md bg-success-transparent me-3">
                          <i class="ri-map-pin-line fs-20"></i>
                        </span>
                        <div>
                          <p class="fw-semibold mb-0">Zonas</p>
                          <small class="text-muted">Consulta y organiza tus ubicaciones.</small>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="d-flex align-items-center p-3 border rounded-2 h-100">
                        <span class="avatar avatar-md bg-info-transparent me-3">
                          <i class="ri-settings-3-line fs-20"></i>
                        </span>
                        <div>
                          <p class="fw-semibold mb-0">Configuracion</p>
                          <small class="text-muted">Mantiene el sistema listo para trabajar.</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
            </div>                       

            

            
            
          </div>          
          <!-- End::row-1 --> 
          
          
          <!-- MODAL - VER FOTO -->
          <div class="modal fade modal-effect" id="modal-ver-imgenes" tabindex="-1" aria-labelledby="modal-ver-imgenes" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-scrollable">
              <div class="modal-content">
                <div class="modal-header">
                  <h6 class="modal-title fs-13 title-ver-imgenes" id="modal-ver-imgenesLabel1">Imagen</h6>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body html_modal_ver_imgenes">
                  
                </div>
                <div class="modal-footer py-2">
                  <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal" ><i class="las la-times fs-lg"></i> Close</button>                  
                </div>
              </div>
            </div>
          </div> 
          <!-- End::Modal - Ver foto proveedor -->          

          

        </div>
      </div>
      <!-- End::app-content -->

      @include('layouts.yn_search_modal')
      @include('layouts.yn_footer')

    </div>

    @include('layouts.yn_scripts')
    @include('layouts.yn_custom_switcherjs') 
   
    <script>
      $(function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
      });
    </script>


  </body>

  </html>
