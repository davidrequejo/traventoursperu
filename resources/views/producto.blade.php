<!DOCTYPE html>
<html lang="es" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
  data-menu-styles="dark" data-toggled="icon-overlay-close" loader="enable">

<head>
  @include('layouts.yn_head', ['title_page' => 'Productos'])

  <style>
    #tabla-productos_filter {
      width: calc(100% - 10px) !important;
      display: flex !important;
      justify-content: space-between !important;
    }

    #tabla-productos_filter label,
    #tabla-productos_filter label input {
      width: 100% !important;
    }

    .producto-form-shell {
      max-width: 1120px;
      margin: 0 auto;
    }

    .producto-form-card {
      position: relative;
    }

    .producto-fieldset {
      border: 1px solid var(--default-border);
      border-radius: .5rem;
      padding: .85rem 1rem 1rem;
      margin: 0 0 .85rem;
      background: var(--custom-white);
      position: relative;
    }

    .producto-fieldset legend {
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

    .producto-fieldset .form-label {
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

    .form-save-overlay {
      position: absolute;
      inset: 0;
      z-index: 25;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, .78);
      backdrop-filter: blur(1.5px);
      border-radius: .5rem;
    }

    .form-save-overlay.show {
      display: flex;
    }

    .overlay-chip {
      background: #fff;
      border: 1px solid var(--default-border);
      border-radius: 999px;
      padding: .5rem .85rem;
      font-weight: 600;
      box-shadow: 0 5px 18px rgba(16, 24, 40, .08);
      display: inline-flex;
      align-items: center;
      gap: .5rem;
    }
  </style>
</head>

<body id="body-productos">
  @include('layouts.yn_switcher')
  @include('layouts.yn_loader')

  <div class="page">
    @include('layouts.yn_header')
    @include('layouts.yn_sidebar')

    <div class="main-content app-content">
      <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
          <div>
            <p class="fw-semibold fs-18 mb-0 title-body-pagina">Productos</p>
            <nav>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Productos</li>
              </ol>
            </nav>
          </div>

          <div class="btn-list mt-md-0 mt-2">
            <div class="form-check form-switch d-inline-flex align-items-center mb-0 me-2">
              <input class="form-check-input" type="checkbox" role="switch" id="incluir-eliminados-producto">
              <label class="form-check-label fs-12 text-muted ms-2" for="incluir-eliminados-producto">Papelera</label>
            </div>

            @permiso('productos', 'crear')
            <button type="button" class="btn btn-primary m-r-10px" id="btn-nuevo-producto">
              <i class="ri-add-line me-2"></i>Agregar
            </button>
            @endpermiso

            <button type="button" class="btn btn-light label-btn m-r-10px" id="btn-regresar-producto" style="display:none;">
              <i class="bi bi-arrow-left label-btn-icon me-2"></i>Regresar
            </button>
          </div>
        </div>

        <div id="panel-productos-tabla">
          <div class="card custom-card">
            <div class="card-body">
              <div class="table-responsive">
                <table id="tabla-productos" class="table table-bordered table-striped w-100">
                  <thead>
                    <tr>
                      <th class="text-center">OP</th>
                      <th>Codigo</th>
                      <th>Nombre</th>
                      <th>Tipo</th>
                      <th>Marca</th>
                      <th>Categoria</th>
                      <th>Unidad</th>
                      <th class="text-end">Stock</th>
                      <th class="text-end">Precio</th>
                      <th class="text-center">Estado</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div id="panel-productos-form" style="display:none;">
          <div class="producto-form-shell">
            <div class="card custom-card producto-form-card">
              <div class="form-save-overlay" id="overlay-guardando-producto">
                <div class="overlay-chip">
                  <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                  Guardando producto...
                </div>
              </div>

              <form id="form-producto" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="idproducto" id="producto_idproducto">
                <input type="hidden" name="imagen_actual" id="producto_imagen_actual">

                <div class="card-header justify-content-between flex-wrap gap-2">
                  <div>
                    <h5 class="card-title mb-1" id="form-producto-titulo">Nuevo producto</h5>
                    <p class="text-muted mb-0 fs-12">Registra la informacion comercial y de inventario del producto.</p>
                  </div>
                  <div class="btn-list">
                    <button type="button" class="btn btn-outline-danger" id="btn-cancelar-producto">
                      <i class="ti ti-circle-dashed-x"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-producto">
                      <i class="ti ti-device-floppy"></i> Guardar
                    </button>
                  </div>
                </div>

                <div class="card-body">
                  <div class="row gy-3">
                    <div class="col-md-6 col-xl-6 col-xxl-6">
                      <fieldset class="producto-fieldset">
                        <legend><i class="ri-file-list-3-line"></i>Datos generales</legend>
                        <div class="row gy-2">
                          <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <label for="producto_tipo" class="form-label">Tipo <sup class="text-danger">*</sup></label>
                            <select class="form-control select2" name="tipo" id="producto_tipo" style="width:100%;">
                              <option value="">Seleccione</option>
                              <option value="PR">Producto</option>
                              <option value="SR">Servicio</option>
                            </select>
                          </div>
                          <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <label for="producto_unidad" class="form-label">Unidad Medida <sup class="text-danger">*</sup></label>
                            <select class="form-control select2" name="idsunat_unidad_medida" id="producto_unidad" style="width:100%;"></select>
                          </div>
                          <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <label for="producto_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="nombre" id="producto_nombre" maxlength="250">
                          </div>
                          <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <label for="producto_codigo_alterno" class="form-label">Codigo alterno</label>
                            <input type="text" class="form-control" name="codigo_alterno" id="producto_codigo_alterno" maxlength="50">
                          </div>
                        </div>
                      </fieldset>
                    </div>

                    <div class="col-md-6 col-xl-6 col-xxl-6">
                      <fieldset class="producto-fieldset">
                        <legend><i class="ri-price-tag-3-line"></i>Clasificacion y precios</legend>
                        <div class="row gy-2">
                          <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <label for="producto_marca" class="form-label d-flex align-items-center gap-2">
                              Marca <sup class="text-danger">*</sup>
                              @permiso('catalogo_general', 'crear')
                              <button type="button" class="btn btn-sm btn-primary label-action-btn js-abrir-rapido-catalogo" data-tipo="marca" title="Nueva marca">
                                <i class="ti ti-plus"></i>
                              </button>
                              @endpermiso
                            </label>
                            <select class="form-control select2" name="idmarca" id="producto_marca" style="width:100%;"></select>
                          </div>
                          <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <label for="producto_categoria" class="form-label d-flex align-items-center gap-2">
                              Categoria <sup class="text-danger">*</sup>
                              @permiso('catalogo_general', 'crear')
                              <button type="button" class="btn btn-sm btn-primary label-action-btn js-abrir-rapido-catalogo" data-tipo="categoria" title="Nueva categoria">
                                <i class="ti ti-plus"></i>
                              </button>
                              @endpermiso
                            </label>
                            <select class="form-control select2" name="idcategoria" id="producto_categoria" style="width:100%;"></select>
                          </div>
                          <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <label for="producto_precio_compra" class="form-label">Precio compra</label>
                            <input type="number" class="form-control" name="precio_compra" id="producto_precio_compra" min="0" step="0.01">
                          </div>
                          <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <label for="producto_precio_venta" class="form-label">Precio venta</label>
                            <input type="number" class="form-control" name="precio_venta" id="producto_precio_venta" min="0" step="0.01">
                          </div>
                          <div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <label for="producto_stock_minimo" class="form-label">Stock minimo</label>
                            <input type="number" class="form-control" name="stock_minimo" id="producto_stock_minimo" min="0" step="0.01">
                          </div>
                        </div>
                      </fieldset>
                    </div>

                    <div class="col-md-12">
                      <fieldset class="producto-fieldset mb-0">
                        <legend><i class="ri-chat-1-line"></i>Observaciones e imagen</legend>
                        <div class="row gy-3">
                          <div class="col-md-8">
                            <label for="producto_descripcion_adicional" class="form-label">Descripcion adicional</label>
                            <textarea class="form-control" name="descripcion_adicional" id="producto_descripcion_adicional" rows="1" maxlength="250"></textarea>
                          </div>
                          <div class="col-md-4">
                            <label for="producto_imagen_file" class="form-label">Imagen</label>
                            <input type="file" class="form-control" name="imagen_file" id="producto_imagen_file" accept=".jpg,.jpeg,.png,.webp,.gif">
                          </div>
                        </div>
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
  </div>

  <div class="modal fade" id="modal-catalogo-rapido" tabindex="-1" aria-labelledby="modal-catalogo-rapido-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title" id="modal-catalogo-rapido-label">Nuevo registro</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="form-catalogo-rapido" method="POST" novalidate>
            @csrf
            <input type="hidden" id="catalogo_rapido_tipo" name="tipo" value="">
            <div class="mb-3">
              <label for="catalogo_rapido_nombre" class="form-label">Nombre <sup class="text-danger">*</sup></label>
              <input type="text" class="form-control" id="catalogo_rapido_nombre" name="nombre" maxlength="100">
            </div>
            <div class="mb-0">
              <label for="catalogo_rapido_descripcion" class="form-label">Descripcion</label>
              <textarea class="form-control" id="catalogo_rapido_descripcion" name="descripcion" rows="2" maxlength="250"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary" id="btn-guardar-catalogo-rapido" form="form-catalogo-rapido">
            <i class="ti ti-device-floppy me-1"></i> Guardar
          </button>
        </div>
      </div>
    </div>
  </div>

  @include('layouts.yn_footer')
  @include('layouts.yn_scripts')
  @include('layouts.yn_custom_switcherjs')



  <script src="{{ asset('assets/js/producto.js') }}?v={{ filemtime(public_path('assets/js/producto.js')) }}"></script>
</body>

</html>
