<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Catalogo de Codigos'])

  <style>
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

    .catalogo-code {
      display: inline-flex;
      min-width: 42px;
      justify-content: center;
      border-radius: .35rem;
      padding: .25rem .5rem;
      font-weight: 700;
      color: var(--primary-color);
      background: var(--primary01);
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

<body id="body-sunat-catalogos">
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
              <p class="fw-semibold fs-18 mb-0 title-body-pagina">Catalogo de Codigos SUNAT</p>
              <span class="fs-semibold text-muted detalle-body-pagina">
                <nav>
                  <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                    <li class="breadcrumb-item">SUNAT</li>
                    <li class="breadcrumb-item active" aria-current="page">Catalogo de Codigos</li>
                  </ol>
                </nav>
              </span>
            </div>
          </div>

          <div class="catalogo-layout">
            <div>
              <div class="catalogo-side-tabs mb-3" id="catalogo-tabs" role="tablist" aria-orientation="vertical">
                @foreach ($catalogos as $index => $catalogo)
                  <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="tab-{{ $catalogo['key'] }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $catalogo['key'] }}" type="button" role="tab" aria-controls="pane-{{ $catalogo['key'] }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                    <i class="{{ $catalogo['icono'] }} fs-18"></i>
                    <span>
                      <span class="d-block">{{ $catalogo['codigo'] }} {{ $catalogo['titulo'] }}</span>
                      <span class="d-block fs-12 text-muted fw-normal">{{ $catalogo['rows']->count() }} registros</span>
                    </span>
                  </button>
                @endforeach

              </div>
            </div>

            <div class="tab-content">
              @foreach ($catalogos as $index => $catalogo)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }} border-0" id="pane-{{ $catalogo['key'] }}" role="tabpanel" aria-labelledby="tab-{{ $catalogo['key'] }}" tabindex="0">
                  <div class="card custom-card">
                    <div class="card-header justify-content-between flex-wrap gap-2">
                      <div>
                        <div class="card-title mb-0">
                          <i class="{{ $catalogo['icono'] }} me-1"></i> {{ $catalogo['codigo'] }} - {{ $catalogo['titulo'] }}
                        </div>
                        <p class="text-muted mb-0 fs-12">{{ $catalogo['descripcion'] }}</p>
                      </div>
                      <span class="badge bg-secondary-transparent">{{ $catalogo['rows']->count() }} registros</span>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive catalogo-datatable-wrapper">
                        <table id="tabla-catalogo-{{ $catalogo['key'] }}" class="table table-bordered table-striped w-100 catalogo-table">
                          <thead>
                            <tr>
                              @foreach ($catalogo['columns'] as $column)
                                <th>{{ $column }}</th>
                              @endforeach
                            </tr>
                          </thead>
                          <tbody>
                            @forelse ($catalogo['rows'] as $row)
                              <tr data-catalogo-row>
                                @foreach (array_keys($row->getAttributes()) as $attribute)
                                  <td>
                                    @if ($loop->first)
                                      <span class="catalogo-code">{{ $row->{$attribute} ?: '-' }}</span>
                                    @else
                                      {{ $row->{$attribute} ?: '-' }}
                                    @endif
                                  </td>
                                @endforeach
                              </tr>
                            @empty
                              <tr>
                                <td colspan="{{ count($catalogo['columns']) }}" class="text-center text-muted">Sin registros.</td>
                              </tr>
                            @endforelse
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
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

  <script src="{{ asset('assets/js/sunat_catalogo_codigo.js') }}?v={{ filemtime(public_path('assets/js/sunat_catalogo_codigo.js')) }}"></script>
</body>

</html>
