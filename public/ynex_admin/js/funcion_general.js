/*  ══════════════════════════════════════════ - F E C H A S - ══════════════════════════════════════════ */

function sumar_mes(fecha) {
  var split_fecha =  fecha.split("-");
  var dias_total_mes = cantDiasEnUnMes( parseInt(split_fecha[1]), parseInt(split_fecha[0]) );  
  var mes_next =  sumaFecha(dias_total_mes-1, fecha); 
  // console.log(`🚀 ${fecha} + ${dias_total_mes-1} =  fecha_f:${mes_next}`);
  return mes_next;
}

function sumar_mes_v2(m = 1, fecha) { var date = new Date(fecha);   return new Date( date.setMonth(date.getMonth() + m ) ).toISOString().slice(0, 10); }

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
function sumar_year_moment(d, fecha) { return moment(fecha).add(d, 'years').format('YYYY-MM-DD'); }

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

  if (fecha == '' || fecha == null || fecha == '0000-00-00') {  } else {
    dia_mes = parseFloat(moment(fecha).format('DD'));
  }
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

// calulamos la cantidad de dias de una mes especifico
function cantidad_dias_mes(year, month) {
  var diasMes = new Date(year, month, 0).getDate();
  return diasMes;
}

// convierte de una fecha(aa-mm-dd): 2021-12-23 a una fecha(dd-mm-aa): 23-12-2021
function format_d_m_a(fecha, style = '-') {
  var format = "";
  if (fecha == '' || fecha == null || fecha == '0000-00-00') { format = "-"; } else {
    let splits = fecha.split("-"); //console.log(splits);
    format = `${splits[2]}${style}${splits[1]}${style}${splits[0]}`;
  } 
  return format;
}

// convierte de una fecha(aa-mm-dd): 23-12-2021 a una fecha(dd-mm-aa): 2021-12-23
function format_a_m_d(fecha, style = '-') {
  var format = "";
  if (fecha == '' || fecha == null || fecha == '00-00-0000') { format = "-"; } else {
    let splits = fecha.split("-"); //console.log(splits);
    format = `${splits[2]}${style}${splits[1]}${style}${splits[0]}`;
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
  var hoy = moment().format('YYYY-MM-DD'); $(nombre_input).attr('max',hoy);
}

// restringimos la fecha para no elegir menores de 18
function no_select_over_18(nombre_input) { 
  var fecha = sumar_year_moment(-18, moment().format('YYYY-MM-DD')); $(nombre_input).attr('max',fecha); 
}

// restringimos la fecha para no elegir mañana
function restrigir_fecha_ant(nombre_input, fecha_minima) {
  $(nombre_input).attr('min',fecha_minima);
  $(nombre_input).rules("add", { min: fecha_minima, messages: { min: `Ingresa una fecha mayor a: ${format_d_m_a(fecha_minima)}` } });
}

function cant_dias_mes(date_anio, date_mes, array = false) {
	var año = date_anio;
  var mes = date_mes;
  var fecha_array = [];

  if (date_anio == '' || date_anio == null || date_mes =='' || date_mes ==null ) {
    return '';
  } else {
    var diasMes = new Date(año, mes, 0).getDate();
    var diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    
    if (array == true) {
      for (let index = 1; index <= diasMes; index++) { fecha_array.push(index); }
      return fecha_array;
    }
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
  if (typeof num === 'number' && isFinite(num)) {
    return num; // Ya es un número válido
  }

  if (typeof num === 'string') {
    const cleaned = num.replace(/,/g, '');
    const parsed = parseFloat(cleaned);
    return isNaN(parsed) ? num : parsed;
  }

  return num; // Si no es número ni string, devuélvelo tal cual
}

// Redondear a un exponente
function redondearExp(numero, digitos = 2) {
  numero = Number(numero);

  if (isNaN(numero)) return 0;

  const factor = 10 ** digitos;
  return Math.round((numero + Math.sign(numero) * Number.EPSILON) * factor) / factor;
}

//Redondear 2 decimales (1.56 = 1.60, 1.52 = 1.50), para dinero
function roundTwo(num) { return Number(+(Math.round(num + "e+1") + "e-1")).toFixed(2); }

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

function preservarNumeroLetra(str) {
  var string = "";
  if (str == "" || str == null || str === undefined) { } else {     
   string = str.replace(/[^a-zA-Z 0-9.]/g, '');    
  }
  return string;
}

// to miniscula
function convert_minuscula(e) { e.value = e.value.toLowerCase(); }

function quitar_punto(string){ 
  return string.replace(/\./g,'');
}

function replace_punto_a_guion(string) {
  return string.replace(/\./g,'-');
}

function quitar_guion(str) {

  if (str == '' || str == null ) {
    return "-";
  } else {
    return str.replace("-", "");
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
}

function mayus(e) {
  e.value = e.value.toUpperCase();
}

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
        $("#"+id+"_i").attr("src", "../assets/img/default/img_defecto.png");
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
				toastr.warning('La imagen: '+file.name.toUpperCase()+' es muy pesada. Tamaño máximo 10mb');
				$("#"+id+"_i").attr("src", "../assets/img/default/img_error.png");
				$("#"+id).val("");
			}
		}

	}else{

		toastr.error('Seleccione una Imagen');
    if (img_default == '' || img_default == null || img_default == false || img_default == true ) {
      $("#"+id+"_i").attr("src", "../assets/img/default/img_defecto.png");
    } else {
      $("#"+id+"_i").attr("src", img_default);
    }  
		$("#"+id+"_nombre").html("");
	}
}

/* PREVISUALIZA: img, pdf, doc, excel,  */
function addImageApplication(e, id, img_default='', width='100%', height='310', detalle_upload=false) {
  
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
        $(`#${id}_ver`).html('<img src="../assets/svg/doc_uploads.svg" alt="" width="50%" >'); 
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

          $(`#${id}_ver`).html( identificando_archivo(file.name, result, width, height ) );
          $(`.jq_image_zoom`).zoom({ on:'grab' });
          
          if (detalle_upload == true) {
            $(`#${id}_nombre`).html(`<div class="row">
              <div class="col-sm-1 col-md-2 col-lg-2 col-xl-2 mt-2 text-center">
                <button class="btn btn-danger  btn-xs h-100" onclick="${id}_eliminar();" type="button" data-bs-toggle="tooltip" title="Eliminar" ><i class='bx bx-trash' ></i></button>
              </div>
              <div class="col-sm-11 col-md-10 col-lg-10 col-xl-10 mt-2 text-left">
                <p class="my-0"><b>Nombre:</b><ins><i> ${file.name}</i></ins></p>
                <p class="my-0"><b>Tamaño:</b> ${formato_miles(sizemegaBytes)} mb</p>
                <p class="my-0"><b>Tipo:</b> ${file.type}</p>
              </div>              
            </div>`);
          } else {
            $(`#${id}_nombre`).html(`<div class="row">
              <div class="col-md-12">
                <i> ${file.name} </i>
              </div>
              <div class="col-md-12">
                <button class="btn btn-danger btn-block btn-xs" onclick="${id}_eliminar();" type="button" data-bs-toggle="tooltip" title="Eliminar" ><i class='bx bx-trash' ></i></button>
              </div>
            </div>`);
          }			
          $('[data-bs-toggle="tooltip"]').tooltip();		

          Swal.fire({
            position: 'top-end',  icon: 'success',  title: `El documento: ${file.name.toUpperCase()} es aceptado.`, showConfirmButton: false, timer: 1500
          });
				}

				reader.readAsDataURL(file);

			} else {
        Swal.fire({
          position: 'top-end',  icon: 'warning',  title: `El documento: ${file.name.toUpperCase()} es muy pesado.`, showConfirmButton: false,  timer: 1500
        })

        if (img_default == '' || img_default == null || img_default == false || img_default == true ) {
          $(`#${id}_ver`).html('<img src="../assets/svg/doc_uploads.svg" alt="" width="50%" >'); 
        } else {
          $(`#${id}_ver`).html(`<img src="${img_default}" alt="" width="50%" >`); 
        }
        
        $("#"+id+"_nombre").html("");
				$("#"+id).val("");
			}
		}
	}else{
    Swal.fire({
      position: 'top-end',  icon: 'error', title: 'Seleccione un documento',  showConfirmButton: false,  timer: 1500
    });

    if (img_default == '' || img_default == null || img_default == false || img_default == true ) {
      $(`#${id}_ver`).html('<img src="../assets/svg/doc_uploads.svg" alt="" width="50%" >'); 
    } else {
      $(`#${id}_ver`).html(`<img src="${img_default}" alt="" width="50%" >`); 
    }		 
    
		$("#"+id+"_nombre").html("");
    $("#"+id).val("");
	}	
  
}

// recargar un doc para ver
function re_visualizacion(id,  url_carpeta, width='100%', height='310') {
  console.log(id,  url_carpeta, width, height);
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

      $("#doc"+id+"_ver").html('<img src="../assets/svg/pdf_trasnparent.svg" alt="" width="50%" >');

		  $("#doc"+id+"_nombre").html("");

    } else {
      $("#doc"+id+"_ver").html( doc_view_extencion(antiguopdf, url_carpeta, width, height) );

      if (pdf_o_img(antiguopdf) == true) {
        toastr.success('Documento vizualizado correctamente!!!');
      } else {
        toastr.error('Documento NO TIENE PREVIZUALIZACION!!!');
      }
            
    }
    // console.log('hola'+dr);
  }else{

    pdffile_url=URL.createObjectURL(pdffile);

    var sizeByte = pdffile.size; console.log(pdffile.type);
		var sizekiloBytes = parseInt(sizeByte / 1024);
		var sizemegaBytes = (sizeByte / 1000000);

    // cargamos la imagen adecuada par el archivo
    
    // toastr.error('Documento NO TIENE PREVIZUALIZACION!!!');
    toastr.success('Documento vizualizado correctamente!!!');
    $(`#doc${id}_ver`).html(identificando_archivo(pdffile.name, pdffile_url, width, height ));
    $(`.jq_image_zoom`).zoom({ on:'grab' });
    
    console.log(pdffile);
  }
}

function doc_view_extencion(filename, url_carpeta='',  width='50%', height='auto', error_img = false,  url_complete = false) {

  var html = ''; var extencion = '';
  var host = '';  var ruta = '';

  if (url_complete == true) {
    host =  `${url_carpeta}` ;
    ruta = host;
  } else {
    ruta =  `${window.location.origin}/corporacion_praga/${url_carpeta}/${filename}`;     
    host = window.location.host == 'localhost' || es_numero(parseFloat(window.location.host)) == true ? `${window.location.origin}/corporacion_praga/${url_carpeta}/${filename}` : `${window.location.origin}/${url_carpeta}/${filename}` ;    
  }  
  
  // cargamos la imagen adecuada par el archivo
  if ( UrlExists(host) != 200 ) { console.log('no existe');
    if (error_img == false || error_img == null ) {    
      html = `<div class="callout callout-danger">
        <p class="text-danger font-size-12px text-left">404 Documento no encontrado!!</p>
        <p class="font-size-10px text-left">Hubo un <b>error</b> al <b>encontrar este archivo</b> , los mas probable es que se haya eliminado, o se haya movido a otro lugar, se <b>recomienda editar</b> en su módulo correspodiente.</p>
      </div>`;
      extencion = extrae_extencion(filename);
    } else {
      var html_img_error =  `<img src="${error_img}" alt="" width="${width}" onerror="this.src='../assets/svg/404-v2.svg';"  >`;
      return html_img_error;      
    }
  }else  {
    html = identificando_archivo(filename, host, width, height );       
  }

  extencion = extrae_extencion(filename); 

  return html;
}

function doc_view_download_expand(filename, ruta='', nombre_decarga='', width='50%', height='auto', error_img = false) {

  var html = ''; var extencion = '';
  var expand_disabled = '';

  var ruta_file = window.location.host == 'localhost' || es_numero(parseFloat(window.location.host)) == true ? `${window.location.origin}/corporacion_praga/${ruta}/${filename}` : `${window.location.origin}/${ruta}/${filename}` ;
 
  // cargamos la imagen adecuada par el archivo
  if ( UrlExists(ruta_file) != 200 ) { console.log('no existe');
    if (error_img == false || error_img == null) {    
      html = `<div class="callout callout-danger">
        <p class="text-danger font-size-12px text-left">404 Documento no encontrado!!</p>
        <p class="font-size-10px text-left">Hubo un <b>error</b> al <b>encontrar este archivo</b> , los mas probable es que se haya eliminado, o se haya movido a otro lugar, se <b>recomienda editar</b> en su módulo correspodiente.</p>
      </div>`;
      extencion = extrae_extencion(filename);
      return html
    } else {
      var html_img_error = `<img src="${error_img}" alt="" width="${width}" onerror="this.src='../assets/svg/404-v2.svg';"  >`;
      return html_img_error;     
    }
  }else {

    html = identificando_archivo(filename, ruta_file, width, height);
    extencion = extrae_extencion(filename);
    expand_disabled = pdf_o_img(filename) ? '' : 'disabled';  
  
  }       

  var div_html = `<div class="row">
    <div class="col-6 col-md-6 text-center">
      <a class="btn btn-sm btn-block btn-warning" href="${ruta_file}" download="${nombre_decarga}" type="button"><i class="fas fa-download"></i> Descargar</a>
    </div>
    <div class="col-6 col-md-6 text-center">
      <a class="btn btn-sm btn-block btn-info ${expand_disabled}" href="${ruta_file}" target="_blank" type="button"><i class="fas fa-expand"></i> Ver completo</a>
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
    extrae_extencion(filename) == "jpeg" || extrae_extencion(filename) == "jpg" || extrae_extencion(filename) == "JPG" || extrae_extencion(filename) == "jpe" ||
    extrae_extencion(filename) == "jfif" || extrae_extencion(filename) == "gif" || extrae_extencion(filename) == "png" ||
    extrae_extencion(filename) == "tiff" || extrae_extencion(filename) == "tif" || extrae_extencion(filename) == "webp" ||
    extrae_extencion(filename) == "bmp" || extrae_extencion(filename) == "svg" || extrae_extencion(filename) == "avif" ) {
    html = `<i class="fas fa-file-image ${(color_class==''? 'text-primary': color_class)} ${font_size_class}"></i>`;    
  }else{
    html = `<i class="fas fa-file-alt ${color_class} ${font_size_class}"></i>`;    
  }
  return html;
}

function identificando_archivo(filename, ruta, width = '100%', height = 'auto') {
  console.log(filename, width, height);
  if ( extrae_extencion(filename) == "xls") {
    return `<img src="../assets/svg/xls.svg" alt="" width="50%" height="50%" >`;    
  } else if ( extrae_extencion(filename) == "xlsx" ) {    
    return `<img src="../assets/svg/xlsx.svg" alt="" width="50%" height="50%" >`;
  }else if ( extrae_extencion(filename) == "csv" ) {
    return `<img src="../assets/svg/csv.svg" alt="" width="50%" height="50%" >`;
  }else if ( extrae_extencion(filename) == "xlsm" ) {
    return `<img src="../assets/svg/xlsm.svg" alt="" width="50%" height="50%" >`;
  }else if ( extrae_extencion(filename) == "xlsb" ) {
    return `<img src="../assets/svg/xlsb.svg" alt="" width="50%" height="50%" >`;
  }else if ( extrae_extencion(filename) == "docx" ||  extrae_extencion(filename) == "docm"  || extrae_extencion(filename) == "dot" ||  extrae_extencion(filename) == "dotx" ||  extrae_extencion(filename) == "dotm") {
    return `<img src="../assets/svg/docx.svg" alt="" width="50%" height="50%" >`;
  }else if ( extrae_extencion(filename) == "doc") {
    return `<img src="../assets/svg/doc.svg" alt="" width="50%" height="50%" >`;
  }else if ( extrae_extencion(filename) == "dwg") {
    return `<img src="../assets/svg/dwg.svg" alt="" width="50%" height="50%" >`;
  }else if ( extrae_extencion(filename) == "zip" || extrae_extencion(filename) == "rar" || extrae_extencion(filename) == "iso") {
    return `<img src="../assets/img/default/zip.png" alt="" width="50%" height="50%" >`;
  }else if ( extrae_extencion(filename) == "pdf" || extrae_extencion(filename) == "PDF" ) {
    //recomendado - height="210" 
    return `<iframe src="${ruta}" onerror="this.src='../assets/svg/404-v2.svg';" frameborder="0" scrolling="no" width="${width}" height="${height}"> </iframe>`;
  } else if ( extrae_extencion(filename) == "pfx" || extrae_extencion(filename) == "p12" ) {    
    return `<img src="../assets/img/default/pfx.jpg" alt="" width="50%" >`;
  } else if (
    extrae_extencion(filename) == "jpeg" || extrae_extencion(filename) == "jpg" || extrae_extencion(filename) == "JPG" || extrae_extencion(filename) == "jpe" ||
    extrae_extencion(filename) == "jfif" || extrae_extencion(filename) == "gif" || extrae_extencion(filename) == "png" ||
    extrae_extencion(filename) == "tiff" || extrae_extencion(filename) == "tif" || extrae_extencion(filename) == "webp" ||
    extrae_extencion(filename) == "bmp" || extrae_extencion(filename) == "svg" || extrae_extencion(filename) == "avif" ) {
    return `<center><span class="jq_image_zoom"><img id="_cargando_img_" src="${ruta}" alt="" width="${width}" onerror="this.src='../assets/svg/404-v2.svg';"  ></span></center>`;    
  }else{
    return `<img src="../assets/svg/doc_si_extencion.svg" alt="" width="50%" height="50%"  >`;
  }
}

function extrae_extencion(filename) {   
  if (filename == "" || filename == null || filename === undefined) { return '';  }else{ return  filename.split(".").pop(); }
}

function pdf_o_img(filename) {
  data = false;
  if ( extrae_extencion(filename) == "pdf" ) {
    //recomendado - height="210" 
    data = true;

  } else if (
    extrae_extencion(filename) == "jpeg" || extrae_extencion(filename) == "jpg" || extrae_extencion(filename) == "JPG" || extrae_extencion(filename) == "jpe" ||
    extrae_extencion(filename) == "jfif" || extrae_extencion(filename) == "gif" || extrae_extencion(filename) == "png" ||
    extrae_extencion(filename) == "tiff" || extrae_extencion(filename) == "tif" || extrae_extencion(filename) == "webp" ||
    extrae_extencion(filename) == "bmp" || extrae_extencion(filename) == "svg" || extrae_extencion(filename) == "avif" ) {

    data = true;
    
  }
  return data;
}

function es_img(filename) {
  data = false;
  if (
    extrae_extencion(filename) == "jpeg" || extrae_extencion(filename) == "jpg" || extrae_extencion(filename) == "JPG" || extrae_extencion(filename) == "jpe" ||
    extrae_extencion(filename) == "jfif" || extrae_extencion(filename) == "gif" || extrae_extencion(filename) == "png" ||
    extrae_extencion(filename) == "tiff" || extrae_extencion(filename) == "tif" || extrae_extencion(filename) == "webp" ||
    extrae_extencion(filename) == "bmp" || extrae_extencion(filename) == "svg" || extrae_extencion(filename) == "avif") {

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
function buscar_sunat_reniec(formulario= '', input='', tipo_documento, dniruc, nombre, apellido, direccion, distrito, titular, choice_distrito = false) {
  //console.log(input);

  $(`#search${input}`).hide(); $(`#charge${input}`).show();

  let tipo_doc = $(tipo_documento).val();

  let dni_ruc = $(dniruc).val(); 
   
  if (tipo_doc == "1") { // DNI

    if (dni_ruc.length == "8") {

      $.post("../ajax/ajax_general.php?op=reniec_jdl", { dni: dni_ruc }, function (data, status) {

        data = JSON.parse(data);  //console.log(data);

        if (data == null) {

          $(`#search${input}`).show(); $(`#charge${input}`).hide();
          $(nombre).val(''); $(apellido).val(''); $(titular).val('');          
          toastr_error('Error!!', 'El sistema de BUSQUEDA esta en mantenimiento.', 700);
          
        } else {
          if (data.success == false) {

            $(`#search${input}`).show(); $(`#charge${input}`).hide();

            toastr_error('Error de búsqueda!!', 'Es probable que el sistema de busqueda esta en mantenimiento o los datos no existe en la RENIEC!!!', 700);
          } else {

            $(`#search${input}`).show();  $(`#charge${input}`).hide();

            $(nombre).val(data.nombres );
            $(apellido).val( data.apellidoPaterno + " " + data.apellidoMaterno);
            $(titular).val(data.nombres + " " + data.apellidoPaterno + " " + data.apellidoMaterno);           

            if (data.direccion != '' && data.direccion != null) {   $(direccion).val(data.direccion); }
            if (data.distrito != '' && data.distrito != null) {  $(distrito).val(data.distrito).trigger('change'); }
            if (choice_distrito == true) {
              if (data.distrito != '' && data.distrito != null) { document.querySelector(distrito).choices.setChoiceByValue(data.distrito); }
            } 

            toastr_success('Éxito!!!', `Persona encontrada!!!,  <span class="fs-11">${data.version}</span>`, 700);
          } 
        }
        $(formulario).valid();
      });
    } else {

      $(dniruc).addClass("is-invalid");
      $(`#search${input}`).show();   $(`#charge${input}`).hide();
      $(nombre).val(''); $(apellido).val(''); $(titular).val('');
      toastr_info('Alerta!!', 'Asegurese de que el DNI tenga 8 dígitos!!!', 700);
    }
  } else if (tipo_doc == "6") {  // RUC

    if (dni_ruc.length == "11") {
      $.post("../ajax/ajax_general.php?op=sunat_jdl", { ruc: dni_ruc }, function (data, status) {

        data = JSON.parse(data);    // console.log(data);

        if (data == null) {
          $(`#search${input}`).show();  $(`#charge${input}`).hide();           
          toastr_error('Error!!', 'El sistema de BUSQUEDA esta en mantenimiento.', 700);
          
        } else {

          if (data.success == false) {

            $(`#search${input}`).show(); $(`#charge${input}`).hide();             
            $(nombre).val(''); $(apellido).val('');  $(direccion).val(''); $(titular).val('');  
            toastr_error('Error de búsqueda', 'Datos no encontrados en la SUNAT!!!', 700);
            
          } else {

            if (data.estado == "ACTIVO") {

              $(`#search${input}`).show(); $(`#charge${input}`).hide();

              var api_razonSocial     = (data.razonSocial == null ? "" : data.razonSocial);
              var api_nombreComercial = (data.nombreComercial == null ? "" : data.nombreComercial);
              var api_direccion       = (data.direccion == null ? "" : data.direccion);
              var api_distrito        = (data.distrito == null ? "" : data.distrito);

              $(nombre).val(api_razonSocial); 
              $(apellido).val(api_nombreComercial);  
              $(direccion).val(api_direccion);
              $(distrito).val(api_distrito).trigger('change');

              if (choice_distrito == true) {
                if (data.distrito != '' && data.distrito != null) { document.querySelector(distrito).choices.setChoiceByValue(data.distrito); }
              } 
              
              toastr_success('', 'Datos encontrados!!', 700);

            } else {

              toastr_info('Alerta!!', 'Se recomienda NO generar FACTURAS ó BOLETAS!!!', 700);

              $(`#search${input}`).show();  $(`#charge${input}`).hide();

              var api_razonSocial     = (data.razonSocial == null ? "" : data.razonSocial);
              var api_nombreComercial = (data.nombreComercial == null ? "" : data.nombreComercial);
              var api_direccion       = (data.direccion == null ? "" : data.direccion);
              var api_distrito        = (data.distrito == null ? "" : data.distrito);

              $(nombre).val(api_razonSocial); 
              $(apellido).val(api_nombreComercial);  
              $(direccion).val(api_direccion);
              $(distrito).val(api_distrito).trigger('change');

              if (choice_distrito == true) {
                if (data.distrito != '' && data.distrito != null) { document.querySelector(distrito).choices.setChoiceByValue(data.distrito); }
              } 
            }
          }
        } 
        $(formulario).valid();         
      });
    } else {

      $(dniruc).addClass("is-invalid");
      $(`#search${input}`).show();  $(`#charge${input}`).hide();
      $(nombre).val(''); $(apellido).val('');  $(direccion).val(''); $(titular).val('');      

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

/*  ══════════════════════════════════════════ - M E N S A J E S - ══════════════════════════════════════════ */

function ok_dowload_doc() { toastr.success("El documento se descargara en breve!!"); }

function error_dowload_doc() { toastr.success("Hubo un ERROR en la descarga, reintente nuevamente!!"); }

function no_doc() { toastr.error("No hay DOC disponible, suba un DOC en el apartado de editar!!") }

/*  ══════════════════════════════════════════ - O T R O S - ══════════════════════════════════════════ */

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
  
    //El siguiente fragmento de codigo lo uso para igualar la fecha de nacimiento con la fecha de hoy del usuario
    let d = new Date(),    month = '' + (d.getMonth() + 1),    day = '' + d.getDate(),   year = d.getFullYear();
    
    if (month.length < 2) 
      month = '0' + month;
    if (day.length < 2) 
      day = '0' + day;
    d=[year, month, day].join('-')

    /*------------*/
    var hoy = new Date(d);//fecha del sistema con el mismo formato que "fechaUsuario"

    var cumpleanos = new Date(fechaUsuario);
    
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
  
  var host = window.location.host == 'localhost' || es_numero(parseFloat(window.location.host)) == true ? `${window.location.origin}/corporacion_praga/${url}` : `${window.location.origin}/${url}`;
  
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

function dowload_pdf() {
  toastr.success("El documento se descargara en breve!!");
}

function quitar_igv_del_precio(precio , igv, tipo ) {
  
  var precio_sin_igv = 0;

  switch (tipo) {
    case 'decimal':

      // validamos el valor del igv ingresado
      if (igv > 0 && igv <= 1) { 
        $("#tipo_gravada").val('GRAVADA');
        $(".tipo_gravada").html('GRAVADA');
        $(".val_igv").html(`IGV (${(parseFloat(igv) * 100).toFixed(2)}%)`); 
      } else { 
        igv = 0; 
        $(".val_igv").html('IGV (0%)'); 
        $("#tipo_gravada").val('NO GRAVADA');
        $(".tipo_gravada").html('NO GRAVADA');
      }

      if (parseFloat(precio) != NaN && igv > 0 ) {
        precio_sin_igv = ( parseFloat(precio) * 100 ) / ( ( parseFloat(igv) * 100 ) + 100 )
      }else{
        precio_sin_igv = precio;
      }
    break;

    case 'entero':
      
      // validamos el valor del igv ingresado
      if (igv > 0 && igv <= 100) { 
        $("#tipo_gravada").val('GRAVADA');
        $(".tipo_gravada").html('GRAVADA');
        $(".val_igv").html(`IGV (${parseFloat(igv)}%)`); 
      } else { 
        igv = 0; 
        $(".val_igv").html('IGV (0%)'); 
        $("#tipo_gravada").val('NO GRAVADA');
        $(".tipo_gravada").html('NO GRAVADA');
      }

      if (parseFloat(precio) != NaN && igv > 0 ) {
        precio_sin_igv = ( parseFloat(precio) * 100 ) / ( parseFloat(igv)  + 100 )
      }else{
        precio_sin_igv = precio;
      }
    break;
  
    default:
      $(".val_igv").html('IGV (0%)');
      toastr_error("Vacio!!","No has difinido un tipo de calculo de IGV", 700);
    break;
  } 
  
  return precio_sin_igv; 
}

// Extrae el numero de documento de un SELECT2
function extrae_ruc(select = null, input = null) {
  $(input).val('');
  if (select) {
    if ($(select).select2("val") == null || $(select).select2("val") == '') { 
      $('.btn-editar-proveedor').addClass('disabled').attr('data-original-title','Seleciona un proveedor');
    } else if ($(select).select2("val") == 1) {    
      $('.btn-editar-proveedor').addClass('disabled').attr('data-original-title','No editable');      
    } else{
      var name_proveedor = $(select).select2('data')[0].text;
      $('.btn-editar-proveedor').removeClass('disabled').attr('data-original-title',`Editar: ${recorte_text(name_proveedor, 15)}`);

      // guardamos el numero de documento selecionado
      if (input) { $(input).val($(select).select2('data')[0].element.attributes.ruc_dni.value ); }         
    }
  }
  
  $('[data-bs-toggle="tooltip"]').tooltip();
}

function replicar_value_input( input_entrada, input_salida) {
  var value = $(input_entrada).val(); $(`${input_salida}`).val(value).trigger("change");
}

function replicar_value_input2(id, name_input, valor) {
  var value = $(valor).val(); 
  $(`${name_input}`).val(value).trigger("change");
}

function valor_is_checked(input, val_is_true, val_is_false) {
  if ($(input).is(':checked')) {
    $(input).val(val_is_true)
  } else {
    $(input).val(val_is_false)
  }
}

/**
 * Funcion para agergar toltip personalizado:
 *
 * @param {text} div              #identificador.
 * @param {text} title_toltip     Mensaje del toltip.
*/
function add_tooltip_custom(div, title_toltip) {
  $(div).attr('data-bs-toggle', 'tooltip').attr('data-bs-original-title', title_toltip);
  $('[data-bs-toggle="tooltip"]').tooltip();
}

function is_mobil() {
  if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){
      return true;
  } else{
      return false;
  }
}

function get_uid() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
      return v.toString(16);
  });
}

/**
 * Funcion para agergar toltip personalizado:
 *
 * @param {text} clase_id     Nombre de la clase o id a usar.
 * @param {number} cant    Cantidad a aumentar.
*/
function contarDivs(clase_id, cant = 0) {
  return $(`${clase_id}`).length + cant;
}

/**
 * Funcion para agergar toltip personalizado:
 *
 * @param {text} clase_id     Nombre de la clase o id a usar.
*/
function contarDivsArray(clase_id) {
  return $(`${clase_id}`).length ;
}

/**
 * Funcion para agergar toltip personalizado:
 *
 * @param {text} clase_id  Nombre de la clase o ID a usar.
 * @param {text} atributo  Nombre de la Atributo a usar.
 * @param {text} prefijo   Nombre de la prefijo que esta dentro del atributo a usar.
*/
function renombrarInputsArray(clase_id, atributo, prefijo) {
  $(`${clase_id}`).each((index, element) => {
    $(element).attr(atributo, `${prefijo}[${index}]`);
  });
}

function renombrarOtroInputsArrayMultiple(clase_id, atributo, prefijo) {
  const grupos = document.querySelectorAll(clase_id);
  grupos.forEach((grupo, index) => {
    const checkboxes = grupo.querySelectorAll(`input[${atributo}^="${prefijo}"]`);
    checkboxes.forEach(cb => {

      if (atributo == 'name') {
        cb.name = `${prefijo}[${index}][]`; // solo modificamos
      } else if (atributo == 'id') {
        cb.id = `${prefijo}[${index}][]`; // solo modificamos
      }
      
    });
  });
}

function renombrarDivAttrArray(clase_id, atributo, prefijo) {
  $(`${clase_id}`).each((index, element) => {
    $(element).attr(atributo, `${index}`);
  });
}

function renombrarDivArray(clase_id, prefijoHtml) {
  $(`${clase_id}`).each((index, element) => {
    $(element).html( `${prefijoHtml} ${index + 1}`);
  });
}

/**
 * Funcion para agergar toltip personalizado:
 *
 * @param {text} clase_id  Nombre de la clase o ID a usar.
 * @param {text} atributo  Nombre de la Atributo a usar.
 * @param {text} prefijo   Nombre de la prefijo que esta dentro del atributo a usar.
*/
function renombrarInputsArrayContenedor(clase_id, atributo, prefijo) {  
  $(`${clase_id}`).each((index, div) => {                               // Seleccionar todos los divs con la clase especificada
    let input;    
    const fieldset = $(div).find("fieldset");
    if (fieldset.length && fieldset.find("input").length) {             // Verificar si existe un fieldset dentro del div      
      input = fieldset.find("input");                                   // Si el fieldset existe y contiene un input, buscar el input dentro del fieldset
    } else {      
      input = $(div).find("input");                                     // Si no existe fieldset o no tiene inputs, buscar el input directamente en el div
    }    
    if (input.length) { input.attr(atributo, `${prefijo}[${index}]`); } // Renombrar el atributo del input si existe
  });
}

/**
 * Funcion para agergar toltip personalizado:
 *
 * @param {text} bytes  Tamaño en Bytes para calcular.
*/
function formatFileSize(bytes) {
  if (bytes === 0) return "0 Bytes";

  const sizes = ["Bytes", "KB", "MB", "GB", "TB"];
  const i = Math.floor(Math.log(bytes) / Math.log(1024)); // Determina el índice basado en el tamaño
  const size = bytes / Math.pow(1024, i); // Calcula el tamaño en la unidad adecuada

  return `${size.toFixed(2)} ${sizes[i]}`; // Retorna el tamaño con dos decimales
}


function div_ocultar_mostrar(div_ocultar, div_mostrar) { 
  $(div_ocultar).hide();
  $(div_mostrar).show();  
}