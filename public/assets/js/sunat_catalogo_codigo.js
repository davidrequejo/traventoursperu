let tablasCatalogoSunat = [];

$(function () {
  inicializarTablasCatalogoSunat();

  $('#catalogo-tabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
    ajustarTablasCatalogoSunat();
  });
});

function inicializarTablasCatalogoSunat() {
  tablasCatalogoSunat = $('.catalogo-table').map(function () {
    return $(this).DataTable({
      responsive: true,
      processing: true,
      deferRender: true,
      autoWidth: false,
      pageLength: 10,
      lengthMenu: [5, 10, 25, 50, 100],
      dom: "<'row align-items-center mb-2'<'col-xl-8 col-lg-7 col-md-12'f><'col-xl-4 col-lg-5 col-md-12 mt-2 mt-lg-0 d-flex justify-content-lg-end'l>>" +
        "rt" +
        "<'row align-items-center mt-3'<'col-md-6'i><'col-md-6'p>>",
      language: {
        lengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Buscar en este catalogo',
        zeroRecords: 'No se encontraron registros.',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Sin registros disponibles',
        infoFiltered: '(filtrado de _MAX_ registros)',
        processing: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...',
        paginate: {
          first: 'Primero',
          last: 'Ultimo',
          next: 'Siguiente',
          previous: 'Anterior',
        },
      },
      order: [[0, 'asc']],
    });
  }).get();

  ajustarTablasCatalogoSunat();
}

function ajustarTablasCatalogoSunat() {
  tablasCatalogoSunat.forEach(function (tabla) {
    tabla.columns.adjust().responsive.recalc();
  });
}
