<!DOCTYPE html>
  <html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

  <head>
    @include('layouts.yn_head', ['title_page' => 'Reserva'])

    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset('ynex_admin/libs/summernote/summernote-bs4.min.css') }}">


    <link rel="stylesheet" href="{{ asset('ynex_admin/libs/quill/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('ynex_admin/libs/quill/quill.bubble.css') }}">


    <link rel="stylesheet" href="{{ asset('ynex_admin/libs/filepond/filepond.min.css') }}">
    <link rel="stylesheet" href="{{ asset('ynex_admin/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css') }}">
    <link rel="stylesheet" href="{{ asset('ynex_admin/libs/filepond-plugin-image-edit/filepond-plugin-image-edit.min.css') }}">
    <link rel="stylesheet" href="{{ asset('ynex_admin/libs/dropzone/dropzone.css') }}">
    <!-- GLightbox CSS -->
    <link rel="stylesheet" href="{{ asset('ynex_admin/libs/glightbox/css/glightbox.min.css') }}">

    <style>
      :root {
        --reserva-surface: var(--custom-white);
        --reserva-surface-muted: var(--default-background);
        --reserva-border: var(--default-border);
        --reserva-border-soft: rgba(15, 23, 42, 0.12);
        --reserva-text: var(--default-text-color);
        --reserva-muted: var(--text-muted);
        --reserva-action-bg: rgba(255, 255, 255, 0.96);
        --reserva-loader-bg: rgba(255, 255, 255, 0.82);
        --reserva-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
        --reserva-action-shadow: 0 -6px 18px rgba(15, 23, 42, 0.08);
      }

      [data-theme-mode=dark] {
        --reserva-surface: var(--custom-white);
        --reserva-surface-muted: var(--default-background);
        --reserva-border: var(--default-border);
        --reserva-border-soft: rgba(255, 255, 255, 0.16);
        --reserva-text: var(--default-text-color);
        --reserva-muted: var(--text-muted);
        --reserva-action-bg: rgba(26, 28, 30, 0.96);
        --reserva-loader-bg: rgba(26, 28, 30, 0.76);
        --reserva-shadow: 0 10px 28px rgba(0, 0, 0, 0.28);
        --reserva-action-shadow: 0 -6px 18px rgba(0, 0, 0, 0.24);
      }
      .imagen-metodo-pago img {
        /*height: auto !important;  Mantén la proporción de aspecto */
        width: 140px !important;
        /* Máximo ancho permitido */
        height: 130px !important;
        /* Máximo alto permitido */
        object-fit: contain !important;
        border: 1px solid var(--reserva-border) !important;
        box-sizing: border-box !important;
      }

      .div_pago_rapido img {
        width: 60px;
        /* Ajusta el tamaño de las imágenes */
        height: 100%;
        cursor: pointer;
        border: 3px solid var(--reserva-border);
        border-radius: 5px;
        transition: border-color 0.3s ease;
        /* Suaviza la transición */
      }

      .div_pago_rapido img:hover {
        border-color: #007bff;
        /* Cambia el borde al pasar el ratón */
      }


      .reserva-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
      }

      .reserva-toolbar .btn-list {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }

      .reserva-panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0;
        font-weight: 700;
      }

      .reserva-panel-title i {
        color: var(--primary-color);
        font-size: 1.1rem;
      }

      .reserva-filters {
        background: var(--reserva-surface-muted);
        border-bottom: 1px solid var(--reserva-border);
      }

      .reserva-filter-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(180px, 240px)) minmax(260px, 1fr);
        gap: 12px;
        align-items: end;
      }

      .reserva-table-shell {
        border: 1px solid var(--reserva-border);
        border-radius: 8px;
        overflow: auto;
      }

      .reserva-form-shell {
        /*max-width: 1360px;*/
        margin: 0 auto;
      }

      .reserva-section {
        border: 1px solid var(--reserva-border);
        border-radius: 8px;
        padding: 16px;
        background: var(--reserva-surface);
        color: var(--reserva-text);
      }

      .reserva-section + .reserva-section {
        margin-top: 16px;
      }

      .reserva-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 10px;
        margin-bottom: 14px;
        border-bottom: 1px dashed var(--reserva-border-soft);
        color: var(--primary-color);
        font-weight: 700;
      }

      .reserva-section-title i {
        font-size: 1rem;
      }

      .reserva-inline-actions {
        display: inline-flex;
        align-items: center;
        gap: 4px;
      }

      .reserva-inline-actions .badge,
      .reserva-inline-actions button.badge {
        width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
      }

      .reserva-input-panel {
        border: 1px solid var(--reserva-border);
        border-radius: 8px;
        padding: 12px;
        background: var(--reserva-surface-muted);
        color: var(--reserva-text);
      }

      .reserva-modal .modal-content {
        border: 0;
        border-radius: 8px;
        background: var(--reserva-surface);
        color: var(--reserva-text);
      }

      .reserva-modal .modal-header {
        border-bottom: 1px solid var(--reserva-border);
      }

      #modal-asociar-comprobante-reserva .select2-container--bootstrap4 .select2-results__option--highlighted,
      #modal-asociar-comprobante-reserva .select2-container--bootstrap4 .select2-results__option--highlighted .text-muted {
        color: #fff !important;
      }

      #modal-asociar-comprobante-reserva .select2-container--bootstrap4 .select2-results__option--highlighted .fw-semibold {
        color: #fff !important;
      }
      #tabla-reserva_filter {
        width: 100% !important;
      }

      #tabla-reserva_filter label {
        width: 100% !important;
        margin-bottom: 0 !important;
      }

      #tabla-reserva_filter label input {
        width: 100% !important;
        margin-left: 0 !important;
      }

      #tabla-reserva_length select {
        min-width: 58px;
      }

      #tabla-reserva_wrapper .dt-buttons {
        display: inline-flex;
        gap: 4px;
        margin-left: 4px;
      }

      #tabla-reserva_wrapper .dt-buttons .btn,
      #tabla-reserva_wrapper .buttons-reload {
        width: 34px;
        height: 31px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }

      #tabla-reserva {
        vertical-align: middle;
      }

      #tabla-reserva th,
      #tabla-reserva td {
        vertical-align: middle;
        white-space: nowrap;
      }

      .reserva-action-bar {
        position: sticky;
        bottom: 0;
        z-index: 8;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 16px;
        padding: 12px;
        background: var(--reserva-action-bg);
        border: 1px solid var(--reserva-border);
        border-radius: 8px;
        box-shadow: var(--reserva-action-shadow);
        backdrop-filter: blur(6px);
        color: var(--reserva-text);
      }

      .reserva-action-total {
        min-width: 170px;
      }

      .reserva-action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
      }



      .reserva-step-nav {
        position: sticky;
        top: 70px;
        z-index: 9;
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding: 8px;
        border: 1px solid var(--reserva-border);
        border-radius: 8px;
        background: var(--reserva-surface);
      }

      .reserva-step-tab {
        min-width: 112px;
        border: 1px solid var(--reserva-border-soft);
        background: var(--reserva-surface-muted);
        color: var(--reserva-text);
        font-weight: 600;
        white-space: nowrap;
      }

      .reserva-step-tab.active {
        border-color: var(--primary-color);
        background: var(--primary-color);
        color: #fff;
      }

      .reserva-step-tab.is-disabled {
        display: none;
      }

      .reserva-tab-pane {
        display: none;
        position: relative;
      }

      .reserva-tab-pane.active {
        display: block;
      }

      .reserva-summary-col {
        align-self: flex-start;
      }

      .reserva-summary-card {
        position: sticky;
        top: 132px;
        border: 1px solid var(--reserva-border);
        border-radius: 8px;
        background: var(--reserva-surface);
        box-shadow: var(--reserva-shadow);
      }

      .reserva-summary-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 14px 10px;
        border-bottom: 1px solid var(--reserva-border);
        font-weight: 700;
        color: var(--reserva-text);
      }

      .reserva-summary-body {
        padding: 10px 14px 14px;
      }

      .reserva-summary-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 9px 0;
        border-bottom: 1px dashed var(--reserva-border-soft);
        color: var(--reserva-muted);
      }

      .reserva-summary-row:last-child {
        border-bottom: 0;
      }

      .reserva-summary-value {
        font-weight: 700;
        color: var(--reserva-text);
        text-align: right;
      }


      .reserva-summary-row label {
        margin-bottom: 0;
        font-weight: 600;
      }

      .reserva-summary-control {
        max-width: 132px;
      }

      .reserva-summary-control .form-control {
        text-align: right;
        font-weight: 700;
      }

      .reserva-summary-control .form-control,
      .reserva-summary-control .input-group-text {
        background-color: var(--form-control-bg);
        border-color: var(--input-border);
        color: var(--reserva-text);
      }

      .reserva-summary-row.total .form-control {
        color: var(--primary-color);
        font-size: 1rem;
      }
      .reserva-summary-row.total .reserva-summary-value {
        color: var(--primary-color);
        font-size: 1.15rem;
      }

      .reserva-section-loader {
        position: absolute;
        inset: 0;
        z-index: 6;
        display: none;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border-radius: 8px;
        background: var(--reserva-loader-bg);
        color: var(--reserva-text);
        font-weight: 700;
      }

      .reserva-summary-card.is-loading .reserva-section-loader,
      .reserva-tab-pane.is-loading .reserva-section-loader {
        display: flex;
      }

      .reserva-field-error {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        color: #dc3545;
      }

      .select2-container.is-invalid .select2-selection {
        border-color: #dc3545 !important;
      }

      .reserva-pax-message {
        display: none;
        margin-top: 8px;
      }

      .reserva-pax-message.is-visible {
        display: block;
      }

      #tabla-productos-seleccionados {
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 1276px;
        table-layout: fixed;
      }

      #tabla-productos-seleccionados th:nth-child(1),
      #tabla-productos-seleccionados td:nth-child(1) { width: 64px; }

      #tabla-productos-seleccionados th:nth-child(2),
      #tabla-productos-seleccionados td:nth-child(2) { width: 260px; }

      #tabla-productos-seleccionados th:nth-child(3),
      #tabla-productos-seleccionados td:nth-child(3) { width: 140px; }

      #tabla-productos-seleccionados th:nth-child(4),
      #tabla-productos-seleccionados td:nth-child(4) { width: 140px; }

      #tabla-productos-seleccionados th:nth-child(5),
      #tabla-productos-seleccionados td:nth-child(5) { width: 80px; }

      #tabla-productos-seleccionados th:nth-child(6),
      #tabla-productos-seleccionados td:nth-child(6) { width: 165px; }

      #tabla-productos-seleccionados th:nth-child(7),
      #tabla-productos-seleccionados td:nth-child(7) { width: 270px; }

      #tabla-productos-seleccionados th:nth-child(8),
      #tabla-productos-seleccionados td:nth-child(8) { width: 115px; }

      #tabla-productos-seleccionados th:nth-child(9),
      #tabla-productos-seleccionados td:nth-child(9) { width: 120px; }
      #tabla-productos-seleccionados thead th {
        background: var(--reserva-surface-muted);
        border-color: var(--reserva-border) !important;
        color: var(--reserva-muted);
        padding: 10px 8px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: uppercase;
      }

      #tabla-productos-seleccionados tbody tr {
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
      }

      #tabla-productos-seleccionados tbody tr:hover {
        transform: translateY(-1px);
      }

      #tabla-productos-seleccionados tbody td {
        background: var(--reserva-surface);
        border-top: 1px solid var(--reserva-border) !important;
        border-bottom: 1px solid var(--reserva-border) !important;
        border-left: 0 !important;
        border-right: 0 !important;
        color: var(--reserva-text);
        padding: 9px 8px;
        vertical-align: middle;
      }

      #tabla-productos-seleccionados tbody tr:hover td {
        border-color: var(--primary-color) !important;
        box-shadow: 0 6px 16px rgba(var(--primary-rgb), 0.08);
      }

      #tabla-productos-seleccionados tbody td:first-child {
        border-left: 1px solid var(--reserva-border) !important;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
        text-align: center;
      }

      #tabla-productos-seleccionados tbody td:last-child {
        border-right: 1px solid var(--reserva-border) !important;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
      }

      #tabla-productos-seleccionados tbody tr:hover td:first-child,
      #tabla-productos-seleccionados tbody tr:hover td:last-child {
        border-color: var(--primary-color) !important;
      }

      #tabla-productos-seleccionados tbody td:nth-child(2) {
        font-weight: 700;
        min-width: 210px;
        white-space: normal;
      }

      #tabla-productos-seleccionados .form-control,
      #tabla-productos-seleccionados .form-select,
      #tabla-productos-seleccionados .textarea_datatable {
        width: 100% !important;
        max-width: 100%;
        background-color: var(--form-control-bg) !important;
        border-color: var(--input-border) !important;
        color: var(--reserva-text) !important;
        font-size: 12px;
        min-height: 36px;
        border-radius: 6px;
        box-shadow: none;
      }

      #tabla-productos-seleccionados .textarea_datatable {
        min-width: 100%;
        height: 40px;
        padding: 6px 8px;
        border-radius: 4px;
        resize: vertical;
      }

      #tabla-productos-seleccionados .product-btn {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }


      #tabla-productos-seleccionados tbody td:nth-child(1),
      #tabla-productos-seleccionados tbody td:nth-child(5),
      #tabla-productos-seleccionados tbody td:nth-child(8),
      #tabla-productos-seleccionados tbody td:nth-child(9),
      #tabla-productos-seleccionados thead th:nth-child(1),
      #tabla-productos-seleccionados thead th:nth-child(5),
      #tabla-productos-seleccionados thead th:nth-child(8),
      #tabla-productos-seleccionados thead th:nth-child(9) {
        text-align: center;
      }

      #tabla-productos-seleccionados tbody td:nth-child(8) .form-control,
      #tabla-productos-seleccionados tbody td:nth-child(9) .form-control {
        text-align: right;
      }

      #tabla-productos-seleccionados tbody td:nth-child(5) .form-control {
        text-align: center;
      }

      #tabla-productos-seleccionados .form-control:focus,
      #tabla-productos-seleccionados .form-select:focus,
      #tabla-productos-seleccionados .textarea_datatable:focus {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 0.12rem rgba(var(--primary-rgb), 0.12) !important;
      }
      #tabla-productos-seleccionados tfoot td {
        background: var(--reserva-surface-muted);
        border-top: 1px solid var(--reserva-border) !important;
        border-bottom: 0 !important;
        color: var(--reserva-text);
        padding: 12px 10px;
        font-size: 13px;
      }

      #tabla-productos-seleccionados tfoot td:last-child {
        color: var(--primary-color);
        font-size: 15px;
      }

      #tabla-habitaciones-seleccionadas th:nth-child(8),
      #tabla-habitaciones-seleccionadas td:nth-child(8),
      #tabla-habitaciones-seleccionadas th:nth-child(9),
      #tabla-habitaciones-seleccionadas td:nth-child(9),
      #tabla-habitaciones-seleccionadas th:nth-child(10),
      #tabla-habitaciones-seleccionadas td:nth-child(10),
      #tabla-habitaciones-seleccionadas th:nth-child(12),
      #tabla-habitaciones-seleccionadas td:nth-child(12) {
        min-width: 150px;
      }

      #tabla-habitaciones-seleccionadas td:nth-child(8) .form-control,
      #tabla-habitaciones-seleccionadas td:nth-child(9) .form-control,
      #tabla-habitaciones-seleccionadas td:nth-child(10) .form-control,
      #tabla-habitaciones-seleccionadas td:nth-child(12) .form-control {
        min-width: 135px;
        height: 38px;
        font-size: 13px;
        font-weight: 700;
        text-align: right;
      }

      #tabla-habitaciones-seleccionadas td:nth-child(8) .form-control {
        text-align: center;
      }

      #tabla-habitaciones-seleccionadas th:nth-child(3),
      #tabla-habitaciones-seleccionadas td:nth-child(3) {
        min-width: 86px;
        text-align: center;
      }

      #tabla-habitaciones-seleccionadas td:nth-child(3) .form-control {
        min-width: 70px;
        height: 38px;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
      }

      #tabla-habitaciones-seleccionadas .form-control.is-valid,
      #tabla-habitaciones-seleccionadas .form-control.is-invalid,
      #tabla-productos-seleccionados .form-control.is-valid,
      #tabla-productos-seleccionados .form-control.is-invalid {
        background-image: none !important;
        padding-right: 0.5rem !important;
      }

      .facturacion-print-frame {
        width: 100%;
        height: 70vh;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #f8f9fa;
      }

      @media (max-width: 1199.98px) {
        .reserva-filter-grid {
          grid-template-columns: 1fr;
        }
        .reserva-step-nav,
        .reserva-summary-card {
          position: static;
        }
      }
      @media (max-width: 575.98px) {
        .reserva-action-bar {
          align-items: stretch;
          flex-direction: column;
        }

        .reserva-action-buttons .btn {
          flex: 1 1 auto;
        }
      }

    </style>
  </head>

  <body id="body-tours">
    @include('layouts.yn_switcher')
    @include('layouts.yn_loader')

    <div class="page">
      @include('layouts.yn_header')
      @include('layouts.yn_sidebar')
      

        <!-- Start::app-content -->
        <div class="main-content app-content">
          <div class="container-fluid">
            <!-- Start::page-header -->
            <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb reserva-toolbar">
              <div>
                <p class="fw-semibold fs-18 mb-0 title-body-pagina">Reservas</p>
                <nav>
                  <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Reservas</li>
                  </ol>
                </nav>
              </div>

              <div class="btn-list mt-md-0 mt-2">
                <button type="button" class="btn btn-light btn-cancelar m-r-10px" onclick="show_hide_form(1);" style="display: none;" data-bs-toggle="tooltip" title="Regresar">
                  <i class="bi bi-arrow-left label-btn-icon me-2"></i>Regresar
                </button>
                <button type="button" class="btn-modal-effect btn btn-success label-btn btn-guardar m-r-10px" style="display: none;">
                  <i class="ri-save-2-line label-btn-icon me-2"></i> Guardar
                </button>
                <button type="button" class="btn-modal-effect btn btn-primary label-btn btn-agregar m-r-10px" onclick="nueva_reserva();">
                  <i class="ri-user-add-line label-btn-icon me-2"></i>Agregar
                </button>
                <button type="button" class="btn btn-icon btn-sm btn-light m-r-10px" id="btn-recargar-reservas" data-bs-toggle="tooltip" title="Actualizar">
                  <i class="las la-sync-alt"></i>
                </button>
              </div>
            </div>
            <!-- End::page-header -->

            <!-- Start::row-1 -->
            <div class="row g-3">
              <div class="col-xxl-12 col-xl-12">
                <div>
                  <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                      <div>
                        <h5 class="card-title mb-1 reserva-panel-title"><i class="ri-calendar-check-line"></i> Listado de reservas</h5>
                        <p class="text-muted mb-0 fs-12">Consulta, filtra y administra reservas registradas.</p>
                      </div>
                    </div>
                    <div class="p-3 div_filtros reserva-filters" > 
                      <div class="row g-3">
                        <div class="col-12">
                          <div class="reserva-filter-grid">                                             
                            <!-- ::::::::::::::::::::: FILTRO FECHA :::::::::::::::::::::: -->
                            <div>
                              <div class="form-group">
                                <label for="filtro_fecha_i" class="form-label">
                                  <span class="badge bg-info m-r-4px cursor-pointer" onclick="reload_filtro_fecha_i();" data-bs-toggle="tooltip" title="Remover filtro"><i class="bi bi-trash3"></i></span>
                                  Fecha Inicio</label>
                                <input type="date" class="form-control" name="filtro_fecha_i" id="filtro_fecha_i" value="{{ now('America/Lima')->startOfMonth()->format('Y-m-d') }}" onchange="cargando_search(); delay(function(){filtros()}, 50 );">
                              </div>
                            </div>
                            <!-- ::::::::::::::::::::: FILTRO FECHA :::::::::::::::::::::: -->
                            <div>
                              <div class="form-group">
                                <label for="filtro_fecha_f" class="form-label">
                                  <span class="badge bg-info m-r-4px cursor-pointer" onclick="reload_filtro_fecha_f();" data-bs-toggle="tooltip" title="Remover filtro"><i class="bi bi-trash3"></i></span>
                                  Fecha Fin</label>
                                <input type="date" class="form-control" name="filtro_fecha_f" id="filtro_fecha_f" value="{{ now('America/Lima')->endOfMonth()->format('Y-m-d') }}" onchange="cargando_search(); delay(function(){filtros()}, 50 );">
                              </div>
                            </div>
                            <!-- ::::::::::::::::::::: FILTRO CLIENTE :::::::::::::::::::::: -->
                            <div>
                              <div class="form-group">
                                <label for="filtro_cliente" class="form-label">
                                  <span class="badge bg-info m-r-4px cursor-pointer" onclick="reload_filtro_cliente();" data-bs-toggle="tooltip" title="Actualizar"><i class="las la-sync-alt"></i></span>
                                  Cliente
                                  <span class="charge_filtro_cliente"></span>
                                </label>
                                <select name="filtro_cliente" id="filtro_cliente" onchange="cargando_search(); delay(function(){filtros()}, 50 );"> </select>
                              </div>
                            </div>                        
                          </div> 
                        </div>
                      </div>                                    
                                                      
                    </div>
                    <div class="card-body">

                      <!-- ------------ Tabla de Reservas ------------- -->
                      <div class="table-responsive reserva-table-shell" id="div-tabla">

                        <table class="table table-bordered table-hover align-middle w-100 mb-0" style="width: 100%;" id="tabla-reserva">
                          <thead>
                            <tr>
                              <th class="text-center">#</th>
                              <th class="text-center">Acc</th>
                              <th>Responsable</th>                              
                              <th>Tipo</th>                              
                              <th>F. Llegada</th>
                              <th class="text-center">Pax</th>
                              <th>Telefono</th>
                              <th>R. Usuario</th>
                              <th>Total</th>
                              <th>Deuda</th>
                              <th>Estado</th>
                            </tr>
                          </thead>
                          <tbody></tbody>
                          <tfoot>
                            <tr>
                              <td colspan="8" class="text-end fw-bold">Total general:</td>
                              <td id="tabla-reserva-total-general" class="fw-bold">S/ 0.00</td>
                              <td id="tabla-reserva-deuda-general" class="fw-bold">S/ 0.00</td>
                              <td></td>
                            </tr>
                          </tfoot>

                        </table>


                      </div>
                      <!-- ------------ Formulario de reservas ------------ -->
                      <div class="div-form reserva-form-shell p-0" style="display: none;">
                        <form name="form-agregar-reserva" id="form-agregar-reserva" method="POST" class="needs-validation" novalidate>
                          <div class="row g-3" id="cargando-1-formulario">

                            <div class="col-12">
                              <div class="reserva-step-nav" role="tablist" aria-label="Pasos de reserva">
                                <button type="button" class="btn reserva-step-tab active" data-reserva-step="general"><i class="ri-user-line me-1"></i> General</button>
                                <button type="button" class="btn reserva-step-tab" data-reserva-step="tours"><i class="ri-map-pin-line me-1"></i> Tours</button>
                                <button type="button" class="btn reserva-step-tab reserva-tab-hotel" data-reserva-step="hotel"><i class="ri-hotel-line me-1"></i> Hotel</button>
                                <button type="button" class="btn reserva-step-tab reserva-tab-vuelo" data-reserva-step="vuelo"><i class="ri-plane-line me-1"></i> Vuelo</button>
                              </div>
                            </div>

                            <div class="col-12 col-xl-3 order-3 order-xl-2 reserva-summary-col">
                              <aside class="reserva-summary-card" aria-label="Resumen de reserva">
                                <div class="reserva-summary-header"><i class="ri-bill-line text-primary"></i> Resumen</div>
                                <div class="reserva-summary-body">
                                  <div class="reserva-summary-row"><label for="resumen_total_tours">Total tours</label><div class="input-group input-group-sm reserva-summary-control"><span class="input-group-text">S/</span><input type="number" class="form-control reserva-summary-input" id="resumen_total_tours" step="0.01" min="0" value="0.00" data-summary-field="tours"></div></div>
                                  <div class="reserva-summary-row reserva-summary-hotel"><label for="resumen_total_hotel">Total hotel</label><div class="input-group input-group-sm reserva-summary-control"><span class="input-group-text">S/</span><input type="number" class="form-control reserva-summary-input" id="resumen_total_hotel" step="0.01" min="0" value="0.00" data-summary-field="hotel"></div></div>
                                  <div class="reserva-summary-row reserva-summary-vuelo"><label for="resumen_total_vuelo">Vuelo</label><div class="input-group input-group-sm reserva-summary-control"><span class="input-group-text">S/</span><input type="number" class="form-control reserva-summary-input" id="resumen_total_vuelo" step="0.01" min="0" value="0.00" data-summary-field="vuelo"></div></div>
                                  <div class="reserva-summary-row total"><label for="resumen_total_reserva">Total reserva</label><div class="input-group input-group-sm reserva-summary-control"><span class="input-group-text">S/</span><input type="number" class="form-control reserva-summary-input fw-bold" id="resumen_total_reserva" step="0.01" min="0" value="0.00" data-summary-field="total"></div></div>
                                </div>
                              </aside>
                            </div>

                            <div class="col-12 col-xl-9 order-xl-1 reserva-tab-pane active" data-reserva-step-panel="general">
                              <div class="reserva-section mb-3">
                                <div class="reserva-section-title">
                                  <i class="fa-solid fa-user"></i>
                                  <span>Datos generales</span>
                                </div>
                                <div class="row g-3">
                                  <!-- ID -->
                                  <input type="hidden" name="idreserva" id="idreserva" />

                            <div class="col-md-3 col-lg-3 col-xl-3 col-xxl-2">
                              <div class="form-group">
                                <label for="es_tour_solo" class="form-label">Solo Tours <sup class="text-danger">*</sup></label>
                                <div class="form-check form-switch d-flex align-items-center gap-2 reserva-input-panel ps-5 pe-3 py-2 mb-0">
                                  <input class="form-check-input" type="checkbox" role="switch" id="es_tour_solo" name="es_tour_solo" onchange="toggleModoReserva()">
                                  <label class="form-check-label text-muted" for="es_tour_solo">Sin hotel</label>
                                </div>
                              </div>
                            </div>

                            <!------------------- CODIGO --------------- -->
                            <div class="col-md-3 col-lg-3 col-xl-2 col-xxl-2">
                              <div class="form-group">
                                <label for="codigo" class="form-label">Nro Reserva <sup class="text-danger">*</sup> <span class="charge_codigo"></span></label>
                                <input type="text" class="form-control" name="codigo" id="codigo" onkeyup="mayus(this);" readonly data-bs-toggle="tooltip" data-bs-original-title="No se puede editar" />
                              </div>
                            </div>
                            <!-- ----------------- Cliente Responsable --------------- -->
                            <div class="col-md-6 col-lg-6 col-xl-5 col-xxl-6">
                              <div class="form-group">
                                <label for="idpersona_cliente" class="form-label">
                                  <span class="reserva-inline-actions"><button type="button" class="badge bg-success border-0 m-r-4px cursor-pointer js-agregar-nuevo-cliente" data-cliente-tipo="Clienteadd" title="Agregar"><i class="las la-plus"></i></button>
                                  <span class="badge bg-info m-r-4px cursor-pointer" onclick="reload_cliente_reserva();" data-bs-toggle="tooltip" title="Actualizar"><i class="las la-sync-alt"></i></span></span>
                                  Cliente Responsable <sup class="text-danger">*</sup>
                                  <span class="charge_cliente_reserva"></span>
                                </label>
                                <select class="form-control" name="idpersona_cliente" id="idpersona_cliente" onchange="valid_nro_cel();">
                                  <!-- lista de u medidas -->
                                </select>
                              </div>
                            </div>
                            <!-- ----------------- Nro Celular --------------- -->
                            <div class="col-md-3 col-lg-4 col-xl-2 col-xxl-2">
                              <div class="form-group">
                                <label for="nro_celular" class="form-label">Nro Celular <sup class="text-danger">*</sup></label>
                                <input type="text" class="form-control" id="nro_celular" readonly />
                              </div>
                            </div>
                            <!-- ----------------- Origen Reserva --------------- -->
                            <div class="col-md-4 col-lg-4 col-xl-4 col-xxl-3">
                              <div class="form-group">
                                <label for="idorigenreserva" class="form-label">
                                  <span class="badge bg-info m-r-4px cursor-pointer" data-bs-toggle="tooltip" title="Actualizar"><i class="las la-sync-alt"></i></span>
                                  Origen Reserva <sup class="text-danger">*</sup>
                                  <span class="charge_ididorigenreserva"></span>
                                </label>
                                <select class="form-control" name="idorigenreserva" id="idorigenreserva">
                                  <!-- lista de origenes de reserva -->
                                </select>
                              </div>
                            </div>

                            <!-- ----------------- Cantidad de personas por etapa --------------- -->
                            <div class="col-md-2 col-lg-4 col-xl-2 col-xxl-3 mt-3">
                              <div class="form-group">
                                <label for="prov_dep" class=" d-sm-flex d-block align-items-center justify-content-between page-header-breadcrumb form-label">
                                  <span>Nro Pax <sup class="text-danger">*</sup> </span>
                                  <span onclick="delay(function(){referencia_personas()}, 100 );"> <input type="checkbox" id="checkboxReferencia"> Ref. </span>
                                </label>

                                <input type="number" class="form-control" id="numero_pasajero" name="numero_pasajero" onkeyup="number_pax();" />
                              </div>
                            </div>

                            <div class="col-md-6 col-lg-6 col-xl-4 col-xxl-4 pt-3 div_referencia_pers" style="display: none !important;">

                              <div class="reserva-input-panel">
                                <div class="row g-2">
                                  <!-- Ninos -->
                                  <div class="col-4">
                                    <div class="form-group">
                                      <input type="number" class="form-control" name="cant_ninos" id="cant_ninos" placeholder="Ninos" onchange="validar_cant_pax();" onkeyup="validar_cant_pax();">
                                    </div>
                                  </div>

                                  <!-- Adultos -->
                                  <div class="col-4">
                                    <div class="form-group">
                                      <input type="number" class="form-control" name="cant_adultos" id="cant_adultos" placeholder="Adultos" onchange="validar_cant_pax();" onkeyup="validar_cant_pax();">
                                    </div>
                                  </div>

                                  <!-- Ancianos -->
                                  <div class="col-4">
                                    <div class="form-group">
                                      <input type="number" class="form-control" name="cant_ancianos" id="cant_ancianos" placeholder="Ancianos" onchange="validar_cant_pax();" onkeyup="validar_cant_pax();">
                                    </div>
                                  </div>

                                </div>
                              </div>

                            </div>

                            <div class="alert alert-danger reserva-pax-message" id="reserva_pax_error"></div>

                            <!-- --------- Llegada por  ------ -->
                            <div class="col-md-3 col-lg-3 col-xl-3 col-xxl-3 mt-3 class_llegada_por">
                              <div class="form-group">
                                 <label for="idllegada_por" class="form-label">
                                  <span class="badge bg-info m-r-4px cursor-pointer" onclick="reload_llegadapor_reserva();" data-bs-toggle="tooltip" title="Actualizar"><i class="las la-sync-alt"></i></span>
                                  Llegada Por <sup class="text-danger">*</sup>
                                  <span class="charge_idllegada_por"></span>
                                </label>
                                <select class="form-control" name="idllegada_por" id="idllegada_por" onchange="llegada_por()">
                                  <!-- lista de llegada por -->
                                </select>
                              </div>
                            </div>


                            <!-- --------- llegada empresa  ------ -->
                            <div class="col-md-3 col-lg-3 col-xl-3 col-xxl-3 mt-3 class_llegada_por">
                              <div class="form-group">
                                <label for="llegada_por_empresa" class="form-label">
                                  <span class="badge bg-info m-r-4px cursor-pointer" onclick="reload_llegada_por_empresa_reserva();" data-bs-toggle="tooltip" title="Actualizar"><i class="las la-sync-alt"></i></span>
                                  Empresa Llegada <sup class="text-danger">*</sup>
                                  <span class="charge_llegada_por_empresa"></span>
                                </label>
                                <select class="form-control llegada_por_empresas" name="llegada_por_empresa" id="llegada_por_empresa" >
                                  <!-- lista de llegada empresa -->
                                </select>
                              </div>
                            </div>

                            <!-- --------- Fecha llagada ------ -->
                            <div class="col-md-3 col-lg-3 col-xl-2 col-xxl-3 mt-3">
                              <div class="form-group">
                                <label for="llegada_fecha" class="form-label">Fecha Llegada</label>
                                <input type="date" class="form-control" name="llegada_fecha" id="llegada_fecha" onchange="fecha_a_partir_fecha_llegada()" />
                              </div>
                            </div>

                            <!-- --------- Hora llagada ------ -->
                            <div class="col-md-3 col-lg-3 col-xl-2 col-xxl-3 mt-3">
                              <div class="form-group">
                                <label for="llegada_hora" class="form-label">Hora Llegada</label>
                                <input type="time" class="form-control" name="llegada_hora" id="llegada_hora" />
                              </div>
                            </div>
                            <!-- --------- Fecha Salida ------ -->
                            <div class="col-md-3 col-lg-3 col-xl-2 col-xxl-3 mt-3">
                              <div class="form-group">
                                <label for="salida_fecha" class="form-label">Fecha Salida</label>
                                <input type="date" class="form-control" name="salida_fecha" id="salida_fecha" />
                              </div>
                            </div>

                            <!-- --------- Trabajador  ------ -->
                            <div class="col-md-3 col-lg-3 col-xl-3 col-xxl-3 mt-3">
                              <div class="form-group">
                                <label for="idasesorreserva" class="form-label">Asesor de Reserva <sup class="text-danger">*</sup></label>
                                <select class="form-control" name="idasesorreserva" id="idasesorreserva" >
                                  <!-- lista de ubigeo_distritos -->
                                </select>
                              </div>
                            </div>

                            <!-- ----------------- Nro Ref. o Referencia --------------- -->
                            <div class="col-md-3 col-lg-3 col-xl-3 col-xxl-3 mt-3 hidden">
                              <div class="form-group">
                            <!-- ----------------- Nro Ref. o Referencia --------------- -->
                                <input type="text" class="form-control" name="nro_referencia" id="nro_referencia" />
                              </div>
                            </div>

                            <!-- ----------------- observaciones --------------- -->
                            <div class="col-md-3 col-lg-3 col-xl-6 col-xxl-3 mt-3 hidden">
                              <div class="form-group">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <input type="text" class="form-control" name="observaciones" id="observaciones" />
                              </div>
                            </div>

                            <!-- ----------------- Reservar solo hotel --------------- -->
                            <div class="col-md-3 col-lg-3 col-xl-2 col-xxl-2 mt-4 hidden">
                              <div class="form-group">
                                <label for="reserva_solo_hotel" class="form-label">Reservar solo hotel <i class="bi bi-exclamation-circle" data-bs-toggle="tooltip" title="Para usar esta opcion el cliente debe tener algun saldo a FAVOR para poder utilizarlo como pago."></i></label>
                                <div class="toggle toggle-secondary reserva_solo_hotel" onclick="delay(function(){only_room()}, 100 );"> <span></span> </div>
                                <input type="hidden" class="form-control" name="reserva_solo_hotel" id="reserva_solo_hotel" value="NO">
                              </div>
                            </div>

                            <!-- Especificar Itinerario -->
                            <div class="col-md-3 col-lg-3 col-xl-2 col-xxl-2 mt-4 hidden">
                              <div class="form-group">
                                <label for="esp_itinerario" class="form-label">Especificar Itinerario <i class="bi bi-exclamation-circle" data-bs-toggle="tooltip" title="Para usar esta opcion el cliente debe tener algun saldo a FAVOR para poder utilizarlo como pago."></i></label>
                                <div class="toggle toggle-secondary esp_itinerario" onclick="delay(function(){esp_itinerario_valid()}, 100 );"> <span></span> </div>
                                <input type="hidden" class="form-control" name="esp_itinerario" id="esp_itinerario" value="NO">
                              </div>
                            </div>

                                </div>
                              </div>
                            </div>

                            <div class="col-12 col-xl-9 order-xl-1 reserva-tab-pane datos-itinerario reserva-section" data-reserva-step-panel="general" style="display: none !important;">

                              <div class="row g-3">
                                <div class="col-12 pl-0">
                                  <div class="reserva-section-title">
                                    <i class="bi bi-list-check"></i>
                                    <span>Detalle de itinerario</span>
                                  </div>
                                </div>
                              </div>

                              <div class="reserva-input-panel">
                                <div class="row g-2">

                                  <!-- Detalle - Especificar Itinerario -->
                                  <div class="col-12">
                                    <div class="form-label">
                                      <label for="incluye" class="form-label">Incluye <samp>(Resumen)</samp> </label>
                                      <div id="detalle_incluye">
                                      </div>
                                    </div>
                                  </div>

                                </div>
                              </div>
                            </div>

                            <div class="col-12 col-xl-9 order-xl-1 mt-3 div_datos_tours reserva-section reserva-tab-pane" data-reserva-step-panel="tours">
                              <div class="reserva-section-title">
                                <i class="fa-solid fa-location-dot"></i>
                                <span>Tours turisticos</span>
                              </div>
                              <div class="row g-3 align-items-end reserva-input-panel mb-3">
                                <div class="col-12 col-md-6 col-xl-4 position-relative">
                                  <div class="input-group">
                                    <button type="button" class="input-group-text buscar_x_code" onclick="agregarDetalleComprobante(null,'OK', false );" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Buscar por codigo del Tours."><i class='bx bx-search-alt'></i></button>
                                    <input type="text" name="search_tours" id="search_tours" class="form-control" onkeyup="mayus(this);" placeholder="Busca por codigo o nombre del tour.">
                                  </div>
                                  <ul id="searchResults" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>
                                </div>
                              </div>
                              <div class="row g-3">
                                <div class="col-12">
                                  <div class="table-responsive reserva-table-shell">
                                    <table class="table table-sm table-bordered table-hover align-middle text-nowrap mb-0" id="tabla-productos-seleccionados">
                                      <thead>
                                        <tr>
                                          <th scope="col" style="width: 80px;">Borrar</th>
                                          <th scope="col" style="width: 100px;">Tours</th>
                                          <th scope="col" style="width: 115px;">Vehiculo</th>
                                          <th scope="col" style="width: 115px;">Turno</th>
                                          <th scope="col" style="width: 80px;">Pax</th>
                                          <th scope="col" style="width: 130px;">Fecha</th>
                                          <th scope="col" style="width: 150px;">Observación</th>
                                          <th scope="col" style="width: 120px;">P/U</th>
                                          <th scope="col" style="width: 150px;">Precio</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <!-- filas aqui -->
                                      </tbody>
                                      <tfoot>
                                        <tr>
                                          <td colspan="8" class="text-end fw-bold">Total: S/</td>
                                          <td class="fw-bold"> <span class="subtotaltours">0.00</span> </td>

                                        </tr>
                                      </tfoot>

                                    </table>
                                  </div>

                                </div>

                                <div class="col-md-12 col-lg-12 col-xl-12 col-xxl-12 mt-3">
                                  <div class="form-group">
                                    <label for="detalle_ubicacion_r" class="form-label">Ubicación y referencia para el recojo del turista</label>
                                    <textarea class="form-control" name="detalle_ubicacion_r" id="detalle_ubicacion_r" rows="2" placeholder="Se encuentra en ...."></textarea>

                                  </div>
                                </div>
                              </div>

                            </div>

                            <div class="col-12 col-xl-9 order-xl-1 mt-3 div_datos_alojamiento reserva-section reserva-tab-pane" data-reserva-step-panel="hotel">
                              <div class="reserva-section-title">
                                <i class="fa-solid fa-hotel"></i>
                                <span>Alojamiento</span>
                              </div>
                              <div class="row g-3 align-items-end reserva-input-panel mb-3">
                                <div class="col-12 col-md-6">
                                  <label for="searchInput" class="form-label mb-0 me-2">Seleccionar Hotel</label>
                                  <select class="form-control" id="select_idhotel" onchange="seleccion_hotel();">
                                    <!-- lista de hotels -->
                                  </select>
                                </div>
                                <div class="col-12 col-md-4">
                                  <label for="select_habitacion" class="form-label mb-0 me-2">Asignar Habitacion</label>
                                  <select class="form-control" id="select_habitacion">
                                    <!-- lista de ubigeo_distritos -->
                                  </select>
                                </div>

                                <div class="col-12 col-md-2">
                                  <label for="searchInput" class="form-label mb-1">Agregar</label> <br>
                                  <a class="btn btn-icon btn-sm btn-success-light border-success product-btn" onclick="agregar_habitacion()" data-bs-toggle="tooltip" title="Agregar"><i class="fa-solid fa-plus"></i></a>
                                </div>
                              </div>

                              <div class="row g-3">
                                <div class="col-12">
                                  <div class="table-responsive reserva-table-shell">
                                    <table class="table table-sm table-bordered table-hover align-middle text-nowrap mb-0" id="tabla-habitaciones-seleccionadas">
                                      <thead>
                                        <tr>
                                          <th scope="col" style="width: 50px;">Borrar</th>
                                          <th scope="col" style="width: 150px;">Hotel</th>
                                          <th scope="col" style="width: 100px;">Pax</th>
                                          <th scope="col" style="width: 120px;">Cant Hab.</th>
                                          <th scope="col" style="width: 200px;">F. Llegada</th>
                                          <th scope="col" style="width: 200px;">F. Salida</th>
                                          <th scope="col" style="width: 150px;" >Check-in</th>
                                          <th scope="col" style="width: 120px;"># Noches</th>
                                          <th scope="col" style="width: 150px;">Precio</th>
                                          <th scope="col" style="width: 150px;">Adicional (+ -)</th>
                                          <th scope="col" style="width: 150px;">Observacion</th>
                                          <th scope="col" style="width: 150px;">Total</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        
                                      </tbody>
                                      <tfoot>
                                        <tr>
                                          <td colspan="11" class="text-end fw-bold">Total: S/</td>
                                          <td class="fw-bold"> <span class="total_hab" >S/0.00</span> </td>

                                        </tr>
                                      </tfoot>
                                    </table>
                                  </div>
                                </div>
                              </div>

                            </div>

                            <div class="col-12 col-xl-9 order-xl-1 mt-4 div_datos_compravuelo reserva-section reserva-tab-pane" data-reserva-step-panel="vuelo">
                              <div class="reserva-section-title">
                                <i class="fa-brands fa-avianex"></i>
                                <span>Compra de vuelo</span>
                              </div>
                              <div class="row g-3">
                                <!-- Imagen -->
                                <div class="col-md-3 col-lg-3 col-xl-3 col-xxl-3 mt-3 content-metodo-pago-1">
                                  <span class="fw-semibold">Informacion Ticket</span>
                                  <div class="row g-3">
                                    <!-- Boucher -->
                                    <div class="col-sm-12 col-lg-12 col-xl-12 pt-2">
                                      <div class="form-group">
                                        <input type="file" class="multiple-filepond " multiple name="brochure" id="brochure_1" data-allow-reorder="true" data-max-file-size="3MB" accept="image/*, application/pdf">
                                      </div>
                                    </div>
                                  </div>
                                </div>
                                <div class="col-md-2 col-lg-2 col-xl-2 col-xxl-2 mt-3">
                                  <div class="form-group">
                                    <label for="vuelo_ticket" class="form-label">Nro Ticket</label>
                                    <input type="text" class="form-control" name="vuelo_ticket" id="vuelo_ticket" />
                                  </div>
                                </div>
                                <div class="col-md-3 col-lg-3 col-xl-3 col-xxl-3 mt-3">
                                  <div class="form-group">
                                    <label for="monto_compra_vuelo" class="form-label">Monto Compra S/</label>
                                    <input type="number" class="form-control" name="monto_compra_vuelo" id="monto_compra_vuelo" onkeyup="total_general();" />
                                  </div>
                                </div>
                                <div class="col-md-4 col-lg-4 col-xl-4 col-xxl-4 mt-3">
                                  <div class="form-group">
                                    <label for="obs_vuelo" class="form-label">Observaciones de Vuelo</label>
                                    <textarea class="form-control" name="obs_vuelo" id="obs_vuelo" rows="1" placeholder="Describe las obs ...."></textarea>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="col-12 order-4 order-xl-3">
                              <div class="reserva-action-bar">
                                <input type="hidden" id="total_general_i" name="total_general_i">
                                <div class="reserva-action-buttons ms-auto">
                                  <button type="button" class="btn btn-danger btn-cancelar" onclick="show_hide_form(1); limpiar_form_reserva();" style="display: none;">
                                    <i class="las la-times fs-lg"></i> Cancelar
                                  </button>
                                  <button type="button" class="btn-modal-effect btn btn-success label-btn btn-guardar" style="display: none;">
                                    <i class="ri-save-2-line label-btn-icon me-2"></i> Guardar
                                  </button>
                                </div>
                              </div>
                            </div>
                            </div>

                          </div>
                          <div class="row g-3" id="cargando-2-fomulario" style="display: none;">
                            <div class="col-lg-12 text-center">
                              <div class="spinner-border me-4" style="width: 3rem; height: 3rem;" role="status"></div>
                              <h4 class="bx-flashing">Cargando...</h4>
                            </div>
                          </div>
                          <!-- Chargue -->
                          <div class="p-l-25px col-lg-12" id="barra_progress_tours_div" style="display: none;">
                            <div class="progress progress-lg custom-progress-3" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                              <div id="barra_progress_tours" class="progress-bar" style="width: 0%">
                                <div class="progress-bar-value">0%</div>
                              </div>
                            </div>
                          </div>


                          <!-- Submit -->
                          <button type="submit" style="display: none;" id="submit-form-reserva">Submit</button>

                        </form>
                      </div>

                      <!--DETALLE RESERVA-->
                      <div class="detalle_reserva_ver" style="display: none;">
                          <!--DETALLE RESERVA-->
                      </div>


                    </div>
                    <div class="card-footer border-top-0">
                      <button type="button" class="btn btn-danger btn-cancelar d-none" onclick="show_hide_form(1); limpiar_form_reserva();" style="display: none;"><i class="las la-times fs-lg"></i> Cancelar</button>
                      <button class="btn-modal-effect btn btn-success label-btn btn-guardar m-r-10px d-none" style="display: none;"> <i class="ri-save-2-line label-btn-icon me-2"></i> Guardar </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End::row-1 -->


            <!-- MODAL - VER DETALLE -->
            <div class="modal fade modal-effect reserva-modal" id="modal-ver-detalle-producto" tabindex="-1" aria-labelledby="modal-ver-detalle-productoLabel" aria-hidden="true">
              <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="modal-ver-detalle-productoLabel1"><i class="ri-route-line text-primary me-1"></i> Detalle de tour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body">

                    <div class="row p-3">
                      <div class="col-xxl-12 col-xl-12 detalle_tours">

                      </div>
                    </div>

                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal"><i class="las la-times"></i> Cerrar</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- End::Modal-VerDetalles -->

            <!-- MODAL - TURNO -->
            <div class="modal fade modal-effect reserva-modal" id="modal-agregar_turno" role="dialog" tabindex="-1" aria-labelledby="modal-agregar_turnoLabel">
              <div class="modal-dialog modal-md modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header">
                    <h6 class="modal-title fw-semibold" id="modal-agregar_turnoLabel1"><i class="ri-time-line text-primary me-1"></i> Registrar nuevo turno</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body">
                    <form name="formulario-turno" id="formulario-turno" method="POST" class="row needs-validation" novalidate>
                      <div class="row gy-2" id="cargando-1-fomulario">
                        <input type="hidden" name="idtours_turno" id="idtours_turno">

                        <div class="col-md-6">
                          <div class="form-label">
                            <label for="nombre_turno" class="form-label">Nombre(*)</label>
                            <input type="text" class="form-control" name="nombre_turno" id="nombre_turno" onkeyup="mayus(this);" />
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="descr_turno" class="form-label">Descripcion(*)</label>
                            <input type="text" class="form-control" name="descr_turno" id="descr_turno" onkeyup="mayus(this);" />
                          </div>
                        </div>
                      </div>
                      <div class="row g-3" id="cargando-2-formulario-turno" style="display: none;">
                        <div class="col-lg-12 text-center">
                          <div class="spinner-border me-4" style="width: 3rem; height: 3rem;" role="status"></div>
                          <h4 class="bx-flashing">Cargando...</h4>
                        </div>
                      </div>
                      <button type="submit" style="display: none;" id="submit-form_turno">Submit</button>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" onclick="limpiar_form_turno();"><i class="las la-times fs-lg"></i> Cerrar</button>
                    <button type="button" class="btn btn-primary" id="guardar_registro_turno"><i class="bx bx-save bx-tada fs-lg"></i> Guardar</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- End::Modal-registrar-unidad-medida -->

            <!-- MODAL - AMORTIZAR RESERVA -->
            <div class="modal fade reserva-modal" id="modal-amortizar-reserva" tabindex="-1" aria-labelledby="label-amortizar-reserva" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">

                  <div class="modal-header">
                    <h6 class="modal-title fw-semibold" id="label-amortizar-reserva">
                      <i class="ri-bank-card-line text-primary me-1"></i> Amortizar reserva | <span id="cod-reserva">R2025-000375</span>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>

                  <div class="modal-body px-4">
                    <!-- Alerta estado de caja -->
                    <div id="alerta-caja" class="alert alert-danger d-none" role="alert">
                      Debe corregir el estado de la caja para realizar movimientos de dinero.
                    </div>

                    <form name="form-amortizar-reserva" id="form-amortizar-reserva" method="POST" class="needs-validation" novalidate>
                      <input type="hidden" id="idventa" name="idventa">
                      <input type="hidden" id="idreserva_amortizar" name="idreserva_amortizar">
                      <input type="hidden" id="idrdocumento_pago" name="idrdocumento_pago">
                      <input type="hidden" id="f_impuesto" name="f_impuesto" value="0">
                      <input type="hidden" id="f_venta_subtotal" name="f_venta_subtotal">
                      <input type="hidden" id="f_tipo_gravada" name="f_tipo_gravada" value="SUBTOTAL">
                      <input type="hidden" id="f_venta_descuento" name="f_venta_descuento" value="0">
                      <input type="hidden" id="f_venta_igv" name="f_venta_igv"  value="0">

                      <div class="row g-3">

                        <!--  TIPO COMPROBANTE  -->
                        <div class="col-md-12 col-lg-8 col-xl-8 col-xxl-8">
                          <div class="mb-sm-0 mb-2">
                            <p class="fs-14 mb-2 fw-semibold">Tipo de comprobante </p>
                            <div class="mb-0 authentication-btn-group">
                              <input type="hidden" id="f_tipo_comprobante_hidden" value="12">
                              <input type="hidden" name="f_idsunat_c01" id="f_idsunat_c01" value="12">
                              <div class="btn-group" role="group" aria-label="Basic radio toggle button group">

                                <input type="radio" class="btn-check" name="f_tipo_comprobante" id="f_tipo_comprobante12" value="12" onchange="ver_series_comprobante('#f_tipo_comprobante12'); es_valido_cliente();">
                                <label class="btn btn-sm btn-outline-primary btn-tiket" for="f_tipo_comprobante12"><i class='bx bx-file-blank me-1 align-middle d-inline-block'></i> Ticket</label>

                                <input type="radio" class="btn-check" name="f_tipo_comprobante" id="f_tipo_comprobante03" value="03" onchange="ver_series_comprobante('#f_tipo_comprobante03'); es_valido_cliente();">
                                <label class="btn btn-sm btn-outline-primary btn-boleta" for="f_tipo_comprobante03"><i class="ri-article-line me-1 align-middle d-inline-block"></i>Boleta</label>

                                <input type="radio" class="btn-check" name="f_tipo_comprobante" id="f_tipo_comprobante01" value="01" onchange="ver_series_comprobante('#f_tipo_comprobante01'); es_valido_cliente();">
                                <label class="btn btn-sm btn-outline-primary" for="f_tipo_comprobante01"><i class="ri-article-line me-1 align-middle d-inline-block"></i> Factura</label>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-md-12 col-lg-4 col-xl-4 col-xxl-4">
                          <div class="form-group">
                            <label for="f_serie_comprobante" class="form-label">Serie <i class="bi bi-exclamation-circle" data-bs-toggle="tooltip" title="Si la serie esta vacia no podra emitir el comprobante seleccionado; solicite acceso."></i> <span class="f_charge_serie_comprobante"></span></label>
                            <select class="form-control" name="f_serie_comprobante" id="f_serie_comprobante"></select>
                          </div>
                        </div>

                        <!--  CLIENTE  -->
                        <div class="col-md-12 col-lg-12 col-xl-12 col-xxl-12 div_idpersona_cliente">
                          <div class="form-group">
                            <label for="p_idpersona_cliente" class="form-label">                                  
                              <!-- <span class="badge bg-warning m-r-4px cursor-pointer" onclick="modal_add_trabajador();" data-bs-toggle="tooltip" title="Actualizar Datos"><i class="bi bi-pencil"></i></span> -->
                              <!-- <span class="badge bg-success m-r-4px cursor-pointer" onclick=" modal_add_trabajador(); limpiar_proveedor();" data-bs-toggle="tooltip" title="Agregar"><i class="las la-plus"></i></span> -->
                              <span class="reserva-inline-actions"><button type="button" class="badge bg-success border-0 m-r-4px cursor-pointer js-agregar-nuevo-cliente" data-cliente-tipo="ClientePago" title="Agregar"><i class="las la-plus"></i></button>
                              <span class="badge bg-info m-r-4px cursor-pointer" onclick="reload_p_idpersona_cliente();" data-bs-toggle="tooltip" title="Actualizar"><i class="las la-sync-alt"></i></span></span>
                              Cliente
                              <span class="charge_p_idpersona_cliente"></span>
                            </label>
                            <select class="form-control" name="p_idpersona_cliente" id="p_idpersona_cliente" onchange="es_valido_cliente();"></select>
                          </div>
                        </div>

                        <div class="mb-1 col-md-3 col-lg-6 col-xl-6 col-xxl-4 mt-3">
                          <div class="form-group">
                            <label for="f_metodo_pago_1" class="form-label">Metodo de pago:  </label>
                            <select name="f_metodo_pago_1" id="f_metodo_pago_1" class="form-select" required>                                       
                            </select>
                          </div>                                         
                        </div>

                        <!-- Total a pagar -->
                        <div class="col-12 col-md-4">
                          <label class="form-label">TOTAL A PAGAR</label>
                          <div class="input-group">
                            <span class="input-group-text">s/</span>
                            <input type="text" class="form-control text-end fw-semibold" id="total_amortizar" name="total_amortizar"  readonly>
                          </div>
                        </div>

                        <!-- Monto a pagar -->
                        <div class="col-12 col-md-4">
                          <label class="form-label">MONTO A PAGAR <span class="text-danger">*</span></label>
                          <div class="input-group">
                            <span class="input-group-text">s/</span>
                            <input type="number" step="0.01" min="0" class="form-control text-end" id="monto_amortizar" name="monto_amortizar" required onkeyup="calcular_saldo_restante();" onchange="calcular_saldo_restante();" >
                          </div>
                          <div class="invalid-feedback">Ingrese un monto valido.</div>


                        </div>

                        <!-- Saldo restante -->
                        <div class="col-12 col-md-4">
                          <label class="form-label">SALDO RESTANTE</label>
                          <div class="input-group">
                            <span class="input-group-text text-danger">s/</span>
                            <input type="text" class="form-control text-end text-danger fw-semibold" id="saldo_amortizar" name="saldo_amortizar" value="0" readonly>
                          </div>
                        </div>
                        <!-- Observación -->
                        <div class="col-12 col-md-8">
                          <label class="form-label">Observacion</label>
                          <textarea class="form-control" name="observacion_amortizar" id="observacion_amortizar" rows="1" placeholder="Observacion...."></textarea>
                        </div>


                        <!-- Detalle -->
                        <div class="col-12">
                          <label class="form-label">Detalle Comprobante</label>
                          <textarea class="form-control" name="detalle_comprobante_amortizar" id="detalle_comprobante_amortizar" rows="2" placeholder="Detalle...."></textarea>
                        </div>


                        <!-- Imagen -->
                        <div class="col-md-6 mt-3">
                          <span class=""> <b>Boucher</b> </span>
                          <div class="row g-3">
                            <!-- Boucher -->
                            <div class="col-sm-12 col-lg-12 col-xl-12 pt-2">
                              <div class="form-group">
                                <input type="file" class="multiple-filepond_pago" multiple name="f_mp_comprobante" id="brochure_2" data-allow-reorder="true" data-max-file-size="3MB" accept="image/*, application/pdf">
                              </div>
                            </div>
                          </div>
                        </div>
                        
                      </div>
                      <!-- Submit -->
                      <button type="submit" style="display: none;" id="submit-form-reserva_pago">Submit</button>
                    </form>
                  </div>

                  <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" onclick='limpiar_nuevo_pago();' >Cancelar</button>
                    <button type="button" class="btn btn-sm btn-success label-btn" id="guardar_registro_nuevo_pago_reserva"> <i class="bx bx-check"></i> AMORTIZAR</button>
                  </div>

                </div>
              </div>
            </div>

            <!-- MODAL - ASOCIAR COMPROBANTE -->
            <div class="modal fade reserva-modal" id="modal-asociar-comprobante-reserva" tabindex="-1" aria-labelledby="label-asociar-comprobante-reserva" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header">
                    <h6 class="modal-title fw-semibold" id="label-asociar-comprobante-reserva">
                      <i class="ri-link-m text-primary me-1"></i> Asociar comprobante | <span id="cod-reserva-asociar">Reserva</span>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body px-4">
                    <input type="hidden" id="asociar_reserva_id">
                    <input type="hidden" id="asociar_reserva_saldo">
                    <input type="hidden" id="asociar_idrdocumento">

                    <div class="row g-3">
                      <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                          <label for="select_comprobante_asociar" class="form-label mb-0">Comprobante suelto <span class="text-danger">*</span></label>
                          <div class="form-check form-check-sm mb-0">
                            <input class="form-check-input" type="checkbox" value="1" id="asociar_mostrar_todos_documentos">
                            <label class="form-check-label fs-12" for="asociar_mostrar_todos_documentos">Mostrar todos</label>
                          </div>
                        </div>
                        <select class="form-control" id="select_comprobante_asociar"></select>
                        <small class="text-muted" id="asociar_comprobante_help">Solo aparecen documentos activos del cliente que estan en rdocumento y aun no estan asociados a una reserva.</small>
                      </div>

                      <div class="col-md-4">
                        <label for="monto_comprobante_asociar" class="form-label">Monto a aplicar <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <span class="input-group-text">s/</span>
                          <input type="number" step="0.01" min="0" class="form-control text-end" id="monto_comprobante_asociar">
                        </div>
                      </div>
                      <div class="col-md-8">
                        <label class="form-label">Comprobante seleccionado</label>
                        <div class="form-control bg-light" id="texto_comprobante_asociar">-</div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-guardar-asociar-comprobante">
                      <i class="ri-link-m me-1"></i> Asociar
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- MODAL - AGREGAR CLIENTE - charge 3,4 -->
            <div class="modal fade modal-effect reserva-modal" id="modal-agregar-nuevo-cliente" tabindex="-1" aria-labelledby="Modal-agregar-nuevo-clienteLabel" aria-hidden="true">
              <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0">
                  <div class="modal-header border-bottom">
                    <div>
                      <h5 class="modal-title fw-semibold title-modal-nuevo-cliente" id="Modal-agregar-nuevo-clienteLabel1">Agregar Cliente</h5>
                      <p class="text-muted fs-12 mb-0">Registra la informacion del cliente igual que en el modulo Clientes.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>

                  <form name="form-agregar-nuevo-cliente" id="form-agregar-nuevo-cliente" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body p-0">
                      <div class="row g-0" id="cargando-3-fomulario">
                        <input type="hidden" name="cli_idpersona" id="cli_idpersona" />
                        <input type="hidden" name="cli_tipo_persona_sunat" id="cli_tipo_persona_sunat" value="NATURAL" />
                        <input type="hidden" name="cli_idtipo_persona" id="cli_idtipo_persona" value="3" />
                        <input type="hidden" name="cli_centro_poblado" id="cli_centro_poblado" value="1" />

                        <div class="col-xxl-3 col-xl-4 border-end">
                          <ul class="nav flex-column nav-pills add-product-details-nav p-3" role="tablist">
                            <li class="nav-item m-1" role="presentation">
                              <a class="nav-link d-inline-flex w-100 mb-3 gap-2 align-items-center active" id="reserva-cliente-general-tab" data-bs-toggle="tab" data-bs-target="#reserva-cliente-general-pane" href="#reserva-cliente-general-pane" aria-selected="true" role="tab">
                                <span class="avatar avatar-lg border avatar-rounded"><span class="avatar avatar-md avatar-rounded"><i class="ri-user-line fs-4"></i></span></span>
                                <div>
                                  <p class="mb-1 fs-15 fw-semibold">Informacion General</p>
                                  <span class="text-muted fs-13">Datos basicos del cliente</span>
                                </div>
                              </a>
                            </li>
                            <li class="nav-item m-1" role="presentation">
                              <a class="nav-link d-inline-flex w-100 gap-2 mb-3 align-items-center" id="reserva-cliente-contacto-tab" data-bs-toggle="tab" data-bs-target="#reserva-cliente-contacto-pane" href="#reserva-cliente-contacto-pane" aria-selected="false" tabindex="-1" role="tab">
                                <span class="avatar avatar-lg border avatar-rounded"><span class="avatar avatar-md avatar-rounded"><i class="ri-map-pin-line fs-4"></i></span></span>
                                <div>
                                  <p class="mb-1 fs-15 fw-semibold">Contacto y Direccion</p>
                                  <span class="text-muted fs-13">Medios de contacto y ubicacion</span>
                                </div>
                              </a>
                            </li>
                          </ul>
                          <div class="p-3 pt-0">
                            <div class="px-3 py-3 bg-primary-transparent rounded text-center">
                              <i class="ri-information-line fs-2 text-primary"></i>
                              <p class="fs-12 text-muted mb-0 mt-2">Los campos con asterisco son obligatorios.</p>
                            </div>
                          </div>
                        </div>

                        <div class="col-xxl-9 col-xl-8">
                          <div class="p-3 border-bottom border-block-end-dashed tab-content">
                            <div class="tab-pane show active p-0 border-0 custom-products" id="reserva-cliente-general-pane" role="tabpanel" aria-labelledby="reserva-cliente-general-tab" tabindex="0">
                              <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                <div class="fw-semibold d-block fs-15">Informacion General :</div>
                              </div>
                              <div class="row gy-3">
                                <div class="col-xl-4 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_tipo_persona_sunat_select">Tipo Persona</label>
                                    <select class="form-control" id="cli_tipo_persona_sunat_select">
                                      <option value="NATURAL">NATURAL</option>
                                      <option value="JURIDICA">JURIDICA</option>
                                    </select>
                                  </div>
                                </div>
                                <div class="col-xl-4 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_tipo_documento">Tipo de documento</label>
                                    <select name="cli_tipo_documento" id="cli_tipo_documento" class="form-control" required>
                                      <option value="1">DNI</option>
                                      <option value="6">RUC</option>
                                      <option value="7">EXTRANJERO</option>
                                    </select>
                                  </div>
                                </div>
                                <div class="col-xl-4 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label d-block" for="cli_numero_documento">Nro de documento <sup class="text-danger">*</sup></label>
                                    <div class="input-group">
                                      <input type="number" class="form-control" name="cli_numero_documento" id="cli_numero_documento" placeholder="">
                                      <button class="btn btn-primary" type="button" onclick="buscar_sunat_reniec('#form-agregar-nuevo-cliente', '_t1', '#cli_tipo_documento', '#cli_numero_documento', '#cli_nombre_razonsocial', '#cli_apellidos_nombrecomercial', '#cli_nombre_persona_natural', '#cli_apellido_paterno_persona_natural', '#cli_apellido_materno_persona_natural', '#cli_direccion', '#cli_distrito', '#cli_ubigeo', '#cli_tipo_persona_sunat' );">
                                        <i class="bx bx-search-alt" id="search_t1"></i>
                                        <div class="spinner-border spinner-border-sm" role="status" id="charge_t1" style="display: none;"></div>
                                      </button>
                                    </div>
                                  </div>
                                </div>

                                <div class="col-xl-7 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label label-nom-raz" for="cli_nombre_razonsocial">Descripcion <sup class="text-danger">*</sup></label>
                                    <input type="text" name="cli_nombre_razonsocial" id="cli_nombre_razonsocial" class="form-control" placeholder="Ej. Juan" onkeyup="mayus(this);" />
                                  </div>
                                </div>
                                <div class="col-xl-5 col-md-6 div_nombre_comercial">
                                  <div class="form-group">
                                    <label class="form-label label-ape-come" for="cli_apellidos_nombrecomercial">Nombre Comercial</label>
                                    <input type="text" name="cli_apellidos_nombrecomercial" id="cli_apellidos_nombrecomercial" class="form-control" placeholder="Ej. Apellidos o nombre comercial" onkeyup="mayus(this);" />
                                  </div>
                                </div>
                                <div class="col-xl-5 col-md-6 div_nombre_persona_natural">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_nombre_persona_natural">Nombre (Persona Natural)</label>
                                    <input type="text" name="cli_nombre_persona_natural" id="cli_nombre_persona_natural" class="form-control" placeholder="Ej. Juan" onkeyup="mayus(this);" />
                                  </div>
                                </div>
                                <div class="col-xl-4 col-md-6 div_apellido_paterno_persona_natural">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_apellido_paterno_persona_natural">Apellido Paterno</label>
                                    <input type="text" name="cli_apellido_paterno_persona_natural" id="cli_apellido_paterno_persona_natural" class="form-control" placeholder="Ej. Perez" onkeyup="mayus(this);" />
                                  </div>
                                </div>
                                <div class="col-xl-4 col-md-6 div_apellido_materno_persona_natural">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_apellido_materno_persona_natural">Apellido Materno</label>
                                    <input type="text" name="cli_apellido_materno_persona_natural" id="cli_apellido_materno_persona_natural" class="form-control" placeholder="Ej. Perez" onkeyup="mayus(this);" />
                                  </div>
                                </div>
                                <div class="col-xl-4 col-md-6 div_sexo">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_sexo">Sexo</label>
                                    <select name="cli_sexo" id="cli_sexo" class="form-control">
                                      <option value="M">Masculino</option>
                                      <option value="F">Femenino</option>
                                    </select>
                                  </div>
                                </div>
                                <div class="col-xl-4 col-md-6 div_fecha_nacimiento">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_fecha_nacimiento">Fecha de Nacimiento</label>
                                    <input type="date" name="cli_fecha_nacimiento" id="cli_fecha_nacimiento" class="form-control" />
                                  </div>
                                </div>
                                <div class="col-xl-4 col-md-6 div_nacionalidad">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_nacionalidad">Nacionalidad</label>
                                    <select name="cli_nacionalidad" id="cli_nacionalidad" class="form-control">
                                      <option value="PERUANO">PERUANO</option>
                                      <option value="EXTRANJERO">EXTRANJERO</option>
                                    </select>
                                  </div>
                                </div>
                                <div class="col-xl-4 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_estado_civil">Estado Civil</label>
                                    <select name="cli_estado_civil" id="cli_estado_civil" class="form-control">
                                      <option value="SOLTERO">Soltero/a</option>
                                      <option value="CASADO">Casado/a</option>
                                      <option value="DIVORCIADO">Divorciado/a</option>
                                      <option value="VIUDO">Viudo/a</option>
                                    </select>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div class="tab-pane p-0 border-0" id="reserva-cliente-contacto-pane" role="tabpanel" aria-labelledby="reserva-cliente-contacto-tab" tabindex="0">
                              <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                <div class="fw-semibold d-block fs-15">Contacto y Direccion:</div>
                              </div>
                              <div class="row gy-3">
                                <div class="col-xl-5 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_celular">Celular</label>
                                    <input type="tel" name="cli_celular" id="cli_celular" class="form-control" onkeypress="return soloNumeros(event)" placeholder="Ej. 987654321" />
                                  </div>
                                </div>
                                <div class="col-xl-7 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_correo">Correo</label>
                                    <input type="email" name="cli_correo" id="cli_correo" class="form-control" placeholder="correo@ejemplo.com" />
                                  </div>
                                </div>
                                <div class="col-xl-12">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_direccion">Direccion</label>
                                    <textarea name="cli_direccion" id="cli_direccion" class="form-control" rows="2" placeholder="Calle, numero, urbanizacion"></textarea>
                                  </div>
                                </div>
                                <div class="col-xl-12">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_direccion_referencia">Referencia de Direccion</label>
                                    <textarea name="cli_direccion_referencia" id="cli_direccion_referencia" class="form-control" rows="2" placeholder="Cerca de..."></textarea>
                                  </div>
                                </div>
                                <div class="col-xl-4 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_distrito">Distrito</label>
                                    <select name="cli_distrito" id="cli_distrito" class="form-control" style="width: 100%;"></select>
                                  </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_provincia">Provincia <span class="chargue-dep"></span></label>
                                    <input type="text" name="cli_provincia" id="cli_provincia" class="form-control" readonly />
                                  </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_departamento">Departamento <span class="chargue-pro"></span></label>
                                    <input type="text" name="cli_departamento" id="cli_departamento" class="form-control" readonly />
                                  </div>
                                </div>
                                <div class="col-xl-2 col-md-6">
                                  <div class="form-group">
                                    <label class="form-label" for="cli_ubigeo">Codigo Ubigeo <span class="chargue-ubi"></span></label>
                                    <input type="text" name="cli_ubigeo" id="cli_ubigeo" class="form-control" readonly />
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="row text-center" id="cargando-4-fomulario" style="display: none;">
                            <div class="col-lg-12 py-4">
                              <div class="spinner-border me-4" style="width: 3rem; height: 3rem;" role="status"></div>
                              <h4 class="bx-flashing">Cargando...</h4>
                            </div>
                          </div>

                          <div class="p-l-25px col-lg-12 px-3 pb-3" id="barra_progress_nuevo_cliente_div" style="display: none;">
                            <div class="progress progress-lg custom-progress-3" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                              <div id="barra_progress_nuevo_cliente" class="progress-bar" style="width: 0%">
                                <div class="progress-bar-value">0%</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <button type="submit" style="display: none;" id="submit-form-nuevo-cliente">Submit</button>
                    </div>

                    <div class="modal-footer border-top py-2">
                      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                      <button type="button" class="btn btn-primary label-btn" id="guardar_registro_nuevo_cliente"><i class="ti ti-device-floppy label-btn-icon me-2"></i>Guardar</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <!-- End::Modal -->



          </div>
        </div>
        <!-- End::app-content -->

      <div class="modal fade" id="modal-impresion-reserva" tabindex="-1" aria-labelledby="modal-impresion-reserva-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header py-2">
              <div>
                <h6 class="modal-title" id="modal-impresion-reserva-label">Impresion de comprobante</h6>
                <div class="text-muted fs-12" id="modal-impresion-reserva-subtitle">-</div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <ul class="nav nav-tabs tab-style-2 mb-3" id="reserva-print-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="reserva-print-a4-tab" data-bs-toggle="tab" data-bs-target="#reserva-print-a4-pane" type="button" role="tab" aria-controls="reserva-print-a4-pane" aria-selected="true">
                    <i class="ri-file-text-line me-1"></i>A4
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="reserva-print-ticket-tab" data-bs-toggle="tab" data-bs-target="#reserva-print-ticket-pane" type="button" role="tab" aria-controls="reserva-print-ticket-pane" aria-selected="false">
                    <i class="ri-receipt-line me-1"></i>Ticket termico
                  </button>
                </li>
              </ul>
              <div class="tab-content">
                <div class="tab-pane fade show active" id="reserva-print-a4-pane" role="tabpanel" aria-labelledby="reserva-print-a4-tab" tabindex="0">
                  <div class="d-flex justify-content-end gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-primary js-imprimir-formato-reserva" data-formato="a4"><i class="ri-printer-line me-1"></i>Imprimir</button>
                    <a class="btn btn-sm btn-light js-abrir-formato-reserva" data-formato="a4" href="#" target="_blank" rel="noopener"><i class="ri-external-link-line me-1"></i>Abrir</a>
                  </div>
                  <iframe class="facturacion-print-frame" id="reserva-print-a4-frame" title="Formato A4"></iframe>
                </div>
                <div class="tab-pane fade" id="reserva-print-ticket-pane" role="tabpanel" aria-labelledby="reserva-print-ticket-tab" tabindex="0">
                  <div class="d-flex justify-content-end gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-primary js-imprimir-formato-reserva" data-formato="ticket"><i class="ri-printer-line me-1"></i>Imprimir</button>
                    <a class="btn btn-sm btn-light js-abrir-formato-reserva" data-formato="ticket" href="#" target="_blank" rel="noopener"><i class="ri-external-link-line me-1"></i>Abrir</a>
                  </div>
                  <iframe class="facturacion-print-frame" id="reserva-print-ticket-frame" title="Formato ticket termico"></iframe>
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

    <!-- Quill Editor JS -->
    <script src="{{ asset('ynex_admin/libs/quill/quill.min.js') }}"></script>

    <!-- Filepond JS -->
    <script src="{{ asset('ynex_admin/libs/filepond/filepond.min.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond/locale/es-es.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond-plugin-image-edit/filepond-plugin-image-edit.min.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond-plugin-image-crop/filepond-plugin-image-crop.min.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond-plugin-image-resize/filepond-plugin-image-resize.min.js') }}"></script>
    <script src="{{ asset('ynex_admin/libs/filepond-plugin-image-transform/filepond-plugin-image-transform.min.js') }}"></script>

    <!-- Dropzone JS -->
    <script src="{{ asset('ynex_admin/libs/dropzone/dropzone-min.js') }}"></script>
    <!-- Gallery JS -->
    <script src="{{ asset('ynex_admin/libs/glightbox/js/glightbox.min.js') }}"></script>

    <!-- Select2 Cdn -->
    <script src="{{ asset('assets/js/reserva.js') }}?v={{ filemtime(public_path('assets/js/reserva.js')) }}"></script>
    <script src="{{ asset('assets/js/reserva_pago.js') }}?v={{ filemtime(public_path('assets/js/reserva_pago.js')) }}"></script>

    <script>
      $(function() {

        $('[data-bs-toggle="tooltip"]').tooltip();
      });
    </script>


  </body>



  </html>
