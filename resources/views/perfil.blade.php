<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Mi Perfil'])

  <style>
    .perfil-wrapper {
      max-width: 1120px;
      margin: 0 auto;
    }

    .perfil-summary {
      background: linear-gradient(135deg, var(--primary-color), #2f6fed);
      color: #fff;
      position: relative;
    }

    .perfil-summary::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(255, 255, 255, 0.08);
      pointer-events: none;
    }

    .perfil-summary > * {
      position: relative;
      z-index: 1;
    }

    .perfil-avatar {
      border: 3px solid rgba(255, 255, 255, 0.45);
      box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, 0.18);
    }

    .perfil-stat {
      min-width: 120px;
      background: rgba(255, 255, 255, 0.13);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 0.5rem;
      padding: 0.75rem 1rem;
    }

    .perfil-security-layout {
      display: grid;
      grid-template-columns: 245px minmax(0, 1fr);
      gap: 1.5rem;
    }

    .perfil-side-tabs {
      position: sticky;
      top: 5rem;
    }

    .perfil-side-tabs .nav-link {
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

    .perfil-side-tabs .nav-link i {
      color: var(--text-muted);
    }

    .perfil-side-tabs .nav-link.active {
      color: #fff;
      background: var(--primary-color);
      border-color: var(--primary-color);
      box-shadow: 0 0.65rem 1.2rem var(--primary02);
    }

    .perfil-side-tabs .nav-link.active i,
    .perfil-side-tabs .nav-link.active .text-muted {
      color: rgba(255, 255, 255, 0.85) !important;
    }

    .perfil-help-card {
      border: 1px dashed var(--default-border);
      border-radius: 0.5rem;
      background: var(--default-background);
    }

    @media (max-width: 991.98px) {
      .perfil-security-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .perfil-side-tabs {
        position: static;
      }
    }

    @media (max-width: 575.98px) {
      .perfil-stat {
        width: 100%;
      }
    }
  </style>
</head>

<body id="body-perfil" data-auth-user-id="{{ auth()->id() }}">
  @php(auth()->user()?->loadMissing('persona.cargo'))
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="perfil-wrapper">
          <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
              <p class="fw-semibold fs-18 mb-0 title-body-pagina">Mi Perfil</p>
              <span class="fs-semibold text-muted detalle-body-pagina">
                <nav>
                  <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mi Perfil</li>
                  </ol>
                </nav>
              </span>
            </div>
          </div>

          <div class="card custom-card overflow-hidden">
            <div class="card-body p-0">
              <div class="perfil-summary p-4" id="perfil-cover">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
                  <div class="d-flex align-items-center flex-wrap gap-3">
                    <span class="avatar avatar-xxl avatar-rounded online perfil-avatar">
                      <img src="{{ auth()->user()->profile_photo_url }}" alt="" id="perfil-foto" onerror="this.src='{{ asset('assets/modulo/persona/perfil/hombre.png') }}';">
                    </span>

                    <div class="main-profile-info">
                      <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <h6 class="fw-semibold fs-18 mb-0 text-fixed-white" id="perfil-nombre">{{ auth()->user()->display_name }}</h6>
                        <span class="badge bg-light text-dark" id="perfil-estado-2fa">Cargando</span>
                      </div>
                      <p class="mb-2 text-fixed-white op-8" id="perfil-email">{{ auth()->user()->email }}</p>
                      <div class="d-flex flex-wrap gap-3 fs-12 text-fixed-white op-7">
                        <span><i class="ri-shield-user-line me-1 align-middle"></i>Cuenta personal</span>
                        <span><i class="ri-time-line me-1 align-middle"></i><span id="perfil-actualizado">-</span></span>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex flex-wrap gap-2">
                    <div class="perfil-stat">
                      <p class="fw-bold fs-20 text-fixed-white text-shadow mb-0" id="perfil-sesiones-total">0</p>
                      <p class="mb-0 fs-11 op-7 text-fixed-white">Sesiones</p>
                    </div>
                    <div class="perfil-stat">
                      <p class="fw-bold fs-20 text-fixed-white text-shadow mb-0" id="perfil-2fa-resumen">-</p>
                      <p class="mb-0 fs-11 op-7 text-fixed-white">2FA</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="p-4">
                <div class="d-flex align-items-start gap-3">
                  <span class="avatar avatar-md avatar-rounded bg-primary-transparent text-primary flex-shrink-0">
                    <i class="ri-lock-password-line fs-20"></i>
                  </span>
                  <div>
                    <p class="fs-15 mb-1 fw-semibold">Seguridad de la cuenta</p>
                    <p class="text-muted mb-0">Administra el segundo factor de autenticacion y tus sesiones activas desde un solo lugar.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="perfil-security-layout">
            <div>
              <div class="perfil-side-tabs" id="perfil-security-tabs" role="tablist" aria-orientation="vertical">
                <button class="nav-link active" id="perfil-2fa-tab" data-bs-toggle="tab" data-bs-target="#perfil-2fa-tab-pane" type="button" role="tab" aria-controls="perfil-2fa-tab-pane" aria-selected="true">
                  <i class="ri-shield-keyhole-line fs-18"></i>
                  <span>
                    <span class="d-block">Autenticacion 2FA</span>
                    <span class="d-block fs-12 text-muted fw-normal">Segundo factor</span>
                  </span>
                </button>

                <button class="nav-link" id="perfil-sesiones-tab" data-bs-toggle="tab" data-bs-target="#perfil-sesiones-tab-pane" type="button" role="tab" aria-controls="perfil-sesiones-tab-pane" aria-selected="false">
                  <i class="ri-computer-line fs-18"></i>
                  <span>
                    <span class="d-block">Sesiones activas</span>
                    <span class="d-block fs-12 text-muted fw-normal">Dispositivos conectados</span>
                  </span>
                </button>

                <div class="perfil-help-card p-3 mt-3 text-center">
                  <span class="avatar avatar-md avatar-rounded bg-primary-transparent text-primary mb-2">
                    <i class="ri-lock-line fs-20"></i>
                  </span>
                  <p class="fw-semibold mb-1 fs-13">Centro de seguridad</p>
                  <p class="text-muted mb-0 fs-12">Configura y revisa tu acceso desde este panel.</p>
                </div>
              </div>
            </div>

            <div class="tab-content">
              <div class="tab-pane fade show active border-0" id="perfil-2fa-tab-pane" role="tabpanel" aria-labelledby="perfil-2fa-tab" tabindex="0">
                <div class="card custom-card">
                  <div class="card-header justify-content-between flex-wrap gap-2">
                    <div class="card-title mb-0">
                      <i class="ri-shield-keyhole-line me-1"></i> Autenticacion de dos factores
                    </div>
                    <span class="badge bg-secondary-transparent" id="perfil-2fa-badge">Cargando</span>
                  </div>
                  <div class="card-body p-0" id="perfil-2fa-body">
                    <div class="text-center text-muted py-5">
                      <div class="spinner-border text-primary mb-3" role="status"></div>
                      <div class="fw-semibold">Cargando configuracion 2FA...</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade border-0" id="perfil-sesiones-tab-pane" role="tabpanel" aria-labelledby="perfil-sesiones-tab" tabindex="0">
                <div class="card custom-card">
                  <div class="card-header justify-content-between flex-wrap gap-2">
                    <div class="card-title mb-0">
                      <i class="ri-computer-line me-1"></i> Sesiones activas
                    </div>
                    <button type="button" class="btn btn-sm btn-danger-light btn-wave" id="perfil-cerrar-otras-sesiones">
                      <i class="ri-logout-box-r-line me-1"></i>Cerrar otras sesiones
                    </button>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <p class="fs-15 mb-1 fw-semibold">Dispositivos conectados</p>
                      <p class="text-muted mb-0 fs-13">Revisa tus accesos y cierra las sesiones que ya no uses.</p>
                    </div>

                    <div id="perfil-sesiones-body">
                      <div class="text-center text-muted py-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <div class="fw-semibold">Consultando sesiones activas...</div>
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

    @include('layouts.yn_search_modal')
    @include('layouts.yn_footer')
  </div>

  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')

  <script src="{{ asset('assets/js/perfil.js') }}?v={{ filemtime(public_path('assets/js/perfil.js')) }}"></script>
</body>

</html>
