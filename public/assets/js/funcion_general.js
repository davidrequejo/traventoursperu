/*  ══════════════════════════════════════════ - F E C H A S - ══════════════════════════════════════════ */

function sumar_mes(m = 1, fecha) { var date = new Date(fecha);   return new Date( date.setMonth(date.getMonth() + m ) ).toISOString().slice(0, 10); }

// Función que suma o resta días a la fecha indicada
sumaFecha = function(d, fecha){
  var Fecha = new Date();
  var sFecha = fecha || (Fecha.getDate() + "/" + (Fecha.getMonth() +1) + "/" + Fecha.getFullYear());
  var sep = sFecha.indexOf('/') != -1 ? '/' : '-';
  var aFecha = sFecha.split(sep);
  var fecha = aFecha[2]+'/'+aFecha[1]+'/'+aFecha[0];
  fecha= new Date(fecha);
  fecha.setDate(fecha.getDate()+parseInt(d));
  var anno=fecha.getFullYear();
  var mes= fecha.getMonth()+1;
  var dia= fecha.getDate();
  mes = (mes < 10) ? ("0" + mes) : mes;
  dia = (dia < 10) ? ("0" + dia) : dia;
  var fechaFinal = dia+sep+mes+sep+anno;
  return (fechaFinal);
}

function sumar_dias_moment(d, fecha) { return moment(fecha).add(d, 'days').format('YYYY-MM-DD'); }
function sumar_meses_moment(d, fecha) { return moment(fecha).add(d, 'months').format('YYYY-MM-DD'); }

// Extrae los nombres de dias de semana "Abreviado"
function extraer_dia_semana_number_moment(fecha) { return moment(fecha, "YYYY-MM-DD").day(); }
function extraer_semana_anio_number_moment(fecha) { return moment(fecha, "YYYY-MM-DD").week(); }

// Extrae los nombres de dias de semana "Abreviado"
function extraer_dia_semana(fecha) {
  const fechaComoCadena = fecha; // día fecha
  const dias = ['lu', 'ma', 'mi', 'ju', 'vi', 'sa', 'do']; //
  const numeroDia = new Date(fechaComoCadena).getDay();
  const nombreDia = dias[numeroDia];
  return nombreDia;
}

// extrae los nombres de dias de semana "Completo"
function extraer_dia_semana_completo(fecha) {

  var nombreDia = "";

  if (fecha == '' || fecha == null || fecha == '0000-00-00') { nombreDia = "-"; } else {
    const fechaComoCadena = fecha; // día fecha
    const dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']; //
    const numeroDia = new Date(fechaComoCadena).getDay();
    nombreDia = dias[numeroDia];
  }
  return nombreDia;
}

function extraer_dia_mes(fecha) {
  var dia_mes = "";
  if (fecha == '' || fecha == null || fecha == '0000-00-00') {  } else { dia_mes = parseFloat(moment(fecha).format('DD')); }
  return dia_mes;
}

function extraer_mes_number(fecha) {
  var dia_mes = "";
  if (fecha == '' || fecha == null || fecha == '0000-00-00') {  } else { dia_mes = moment(fecha).format('MM'); }
  return dia_mes;
}

function extraer_year(fecha) {
  var dia_mes = "";
  if (fecha == '' || fecha == null || fecha == '0000-00-00') {  } else { dia_mes = moment(fecha).format('YYYY'); }
  return dia_mes;
}

function extraer_ultimo_dia_mes(fecha) {
  var ultimo_dia_mes = "";
  if (fecha == '' || fecha == null || fecha == '0000-00-00') {  } else {
    ultimo_dia_mes = moment(fecha).endOf("month").format('DD-MM-YYYY');
  }
  return ultimo_dia_mes;
}

function extraer_nombre_mes(fecha) {

  var nombre_completo = "";

  if (fecha == '' || fecha == null || fecha == '0000-00-00') { nombre_completo = "-"; } else {
    const array_mes = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    
    let date = new Date(fecha.replace(/-+/g, '/'));
      
    var mes_indice = date.getMonth();

    nombre_completo = array_mes[mes_indice];
  }

  return nombre_completo;
}

// convierte de una fecha(aa-mm-dd): 2021-12-23 a una fecha(dd-mm-aa): 23-12-2021
function format_d_m_a(fecha, ext= '-') {
  var format = "";
  if (fecha == '' || fecha == null || fecha == '0000-00-00') { format = "-"; } else {
    let splits = fecha.split("-"); //console.log(splits);
    format = `${splits[2]}${ext}${splits[1]}${ext}${splits[0]}`;
  } 
  return format;
}

// convierte de una fecha(aa-mm-dd): 23-12-2021 a una fecha(dd-mm-aa): 2021-12-23
function format_a_m_d(fecha) {
  var format = "";
  if (fecha == '' || fecha == null || fecha == '00-00-0000') { format = "-"; } else {
    let splits = fecha.split("-"); //console.log(splits);
    format = `${splits[2]}-${splits[1]}-${splits[0]}`;
  } 
  return format;
}

// convierte de una fecha(mm-dd-aa): 23-12-2021 a una fecha(mm-dd-aa): 12-23-2021
function format_m_d_a(fecha) {
  var format = "";
  if (fecha == '' || fecha == null || fecha == '00-00-0000') { format = "-"; } else {
    let splits = fecha.split("-"); //console.log(splits);
    format = `${splits[1]}-${splits[0]}-${splits[2]}`;
  } 
  return format;
}

// convierte de una fecha(aa-mm-dd): 2021-12-23 a una fecha(aa-mm): 2021-12
function format_a_m(fecha) {
  var format = "";
  if (fecha == '' || fecha == null || fecha == '00-00-0000') { format = "-"; } else {
    format = moment(fecha).format('YYYY-MM');
  } 
  return format;
}

// convierte de una fecha(aa-mm-dd): 23-12-2021 a una fecha(aa-mm): 12-2021
function format_m_a(fecha) {
  var format = "";
  if (fecha == '' || fecha == null || fecha == '00-00-0000') { format = "-"; } else {
    format = moment(fecha).format('MM-YYYY');
  } 
  return format;
}

// restringimos la fecha para no elegir mañana
function no_select_tomorrow(nombre_input) {  
  var today = moment().format('YYYY-MM-DD');  
  $(nombre_input).attr('max',today);
}

// restringimos la fecha para no elegir mañana
function restrigir_fecha_ant(nombre_input,fecha_minima) {
  console.log(nombre_input,fecha_minima);
  var today2 = new Date(fecha_minima);
  var dd2 = today2.getDate()+1;
  var mm2 = today2.getMonth()+1; //January is 0!
  var yyyy2 = today2.getFullYear();
  
  if(dd2<10){ dd2='0'+dd2  }
  if(mm2<10){ mm2='0'+mm2 }
  
  today2 = yyyy2+'-'+mm2+'-'+dd2;
  console.log(today2);

  $(nombre_input).attr('min',today2);
  $(nombre_input).rules("add", { min: today2, messages: { min: `Ingresa una fecha mayor a: ${format_d_m_a(today2)}` } });
}

// calulamos la cantidad de dias de una mes especifico
function cantidad_dias_mes(year, month) {
  var diasMes = new Date(year, month, 0).getDate();
  return diasMes;
}
function cant_dias_mes(date_anio, date_mes) {
	var año = date_anio;
  var mes = date_mes;

  if (date_anio == '' || date_anio == null || date_mes =='' || date_mes ==null ) {
    return '';
  } else {
    var diasMes = new Date(año, mes, 0).getDate();
    var diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    
    // for (var dia = 1; dia <= diasMes; dia++) {
    //   // Ojo, hay que restarle 1 para obtener el mes correcto
    //   var indice = new Date(año, mes - 1, dia).getDay();
    //   console.log(`El día número ${dia} del mes ${mes} del año ${año} es ${diasSemana[indice]}`);
    // }
    return diasMes;
  }   
  
}

function fecha_dentro_de_rango(fecha, rango_inicial, rango_final) {

  var fechar_validar = new Date(fecha);
  var f1 = new Date(rango_inicial);
  var f2 = new Date(rango_final);

  //nos aseguramos que no tengan hora
  fechar_validar.setHours(0,0,0,0);
  f1.setHours(0,0,0,0);
  f2.setHours(0,0,0,0);
 
  // validamos las fechas con un IF
  if (fechar_validar.getTime() >= f1.getTime() && fechar_validar.getTime() <= f2.getTime() ){
    return true;
  }

  return false;
}

function validarFechaEnRango(fechaI, fechaF, fechaV){
  
  const fechaInicio=new Date(fechaI);
  const fechaFin=new Date(fechaF);
  const fechaValidar=new Date(fechaV);

  const fechaInicioMs = fechaInicio.getTime();
  const fechaFinMs = fechaFin.getTime();
  const fechaValidarMs = fechaValidar.getTime();

  if(fechaValidarMs >= fechaInicioMs && fechaValidarMs <= fechaFinMs){
    //console.log(fechaI, fechaF, fechaV + ' este es');
    return true;
  }else{
    //console.log(fechaI, fechaF, fechaV+ ' este no es');
    return false;
  }
}

function validar_fecha_igual(f1, f2) { return moment(f1).isSame(f2); }

function validarFechaMayorIgualQue(fecha_1, fecha_2) {
  const fecha_a=new Date(fecha_1);
  const fecha_b=new Date(fecha_2);
  if(fecha_a >= fecha_b ){
    //console.log(fechaI, fechaF, fechaV + ' este es');
    return true;
  }else{
    //console.log(fechaI, fechaF, fechaV+ ' este no es');
    return false;
  }
}

function validarFechaMenorIgualQue(fecha_1, fecha_2) {
  const fecha_a=new Date(fecha_1);
  const fecha_b=new Date(fecha_2);
  if(fecha_a <= fecha_b ){
    //console.log(fechaI, fechaF, fechaV + ' este es');
    return true;
  }else{
    //console.log(fechaI, fechaF, fechaV+ ' este no es');
    return false;
  }
}

function valida_fecha_menor_que(fecha_menor, fecha_mayor) {
  var f1 = new Date(fecha_menor); //fecha "fecha_menor" parseado a "Date()"
  var f2 = new Date(fecha_mayor); //fecha "fecha_mayor" parseado a "Date()"

  var estado = false;

  //nos aseguramos que no tengan hora
  f1.setHours(0,0,0,0);
  f2.setHours(0,0,0,0);

  // validamos las fechas con un IF
  if (f1.getTime() < f2.getTime()){  estado = true; }

  return estado;
}

function validarFechaMenorQue(fecha_1, fecha_2) {
  const fecha_a=new Date(fecha_1);
  const fecha_b=new Date(fecha_2);
  if(fecha_a < fecha_b ){
    //console.log(fechaI, fechaF, fechaV + ' este es');
    return true;
  }else{
    //console.log(fechaI, fechaF, fechaV+ ' este no es');
    return false;
  }
}

function diferencia_de_dias(fecha_i, fecha_f) {
  var fecha1 = moment(fecha_i);
  var fecha2 = moment(fecha_f); 
  return fecha2.diff(fecha1, 'days');
}

/*  ══════════════════════════════════════════ - N U M E R I C O S - ══════════════════════════════════════════ */
// Formato de miles a INPUT
function formato_miles_input (nombre_input) {  
  // input con comas de miles
  $(nombre_input).on({
    focus: function (event) {
      $(event.target).select();
    },
    keyup: function (event) {
      $(event.target).val(function (index, value) {
        return value.replace(/\D/g, "").replace(/([0-9])([0-9]{2})$/, "$1.$2").replace(/\B(?=(\d{3})+(?!\d)\.?)/g, ",");
      });
    },
  });
}

//variables globales para definir el separador de millares y decimales
//Para coma millares y punto en decimales (USA)
const DECIMALES = ".";
// cambiar por "," para coma decimal y punto en millares (ESPAÑA)
const INFLOCAL = DECIMALES === "." ? new Intl.NumberFormat("en-US") : new Intl.NumberFormat("es-ES");
//============================================================================
let regexpInteger = new RegExp("[^0-9]", "g");
let regexpNumber = new RegExp("[^0-9" + "\\" + DECIMALES + "]", "g");
//============================================================================

// Formatear numeros decimales indistintamente tanto positivos como negativos
function numberFormatIndistinto(e) {
  if (this.value !== "") {
    //ver si el primer caracter es el simbolo minus "-"
    let caracterInicial = this.value.substring(0, 1);
    //si hay caracter negativo al inicio se quita del proceso de formateo
    //se filtra el contenido de caracteres no admisibles
    //se divide el numero entre la parte entera y la parte decimal
    let contenido = caracterInicial === "-" ? this.value.substring(1, this.value.length).replace(regexpNumber, "").split(DECIMALES) : this.value.replace(regexpNumber, "").split(DECIMALES);
    // añadimos los separadores de miles a la parte entera del numero
    contenido[0] = contenido[0].length ? INFLOCAL.format(parseInt(contenido[0])) : "0";
    // Juntamos el numero con los decimales si hay decimales
    this.value = contenido.length > 1 ? contenido.slice(0, 2).join(DECIMALES) : contenido[0];
    // Juntamos el signo "-" minus si existe
    if (caracterInicial === "-") {
      this.value = caracterInicial + this.value;
    }
    //damos color rojo si numero negativo
    // this.className = this.value.substring(0, 1) !== "-" ? "numberIndistinto numero_positivos" : "numberIndistinto numero_negativos";
    if (this.value.substring(0, 1) !== "-") {
      this.classList.remove('numberIndistinto', 'numero_negativos'); this.classList.add('numberIndistinto', 'numero_positivos');
    }else{
      this.classList.remove('numberIndistinto', 'numero_positivos'); this.classList.add('numberIndistinto', 'numero_negativos');
    }
  }
}

// Formatear numeros decimales indistintamente tanto positivos como negativos con solo 2 decimales
function numberFormatIndistintoFixed (nombre_input) {  
  console.log('holaaaaaa');
  if (this.value !== "") {
    
    //ver si el primer caracter es el simbolo minus "-"
    let caracterInicial = this.value.substring(0, 1);
    //si hay caracter negativo al inicio se quita del proceso de formateo
    //se filtra el contenido de caracteres no admisibles
    //se divide el numero entre la parte entera y la parte decimal
    let contenido = caracterInicial === "-" ? this.value.substring(1, this.value.length).replace(regexpNumber, "").split(DECIMALES) : this.value.replace(regexpNumber, "").split(DECIMALES);
    //ver si hay ya 2 decimales introducidos
    if (contenido.length > 1) {
      if (contenido[1].length > 2) {
        contenido[1] = contenido[1].substring(0, contenido[1].length - 1);
      }
    }
    // añadimos los separadores de miles a la parte entera del numero
    contenido[0] = contenido[0].length ? INFLOCAL.format(parseInt(contenido[0])) : "0";
    // Juntamos el numero con los decimales si hay decimales
    this.value = contenido.length > 1 ? contenido.slice(0, 2).join(DECIMALES) : contenido[0];
    // Juntamos el signo "-" minus si existe
    if (caracterInicial === "-") {
      this.value = caracterInicial + this.value;
    }
    //damos color rojo si numero negativo
    // this.className = this.value.substring(0, 1) !== "-" ? "numberIndistinto numero_positivos" : "numberIndistinto numero_negativos";
    if (this.value.substring(0, 1) !== "-") {
     this.classList.remove('numberIndistinto', 'numero_negativos'); this.classList.add('numberIndistinto', 'numero_positivos');
    }else{
      this.classList.remove('numberIndistinto', 'numero_positivos'); this.classList.add('numberIndistinto', 'numero_negativos');
    }
  }
}

window.onload = function () {
  // ################ SE EJECUTA DESPUES CARGAR EL CODIGO CSS y HTML #############
  // Creamos el evento keyup para cada clase definida
  // document.querySelectorAll(".integerIndistinto").forEach((el) => el.addEventListener("keyup", integerFormatIndistinto));
  // document.querySelectorAll(".integerPositivo").forEach((el) => el.addEventListener("keyup", integerFormatPositivo));
  document.querySelectorAll(".numberIndistinto").forEach((el) => el.addEventListener("keyup", numberFormatIndistinto));
  // document.querySelectorAll(".numberPositivo").forEach((el) => el.addEventListener("keyup", numberFormatPositivo));
  document.querySelectorAll(".numberIndistintoFixed").forEach((el) => el.addEventListener("keyup", numberFormatIndistintoFixed));
  // document.querySelectorAll(".numberPositivoFixed").forEach((el) => el.addEventListener("keyup", numberFormatPositivoFixed));
};

// Formato de miles
function formato_miles(num) {
  if (num == 0 || num == null) return "0.00";
  if (!num || num == "NaN") return "-";
  if (num == "Infinity") return "&#x221e;";
  num = num.toString().replace(/\$|\,/g, "");
  if (isNaN(num)) num = "0";
  sign = num == (num = Math.abs(num));
  num = Math.floor(num * 100 + 0.50000000001);
  cents = num % 100;
  num = Math.floor(num / 100).toString();
  if (cents < 10) cents = "0" + cents;
  for (var i = 0; i < Math.floor((num.length - (1 + i)) / 3); i++) num = num.substring(0, num.length - (4 * i + 3)) + "," + num.substring(num.length - (4 * i + 3));
  return (sign ? "" : "-") + num + "." + cents;
}

function es_numero(num) {
  if (!num || num == "NaN") return false;
  if (num == "Infinity") return false;
  if (isNaN(num)){ return false } else { return true };
}

function convertir_a_numero(num) {
  return es_numero(num) == true ? parseFloat(num) : 0.00 ;
}

// Quitar formato de miles
function quitar_formato_miles(num) {
  let inVal = 0;
  if (!num || num == "NaN" || num == "" || num == null || num == "Infinity" || num === undefined) { } else {
    inVal = parseFloat( num.replace(/,/g, "") );
  }
  return inVal;
}

// Redondear a un exponente
function redondearExp(numero, digitos=2) {
  function toExp(numero, digitos) {
    let arr = numero.toString().split("e");
    let mantisa = arr[0], exponente = digitos;
    if (arr[1]) exponente = Number(arr[1]) + digitos;
    return Number(mantisa + "e" + exponente.toString());
  }
  let entero = Math.round(toExp(Math.abs(numero), digitos));
  return Math.sign(numero) * toExp(entero, -digitos);
}

//Redondear 2 decimales (1.56 = 1.60, 1.52 = 1.50), para dinero
function roundTwo(num) { return Number(+(Math.round(num + "e+1") + "e-1")).toFixed(2); }

function redondear_mas(numero) { return Math.ceil(numero);  }

function roundToHalf(numero) { 
  var converted = parseFloat(numero); // Asegúrate de que tengamos un número.
  var decimal = (converted - parseInt(converted, 10)); 
  decimal = Math.round(decimal * 10); 
  if (decimal >= 5) { return (parseInt(converted, 10)+0.5); } else{ return Math.round(converted); }
} 

// Unico ID
function unique_id() { return parseInt(Math.round(new Date().getTime() + Math.random() * 100)); }

/*  ══════════════════════════════════════════ - S T R I N G - ══════════════════════════════════════════ */

// Codificamos los caracteres: &, <, >, ", '
function encodeHtml(str) {
  var encode = "";
  if (str == "" || str == null || str === undefined) { } else {
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    encode = str.replace(/[&<>"']/g, function(m) {return map[m];}); //console.log(encode);
  }
  return encode;
}

// Decodificamos los caracteres: &amp; &lt; &gt; &quot; &#039;
function decodeHtml(str) {
  var decode = "";
  if (str == "" || str == null || str === undefined) { } else {
    var map = {
      '&amp;': '&',
      '&lt;': '<',
      '&gt;': '>',
      '&quot;': '"',
      '&#039;': "'"
    };
    decode = str.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, function(m) {return map[m];}); //console.log(decode);
  }
  return decode;
}

function removeHtml(str) {
  if ((str===null) || (str==='')){
    return '';
  }else{
    str = str.toString();
    return str.replace( /(<([^>]+)>)/ig, '');
  }
}

function removeCaracterEspecial(str) {
  var string = "";
  if (str == "" || str == null || str === undefined) { } else {     
    string = str.replace(/[`~!@#$%^&*()_|+\-=?;:°'",.<>\{\}\[\]\\\/]/g, '');
  }
  return string;
}

function removeCaracterEspecial_v2(str) {
  var string = "";
  if (str == "" || str == null || str === undefined) { } else {     
    string = str.replace(/[`~!@#$%^&*()_|+\-=?;:°'",.<>\{\}\[\]\\\/ ]/g, '');
  }
  return string;
}

function preservarNumeroLetra(str) {
  var string = "";
  if (str == "" || str == null || str === undefined) { } else {     
   string = str.replace(/[^a-zA-Z 0-9.]/g, '');    
  }
  return string;
}

// to miniscula
function convert_minuscula(e) { e.value = e.value.toLowerCase(); }
function convert_minuscula_v2(e) { return e.toLowerCase(); }

function quitar_punto(string){ 
  return string.replace(/\./g,'');
}

function replace_punto_a_guion(string) {
  return string.replace(/\./g,'-');
}

function quitar_guion(str) {

  if (str == '' || str == null ) { return ""; } else {
    return str.replace(/\-/g, "");
  }  
}
function recorte_text(str='', cant = 10) {
  if (str == '' || str == null) {
    return "";
  } else {
    if (str.length > cant) { return `${str.slice(0,cant)}...`; }else{ return str.slice(0,cant); }    
  }
}

//capitalize all words of a string. 
function capitalizeWords(str) {
  var string = "";
  if (str == "" || str == null || str === undefined) { } else {     
    string = str.replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
  }
  return string;
};

/*  ══════════════════════════════════════════ - T I E M P O S - ══════════════════════════════════════════ */

// retrazamos la ejecuccion de una funcion
var delay = (function(){
  var timer = 0;
  return function(callback, ms){ clearTimeout (timer); timer = setTimeout(callback, ms); };
})();

/*  ══════════════════════════════════════════ - S U B I R   D O C S  - ══════════════════════════════════════════ */

/* PREVISUALIZAR: img */
function addImage(e, id, img_default='') {
  // colocamos cargando hasta que se vizualice
  $("#"+id+"_ver").html('<i class="fas fa-spinner fa-pulse fa-6x"></i><br><br>');

	console.log(id);

	var file = e.target.files[0], imageType = /image.*/;
	
	if (e.target.files[0]) {

		var sizeByte = file.size;

		var sizekiloBytes = parseInt(sizeByte / 1024);

		var sizemegaBytes = (sizeByte / 1000000);
		// alert("KILO: "+sizekiloBytes+" MEGA: "+sizemegaBytes)

		if (!file.type.match(imageType)){
			// return;
			toastr.error('Este tipo de ARCHIVO no esta permitido <br> elija formato: <b>.png .jpeg .jpg .webp etc... </b>');

			if (img_default == '' || img_default == null || img_default == false || img_default == true ) {
        $("#"+id+"_i").attr("src", "../dist/img/default/img_defecto.png");
      } else {
        $("#"+id+"_i").attr("src", img_default);
      }      

		}else{

			if (sizekiloBytes <= 10240) {

				var reader = new FileReader();

				reader.onload = fileOnload;

				function fileOnload(e) {

					var result = e.target.result;

					$("#"+id+"_i").attr("src", result);

          $(`.jq_image_zoom`).zoom({ on:'grab' });

					$("#"+id+"_nombre").html(''+
						'<div class="row">'+
              '<div class="col-md-12">'+
              file.name +
              '</div>'+
              '<div class="col-md-12">'+
              '<button  class="btn btn-danger  btn-block" onclick="'+id+'_eliminar();" style="padding:0px 12px 0px 12px !important;" type="button" ><i class="far fa-trash-alt"></i></button>'+
              '</div>'+
            '</div>'+
					'');

					toastr.success('Imagen aceptada.')
				}

				reader.readAsDataURL(file);

			} else {

				toastr.warning('La imagen: '+file.name.toUpperCase()+' es muy pesada. Tamaño máximo 10mb')

				$("#"+id+"_i").attr("src", "../dist/img/default/img_error.png");

				$("#"+id).val("");
			}
		}

	}else{

		toastr.error('Seleccione una Imagen');

    if (img_default == '' || img_default == null || img_default == false || img_default == true ) {
      $("#"+id+"_i").attr("src", "../dist/img/default/img_defecto.png");
    } else {
      $("#"+id+"_i").attr("src", img_default);
    }  

		$("#"+id+"_nombre").html("");
	}
}

/* PREVISUALIZA: img, pdf, doc, excel,  */
function addImageApplication(e, id, img_default='', width='100%', height='310', detalle_upload=false) {
  console.log(id, img_default, width, height, detalle_upload);
  $(`#${id}_ver`).html('<i class="fas fa-spinner fa-pulse fa-6x"></i><br>');	

	var file = e.target.files[0], archivoType = /image.*|application.*/; console.log(file);
	
	if (e.target.files[0]) {
    
		var sizeByte = file.size; console.log(file.type);
		var sizekiloBytes = parseInt(sizeByte / 1024);
		var sizemegaBytes = (sizekiloBytes / 1024);
		// alert("KILO: "+sizekiloBytes+" MEGA: "+sizemegaBytes)

		if (!file.type.match(archivoType) ){
			// return;
      Swal.fire({
        position: 'top-end',
        icon: 'error',
        title: 'Este tipo de ARCHIVO no esta permitido elija formato: .pdf, .png. .jpeg, .jpg, .jpe, .webp, .svg',
        showConfirmButton: false,
        timer: 1500
      });

      if (img_default == '' || img_default == null || img_default == false || img_default == true ) {
        $(`#${id}_ver`).html('<img src="../dist/svg/doc_uploads.svg" alt="" width="50%" >'); 
      } else {
        $(`#${id}_ver`).html(`<img src="${img_default}" alt="" width="50%" >`); 
      }
      

		}else{

			if (sizekiloBytes <= 40960) {

				var reader = new FileReader();

				reader.onload = fileOnload;

				function fileOnload(e) {

					var result = e.target.result;

          // cargamos la imagen adecuada par el archivo
				  if ( extrae_extencion(file.name) == "doc") {
            $(`#${id}_ver`).html('<img src="../dist/svg/doc.svg" alt="" width="50%" >');
          } else if ( extrae_extencion(file.name) == "docx" ) {             
            $(`#${id}_ver`).html('<img src="../dist/svg/docx.svg" alt="" width="50%" >');
          }else if ( extrae_extencion(file.name) == "pdf" || extrae_extencion(file.name) == "PDF" ) {              
            $(`#${id}_ver`).html(`<iframe src="${result}" frameborder="0" scrolling="no" width="${width}" height="${height}"></iframe>`);
          }else if ( extrae_extencion(file.name) == "csv" ) {              
            $(`#${id}_ver`).html('<img src="../dist/svg/csv.svg" alt="" width="50%" >');
          } else if ( extrae_extencion(file.name) == "xls" ) {             
            $(`#${id}_ver`).html('<img src="../dist/svg/xls.svg" alt="" width="50%" >');
          } else if ( extrae_extencion(file.name) == "xlsx" ) {             
            $(`#${id}_ver`).html('<img src="../dist/svg/xlsx.svg" alt="" width="50%" >');
          } else if ( extrae_extencion(file.name) == "xlsm" ) {             
            $(`#${id}_ver`).html('<img src="../dist/svg/xlsm.svg" alt="" width="50%" >');
          } else if ( extrae_extencion(file.name) == "xlsb" ) {             
            $(`#${id}_ver`).html('<img src="../dist/svg/xlsb.svg" alt="" width="50%" >');
          } else if (
            extrae_extencion(file.name) == "jpeg" || extrae_extencion(file.name) == "jpg" || extrae_extencion(file.name) == "jpe" ||
            extrae_extencion(file.name) == "jfif" || extrae_extencion(file.name) == "gif" || extrae_extencion(file.name) == "png" ||
            extrae_extencion(file.name) == "tiff" || extrae_extencion(file.name) == "tif" || extrae_extencion(file.name) == "webp" ||
            extrae_extencion(file.name) == "bmp" || extrae_extencion(file.name) == "svg" ) {

            $(`#${id}_ver`).html(`<span class="jq_image_zoom"><img src="${result}" alt="" width="100%" onerror="this.src='../dist/svg/error-404-x.svg';" ></span>`); 
            $(`.jq_image_zoom`).zoom({ on:'grab' });
          } else {
            $(`#${id}_ver`).html('<img src="../dist/svg/doc_si_extencion.svg" alt="" width="50%" >');
          }
          
          if (detalle_upload == true) {
            $(`#${id}_nombre`).html(`<div class="row">
              <div class="col-sm-11 col-md-10 col-lg-11 col-xl-11 mt-2">
                <p><b>Nombre:</b><i> ${file.name}</i></p>
                <p><b>Tamaño:</b> ${formato_miles(sizemegaBytes)} mb</p>
                <p><b>Tipo:</b> ${file.type}</p>
              </div>
              <div class="col-sm-1 col-md-2 col-lg-1 col-xl-1 mt-2">
                <button class="btn btn-danger btn-block btn-xs h-100" onclick="${id}_eliminar();" type="button" ><i class="far fa-trash-alt"></i></button>
              </div>
            </div>`);
          } else {
            $(`#${id}_nombre`).html(`<div class="row">
              <div class="col-md-12">
                <i> ${file.name} </i>
              </div>
              <div class="col-md-12">
                <button class="btn btn-danger btn-block btn-xs" onclick="${id}_eliminar();" type="button" ><i class="far fa-trash-alt"></i></button>
              </div>
            </div>`);
          }					

          Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: `El documento: ${file.name.toUpperCase()} es aceptado.`,
            showConfirmButton: false,
            timer: 1500
          });
				}

				reader.readAsDataURL(file);

			} else {
        Swal.fire({
          position: 'top-end',
          icon: 'warning',
          title: `El documento: ${file.name.toUpperCase()} es muy pesado.`,
          showConfirmButton: false,
          timer: 1500
        })

        if (img_default == '' || img_default == null || img_default == false || img_default == true ) {
          $(`#${id}_ver`).html('<img src="../dist/svg/doc_uploads.svg" alt="" width="50%" >'); 
        } else {
          $(`#${id}_ver`).html(`<img src="${img_default}" alt="" width="50%" >`); 
        }
        
        $("#"+id+"_nombre").html("");
				$("#"+id).val("");
			}
		}
	}else{
    Swal.fire({
      position: 'top-end',
      icon: 'error',
      title: 'Seleccione un documento',
      showConfirmButton: false,
      timer: 1500
    });

    if (img_default == '' || img_default == null || img_default == false || img_default == true ) {
      $(`#${id}_ver`).html('<img src="../dist/svg/doc_uploads.svg" alt="" width="50%" >'); 
    } else {
      $(`#${id}_ver`).html(`<img src="${img_default}" alt="" width="50%" >`); 
    }		 
    
		$("#"+id+"_nombre").html("");
    $("#"+id).val("");
	}	
}

// recargar un doc para ver
function re_visualizacion(id, carpeta, sub_carpeta, width='100%', height='310') {
  console.log(id, carpeta, sub_carpeta, width, height);
  $("#doc"+id+"_ver").html('<i class="fas fa-spinner fa-pulse fa-6x"></i><br><br>');

  pdffile     = document.getElementById("doc"+id+"").files[0];

  var antiguopdf  = $("#doc_old_"+id+"").val();

  if(pdffile === undefined){

    if (antiguopdf == "") {

      Swal.fire({
        position: 'top-end',
        icon: 'error',
        title: 'Seleccione un documento',
        showConfirmButton: false,
        timer: 1500
      })

      $("#doc"+id+"_ver").html('<img src="../dist/svg/pdf_trasnparent.svg" alt="" width="50%" >');

		  $("#doc"+id+"_nombre").html("");

    } else {
      $("#doc"+id+"_ver").html( doc_view_extencion(antiguopdf, carpeta, sub_carpeta, width, height) );

      if (pdf_o_img(antiguopdf) == true) {
        toastr.success('Documento vizualizado correctamente!!!');
      } else {
        toastr.error('Documento NO TIENE PREVIZUALIZACION!!!');
      }
            
    }
    // console.log('hola'+dr);
  }else{

    pdffile_url=URL.createObjectURL(pdffile);

    var sizeByte = pdffile_url.size; console.log(pdffile_url.type);
		var sizekiloBytes = parseInt(sizeByte / 1024);
		var sizemegaBytes = (sizeByte / 1000000);

    // cargamos la imagen adecuada par el archivo
    if ( extrae_extencion(pdffile.name) == "doc") {
      $("#doc"+id+"_ver").html('<img src="../dist/svg/doc.svg" alt="" width="50%" >');
      toastr.error('Documento NO TIENE PREVIZUALIZACION!!!')
    } else if ( extrae_extencion(pdffile.name) == "docx" ) {
      
      $("#doc"+id+"_ver").html('<img src="../dist/svg/docx.svg" alt="" width="50%" >');
      toastr.error('Documento NO TIENE PREVIZUALIZACION!!!')
    } else if ( extrae_extencion(pdffile.name) == "pdf" || extrae_extencion(pdffile.name) == "PDF" ) {
        
      $("#doc"+id+"_ver").html(`<iframe src="${pdffile_url}" frameborder="0" scrolling="no" width="${width}" height="${height}"> </iframe>`);
      toastr.success('Documento vizualizado correctamente!!!');
    } else if ( extrae_extencion(pdffile.name) == "csv" ) {
          
      $("#doc"+id+"_ver").html('<img src="../dist/svg/csv.svg" alt="" width="50%" >');
      toastr.error('Documento NO TIENE PREVIZUALIZACION!!!');
    } else if ( extrae_extencion(pdffile.name) == "xls" ) {
            
      $("#doc"+id+"_ver").html('<img src="../dist/svg/xls.svg" alt="" width="50%" >');
      toastr.error('Documento NO TIENE PREVIZUALIZACION!!!');
    } else if ( extrae_extencion(pdffile.name) == "xlsx" ) {
              
      $("#doc"+id+"_ver").html('<img src="../dist/svg/xlsx.svg" alt="" width="50%" >');
      toastr.error('Documento NO TIENE PREVIZUALIZACION!!!');
    } else if ( extrae_extencion(pdffile.name) == "xlsm" ) {
                
      $("#doc"+id+"_ver").html('<img src="../dist/svg/xlsm.svg" alt="" width="50%" >');
      toastr.error('Documento NO TIENE PREVIZUALIZACION!!!');
    } else if ( extrae_extencion(pdffile.name) == "xlsb" ) {
                
      $("#doc"+id+"_ver").html('<img src="../dist/svg/xlsb.svg" alt="" width="50%" >');
      toastr.error('Documento NO TIENE PREVIZUALIZACION!!!');
    } else if (
      extrae_extencion(pdffile.name) == "jpeg" || extrae_extencion(pdffile.name) == "jpg" || extrae_extencion(pdffile.name) == "jpe" ||
      extrae_extencion(pdffile.name) == "jfif" || extrae_extencion(pdffile.name) == "gif" || extrae_extencion(pdffile.name) == "png" ||
      extrae_extencion(pdffile.name) == "tiff" || extrae_extencion(pdffile.name) == "tif" || extrae_extencion(pdffile.name) == "webp" ||
      extrae_extencion(pdffile.name) == "bmp" || extrae_extencion(pdffile.name) == "svg" ) {

      $("#doc"+id+"_ver").html(`<span class="jq_image_zoom"><img src="${pdffile_url}" alt="" width="100%" ></span>`);
      $(`.jq_image_zoom`).zoom({ on:'grab' });
      toastr.success('Documento vizualizado correctamente!!!');
    } else {
      $("#doc"+id+"_ver").html('<img src="../dist/svg/doc_si_extencion.svg" alt="" width="50%" >');
      toastr.error('Documento NO TIENE PREVIZUALIZACION!!!');
    }
    
    console.log(pdffile);
  }
}

function doc_view_extencion(filename, carpeta='', sub_carpeta='', width='50%', height='auto', return_error_img = false, error_img ='') {

  var html = ''; var extencion = '';
  var host = '';  var ruta = '';

  if (carpeta == '' && sub_carpeta == '') {
    host = window.location.host == 'localhost'? `http://localhost/admin_sevens/${filename}` : `${window.location.origin}/${filename}` ;
    ruta = host;
  } else {
    ruta = sub_carpeta == '' || sub_carpeta == null ?  `../dist/docs/${carpeta}/${filename}`: `../dist/docs/${carpeta}/${sub_carpeta}/${filename}`;
    
    if (sub_carpeta == '' || sub_carpeta == null) {
      host = window.location.host == 'localhost'? `http://localhost/admin_sevens/dist/docs/${carpeta}/${filename}` : `${window.location.origin}/dist/docs/${carpeta}/${filename}` ;
    } else {
      host = window.location.host == 'localhost'? `http://localhost/admin_sevens/dist/docs/${carpeta}/${sub_carpeta}/${filename}` : `${window.location.origin}/dist/docs/${carpeta}/${sub_carpeta}/${filename}` ;
    }
  }
  

  // cargamos la imagen adecuada par el archivo
  if ( UrlExists(host) != 200 ) { console.log('no existe');
    if (return_error_img == true) {    
      var html_img_error =  error_img == '' || error_img == null ? `<img src="../dist/svg/404-v2.svg" alt="" width="${width}" >` : `<img src="${error_img}" alt="" width="${width}" onerror="this.src='../dist/svg/404-v2.svg';"  >`;
      return html_img_error;
    } else {
      html = `<div class="callout callout-danger">
        <p class="text-danger font-size-12px text-left">404 Documento no encontrado!!</p>
        <p class="font-size-10px text-left">Hubo un <b>error</b> al <b>encontrar este archivo</b> , los mas probable es que se haya eliminado, o se haya movido a otro lugar, se <b>recomienda editar</b> en su módulo correspodiente.</p>
      </div>`;
      extencion = extrae_extencion(filename);
    }
  }else if ( extrae_extencion(filename) == "xls") {

    html = `<img src="../dist/svg/xls.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);

  } else if ( extrae_extencion(filename) == "xlsx" ) {    

    html = `<img src="../dist/svg/xlsx.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);

  }else if ( extrae_extencion(filename) == "csv" ) {

    html = `<img src="../dist/svg/csv.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);

  }else if ( extrae_extencion(filename) == "xlsm" ) {

    html = `<img src="../dist/svg/xlsm.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
  }else if ( extrae_extencion(filename) == "xlsb" ) {

    html = `<img src="../dist/svg/xlsb.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);

  }else if ( extrae_extencion(filename) == "docx" ||  extrae_extencion(filename) == "docm"  || extrae_extencion(filename) == "dot" ||  extrae_extencion(filename) == "dotx" ||  extrae_extencion(filename) == "dotm") {

    html = `<img src="../dist/svg/docx.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);

  }else if ( extrae_extencion(filename) == "doc") {

    html = `<img src="../dist/svg/doc.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);

  }else if ( extrae_extencion(filename) == "dwg") {

    html = `<img src="../dist/svg/dwg.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);

  }else if ( extrae_extencion(filename) == "zip" || extrae_extencion(filename) == "rar" || extrae_extencion(filename) == "iso") {

    html = `<img src="../dist/img/default/zip.png" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);

  }else if ( extrae_extencion(filename) == "pdf" || extrae_extencion(filename) == "PDF" ) {
    //recomendado - height="210" 
    html = `<iframe src="${ruta}" onerror="this.src='../dist/svg/404-v2.svg';" frameborder="0" scrolling="no" width="${width}" height="${height}"> </iframe>`;
    extencion = extrae_extencion(filename);
  
  } else if (
    extrae_extencion(filename) == "jpeg" || extrae_extencion(filename) == "jpg" || extrae_extencion(filename) == "jpe" ||
    extrae_extencion(filename) == "jfif" || extrae_extencion(filename) == "gif" || extrae_extencion(filename) == "png" ||
    extrae_extencion(filename) == "tiff" || extrae_extencion(filename) == "tif" || extrae_extencion(filename) == "webp" ||
    extrae_extencion(filename) == "bmp" || extrae_extencion(filename) == "svg" ) {

    html = `<center><span class="jq_image_zoom"><img id="_cargando_img_" src="${ruta}" alt="" width="${width}" onerror="this.src='../dist/svg/404-v2.svg';"  ></span></center>`;
    extencion = extrae_extencion(filename);
    //loading_img('_cargando_img_', ruta, width);
    
  }else{
    html = `<img src="../dist/svg/doc_si_extencion.svg" alt="" width="50%" height="50%"  >`;
    extencion = extrae_extencion(filename);
    
  }

  return html;
}

function doc_view_download_expand(filename, ruta='', nombre_decarga='', width='50%', height='auto', return_error_img = false, error_img ='') {

  var html = ''; var extencion = '';
  var expand_disabled = '';

  var ruta_file = window.location.host == 'localhost'? `http://localhost/admin_sevens/${ruta}/${filename}` : `${window.location.origin}/${ruta}/${filename}` ;
 
  // cargamos la imagen adecuada par el archivo
  if ( UrlExists(ruta_file) != 200 ) { console.log('no existe');
    if (return_error_img == true) {    
      var html_img_error =  error_img == '' || error_img == null ? `<img src="../dist/svg/404-v2.svg" alt="" width="${width}" >` : `<img src="${error_img}" alt="" width="${width}" onerror="this.src='../dist/svg/404-v2.svg';"  >`;
      return html_img_error;
    } else {
      html = `<div class="callout callout-danger">
        <p class="text-danger font-size-12px text-left">404 Documento no encontrado!!</p>
        <p class="font-size-10px text-left">Hubo un <b>error</b> al <b>encontrar este archivo</b> , los mas probable es que se haya eliminado, o se haya movido a otro lugar, se <b>recomienda editar</b> en su módulo correspodiente.</p>
      </div>`;
      extencion = extrae_extencion(filename);
      return html
    }
  }else if ( extrae_extencion(filename) == "xls") {

    html = `<img src="../dist/svg/xls.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';

  } else if ( extrae_extencion(filename) == "xlsx" ) {    

    html = `<img src="../dist/svg/xlsx.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';

  }else if ( extrae_extencion(filename) == "csv" ) {

    html = `<img src="../dist/svg/csv.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';

  }else if ( extrae_extencion(filename) == "xlsm" ) {

    html = `<img src="../dist/svg/xlsm.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';

  }else if ( extrae_extencion(filename) == "xlsb" ) {

    html = `<img src="../dist/svg/xlsb.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';

  }else if ( extrae_extencion(filename) == "docx" ||  extrae_extencion(filename) == "docm"  || extrae_extencion(filename) == "dot" ||  extrae_extencion(filename) == "dotx" ||  extrae_extencion(filename) == "dotm") {

    html = `<img src="../dist/svg/docx.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';

  }else if ( extrae_extencion(filename) == "doc") {

    html = `<img src="../dist/svg/doc.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';

  }else if ( extrae_extencion(filename) == "dwg") {

    html = `<img src="../dist/svg/dwg.svg" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';
    
  }else if ( extrae_extencion(filename) == "zip" || extrae_extencion(filename) == "rar" || extrae_extencion(filename) == "iso") {

    html = `<img src="../dist/img/default/zip.png" alt="" width="50%" height="50%" >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';

  }else if ( extrae_extencion(filename) == "pdf" || extrae_extencion(filename) == "PDF" ) {
    //recomendado - height="210" 
    html = `<iframe src="${ruta_file}" onerror="this.src='../dist/svg/404-v2.svg';" frameborder="0" scrolling="no" width="${width}" height="${height}"> </iframe>`;
    extencion = extrae_extencion(filename);
    expand_disabled = '';
  
  } else if (
    extrae_extencion(filename) == "jpeg" || extrae_extencion(filename) == "jpg" || extrae_extencion(filename) == "jpe" ||
    extrae_extencion(filename) == "jfif" || extrae_extencion(filename) == "gif" || extrae_extencion(filename) == "png" ||
    extrae_extencion(filename) == "tiff" || extrae_extencion(filename) == "tif" || extrae_extencion(filename) == "webp" ||
    extrae_extencion(filename) == "bmp" || extrae_extencion(filename) == "svg" ) {

    html = `<center><span class="jq_image_zoom"><img id="_cargando_img_" src="${ruta_file}" alt="" width="${width}" onerror="this.src='../dist/svg/404-v2.svg';"  ></span></center>`;
    extencion = extrae_extencion(filename);
    expand_disabled = '';
    
  }else{
    html = `<img src="../dist/svg/doc_si_extencion.svg" alt="" width="50%" height="50%"  >`;
    extencion = extrae_extencion(filename);
    expand_disabled = 'disabled';    
  }

  var div_html = `<div class="row">
    <div class="col-6 col-md-6">
      <a class="btn btn-xs btn-block btn-warning" href="${ruta_file}" download="${nombre_decarga}" type="button"><i class="fas fa-download"></i> Descargar</a>
    </div>
    <div class="col-6 col-md-6">
      <a class="btn btn-xs btn-block btn-info ${expand_disabled}" href="${ruta_file}" target="_blank" type="button"><i class="fas fa-expand"></i> Ver completo</a>
    </div>
    <div class="col-12 col-md-12 mt-2"  width="auto">   
      ${html} 
    </div>
  </div>`;

  return div_html;
}

function loading_img(attr_img, file_img, width_img) {
  $('#_cargando_img_').attr('onload', '');
  //attr_img.removeAttribute("onload");
  console.log(attr_img.src);
  const img_temp= document.createElement('img');
  img_temp.addEventListener('load', ()=>{
    console.log('imgagen cargadaaaa');    
    $('#_cargando_img_').attr('width', width_img);
    attr_img.src = img_temp.src;
  });
  img_temp.src = file_img;  
}

function doc_view_icon(filename, color_class='', font_size_class='' ) {

  // cargamos la imagen adecuada par el archivo
  if ( extrae_extencion(filename) == "xls") {

    html = `<i class="far fa-file-excel ${(color_class==''? 'text-success': color_class)} ${font_size_class}"></i>`;

  } else if ( extrae_extencion(filename) == "xlsx" ) {    

    html = `<i class="far fa-file-excel ${(color_class==''? 'text-success': color_class)} ${font_size_class}"></i>`;

  }else if ( extrae_extencion(filename) == "csv" ) {

    html = `<i class="fas fa-file-csv ${(color_class==''? 'text-success': color_class)} ${font_size_class}"></i>`;

  }else if ( extrae_extencion(filename) == "xlsm" ) {

    html = `<i class="far fa-file-excel ${(color_class==''? 'text-success': color_class)} ${font_size_class}"></i>`;
    
  }else if ( extrae_extencion(filename) == "xlsb" ) {

    html = `<i class="far fa-file-excel ${(color_class==''? 'text-success': color_class)} ${font_size_class}"></i>`;

  }else if ( extrae_extencion(filename) == "docx" ) {

    html = `<i class="fas fa-file-word ${(color_class==''? 'text-primary': color_class)} ${font_size_class}"></i>`;

  }else if ( extrae_extencion(filename) == "doc") {

    html = `<i class="fas fa-file-word ${(color_class==''? 'text-primary': color_class)} ${font_size_class}"></i>`;


  }else if ( extrae_extencion(filename) == "pdf" ) {
    
    html = `<i class="far fa-file-pdf ${(color_class==''? 'text-danger': color_class)} ${font_size_class}"></i>`;
  
  } else if (
    extrae_extencion(filename) == "jpeg" || extrae_extencion(filename) == "jpg" || extrae_extencion(filename) == "jpe" ||
    extrae_extencion(filename) == "jfif" || extrae_extencion(filename) == "gif" || extrae_extencion(filename) == "png" ||
    extrae_extencion(filename) == "tiff" || extrae_extencion(filename) == "tif" || extrae_extencion(filename) == "webp" ||
    extrae_extencion(filename) == "bmp" || extrae_extencion(filename) == "svg" ) {

    html = `<i class="fas fa-file-image ${(color_class==''? 'text-primary': color_class)} ${font_size_class}"></i>`;
    
  }else{

    html = `<i class="fas fa-file-alt ${color_class} ${font_size_class}"></i>`;
    
  }

  return html;
}

function extrae_extencion(filename) { 
  var exten = "";
  if (filename == "" || filename == null || filename === undefined) {  }else{
    exten = filename.split(".").pop();
  }
  return exten; 
}

function pdf_o_img(filename) {
  data = false;
  if ( extrae_extencion(filename) == "pdf" ) {
    //recomendado - height="210" 
    data = true;

  } else if (
    extrae_extencion(filename) == "jpeg" || extrae_extencion(filename) == "jpg" || extrae_extencion(filename) == "jpe" ||
    extrae_extencion(filename) == "jfif" || extrae_extencion(filename) == "gif" || extrae_extencion(filename) == "png" ||
    extrae_extencion(filename) == "tiff" || extrae_extencion(filename) == "tif" || extrae_extencion(filename) == "webp" ||
    extrae_extencion(filename) == "bmp" || extrae_extencion(filename) == "svg" ) {

    data = true;
    
  }
  return data;
}

function es_img(filename) {
  data = false;
  if (
    extrae_extencion(filename) == "jpeg" || extrae_extencion(filename) == "jpg" || extrae_extencion(filename) == "jpe" ||
    extrae_extencion(filename) == "jfif" || extrae_extencion(filename) == "gif" || extrae_extencion(filename) == "png" ||
    extrae_extencion(filename) == "tiff" || extrae_extencion(filename) == "tif" || extrae_extencion(filename) == "webp" ||
    extrae_extencion(filename) == "bmp" || extrae_extencion(filename) == "svg" ) {

    data = true;    
  }

  return data;
}

// cuando hace click en revizualizar
function reload_zoom() {
  $(`.jq_image_zoom`).zoom({ on:'grab' });
}


/*  ══════════════════════════════════════════ - A P I S - ══════════════════════════════════════════ */
// Buscar Reniec SUNAT
function buscar_sunat_reniec(formulario= '', input='', tipo_documento, dniruc, descripcion, nombrecomercial, nombre_persona_natural, apellido_paterno_persona_natural, apellido_materno_persona_natural, direccion, distrito, cod_ubigeo, tipo_entidad_sunat) {
  //console.log(input);

  $(`#search${input}`).hide(); $(`#charge${input}`).show();

  let tipo_doc = $(tipo_documento).val();

  let dni_ruc             = $(dniruc).val(); 
  let descripcion_persona = $(descripcion).val();
  let nombre_comercial    = $(nombrecomercial).val();
  let nombre_persona      = $(nombre_persona_natural).val();
  let apellido_paterno    = $(apellido_paterno_persona_natural).val();
  let apellido_materno    = $(apellido_materno_persona_natural).val();
  let direccion_persona   = $(direccion).val();
  let distrito_persona    = $(distrito).val();
  let cod_ubigeo_persona  = $(cod_ubigeo).val();
  let tipo_entidad        = $(tipo_entidad_sunat).val();
  let tipo_entidad_normalizado = (tipo_entidad || '').toString().trim().toUpperCase();

  let base_url = document.querySelector('meta[name="app-url"]')?.content || '';
  
  if (tipo_entidad_normalizado === '') {
    toastr_error('Error!!', 'Seleccione el tipo de entidad', 700);
  }else{
    
    if ( tipo_doc == "1") { // DNI

      if (dni_ruc.length == "8") {

        $.getJSON(`${base_url}/reniec/dni`, { dni: dni_ruc }, function (e, status) { 

          if (e == null || e.length === 0 ) {

            $(`.valido_novalido`).html('<span class="badge bg-danger">NO ACTIVO</span>');

            $(`#search${input}`).show(); $(`#charge${input}`).hide();
            $(descripcion).val(''); $(nombrecomercial).val(''); $(nombre_persona_natural).val(''); $(apellido_paterno_persona_natural).val(''); $(apellido_materno_persona_natural).val('');
            toastr_info('Error!!', 'El sistema de BUSQUEDA esta en mantenimiento. Ingrese sus datos manualmente', 700);

            
          } else {
            if (e.data.success == false) {

              $(`.valido_novalido`).html('<span class="badge bg-danger">NO ENCONTRADO</span>');

              $(`#search${input}`).show(); $(`#charge${input}`).hide();

              toastr_info('Error!!', 'El sistema de BUSQUEDA esta en mantenimiento. Ingrese sus datos manualmente', 700);
            } else {

              console.log(e.data.data);

              $(`.valido_novalido`).html('<span class="badge bg-info" >ACTIVO</span>');
              $(`#search${input}`).show();  $(`#charge${input}`).hide();

              $(descripcion).val(e.data.data.nombres + " " + e.data.data.apellido_paterno + " " + e.data.data.apellido_materno);
              console.log(e.data.data.nombres + " " + e.data.data.apellido_paterno + " " + e.data.data.apellido_materno);
              
              $(nombre_persona_natural).val(e.data.data.nombres );
              $(apellido_paterno_persona_natural).val(e.data.data.apellido_paterno);
              $(apellido_materno_persona_natural).val(e.data.data.apellido_materno);   

              if (e.data.data.direccion != '' && e.data.data.direccion != null) {   $(direccion).val(e.data.data.direccion); }
              if (e.data.data.distrito != '' && e.data.data.distrito != null) {
                if (distrito === '#cli_distrito' && typeof seleccionarDistritoClientePorTexto === 'function') {
                  seleccionarDistritoClientePorTexto(e.data.data.distrito);
                } else {
                  $(distrito).val(e.data.data.distrito).trigger('change');
                }
              }
            
              toastr_success('Éxito!!!', `Persona encontrada!!!,  <span class="fs-11">${e.data.data.version}</span>`, 700);
            } 
          }
          $(formulario).valid();
        }).fail(function (e) { ver_errores(e); });
      } else {

        $(dniruc).addClass("is-invalid");
        $(`#search${input}`).show();   $(`#charge${input}`).hide();
        $(descripcion).val(''); $(nombrecomercial).val(''); $(nombre_persona_natural).val(''); $(apellido_paterno_persona_natural).val(''); $(apellido_materno_persona_natural).val('');
        toastr_info('Alerta!!', 'Asegurese de que el DNI tenga 8 dígitos!!!', 700);
      }
    } else if (tipo_doc == "6") {  // RUC

      if (dni_ruc.length == "11") {
        
        let dni = '';
        let reqSunat = $.getJSON(`${base_url}/sunat/ruc`, { ruc: dni_ruc });
        let reqReniec = $.Deferred().resolve([null]).promise();

        if (tipo_entidad_normalizado === 'NATURAL') {

         // desde la posición 2 (tercer carácter) y quitando el último
         dni = dni_ruc.startsWith('10') ? dni_ruc.substring(2, 10) : '';
         reqReniec = (tipo_entidad_normalizado === 'NATURAL' && dni.length === 8)
          ? $.getJSON(`${base_url}/reniec/dni`, { dni: dni })
          : $.Deferred().resolve([null]).promise();
        
        }


        $.when(reqSunat, reqReniec).done(function (sunatResp, reniecResp) {
          let e = Array.isArray(sunatResp) ? sunatResp[0] : sunatResp;
          let reniec = Array.isArray(reniecResp) ? reniecResp[0] : reniecResp;

          if (e == null || e.length === 0) {
            $(`#search${input}`).show();  $(`#charge${input}`).hide();           
            toastr_info('Error!!', 'El sistema de BUSQUEDA esta en mantenimiento. Ingrese sus datos manualmente', 700);
            
          } else {

            if (e.data.success == false) {
              $(`.valido_novalido`).html('<span class="badge bg-danger">NO ACTIVO</span>');
              $(`#search${input}`).show(); $(`#charge${input}`).hide();
              $(direccion).val('');
              toastr_info('Error!!', 'El sistema de BUSQUEDA esta en mantenimiento. Ingrese sus datos manualmente', 700);
              
            } else {
              let nombreCompletoReniec = '';
              if (reniec && reniec.data && reniec.data.success !== false && reniec.data.data) {
                const datosReniec = reniec.data.data;
                const nombresReniec = datosReniec.nombres || '';
                const apellidoPaternoReniec = datosReniec.apellido_paterno || '';
                const apellidoMaternoReniec = datosReniec.apellido_materno || '';
                nombreCompletoReniec = [nombresReniec, apellidoPaternoReniec, apellidoMaternoReniec].filter(Boolean).join(' ');
                $(nombre_persona_natural).val(nombresReniec);
                $(apellido_paterno_persona_natural).val(apellidoPaternoReniec);
                $(apellido_materno_persona_natural).val(apellidoMaternoReniec);
              }

              if (e.data.data.estado == "ACTIVO") {
                $(`.valido_novalido`).html('<span class="badge bg-info">ACTIVO</span>');
                $(`#search${input}`).show(); $(`#charge${input}`).hide();

                var api_razonSocial     = (e.data.data.nombre_o_razon_social == null ? "" : e.data.data.nombre_o_razon_social);
                if (tipo_entidad_normalizado === 'NATURAL' && nombreCompletoReniec) {
                  api_razonSocial = nombreCompletoReniec;
                }
                var api_nombreComercial = (e.data.data.nombreComercial == null ? "" : e.data.data.nombreComercial);
                var api_direccion       = (e.data.data.direccion == null ? "" : e.data.data.direccion);
                var api_distrito        = (e.data.data.distrito == null ? "" : e.data.data.distrito);

                $(descripcion).val(api_razonSocial); 
                $(nombrecomercial).val(api_nombreComercial);  
                $(direccion).val(api_direccion);
                if (distrito === '#cli_distrito' && typeof seleccionarDistritoClientePorTexto === 'function') {
                  seleccionarDistritoClientePorTexto(api_distrito);
                } else {
                  $(distrito).val(api_distrito).trigger('change');
                }
                
                toastr_success('', 'Datos encontrados!!', 700);

              } else {
                $(`.valido_novalido`).html('<span class="badge bg-danger">NO ACTIVO</span>');
                toastr_info('Alerta!!', 'Se recomienda NO generar FACTURAS ó BOLETAS!!!', 700);

                $(`#search${input}`).show();  $(`#charge${input}`).hide();

                var api_razonSocial     = (e.data.data.nombre_o_razon_social == null ? "" : e.data.data.nombre_o_razon_social);
                if (tipo_entidad_normalizado === 'NATURAL' && nombreCompletoReniec) {
                  api_razonSocial = nombreCompletoReniec;
                }
                var api_nombreComercial = (e.data.data.nombreComercial == null ? "" : e.data.data.nombreComercial);
                var api_direccion       = (e.data.data.direccion == null ? "" : e.data.data.direccion);
                var api_distrito        = (e.data.data.distrito == null ? "" : e.data.data.distrito);

                $(descripcion).val(api_razonSocial); 
                $(nombrecomercial).val(api_nombreComercial);  
                $(direccion).val(api_direccion);
                if (distrito === '#cli_distrito' && typeof seleccionarDistritoClientePorTexto === 'function') {
                  seleccionarDistritoClientePorTexto(api_distrito);
                } else {
                  $(distrito).val(api_distrito).trigger('change');
                }
              }
            }
          } 
          $(formulario).valid();         
        }).fail(function (e) { ver_errores(e); });
      } else {

        $(dniruc).addClass("is-invalid");
        $(`#search${input}`).show();  $(`#charge${input}`).hide();
        $(descripcion).val(''); $(nombrecomercial).val(''); $(nombre_persona_natural).val(''); $(apellido_paterno_persona_natural).val(''); $(apellido_materno_persona_natural).val('');    

        toastr_info('Alerta!!', 'Asegurese de que el RUC tenga 11 dígitos!!!', 700);
        $(formulario).valid();
      }
    } else if (tipo_doc == "0" || tipo_doc == "4" || tipo_doc == "7") {    

      $(`#search${input}`).show();  $(`#charge${input}`).hide();
      toastr_info('Alerta!!', 'No necesita hacer consulta.', 700);
      $(formulario).valid();
    } else {

      $(tipo_documento).addClass("is-invalid");
      $(`#search${input}`).show(); $(`#charge${input}`).hide();    
      toastr_error('Error!!', 'Selecione un tipo de documento.', 700);
      $(formulario).valid();
    }
  }
}

/*  ══════════════════════════════════════════ - M E N S A J E S - ══════════════════════════════════════════ */

function ok_dowload_doc() { toastr.success("El documento se descargara en breve!!"); }

function error_dowload_doc() { toastr.success("Hubo un ERROR en la descarga, reintente nuevamente!!"); }

function no_doc() { toastr.error("No hay DOC disponible, suba un DOC en el apartado de editar!!") }

/*  ══════════════════════════════════════════ - O T R O S - ══════════════════════════════════════════ */

function replicar_data_input(input_origen, input_destino) { $(input_destino).val($(input_origen).val()); }

function decifrar_format_banco(format) {

  var array_format =  format.split("-"); var format_final = "";
  
  array_format.forEach((item, index)=>{

    for (let index = 0; index < parseInt(item); index++) { format_final = format_final.concat("9"); }   

    if (parseInt(item) != 0) { format_final = format_final.concat("-"); }
  });

  var ultima_letra = format_final.slice(-1);
   
  if (ultima_letra == "-") { format_final = format_final.slice(0, (format_final.length-1)); }

  return format_final;
}

/*Validación Fecha de Nacimiento Mayoria de edad del usuario*/
function calcular_edad(input_fecha_nacimiento='', input_edad, span_edad='') {

  var fechaUsuario = $(input_fecha_nacimiento).val();

  if (fechaUsuario) {         
  
    /*------------*/
    var hoy = new Date(moment().format('YYYY-MM-DD'));//fecha del sistema con el mismo formato que "fechaUsuario"

    var cumpleanos = new Date(format_a_m_d(fechaUsuario));
    
    //Calculamos años
    var edad = hoy.getFullYear() - cumpleanos.getFullYear();

    var m = hoy.getMonth() - cumpleanos.getMonth();

    if (m < 0 || (m === 0 && hoy.getDate() < cumpleanos.getDate())) {

      edad--;
    }

    // calculamos los meses
    var meses=0;

    if(hoy.getMonth()>cumpleanos.getMonth()){

      meses=hoy.getMonth()-cumpleanos.getMonth();

    }else if(hoy.getMonth()<cumpleanos.getMonth()){

      meses=12-(cumpleanos.getMonth()-hoy.getMonth());

    }else if(hoy.getMonth()==cumpleanos.getMonth() && hoy.getDate()>cumpleanos.getDate() ){

      if(hoy.getMonth()-cumpleanos.getMonth()==0){

        meses=0;
      }else{

        meses=11;
      }            
    }

    // Obtener días: día actual - día de cumpleaños
    let dias  = hoy.getDate() - cumpleanos.getDate();

    if(dias < 0) {
      // Si días es negativo, día actual es mayor al de cumpleaños,
      // hay que restar 1 mes, si resulta menor que cero, poner en 11
      meses = (meses - 1 < 0) ? 11 : meses - 1;
      // Y obtener días faltantes
      dias = 30 + dias;
    }

    // console.log(`Tu edad es de ${edad} años, ${meses} meses, ${dias} días`);
    $(input_edad).val(edad);

    $(span_edad).html(`${edad} años`);
    // calcular mayor de 18 años
    if(edad>=18){

      console.log("Eres un adulto");

    }else{
      // Calcular faltante con base en edad actual
      // 18 menos años actuales
      let edadF = 18 - edad;
      // El mes solo puede ser 0 a 11, se debe restar (mes actual + 1)
      let mesesF = 12 - (meses + 1);
      // Si el mes es mayor que cero, se debe restar 1 año
      if(mesesF > 0) { edadF --;  }
      let diasF = 30 - dias;
      // console.log(`Te faltan ${edadF} años, ${mesesF} meses, ${diasF} días para ser adulto`);
    }

  } else {

    $(input_edad).val("");

    $(span_edad).html(`0 años`); 
  }
}


function UrlExists(url) {  
  var http = new XMLHttpRequest();
  http.open("HEAD", url, false);
  http.send(); //console.log(http.status);
  return http.status;
}

function DocExist(url) {  
  
  var host = window.location.host == 'localhost'? `http://localhost/admin_sevens/${url}` : `${window.location.origin}/${url}`;
  
  var http = new XMLHttpRequest();
  http.open("HEAD", host, false);
  http.send(); //console.log(http.status);
  return http.status;
}

function fechas_valorizacion_quincena(fecha_inicial, fecha_final) {
  var fecha_ii = format_d_m_a(fecha_inicial);
  var fecha_ff = "";
  var fecha_iterativa = format_d_m_a(fecha_inicial);
  var fechas_array = [];
  var i = 1;
  var cal_mes = false;

  while (cal_mes == false) {

    var dia_mes = extraer_dia_mes(format_a_m_d(fecha_ii));
    if (dia_mes < 15) {
      fecha_ff = `15-${format_m_a(format_a_m_d(fecha_ii))}`;
    } else if (dia_mes >= 15 ) {
      fecha_ff =  extraer_ultimo_dia_mes(format_a_m_d(fecha_ii));
    }    
    
    if (validarFechaMenorQue( format_a_m_d(fecha_ff), fecha_final) == false) { 
      cal_mes = true; fecha_ff = format_d_m_a(fecha_final);       
    }

    fechas_array.push({ 'fecha_inicio':fecha_ii, 'fecha_fin':fecha_ff, 'num_q_s': i, });
    //console.log(fecha_ii, fecha_ff); console.log(cal_mes);
    fecha_ii = sumaFecha(1,fecha_ff);
    i++;
  }
  return fechas_array;
}

function fechas_valorizacion_mensual(fecha_inicial, fecha_final) {
  var fecha_ii = format_d_m_a(fecha_inicial);
  var fecha_ff = "";
  var fecha_iterativa = format_d_m_a(fecha_inicial);
  var fechas_array = [];
  var i = 1;
  var cal_mes = false;

  while (cal_mes == false) {

    var dia_mes = extraer_dia_mes(format_a_m_d(fecha_ii));
    
    fecha_ff =  extraer_ultimo_dia_mes(format_a_m_d(fecha_ii));     
    
    if (validarFechaMenorQue( format_a_m_d(fecha_ff), fecha_final) == false) { 
      cal_mes = true; fecha_ff = format_d_m_a(fecha_final);       
    }
    
    fechas_array.push({ 'fecha_inicio':fecha_ii, 'fecha_fin':fecha_ff, 'num_q_s': i, });
    //console.log(fecha_ii, fecha_ff); console.log(cal_mes);
    fecha_ii = sumaFecha(1,fecha_ff);
    i++;
  }
  return fechas_array;
}


function optener_ultima_clase(clase) {
  var clases = $(clase).last()[0].className;
  var ultima_clase = clases.split("")[clases.length - 1];
  return ultima_clase;
}

function abrir_calculadora() {
  var newWindow = window.open("https://www.desmos.com/scientific?lang=es", "_blank", "top=100, left=100, width=350, height=500, menubar=yes,toolbar=yes, scrollbars=yes, resizable=yes");
}

function no_pdf() {
  toastr.error("No hay DOC disponible, suba un DOC en el apartado de editar!!")
}

function dowload_pdf() {
  toastr.success("El documento se descargara en breve!!")
}



