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
 * along with wForm.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


//LIBRERIE
	//carica db
	require_once("lib/lib_word.php");

$dir = "doc/";
$dh  = opendir($dir);
while (false !== ($filename = readdir($dh))) {
	if (	   $filename != "." 
			&& $filename != ".." 
			&& pathinfo($dir.$filename , PATHINFO_EXTENSION) == 'docx'
			&& substr($filename, 0,1) != "~"
			)
	{
		$doc_name = pathinfo($dir.$filename , PATHINFO_FILENAME); 
		$doc_info = docx_load_info($doc_name);
		$doc_title = array_key_exists("dc:title",$doc_info) ? $doc_info['dc:title'] : $filename ;
		$doc_keywords = array_key_exists("cp:keywords",$doc_info) ? $doc_info['cp:keywords'] : "" ;
		$doc_description = array_key_exists("dc:description",$doc_info) ? $doc_info['dc:description'] : "" ;
		$doc_created = date("d/m/Y H:i:s",strtotime($doc_info['dcterms:created']));
		$doc_modified = date("d/m/Y H:i:s",strtotime($doc_info['dcterms:modified']));
		$doc_creator = date("d/m/Y H:i:s",strtotime($doc_info['dc:creator']));
		$doc_lastModifiedBy = date("d/m/Y H:i:s",strtotime($doc_info['cp:lastModifiedBy']));
		$docs[] = Array( "doc_name" => $doc_name, 
					"doc_title" => $doc_title,
					"doc_keywords" => $doc_keywords,
					"doc_description" => $doc_description,
					"doc_created" => $doc_created,
					"doc_modified" => $doc_modified,
					"doc_creator" => $doc_creator,
					"doc_lastModifiedBy" => $doc_lastModifiedBy,
					);

	}
}
sort($docs);
		
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>ChiantiBanca Minute</title>

	<!-- dev wrapper for IE -->
	<script src="/wform/js/dev.js"></script>
	
	<!-- jQuery Version 1.11.3 -->
    <script src="/wform/js/jquery.min.js"></script>
	<script src="/wform/js/noty/packaged/jquery.noty.packaged.min.js"></script>
	
	<!-- Load Bootstrap 3.3.6 -->
	<link rel="stylesheet" href="/wform/css/bootstrap.min.css" 
	integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" 
	crossorigin="anonymous">
	<link rel="stylesheet" href="/wform/css/bootstrap-theme.min.css" 
	integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" 
	crossorigin="anonymous">
	
    <!-- Main CSS -->
    <link rel="stylesheet" href="/wform/css/style.css" 
	
	<!-- Main Js -->

	
	<!-- Additional Js -->
	<script src="/wform/js/clipboard/clipboard.min.js"></script>
	
	<script src="/wform/js/jstorage/json2.js"></script>
	<script src="/wform/js/jstorage/jstorage.min.js"></script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<script type="text/javascript">
		$(document).ready( function() {
			
			//carica Ultimi documenti
			h_id = $.jStorage.get("h_id", "no_history")
			h_data = $.jStorage.get("h_data", "no_history")
			if(h_id=="no_history"){
				//Nessun documento
				$('#table_history tbody').html(	'<tr>'+
												'	<td width="10%">&nbsp;</td>'+
												'	<td><small><em>Nessuna minuta trovata.</em></small></td>'+
												'	<td></td>'+
												'	<td></td>'+
												'</tr>');
			}else{
				//Carica ultimi documenti
				var index;
				for (index = 0; index < h_id.length; ++index) {
					/*console.log(h_id[index] + ' ' + h_data[index].length +
					' ' + h_data[index][0]+
					' ' + h_data[index][1]+ 
					' ' + h_data[index][2]+
					' ' + h_data[index][3]+
					' ' + h_data[index][4]
					);*/
					$('#table_history tbody').append(	'<tr data-href="'+h_id[index]+'">'+
														'	<td width="1%"></td>'+
														'	<td width="10%" ><small><em>/'+h_id[index]+'</em></small></td>'+
														'	<td width="54%">'+h_data[index][1]+'</td>'+
														'	<td width="10%"><small>'+h_data[index][0]+'</small></td>'+
														'	<td width="20%"><small><em>'+h_data[index][2]+'</em></small></td>'+
														'	<td width="5%">'+(h_data[index][3]? "<a href='lib/req_word.php?action=gen&format=docx&id="+h_id[index]+"'><span class='glyphicon glyphicon-download-alt' aria-hidden='true'></span></a>" : "" )+'</td>'+
														'</tr>');
				}
			}
			
			//Tabelle con pointer
			$(function(){
				$('.table tr[data-href]').each(function(){
					$(this).css('cursor','pointer').hover(
						function(){ 
							$(this).addClass('active'); 
						},  
						function(){ 
							$(this).removeClass('active'); 
						}).mousedown( function(e){
							switch(e.which)
							{
								case 1:
									//left Click									
									document.location = $(this).attr('data-href'); 
									return true;
								break;
								case 2:
									//middle Click
									//$( "#table_history tbody" ).append( "<a id='tmp_a' href='"+$(this).attr('data-href')+"' target='_blank' style='display:none;'> test </a>" );
									//document.getElementById("tmp_a").click();
									window.open(	$(this).attr('data-href'),
													'_blank');
									return true;
								break;
								case 3:
									//right Click
									return false;
								break;
							}
						}
					);
				});
			});
			
		});
</script>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="list.php">Creazione Minuta per Notaio</a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            
			<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav pull-right">
                    <!--
					<li>
                        <a href="">Link 1</a>
                    </li>
					<li>
                        <a href="">Link 2</a>
                    </li>
					-->
                </ul>
            </div>
			
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <!-- Page Content -->
	<div class="container-fluid" style="margin:0px 10%">
		
		<div class="row">
		  <div class="col-md-12" style="margin:40px 0px 0px 0px ;">
				<h5 style="display:inline;"> <span class="glyphicon glyphicon-file" aria-hidden="true"></span> &nbsp; Crea nuova Minuta </h5>
				<span  style="display:inline;font-size: 0.8em;"> </span>
				<hr style="margin:5px 0px 0px 0px;" />
		  </div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<table id="table_doc" class="table table-hover"> 
					<!-- <thead> 
						<tr> 
							<th>#</th> 
							<th>First Name</th> 
							<th>Last Name</th> 
							<th>Username</th> 
						</tr>
					</thead> -->
					<tbody> 
					<?php
					foreach($docs as $doc){
						?>
						<tr data-href="<?php echo $doc['doc_name']; ?>"> 
							<td width="1%">&nbsp;</td>
							<td><b><?php echo strtoupper($doc['doc_name']); ?></b></td>
							<td><?php echo $doc['doc_title']; ?> 
								<br><small><em><?php echo $doc['doc_description']; ?></em></small>
							</td>
							<td><em><?php echo $doc['doc_keywords']; ?></em></td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
			</div>
		</div>
		

		
		<div class="row">
		  <div class="col-md-12" style="margin:40px 0px 0px 0px ;">
				<h5 style="display:inline;"> <span class="glyphicon glyphicon-pushpin" aria-hidden="true"></span> &nbsp; Le tue ultime minute </h5>
				<span  style="display:inline;font-size: 0.8em;"> </span>
				<hr style="margin:5px 0px 0px 0px;" />
		  </div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<table id="table_history" class="table"> 
					<tbody> 
						
					</tbody>
				</table>
			</div>
		</div>
		



    </div>
    <!-- /.container -->

    <!-- Bootstrap Core JavaScript -->
	<script src="/wform/js/bootstrap.min.js" 
	integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" 
	crossorigin="anonymous"></script>

</body>

</html>