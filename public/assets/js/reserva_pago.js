
const ReservaPagoEndpoints = Object.freeze({
  seriesComprobante: '/reservas/catalogos/series-comprobante',
  clientes: '/reservas/catalogos/clientes',
  clienteNumeroDocumento: function (clienteId) {
    return `/reservas/clientes/${clienteId}/numero-documento`;
  },
});
function apiUrlReserva(path) {
  const base = document.querySelector('meta[name="app-url"]')?.content || '';
  return base + path;
}
// ::::::::::::::::::::::::::::::::::::::::::::: MOSTRAR SERIES :::::::::::::::::::::::::::::::::::::::::::::

function ver_series_comprobante(input, selected = null, selectedLabel = null) {
  
  $("#f_serie_comprobante").html('');
  $(".f_charge_serie_comprobante").html(`<div class="spinner-border spinner-border-sm" role="status"></div>`);

  var tipo_comprobante = $(input).val() == ''  || $(input).val() == null ? '' : $(input).val(); console.log(tipo_comprobante);  

  $('#f_tipo_comprobante_hidden').val(tipo_comprobante);

  // VALIDANDO SEGUN: TIPO DE COMPROBANTE    
  
  if ( tipo_comprobante == '01') {   
    $("#f_idsunat_c01").val(1); // Asginamos el ID manualmente de: sunat_c01_tipo_comprobante FACTURA
    $("#p_idpersona_cliente").val("").trigger("change");     
    
  } else if ( tipo_comprobante == '03') {
    $("#f_idsunat_c01").val(3); // Asginamos el ID manualmente de: sunat_c01_tipo_comprobante BOLETA

  } else if ( tipo_comprobante == '12') {
    $("#f_idsunat_c01").val(12); // Asginamos el ID manualmente de: sunat_c01_tipo_comprobante TICKET     
    
  }  

  return $.getJSON(apiUrlReserva(ReservaPagoEndpoints.seriesComprobante), { tipo_comprobante: tipo_comprobante },  function (e, status) {    
    if (e.status == true) {   
        
      $("#f_serie_comprobante").html(e.data);
      if (selected !== null && selected !== undefined && selected !== '') {
        $("#f_serie_comprobante").val(String(selected)).trigger("change");
        if (!$("#f_serie_comprobante").val() && selectedLabel) {
          $("#f_serie_comprobante").append(new Option(selectedLabel, String(selected), true, true)).trigger("change");
        }
      } else {
        var primeraSerie = $("#f_serie_comprobante option[value!='']").first().val() || null;
        $("#f_serie_comprobante").val(primeraSerie).trigger("change");
      }
      $(".f_charge_serie_comprobante").html('');
      
    } else { ver_errores(e); }
  }).fail( function(e) { ver_errores(e); } );

  cambio_de_tipo_comprobante = tipo_comprobante;
}


// ::::::::::::::::::::::::::::::::::::::::::::: CLIENTE VALIDO :::::::::::::::::::::::::::::::::::::::::::::
function es_valido_cliente() {

  var id_cliente = $('#p_idpersona_cliente').val() == ''  || $('#p_idpersona_cliente').val() == null ? '' : $('#p_idpersona_cliente').val();
  $(".span_dia_cancelacion").html(``);

  if (id_cliente != null && id_cliente != '') {

    var tipo_comprobante = $('#f_tipo_comprobante_hidden').val() == ''  || $('#f_tipo_comprobante_hidden').val() == null ? '' : $('#f_tipo_comprobante_hidden').val();    if (!$('#p_idpersona_cliente').hasClass('select2-hidden-accessible')) {
      return;
    }

    var clienteData = $('#p_idpersona_cliente').select2('data');
    var clienteOption = clienteData && clienteData[0] && clienteData[0].element ? clienteData[0].element : null;
    if (!clienteOption) {
      return;
    }

    var tipo_documento    = clienteOption.getAttribute('tipo_documento') || '';
    var numero_documento  = clienteOption.getAttribute('numero_documento') || '';
    var direccion         = clienteOption.getAttribute('direccion') || '';  
    var dia_cancelacion = '';  
    var campos_requeridos = ""; 
    var es_valido = true; 
    if (tipo_comprobante == '01') {       // FACTURA
      
      if ( tipo_documento == '6'  ) { }else{ campos_requeridos = campos_requeridos.concat(`<li>Tipo de Documento: RUC</li>`);  }
      if ( numero_documento != '' ) { }else{ campos_requeridos = campos_requeridos.concat(`<li>Numero de Documento</li>`);  }
      if ( direccion != '' ) {    }else{  campos_requeridos = campos_requeridos.concat(`<li>Direccion</li>`);  }
      if (tipo_documento == '6' && numero_documento != '' && direccion != '' ) {  es_valido = true;  } else {   es_valido = false; }

    } else if (tipo_comprobante == '03' || id_cliente == '1') {  // BOLETA
      
      if ( tipo_documento == '1' || tipo_documento == '6' ) {  }else{  campos_requeridos = campos_requeridos.concat(`<li>Tipo de Documento: DNI o RUC</li>`);  }
      if ( numero_documento != '' ) {  }else{  campos_requeridos = campos_requeridos.concat(`<li>Numero de Documento</li>`);  }
      if ( direccion == '' || direccion == null ) {  campos_requeridos = campos_requeridos.concat(`<li>Direccion</li>`);  }else{    }
      if ( (tipo_documento == '1' || tipo_documento == '6' || tipo_documento == '0' ) && numero_documento != ''  ) { es_valido = true; } else {  es_valido = false; }

    } else if (tipo_comprobante == '12' ) { // TICKET
      es_valido = true;
    }

    if (es_valido == true) {
     
    } else {

      if (tipo_comprobante == '03' && tipo_documento == '0' ) {
        Swal.fire({
          title: "Desea emitir Boleta?",
          html: "Si deseas emitir Boleta sin DNI, actualiza el numero con 8 ceros: 00000000, o ingrese el DNI correcto del cliente.",
          input: "text",
          inputValue: '00000000',
          inputAttributes: { autocapitalize: "off" },
          showCancelButton: true,
          confirmButtonText: "Actualizar DNI",
          showLoaderOnConfirm: true,
          preConfirm: async (numero_documento) => {
            try {
              var id_cliente = $("#p_idpersona_cliente").select2('val') == null ? '' : $("#p_idpersona_cliente").select2('val');
              const UrlUpdate_client = apiUrlReserva(ReservaPagoEndpoints.clienteNumeroDocumento(id_cliente));
              const response = await fetch(UrlUpdate_client, {
                method: 'PATCH',
                headers: {
                  'X-CSRF-TOKEN': typeof csrfReserva === 'function' ? csrfReserva() : document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                  'Accept': 'application/json',
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({ numero_documento }),
              });
              if (!response.ok) {
                return Swal.showValidationMessage(` ${JSON.stringify(await response.json())} `);
              }
              return response.json();
            } catch (error) {
              Swal.showValidationMessage(`<b>Solicitud fallida:</b> ${error}`);
            }
          },
          allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
          if (result.isConfirmed) {
            var id_cliente = $("#p_idpersona_cliente").select2('val') == null ? '' : $("#p_idpersona_cliente").select2('val');
            lista_select2(apiUrlReserva(ReservaPagoEndpoints.clientes), '#p_idpersona_cliente', id_cliente);
            sw_success('Datos Actualizado!!', 'Se actualizado el Nro Documento correctamente');
          }else{
            $("#p_idpersona_cliente").val('').trigger('change'); 
            $(".span_dia_cancelacion").html(``);
          }
        });
      } else {
        sw_cancelar('Cliente no permitido', `El cliente no cumple con los siguientes requsitos:  <ul class="pt-3 text-left font-size-13px"> ${campos_requeridos} </ul>`, 10000);
        $("#p_idpersona_cliente").val('').trigger('change'); 
        $(".span_dia_cancelacion").html(``);
      }
      
    }   
    
    console.log(tipo_comprobante, tipo_documento, numero_documento, direccion, es_valido);
  }
}


// .....::::::::::::::::::::::::::::::::::::: S E C C I O N   M E T O D O   D E   P A G O   :::::::::::::::::::::::::::::::::::::::..

function capturar_pago_venta(id) {   
  
  var metodo_pago = $(`#f_metodo_pago_${id}`).val() == null || $(`#f_metodo_pago_${id}`).val() == "" ? "" : $(`#f_metodo_pago_${id}`).val() ; //console.log(metodo_pago);
  
  $(`.span-code-baucher-pago-${id}`).html(`(${metodo_pago == null ? 'Seleccione metodo pago' : metodo_pago })`);  
  
  if (metodo_pago == null || metodo_pago == '' || metodo_pago == "EFECTIVO" || metodo_pago == "CREDITO") {
    $(`#content-metodo-pago-${id}`).hide();    
  } else if ( metodo_pago == "MIXTO" ) {
    $(`#content-metodo-pago-${id}`).show();       
  } else {    
    $(`#content-metodo-pago-${id}`).show();    
  }  

}

function calcular_saldo_restante() {  
  var total_amortizar = $('#total_amortizar').val() == '' || $('#total_amortizar').val() == null ? 0 : parseFloat($('#total_amortizar').val());
  var monto_amortizar = $('#monto_amortizar').val() == '' || $('#monto_amortizar').val() == null ? 0 : parseFloat($('#monto_amortizar').val());
  var saldo_restante = total_amortizar - monto_amortizar;
  $('#saldo_amortizar').val(saldo_restante.toFixed(2));
  $('#f_venta_subtotal').val(monto_amortizar.toFixed(2));
}

