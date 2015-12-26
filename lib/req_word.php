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

	//carica interprete word 
	require_once("lib_word.php");
	

//FILTRO
	$action = isset($_GET['action']) ? mysqli_real_escape_string($dbhandle,$_GET['action']) : null;
		if(is_null($action)) die();
	$id = isset($_GET['id']) ? mysqli_real_escape_string($dbhandle,$_GET['id']) : null;
		$id = ($id != '') ? $id : null;
	$debug = isset($_GET['debug']) ? mysqli_real_escape_string($dbhandle,$_GET['debug']) : null;
		$debug = ($debug == '1') ? (bool)$debug : null;

//CHECK PARAMETRI
	if(is_null($id)){
		die();
	}

//ACTION
switch ($action) {
    case "gen":

		//GET DATA
		$sql = "SELECT * FROM `wform`.`form` WHERE id_form = '".$id."' ";
		$stmt = mysqli_query( $dbhandle, $sql);
		if ( !$stmt ){
			 echo "#Error in statement execution.\n";
			 die( print_r( mysqli_error($dbhandle), true));
		}
		$count = 0;
		if( $row = mysqli_fetch_array( $stmt, MYSQLI_ASSOC)) {
				
			//decode
			$data = json_decode($row['data']);
			
			//prendi parm
			//var_dump($data);
			//var_dump($data[0]->id);
			//var_dump($data[0]->mod);
			
			$doc_id = $data[0]->id;
			$doc_type = $data[0]->mod;
			
			//Carica Paragrafi Testo da Template e Form
			$p = form_load_text($doc_type);
			$form = form_load($doc_type,$p);
			//(TODO: versionamento dei documenti) {? salva form in db e riutilizza quello}
			
			//Generate Word
			text_compose($form,$p,$data);
			
			//Send Output
			if($debug){
				echo preg_replace('/(?:<(par(?:[^>]*))>)/', '<br>', $p);
			} else {
				//Send Word
				text_wordize($form,$p,$doc_id);
			}		
		
		} else {
			//id non trovato
			die();
		}
	
	
    break;
}

?>