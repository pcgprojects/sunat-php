<?php

error_reporting(E_ERROR | E_PARSE);
include dirname(__FILE__) . "/constants.php";
require dirname(__FILE__) . "/functions.php";
date_default_timezone_set('GTM');

/**
*Generando archivo txt
*
*/
function generar_txt($file,$file_){
	$data = file_get_contents($file);
	
	$json = json_decode($data);
	
	$titulo_arr = explode("-", $file_);	
	
	$correlativo = str_replace(".json","",$titulo_arr[3]);
	$correlativo = str_pad($correlativo, 10, "0", STR_PAD_LEFT);
	$tipoDocumento = $titulo_arr[1];
	$fechaOriginal = date("d-m-Y H:i:s", filemtime($file));
	
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
	
	$titulo =  genera_titulo($json->cabecera,$correlativo,$tipoDocumento);
	$cabecera = genera_cabecera($json->cabecera,$correlativo,$tipoDocumento,$datosExtras);
	$detalle = genera_detalle($json);
	$cliente = genera_detalle_cliente($json->cabecera);
	
	genera_archivo($titulo,$cabecera,$detalle,$cliente);
}


function genera_titulo($cabecera,$correlativo,$tipoDocumento){
	//RUC
	$nro_1= CONST_RUC;
	//Código sucursal emisor
	$nro_2= CONST_SUCURSAL;
	//Correlativo
	$nro_3= $correlativo;
	//Fecha envío
	$nro_4= formatea_fecha($cabecera->fecEmision);
	//Código tipo documento
	$nro_5= $tipoDocumento;
	//Versión del TXT
	$nro_6= CONST_CLIENT_VERSION_TXT;
	
	$titulo = $nro_1.$nro_2.$nro_3.$nro_4.$nro_5.$nro_6;
	
	return $titulo;
}

function genera_cabecera($cabecera,$correlativo,$tipoDocumento,$datosExtras){	
	
	$arrayCabecera = [
		CONST_CLIENT_VERSION_TXT,
		$correlativo, //Id documento correlativo
		$tipoDocumento, //Código tipo de documento
		CONST_TIPO_OPERACION, //Tipo de operación
		CONST_RUC,
		CONST_SUCURSAL, //Código sucursal SUNAT
		$datosExtras['fechaEmision'], //Fecha de emisión
		$datosExtras['fechaVcto'], //Fecha de vencimiento
		$cabecera->tipMoneda,
		$cabecera->mtoOperGravadas, //subtotal 10
		$cabecera->mtoDescuentos,//total, descuentos 11
		$cabecera->mtoImpVenta, //total 12
		0,//total, ISC  13
		$cabecera->mtoIGV,//total, IGV  14
		0, //total, otros cargos 15
		0, //total, otros tributos 16
		$cabecera->mtoImpVenta, //total 17
		0, //Tiene documento referencia 18
		'na', //Tiene documento referencia 19
		'', //Dejar en blanco 20
		'', //Dejar en blanco 21
		$datosExtras['serieDocumento'], //Dejar en blanco 22 del TITULO
		'', //Dejar en blanco 23  CONSULTAR JDM
		'', //Dejar en blanco 24 DE BASE DE DATOS
		'', //Dejar en blanco 25 obs CONSULTAR
		'009', //Condición de pago 26
		'', //Numero de la orden de compra 27
		'', //Guia de remisión 28
		((int)$cabecera->mtoImpVenta > 700)? 1:0, //Mensaje detracción 29  mayor a 700so
		0, //Transferencia gratuita 30
		0, //Documento relacionado 31
		0, //Descuento global 32
		0, //Otros cargos globales  33
		0, //Anticipo  34
	];
	
	$cabecera = implode("|", $arrayCabecera);
	
	return $cabecera;
}

function genera_detalle($json){
	$detalle = $json->detalle;
	$cabecera = $json->cabecera;
	$detalleFull = "";
	$i = 1;
	foreach($detalle as $det){
		
		$descripcion = $det->desItem;
		$descripcion = str_replace("<![CDATA[","",$descripcion);
		$descripcion = str_replace("]]>","",$descripcion);
		
		$arrayDetalle = [
			$i,  // Id detalle  1
			'SERVICIO', // Tipo ítem  2 
			$det->tipAfeIGV, //tipo afectación al IGV	 3
			$det->codUnidadMedida, // Unidad medida 4
			0, //Código interno 5 ***** CONSULTAR JDM
			0, //Código producto SUNAT 6 ***** Consultar con Ing. anterior 85121600,85121800, 85121900
			$descripcion, // Descripción 7  **** Consultar si queda con CDATA o se le quita
			$det->ctdUnidadItem, // Cantidad 8
			$det->mtoValorUnitario, //Valor unitario 9
			0, //Descuento 10
			0, //Porcentaje descuento 0-1   11
			$cabecera->mtoOperGravadas, //Base imponible 12
			$cabecera->mtoIGV, //IGV  13
			'0.00', //ISC 14
			'0.00', //Porcentaje ISC  15
			0, // Otros cargos 16
			0, // Porcentaje otros cargos 17
			0, // Otros tributos 18
			0, // Porcentaje otros tributos  19
			$cabecera->mtoImpVenta, // Importe total 20
		];
		$detalle = implode("|", $arrayDetalle);

		$detalleFull .= $detalle."\r\n";

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


limpiarCarpeta();
leer_archivos(CONST_RUTA_JSON);

?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<a href="file:///F:\formatos\20494306043-03-B004-0000000014.pdf">Ver PDF Sunat</a>



</body>
</html>