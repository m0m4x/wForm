<?php
ini_set('xdebug.var_display_max_depth', 5);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);


//HEADER - NO CACHE
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-Type: text/html; charset=UTF-8'); 

	//carica interprete word 
	require_once("lib_word.php");


//APRI DB
$dbhandle = new mysqli("127.0.0.1", "php", "php", "wform");
if ($dbhandle->connect_errno)
	die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
if($dbhandle === false)
	die("Servizio momentaneamente non disponibile! (missing db)"); 

//FILTRO
$action = isset($_GET['action']) ? mysqli_real_escape_string($dbhandle,$_GET['action']) : null;
	if(is_null($action)) die();

$id = isset($_GET['id']) ? mysqli_real_escape_string($dbhandle,$_GET['id']) : null;
$id = ($id != '') ? $id : null;

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
		
		//Carica Form  (TODO: versionamento dei documenti) {? salva form in db e riutilizza quello}
		$form = form_load($doc_type);

		//Carica Paragrafi Testo
		$listp = form_load_text($doc_type);
		
		//Debug
		//var_dump($form);
		//echo($listp)."\n\n\n\n";
		
		//Elabora
		foreach ($form as $var) {
			$var_id = $var['id'];
			//Sostituisci tutte le variabili
			if($var['tipo']=="var"){
				//Trova Valore
				$var_value = get_data_val($var['id'],$data);
				//Sostituisci
				$pattern = "/[\[](".$var_id.")[^\]]*[\]]/";
				$listp = preg_replace($pattern, $var_value, $listp);
			}
			if($var['id']=="garante_fidejussore"){
				//      [\[](?:se )([!])?(?:nome)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*)*(?:\[\/se])
				//v.2	[\[](?:se )([!])?(?:nome)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[\/se])
				//		[se !nome|!nome2|io (sddaa)]aa1asssasa[/se]
				//			group #1 =>	!				negazione su parola cercata
				//			group #2 =>	|!nome2|io		stringhe OR/AND
				//			group #3 =>	aa1asssasa		contenuto	
				//Trova Valore
				$var_value = get_data_val($var['id'],$data);
				
				echo "\n";
				echo $var_id."\n";
				$pattern = "/[\[](?:se )([!])?(?:".$var_id.")((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[\/se])/";
				echo $pattern."\n"."\n";
				$text = preg_replace_callback(	$pattern,
												function ($matches) {
													echo "Trovato\n";
													var_dump($matches)."\n\n";
													//0 -> contenuto completo (da sostituire)
													//1 -> eventuale negazione
													//2 -> contenuto del se
													
													// !!! Prima volta che ok ritorna
													
													// Analisi della prima condizione
														// vedi negazione eventuale!
													
													// Parsa eventuali altre condizioni
													
													// Ritorna valore da sostituire
														// group 2 se ok
														//vuoto se falso
													
												},
												$listp );
				echo $text;
				exit;
			}
			
			if($var['tipo']=="sed"){
				
			}
		}
		
		//echo($listp);
		
		//Attiva/Disattiva testi condizionati
		
		} else {
			//id non trovato
			die();
		}
			
	
	
	
	
	
	
	
    break;
}

function get_data_val($name,$data){
	//echo $name;
	foreach ($data as $var) {
		if(@$var->name){
			if($var->name==$name){
				return $var->value;
			}
		}
	}
	return "_______________";
}
?>