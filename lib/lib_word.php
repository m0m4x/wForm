<?php

//LIBRERIE
	//carica db
	require_once("lib_math.php");

ini_set('xdebug.var_display_max_depth', 10);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

//HEADER - NO CACHE
/*header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past*/
//header('Content-Type: text/html; charset=UTF-8'); 

function docx_load_info($doc_type){
	
	$archiveFile = dirname(__FILE__)."/../doc/".$doc_type.".docx";
	
	//check file existence
	if(!file_exists($archiveFile)) die($archiveFile);
	
	//XML Load
	$xml = new DOMDocument();
		$zip = new ZipArchive;
		if (true === $zip->open($archiveFile)) {
			if (($index = $zip->locateName("docProps/core.xml")) !== false) {
				$data = $zip->getFromIndex($index);
				$zip->close();
				
				// Load XML from a string
				// Skip errors and warnings
				$xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
				
				// Debug - Return data without XML formatting tags
				// echo strip_tags($xml->saveXML());
				
				// Debug - Return completed xml document
				// $xml->preserveWhiteSpace = true;
				// $xml->formatOutput = true;
				// $xml_string = $xml->saveXML();
				// echo $xml_string;
				
			} else {
				$zip->close();
				die("error reading docx - ". $archiveFile);
			}
		}

	//XML Traversing
	$infos = Array();
	$propNodes = $xml->getElementsByTagName('*');
	foreach ( $propNodes as $prop){
		$infos[$prop->nodeName] = $prop->nodeValue;
	}
		
	return $infos;
}

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
		
		$p_align = "";
		$p_temp = "";
		
		foreach($par->childNodes as $txt) { 
		
			// leggi allineamento paragrafo
			if($txt->tagName=="w:pPr"){
				foreach($txt->childNodes as $txt_pPr) {
					//echo "--".$txt_pPr->getAttribute("w:val");
					if($txt_pPr->tagName=="w:jc") $p_align = $txt_pPr->getAttribute("w:val");
				}
			}

			//Nodo di testo
			if($txt->tagName=="w:r"){
				// leggi bold
				$txt_bold = false;
				foreach($txt->childNodes as $txt_rPr) { 
					if($txt_rPr->tagName=="w:rPr"){
						foreach($txt_rPr->childNodes as $txt_b) { 
							if($txt_b->tagName=="w:b"){
								if($txt_b->hasAttribute("w:val")) {
									$txt_bold=(bool) $txt_b->getAttribute("w:val");
								}else {
									$txt_bold=true;
								}
							}
						}
					}
				}
				// leggi testo
				if($txt->textContent!=""){
					if($txt_bold) {
						if(str_replace(" ","",$txt->textContent)!="")
							$p_temp .= "<b>".$txt->textContent."</b>";
						 else 
							$p_temp .= $txt->textContent;
					} else
					$p_temp .= $txt->textContent;
				}
			}
		}
		
		// TODO per evitare paragrafo vuoto per blocchi [se] vuoti
		//se paragrafo con solo tag se [se][/se]
		//metti il fine paragrafo all'interno del se
		
		//Aggiungi testo
		$p .= "<par ".$p_align.">".$p_temp."";
	}
	
			
	//Pulisci							
	$p = text_clean($p);
	
	//echo $p;exit;
	
	return $p;
}

function form_load($doc_type,&$p){
	
	//Get text
	$p = form_load_text($doc_type);
		
	//Reset Global Var
	$form=Array();
	$stack_se=Array();
	
	$check_addcond = Array(); // ci scrivo le condizioni aggiuntive, solo per vedere se alla fine sono state definite singolarmente.

	//Parsing codes
	$pattern = "/[\[][^\]]*[\]]/";	// [*]
	$p = preg_replace_callback_offset(	$pattern, 
							function ($op) use (&$form,&$stack_se,&$check_addcond)  {
								//Generate field $f in $form
								// (id) 
								// (tipo) 
								// (label)
								// (info)
								// (pos) 	da 	=> (p -> par, c -> car)
								//			a 	=> (p -> par, c -> car)
								// (condizioni)	 (condizione1 -> testo1)
								
								//debug
								//var_dump($op);
								//var_dump($stack_se);

								// Imposta valori default							
								$opcode=$op[0];
								$opcode_offset = $op[1];
								
								//	0.Parse Info aggiuntive e pulisci
									// Labels
									$info_label = ""; 
									$info_label_atext = "";
									$pattern = "/[\(][^\W\)]{1}[^\)]*[\)]/";	// tutte le parentesi il cui contenuto non inizia con un simbolo
									$infos = array();
									preg_match_all($pattern, $opcode, $infos);
									foreach($infos[0] as $inf){
										$txt = str_replace(array("(", ")"), "", $inf);
										$t = explode(":",$txt);
										if(array_key_exists(0,$t)) $info_label = $t[0];
										if(array_key_exists(1,$t)) $info_label_atext = $t[1];
									}
									//Forced Values
									$info_val = ""; 
									$pattern = "/[\(][=]{1}([^\)]*)[\)]/i";	// tutte le parentesi il cui contenuto inizia con =
									$infos = array();
									preg_match_all($pattern, $opcode, $infos, PREG_SET_ORDER);
									//print_r($infos);
									foreach($infos as $inf){
										// group #1 => $val
										$info_val = $inf[1];
									}
									//Calculated Values
									$info_val_calc = ""; 
									$pattern = "/[\(][#]{1}([^\)]*)[\)]/i";	// tutte le parentesi il cui contenuto inizia con #
									$infos = array();
									preg_match_all($pattern, $opcode, $infos, PREG_SET_ORDER);
									//print_r($infos);
									foreach($infos as $inf){
										// group #1 => $val
										$info_val_calc = $inf[1];
									}
									//Clean
									$opcode = preg_replace("/[\(].*[\)]/", "", $opcode);
									$opcode = preg_replace('!\s+!', ' ', $opcode);
								
								//	1.Estrai Opcode
								//  rimuovi caratteri '][' , estrai info e split contenuto per spazio
								//	[\[](.*?)(?:[\(](.*)[\)])?(?:\s)*[\]]
								//
								$pattern = "/[\[](.*?)[\]]/i";
								preg_match_all($pattern, $opcode, $tags, PREG_SET_ORDER);
								if(!array_key_exists(0,$tags)) {
									die("Error parsing ");
								}
								$opcodes = explode(" ",strtolower($tags[0][1]));
								$opcodes = array_filter($opcodes);
								
								//  1.2 Replace value defalut (same without info)
								$out_replace = "[".$tags[0][1]."]";
								
								//  2. Analize opcodes
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
										
										//Esamina oggetto della condizione - AND/OR con soggetti multipli
										// es. se aaa|bbb allora fai questo
										//estrai SOLO il primo
										$idmultiple = "";
										$pattern = '/[|]/';
										$match = preg_split( $pattern, $id);
										$id = $match[0];
										
										//Esamina se è un se condizionato da variabile
										// in questo caso il se non deve creare una voce nel form, quindi non è rilevante nella variabile $form.
										if(strpos($id,"=")!==false){
											//se condizionato da variabile
											
											$val= substr($id,strpos($id,"=")+1);
											$id = substr($id,0,strpos($id,"="));
											//echo $id.">>".$val;exit;
											//check esiste variabile della condizione?
											if(!in_array($id, get_all_var_id($form)) ){
												//TODO errore se con variabile non esistente
											}
										} else {
											//se normale
											
											// CHECK - per controllo su condizioni aggiuntive
											foreach($match as $m){
												if(substr($m,0,1)=="!") 
													$check_addcond[] = substr($m,1); 
												else 
													$check_addcond[] = $m;
											}
											
											//Valore della condizione
											//non letto in fase di generazione del documento - solo per creare Form
											//negazione -> inverti val
											$neg = check_val_neg($id);
											if($neg) $id = str_replace("!","",$id);
											$val="";
											if($tipo == "se"){
												if($neg) $val = "0"; else $val = "1";
											} else {
												if($neg) $val = "!".$opcodes[2]; else $val = $opcodes[2];
											}
										
										}
										
										//Crea stack SE
										$s = array(	"id" 	=> $id,
													"tipo"	=> $tipo,
													"offset"	=> $opcode_offset,
													"label"	=> $info_label, "info"	=> $info_label_atext,
													"sect" 	=> array( array( 	"id" => $id , 
																				"val" => $val , 
																				"dep" => sizeof($stack_se) , 
																				"txt" => "" ) 
																	) 
													);
										
										//Replace
										$out_replace = str_replace("se ","se#",$out_replace);
													
										//push to stack
										array_push($stack_se, $s);
										break;
									case "altrimenti":
										$s = array_pop($stack_se);						
										$id = $s["id"];
										//completa last sector
											$lastsect = array_pop($s["sect"]); 
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
											$newsect = array( 	"id" => $id , 
																"val" => $val , 
																"dep" => sizeof($stack_se),
																"txt" => "" ) ;
											//print_r($newsect);exit;
											array_push($s["sect"], $newsect); 
											
										//Info aggiuntive - aggiorna se immesse nell'altrimenti
										if($info_label!="") { $s["label"] = $info_label; }
										if($info_label_atext!="") { $s["info"] = $info; }
										
										//Replace
										$out_replace = str_replace("altrimenti","altrimenti#".$id,$op[0]);
										
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
											array_push($s["sect"], $lastsect);
											//completa pos-a
											//$lastpos = array_pop($s["pos"]); 
											//$lastpos["a"] = array( $cur_p, $oppos + strlen($opcode));
											//array_push($s["pos"], $lastpos); print_r($s["pos"]);exit;
											
											
										//Replace
										$out_replace = "[/se#".$id."]";

										//push stack to form
										// necessario controllare se già esistente : nel caso aggiungere pos e sect
										if (!array_key_exists($id,$form) ) {
											$f = array(	"id" => $s["id"],
																"tipo" => $s["tipo"],
																"label" => $s["label"], "info" => $s["info"],
																"sect" => array( $s["sect"]
																				)
																);
											if($info_label!="") { $form[$id]["label"] = $info_label; }
											$form[$id] = $f;
										} else {
											$f = get_var($form, $id);
											//TODO CHECK - Se tipologia diversa errore!
											$f["sect"][] = $s["sect"];
											
											//Info aggiuntive - aggiorna
											if($info_label != "") { $s["label"] = $info_label; }
											if($info_label_atext!="") { $s["info"] = $info_label_atext; }
											if($s["label"] != "") { $f["label"] = $s["label"]; }
											if($s["info"] != "") { $f["info"] = $s["info"]; }
											
											$form[$id] = $f; //Replace not add
										}
										break;
									
									//variabile
									default:
										$id = $opcodes[0];
										
										//ID sistema - skip
										if(($id=="articolo")||(($id=="comma")))
											break;
								
										//Get se stack for conditionated values
										//echo $id."=>".get_last_se($stack_se)."<br>\n";
										$validity[] = get_last_se_validity($stack_se);
										
										//check if "id" exist
										if (!array_key_exists($id,$form) ) {	//is_null(get_var($form,$opcodes[0]))
											//not exist: create
											$f = array(	"id" => $opcodes[0],
														"tipo" => "var",
														"offset"	=> $opcode_offset,
														"label" => $info_label, 
														"info" => $info_label_atext,
														"valid" => $validity, 
														"val" => $info_val,
														"val_calc" => $info_val_calc
														);
											if($info_label!="") $f["label"] = $info_label;
											//Add to form
											$form[$id] = $f;
										} else {
											//exist: add position
											$f = get_var($form,$opcodes[0]);
											if($info_label!="") $f["label"] = $info_label;
											$f["valid"] = array_merge($f["valid"], $validity);
											$form[$id] = $f; //Replace not add
										}
											/*
												//	[\[](.*?)(?:[\(](.*)[\)])?(?:\s)*(?:\=([\=])?(.*))?(?:\s)*[\]]
												//
												//		[nome (sddaa)==a,b,c]
												//
												//			group #1 =>	nome
												//			group #2 =>	id
												//			group #3 =>	'=' se valori se scelta	  ' ' se valore predefinito
												//			group #4 =>	valori
												//
												$pattern = '/[\[](.*?)(?:[\(](.*)[\)])?(?:\s)*(?:\=([\=])?(.*))?(?:\s)*[\]]/';
												preg_match_all($pattern,$op[0],$m, PREG_SET_ORDER);
												if(count($m)>0){
													$id = $m[0][1];
													//var_dump($opcodes[0]);
													//var_dump($m);
													if(count($m[0])>3) {
														$val_type = $m[0][3];
														$val = $m[0][4];
													}else{
														$val_type = "";
														$val = "";
													}
												*/
											
											//echo substr($p, $oppos, strlen($opcode))."\n\n\n"; 
										
								}
								
								//var_dump($out_replace);
								return $out_replace;
							},
							$p,
							PREG_OFFSET_CAPTURE );
	
	//Check
	
		//Se non chiusi
		//var_dump($stack_se);
		if(count($stack_se)>0){
			foreach($stack_se as $se){
				echo "<pre class='xdebug-var-dump' dir='ltr'>ERRORE nel template <b>'".$doc_type."'</b>: Sezione <font color='#cc0000'>[se ".$se['id']."]</font> incompleta!  (carattere nr:".$se['offset'].")</pre>";
			}
		}
		
		//Condition not defined
		//var_dump($check_addcond);
		$check_addcond = array_unique($check_addcond);
		foreach($check_addcond as $cond){
			if(substr($cond,0,1)=="!") $cond = substr($cond,1);
			if (!array_key_exists($cond,$form) ) {
				echo "<pre class='xdebug-var-dump' dir='ltr'>ERRORE nel template <b>'".$doc_type."'</b>: La condizione ausiliaria <font color='#cc0000'>'".$cond."'</font> viene utilizzata senza definizione!</pre>";
			}
		}
	
	//var_dump($form);
	//echo str_replace("<par>","<br>",$p);
	
	//Riordina
	//usort($form, "form_sort");
	
	return $form;
}

function get_last_se_validity($stack_se){
	if(count($stack_se)==0){
		return '*';
	}else {
		//var_dump($stack_se);
		$last_se = end($stack_se);
		$last_sect = end($last_se['sect']);
		return $last_sect['id']."=".$last_sect['val'];
	}
}

function form_sect($form){
	//Estrai Sections
	$section = "";
	foreach ($form as $field) {
		if(array_key_exists('sect',$field)){
			foreach($field['sect'] as $sect){
				$section[] = $sect;
			}
		}
	}
	usort($section, "sect_cmp");
	return $section;
}

function sect_cmp($a, $b){
	//var_dump($a);exit;
    if ($a[0]['dep'] == $b[0]['dep']) {
        return 0;
    }
    return ($a[0]['dep'] > $b[0]['dep']) ? -1 : 1;
}

function form_sort($a, $b){
	return strcasecmp($a['id'],$b['id']);
}

function check_val_neg($val){
	$neg = false;
	if(substr($val,0,1)=="!"){
		$neg = true;
	}
	return $neg;
}


//CREATE Form

function form_se($form){
	
	usort($form, "form_sort");
	
	//Only 'se'
	foreach ($form as $field) {
		//print_r($field);
		$id = $field['id'];
		$info_label = ($field['label']!="" ? $field['label'] : $field['id']); 
		$info_label = str_replace("_", " ", $info_label);
		
		switch ($field["tipo"]) {
				case "se":	//se checkbox
					
					//var_dump($field);
					
					?>
						<!-- se checkbox -->
						<div class="form-group form-group-sm">
						<label for="<?php echo $id; ?>" class="control-label col-md-4"><?php echo $info_label; ?></label>
							<div class="col-md-8">
								<div class="checkbox">
									<label><input type="checkbox" name="<?php echo $id; ?>" id="<?php echo $id; ?>" value="1"></label>
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
					for($i=0;$i<count($field['sect']);$i++){
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
						<div class="form-group form-group-sm radio_buttons optional user_horizontal_sex input-group-radio">
							<label class="radio_buttons control-label col-md-4"><?php echo $info_label; ?></label>
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
	
function form_var(){
	global $form;
	//Only 'var'
	foreach ($form as $field) {
		if($field["tipo"]=="var") {
			print_var($field);
		}
	}
	
}
	function print_var($field,$add=false){
		global $form;
		global $form_validityr;
	
		//E' un aggiunta ad un campo precedente?
		$id = $field['id'];
		if($add){
			 $info_label="...in lettere";
			 $pos1=5;
			 $pos2=5;
		}else{
			 $info_label = ($field['label']!="" ? $field['label'] : $field['id']); 
			 $pos1=4;
			 $pos2=6;
		}
		$info_label = str_replace("_", " ", $info_label);
		
		//suffisso _lettere
		$suffisso = "_lettere";
		$f_add = false;
		if(!$add){
			if ( endsWith($id, "_lettere") && array_key_exists(substr($id,0,strlen($suffisso)*-1),$form)) return;
			if (array_key_exists($id."_lettere",$form) ) { $f_add=true; }
		}
			
		//Gestisci Valori
		$forced_val = "";
		$forced_type = "";
		if(array_key_exists("val",$field)){
			if($field['val']!=""){
				$forced_val = explode(",",$field['val']);
				$forced_val = array_filter($forced_val);
				if(count($forced_val)>1){
					//valori obbligatori
					$forced_type = "list";
				} else {
					//valore predefinito
					$forced_type = "value";
				}
			}
		}
		
		//Mostra sempre o nascondi
		$hide = form_var_to_hide($id);
			
		//var_dump($form_validityr['*']);
		
		//Seleziona tipologia
		if($forced_type=="list"){
			//Dropdown
			
			?>
				<!-- input con menu -->
				<div class="form-group form-group-sm" style="<?php if($hide) echo "display:none;";?>">
					<label for="<?php echo $id; ?>" class="control-label col-md-<?php echo $pos1; ?>"><?php echo $info_label; ?></label>
					<div class="col-md-<?php echo $pos2; ?>">
						<div class="input-group input-group-sm">
						  <input type="text" class="form-control" name="<?php echo $id; ?>" id="<?php echo $id; ?>" placeholder="<?php echo $id; ?>" value="">
						  <div class="input-group-btn">
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <span class="caret"></span></button>
							<ul class="dropdown-menu dropdown-menu-right">
							<?php
								foreach($forced_val as $val){
									echo '<li><a input="'.$id.'">'.$val."</a></li>";
								}
							?>
							</ul>
						  </div>
						</div>
					</div>
				</div>
				
			<?php
			
		} else {
			//Input normale
			
			?> 
				<!-- variable -->
				<div class="form-group form-group-sm" style="<?php if($hide) echo "display:none;"; if($f_add) echo "margin-bottom:5px !important;";?>" >
					<label for="<?php echo $id; ?>" class="control-label col-md-<?php echo $pos1; ?>"><?php echo $info_label; ?></label>
					<div class="col-md-<?php echo $pos2; ?>">
						<input type="text" class="form-control" name="<?php echo $id; ?>" id="<?php echo $id; ?>" placeholder="<?php echo $id; ?>" value="<?php echo $forced_val;?>">
						<?php if($field['info']!=""){ ?><span class="help-block"> <?php echo $field['info']; ?> </span><?php } ?>
					</div>
				</div>
			<?php
			
		}
		
		// suffisso _lettere
		if ($f_add) {
			$field_add = get_var($form,$id."_lettere");
			print_var($field_add,true);
		}
		
	}

function form_relations($form){
	
	$val = Array();
	$val['*'] = Array();
	
	foreach ($form as $field) {
		
		$id = $field['id'];
		if($field['tipo']=="var"){
			
			if(!array_key_exists("id",$val['*'])){
				sort($field["valid"]);
				foreach ($field["valid"] as $condition){
					if($condition=='*') {
						$val['*'][] = $id;
						break;
					}else{
						$s = explode("=",$condition);
						$campo = $s[0];
						$valore = $s[1];
						$val[$campo][]=$id;
						$val[$campo] = array_unique($val[$campo]);
					}
				}						
			}
			
		}
		
	}
	var_dump($val);

	return $val;
}
function form_validity($form){
	
	$val = Array();
	$val['*'] = Array();
	
	foreach ($form as $field) {
		
		$id = $field['id'];
		if($field['tipo']=="var"){

			if(!array_key_exists("id",$val['*'])){
				sort($field["valid"]);
				foreach ($field["valid"] as $condition){
					if($condition=='*') {
						$val['*'][] = $id;
						break;
					}else{
						$s = explode("=",$condition);
						$campo = $s[0];
						$valore = $s[1];
						$val[$id][$campo][]=$valore;
						$val[$id][$campo] = array_unique($val[$id][$campo]);
					}
				}						
			}
			
		}
		
	}
	unset($val['*']);
	var_dump($val);
	
	return $val;
}

function form_validityrel($form){
	
	$val = Array();
	$val['*'] = Array();
	$val2 = Array();
	$val3 = Array();
	
	foreach ($form as $field) {
		
		$id = $field['id'];
		if($field['tipo']=="var"){
			
			//echo $id;
			//var_dump($field["valid"]);
			
			// validità costruisci array
			// da : id da attivare -> campo -> val
			// a : campo -> valore -> id da attivare
			if(!array_key_exists("id",$val['*'])){
				sort($field["valid"]);
				foreach ($field["valid"] as $condition){
					if($condition=='*') {
						$val['*'][] = $id;
						break;
					}else{
						$s = explode("=",$condition);
						$campo = $s[0];
						$valore = $s[1];
						$val[$campo][$valore][]=$id;
						$val2[$id][$campo][]=$valore;
						$val3[$campo][]=$id;
						$val[$campo][$valore] = array_unique($val[$campo][$valore]);
					}
				}						
			}
			
		}
		
	}
	//var_dump($val3);exit;
	
	//Cancella eventuali record in $val[][][$id]
	/*foreach($val['*'] as $field){
		foreach ($val as $id => &$arr_v){
			if ($id!= '*')
			foreach($arr_v as $v => &$id){
				if($id==$field){
					echo $id.">".$v." ".$id."-".$field."<br>\n";
					//unset($arr_v[$v]);
				}
			}
		}
	}*/
	
	//var_dump($val);
	//exit;

	return $val;
}

function form_var_to_hide($id){
	global $field_validity_inv;
	global $form;
	
	//determina se mostrare per defualt una variabile oppure nasconderla
	
	// variabili presenti nel corpo del testo (non dentro a dei se)
	if(in_array($id,$field_validity_inv['*'])){
		return false;
	}
	
	//variabili in un se (checkbox) a valore 0 (che è il default per un checkbox)
	foreach($field_validity_inv as $choice_id=>$choice_values){
		//se è un checkbox
		if(array_key_exists($choice_id,$form))
		if($form[$choice_id]['tipo'] == "se"){
			if(array_key_exists("0",$choice_values)){
				foreach($choice_values['0'] as $var_id){
					if($id==$var_id){
						return false;
					}
				}	
			}		
		}
	}
	
	return true;
}

//prendi il nome della variabile ritenuta 'soggetto' del form
function form_type_subject_var($vars){
	//Seleziona nome cliente
	$key_matr = Array(	Array('cliente','nome'),
						Array('cliente','intestazione'),
						Array('soggetto','stipulante'),
						Array('soggetto','firmatario')
				);
	$ret = false;
	foreach ($vars as $var) {
		if(array_key_exists("name",$var)){
			foreach ($key_matr  as $key_array) {
				$ret = true;
				foreach ($key_array as $key)
					if(strpos($var->name, $key) === false) { $ret = false; break; }
				if($ret) return $var->name;
			}
		}
	}
	return $ret;
}

//DEBUG

//Paragrafi
//print_r($listp);

//Form
//print_r($form);

//GENERATE Word

function text_compose($form,&$p,$data){
	
		//Debug
		//var_dump($field);exit;
		//echo($p);exit;
		//var_dump($form);exit;
		//echo str_replace("<par>","<br>",$listp);
		$dbg_choice = false;
		


		
		//Elabora
		foreach ($form as $var) {
			$var_id = $var['id'];
			
			//debug
			//var_dump($var);
			//echo "\n<br>".$var_id." - >".$var['tipo']."<";
			
			//Trova Valore
			if(in_array($var['tipo'],Array("var","se","sed"))){
				//prendi valore in form salvato
				$var_value = get_data_val($data,$var);
			
				//Esamina eventuali valori calcolo
				if(array_key_exists('val_calc',$var)){
					if($var['val_calc']!=""){
						$val_calc = str_var_replace($form,$data,$var['val_calc']);
						//calcola
						$var_value = calculate_string($val_calc);
					}
				}
			}
			
			//Sostituisci tutte le variabili
			if($var['tipo']=="var"){
				
				//Sostituisci
				$pattern = '/[\[]('.$var_id.')[\s]*[\]]/i';
				$p = preg_replace($pattern, $var_value, $p);
				
				//VAR con sect
				//se questa varabile ha un campo sect significa che esiste un [se#%var%=qualcosa]aaaa[/se]
				if(array_key_exists('sect',$var)){
					//
					//v.1 	[\[](?:se#prova)[=]([^\]\|]*)((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[altrimenti#prova\](.*?))?(?:\[\/se#prova])
					//
					// [se#prova=!condizione|!qwe] testo1 [altrimenti#prova]  testo2  [/se#prova]
					//			group #1 =>	!condizione
					//			group #2 =>	|!qwe
					//			group #3 =>	testo1
					//			group #4 =>	testo2
					
					$pattern = "/[\[](?:se#".$var_id.")[=]([^\]\|\&]*)((?:[\|\&](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[altrimenti#".$var_id."\](.*?))?(?:\[\/se#".$var_id."])/";
					
					$p = preg_replace_callback(	$pattern,
												function ($matches) use ($var_id,$var_value,$form,$data)  {
													global $dbg_choice;
													//debug
													//$dbg_choice = true;
													if($dbg_choice) echo "\n<br>Trovato\n (val:".$var_value.")";
													if($dbg_choice) var_dump($matches)."\n\n";
													//0 -> contenuto completo (da sostituire)
													//1 -> condizione					(!testo oppure testo)
													//2 -> altre condizioni
													//3 -> contenuto del se				(valore da retituire se true true)
													//4 -> contenuto dell'altrimenti	(valore da retituire se true falso)
													
													//Imposta valore falso - se trovi una condizione vera cambialo
													$out_value_false = count($matches)>3 ? @$matches[4] : "";
													$out_value_true = $matches[3];
													$out_bool = false;
													
													//Analisi prima condizione
													$c_val = $matches[1];
													$c_neg = false;
													if($c_val{0}=="!"){
														$c_val = substr($c_val,1);
														$c_neg = true;
													}
													//Sostituisci eventuali variabili
													$c_val = str_var_replace($form,$data,$c_val);
													//valuta
													$out_bool = compare($out_bool, "|", $c_neg, $var_value, $c_val);
													if($dbg_choice) echo "se $var_id (neg: '$c_neg' cond: '$c_val' val: '".$var_value."' result:'$out_bool')";
													
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
															$c_op = $c{0};		//operatore
																$c_neg = false;
																$c_val = "";		//id
																if($c{1}=="!") {
																	//neg
																	$c_neg = true;
																	$c_val = substr($c,2);
																} else {
																	//normal
																	$c_neg = false;
																	$c_val = substr($c,1);
																}
															//Valore condizione
															$c_val = str_var_replace($form,$data,$c_val);
															//Valuta
															$out_bool = compare($out_bool, $c_op, $c_neg, $var_value, $c_val);
															if($dbg_choice) echo " $c_op (neg: '$c_neg' cond:'$c_val' val:'$var_value' result:'$out_bool')"; 
														}
													}
													
													//Ritorna valore
													if($out_bool) return $out_value_true; else return $out_value_false;
												},
												$p );
					
					//debug
					//echo $var_id;
					//exit;
					
				}
				
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
				
				//echo "\n";
				//echo $var_id."\n";
				$pattern = "/[\[](?:se#)([!])?(?:".$var_id.")((?:[\|](?:[!])?(?:\w*))*)?[^\]]*[\]](.*?)(?:\[altrimenti#".$var_id."\](.*?))?(?:\[\/se#".$var_id."])/i";
				//echo $pattern."\n"."\n";
				$p = preg_replace_callback(	$pattern,
												function ($matches) use ($var_id,$var_value,$form,$data)  {
													global $dbg_choice;
													//debug
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
												$p );
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
				
				$p = preg_replace_callback(	$pattern,
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
												$p );
												
												//echo str_replace("<par>","<br>",$p);
												//exit;
			}
			
			
		}
		
		//Sostituisci Articolo / comma
		$articolo = 0;
		$comma = 0;
		$p = preg_replace_callback(	'/\[(articolo)(?:\s?(\d+)\s?)?\]|\[(comma)(?:\s?(\d+)\s?)?\]/i',
										function ($matches) {
											global $articolo;
											global $comma;
											//var_dump($matches);
											//var_dump(sizeof($matches));
											//Controlla Variabili di sistema
											$var_value = "";
											if(sizeof($matches)<2){
													if($matches[2]!="")
														$articolo = $articolo + 1;
													else
														$articolo = $matches[2];
													$comma = 0;
													$var_value = $articolo;
											} else if(count($matches)>2) {
													if(count($matches)>4)
														$comma = $matches[4];
													else
														$comma = $comma + 1;
													$var_value = $comma;
											}
											
											return $var_value;
											
										},
										$p);
										
		//echo $p;exit;
		
		
		
		//Pulisci
		$p = text_clean($p);
	
	
}

function text_wordize($form,$par,$doc_id){
	
	require_once 'PhpWord/Autoloader.php';
	\PhpOffice\PhpWord\Autoloader::register();
	
	//\PhpOffice\PhpWord\Settings::setPdfRendererPath('tcpdf');
	//\PhpOffice\PhpWord\Settings::setPdfRendererName('TCPDF');

	// Creating the new document...
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	
	// Adding an empty Section to the document...
	$section = $phpWord->addSection();
	
	//Split
	$array_p = preg_split('/(?:<(b)>|<(\/b)>|<(par(?:[^>]*))>)/', $par, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	
	//Compose
	$fontStyle = array('name' => 'Tahoma', 'size' => 10);
	$paragraphStyle = array('align' => 'both', 'spaceAfter' => 0);
	//$section->addText('I am simple paragraph', $fontStyle, $paragraphStyle);

	//Iterate
	$textrun = $section->addTextRun();
	$bold = false;
	foreach($array_p as $p){
		
		$codes = explode(" ",$p);
		switch($codes[0]){
			case "b":
				$bold = true;
			break;
			case "/b":
				$bold = false;
			break;
			case "par":
				//align
				$align = "";
				if(count($codes)>1){
					$align = $codes[1];
				}
				$textrun = $section->addTextRun(array('align' => $align, 'spaceAfter' => 0, 'spaceBefore' => 0, 'lineHeight' => 1));
			break;
			default:
				$textrun->addText(htmlspecialchars($p), array('bold' => $bold) );
		}

		
	}

	//Send to Browser
	header("Content-Description: File Transfer");
	header('Content-Disposition: attachment; filename="doc_'.$doc_id.'.docx"');
	header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document; charset=UTF-8;');
	header('Content-Transfer-Encoding: binary');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Expires: 0');
	$xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
	//$xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord , 'PDF');
	$xmlWriter->save("php://output");

}

function text_clean($p){
	
	//echo($p);exit;
	
	//Pulisci interno dei tags
	//Controllo dell'interruzione di <b> all'interno di tag []  
	$pattern = "/[\[][^\]]*[\]]/";	// [*]
	$p = preg_replace_callback(		$pattern, 
									function ($matches) { 
										//var_dump($matches);
										$counter = 0;
										$clean = preg_replace_callback(	'/<[^>]*>/', 
																		function ($m) use(&$counter) {
																			if($m[0][1]=="/")
																				$counter--;
																				else
																				$counter++;
																			return "";
																		},
																		$matches[0]);
										
										//Chiudi o riapri <b> fupori dal tag
										//var_dump($counter);
										$clean_add="";
										while($counter<>0){
											if($counter>0){
												$clean_add.="<b>";
												$counter--;
											}else{
												$clean_add.="</b>";
												$counter++;
											}
										}
										
										return $clean.$clean_add;
									} , $p);
	
	//pulisci <b></b>
	$p = str_replace("<b></b>","",$p);
	$p = str_replace("</b><b>","",$p);
	
	//Rimuovi <par> duplicati
	//$p = preg_replace('/(?:<par\s?([^>]*)>)+/', '<par $1>', $p);			//mantieni solo 1 par
	$p = preg_replace('/(?:<par\s?([^>]*)>){3,}/', '<par><par $1>', $p);	//mantieni al max 2 par (1 rigo vuoto)
	
	return $p;
}


/* Funzioni su $data / $form
*/

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
function get_data_val_id($data,$id){
	return get_data_val($data, Array('tipo'=>'', 'id'=>$id ) ) ;
}
function get_var($form,$id){
	foreach ($form as $var) {
		if($var['id']==$id)
			return $var;
	}
	return null;
}
function get_all_var_id($form){
	$vars = Array();
	foreach ($form as $var) {
		if($var['tipo']=="var")
			$vars[] = $var['id'];
	}
	return $vars;
}
function get_all_id($form){
	$vars = Array();
	foreach ($form as $var) {
			$vars[] = $var['id'];
	}
	return $vars;
}

//sostituisci variabili in stringa
function str_var_replace($form,$data,$val_calc){
	//foreach(get_all_var_id($form) as $v){		//solo var
	foreach(get_all_id($form) as $v){		//anche se
		if(strpos($val_calc,$v) !== false){
			$val_calc = str_replace($v,get_data_val_id($data,$v),$val_calc);
		}
	}
	return $val_calc;
}



/* Funzioni Generiche
*/

function preg_replace_callback_offset($pattern, $callback, $subject, $limit = -1, &$count = 0) {
    if (is_array($subject)) {
        foreach ($subject as &$subSubject) {
            $subSubject = preg_replace_callback_offset($pattern, $callback, $subSubject, $limit, $subCount);
            $count += $subCount;
        }
        return $subject;
    }
    if (is_array($pattern)) {
        foreach ($pattern as $subPattern) {
            $subject = preg_replace_callback_offset($subPattern, $callback, $subject, $limit, $subCount);
            $count += $subCount;
        }
        return $subject;
    }
    $limit  = max(-1, (int)$limit);
    $count  = 0;
    $offset = 0;
    $buffer = (string)$subject;
    while ($limit === -1 || $count < $limit) {
        $result = preg_match($pattern, $buffer, $matches, PREG_OFFSET_CAPTURE, $offset);
        if (FALSE === $result) return FALSE;
        if (!$result) break;
        $pos     = $matches[0][1];
        $len     = strlen($matches[0][0]);
        $replace = call_user_func($callback, $matches[0]);
        $buffer = substr_replace($buffer, $replace, $pos, $len);
        $offset = $pos + strlen($replace);
        $count++;
    }
    return $buffer;
}
function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}


// Compara condizioni
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