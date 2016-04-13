<?php
/*
 * This file is part of wForm
 * 
 * Copyright (C) 2016 Zanini Massimo
 * 
 * wForm is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * wForm is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with wForm.  If not, see <http://www.gnu.org/licenses/agpl.html>
 *
 */


//HEADER - NO CACHE
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-Type: text/html; charset=UTF-8'); 

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
$data = isset($_GET['data']) ? mysqli_real_escape_string($dbhandle,$_GET['data']) : null;

//ACTION
switch ($action) {
    case "put":
		if(is_null($id)){
			//crea
			$id = getToken(5);
			//aggiorna data (inserendo id)
			$data = json_decode($_GET['data']);
			$data[0]->id = $id;
			$data = mysqli_real_escape_string($dbhandle,json_encode($data));
			//crea query
			$sql = "INSERT INTO `wform`.`form` (`id_form`, `data`, `created`) VALUES ('".$id."', '".$data."', NOW());";
			//esegui
			$stmt = mysqli_query( $dbhandle, $sql);
			if ( !$stmt ){
				echo "#Operazione fallita! (".$id .") err db: ".print_r( sqlsrv_errors(), true);
			} else {
				echo ">".$id;
			}
		}else{
			//modifica
			$sql = "UPDATE `wform`.`form` SET `data` = '".$data."' WHERE `form`.`id_form` = '".$id."';";
			//esegui
			$stmt = mysqli_query( $dbhandle, $sql);
			if ( !$stmt ){
				echo "#Operazione fallita! (".$id .") err db: ".print_r( sqlsrv_errors(), true);
			} else {
				echo "=".$id;
			}
		}
    break;
	case "get":
		$sql = "SELECT * FROM `wform`.`form` WHERE id_form = '".$id."' ";
		$stmt = mysqli_query( $dbhandle, $sql);
		if ( !$stmt ){
			 echo "#Error in statement execution.\n";
			 die( print_r( mysqli_error($dbhandle), true));
		}
		$count = 0;
		if( $row = mysqli_fetch_array( $stmt, MYSQLI_ASSOC))
			echo $row['data'];
			//echo json_encode($row['data']);
	break;
}

function crypto_rand_secure($min, $max)
{
    $range = $max - $min;
    if ($range < 1) return $min; // not so random...
    $log = ceil(log($range, 2));
    $bytes = (int) ($log / 8) + 1; // length in bytes
    $bits = (int) $log + 1; // length in bits
    $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
    do {
        $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
        $rnd = $rnd & $filter; // discard irrelevant bits
    } while ($rnd >= $range);
    return $min + $rnd;
}

function getToken($length)
{
	global $dbhandle;
	
	$token = "";
	do {
		
		//generate
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789";
		$max = strlen($codeAlphabet) - 1;
		for ($i=0; $i < $length; $i++) {
			$token .= $codeAlphabet[crypto_rand_secure(0, $max)];
		}
		
		//check Existence
		$result = mysqli_query($dbhandle ,"SELECT id_form FROM `wform`.`form` WHERE id_form = '" . $token . "'"); 
		$count = mysqli_num_rows($result); 
	
	} while ($count > 0);
	
    return $token;
}

?>