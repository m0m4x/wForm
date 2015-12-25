<?php
ini_set('xdebug.var_display_max_depth', 10);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

//HEADER - NO CACHE
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-Type: text/html; charset=UTF-8'); 


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
		foreach($par->childNodes as $txt) { 
			//print_r($txt);
			if($txt->tagName=="w:r"){
				$txt_bold = false;
				foreach($txt->childNodes as $txt_rPr) { 
					if($txt_rPr->tagName=="w:rPr"){
						foreach($txt_rPr->childNodes as $txt_b) { 
							if($txt_b->tagName=="w:b")
								$txt_bold=true;
						}
					}
				}
				if($txt->nodeValue!=""){
					if($txt_bold)
					$p .= "<b>".$txt->nodeValue."</b>";
					else
					$p .= $txt->nodeValue;
				}
			}
		}
		$p .= "<par>";
	}
	
	//Pulisci tags
	$pattern = "/[\[][^\]]*[\]]/";	// [*]
	$p = preg_replace_callback(		$pattern, 
									function ($matches) { 
										$counter = 0;
										$clean = preg_replace_callback(	'/<[^>]*>/', 
																		function ($m) use($counter) {
																			//echo $m[0][1];
																			if($m[0][1]=="/")
																				$counter--;
																				else
																				$counter++;
																			return "";
																		},
																		$matches[0]);
											return $clean;
									} , $p);
					
									
	//Rimuovi <par> duplicati
	$p = preg_replace('#(<par\s?/?>)+#', '<par>', $p);
	
	//pulisci <b></b>
	$p = str_replace("<b></b>","",$p);
	$p = str_replace("</b><b>","",$p);
	
	return $p;
}

function form_load_p($doc_type){
	
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
	}
	
	return $listp;
}

/*$form=Array();
$stack_se=Array();*/
function form_load($doc_type,&$p){
	
	//Get text
	$p = form_load_text($doc_type);
		
	//Reset Global Var
	$form=Array();
	$stack_se=Array();

	//Parsing codes
	$pattern = "/[\[][^\]]*[\]]/";	// [*]
	$p = preg_replace_callback(	$pattern, 
							function ($op) use (&$form,&$stack_se)  {
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
									$info_val_type = "";
									$info_val = ""; 
									$pattern = "/[\(][=]{1}([=])?([^\)]*)[\)]/i";	// tutte le parentesi il cui contenuto inizia con =
									$infos = array();
									preg_match_all($pattern, $opcode, $infos, PREG_SET_ORDER);
									//print_r($infos);
									foreach($infos as $inf){
										// group #1 => $val_type
										// group #2 => $val
										$info_val_type = $inf[1];
										$info_val = $inf[2];
									}
									
									//Clean
									$opcode = preg_replace("/[\(][^\)]*[\)]/", "", $opcode);
								
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
										
										//In caso di AND/OR prendi solo il primo come id
										$pattern = '/[|&]/';
										$match = preg_split( $pattern, $id);
										$id = $match[0];
										
										//Valore della condizione
										//negazione -> inverti val
											$neg = check_val_neg($id);
											if($neg) $id = str_replace("!","",$id);
										$val="";
										if($tipo == "se"){
											if($neg) $val = "0"; else $val = "1";
										} else {
											if($neg) $val = "!".$opcodes[2]; else $val = $opcodes[2];
										}
										
										//Crea stack SE
										$s = array(	"id" 	=> $id,
													"tipo"	=> $tipo,
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
											$f = search_form($id, $form);
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
										//check if "id" exist
										if (!array_key_exists($id,$form) ) {	//is_null(search_form($opcodes[0], $form))
											//not exist: create
											$f = array(	"id" => $opcodes[0],
														"tipo" => "var",
														"label" => $info_label, 
														"info" => $info_label_atext,
														"val" => $info_val,
														"val_tipo" => $info_val_type
														);
											if($info_label!="") $f["label"] = $info_label;
											//Add to form
											$form[$id] = $f;
										} else {
											//exist: add position
											$f = search_form($opcodes[0], $form);
											if($info_label!="") $f["label"] = $info_label;
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
	
	//var_dump($form);exit;
	//echo str_replace("<par>","<br>",$p);
	
	//Riordina
	//usort($form, "form_sort");
	
	return $form;
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
function sect_cmp($a, $b)
{
	//var_dump($a);exit;
    if ($a[0]['dep'] == $b[0]['dep']) {
        return 0;
    }
    return ($a[0]['dep'] > $b[0]['dep']) ? -1 : 1;
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


/*
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
*/

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
	
function form_var($form){
	
	//Only 'var'
	foreach ($form as $field) {
		//print_r($field);
		$id = $field['id'];
		$info_label = ($field['label']!="" ? $field['label'] : $field['id']); 
		$info_label = str_replace("_", " ", $info_label);
		
				
		//Gestisci Valori
		$forced_val = "";
		if(array_key_exists("val",$field)){
			if($field['val']!=""){
				if($field['val_tipo']=="="){
					//valori obbligatori
					$forced_val = explode(",",$field['val']);
					$forced_val = array_filter($forced_val);
					//var_dump($forced_val);
				} else {
					//valore predefinito
					$forced_val = $field['val'];
				}
			}
		}
	
/*	

		*/
		
		//Switch
		switch ($field["tipo"]) {
				case "var":	//variable
					//var_dump($field);
					//Seleziona tipologia
					if($field['val_tipo']=="="){
						//Dropdown
						
						?>
							<!-- input con menu -->
							<div class="form-group form-group-sm">
								<label for="<?php echo $id; ?>" class="control-label col-md-4"><?php echo $info_label; ?></label>
								<div class="col-md-6">
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
							

							<!-- scelta tra valori 
							<div class="form-group form-group-sm radio_buttons optional user_horizontal_sex">
								<label class="radio_buttons control-label col-md-4"><?php echo $info_label; ?></label>
								<div class="col-md-6">
								<input type="text" class="form-control" name="<?php echo $id; ?>" id="<?php echo $id; ?>" placeholder="<?php echo $id; ?>" value="">
								<?php
								
									foreach($forced_val as $value){
										$val_id = $id."_".$value;
										$val_name= $id;
										?>
										
											<label class="radio-inline radio-inline">
											
												<input type="radio" name="<?php echo $val_name; ?>" id="<?php echo $val_id; ?>" value="<?php echo $value; ?>"> <?php echo str_replace("_"," ", $value); ?>
											</label>
										<?php
									}
								
								?>
								<?php if($field['info']!=""){ ?><p class="help-block"> <?php echo $field['info']; ?> </p><?php } ?>
								</div>
							</div>
							-->
						
						<?php
						
					} else {
						//Input normale
						
						?>
							<!-- variable -->
							<div class="form-group form-group-sm">
								<label for="<?php echo $id; ?>" class="control-label col-md-4"><?php echo $info_label; ?></label>
								<div class="col-md-6">
									<input type="text" class="form-control" name="<?php echo $id; ?>" id="<?php echo $id; ?>" placeholder="<?php echo $id; ?>" value="<?php echo $forced_val;?>">
									<?php if($field['info']!=""){ ?><span class="help-block"> <?php echo $field['info']; ?> </span><?php } ?>
								</div>
							</div>
						<?php
						
					}
				

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