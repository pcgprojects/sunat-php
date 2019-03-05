<?php

error_reporting(E_ERROR | E_PARSE);
include dirname(__FILE__) . "/constants.php";
require dirname(__FILE__) . "/functions.php";
date_default_timezone_set('GTM');


function prepareJSON($input) {

    //This will convert ASCII/ISO-8859-1 to UTF-8.
    //Be careful with the third parameter (encoding detect list), because
    //if set wrong, some input encodings will get garbled (including UTF-8!)
    $imput = mb_convert_encoding($input, 'UTF-8', 'ASCII,UTF-8,ISO-8859-1');

    //Remove UTF-8 BOM if present, json_decode() does not like it.
    if(substr($input, 0, 3) == pack("CCC", 0xEF, 0xBB, 0xBF)) $input = substr($input, 3);

    return $input;
}


function prepareCharset($str) {

    // set default encode
    mb_internal_encoding('UTF-8');

    // pre filter
    if (empty($str)) {
        return $str;
    }

    // get charset
    $charset = mb_detect_encoding($str, array('ISO-8859-1', 'UTF-8', 'ASCII'));

    if (stristr($charset, 'utf') || stristr($charset, 'iso')) {
        $str = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', utf8_decode($str));
    } else {
        $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    }

    // remove BOM
    $str = urldecode(str_replace("%C2%81", '', urlencode($str)));

    // prepare string
    return $str;
}

/**
*Generando archivo txt
*
*/
function generar_txt($file,$file_,$lastId){
	$data = file_get_contents($file);
	
	$data = prepareCharset($data);
	/*Validar errores en JSON*/
	
	
	$json = json_decode( $data, true, 9 );
	$json_errors = array(
		JSON_ERROR_NONE => 1,
		JSON_ERROR_DEPTH => -1,
		JSON_ERROR_CTRL_CHAR => -1,
		JSON_ERROR_SYNTAX =>999,
	);
	
	$titulo_arr = explode("-", $file_);	
	$correlativo = str_replace(".json","",$titulo_arr[3]);
	$correlativo = str_pad($correlativo, 8, "0", STR_PAD_LEFT);
	$correlativoSqlite = $lastId;	
	$tipoDocumento = $titulo_arr[1];
	$fechaOriginal = date("d-m-Y H:i:s", filemtime($file));
	
	if($json_errors[json_last_error()] === 999){
		$dataArr= json_decode(prepareJSON($data), true);
		
		if($tipoDocumento === '01'){
			/*Factura*/
			$dias = obtenerDiasXRUC($dataArr['cabecera']['numDocUsuario']);
			$fechaVcto = date("d/m/Y", strtotime(date("d-m-Y H:i:s",strtotime($fechaOriginal."+ $dias days"))));
		}else{
			/*Boleta*/
			$fechaVcto = date("d/m/Y", filemtime($file));
		}
		$datosExtras = [
			'fechaEmision' => date("d/m/Y H:i:s", filemtime($file)),
			'fechaVcto' =>  $fechaVcto,
			'serieDocumento' => $titulo_arr[2]
		];
		
		$titulo =  genera_titulo_($dataArr['cabecera'],$correlativoSqlite,$tipoDocumento);
		$cabecera = genera_cabecera_($dataArr['cabecera'],$correlativo,$correlativoSqlite,$tipoDocumento,$datosExtras);
		
		
		$detalle = genera_detalle_($dataArr['cabecera'],$dataArr['detalle']);
		$cliente = genera_detalle_cliente_($dataArr['cabecera']);
		
		if((double) $dataArr['cabecera']['sumDsctoGlobal'] > 0){
			$descuentoGlobal = "\r\n".genera_detalle_descuentos($dataArr);
		}else{
			$descuentoGlobal = '';
		}
		
		
		genera_archivo($titulo,$cabecera,$detalle,$cliente,$descuentoGlobal);
		
	}else{
		$json = json_decode($data);
	
		if($tipoDocumento === '01'){
			/*Factura*/
			$dias = obtenerDiasXRUC($json->cabecera->numDocUsuario);
			$fechaVcto = date("d/m/Y", strtotime(date("d-m-Y H:i:s",strtotime($fechaOriginal."+ $dias days"))));
		}else{
			/*Boleta*/
			$fechaVcto = date("d/m/Y", filemtime($file));
		}
		$datosExtras = [
			'fechaEmision' => date("d/m/Y H:i:s", filemtime($file)),
			'fechaVcto' =>  $fechaVcto,
			'serieDocumento' => $titulo_arr[2]
		];
		
		$titulo =  genera_titulo($json->cabecera,$correlativoSqlite,$tipoDocumento);
		$cabecera = genera_cabecera($json->cabecera,$correlativo,$correlativoSqlite,$tipoDocumento,$datosExtras);
		
		
		$detalle = genera_detalle($json->cabecera,$json->detalle);
		$cliente = genera_detalle_cliente($json->cabecera);
		
		if((double) $json->cabecera->sumDsctoGlobal > 0){
			$descuentoGlobal = "\r\n".genera_detalle_descuentos($json);
		}else{
			$descuentoGlobal = '';
		}
		
		
		genera_archivo($titulo,$cabecera,$detalle,$cliente,$descuentoGlobal);		
	}
}


function genera_titulo($cabecera,$correlativoSqlite,$tipoDocumento){
	//RUC
	$nro_1= CONST_RUC;
	//Código sucursal emisor
	$nro_2= CONST_SUCURSAL;
	//Correlativo
	$nro_3= $correlativoSqlite;
	//Fecha envío
	$nro_4= formatea_fecha($cabecera->fecEmision);
	//Código tipo documento
	$nro_5= $tipoDocumento;
	//Versión del TXT
	$nro_6= CONST_CLIENT_VERSION_TXT;
	
	$titulo = $nro_1.$nro_2.$nro_3.$nro_4.$nro_5.$nro_6;
	
	return $titulo;
}

function genera_titulo_($cabecera,$correlativoSqlite,$tipoDocumento){
	//RUC
	$nro_1= CONST_RUC;
	//Código sucursal emisor
	$nro_2= CONST_SUCURSAL;
	//Correlativo
	$nro_3= $correlativoSqlite;
	//Fecha envío
	$nro_4= formatea_fecha($cabecera['fecEmision']);
	//Código tipo documento
	$nro_5= $tipoDocumento;
	//Versión del TXT
	$nro_6= CONST_CLIENT_VERSION_TXT;
	
	$titulo = $nro_1.$nro_2.$nro_3.$nro_4.$nro_5.$nro_6;
	
	return $titulo;
}

function genera_cabecera($cabecera,$correlativo,$correlativoSqlite,$tipoDocumento,$datosExtras){	
	
	$usuario = '';
	$serie = $datosExtras['serieDocumento'];
	if($serie === 'F001'){
		$usuario = 'faccsl';
	}else if($serie === 'F002' || $serie === 'B002'){
		$usuario = 'admcsl';
	}
	$arrayCabecera = [
		CONST_CLIENT_VERSION_TXT,
		$correlativoSqlite, //Id documento correlativo
		$tipoDocumento, //Código tipo de documento
		CONST_TIPO_OPERACION, //Tipo de operación
		CONST_RUC,
		CONST_SUCURSAL, //Código sucursal SUNAT
		$datosExtras['fechaEmision'], //Fecha de emisión
		$datosExtras['fechaVcto'], //Fecha de vencimiento
		$cabecera->tipMoneda,
		$cabecera->mtoOperGravadas, //subtotal 10
		$cabecera->mtoDescuentos,//total, descuentos 11
		$cabecera->mtoOperGravadas, //total 12
		0,//total, ISC  13
		$cabecera->mtoIGV,//total, IGV  14
		0, //total, otros cargos 15
		0, //total, otros tributos 16
		$cabecera->mtoImpVenta, //total 17
		0, //Tiene documento referencia 18
		'na', //Tiene documento referencia 19
		'', //Dejar en blanco 20
		'', //Dejar en blanco 21
		$serie, //Dejar en blanco 22 del TITULO
		$correlativo, //Dejar en blanco 23  CONSULTAR JDM
		$usuario, //Dejar en blanco 24 DE BASE DE DATOS
		'', //Dejar en blanco 25 obs CONSULTAR
		'009', //Condición de pago 26
		'', //Numero de la orden de compra 27
		'', //Guia de remisión 28
		((int)$cabecera->mtoImpVenta > 700)? 1:0, //Mensaje detracción 29  mayor a 700so
		0, //Transferencia gratuita 30
		0, //Documento relacionado 31
		1, //Descuento global 32
		0, //Otros cargos globales  33
		0, //Anticipo  34
	];
	
	$cabecera = implode("|", $arrayCabecera);
	
	return $cabecera;
}

function genera_cabecera_($cabecera,$correlativo,$correlativoSqlite,$tipoDocumento,$datosExtras){	
	
	$usuario = '';
	$serie = $datosExtras['serieDocumento'];
	if($serie === 'F001'){
		$usuario = 'faccsl';
	}else if($serie === 'F002' || $serie === 'B002'){
		$usuario = 'admcsl';
	}
	$arrayCabecera = [
		CONST_CLIENT_VERSION_TXT,
		$correlativoSqlite, //Id documento correlativo
		$tipoDocumento, //Código tipo de documento
		CONST_TIPO_OPERACION, //Tipo de operación
		CONST_RUC,
		CONST_SUCURSAL, //Código sucursal SUNAT
		$datosExtras['fechaEmision'], //Fecha de emisión
		$datosExtras['fechaVcto'], //Fecha de vencimiento
		$cabecera['tipMoneda'],
		$cabecera['mtoOperGravadas'], //subtotal 10
		$cabecera['mtoDescuentos'],//total, descuentos 11
		$cabecera['mtoOperGravadas'], //total 12
		0,//total, ISC  13
		$cabecera['mtoIGV'],//total, IGV  14
		0, //total, otros cargos 15
		0, //total, otros tributos 16
		$cabecera['mtoImpVenta'], //total 17
		0, //Tiene documento referencia 18
		'na', //Tiene documento referencia 19
		'', //Dejar en blanco 20
		'', //Dejar en blanco 21
		$serie, //Dejar en blanco 22 del TITULO
		$correlativo, //Dejar en blanco 23  CONSULTAR JDM
		$usuario, //Dejar en blanco 24 DE BASE DE DATOS
		'', //Dejar en blanco 25 obs CONSULTAR
		'009', //Condición de pago 26
		'', //Numero de la orden de compra 27
		'', //Guia de remisión 28
		((int)$cabecera['mtoImpVenta'] > 700)? 1:0, //Mensaje detracción 29  mayor a 700so
		0, //Transferencia gratuita 30
		0, //Documento relacionado 31
		1, //Descuento global 32
		0, //Otros cargos globales  33
		0, //Anticipo  34
	];
	
	$cabecera_ = implode("|", $arrayCabecera);
	
	return $cabecera_;
}

function genera_detalle($cabecera,$detalle){
	
	$detalleFull = "";
	$i = 1;
	
	$totalDescuento = $cabecera->mtoDescuentos;
	
	
	$cantidadProductos = count($detalle);
	$descuentoUnitario = (double) $totalDescuento / $cantidadProductos;
	$porcentaje_dscto = (double) $descuentoUnitario / (double)$totalDescuento;
	
	foreach($detalle as $det){
		
		$descripcion = $det->desItem;
		$descripcion = str_replace("<![CDATA[","",$descripcion);
		$descripcion = str_replace("]]>","",$descripcion);
		
		$IGV = (double) $det->mtoValorVentaItem * 0.18;
		$importe_total = (double)$det->mtoValorVentaItem + $IGV;
		//$descuentoUnitario, //Descuento 10
		//$porcentaje_dscto, //Porcentaje descuento 0-1   11
		$arrayDetalle = [
			$i,  // Id detalle  1
			'SERVICIO', // Tipo ítem  2 
			$det->tipAfeIGV, //tipo afectación al IGV	 3
			$det->codUnidadMedida, // Unidad medida 4
			0, //Código interno 5 ***** CONSULTAR JDM
			CONST_CODIGO_PRODUCTO, //Código producto SUNAT 6 ***** Consultar con Ing. anterior 85121600,85121800, 85121900
			$descripcion, // Descripción 7  **** Consultar si queda con CDATA o se le quita
			$det->ctdUnidadItem, // Cantidad 8
			$det->mtoValorUnitario, //Valor unitario 9
			0, //Descuento 10
			0,//Porcentaje descuento 0-1   11
			$det->mtoValorVentaItem, //Base imponible 12
			$IGV , //IGV  13
			'0.00', //ISC 14
			'0.00', //Porcentaje ISC  15
			0, // Otros cargos 16
			0, // Porcentaje otros cargos 17
			0, // Otros tributos 18
			0, // Porcentaje otros tributos  19
			$importe_total, // Importe total 20
		];
		$detalle_ = implode("|", $arrayDetalle);

		$detalleFull .= $detalle_."\r\n";

		$i++;
	}
	return $detalleFull;
}


function genera_detalle_($cabecera,$detalle){
	
	$detalleFull = "";
	$i = 1;
	
	
	$totalDescuento = $cabecera['mtoDescuentos'];
	
	
	
	$cantidadProductos = count($detalle);
	$descuentoUnitario = (double) $totalDescuento / $cantidadProductos;
	$porcentaje_dscto = (double) $descuentoUnitario / (double)$totalDescuento;
	
	foreach($detalle as $det){
		
		$descripcion = $det['desItem'];
		$descripcion = str_replace("<![CDATA[","",$descripcion);
		$descripcion = str_replace("]]>","",$descripcion);
		
		
		$IGV = (double) $det['mtoValorVentaItem'] * 0.18;
		$importe_total = (double)$det['mtoValorVentaItem'] + $IGV;
		//$descuentoUnitario, //Descuento 10
		//$porcentaje_dscto, //Porcentaje descuento 0-1   11
		$arrayDetalle = [
			$i,  // Id detalle  1
			'SERVICIO', // Tipo ítem  2 
			$det['tipAfeIGV'], //tipo afectación al IGV	 3
			$det['codUnidadMedida'], // Unidad medida 4
			0, //Código interno 5 ***** CONSULTAR JDM
			CONST_CODIGO_PRODUCTO, //Código producto SUNAT 6 ***** Consultar con Ing. anterior 85121600,85121800, 85121900
			$descripcion, // Descripción 7  **** Consultar si queda con CDATA o se le quita
			$det['ctdUnidadItem'], // Cantidad 8
			$det['mtoValorUnitario'], //Valor unitario 9
			0, //Descuento 10
			0,//Porcentaje descuento 0-1   11
			$det['mtoValorVentaItem'], //Base imponible 12
			$IGV, //IGV  13
			'0.00', //ISC 14
			'0.00', //Porcentaje ISC  15
			0, // Otros cargos 16
			0, // Porcentaje otros cargos 17
			0, // Otros tributos 18
			0, // Porcentaje otros tributos  19
			$importe_total, // Importe total 20
		];
		$detalle_ = implode("|", $arrayDetalle);

		$detalleFull .= $detalle_."\r\n";

		$i++;
	}
	return $detalleFull;
}

function genera_detalle_cliente($cabecera){
	
	$rznSocialUsuario = $cabecera->rznSocialUsuario;
		$rznSocialUsuario = str_replace("<![CDATA[","",$rznSocialUsuario);
		$rznSocialUsuario = str_replace("]]>","",$rznSocialUsuario);
		
	$arrayCabecera = [
		'Cliente', //Id diferencial 1
		$cabecera->tipDocUsuario, // dni=1 , ruc=6   2
		$cabecera->numDocUsuario, // Documento del cliente  3
		$rznSocialUsuario, // Apellidos y nombres o razón social del cliente 4
		'', // Nombre comercial del cliente  5
		'Pe', // País 6
		'110101', // Ubigeo 7
		'', // Dirección sacarlo de Base de datos 8
		'', // Teléfono 9
		'', // Correo 10
	];
	
	$cabecera = implode("|", $arrayCabecera);
	
	return $cabecera;
}


function genera_detalle_cliente_($cabecera){
	
	$rznSocialUsuario = $cabecera->rznSocialUsuario;
		$rznSocialUsuario = str_replace("<![CDATA[","",$rznSocialUsuario);
		$rznSocialUsuario = str_replace("]]>","",$rznSocialUsuario);
		
	$arrayCabecera = [
		'Cliente', //Id diferencial 1
		$cabecera['tipDocUsuario'], // dni=1 , ruc=6   2
		$cabecera['numDocUsuario'], // Documento del cliente  3
		$rznSocialUsuario, // Apellidos y nombres o razón social del cliente 4
		'', // Nombre comercial del cliente  5
		'Pe', // País 6
		'110101', // Ubigeo 7
		'', // Dirección sacarlo de Base de datos 8
		'', // Teléfono 9
		'', // Correo 10
	];
	
	$cabecera_ = implode("|", $arrayCabecera);
	
	return $cabecera_;
}

function genera_detalle_descuentos($json){
	
	$cabecera = $json->cabecera;
	$detalle = $json->detalle;
	$sumDsctoGlobal = (double) $cabecera->sumDsctoGlobal;
	$montoSumatoriaBase = 0;
	foreach($detalle as $det){
		$montoSumatoriaBase = (double) $montoSumatoriaBase +  (double)$det->mtoValorVentaItem;
	}
	
	$porcentajeDscto = ($sumDsctoGlobal / $montoSumatoriaBase);
	
	$arrayCabecera = [
		'Descuento', //Id diferencial 1
		$porcentajeDscto, // Porcentaje 2
		$sumDsctoGlobal, // Valor 3
		'02', // Código motivo de descuento 4
	];
	
	$cabecera = implode("|", $arrayCabecera);
	
	return $cabecera;
}

//limpiarCarpeta();
leer_archivos(CONST_RUTA_JSON);



?>

<script>
/*
setTimeout(function(){
   window.location.reload(1);
}, 5000);
*/
</script>
