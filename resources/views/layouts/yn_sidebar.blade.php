<!-- Start::app-sidebar -->
<aside class="app-sidebar sticky" id="sidebar">

  <!-- Start::main-sidebar-header -->
  <div class="main-sidebar-header">
    <a href="{{ route('inicio') }}" class="header-logo">
      <img src="{{ asset('assets/images/brand-logos/logo-100x35.png') }}" alt="logo" class="desktop-logo">
      <img src="{{ asset('assets/images/brand-logos/toggle-logo.png') }}" alt="logo" class="toggle-logo">
      <img src="{{ asset('assets/images/brand-logos/logo-100x35.png') }}" alt="logo" class="desktop-dark">
      <img src="{{ asset('assets/images/brand-logos/logo-36x36.png') }}" alt="logo" class="toggle-dark">
      <img src="{{ asset('assets/images/brand-logos/desktop-white.png') }}" alt="logo" class="desktop-white">
      <img src="{{ asset('assets/images/brand-logos/toggle-white.png') }}" alt="logo" class="toggle-white">
    </a>
  </div>
  <!-- End::main-sidebar-header -->

  <!-- Start::main-sidebar -->
  <div class="main-sidebar" id="sidebar-scroll">

    <!-- Start::nav -->
    <nav class="main-menu-container nav nav-pills flex-column sub-open">
      <div class="slide-left" id="slide-left">
        <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
          <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
        </svg>
      </div>
      <ul class="main-menu">

        <!-- <li class="slide"> <input type="text" class="form-control form-control-sm" id="input-buscar-li-menu" placeholder="Buscar menu" > </li> -->

        <li class="slide" style="position: relative;">
          <div class="position-relative">

            <input type="text" class="form-control form-control-sm" id="___menuSearchInput" placeholder="Buscar modulo...">
            <ul id="___menuSearchDropdown" class="___dropdown-list list-group position-absolute w-100" style="z-index: 1000; display: none;"></ul>
          </div>

        </li>

        <!-- Start::slide__category -->
        <li class="slide__category">
          <span class="category-name">I N I C I O</span>
          <!-- <span class="badge bg-warning-transparent category-name float-end" id="anclar-menu-personalizado">
            <i class="bi bi-pin fs-13 cursor-pointer"></i>
          </span> -->
        </li>
        <!-- End::slide__category -->

        <!-- Start::slide -->
          @permiso('dashboard', 'ver')
          <li class="slide">
            <a href="#" class="side-menu__item">
              <i class="bx bx-home side-menu__icon"></i><span class="side-menu__label"> Dashboards</span>
            </a>
          </li>
          @endpermiso
        <!-- End::slide -->

        <!-- Start::slide__category -->
        <li class="slide__category"><span class="category-name">G E S T I O N - D E - V E N T A S</span></li>
        <!-- End::slide__category -->

        <!-- Start::slide -->
          @permiso(['facturacion', 'clientes', 'productos'], 'ver')
          <li class="slide has-sub">
            <a href="javascript:void(0);" class="side-menu__item">
              <i class='bx bx-cart-plus side-menu__icon'></i>
              <span class="side-menu__label">Ventas</span>
              <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child1">
              <li class="slide side-menu__label1"> <a href="javascript:void(0)">Ventas</a> </li>
              @permiso('facturacion', 'ver')
              <li class="slide"> <a href="{{ route('facturacion.index') }}" class="side-menu__item">Facturacion</a></li>
              @endpermiso
              @permiso('clientes', 'ver')
              <li class="slide"> <a href="{{ route('cliente.index') }}" class="side-menu__item">Clientes</a></li>
              @endpermiso
              @permiso('productos', 'ver')
              <li class="slide"> <a href="{{ route('producto.index') }}" class="side-menu__item">Productos</a></li>
              @endpermiso
            </ul>
          </li>
          @endpermiso
        <!-- End::slide -->

        <!-- Start::slide -->
          @permiso(['gestion_reservas', 'reserva', 'llegada', 'salida'], 'ver')
          <li class="slide has-sub">
            <a href="javascript:void(0);" class="side-menu__item">
              <i class="bx  bx-basket side-menu__icon"></i>
              <span class="side-menu__label">Gestión Reserva</span>
              <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child1">
              <li class="slide side-menu__label1 "> <a class="" href="javascript:void(0)">Gestión Reserva</a> </li>

              @permiso('reserva', 'ver')
              <li class="slide "> <a href="{{ route('reservas.index') }}" class="side-menu__item ">Reserva</a></li>
              @endpermiso

              @permiso('llegada', 'ver')
              <li class="slide "> <a href="{{ route('llegadas.index') }}" class="side-menu__item ">Llegada</a></li>
              @endpermiso

              @permiso('salida', 'ver')
              <li class="slide "> <a href="{{ route('salidas.index') }}" class="side-menu__item ">Salida</a></li>
              @endpermiso
            </ul>
          </li>
          @endpermiso
        <!-- End ::slide -->

        <!-- Start::slide -->
          @permiso(['venta', 'bitacora_de_sistema'], 'ver')
          <li class="slide has-sub">
            <a href="javascript:void(0);" class="side-menu__item">
              <i class='bx bx-cart-plus side-menu__icon'></i>
              <span class="side-menu__label">Reportes</span>
              <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child1">
              <li class="slide side-menu__label1"> <a href="javascript:void(0)">Reportes</a> </li>

              @permiso('venta', 'ver')
              <li class="slide"> <a href="#" class="side-menu__item">Ventas</a></li>
              @endpermiso
              @permiso('bitacora_de_sistema', 'ver')
              <li class="slide"> <a href="#" class="side-menu__item">Bitacora del Sistema</a></li>
              @endpermiso
            </ul>
          </li>
          @endpermiso
        <!-- End::slide -->

        <!-- Start::slide__category -->
        <li class="slide__category"><span class="category-name">T U R I S M O</span></li>
        <!-- End::slide__category -->

        <!-- Start::slide -->
          @permiso(['tours', 'hoteles', 'agencias', 'aerolineas'], 'ver')
          <li class="slide has-sub">
            <a href="javascript:void(0);" class="side-menu__item">
              <i class='bx bx-dollar-circle side-menu__icon'></i>
              <span class="side-menu__label">Turismo <span class="badge bg-secondary-transparent ms-2">New</span></span>
              <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child1 mega-menu">
              <li class="slide side-menu__label1"> <a href="javascript:void(0)">Turismo <span class="badge bg-secondary-transparent ms-2">New</span></a></li>
                @permiso('tours', 'ver')
                <li class="slide"> <a href="{{ route('tours.index') }}" class="side-menu__item">Tours</a></li>
                @endpermiso
                @permiso('hoteles', 'ver')
                <li class="slide"> <a href="{{ route('hoteles.index') }}" class="side-menu__item">Hoteles</a></li>
                @endpermiso
                @permiso('agencias', 'ver')
                <li class="slide"> <a href="{{ route('agencias.index') }}" class="side-menu__item">Agencias</a></li>
                @endpermiso
                @permiso('aerolineas', 'ver')
                <li class="slide"> <a href="{{ route('aerolineas.index') }}" class="side-menu__item">Aerolineas</a></li>
                @endpermiso
            </ul>
          </li>
          @endpermiso
        <!-- End::slide -->

        <!-- Start::slide__category -->
        <li class="slide__category"><span class="category-name">C O N T A B I L I D A D</span></li>
        <!-- End::slide__category -->

        <!-- Start::slide -->
          @permiso(['caja', 'ingreso_y_egreso'], 'ver')
          <li class="slide has-sub">
            <a href="javascript:void(0);" class="side-menu__item">
              <i class='bx bx-dollar-circle side-menu__icon'></i>
              <span class="side-menu__label">Caja <span class="badge bg-secondary-transparent ms-2">New</span></span>
              <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child1 mega-menu">
              <li class="slide side-menu__label1"> <a href="javascript:void(0)">Caja <span class="badge bg-secondary-transparent ms-2">New</span></a></li>
                @permiso('caja', 'ver')
                <li class="slide"> <a href="#" class="side-menu__item">Caja</a></li>
                @endpermiso
                @permiso('ingreso_y_egreso', 'ver')
                <li class="slide"> <a href="{{ route('ingreso-egreso.index') }}" class="side-menu__item">Ingreso y Egreso</a></li>
                @endpermiso
            </ul>
          </li>
          @endpermiso
        <!-- End::slide -->

        <!-- Start::slide__category -->
        <li class="slide__category"><span class="category-name">G E S T I O N - R R H H</span></li>
        <!-- End::slide__category -->

        <!-- Start::slide -->
          @permiso('trabajadores', 'ver')
          <li class="slide has-sub">
            <a href="javascript:void(0);" class="side-menu__item">
              <i class='bx bx-user-check side-menu__icon'></i>
              <span class="side-menu__label">Planilla Personal</span>
              <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child1">
              <li class="slide side-menu__label1"><a href="javascript:void(0)">Planilla Personal</a></li>
              @permiso('trabajadores', 'ver')
              <li class="slide"><a href="{{ route('trabajadores.index') }}" class="side-menu__item">Trabajadores</a></li>
              @endpermiso
              <!-- <li class="slide"><a href="#" class="side-menu__item">Tipo de seguro</a></li> -->
              <!-- <li class="slide"><a href="#" class="side-menu__item">Boleta de pago</a></li> -->
            </ul>
          </li>
          @endpermiso
        <!-- End::slide -->

        <!-- Start::slide__category -->
        <li class="slide__category"><span class="category-name">C O N F I G U R A C I O N</span></li>
        <!-- End::slide__category -->

        <!-- Start::slide -->
          @permiso(['catalogos_de_codigo', 'tipo_de_comprobantes', 'series_de_comprobantes'], 'ver')
          <li class="slide has-sub">
            <a href="javascript:void(0);" class="side-menu__item">
              <i class='bx bx-building side-menu__icon'></i>
              <span class="side-menu__label">SUNAT</span>
              <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child1">
              <li class="slide side-menu__label1"><a href="javascript:void(0)">SUNAT</a></li>
              @permiso('catalogos_de_codigo', 'ver')
              <li class="slide"><a href="{{ route('sunat.catalogos-codigo.index') }}" class="side-menu__item">Catalogo de Codigos</a></li>
              @endpermiso
              @permiso('tipo_de_comprobantes', 'ver')
              <li class="slide"><a href="{{ route('sunat.tipos-comprobantes.index') }}" class="side-menu__item">Tipos de Comprobantes</a></li>
              @endpermiso
              @permiso('series_de_comprobantes', 'ver')
              <li class="slide"><a href="{{ route('sunat.series-comprobantes.index') }}" class="side-menu__item">Series de Comprobantes</a></li>
              @endpermiso

            </ul>
          </li>
          @endpermiso
        <!-- End::slide -->

        <!-- Start::slide -->
          @permiso(['usuarios_del_sistema', 'empresa', 'catalogo_general', 'cuentas_bancarias'], 'ver')
          <li class="slide has-sub">
            <a href="javascript:void(0);" class="side-menu__item">
              <i class='bx bx-cog side-menu__icon'></i>
              <span class="side-menu__label">Configuración</span>
              <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child1">
              <li class="slide side-menu__label1"><a href="javascript:void(0)">Configuración</a></li>
              @permiso('usuarios_del_sistema', 'ver')
              <li class="slide"><a href="{{ route('usuarios.index') }}" class="side-menu__item">Usuarios del Sistema</a></li>
              @endpermiso
              @permiso('empresa', 'ver')
              <li class="slide"><a href="{{ route('empresa.index') }}" class="side-menu__item">Empresa</a></li>
              @endpermiso
              @permiso('catalogo_general', 'ver')
              <li class="slide"><a href="{{ route('catalogo_general.index') }}" class="side-menu__item">Catalogo General</a></li>
              @endpermiso
              @permiso('cuentas_bancarias', 'ver')
              <li class="slide"> <a href="{{ route('cuentas-bancarias.index') }}" class="side-menu__item">Cuentas Bancarias</a></li>
              @endpermiso
            </ul>
          </li>
          @endpermiso
        <!-- End::slide -->

        <!-- Start::slide -->
        @permiso('papelera', 'ver')
        <li class="slide">
          <a href="{{ route('papelera.index') }}" class="side-menu__item">
            <i class='bx bx-trash side-menu__icon'></i><span class="side-menu__label">Papelera</span>
          </a>
        </li>
        @endpermiso
        <!-- End::slide -->

        <!-- Start::slide__category -->
        <li class="slide__category"><span class="category-name">S O P O R T E</span></li>
        <!-- End::slide__category -->

        <!-- Start::slide -->
        <li class="slide">
          <a href="https://wa.link/1dpx0i" class="side-menu__item" target="_blank">
            <i class="bx bx-home side-menu__icon"></i><span class="side-menu__label"> Soporte Técnico</span>
          </a>
        </li>
        <!-- End::slide -->


      </ul>
      <div class="slide-right" id="slide-right">
        <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24"
          height="24" viewBox="0 0 24 24">
          <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
        </svg>
      </div>
    </nav>
    <!-- End::nav -->

  </div>
  <!-- End::main-sidebar -->

</aside>
<!-- End::app-sidebar -->


<script>
  (function ___menuSearchInit() {
    const input = document.getElementById('___menuSearchInput');
    const dropdown = document.getElementById('___menuSearchDropdown');

    // 1) Helper robusto para decidir si un href es navegable
    function ___isNavigableHref(href) {
      const h = (href || '').trim().toLowerCase();
      if (!h) return false; // verificar / null
      if (h === '#' || h.startsWith('#')) return false; // ancla
      if (h.startsWith('javascript:')) return false; // javascript:void(0) y similares
      if (h === 'void(0)' || h === 'void(0);') return false;
      // Excluir otros esquemas no navegables si deseas:
      if (h.startsWith('mailto:') || h.startsWith('tel:')) return false;
      return true;
    }

    // 2) Recolectar enlaces vÃƒÆ’Ã‚Â¡lidos del menÃƒÆ’Ã‚Âº principal (solo a[href])
    const menuAnchors = Array.from(document.querySelectorAll('.main-menu li.slide a[href]'));
    const menuLinks = menuAnchors.filter(a => ___isNavigableHref(a.getAttribute('href')));

    // (Opcional) Quitar duplicados por href+texto
    const seen = new Set();
    const uniqueMenuLinks = [];
    for (const a of menuLinks) {
      const key = (a.getAttribute('href') || '').trim() + '|' + a.innerText.trim();
      if (!seen.has(key)) {
        seen.add(key);
        uniqueMenuLinks.push(a);
      }
    }

    // 3) BÃƒÆ’Ã‚Âºsqueda
    input.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase().trim();
      dropdown.innerHTML = '';

      if (searchTerm === '') {
        dropdown.style.display = 'none';
        return;
      }

      const matches = uniqueMenuLinks.filter(link =>
        (link.innerText || link.textContent).toLowerCase().includes(searchTerm)
      );

      if (matches.length === 0) {
        dropdown.style.display = 'none';
        return;
      }

      // Construir la lista (usa estructura tipo dropdown de tu plantilla)
      matches.forEach(link => {
        const li = document.createElement('li');
        li.className = 'list-group-item hover-text-primary list-group-item-action bg-light cursor-pointer p-1';

        const a = document.createElement('a');
        a.href = link.getAttribute('href'); // usar el atributo crudo
        a.innerHTML = `<i class="ti ti-square-rounded-chevrons-right"></i> ${link.innerText.trim()}`;

        li.appendChild(a);
        dropdown.appendChild(li);
      });

      dropdown.style.display = 'block';
    });

    // 4) Cerrar dropdown al hacer click fuera
    document.addEventListener('click', function(e) {
      if (!input.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });

    input.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        dropdown.style.display = 'none';
      }
    });
  })();
</script>
