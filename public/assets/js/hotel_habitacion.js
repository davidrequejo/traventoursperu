var tabla_hotel_habitacion;

//Función que se ejecuta al inicio
function init_hotel_habitacion() {
  
  $("#bloc_Recurso").addClass("menu-open");

  $("#mRecurso").addClass("active");

  tabla_principal_hotel_habitacion();

  $("#guardar_registro_hotel_habitacion").on("click", function (e) { if ( $(this).hasClass('send-data')==false) { $("#submit-form-hotel-habitacion").submit(); 
    
  }  });

}

//Función limpiar_form
function limpiar_form_hotel_habitacion() {
  $("#guardar_registro_hotel_habitacion").html('<i class="bx bx-save bx-tada"></i> Guardar').removeClass('disabled');

  $("#idhotel_habitacion").val("");
  $("#nombre_hotel_habitacion").val("");
  $("#precio_hotel_habitacion").val("");
  $("#descripcion_hotel_habitacion").val("");
    
  // Limpiamos las validaciones
  $(".form-control").removeClass('is-valid');
  $(".form-control").removeClass('is-invalid');
  $(".error.invalid-feedback").remove();
}

//Función Listar
function tabla_principal_hotel_habitacion() {

  tabla_hotel_habitacion = $('#tabla-hotel-habitacion').dataTable({
    lengthMenu: [[ -1, 5, 10, 25, 75, 100, 200,], ["Todos", 5, 10, 25, 75, 100, 200, ]],//mostramos el menú de registros a revisar
    "aProcessing": true,//Activamos el procesamiento del datatables
    "aServerSide": true,//Paginación y filtrado realizados por el servidor
    dom:"<'row'<'col-md-4'B><'col-md-2 float-left'l><'col-md-6'f>r>t<'row'<'col-md-6'i><'col-md-6'p>>",//Definimos los elementos del control de tabla
    buttons: [
      { text: '<i class="fa-solid fa-arrows-rotate"></i> ', className: "buttons-reload px-2 btn btn-sm btn-outline-info btn-wave ", action: function ( e, dt, node, config ) { if (tabla_hotel_habitacion) { tabla_hotel_habitacion.ajax.reload(null, false); } } },
      { extend: 'copy', exportOptions: { columns: [0,2,3], }, text: `<i class="fas fa-copy" ></i>`, className: "px-2 btn btn-sm btn-outline-dark btn-wave ", footer: true,  }, 
      { extend: 'excel', exportOptions: { columns: [0,2,3], }, title: 'Lista de planes', text: `<i class="far fa-file-excel fa-lg" ></i>`, className: "px-2 btn btn-sm btn-outline-success btn-wave ", footer: true,  }, 
      { extend: 'pdf', exportOptions: { columns: [0,2,3], }, title: 'Lista de planes', text: `<i class="far fa-file-pdf fa-lg"></i>`, className: "px-2 btn btn-sm btn-outline-danger btn-wave ", footer: false, orientation: 'landscape', pageSize: 'LEGAL',  },
      { extend: "colvis", text: `<i class="fas fa-outdent"></i>`, className: "px-2 btn btn-sm btn-outline-primary", exportOptions: { columns: "th:not(:last-child)", }, },
    ],
    ajax:{
      url: '../ajax/hotel_habitacion.php?op=tabla_hotel_habitacion',
      type : "get",
      dataType : "json",						
      error: function(e){
        console.log(e.responseText);	ver_errores(e);
      },
      complete: function () {
        $(".buttons-reload").attr('data-bs-toggle', 'tooltip').attr('data-bs-original-title', 'Recargar');
        $(".buttons-copy").attr('data-bs-toggle', 'tooltip').attr('data-bs-original-title', 'Copiar');
        $(".buttons-excel").attr('data-bs-toggle', 'tooltip').attr('data-bs-original-title', 'Excel');
        $(".buttons-pdf").attr('data-bs-toggle', 'tooltip').attr('data-bs-original-title', 'PDF');
        $(".buttons-colvis").attr('data-bs-toggle', 'tooltip').attr('data-bs-original-title', 'Columnas');
        $('[data-bs-toggle="tooltip"]').tooltip();
      },
      dataSrc: function (e) {
				if (e.status != true) {  ver_errores(e); }  return e.aaData;
			},
    },
    createdRow: function (row, data, ixdex) {
      // columna: #
      if (data[6] != '') { $("td", row).eq(6).addClass("text-center"); }
    },
		language: {
      lengthMenu: "_MENU_ ",
      buttons: { copyTitle: "Tabla Copiada", copySuccess: { _: "%d líneas copiadas", 1: "1 línea copiada", }, },
      sLoadingRecords: '<i class="fas fa-spinner fa-pulse fa-lg"></i> Cargando datos...'
    },
    "bDestroy": true,
    "iDisplayLength": 5,//Paginación
    "order": [[0, "asc"]]//Ordenar (columna,orden)
  }).DataTable();
}

//Función para guardar o editar
function guardar_y_editar_hotel_habitacion(e) {
  // e.preventDefault(); //No se activará la acción predeterminada del evento
  var formData = new FormData($("#form-agregar-hotel-habitacion")[0]);
 
  $.ajax({
    url: "../ajax/hotel_habitacion.php?op=guardar_y_editar_hotel_habitacion",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (e) {
      e = JSON.parse(e);  console.log(e);  
      if (e.status == true) {
        Swal.fire("Correcto!", "Hotel registrado correctamente.", "success");
	      tabla_hotel_habitacion.ajax.reload(null, false);         
				limpiar_form_hotel_habitacion();
        $("#modal-agregar-hotel-habitacion").modal("hide");        
			}else{
				ver_errores(e);
			}
      $("#guardar_registro_hotel_habitacion").html('<i class="bx bx-save bx-tada"></i> Guardar').removeClass('disabled send-data');
    }
  });
}

function mostrar_hotel(idhotel_habitacion) {
  $(".tooltip").remove();
  $("#cargando-18-fomulario").hide();
  $("#cargando-17-fomulario").show();
  
  limpiar_form_hotel_habitacion();

  $("#modal-agregar-hotel-habitacion").modal("show")

  $.post("../ajax/hotel_habitacion.php?op=mostrar_hotel", { idhotel_habitacion: idhotel_habitacion }, function (e, status) {

    e = JSON.parse(e);  console.log(e);  

    if (e.status == true) {
      $("#idhotel_habitacion").val(e.data.idhotel_habitacion );
      $("#nombre_hotel_habitacion").val(e.data.nombre);    
      $("#precio_hotel_habitacion").val(e.data.precio);  
      $("#descripcion_hotel_habitacion").val(e.data.descripcion);    


      $("#cargando-17-fomulario").show();
      $("#cargando-18-fomulario").hide();
    } else {
      ver_errores(e);
    }
    
  }).fail( function(e) { ver_errores(e); } );
}

//Función para desactivar registros
function eliminar_hotel(idplan, nombre) {

  crud_eliminar_papelera(
    "../ajax/hotel_habitacion.php?op=desactivar_hotel",
    "../ajax/hotel_habitacion.php?op=eliminar_hotel", 
    idplan, 
    "!Elija una opción¡", 
    `<b class="text-danger"><del>${nombre}</del></b> <br> En <b>papelera</b> encontrará este registro! <br> Al <b>eliminar</b> no tendrá acceso a recuperar este registro!`, 
    function(){ sw_success('♻️ Papelera! ♻️', "Tu registro ha sido reciclado." ) }, 
    function(){ sw_success('Eliminado!', 'Tu registro ha sido Eliminado.' ) }, 
    function(){ tabla_hotel_habitacion.ajax.reload(null, false); },
    false, 
    false, 
    false,
    false
  );

}


$(document).ready(function () {
    init_hotel_habitacion();
});

$(function () {

  $("#form-agregar-hotel-habitacion").validate({
    rules: {
      nombre_hotel_habitacion: { required: true,  maxlength: 60,  } ,     // terms: { required: true },
      precio_hotel_habitacion: { required: true,  maxlength: 60,  } ,  
      descripcion_hotel_habitacion: { required: false,  maxlength: 200,  } ,  
            
    },
    messages: {
      nombre_hotel_habitacion: {  required: "Campo requerido.", },
      precio_hotel_habitacion: {  required: "Campo requerido. ",},
     
    },
        
    errorElement: "span",

    errorPlacement: function (error, element) {
      error.addClass("invalid-feedback");
      element.closest(".form-group").append(error);
    },

    highlight: function (element, errorClass, validClass) {
      $(element).addClass("is-invalid").removeClass("is-valid");
    },

    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass("is-invalid").addClass("is-valid");   
    },
    submitHandler: function (e) { 
      $(".modal-body").animate({ scrollTop: $(document).height() }, 600); // Scrollea hasta abajo de la página
      guardar_y_editar_hotel_habitacion(e);      
    },

  });
});

