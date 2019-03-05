<?php

error_reporting(E_ERROR | E_PARSE);
include dirname(__FILE__) . "/constants.php";
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

//Usage:
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
$urlFile = 'F:/formatos/20494306043-03-B002-87925.json';
function remove_utf8_bom($text)
{
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

function stripUtf8BomFromFile($filename) {
    $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
    $file = @fopen($filename, "r");
    $hasBOM = fread($file, 3) === $bom;
    fclose($file);
 
    if ($hasBOM) {
        $contents = file_get_contents($filename);
        file_put_contents($filename, substr($contents, 3));
    }
 
    return $hasBOM;
}

stripUtf8BomFromFile($urlFile);

$myFile = file_get_contents($urlFile);


$textoLimpio = prepareCharset($myFile);
echo $textoLimpio;
var_dump($myDataArr['cabecera']);
 


$json = json_decode( $myFile, true, 9 );
	$json_errors = array(
		JSON_ERROR_NONE => 1,
		JSON_ERROR_DEPTH => -1,
		JSON_ERROR_CTRL_CHAR => -1,
		JSON_ERROR_SYNTAX =>999,
	);
	
	
	echo $json_errors[json_last_error()];
		
		
		
?>