var errores_list = [];

/*  ══════════════════════════════════════════ - C R U D - ══════════════════════════════════════════ */

function crud_listar_tabla(url, nombre_modulo) {
  tabla = $("#tabla_" + nombre_modulo).DataTable({
    responsive: true,
    aProcessing: true, //Activamos el procesamiento del datatables
    aServerSide: true, //Paginación y filtrado realizados por el servidor
    dom: "rtip", //Definimos los elementos del control de tabla
    ajax: {
      url: url,
      type: "get",
      dataType: "json",
      error: function (e) {
        console.log(e.responseText);
      },
    },
    bDestroy: true,
    iDisplayLength: 10, //Paginación
    order: [[0, "asc"]], //Ordenar (columna,orden)
    language: {
      responsive: true,
      url: "/recursos/datable.rs/js/idioma.json",
    },
    // fixedHeader: true
  });
  /* Data table FullResponsive */
  new $.fn.dataTable.FixedHeader(tabla);

  return tabla;
}

/**
 * Funcion para mostarar datos en un SELECT por ejemplo:
 *
 * @param {text} url              ajax/ajax_general.php?op=select2.
 * @param {text} nombre_input     #idpersona.
 * @param {number} id_tabla       idpersona.
 * @param {text} span_charge      .charge_idpersona
 * @param {text} callback_charge  &lt;i class="fas fa-spinner fa-pulse fa-lg text-danger"&gt;&lt;/i&gt;.
 * @param {text} callback_true    'Cargado'.
 * @returns {html} html lista de: &lt;option value="5" &gt;Juan Paz&lt;/option&gt;
*/ 
    
function lista_select2(url, nombre_input, id_tabla, span_charge = null, callback_charge = "", callback_true = '') {
  if (span_charge == null || span_charge == '' || span_charge == false ) { /*No hacemos nada*/ } else {
    if (callback_charge == null || callback_charge == '' || callback_charge == false ) { 
      $(span_charge).html('<div class="spinner-border spinner-border-sm" role="status"></div>'); } else {  $(span_charge).html(callback_charge);
    }    
  }
  $.get(url, function (e, status) {
    try {
      e = JSON.parse(e);   //console.log(e);
      // console.log(url, nombre_input, id_tabla);
      if (e.status==true) {
        $(nombre_input).html(e.data); 
        if ( !id_tabla || id_tabla == "NaN" || id_tabla == "" || id_tabla == null || id_tabla == "Infinity" || id_tabla === undefined) {
          $(nombre_input).val(null).trigger("change");  
        }else if( id_tabla == '_auto_'){ 

        } else {  
          $(nombre_input).val(id_tabla).trigger("change");
        }
        if (span_charge == null || span_charge == '' || span_charge == false ) { } else {  $(span_charge).html(callback_true); }
      } else {
        ver_errores(e);
      }
    } catch (err) { console.log('Error: ', err.message); toastr.error('<h5 class="font-size-16px">Error temporal!!</h5> puede intentalo mas tarde, o comuniquese con <i><a href="tel:+51921305769" >921-305-769</a></i> ─ <i><a href="tel:+51921487276" >921-487-276</a></i>'); }      

  }).fail( function(e) { ver_errores(e); } );
}

function lista_selectChoice(url, nombre_input, id_tabla, span_charge = null, callback_charge = "", callback_true = '') {
  if (span_charge == null || span_charge == '' || span_charge == false ) { /*No hacemos nada*/ } else {
    if (callback_charge == null || callback_charge == '' || callback_charge == false ) { 
      $(span_charge).html('<div class="spinner-border spinner-border-sm" role="status"></div>'); } else {  $(span_charge).html(callback_charge);
    }    
  }
  $.get(url, function (e, status) {
    try {
      e = JSON.parse(e);   //console.log(e);
      // console.log(url, nombre_input, id_tabla);
      if (e.status==true) {
        
        nombre_input.setChoices(e.data);
        
        if ( !id_tabla || id_tabla == "NaN" || id_tabla == "" || id_tabla == null || id_tabla == "Infinity" || id_tabla === undefined) {
          /*nombre_input.setChoiceByValue(null);*/   } else {  nombre_input.setChoiceByValue(id_tabla);
        }
        if (span_charge == null || span_charge == '' || span_charge == false ) { } else {  $(span_charge).html(callback_true); }
      } else {
        ver_errores(e);
      }
    } catch (err) { console.log('Error: ', err.message); toastr.error('<h5 class="font-size-16px">Error temporal!!</h5> puede intentalo mas tarde, o comuniquese con <i><a href="tel:+51921305769" >921-305-769</a></i> ─ <i><a href="tel:+51921487276" >921-487-276</a></i>'); }      

  }).fail( function(e) { ver_errores(e); } );
}


function crud_guardar_editar_card_xhr( url, formData, name_progress, table_reload_1, table_reload_2 = false, table_reload_3 = false, table_reload_4 = false, table_reload_5 = false, table_reload_6 = false, table_reload_7 = false, table_reload_8 = false, table_reload_9 = false) {
  //event.preventDefault();
  $("#div_barra_progress_" + name_progress).show();

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {

      try {
        datos = JSON.parse(datos); console.log(datos);

        if (datos.status == true) { 

          if (table_reload_1) { table_reload_1(); }
          if (table_reload_2) { table_reload_2(); }
          if (table_reload_3) { table_reload_3(); }
          if (table_reload_4) { table_reload_4(); }
          if (table_reload_5) { table_reload_5(); }

        } else {
          ver_errores(datos); 
        }
      } catch (err) { console.log('Error: ', err.message); toastr.error('<h5 class="font-size-16px">Error temporal!!</h5> puede intentalo mas tarde, o comuniquese con <i><a href="tel:+51921305769" >921-305-769</a></i> ─ <i><a href="tel:+51921487276" >921-487-276</a></i>'); }
    },
    xhr: function () {
      var xhr = new window.XMLHttpRequest();

      xhr.upload.addEventListener( "progress", function (evt) {

        if (evt.lengthComputable) {
          var prct = (evt.loaded / evt.total) * 100;
          prct = Math.round(prct);

          $("#barra_progress_" + name_progress).css({ width: prct + "%", });

          $("#barra_progress_" + name_progress).text(prct + "%");

        }
      }, false );

      return xhr;
    },
    beforeSend: function () {
      $("#div_barra_progress_" + name_progress).show();
      $("#barra_progress_" + name_progress).css({ width: "0%",  });
      $("#barra_progress_" + name_progress).text("0%");
    },
    complete: function () {
      $("#div_barra_progress_" + name_progress).hide();
      $("#barra_progress_" + name_progress).css({ width: "0%", });
      $("#barra_progress_" + name_progress).text("0%");
    },
    error: function (jqXhr) { ver_errores(jqXhr); },
  });
}

function crud_guardar_editar_card( url, formData, table_reload_1, table_reload_2 = false, table_reload_3 = false, table_reload_4 = false, table_reload_5 = false) {
  //event.preventDefault();

  $("#div_barra_progress_" + nombre_modulo).show();

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      try {
        datos = JSON.parse(datos);

        if (datos.status == true) { 

          if (table_reload_1) { table_reload_1(); }
          if (table_reload_2) { table_reload_2(); }
          if (table_reload_3) { table_reload_3(); }
          if (table_reload_4) { table_reload_4(); }
          if (table_reload_5) { table_reload_5(); }

        } else {         
          ver_errores(datos);
        }
      } catch (err) { console.log('Error: ', err.message); toastr.error('<h5 class="font-size-16px">Error temporal!!</h5> puede intentalo mas tarde, o comuniquese con <i><a href="tel:+51921305769" >921-305-769</a></i> ─ <i><a href="tel:+51921487276" >921-487-276</a></i>'); }
    },    
    error: function (jqXhr) { ver_errores(jqXhr); },
  });
}

function crud_guardar_editar_modal_xhr( url, formData, name_progress, table_reload_1, table_reload_2 = false, table_reload_3 = false, table_reload_4 = false, table_reload_5 = false) {
  //event.preventDefault();

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      try {
        datos = JSON.parse(datos); // console.log(datos.inputt);

        if (datos.status == true) {      

          if (table_reload_1) { table_reload_1(); }
          if (table_reload_2) { table_reload_2(); }
          if (table_reload_3) { table_reload_3(); }
          if (table_reload_4) { table_reload_4(); }
          if (table_reload_5) { table_reload_5(); }

        } else {
          ver_errores(datos);
        }
      } catch (err) { console.log('Error: ', err.message); toastr.error('<h5 class="font-size-16px">Error temporal!!</h5> puede intentalo mas tarde, o comuniquese con <i><a href="tel:+51921305769" >921-305-769</a></i> ─ <i><a href="tel:+51921487276" >921-487-276</a></i>'); }     
    },
    xhr: function () {
      var xhr = new window.XMLHttpRequest();

      xhr.upload.addEventListener( "progress", function (evt) {

        if (evt.lengthComputable) {
          var prct = (evt.loaded / evt.total) * 100;
          prct = Math.round(prct);

          $(`#barra_progress_${name_progress}`).css({ width: prct + "%", });

          $(`#barra_progress_${name_progress}`).text(prct + "%");

        }
      }, false );

      return xhr;
    },
    beforeSend: function () {
      $(`#div_barra_progress_${name_progress}`).show();
      $(`#barra_progress_${name_progress}`).css({ width: "0%", });
      $(`#barra_progress_${name_progress}`).text("0%");
    },
    complete: function () {
      $(`#div_barra_progress_${name_progress}`).hide();
      $(`#barra_progress_${name_progress}`).css({ width: "0%", });
      $(`#barra_progress_${name_progress}`).text("0%");
    },
    error: function (jqXhr) { ver_errores(jqXhr); },
  });
}

function crud_guardar_editar_modal( url, formData, table_reload_1, table_reload_2 = false, table_reload_3 = false, table_reload_4 = false, table_reload_5 = false) {
  //event.preventDefault();

  $("#div_barra_progress_" + nombre_modulo).show();

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      try {
        datos = JSON.parse(datos);

        if (datos.status == true) { 

          if (table_reload_1) { table_reload_1(); }
          if (table_reload_2) { table_reload_2(); }
          if (table_reload_3) { table_reload_3(); }
          if (table_reload_4) { table_reload_4(); }
          if (table_reload_5) { table_reload_5(); }

        } else {
          ver_errores(datos);
        }
      } catch (err) { console.log('Error: ', err.message); toastr.error('<h5 class="font-size-16px">Error temporal!!</h5> puede intentalo mas tarde, o comuniquese con <i><a href="tel:+51921305769" >921-305-769</a></i> ─ <i><a href="tel:+51921487276" >921-487-276</a></i>'); }
    },
    error: function (jqXhr) { ver_errores(jqXhr); },
  });
}

function crud_guardar_editar_modal_select2_xhr( url, formData, name_progress, url_select2, input_select2, table_reload_1 = false, table_reload_2 = false, table_reload_3 = false, table_reload_4 = false, table_reload_5 = false) {
  //event.preventDefault();

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (e) {
      try {
        e = JSON.parse(e); // console.log(e.inputt);

        if (e.status == true) { 

          if (url_select2 && input_select2) { lista_select2(url_select2, input_select2, e.data); }    

          if (table_reload_1) { table_reload_1(); }
          if (table_reload_2) { table_reload_2(); }
          if (table_reload_3) { table_reload_3(); }
          if (table_reload_4) { table_reload_4(); }
          if (table_reload_5) { table_reload_5(); }

        } else {
          ver_errores(e);
        }
      } catch (err) { console.log('Error: ', err.message); toastr.error('<h5 class="font-size-16px">Error temporal!!</h5> puede intentalo mas tarde, o comuniquese con <i><a href="tel:+51921305769" >921-305-769</a></i> ─ <i><a href="tel:+51921487276" >921-487-276</a></i>'); }      
    },
    xhr: function () {
      var xhr = new window.XMLHttpRequest();
      xhr.upload.addEventListener( "progress", function (evt) {
        if (evt.lengthComputable) {
          var prct = (evt.loaded / evt.total) * 100;
          prct = Math.round(prct);
          $(`${name_progress}`).css({ width: prct + "%", });
          $(`${name_progress}`).text(prct + "%");
        }
      }, false );
      return xhr;
    },
    beforeSend: function () {
      $(`${name_progress}_div`).show();
      $(`${name_progress}`).css({ width: "0%", });
      $(`${name_progress}`).text("0%");
    },
    complete: function () {
      $(`${name_progress}_div`).hide();
      $(`${name_progress}`).css({ width: "0%", });
      $(`${name_progress}`).text("0%");
    },
    error: function (jqXhr) { ver_errores(jqXhr); },
  });
}

// Desactivar, activar, eliminar, etc...
function crud_simple_alerta(url, id_tabla, title, mensaje, text_button, callback_true, table_reload_1=false, table_reload_2=false, table_reload_3=false, table_reload_4=false,table_reload_5=false) {
  
  $(".tooltip").remove();

  Swal.fire({
    title: title,
    html: mensaje,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#d33",
    confirmButtonText: text_button,    
    preConfirm: (input) => {       
      return fetch(`${url}&id_tabla=${id_tabla}`).then(response => {
        //console.log(response);
        if (!response.ok) { throw new Error(response.statusText) }
        return response.json();
      }).catch(error => { Swal.showValidationMessage(`<b>Solicitud fallida:</b> ${error}`); })
    },
    showLoaderOnConfirm: true,
  }).then((result) => {
    console.log(result);
    if (result.isConfirmed) {
      if (result.value.status == true) {
        if (callback_true) { callback_true(); }
        if (table_reload_1) { table_reload_1(); }
        if (table_reload_2) { table_reload_2(); }
        if (table_reload_3) { table_reload_3(); }
        if (table_reload_4) { table_reload_4(); }
        if (table_reload_5) { table_reload_5(); }
        $(".tooltip").remove();
      }else{
        ver_errores(result.value);
      }
    }
  });
}

function crud_eliminar_papelera(url_papelera, url_eliminar, id_tabla, title, mensaje, callback_true_papelera, callback_true_eliminar, table_reload_1=false, table_reload_2=false, table_reload_3=false, table_reload_4=false,table_reload_5=false) {
  
  $(".tooltip").remove();

  Swal.fire({
    title: title,
    html: mensaje,
    icon: "warning",
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonColor: "#17a2b8",
    denyButtonColor: "#d33",
    cancelButtonColor: "#6c757d",    
    confirmButtonText: `<i class="fas fa-times"></i> Papelera`,
    denyButtonText: `<i class="fas fa-skull-crossbones"></i> Eliminar`,
    showLoaderOnConfirm: true,
    preConfirm: (input) => {       
      return fetch(`${url_papelera}&id_tabla=${id_tabla}`).then(response => {
        //console.log(response);
        if (!response.ok) { throw new Error(response.statusText) }
        return response.json();
      }).catch(error => { Swal.showValidationMessage(`<b>Solicitud fallida:</b> ${error}`); })
    },
    showLoaderOnDeny: true,
    preDeny: (input) => {       
      return fetch(`${url_eliminar}&id_tabla=${id_tabla}`).then(response => {
        //console.log(response);
        if (!response.ok) { throw new Error(response.statusText) }
        return response.json();
      }).catch(error => { Swal.showValidationMessage(`<b>Solicitud fallida:</b> ${error}`); })
    },
    allowOutsideClick: () => !Swal.isLoading()
  }).then((result) => {
    console.log(result);
    if (result.isConfirmed) {
      if (result.value.status == true) {
        if (callback_true_papelera) { callback_true_papelera(); }
        if (table_reload_1) { table_reload_1(); }
        if (table_reload_2) { table_reload_2(); }
        if (table_reload_3) { table_reload_3(); }
        if (table_reload_4) { table_reload_4(); }
        if (table_reload_5) { table_reload_5(); }
        $(".tooltip").remove();
      }else{
        ver_errores(result.value);
      }
    }else if (result.isDenied) {
      if (result.value.status == true) {
        if (callback_true_eliminar) { callback_true_eliminar(); }
        if (table_reload_1) { table_reload_1(); }
        if (table_reload_2) { table_reload_2(); }
        if (table_reload_3) { table_reload_3(); }
        if (table_reload_4) { table_reload_4(); }
        if (table_reload_5) { table_reload_5(); }
        $(".tooltip").remove();
      }else{
        ver_errores(result.value);
      }
    }
  });  
}

/*  ══════════════════════════════════════════ - A L E R T A S   S w e e t A l e r t 2 - ══════════════════════════════════════════ */

function sw_cancelar(title='Cancelado!', txt = "Acción cancelada.", timer = 3000, icono = "info") {
  Swal.fire({ title: title, html: txt, timer: timer, icon: icono, });
}

function sw_error(title='Error!', txt = "Acción con error.", timer = 3000, icono = "error") {
  Swal.fire({ title: title, html: txt, timer: timer, icon: icono });
}

function sw_success(title='Exito!', txt = "Acción ejecutada con éxito", timer = 3000, icono = "success") {
  Swal.fire({ title: title, html: txt, timer: timer, icon: icono });
}

function sw_warning(title='Exito!', txt = "Acción ejecutada con éxito", timer = 3000, icono = "warning") {
  Swal.fire({ title: title, html: txt, timer: timer, icon: icono });
}

function sw_question(title='Exito!', txt = "Acción ejecutada con éxito", timer = 3000, icono = "question") {
  Swal.fire({ title: title, html: txt, timer: timer, icon: icono });
}

function confirmar_formulario(flat, callback) {
  if (flat) {
    Swal.fire({ title: "Exito", timer: 2000, icon: "success", });
    if (callback) { callback(); }
  } else {
    Swal.fire({ title: "Error " + datos, timer: 2000, icon: "error", });
  }
}

/*  ══════════════════════════════════════════ - A L E R T A S   T o a s t r - ══════════════════════════════════════════ */
function toastr_error(titulo = "Error!!", mensaje = "Acción ejecutada con error.", timer_duration = 700) {
  // console.log(titulo, mensaje, timer_duration );
  toastr.error( mensaje, titulo,{"closeButton": true, "debug": false, "newestOnTop": true, "progressBar": true, "positionClass": "toast-top-right",  "preventDuplicates": false, "onclick": null,  "showDuration": timer_duration, "hideDuration": "1000",  "timeOut": "5000", "extendedTimeOut": "5000", "showEasing": "swing", "hideEasing": "linear", "showMethod": "slideDown", "hideMethod": "fadeOut" });
}

function toastr_success(titulo = "Éxito!!", mensaje = "Acción ejecutada con éxito.", timer_duration = 1700) {
  // console.log(titulo, mensaje, timer_duration );
  toastr.success( mensaje, titulo,{"closeButton": true, "debug": false, "newestOnTop": true, "progressBar": true, "positionClass": "toast-top-right",  "preventDuplicates": false, "onclick": null,  "showDuration": timer_duration, "hideDuration": "1000",  "timeOut": "5000", "extendedTimeOut": "5000", "showEasing": "swing", "hideEasing": "linear", "showMethod": "slideDown", "hideMethod": "fadeOut" });
}

function toastr_info(titulo = "Informa!!", mensaje = "Verificar esta accion.", timer_duration = 700) {
  // console.log(titulo, mensaje, timer_duration );
  toastr.info( mensaje, titulo,{"closeButton": true, "debug": false, "newestOnTop": true, "progressBar": true, "positionClass": "toast-top-right",  "preventDuplicates": false, "onclick": null,  "showDuration": timer_duration, "hideDuration": "1000",  "timeOut": "5000", "extendedTimeOut": "5000", "showEasing": "swing", "hideEasing": "linear", "showMethod": "slideDown", "hideMethod": "fadeOut", iconClass:"toast-info toast-text-black" });
}

function toastr_warning(titulo = "Alerta!!", mensaje = "Verificar esta accion.", timer_duration = 700) {
  // console.log(titulo, mensaje, timer_duration );
  toastr.warning( mensaje, titulo,{"closeButton": true, "debug": false, "newestOnTop": true, "progressBar": true, "positionClass": "toast-top-right",  "preventDuplicates": false, "onclick": null,  "showDuration": timer_duration, "hideDuration": "1000",  "timeOut": "5000", "extendedTimeOut": "5000", "showEasing": "swing", "hideEasing": "linear", "showMethod": "slideDown", "hideMethod": "fadeOut", iconClass:"toast-warning toast-text-black" });
}

/*  ══════════════════════════════════════════ - E R R O R E S - ══════════════════════════════════════════ */

function ver_errores(e) {
  console.log(e.status);
  if (e.status == 403) {
    console.group("Error"); console.warn('Error 403 -------------'); console.log(e); console.groupEnd();
    Swal.fire(`Error 403 😅!`, `<h5>Prohibido</h5> Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, "error");
  } else if (e.status == 404) {
    console.group("Error"); console.warn('Error 404 -------------'); console.log(e); console.groupEnd();
    Swal.fire(`Error 404 😅!`, `<h5>Archivo no encontrado</h5> Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, "error");
    
  } else if(e.status == 500) {
    console.group("Error"); console.warn('Error 404 -------------'); console.log(e); console.groupEnd();
    Swal.fire(`Error 500 😅!`, `<h5>Error Interno del Servidor</h5> Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, "error");

  }else if (e.status == false) {
    console.group("Error"); console.warn('Error BD -------------'); console.log(e); console.groupEnd();
    Swal.fire(`Error en la Base de Datos 😅!`, `Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, "error");
  
  }else if (e.status == 'mantenimiento') {
    console.group("Error"); console.warn('Mantenimiento -------------'); console.log(e); console.groupEnd();
    sw_error('En Mantenimiento!', `${e.message} <br> Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, 5000);  
    Swal.fire({
      title: `En Mantenimiento!`, 
      html: `<h5>${e.message}</h5> Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, 
      iconHtml: '<img src="../dist/svg/mantenimiento.svg" width="150">',
    });
  }else if (e.status == 'es_sabado') {
    console.group("Error"); console.warn('Es sabado -------------'); console.log(e); console.groupEnd();
    //sw_error('Es Sábado!', `${e.message} <br> Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, 5000);
    Swal.fire({
      title: `Es Sábado!`, 
      html: `<h5>${e.message}</h5> Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, 
      iconHtml: '<img src="../dist/svg/Jesus-Christ.svg" width="100">',
    });

  }else if (e.status == 'duplicado') {
    console.group("Error"); console.warn('Duplicado Error BD -------------'); console.log(e); console.groupEnd();
    Swal.fire(`Estos datos ya existen 😅!`, e.data, "error");  

  }else if (e.status == 'login') {
    console.warn('--- Tu sesion se ha terminado!!');
    // Swal.fire({
    //   title: '<strong>Tu sesion se ha terminado!!</strong>',
    //   icon: 'info',
    //   html: `Inicia <b>sesion</b> nuevamente , <a href="login.php">Salir.</a>`,
    //   showCloseButton: true,
    //   showCancelButton: true,
    //   focusConfirm: false,
    //   confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Salir!',
    //   confirmButtonAriaLabel: 'Thumbs up, great!',
    //   cancelButtonText: '<i class="fa fa-thumbs-down"></i>',
    //   cancelButtonAriaLabel: 'Thumbs down'
    // }).then((result) => {
    //   if (result.isConfirmed) {
    //     Swal.fire('Saliendo...', '<i class="fas fa-spinner fa-pulse"></i> Redireccionando...', 'success');
    //     window.location.href = `${window.location.host =='localhost' || es_numero(parseFloat(window.location.host)) == true ?`${window.location.origin}/corporacion_praga/`:window.location.origin}`;
    //   } else {
    //     Swal.fire('Cerrando sesion', '<i class="fas fa-spinner fa-pulse"></i> De igual manera vamos a cerrar la sesión, jijijiji...', 'success');
    //     window.location.href = `${window.location.host =='localhost' || es_numero(parseFloat(window.location.host)) == true ?`${window.location.origin}/corporacion_praga/`:window.location.origin}`;
    //   }
    // });
    var foto_perfil_login =  DocExist(`assets/modulo/persona/perfil/${localStorage.getItem("nube_foto_perfil")}`) == 200 ? localStorage.getItem("nube_foto_perfil") : 'no-perfil.jpg' ;
    Swal.fire({
      title: '<strong>Tu sesión ha terminado!!</strong>',
      imageUrl: `../assets/modulo/persona/perfil/${foto_perfil_login}`, // Reemplaza con la URL de tu imagen
      imageWidth: 150,
      imageHeight: 150,
      html: `<p>Inicia <b>sesión</b> nuevamente</p><input type="password" id="password" class="swal2-input" placeholder="Ingresa tu clave">`,
      showCloseButton: true,
      showCancelButton: true,
      focusConfirm: false,
      confirmButtonText: 'Iniciar Sesión',
      cancelButtonText: 'Cancelar',
      showLoaderOnConfirm: true, // Muestra el loader mientras se valida la clave
      preConfirm: () => {
        const password = document.getElementById('password').value;
        if (!password) { Swal.showValidationMessage('Por favor, ingresa tu clave');  return false; }// Detiene el flujo hasta que se ingrese una clave

        // Enviar la clave al servidor
        let formData = new FormData();
        formData.append("logina", localStorage.getItem("nube_login")); // Asegúrate de que el backend reciba este parámetro
        formData.append("clavea", password); // Asegúrate de que el backend reciba este parámetro
        formData.append("st", 0); // Asegúrate de que el backend reciba este parámetro

        return fetch("../ajax/usuario.php?op=verificar", { method: 'POST',  body: formData,  })
        .then(response => {
          if (!response.ok) {  throw new Error(`HTTP error! Status: ${response.status}`); }
          return response.json(); // Convertir respuesta a JSON
        })
        .then(e => {
          console.log(e);
          if (e.status == true ) {
            if (e.data.length === 0) { // Asegúrate de que el backend envíe un JSON con { success: true } si la clave es correcta
              throw new Error( 'Clave incorrecta'); // Muestra el mensaje del backend si la clave es incorrecta
            } else {
              return e; // Devuelve los datos para que el `then` de `Swal.fire().then()` los reciba            
            }
          } else {
            ver_errores(e);
          }          
        })
        .catch(error => { Swal.showValidationMessage(`<b>Error:</b> ${error.message}`); });
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        Swal.fire({
          title: '¡Bienvenido!',
          html: '<i class="fas fa-spinner fa-pulse"></i> Actualizando...',
          icon: 'success',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => Swal.showLoading()
        });
        // Redirigir al usuario después de 2 segundos (simulación)
        setTimeout(() => { location.reload(); }, 1000);// Recarga la página actual
      } else {
        Swal.fire('Cerrando sesion', '<i class="fas fa-spinner fa-pulse"></i> De igual manera vamos a cerrar la sesión, jijijiji...', 'success');
        window.location.href = `${window.location.host =='localhost' || es_numero(parseFloat(window.location.host)) == true ?`${window.location.origin}/corporacion_praga/`:window.location.origin}`;    
      }
    });  

  }else if (e.status == 'no_sucursal') {
    console.group("Error"); console.warn('No tiene sucursal -------------'); console.log(e); console.groupEnd();
    //
    Swal.fire({
      title: `No tiene sucursal!`, 
      html: `<h5>${e.message}</h5> Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, 
      iconHtml: '<img src="../dist/svg/no_sucursal.svg" width="100">',
    });

  }else if (e.status == 'nopermiso') {
    console.warn('--- Tu no tienes permiso!!');
    Swal.fire({
      title: '<strong>Ingresar a otros modulos!!</strong>',
      icon: 'info',
      html: e.message,
      showCloseButton: true,
      showCancelButton: true,
      focusConfirm: false,
      confirmButtonText: '<i class="fa fa-thumbs-up"></i> Ok!',
      confirmButtonAriaLabel: 'Thumbs up, great!',
      cancelButtonText: '<i class="fa fa-thumbs-down"></i>',
      cancelButtonAriaLabel: 'Thumbs down'
    }).then((result) => {
      if (result.isConfirmed) {        
        window.location.href = `${window.location.host =='localhost' || es_numero(parseFloat(window.location.host)) == true ?`${window.location.origin}/corporacion_praga/vistas/escritorio.php`:window.location.origin+'/vistas/escritorio.php'}`;
      } else {
        window.location.href = `${window.location.host =='localhost' || es_numero(parseFloat(window.location.host)) == true ?`${window.location.origin}/corporacion_praga/vistas/escritorio.php`:window.location.origin+'/vistas/escritorio.php'}`;
      }
    });
  
  }else if (e.status == 'error_code') {
    sw_error('Error de escritura de <b>codigo</b>!', `${e.message} <br> Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, 5000);
  }else if (e.status == 'error_usuario') {
    // sw_error(`Upss!! Estimado ${e.user}!`, `${e.message} <br> <small>─ o contacte al <a href="https://wa.link/1dpx0i" target="_blank" class="cursor-pointer text-primary" >Ing. de Sistemas</a> ─</small>`,  15000);
    Swal.fire({
      title: (typeof e.titulo === 'undefined' ?  'error' : e.titulo),
      icon: (typeof e.icon === 'undefined' ?  'error' : e.icon) ,
      html: `Estimado ${e.user}! ${e.message} <br> <small>─ o contacte al <a href="https://wa.link/1dpx0i" target="_blank" class="cursor-pointer text-primary" >Ing. de Sistemas</a> ─</small>`,      
    });
  }else if (e.status == 'error_personalizado') {
    // sw_error(`${e.titulo}`, `Estimado ${e.user}! ${e.message} <br> <small>─ o contacte al <a href="https://wa.link/1dpx0i" target="_blank" class="cursor-pointer text-primary" >Ing. de Sistemas</a> ─</small>`,  15000);
    Swal.fire({
      title: `${e.titulo}`,
      icon: (typeof e.icon === 'undefined' ?  'error' : e.icon) ,
      html: `Estimado ${e.user}! ${e.message} <br> <small>─ o contacte al <a href="https://wa.link/1dpx0i" target="_blank" class="cursor-pointer text-primary" >Ing. de Sistemas</a> ─</small>`,      
    });
  } else {
    console.group("Error"); console.warn('Error Grave -------------'); console.log(e); console.groupEnd();
    Swal.fire(`Error Grave 😱!`, `Contacte al <b>Ing. de Sistemas</b> 📞 <br> <i><a href="tel:+51921305769" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-305-769</a></i> ─ <i><a href="tel:+51921487276" data-toggle="tooltip" data-original-title="Llamar al Ing. de Sistemas.">921-487-276</a></i>`, "error");
  }
}

function alert_danger(html) {

  return (`<div class="alert alert-danger alert-dismissible fade show" role="alert" id="alert_error_cliente">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <span class="font-weight-medium">¡ERROR!</span>     
    ${html}     
  </div>`);
}

/*  ══════════════════════════════════════════ - D O W N L O A D - ══════════════════════════════════════════ */
function download_file(ruta, file, name_file) {
  const dowload_file = document.createElement('a');
  dowload_file.href =  `${ruta}${file}`;
  dowload_file.target = '_blank';
  dowload_file.download = name_file;

  document.body.appendChild(dowload_file);
  dowload_file.click();
  document.body.removeChild(dowload_file);
}

/*************************************************************/

function limpiar_form(nombre_modulo, callback) {
  $("#modal_" + nombre_modulo).modal("hide");

  if (callback) {
    callback();
  }

  /* Reiniciamos la barra */
  // reniciar_barra(nombre_modulo);
  /* Limpiamos posibles errores*/
  limpiar_errores(nombre_modulo);
}

function reniciar_barra(nombre_modulo) {
  $("#div_barra_progress_" + nombre_modulo).hide();
  $("#barra_progress_" + nombre_modulo).css({
    width: "0%",
  });
  $("#barra_progress_" + nombre_modulo).text("0%");
}

