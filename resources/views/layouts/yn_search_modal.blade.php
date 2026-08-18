<style>
  .menu-search-modal .modal-dialog {
    max-width: 720px;
  }

  .menu-search-modal .modal-content {
    border: 0;
    border-radius: .85rem;
    overflow: hidden;
    box-shadow: 0 1.25rem 4rem rgba(15, 23, 42, .22);
  }

  .menu-search-modal .modal-header {
    background: linear-gradient(135deg, #111827 0%, #1f2937 68%, #f93822 140%);
    color: #fff;
  }

  .menu-search-input-wrap {
    border: 1px solid var(--default-border);
    border-radius: .7rem;
    background: var(--custom-white);
    transition: border-color .2s ease, box-shadow .2s ease;
  }

  .menu-search-input-wrap:focus-within {
    border-color: #f93822;
    box-shadow: 0 0 0 .18rem rgba(249, 56, 34, .13);
  }

  .menu-search-input {
    min-height: 3.25rem;
    font-size: .98rem;
  }

  .menu-search-results {
    max-height: min(52vh, 430px);
    overflow-y: auto;
  }

  .menu-search-result {
    border: 1px solid var(--default-border);
    border-radius: .65rem;
    color: var(--default-text-color);
    transition: transform .15s ease, border-color .15s ease, background-color .15s ease;
  }

  .menu-search-result:hover,
  .menu-search-result:focus {
    border-color: rgba(249, 56, 34, .45);
    background: rgba(249, 56, 34, .06);
    color: var(--default-text-color);
    transform: translateY(-1px);
  }

  .menu-search-icon {
    width: 2.35rem;
    height: 2.35rem;
    border-radius: .6rem;
    background: rgba(249, 56, 34, .1);
    color: #f93822;
  }

  .menu-search-chip {
    border: 1px solid var(--default-border);
    border-radius: 999px;
    color: var(--default-text-color);
    background: var(--custom-white);
    transition: border-color .15s ease, background-color .15s ease, color .15s ease;
  }

  .menu-search-chip:hover,
  .menu-search-chip:focus {
    border-color: rgba(249, 56, 34, .45);
    background: rgba(249, 56, 34, .08);
    color: #f93822;
  }

  .menu-search-text {
    min-width: 0;
  }
</style>

<div class="modal fade menu-search-modal" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-0 px-4 py-3">
        <div>
          <h5 class="modal-title fw-semibold mb-1" id="searchModalLabel">Buscar en el menu</h5>
          <p class="mb-0 text-white-50 fs-12">Encuentra rapido cualquier modulo disponible en el sidebar.</p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body p-4">
        <div class="menu-search-input-wrap d-flex align-items-center px-3">
          <i class="bx bx-search-alt fs-4 text-muted me-2"></i>
          <input
            type="search"
            class="form-control border-0 shadow-none px-0 menu-search-input"
            id="menuSearchInput"
            placeholder="Buscar modulo, cliente, usuario, reporte..."
            autocomplete="off"
            aria-describedby="menuSearchHelp">
          <button type="button" class="btn btn-sm btn-light ms-2 d-none" id="menuSearchClear" aria-label="Limpiar busqueda">
            <i class="ri-close-line"></i>
          </button>
        </div>
        <div id="menuSearchHelp" class="form-text mt-2">Presiona Enter para abrir el primer resultado.</div>

        <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
          <p class="mb-0 fw-semibold">Resultados</p>
          <span class="badge bg-primary-transparent" id="menuSearchCount">0</span>
        </div>

        <div class="menu-search-results d-grid gap-2" id="menuSearchResults"></div>

        <div class="text-center py-5 d-none" id="menuSearchEmpty">
          <span class="avatar avatar-lg avatar-rounded bg-light text-muted mb-3">
            <i class="ri-search-eye-line fs-3"></i>
          </span>
          <h6 class="fw-semibold mb-1">Sin resultados</h6>
          <p class="text-muted mb-0 fs-13">Prueba con otro nombre del menu o modulo.</p>
        </div>

        <div class="mt-4" id="menuSearchQuickWrap">
          <p class="fw-semibold text-muted mb-2 fs-13">Accesos rapidos</p>
          <div class="d-flex flex-wrap gap-2" id="menuSearchQuickLinks"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('searchModal');
    const input = document.getElementById('menuSearchInput');
    const clearButton = document.getElementById('menuSearchClear');
    const results = document.getElementById('menuSearchResults');
    const empty = document.getElementById('menuSearchEmpty');
    const count = document.getElementById('menuSearchCount');
    const quickLinks = document.getElementById('menuSearchQuickLinks');
    const quickWrap = document.getElementById('menuSearchQuickWrap');

    if (!modal || !input || !results) {
      return;
    }

    const normalizeText = (value) => (value || '')
      .toString()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();

    const isNavigableHref = (href) => {
      const value = (href || '').trim().toLowerCase();
      return value && value !== '#' && !value.startsWith('#') && !value.startsWith('javascript:');
    };

    const escapeHtml = (value) => (value || '').toString().replace(/[&<>"']/g, (character) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    }[character]));

    const getParentName = (link) => {
      const parent = link.closest('ul.slide-menu')?.previousElementSibling;
      return parent?.querySelector('.side-menu__label')?.innerText.replace(/\s+/g, ' ').trim() || 'Menu';
    };

    const getIconClass = (link) => {
      const parentIcon = link.closest('ul.slide-menu')?.previousElementSibling?.querySelector('.side-menu__icon');
      const ownIcon = link.querySelector('.side-menu__icon');
      const iconClass = ownIcon?.className || parentIcon?.className || 'bx bx-link';

      return iconClass.replace(/\bside-menu__icon\b/g, '').replace(/\s+/g, ' ').trim();
    };

    const menuItems = Array.from(document.querySelectorAll('.main-menu a.side-menu__item[href]'))
      .filter((link) => isNavigableHref(link.getAttribute('href')))
      .map((link) => {
        const title = link.innerText.replace(/\s+/g, ' ').trim();
        const parent = getParentName(link);
        const href = link.getAttribute('href');
        const icon = getIconClass(link);

        return {
          title,
          parent,
          href,
          icon,
          target: link.getAttribute('target') || '',
          search: normalizeText(`${title} ${parent} ${href}`),
        };
      })
      .filter((item, index, items) => item.title && items.findIndex((current) => `${current.title}|${current.href}` === `${item.title}|${item.href}`) === index);

    const renderItems = (items) => {
      results.innerHTML = '';
      count.textContent = items.length;
      empty.classList.toggle('d-none', items.length > 0);

      items.forEach((item) => {
        const link = document.createElement('a');
        link.href = item.href;
        link.className = 'menu-search-result d-flex align-items-center gap-3 p-3 text-decoration-none';
        if (item.target) {
          link.target = item.target;
          link.rel = 'noopener';
        }

        link.innerHTML = `
          <span class="menu-search-icon d-inline-flex align-items-center justify-content-center flex-shrink-0">
            <i class="${escapeHtml(item.icon)} fs-5"></i>
          </span>
          <span class="menu-search-text flex-grow-1">
            <span class="d-block fw-semibold text-truncate">${escapeHtml(item.title)}</span>
            <span class="d-block text-muted fs-12 text-truncate">${escapeHtml(item.parent)}</span>
          </span>
          <i class="ri-arrow-right-line text-muted"></i>
        `;

        results.appendChild(link);
      });
    };

    const search = () => {
      const term = normalizeText(input.value);
      const items = term
        ? menuItems.filter((item) => item.search.includes(term)).slice(0, 12)
        : menuItems.slice(0, 8);

      clearButton?.classList.toggle('d-none', !term);
      renderItems(items);
    };

    const renderQuickLinks = () => {
      const items = menuItems.slice(0, 8);
      quickWrap?.classList.toggle('d-none', items.length === 0);
      quickLinks.innerHTML = '';

      items.forEach((item) => {
        const link = document.createElement('a');
        link.href = item.href;
        link.className = 'menu-search-chip px-3 py-2 text-decoration-none fs-13';
        link.textContent = item.title;
        if (item.target) {
          link.target = item.target;
          link.rel = 'noopener';
        }
        quickLinks.appendChild(link);
      });
    };

    input.addEventListener('input', search);

    input.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') {
        return;
      }

      const firstLink = results.querySelector('a.menu-search-result');
      if (firstLink) {
        event.preventDefault();
        firstLink.click();
      }
    });

    clearButton?.addEventListener('click', () => {
      input.value = '';
      input.focus();
      search();
    });

    modal.addEventListener('shown.bs.modal', () => {
      renderQuickLinks();
      search();
      input.focus();
      input.select();
    });
  });
</script>


