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