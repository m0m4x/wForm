<?php

function form_load_text($doc_type){
	
	$archiveFile = dirname(__FILE__)."/../doc/".$doc_type.".docx";
	
	//check file existence
	if(!file_exists($archiveFile)) die($archiveFile);
	
	//XML Load
	$xml = new DOMDocument();

		//word .docx
		$zip = new ZipArchive;
		if (true === $zip->open($archiveFile)) {
			if (($index = $zip->locateName("word/document.xml")) !== false) {
				$data = $zip->getFromIndex($index);
				$zip->close();
				// Load XML from a string
				// Skip errors and warnings
				$xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
				
				// Return data without XML formatting tags
				//echo strip_tags($xml->saveXML());
				
				// Return completed xml document
				//$xml->preserveWhiteSpace = true;
				//$xml->formatOutput = true;
				//$xml_string = $xml->saveXML();
				//echo $xml_string;
			} else {
				$zip->close();
				die("error reading docx...");
			}
		}

	//XML Parsing
	$xml_xpath = new DomXPath($xml);
	$pNodes = $xml_xpath->query("/w:document/w:body/w:p");

	$p = "";
	foreach ($pNodes as $par) {
		$p .= $par->nodeValue."<par>";
	}
	
	return $p;
	
}


function form_load($doc_type){
	
	$archiveFile = dirname(__FILE__)."/../doc/".$doc_type.".docx";
	
	//check file existence
	if(!file_exists($archiveFile)) die($archiveFile);
	
	//XML Load
	$xml = new DOMDocument();

		//word .docx
		$zip = new ZipArchive;
		if (true === $zip->open($archiveFile)) {
			if (($index = $zip->locateName("word/document.xml")) !== false) {
				$data = $zip->getFromIndex($index);
				$zip->close();
				// Load XML from a string
				// Skip errors and warnings
				$xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
				
				// Return data without XML formatting tags
				//echo strip_tags($xml->saveXML());
				
				// Return completed xml document
				//$xml->preserveWhiteSpace = true;
				//$xml->formatOutput = true;
				//$xml_string = $xml->saveXML();
				//echo $xml_string;
			} else {
				$zip->close();
				die("error reading docx...");
			}
		}

	//XML Parsing
	$xml_xpath = new DomXPath($xml);
	$pNodes = $xml_xpath->query("/w:document/w:body/w:p");

	$listp = Array();
	foreach ($pNodes as $p) {
		$listp[] .= $p->nodeValue;
		
		//TODO
		//non prendere il testo come nodevalue, ma parsa i singoli nodi per riportare la formattazione come tag <>
		//in particolare:
		//		- bold: attributo del singolo nodo w:t
		
		//List Attrib
		/*
		foreach ($entry->attributes as $attr) {
			$name = $attr->nodeName;
			$value = $attr->nodeValue;
			echo "Attribute '$name' :: '$value'<br />";
		}
		*/
		
		/*
		//Only txt in par.
		//w:rsidRPr
		if ($entry->hasAttribute("w:rsidRPr"))
		{
			$par = $entry->getAttribute("w:rsidRPr");
			$listp[$par] .= $entry->nodeValue;
			
			
			//chiudi elemento
			//echo 	"node: {$lastele->nodeName}," .
			//		"value: >{$entry->nodeValue}<\n<br>";
			
			
			
			//if($lastp == $par){
			//	//aggiungi a lastele
			//	$par[]
			//} else {
			//
			//	//nuovo elemento
			//	$lastele = $entry;
			//	$lastp = $par;
			//}
			

		}
		*/

	}
	// Default (far comparire come variabili)
	/*if (file_exists('variabili.xml')) {
		$var = simplexml_load_file('variabili.xml');
	} else {
		exit('Failed to open variabili.xml.');
	}*/

	//Create Form
	$form=Array();
	$stack_se=Array();

	// Parsing codes
	$cur_p = 0;
	foreach ($listp as $p) {
		$pattern = "/[\[][^\]]*[\]]/";	// [*]
		preg_match_all($pattern, $p, $matches, PREG_OFFSET_CAPTURE );
		// Orders results so that $matches[0] is an array of full pattern matches, $matches[1]
		//print_r($matches);echo "<br><br>\n\n";	
		
		//per ogni string all'interno di match[0]
		foreach($matches[0] as $op){
			//print_r($op);
			//echo $cur_p .">".$p."<\n\n\n"; 
			
			$opcode=$op[0];
			$oppos=$op[1];
			
			//Info aggiuntive - prendi
			$label = ""; $info = "";
			$pattern = "/[\(][^\)]*[\)]/";	// (*)
			$infs = array();
			preg_match_all($pattern, $opcode, $infs);
			foreach($infs[0] as $inf){
				$txt = str_replace(array("(", ")"), "", $inf);
				$t = explode(":",$txt);
				if(array_key_exists(0,$t)) $label = $t[0];
				if(array_key_exists(1,$t)) $info = $t[1];
			}
			$opcode = preg_replace($pattern, "", $opcode);
			
			//Generate field $f in $form
			// (id) 
			// (tipo) 
			// (label)
			// (info)
			// (pos) 	da 	=> (p -> par, c -> car)
			//			a 	=> (p -> par, c -> car)
			//
			// (condizioni)	 (condizione1 -> testo1)
			
			//1 - rimuovi caratteri '][' e split contenuto per spazio
			$opcodes = explode( " ", str_replace( array(']', '[') , "", $opcode) );
			//print_r($opcodes);exit;
			
			//2 - parse opcodes
			//$opcodes[0] identifica la tipologia di variabile
			//fai qualcosa se è uguale ad uno dei seguenti:
			//		se				=> genera checkbox/dropdown
			//		altrimenti		=> genera checkbox/dropdown
			//		/se				=> genera checkbox/dropdown
			//
			//			Per creare il se si utilizza come variabile temporanea uno stack di 'se' ($s)
			//				stack_se
			//					(id)	
			//					(pos) 	da 	=> (p -> par, c -> car)		//posizione globale del 'se' aperto con opcode 'se' e chiuso con '/se'
			//							a 	=> (p -> par, c -> car)
			//					(sect) array( (val, da, a, txt) )		//array di sezioni del 'se' 
			//															//ogni sezione è un area del se -> di fatto una condizione
			//											if (
			//															=> sezione 1
			//											) else (
			//															=> sezione 2
			//											)
			//
			//			Per ogni opcode devo fare qualcsa:
			//				se				crea stack_se ($s) e apre una sezione
			//				altrimenti: 	chiude sezione e apre nuova sezione
			//				/se:			chiude stack e salva in $form
			//								
			//
			//
			//
			//in alternativa è un campo variabile 	=> genera checkbox/dropdown
			
			//debug
			//echo "=>".$opcode."   par:".$cur_p." pos:".$oppos." label:".$label." info:".$info."<br>\n";
			
			//Filter opcodes (empty line and strtolower)
			$opcodes = array_filter($opcodes);
			$opcodes = array_map('strtolower', $opcodes);

			//Parse single opcode
			$f = "";
			$s = "";
			$id = "";
			switch ($opcodes[0]) {
				
				//se
				case "se":
					//crea stack se
					$id = $opcodes[1];
					
					//tipo se
					$tipo="";
					if(sizeof($opcodes)>2)
						//se solo 1 parola se => checkbox
						$tipo = "sed";
					else
						//se piu parole => dropdown
						$tipo = "se";
								
					//valore della condizione
					//negazione -> inverti val
						$neg = check_val_neg($id);
						if($neg) $id = str_replace("!","",$id);
					$val="";
					if($tipo == "se"){
						if($neg) $val = "0"; else $val = "1";
					} else {
						if($neg) $val = "!".$opcodes[2]; else $val = $opcodes[2];
					}
					
					//crea stack SE
					$s = array(	"id" 	=> $id,
								"tipo"	=> $tipo,
								"label"	=> $label, "info"	=> $info,
								"pos" 	=> array( 	"da" => array($cur_p, $oppos), 
													"a" => array() 
												),
								"sect" 	=> array( array( 	"val" => $val , 
															"da" => array($cur_p, $oppos + strlen($opcode)) ,
															"a" => array() , 
															"txt" => "" ) 
												) 
								);
								
					//push to stack
					array_push($stack_se, $s);
					break;
				case "altrimenti":
					$s = array_pop($stack_se);						
					$id = $s["id"];
					//completa last sector
						$lastsect = array_pop($s["sect"]); 
						$lastsect["a"] = array($cur_p, $oppos);
						//testo della sezione
						$lastsect["txt"] = get_text($lastsect["da"],$lastsect["a"]);
						array_push($s["sect"], $lastsect);
					//inizializza settore
						//val
						//print_r($opcodes);exit;
						if($s["tipo"]=="sed"){
							//è dropdown
							if(count($opcodes)>1){
								//esiste parola
								$neg = check_val_neg($opcodes[1]);
								if($neg) $opcodes[1] = str_replace("!","",$opcodes[1]);
								if($neg) $val = "!".$opcodes[1]; else $val = $opcodes[1];
							} else {
								//non c'è parola 
								//nega il precedente
								$val = "!".$lastsect["val"];
							}
						} else {
							//è checkbox
							if($lastsect["val"]=="0"){
								$val="1";
							} else {
								$val="0";
							}
						}
						//push
						$newsect = array( "val" => $val , "da" => array($cur_p, $oppos + strlen($opcode)) , "a" => array() , "txt" => "" ) ;
						//print_r($newsect);exit;
						array_push($s["sect"], $newsect); 
						
					//Info aggiuntive - aggiorna se immesse nell'altrimenti
					if($label!="") { $s["label"] = $label; }
					if($info!="") { $s["info"] = $info; }
					
					//push to stack_se
					array_push($stack_se, $s);
					
					//se solo parola 'altrimenti' -> add condizione 0 => "testo"
					//se piu parole *** NON POSSIBILE ?
					break;
				case "/se":
					//chiudi settore
					$s = array_pop($stack_se);
					$id = $s["id"];
						//completa last sector
						$lastsect = array_pop($s["sect"]);
						$lastsect["a"] = array($cur_p, $oppos);
						//testo della sezione
						$lastsect["txt"] = get_text($lastsect["da"],$lastsect["a"]);
						array_push($s["sect"], $lastsect);
						//completa pos-a
						//$lastpos = array_pop($s["pos"]); 
						//$lastpos["a"] = array( $cur_p, $oppos + strlen($opcode));
						//array_push($s["pos"], $lastpos); print_r($s["pos"]);exit;
						$s["pos"]["a"] = array( $cur_p, $oppos + strlen($opcode));

					//push stack to form
					// necessario controllare se già esistente : nel caso aggiungere pos e sect
					if (!array_key_exists($id,$form) ) {
						$f = array(	"id" => $s["id"],
											"tipo" => $s["tipo"],
											"label" => $s["label"], "info" => $s["info"],
											"pos" => array( $s["pos"]
															), //array di array per posizioni multiple
											"sect" => array( $s["sect"]
															)
											);
						if($label!="") { $form[$id]["label"] = $label; }
						$form[$id] = $f;
					} else {
						$f = search_form($id, $form);
						//TODO CHECK - Se tipologia diversa errore!
						$f["pos"][] = $s["pos"];
						$f["sect"][] = $s["sect"];
						
						//Info aggiuntive - aggiorna
						if($label != "") { $s["label"] = $label; }
						if($info!="") { $s["info"] = $info; }
						if($s["label"] != "") { $f["label"] = $s["label"]; }
						if($s["info"] != "") { $f["info"] = $s["info"]; }
						
						$form[$id] = $f; //Replace not add
					}
					break;
				
				//variabile
				default:
					$id = $opcodes[0];
					//check if "id" exist
					if (!array_key_exists($id,$form) ) {	//is_null(search_form($opcodes[0], $form))
						//not exist: create
						$f = array(	"id" => $opcodes[0],
									"tipo" => "var",
									"label" => $label, "info" => $info,
									"pos" => array( array(	"da" => array( $cur_p, $oppos), 
															"a" => array( $cur_p, $oppos + strlen($opcode))	
															)
													) //array di array per posizioni multiple
									);
						if($label!="") $f["label"] = $label;
						//Add to form
						$form[$id] = $f;
					} else {
						//exist: add position
						$f = search_form($opcodes[0], $form);
						$f["pos"][] = array(	"da" => array( $cur_p, $oppos), 
												"a" => array( $cur_p, $oppos + strlen($opcode))	
											);
						if($label!="") $f["label"] = $label;
						$form[$id] = $f; //Replace not add
					}
					//echo substr($p, $oppos, strlen($opcode))."\n\n\n"; 
			}
				
		}
		
		//next par
		$cur_p++;
	}

	return $form;
}


function form_sort($a, $b)
{
	return strcasecmp($a['id'],$b['id']);
}

function check_val_neg($val){
	$neg = false;
	if(substr($val,0,1)=="!"){
		$neg = true;
	}
	return $neg;
}


//Functions 4 parsing
function search_form($id,$form){
	foreach($form as $f){
		if($f["id"]==$id){
			return $f;
		}
	}
	return null;
}
function search_form_id($id,$form){
	$i=0;
	foreach($form as $f){
		if($f["id"]==$id){
			return $i;
		}
		$i++;
	}
	return null;
}


function get_text($from,$to){
	global $listp;
	$f_p = $from[0];
	$f_c = $from[1];
	$t_p = $to[0];
	$t_c = $to[1];
	$text = "";
	for($i=$f_p;$i<=$t_p;$i++){
		//foreach par
		$start=0;
		$stop=strlen($listp[$i]);
		if($i==$f_p){
			$start = $f_c;
		}
		if($i==$t_p){
			$stop = $t_c;
		}
		$text .= substr($listp[$i], $start, $stop-$start );
	}
	return $text;
}

//CREATE Form

function form_se($form){
	
	usort($form, "form_sort");
	
	//Only 'se'
	foreach ($form as $field) {
		//print_r($field);
		$id = $field['id'];
		$label = ($field['label']!="" ? $field['label'] : $field['id']); 
		$label = str_replace("_", " ", $label);
		
		switch ($field["tipo"]) {
				case "se":	//se checkbox
					
					?>
						<!-- se checkbox -->
						<div class="form-group">
						<label for="<?php echo $id; ?>" class="control-label col-md-4"><?php echo $label; ?></label>
							<div class="col-md-8">
								<div class="checkbox">
									<label><input type="checkbox" name="<?php echo $id; ?>" id="<?php echo $id; ?>" value="si"></label>
									<?php if($field['info']!=""){ ?><span class="help-block"> <?php echo $field['info']; ?> </span><?php } ?>
								</div>
							</div>
						</div>
					<?php
					
					break;
				case "sed":	//se dropdown
				
					//Get values
					// per ogni posizione del se $field[pos] > tanti possibili valori 
					$values = array();
					//print_r($field['pos']);print_r($field['sect']);exit;
					for($i=0;$i<count($field['pos']);$i++){
						foreach($field['sect'][$i] as $cond){
							$values[$cond["val"]] = $cond;
						}
					}
					
					//pulisci valori da OR AND e NEGAZIONI
					//print_r($values);exit;
					$values_list = array();
					foreach($values as $val => $value){
						//split &,|
						$v_split = preg_split("/[&|]/",$val);
						//print_r($v_split);exit;
						foreach($v_split as $v){
							$values_list[] = str_replace("!","",$v);;
						}
					}
					$values_list = array_unique($values_list);
							
					?>
						<!-- se dropdown -->
						<div class="form-group radio_buttons optional user_horizontal_sex">
							<label class="radio_buttons control-label col-md-4"><?php echo $label; ?></label>
							<div class="col-md-8">
							<?php
							
								foreach($values_list as $value){
									$val_id = $id."_".$value;
									//$val_name = $id."[".$value."]";
									$val_name= $id;
									?>
										<span class="radio">
											<label for="<?php echo $val_id; ?>">
											<input class="radio_buttons optional" id="<?php echo $val_id; ?>" name="<?php echo $val_name; ?>" type="radio" value="<?php echo $value; ?>"><?php echo str_replace("_"," ", $value); ?></label>
											
										</span>
									<?php
								}
							
							?>
							<?php if($field['info']!=""){ ?><p class="help-block"> <?php echo $field['info']; ?> </p><?php } ?>
							</div>
						</div>
					<?php
				
					break;	
		}
	}
	
}
	
function form_var($form){
	
	//Only 'var'
	foreach ($form as $field) {
		//print_r($field);
		$id = $field['id'];
		$label = ($field['label']!="" ? $field['label'] : $field['id']); 
		$label = str_replace("_", " ", $label);
		
		switch ($field["tipo"]) {
				case "var":	//variable
				
					?>
						<!-- variable -->
						<div class="form-group">
							<label for="<?php echo $id; ?>" class="control-label col-md-4"><?php echo $label; ?></label>
							<div class="col-md-8">
								<input type="text" class="form-control" name="<?php echo $id; ?>" id="<?php echo $id; ?>" placeholder="<?php echo $id; ?>">
								<?php if($field['info']!=""){ ?><span class="help-block"> <?php echo $field['info']; ?> </span><?php } ?>
							</div>
						</div>
					<?php
					break;
		}
		
		
	}
	
}




//DEBUG

//Paragrafi
//print_r($listp);

//Form
//print_r($form);



?>