<html xmlns="http://www.w3.org/1999/xhtml"> 
	<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	  <!--
	  <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	  <meta http-equiv="x-ua-compatible" content="IE=7" />
	  -->
	  <meta http-equiv="X-UA-Compatible" content="IE=9">
		<title>ChiantiBanca: Test Minute</title>
	</head>
<body>

<?php
$archiveFile = "mutuo.docx";

// Load Xml
$xml = new DOMDocument();

//Load Word .docx
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

// Form
$form=Array();
$stack_se=Array();

// Parsing codes
$cur_p = 0;
foreach ($listp as $p) {
	preg_match_all("/[\[][^\]]*[\]]/", $p, $matches, PREG_OFFSET_CAPTURE );
	// Orders results so that $matches[0] is an array of full pattern matches, $matches[1]
	//print_r($matches);echo "<br><br>\n\n";
	
	//per ogni string all'interno di match[0]
	foreach($matches[0] as $op){
		//print_r($op);
		//echo $cur_p .">".$p."<\n\n\n"; 
		
		$opcode=$op[0];
		$oppos=$op[1];
		
		//Generate field $f
		// (id) 
		// (tipo) 
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
		//		se											[se xx]  	-> +form checkbox												(len 2)
		//													[se xx a]  	-> +form dropdown con nome xx (aggiungi 'a' a dropdown)			(len >2)
		//													
		//		altrimenti
		//		/se
		//altrimenti è un campo da inserire nel form
		
			// stack_se lifo_se
			//		id	pos(da,a) sect(val,da,a,txt)
			//	se:				crea
			//	altrimenti: 	a , text : ultimo sect
			//	/se:			a , text : ultimo sect
			//					push form
		$f = "";
		$s = "";
		$id = "";
		switch ($opcodes[0]) {
			
			//se
			case "se":
				//crea se
				$id = $opcodes[1];
				
						//tipo se
						$tipo="";
						if(sizeof($opcodes)==2){
							//se solo 1 parola se => checkbox
							$tipo = "se";
						}else{
							//se piu parole => dropdown
							$tipo = "sed";
						}
						
						//condizione
						$val="";
						if($tipo == "se"){
							$val = "1";
						} else {
							$val = $opcodes[2];
						}
						
						//crea
						$s = array(	"id" 	=> $id,
									"tipo"	=> $tipo,
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
				//tipo di altrimenti
				$val="";
				if($s["tipo"] == "se"){	
					//checkbox
					$val = 0;
				}else{
					//dropdown
					$val = $opcodes[1];
				}
				//todo if sizeof($opcodes)>1 e tipo se avverti anomalia.
				
				//aggiungi sect
				$id = $s["id"];
				//completa last sector
					$lastsect = array_pop($s["sect"]); 
					$lastsect["a"] = array($cur_p, $oppos);
					//testo della sezione
					$lastsect["txt"] = get_text($lastsect["da"],$lastsect["a"]);
					array_push($s["sect"], $lastsect);
				//inizializza settore
					$newsect = array( "val" => $val , "da" => array($cur_p, $oppos + strlen($opcode)) , "a" => array() , "txt" => "" ) ;
					array_push($s["sect"], $newsect); 
					
				//se solo parola 'altrimenti' -> add condizione 0 => "testo"
				//se piu parole *** NON POSSIBILE ?
					
				array_push($stack_se, $s);
				break;
			case "/se":
				//chiudi se
				$s = array_pop($stack_se);
				$id = $s["id"];
					//completa last sector
					$lastsect = array_pop($s["sect"]);
					$lastsect["a"] = array($cur_p, $oppos);
					//testo della sezione - todo
					$lastsect["txt"] = get_text($lastsect["da"],$lastsect["a"]);
					array_push($s["sect"], $lastsect);
					//completa pos-a
					//$lastpos = array_pop($s["pos"]); 
					//$lastpos["a"] = array( $cur_p, $oppos + strlen($opcode));
					//array_push($s["pos"], $lastpos); print_r($s["pos"]);exit;
					$s["pos"]["a"] = array( $cur_p, $oppos + strlen($opcode));
				//push to form
				if (!array_key_exists($id,$form) ) {
					$form[$id] = array(	"id" => $s["id"],
										"tipo" => $s["tipo"],
										"pos" => array( $s["pos"]
														), //array di array per posizioni multiple
										"sect" => array( $s["sect"]
														)
										);
				} else {
					$f = search_form($id, $form);
					$f["pos"][] = $s["pos"];
					$f["sect"][] = $s["sect"];
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
								"pos" => array( array(	"da" => array( $cur_p, $oppos), 
														"a" => array( $cur_p, $oppos + strlen($opcode))	
														)
												) //array di array per posizioni multiple
								);
					//Add to form
					$form[$id] = $f;
				} else {
					//exist: add position
					$f = search_form($opcodes[0], $form);
					$f["pos"][] = array(	"da" => array( $cur_p, $oppos), 
											"a" => array( $cur_p, $oppos + strlen($opcode))	
										);
					$form[$id] = $f; //Replace not add
				}
				//echo substr($p, $oppos, strlen($opcode))."\n\n\n"; 
		}

	}
	
	$cur_p++;
}

print_r($form);
//print_r($listp);


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
		$stop=0;
		if($i==$f_p){
			$start = $f_c;
		}
		if($i==$t_p){
			$stop = $t_c;
		}
			}elseif($i==$t_p){
				//last par
				$text .= substr($listp[$i],0,$t_c);
			}else{
				//middle par
				$text .= $listp[$i];
			}
			
		$text .= substr($listp[$i],$f_c);
	}
	return $text;
}

?>
<!--
(cliente_nome|Nome del cliente:)   (cliente_nome)   
-->

</body>
</html>