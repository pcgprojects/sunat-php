<?php
	include dirname(__FILE__) . "/constants.php";
	
	if(isset($_GET["pdf"])){
	
	
	$titulo_pdf = explode("-", $_GET["pdf"]);	
	
	$ruc = $titulo_pdf[0];
	$tipoBoleta = '03';
	$tipoFactura = '01';
	$tipoCode = $titulo_pdf[1];
	$codigo = (int) $titulo_pdf[2];
	
	$urlPDFBoleta = $ruc.'-'.$tipoBoleta.'-'.$tipoCode.'-'.$codigo.'.pdf';
	
	$pathPDF = CONST_RUTA_PDF.'/'.$urlPDFBoleta;
	$url_file = $pathPDF;        
    $data_file = file_exists($pathPDF);

    if ($data_file === false) {
		
		
		$urlPDFBoleta = $ruc.'-'.$tipoFactura.'-'.$tipoCode.'-'.$codigo.'.pdf';
		$pathPDF = CONST_RUTA_PDF.'/'.$urlPDFBoleta;
		$url_file = $pathPDF;        
		$data_file = file_exists($pathPDF);
		if ($data_file === false) {
			echo 'No encuentro archivo.';
		}else{
			$data_pdf = file_get_contents($pathPDF);
		}
    } else {
		$data_pdf = file_get_contents($pathPDF);
    }
				
	header("Content-Type: application/pdf");
	echo $data_pdf;
	}
	
    exit();
	
	
?>
