<?php

	function open_db(){
		//apri db
		$dbhandle = new mysqli("127.0.0.1", "php", "php", "wform");
		if ($dbhandle->connect_errno)
			die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		if($dbhandle === false)
			die("Servizio momentaneamente non disponibile! (missing db)");
		return $dbhandle;
	}


	$dbhandle = open_db();
?>