<?php

require dirname(__FILE__) . "/bdSqlite.php";
/**
*Leer archivos desde ruta especifica
*
**/
function leer_archivos($carpeta){
	echo '<h2>Generando archivos (.txt)</h2>';
    if(is_dir($carpeta)){
        if($dir = opendir($carpeta)){
			$i=0;
            while(($archivo = readdir($dir)) !== false){
                if($archivo != '.' && $archivo != '..' && $archivo != '.htaccess'){
					
                    $info = new SplFileInfo($archivo);					
					if($info->getExtension() === 'json'){
						$i++;
						
						$titulo_arr = explode("-", $archivo);	
						$titulo_arr = str_replace(".json","",$titulo_arr[3]);
						

						/*Consulta si ya existe */
						$existe = existe_registro($titulo_arr);
						
						//if(!$existe){
							$lastId = inserta_registro($titulo_arr);
							$lastId = str_pad($lastId, 10, "0", STR_PAD_LEFT);
						
							echo $i.'.- Procesando archivo '.$archivo.'<br>';
							generar_txt($carpeta.'/'.$archivo,$archivo,$lastId);
						//}
						
						
					}
                }
            }
            closedir($dir);
        }
    }
}

function formatea_fecha($originalDate){
	//Original viene en formato yyyy-MM-dd
	//Nuevo sale en formato dmY
	$newDate = date("dmY", strtotime($originalDate));
	
	return $newDate;
}

function genera_archivo($titulo,$cabecera,$detalle,$cliente,$descuentoGlobal){
	$nombre_archivo = $titulo.".txt"; 

    if(!file_exists($nombre_archivo))
    {
		$FILE_YEAR = date("Y");
        $FILE_MONTH = (int)date("m");
		$FILE_DAY = date("d");
		
		 
		//$URL_FULL = CONST_RUTA_TXT.'/'.$FILE_YEAR.'/'.$FILE_MONTH .'/'.$FILE_DAY;
		$URL_FULL = CONST_RUTA_TXT;
		echo $URL_FULL ;
		
		mkdir($URL_FULL,0777, true);
		if($archivo = fopen($URL_FULL.'/'.$nombre_archivo, "a"))
		{
			if(fwrite($archivo,$cabecera."\r\n".$detalle.$cliente.$descuentoGlobal))
			{
				echo 'Archivo creado '.$nombre_archivo.'<br>';
			}else{
				echo "Ha habido un problema al crear el archivo ".$titulo.'<br>';
			}
 
        fclose($archivo);
		}
    }
 
}

function limpiarCarpeta(){
	$files = glob(CONST_RUTA_TXT.'/*.txt'); //obtenemos todos los nombres de los ficheros
	foreach($files as $file){
		if(is_file($file))
		unlink($file); //elimino el fichero
	}
}

function obtenerDiasXRUC($ruc){
	
	switch($ruc) {
    case SEGURO_MAPFRE_CIA:
        $dias = SEGURO_MAPFRE_DIAS;
		break;
    case SEGURO_MAPFRE_EPS:
        $dias = SEGURO_MAPFRE_EPS_DIAS;
		break;
	case SEGURO_PACIFICO:
        $dias = SEGURO_PACIFICO_DIAS;
		break;
	case SEGURO_PACIFICO_EPS:
        $dias = SEGURO_PACIFICO_EPS_DIAS;
		break;
	case SEGURO_RIMAC:
        $dias = SEGURO_RIMAC_DIAS;
		break;
	case SEGURO_RIMAC_EPS:
        $dias = SEGURO_RIMAC_EPS_DIAS;
		break;
	case SEGURO_SANITAS:
        $dias = SEGURO_SANITAS_DIAS;
		break;
	case SEGURO_FEBAN:
        $dias = SEGURO_FEBAN_DIAS;
		break;
	case SEGURO_LAPOSITIVA:
        $dias = SEGURO_LAPOSITIVA_DIAS;
		break;
	case SEGURO_LAPOSITIVA_EPS:
        $dias = SEGURO_LAPOSITIVA_EPS_DIAS;
		break;
	case SEGURO_CHUBB:
        $dias = SEGURO_CHUBB_DIAS;
		break;
	case SEGURO_FOPASEF:
        $dias = SEGURO_FOPASEF_DIAS;
		break;
	default:
		$dias = 0;
		break;
	}

	return $dias;
}

?>
