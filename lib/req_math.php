<?php
//DEBUG
	ini_set('xdebug.var_display_max_depth', 10);
	ini_set('xdebug.var_display_max_children', 256);
	ini_set('xdebug.var_display_max_data', 1024);


//HEADER - NO CACHE
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	header('Content-Type: text/html; charset=UTF-8'); 


//LIBRERIE
	//carica db
	require_once("lib_db.php");
	
	//carica libreria matematica
	require_once("lib_math.php");

	
//FILTRO
	$action = isset($_GET['action']) ? mysqli_real_escape_string($dbhandle,$_GET['action']) : null;
		if(is_null($action)) die();
	$str = isset($_GET['str']) ? mysqli_real_escape_string($dbhandle,$_GET['str']) : null;
		$str = ($str != '') ? $str : null;
	$debug = isset($_GET['debug']) ? mysqli_real_escape_string($dbhandle,$_GET['debug']) : null;
		$debug = ($debug == '1') ? (bool)$debug : null;

//CHECK PARAMETRI
	if(is_null($str)){
		die();
	}

//MIGLIORA ERROR HANDLER per includere Notice
set_error_handler('exceptions_error_handler');
function exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}
	
	
//ACTION
switch ($action) {
    case "numtoword":
		
		try {
			$str = numbertoWords($str);
			echo $str;
		} catch (Exception $e) {
			//echo $e->getMessage();
			return false;
		}		
		
    break;
}
	
	
?>