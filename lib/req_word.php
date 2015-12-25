<?php
ini_set('xdebug.var_display_max_depth', 10);
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
		
		//Carica Paragrafi Testo
		$listp = form_load_text($doc_type);
		
		//Carica Form  (TODO: versionamento dei documenti) {? salva form in db e riutilizza quello}
		$form = form_load($doc_type,$listp);
		
		//Carica sezioni
		$field = form_sect($form);
		
		//Debug
		//var_dump($field);exit;
		//var_dump($form);exit;
		//echo str_replace("<par>","<br>",$listp);
		$dbg_choice = false;
		
		//Elabora
		foreach ($form as $var) {
			//var_dump($var);
			$var_id = $var['id'];
			//echo "\n<br>".$var_id." - >".$var['tipo']."<";
			
			//Sostituisci tutte le variabili
			if($var['tipo']=="var"){
				//Trova Valore
				$var_value = strtolower(get_data_val($data,$var));
				//Sostituisci
				$pattern = "/[\[](".$var_id.")[\]]/i";
				$listp = preg_replace($pattern, $var_value, $listp);
				
			}
			
			
			if($var['tipo']=="se"){
				//err   [\[](?:se )([!])?(?:nome)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*)*(?:\[\/se])
				//v.2	[\[](?:se )([!])?(?:nome)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[\/se])
				//v.3	[\[](?:se )([!])?(?:nome)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[altrimenti\](.*?))?(?:\[\/se])
				//v.3 w.c		[\[](?:se )([!])?(?:nome)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[altrimenti#nome\](.*?))?(?:\[\/se#nome])
				//		[se !nome|!nome2|io (sddaa)]a[altrimenti]b[/se#nome]
				//			group #1 =>	!				negazione su parola cercata
				//			group #2 =>	|!nome2|io		stringhe OR/AND
				//			group #3 =>	a		contenuto	se true
				//			group #4 =>	b		contenuto	se false	<= solo v.3
				
				//Trova Valore
				$var_value = get_data_val($data,$var);
				
				//echo "\n";
				//echo $var_id."\n";
				$pattern = "/[\[](?:se#)([!])?(?:".$var_id.")((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[altrimenti#".$var_id."\](.*?))?(?:\[\/se#".$var_id."])/i";
				//echo $pattern."\n"."\n";
				$listp = preg_replace_callback(	$pattern,
												function ($matches) use ($var_id,$var_value,$form,$data)  {
													global $dbg_choice;
													//echo "\n<br>Trovato\n (val:".$var_value.")";
													//var_dump($matches)."\n\n";
													//0 -> contenuto completo (da sostituire)
													//1 -> eventuale negazione
													//2 -> altre condizioni
													//3 -> contenuto del se				(valore da retituire se true true)
													//4 -> contenuto dell'altrimenti	(valore da retituire se true falso)
													
													//normalizza
													$matches[2] = strtolower($matches[2]);
													
													//Imposta valore falso - se trovi una condizione vera cambialo
													$out_value_false = count($matches)>3 ? @$matches[4] : "";
													$out_value_true = $matches[3];
													$out_bool = false;
													
													//Verifica condizioni
													// Analisi della prima condizione
														// vedi negazione eventuale!
														if($matches[1]=="!"){
															if(!$var_value) $out_bool = true;
														} else {
															if($var_value) $out_bool = true;
														}
													if($dbg_choice) echo "se (cond: $var_id val: ".$var_value.")($out_bool)"; 
													
													// Analisi condizioni aggiuntive
													if($matches[2]!=""){
														// Parsa eventuali altre condizioni
														$pattern = '/([|&])/';
														$parts = preg_split( $pattern, $matches[2], -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
														for ($i=0, $n=count($parts)-1; $i<$n; $i+=2) {
															$cond[] = $parts[$i].$parts[$i+1];
														}
														//Per ogni condizione
														foreach($cond as $c){
															$c_op = $c{0};
																//negazione
																$c_neg = false;
																$c_id = "";
																if($c{1}=="!") {
																	//neg
																	$c_neg = true;
																	$c_id = substr($c,2);
																} else {
																	//normal
																	$c_neg = false;
																	$c_id = substr($c,1);
																}
															//Valore condizione
															$c_val = get_data_val($data,get_var($form,$c_id));
															if($dbg_choice) echo "$c_op(cond:".$c_id." neg: ".$c_neg." val: ".$c_val.")"; 
															//Valuta?
															$out_bool = compare($out_bool, $c_op, $c_neg, $c_val, 1);
															
														}
														//print_r($condizioni);exit;
													}
													
													if($dbg_choice) if($out_bool) echo "res:true".$out_value_true; else echo "res:false".$out_value_false;
													if($dbg_choice) echo "\n<br>";
													if($out_bool) return $out_value_true; else return $out_value_false;
												},
												$listp );
				//echo $text;
				//exit;
			}
			
			
			if($var['tipo']=="sed"){
				//	v.1	  	[\[](?:se garante )([!])?(\w+)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[altrimenti\](.*?))?(?:\[\/se])	
				//	v.2	    [\[](?:se cliente_tipologia )([!])?(\w+)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)((?:\[altrimenti (?:\w+)\](?:.*?))*)(?:\[\/se])
				//	v.3	    [\[](?:se cliente_tipologia )([!])?(\w+)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)((?:\[altrimenti(?: \w+)?\](?:.*?))*)(?:\[\/se])
				//	v.3	w.c	[\[](?:se cliente_tipologia )([!])?(\w+)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)((?:\[altrimenti(?: \w+)?\](?:.*?))*)(?:\[\/se#cliente_tipologia])
				//		[se garante !a|b|c(asd)]abc[altrimenti d]d[/se]
				//			group #1 =>	!				negazione su parola cercata
				//			group #2 =>	a			condizione di questo blocco
				//			group #3 =>	|b|!c		stringhe condizioni aggiuntive OR/AND (|,&)
				//			group #4 =>	abc									contenuto	se true
				//			group #5 =>	[altrimenti d]d[altrimenti e]e		contenuto	se false
				//$pattern = "/[\[](?:se ".$var_id." )([!])?(\w+)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(\[altrimenti (?:\w+)\](?:.*))*(?:\[\/se])/";
				$pattern = "/[\[](?:se#".$var_id." )([!])?(\w+)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)((?:\[altrimenti#".$var_id."(?: \w+)?\](?:.*?))*)(?:\[\/se#".$var_id."])/i";
				
				//Trova Valore
				$var_value = get_data_val($data,$var);
				
				
				$listp = preg_replace_callback(	$pattern,
												function ($matches) use ($var_id,$var_value,$form,$data)  {
													global $dbg_choice;
													
													//var_dump($matches)."\n\n";
													//0 -> contenuto completo (da sostituire)
													//1 -> eventuale negazione
													//2 -> valore blocco attuale
													//3 -> altre condizioni
													//4 -> contenuto del se				(valore da retituire se true true)
													//5 -> contenuto dell'altrimenti	(valore da retituire se true falso)
													
													//normalizza
													$matches[2] = strtolower($matches[2]);
													
													//Imposta valore falso - se trovi una condizione vera cambialo
													$out_value_false = "";
													$out_value_true = $matches[4];
													$out_bool = false;
													
													//Verifica condizioni
													// Analisi della prima condizione
														// vedi negazione eventuale!
														if($matches[1]=="!"){
															if(!($var_value==$matches[2])) $out_bool = true;
														} else {
															if($var_value==$matches[2]) $out_bool = true;
														}
													if($dbg_choice) echo "sed (cond: $var_id block:$matches[2] val:$var_value neg:$matches[1])($out_bool)"; 
													// Analisi della condizioni aggiuntive
													if($matches[3]!=""){
														// Parsa eventuali altre condizioni
														$pattern = '/([|&])/';
														$parts = preg_split( $pattern, $matches[3], -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
														for ($i=0, $n=count($parts)-1; $i<$n; $i+=2) {
															$cond[] = $parts[$i].$parts[$i+1];
														}
														//Per ogni condizione
														foreach($cond as $c){
															$c_op = $c{0};
																//negazione
																$c_neg = false;
																$c_id = "";
																if($c{1}=="!") {
																	//neg
																	$c_neg = true;
																	$c_id = substr($c,2);
																} else {
																	//normal
																	$c_neg = false;
																	$c_id = substr($c,1);
																}
															if($dbg_choice) echo "$c_op(cond:".$c_id." neg: ".$c_neg." data_val: ".$var_value.") "; 
															//Valuta?
															$out_bool = compare($out_bool, $c_op, $c_neg, $c_id, $var_value);
														}
													}
													// Analisi del falso (solo se falso)
													// imposta valore da ritornare come falso
													// group #5 =>	[altrimenti d]d[altrimenti e]e
													if(!$out_bool){
														if(@$matches[5]!=""){
															$pattern = '/\[altrimenti(?: )?(\w+)?\]([^\[]*)/i';
															preg_match_all($pattern,$matches[5],$cond_false, PREG_SET_ORDER);
															//Parsa condizioni falso
															//var_dump($cond_false);//exit;
															foreach($cond_false as $cond){
																$c_id = strtolower($cond[1]);
																if($dbg_choice) echo "altrimenti se (cond:".$c_id." data_val: ".$var_value.") "; 
																if($c_id==""){
																	$out_value_false = $cond[2];
																	break;
																} else if($var_value==$c_id) {
																	$out_value_false = $cond[2];
																	break;
																}
															}
															
														}
													}
													
													//echo "Ritorna ".$out_value."\n<br>";
													if($dbg_choice) if($out_bool) echo "res:true"; else echo "res:false";
													if($dbg_choice) echo "\n<br>";
													if($out_bool) return $out_value_true; else return $out_value_false;
												},
												$listp );
												
												//echo str_replace("<par>","<br>",$listp);
												//exit;
			}
			
			
		}
		
		
		
		} else {
			//id non trovato
			die();
		}
			
		//echo "---";
		echo str_replace("<par>","<br>",$listp);
	
	
	
	
	
    break;
}

function get_data_val($data,$var){
	//echo $var;
	foreach ($data as $v) {
		if(@$v->name){
			if($v->name==$var['id']){
				return $v->value;
			}
		}
	}
	if($var['tipo']=="var"){
		return "............";
	}else{
		return false;
	}
}

function get_var($form,$id){
	foreach ($form as $var) {
		if($var['id']==$id)
			return $var;
	}
}

function compare($in_bool, $operator, $neg, $expr, $value)
{
   switch(strtolower($operator)) {
      case '|':
		if($neg)
			return $in_bool || !($expr == $value);
		else
			return $in_bool || ($expr == $value);
      case '&':
		if($neg)
			return $in_bool && !($expr == $value);
		else
			return $in_bool && ($expr == $value);
      default:
         throw new Exception("Invalid operator '$operator'");
   }
}  
?>