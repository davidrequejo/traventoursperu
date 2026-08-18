$(function (e) {
  var tabla_articulo;
  // basic datatable
  $('#datatable-basic').DataTable({
    language: {
      searchPlaceholder: 'Search...',
      sSearch: '',
    },
    "pageLength": 10,
    // scrollX: true
  });
  // basic datatable

  // responsive datatable
  tabla_articulo = $('#responsiveDataTable').DataTable({
    responsive: true,
    lengthMenu: [[ -1, 5, 10, 25, 75, 100, 200,], ["Todos", 5, 10, 25, 75, 100, 200, ]],//mostramos el menú de registros a revisar
    "aProcessing": true,//Activamos el procesamiento del datatables
    "aServerSide": true,//Paginación y filtrado realizados por el servidor
    dom:"<'row'<'col-md-3'B><'col-md-3 float-left'l><'col-md-6'f>r>t<'row'<'col-md-6'i><'col-md-6'p>>",//Definimos los elementos del control de tabla
    buttons: [
      { text: '<i class="fa-solid fa-arrows-rotate" data-toggle="tooltip" data-original-title="Recargar"></i> ', className: "btn btn-info", action: function ( e, dt, node, config ) { if (tabla_articulo) { tabla_articulo.ajax.reload(null, false); } } },
      { extend: 'copy', exportOptions: { columns: [1,2,3,4,5,6], }, text: `<i class="fas fa-copy" data-toggle="tooltip" data-original-title="Copiar"></i>`, className: "btn btn-default", footer: true,  }, 
      { extend: 'excel', exportOptions: { columns: [1,2,3,4,5,6], }, title: 'Lista de articulos', text: `<i class="far fa-file-excel fa-lg" data-toggle="tooltip" data-original-title="Excel"></i>`, className: "btn btn-success", footer: true,  }, 
      { extend: 'pdf', exportOptions: { columns: [1,2,3,4,5,6], }, title: 'Lista de articulos', text: `<i class="bx bxs-file-pdf font-size-15px" data-toggle="tooltip" data-original-title="PDF"></i>`, className: "btn btn-danger", footer: false, orientation: 'landscape', pageSize: 'LEGAL',  },
      // { extend: "colvis", text: `<i class="fas fa-outdent"></i>`, className: "btn bg-gradient-gray", exportOptions: { columns: "th:not(:last-child)", }, },
    ],
    // buttons: [
    //   'copy', 'csv', 'excel', 'pdf', 'print'
    // ],
    language: {
      searchPlaceholder: 'Search...',
      sSearch: '',
    },
    "pageLength": 10,
  });
  // responsive datatable

  // responsive modal datatable
  $('#responsivemodal-DataTable').DataTable({
    responsive: {
      details: {
        display: $.fn.dataTable.Responsive.display.modal({
          header: function (row) {
            var data = row.data();
            return data[0] + ' ' + data[1];
          }
        }),
        renderer: $.fn.dataTable.Responsive.renderer.tableAll({
          tableClass: 'table'
        })
      }
    },
    language: {
      searchPlaceholder: 'Search...',
      sSearch: '',
    },
    "pageLength": 10,
  });
  // responsive modal datatable

  // file export datatable
  $('#file-export').DataTable({
    dom: 'Bfrtip',
    buttons: [
      'copy', 'csv', 'excel', 'pdf', 'print'
    ],
    language: {
      searchPlaceholder: 'Search...',
      sSearch: '',
    },
  });
  // file export datatable

  // delete row datatable
  var table = $('#delete-datatable').DataTable({
    language: {
      searchPlaceholder: 'Search...',
      sSearch: '',
    }
  });
  $('#delete-datatable tbody').on('click', 'tr', function () {
    if ($(this).hasClass('selected')) {
      $(this).removeClass('selected');
    }
    else {
      table.$('tr.selected').removeClass('selected');
      $(this).addClass('selected');
    }
  });
  $('#button').on("click", function () {
    table.row('.selected').remove().draw(false);
  });
  // delete row datatable

  // scroll vertical
  $('#scroll-vertical').DataTable({
    scrollY: '265px',
    scrollCollapse: true,
    paging: false,
    scrollX: true,
    language: {
      searchPlaceholder: 'Search...',
      sSearch: '',
    },
  });
  // scroll vertical

  // hidden columns
  $('#hidden-columns').DataTable({
    columnDefs: [
      {
        target: 2,
        visible: false,
        searchable: false,
      },
      {
        target: 3,
        visible: false,
      },
    ],
    language: {
      searchPlaceholder: 'Search...',
      sSearch: '',
    },
    "pageLength": 10,
    // scrollX: true
  });
  // hidden columns

  // add row datatable
  var t = $('#add-row').DataTable({

    language: {
      searchPlaceholder: 'Search...',
      sSearch: '',
    },
  });
  var counter = 1;
  $('#addRow').on('click', function () {
    t.row.add([counter + '.1', counter + '.2', counter + '.3', counter + '.4', counter + '.5']).draw(false);
    counter++;
  });
  // add row datatable

});

