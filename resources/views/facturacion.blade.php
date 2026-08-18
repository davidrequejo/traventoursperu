<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Facturacion'])
  <link rel="stylesheet" href="{{ asset('ynex_admin/libs/flatpickr/plugins/monthSelect/style.css') }}">

  <style>
    #tabla-facturacion_filter {
      width: calc(100% - 10px) !important;
      display: flex !important;
      justify-content: space-between !important;
    }

    #tabla-facturacion_filter label {
      width: 100% !important;
    }

    #tabla-facturacion_filter label input {
      width: 100% !important;
    }

    #tabla-productos-facturacion-modal_filter {
      width: calc(100% - 10px) !important;
      display: flex !important;
      justify-content: space-between !important;
    }

    #tabla-productos-facturacion-modal_filter label,
    #tabla-productos-facturacion-modal_filter label input {
      width: 100% !important;
    }

    .flatpickr-current-month .facturacion-flatpickr-year-wrapper {
      width: 5.5rem;
    }

    .facturacion-flatpickr-year-select {
      width: 100%;
      border: 0;
      background: transparent;
      font-size: 14px;
      font-weight: 600;
      color: inherit;
      outline: 0;
      padding: 0 4px;
      cursor: pointer;
    }

    .facturacion-print-frame {
      width: 100%;
      height: 70vh;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      background: #f8f9fa;
    }
  </style>
</head>

<body id="body-facturacion">
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb card-header-principal">
          <div>
            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Facturacion</p>
            <nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Facturacion</li>
              </ol>
            </nav>
          </div>

          <div class="btn-list mt-md-0 mt-2">
            <div class="form-check form-switch d-inline-flex align-items-center mb-0 me-2" id="div-filtro-principal-facturacion">
              <input class="form-check-input" type="checkbox" role="switch" id="incluir-eliminados-facturacion">
              <label class="form-check-label fs-12 text-muted ms-2" for="incluir-eliminados-facturacion">Papelera</label>
            </div>
            @permiso('facturacion', 'crear')
            <button type="button" class="btn btn-primary label-btn m-r-10px" id="btn-nueva-factura">
              <i class="ri-file-add-line label-btn-icon me-2"></i>Nuevo comprobante
            </button>
            @endpermiso
            <button type="button" class="btn btn-light label-btn m-r-10px" id="btn-regresar-facturacion" style="display:none;">
              <i class="bi bi-arrow-left label-btn-icon me-2"></i>Regresar
            </button>
            @permiso('facturacion', 'crear')
            <button type="submit" class="btn btn-primary label-btn m-r-10px btn-guardar-factura" id="btn-guardar-factura-superior" form="form-factura-facturacion" style="display:none;">
              <i class="ti ti-device-floppy label-btn-icon me-2"></i>Guardar
            </button>
            @endpermiso
          </div>
        </div>

        <div id="div-tabla">
          <div class="card custom-card">
            <div class="card-body">
              <div class="row g-2 mb-3">
                <div class="col-12 col-xl-3">
                  <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="filtro_facturacion_tipo_fecha" id="filtro_facturacion_tipo_fecha_rango" value="fecha" checked>
                      <label class="form-check-label" for="filtro_facturacion_tipo_fecha_rango">Fecha</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="filtro_facturacion_tipo_fecha" id="filtro_facturacion_tipo_fecha_mes" value="mes">
                      <label class="form-check-label" for="filtro_facturacion_tipo_fecha_mes">Mes</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="filtro_facturacion_tipo_fecha" id="filtro_facturacion_tipo_fecha_anio" value="anio">
                      <label class="form-check-label" for="filtro_facturacion_tipo_fecha_anio">Año</label>
                    </div>
                  </div>
                  <div class="d-flex gap-2">
                    <div class="flex-grow-1 min-w-0">
                      <div id="filtro_facturacion_fecha_rango">
                        <input type="text" class="form-control form-control-sm" id="filtro_facturacion_fecha_rango_input" aria-label="Rango de fechas" readonly>
                        <input type="hidden" id="filtro_facturacion_fecha_inicio">
                        <input type="hidden" id="filtro_facturacion_fecha_fin">
                      </div>
                      <input type="text" class="form-control form-control-sm d-none" id="filtro_facturacion_fecha_mes" aria-label="Mes" readonly>
                      <select class="form-control form-control-sm d-none" id="filtro_facturacion_fecha_anio" aria-label="Año"></select>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" id="btn_limpiar_fechas_facturacion" title="Quitar filtro de fecha">
                      <i class="ri-close-line"></i>
                    </button>
                  </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                  <label for="filtro_facturacion_cliente" class="form-label mb-1">Cliente</label>
                  <select class="form-control form-control-sm" id="filtro_facturacion_cliente" style="width:100%;">
                    <option value="">Todos</option>
                  </select>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                  <label for="filtro_facturacion_tipo_documento" class="form-label mb-1">Tipo de documento</label>
                  <select class="form-control form-control-sm" id="filtro_facturacion_tipo_documento" style="width:100%;" multiple></select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                  <label for="filtro_facturacion_estado" class="form-label mb-1">Estado</label>
                  <select class="form-control form-control-sm" id="filtro_facturacion_estado" style="width:100%;" multiple></select>
                </div>
              </div>
              <div class="table-responsive">
                <table id="tabla-facturacion" class="table table-bordered table-striped w-100">
                  <thead>
                    <tr>
                      <th class="text-center">OP</th>
                      <th>Fecha emision</th>
                      <th>Cliente</th>
                      <th>Nro</th>
                      <th class="text-end">Total</th>
                      <th class="text-center">SUNAT</th>
                      <th class="text-center">Estado</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div id="div-formulario-facturacion" style="display:none;">
          <form id="form-factura-facturacion">
            @csrf
            <div class="row">
              <div class="col-lg-4">
                <div class="card custom-card">
                  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                      <h5 class="card-title mb-1" id="titulo-formulario-facturacion">Nuevo comprobante</h5>
                      <p class="text-muted mb-0 fs-12">Cabecera del comprobante electronico.</p>
                    </div>
                  </div>

                  <div class="card-body">
                    <input type="hidden" class="form-control" id="factura_igv_label" readonly>

                    <div class="row g-3">
                      <div class="col-md-12">
                        <div class="form-check form-switch mb-0">
                          <input class="form-check-input" type="checkbox" role="switch" id="factura_imprimir_automatico" checked>
                          <label class="form-check-label" for="factura_imprimir_automatico">Mostrar impresion al guardar</label>
                        </div>
                      </div>
                      <div class="col-md-12">
                        <label class="form-label">Tipo de documento <sup class="text-danger">*</sup></label>
                        <div class="mb-0 authentication-btn-group">
                          <div class="btn-group flex-wrap" role="group" aria-label="Tipos de documento SUNAT" id="facturacion_tipo_documento_group"></div>
                        </div>
                      </div>
                      <div class="col-md-12">
                        <label for="factura_cliente" class="form-label" id="factura_cliente_label">Cliente <sup class="text-danger">*</sup></label>
                        <select class="form-control" id="factura_cliente" name="idpersona_cliente" style="width:100%;"></select>
                      </div>
                      <div class="col-md-6">
                        <label for="factura_serie" class="form-label">Serie <sup class="text-danger">*</sup></label>
                        <select class="form-control" id="factura_serie" name="idserie_comprobante" style="width:100%;"></select>
                      </div>
                      <div class="col-md-6">
                        <label for="factura_fecha_emision" class="form-label">Fecha emision</label>
                        <input type="date" class="form-control" id="factura_fecha_emision" name="fecha_emision" readonly>
                      </div>

                      <div class="col-lg-12">
                        <label for="factura_observacion" class="form-label">Observacion</label>
                        <textarea class="form-control" id="factura_observacion" name="observacion_documento" rows="3" maxlength="1000"></textarea>
                      </div>
                    </div>
                  </div>
                  <div class="card-footer">
                    <div class="d-flex justify-content-end">
                      <button type="button" class="btn btn-sm btn-outline-danger me-1" id="btn-cancelar-facturacion">
                        <i class="ti ti-circle-dashed-x me-1"></i>Cancelar
                      </button>
                      <button type="submit" class="btn btn-sm btn-primary btn-guardar-factura" id="btn-guardar-factura">
                        <i class="ti ti-device-floppy me-1"></i>Guardar
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-8">
                <div class="card custom-card mb-0">
                  <div class="card-header">
                    <div class="row g-2 w-100">
                      <div class="col-12 col-lg-8">
                        <div class="position-relative">
                          <input type="text" class="form-control form-control-sm" id="buscar_producto_directo_factura" placeholder="Buscar producto rapido...">
                          <div class="list-group position-absolute w-100 shadow-sm d-none shadow-0px1rem3rem-rgb-0-0-0-50" id="lista_buscar_producto_directo_factura" style="z-index: 1080; max-height: 320px; overflow:auto;"></div>
                        </div>
                      </div>
                      <div class="col-12 col-lg-4 d-grid">
                        <button type="button" class="btn btn-primary btn-wave btn-sm" id="btn-agregar-item-factura">
                          <i class="ri-add-line me-1 align-middle d-inline-block"></i>Agregar producto
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="text-muted fs-12 mb-2">Haz doble clic sobre la descripcion de un producto para editarla.</div>
                    <div class="table-responsive">
                      <table class="table text-nowrap mb-0 facturacion-detalle-table" id="tabla-detalle-facturacion">
                        <thead>
                          <tr>
                            <th class="text-center" style="width:70px;">OP</th>
                            <th style="min-width:220px;">Producto</th>
                            <th style="min-width:100px;">Cant.</th>
                            <th style="min-width:120px;">Precio</th>
                            <th style="min-width:110px;">Desc. %</th>
                            <th style="min-width:110px;">Desc. S/</th>
                            <th style="min-width:130px;" class="text-end">Subtotal</th>
                          </tr>
                        </thead>
                        <tbody id="factura_detalle_body">
                          <tr class="facturacion-detalle-vacio">
                            <td colspan="7" class="text-center text-muted py-4">
                              No hay productos seleccionados.
                            </td>
                          </tr>
                        </tbody>
                        <tfoot>
                          <tr>
                            <td colspan="3" class="py-1"></td>
                            <td colspan="2" class="py-1 text-end">
                              <div class="fw-semibold">Total Items :</div>
                            </td>
                            <td colspan="2" class="py-1 text-end">
                              <span class="fw-semibold" id="factura_total_items">0</span>
                            </td>
                          </tr>
                          <tr>
                            <td colspan="3" class="py-1"></td>
                            <td colspan="2" class="py-1 text-end">
                              <div class="fw-semibold">Sub Total :</div>
                            </td>
                            <td colspan="2" class="py-1 text-end">
                              <span class="fw-semibold" id="factura_total_subtotal">S/ 0.00</span>
                            </td>
                          </tr>
                          <tr>
                            <td colspan="3" class="py-1"></td>
                            <td colspan="2" class="py-1 text-end">
                              <div class="fw-semibold">Descuento :</div>
                            </td>
                            <td colspan="2" class="py-1 text-end">
                              <span class="fw-semibold" id="factura_total_descuento">S/ 0.00</span>
                            </td>
                          </tr>
                          <tr>
                            <td colspan="3" class="py-1"></td>
                            <td colspan="2" class="py-1 text-end">
                              <div class="fw-semibold" id="factura_label_igv">IGV 0% :</div>
                            </td>
                            <td colspan="2" class="py-1 text-end">
                              <span class="fw-semibold" id="factura_total_igv">S/ 0.00</span>
                            </td>
                          </tr>
                          <tr>
                            <td colspan="3" class="py-1"></td>
                            <td colspan="2" class="py-1 text-end">
                              <div class="fw-semibold">Total :</div>
                            </td>
                            <td colspan="2" class="py-1 text-end">
                              <span class="fs-16 fw-semibold text-primary" id="factura_total_general">S/ 0.00</span>
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                    <ul class="nav nav-tabs tab-style-2 nav-justified mb-2 mt-3 d-sm-flex d-block" id="ccCobroTabs" role="tablist">
                      <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="cc-pago-metodo-tab" data-bs-toggle="tab" data-bs-target="#cc-pago-metodo-tab-pane" type="button" role="tab" aria-selected="true">
                          <i class="ri-bank-card-line me-1 align-middle"></i>Metodo de pago
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link" id="cc-pago-archivos-tab" data-bs-toggle="tab" data-bs-target="#cc-pago-archivos-tab-pane" type="button" role="tab" aria-selected="false">
                          <i class="ri-attachment-2 me-1 align-middle"></i>Documentos
                        </button>
                      </li>
                    </ul>

                    <div class="tab-content" id="ccCobroTabsContent">
                      <div class="tab-pane fade show active text-muted" id="cc-pago-metodo-tab-pane" role="tabpanel" aria-labelledby="cc-pago-metodo-tab" tabindex="0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <small class="text-muted">Agrega uno o varios metodos de pago para este cobro.</small>
                          <button type="button" class="btn btn-sm btn-outline-primary" id="btn-cc-agregar-metodo-pago">
                            <i class="ri-add-line me-1"></i>Agregar
                          </button>
                        </div>
                        <div class="table-responsive">
                          <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                              <tr>
                                <th>Cuenta bancaria / Metodo</th>
                                <th style="width: 140px;">Monto</th>
                                <th style="width: 180px;">Voucher</th>
                                <th style="width: 70px;" class="text-center">OP</th>
                              </tr>
                            </thead>
                            <tbody id="cc_pago_metodos_body">
                              <tr class="js-cc-metodo-empty-row">
                                <td colspan="4" class="text-center text-muted py-3">Ningun metodo de pago agregado.</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                        <input type="hidden" id="cc_pago_metodos_valid" name="cc_pago_metodos_valid" value="1">
                        <div id="cc_pago_metodos_error_target"></div>
                      </div>

                      <div class="tab-pane fade text-muted" id="cc-pago-archivos-tab-pane" role="tabpanel" aria-labelledby="cc-pago-archivos-tab" tabindex="0">
                        <div class="mb-2">
                          <label for="cc_pago_documentos" class="form-label">Adjuntar documentos</label>
                          <input type="file" class="form-control" id="cc_pago_documentos" name="cc_pago_documentos[]" multiple>
                          <small class="text-muted">Puedes subir PDF, imagen, Word, Excel, etc. Maximo 10 MB por archivo.</small>
                        </div>
                        <div class="table-responsive">
                          <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                              <tr>
                                <th>Archivo</th>
                                <th style="width: 120px;">Tamanio</th>
                                <th style="width: 70px;" class="text-center">OP</th>
                              </tr>
                            </thead>
                            <tbody id="cc-pago-archivos-body">
                              <tr class="js-cc-archivo-empty-row">
                                <td colspan="3" class="text-center text-muted py-3">Ningun documento adjunto.</td>
                              </tr>
                            </tbody>
                          </table>
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

    <div class="dropdown-menu shadow shadow-lg" id="menu-contextual" style="display:none; position:absolute; z-index:1055; min-width: 260px;">
      <ul class="nav nav-pills flex-column">
        <li class="dropdown-header" id="menu-contextual-facturacion-titulo">Comprobante</li>
        <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-facturacion-imprimir"><i class="ri-printer-line"></i> Imprimir</a></li>
        <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-facturacion-detalle"><i class="bi bi-eye"></i> Ver detalle SUNAT</a></li>
        @permiso('facturacion', 'editar')
        <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-facturacion-anular"><i class="ri-file-reduce-line"></i> Anular con nota de credito</a></li>
        <li class="nav-item"><a href="#" class="nav-link py-1" id="opcion-facturacion-desactivar-ticket"><i class="ri-delete-bin-line"></i> Desactivar ticket</a></li>
        @endpermiso
      </ul>
    </div>

    <div class="modal fade" id="modal-anular-sunat-facturacion" tabindex="-1" aria-labelledby="modal-anular-sunat-facturacion-label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h6 class="modal-title" id="modal-anular-sunat-facturacion-label">Registrar nota de credito</h6>
              <div class="text-muted fs-12" id="modal-anular-sunat-facturacion-subtitle">-</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form id="form-anular-sunat-facturacion">
              <input type="hidden" id="anular_facturacion_iddocumento">
              <div class="border rounded p-3 mb-3 bg-light">
                <div class="row g-3">
                  <div class="col-12 col-md-7">
                    <div class="text-muted fs-12">Cliente</div>
                    <div class="fw-semibold" id="anular_facturacion_cliente">-</div>
                    <div class="text-muted fs-12" id="anular_facturacion_cliente_documento">-</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted fs-12">Comprobante</div>
                    <div class="fw-semibold" id="anular_facturacion_comprobante">-</div>
                  </div>
                  <div class="col-6 col-md-2 text-md-end">
                    <div class="text-muted fs-12">Total</div>
                    <div class="fw-semibold" id="anular_facturacion_total">-</div>
                  </div>
                  <div class="col-12">
                    <div class="text-muted fs-12 mb-1">Detalle del documento</div>
                    <div id="anular_facturacion_detalle" class="small">-</div>
                  </div>
                </div>
              </div>
              <div class="mb-3" id="anular_facturacion_serie_group">
                <label for="anular_facturacion_motivo" class="form-label">Motivo SUNAT <sup class="text-danger">*</sup></label>
                <select class="form-control" id="anular_facturacion_motivo" required></select>
              </div>
              <div class="mb-3">
                <label for="anular_facturacion_serie" class="form-label">Serie de nota de credito <sup class="text-danger">*</sup></label>
                <select class="form-control" id="anular_facturacion_serie" required></select>
              </div>
              <div>
                <label for="anular_facturacion_observacion" class="form-label">Observacion</label>
                <textarea class="form-control" id="anular_facturacion_observacion" rows="3" maxlength="1000"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer py-1">
            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-sm btn-success" id="btn-confirmar-anular-sunat-facturacion" form="form-anular-sunat-facturacion">
              <i class="ri-file-reduce-line me-1"></i> Registrar nota de credito
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal-detalle-sunat-facturacion" tabindex="-1" aria-labelledby="modal-detalle-sunat-facturacion-label" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header py-2">
            <div>
              <h6 class="modal-title" id="modal-detalle-sunat-facturacion-label">Detalle SUNAT</h6>
              <div class="text-muted fs-12" id="modal-detalle-sunat-facturacion-subtitle">-</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="text-muted fs-12">Tipo de documento</div>
                <div class="fw-semibold" id="detalle-sunat-tipo-documento">-</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-muted fs-12">Numero de documento</div>
                <div class="fw-semibold" id="detalle-sunat-numero-documento">-</div>
              </div>
              <div class="col-12 col-md-4">
                <div class="text-muted fs-12">Estado</div>
                <div id="detalle-sunat-estado">-</div>
              </div>
              <div class="col-12 col-md-4">
                <div class="text-muted fs-12">Codigo</div>
                <div class="fw-semibold" id="detalle-sunat-code">-</div>
              </div>
              <div class="col-12 col-md-4">
                <div class="text-muted fs-12">Hash</div>
                <div class="text-break" id="detalle-sunat-hash">-</div>
              </div>
              <div class="col-12">
                <div class="text-muted fs-12">Mensaje</div>
                <div class="text-break" id="detalle-sunat-mensaje">-</div>
              </div>
              <div class="col-12">
                <div class="text-muted fs-12">Observacion</div>
                <div class="text-break" id="detalle-sunat-observacion">-</div>
              </div>
              <div class="col-12">
                <div class="text-muted fs-12">Error</div>
                <div class="text-break" id="detalle-sunat-error">-</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal-productos-facturacion" tabindex="-1" aria-labelledby="modal-productos-facturacion-label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modal-productos-facturacion-label">Seleccionar producto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle w-100" id="tabla-productos-facturacion-modal">
                <thead>
                  <tr>
                    <th class="text-center" style="width:70px;">OP</th>
                    <th>Codigo</th>
                    <th>Producto</th>
                    <th class="text-end">Precio</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modal-impresion-facturacion" tabindex="-1" aria-labelledby="modal-impresion-facturacion-label" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header py-2">
            <div>
              <h6 class="modal-title" id="modal-impresion-facturacion-label">Impresion de comprobante</h6>
              <div class="text-muted fs-12" id="modal-impresion-facturacion-subtitle">-</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <ul class="nav nav-tabs tab-style-2 mb-3" id="facturacion-print-tabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="facturacion-print-a4-tab" data-bs-toggle="tab" data-bs-target="#facturacion-print-a4-pane" type="button" role="tab" aria-controls="facturacion-print-a4-pane" aria-selected="true">
                  <i class="ri-file-text-line me-1"></i>A4
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="facturacion-print-ticket-tab" data-bs-toggle="tab" data-bs-target="#facturacion-print-ticket-pane" type="button" role="tab" aria-controls="facturacion-print-ticket-pane" aria-selected="false">
                  <i class="ri-receipt-line me-1"></i>Ticket termico
                </button>
              </li>
            </ul>
            <div class="tab-content">
              <div class="tab-pane fade show active" id="facturacion-print-a4-pane" role="tabpanel" aria-labelledby="facturacion-print-a4-tab" tabindex="0">
                <div class="d-flex justify-content-end gap-2 mb-2">
                  <button type="button" class="btn btn-sm btn-primary js-imprimir-formato-facturacion" data-formato="a4"><i class="ri-printer-line me-1"></i>Imprimir</button>
                  <a class="btn btn-sm btn-light js-abrir-formato-facturacion" data-formato="a4" href="#" target="_blank" rel="noopener"><i class="ri-external-link-line me-1"></i>Abrir</a>
                </div>
                <iframe class="facturacion-print-frame" id="facturacion-print-a4-frame" title="Formato A4"></iframe>
              </div>
              <div class="tab-pane fade" id="facturacion-print-ticket-pane" role="tabpanel" aria-labelledby="facturacion-print-ticket-tab" tabindex="0">
                <div class="d-flex justify-content-end gap-2 mb-2">
                  <button type="button" class="btn btn-sm btn-primary js-imprimir-formato-facturacion" data-formato="ticket"><i class="ri-printer-line me-1"></i>Imprimir</button>
                  <a class="btn btn-sm btn-light js-abrir-formato-facturacion" data-formato="ticket" href="#" target="_blank" rel="noopener"><i class="ri-external-link-line me-1"></i>Abrir</a>
                </div>
                <iframe class="facturacion-print-frame" id="facturacion-print-ticket-frame" title="Formato ticket termico"></iframe>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-detalle-facturacion" aria-labelledby="offcanvas-detalle-facturacion-label">
      <div class="offcanvas-header border-bottom py-2">
        <h5 class="offcanvas-title" id="offcanvas-detalle-facturacion-label">Detalle de comprobante</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
      </div>
      <div class="offcanvas-body p-0">
        <div id="offcanvas-detalle-facturacion-body" class="p-2">
          <div class="text-center text-muted py-3">Seleccione un comprobante para ver su detalle.</div>
        </div>
      </div>
    </div>

    @include('layouts.yn_footer')
  </div>

  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')

  <script src="{{ asset('ynex_admin/libs/flatpickr/plugins/monthSelect/index.js') }}"></script>
  <script src="{{ asset('assets/js/facturacion.js') }}?v={{ filemtime(public_path('assets/js/facturacion.js')) }}"></script>
</body>

</html>
