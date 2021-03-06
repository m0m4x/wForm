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
	require_once("lib/lib_db.php");

//FILTRO 
$id = isset($_GET['id']) ? mysqli_real_escape_string($dbhandle,$_GET['id']) : null;
	if(is_null($id)) die();

//CHECK PARAMETRI
	if(is_null($id)){
		die();
	}
	$debug = isset($_GET['debug']) ? mysqli_real_escape_string($dbhandle,$_GET['debug']) : null;
		$debug = ($debug == '1') ? (bool)$debug : null;
	
//CARICA MINUTA - DATI da ID
	$sql = "SELECT * FROM `wform`.`form` WHERE id_form = '".$id."' ";
	$stmt = mysqli_query( $dbhandle, $sql);
	if ( !$stmt ){
		 echo "#Error in statement execution.\n";
		 die( print_r( mysqli_error($dbhandle), true));
	}
	$row = mysqli_fetch_array( $stmt, MYSQLI_ASSOC);
	
	//Check se è un modello
	$form_data = null;
	if(!isset($row)) {
		
		//non esiste id - carica da modello file
		if(!file_exists("doc/".$id.".docx")){
			//Form richiesto NON ESISTENTE:  
			echo "Reindirizzo a scelta Minuta!";
			header("Location: /wform/list.php");
			die();	
		}
		
		$form_data = null;
		$doc_type = $id;
		$id = null;
		
	} else {
		
		//decode data
		$form_data = json_decode($row['data']);
		$doc_type = $form_data[0]->mod;
		
	}
	
	//carica interprete word 
	require_once("lib/lib_word.php");
	$p = form_load_text($doc_type);
	$form = form_load($doc_type,$p);
	//Relazioni di validità dei campi
	$field_relations = form_relations($form);
	$field_validity = form_validity($form); 
	//$field_validity_inv = form_validityrel($form); 
	
	//registra versione se non esiste (array to json in db)
	//TODO
	$doc_ver = 0;

//DATI PER UI
	if(!is_null($id)){
		$doc_subject_var = form_type_subject_var($form_data);
		//var_dump($doc_subject);
	} else {
		$doc_subject_var = "";
	}


//DATI PER DEBUG
	if($debug){
		echo "<h1>form</h1>";
		echo var_dump($form)."<br>";
		echo "<h1>form relations</h1>";
		echo var_dump($field_relations)."<br>";
		echo "<h1>form validity </h1>";
		echo var_dump($field_validity)."<br>";
	}
	
	
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
    <script src="/wform/js/main.js"></script>
	
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
	var currentID = "<?php echo $id; ?>";
	var currentMOD = "<?php echo $doc_type; ?>";
	
	var form_subject_var = "<?php echo $doc_subject_var; ?>";
	
	var field_relations = <?php echo json_encode($field_relations); ?>;
	var field_validity = <?php echo json_encode($field_validity); ?>;
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
		  <div class="col-md-12" style="margin:10px 0px;">
				<h4 id="doc_title" style="display:inline;"> <?php if(is_null($id)){ echo "Minuta ".strtoupper($doc_type); } ?> &nbsp; </h4>
				<span id="doc_subtitle" style="display:inline;font-size: 0.8em;"> </span>
		  </div>
		</div>
		<div class="row">
			<small>
			<div class="col-md-8">
			<?php if(!is_null($id)){ ?>
			 Creata alle ore <?php echo date("H:i", strtotime($row['created'])); ?> del <?php echo date("d/m/Y", strtotime($row['created'])); ?>
			<?php } ?> &nbsp;
			</div>
			<div class="col-md-4 text-right">
				
				<div id="alert_form_notsaved" style="display:none;" class="has-warning text-info"> <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> Il Documento non è ancora stato salvato! </div>
				<div id="alert_form_modified" style="display:none;" class="has-warning text-danger"> <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> Sono presenti modifiche non salvate! </div>
				<div id="alert_form_saved" style="display:none;" class="has-warning text-success"> <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> Documento salvato! </div>
			</div>
			</small>
		</div>
		<hr style="margin-top:5px;" />
		<div class="row">
		  <div class="col-md-12">
		  
		  <!-- Form -->
		  
			<div class="bs-example">
				<form class="form-horizontal" id="mainform">

				  <!-- Row start -->
				  <div class="row">
					<div class="col-md-12 col-sm-12 col-xs-12">
					  <div class="panel panel-default">
						<div class="panel-heading clearfix">
						  <i class="icon-calendar"></i>
						  <h3 class="panel-title">Configurazione di base</h3>
						</div>
					   
						<div class="panel-body">
						  <form id="mainform" class="form-horizontal row-border" action="#">
							
							<?php form_se($form); ?>
							
						  </form>
						</div>
					  </div>
					</div>
				  </div>
				  <!-- Row end -->
				  
				<div class="row" style="margin-bottom:20px">
				  <div class="col-md-4"><button type="button" class="btn-salva btn btn-success btn-block" >Salva</button></div>
				  <div class="col-md-4">&nbsp;</div>
				  <div class="col-md-4">&nbsp;</div>
				</div>
						

				  <!-- Row start -->
				  <div class="row">
					<div class="col-md-12 col-sm-12 col-xs-12">
					  <div class="panel panel-default">
						<div class="panel-heading clearfix">
						  <i class="icon-calendar"></i>
						  <h3 class="panel-title">Tabella dei dati</h3>
						</div>
					   
						<div class="panel-body">
						  <form id="mainform" class="form-horizontal row-border" action="#">
							
							<?php form_var($form); ?>
							
						  </form>
						</div>
					  </div>
					</div>
				  </div>
				  <!-- Row end -->
				
				</form>
			</div>

		  <hr />
		  </div>
		</div>
		
		<div class="row">
		  <div class="col-md-4"><button type="button" class="btn-salva btn btn-success btn-block" >Salva</button></div>
		  <div class="col-md-4">&nbsp;</div>
		  <div class="col-md-4">
			<div class="btn-crea-tooltip" style="display:block;" data-title="Prima di creare la minuta è necessario completare tutte le scelte nella sezione configurazione!">
				<button type="button" class="btn-crea btn btn-primary btn-block" disabled="">Crea Minuta</button> &nbsp;
			</div>
		  </div>
		</div>
		
		<!--
		<div class="row" style="margin-top:20px">
		  <div class="col-md-4"><button type="button" class="btn-carica btn btn-success btn-block" >Carica</button></div>
		  <div class="col-md-4">&nbsp;</div>
		  <div class="col-md-4"></div>
		</div>
		-->
		
		<!-- Technical Data -->
		<div class="row" style="margin:20px 0px;">
		  <div class="col-md-4">
			  <!--
			  <span class="label label-default">
			  URL:<samp> 127.0.0.1/wform/ </samp>	  
			  </span>
			  -->
		  </div>
		</div>


    </div>
    <!-- /.container -->
	
	<!-- Modal -->
	<div class="modal fade" id="commModal" tabindex="-1" role="dialog" aria-labelledby="commModalLabel">
	  <div style="display:table;height: 100%;width: 100%;pointer-events:none;">
	  <div class="modal-dialog" role="document" style="display: table-cell;vertical-align: middle;pointer-events:none;">
		<div class="modal-content" style="width:inherit;height:inherit;margin: 0 auto;pointer-events:all;">
		  <div class="modal-body">
		        <div class="row">
				  <div class="col-md-2 col-md-offset-10">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				  </div>
				</div>
				<div id="commModalContent_save">
					<div class="row">
					  <div class="col-md-1"></div>
					  <div class="col-md-10">
						  <span class="glyphicon glyphicon-saved" aria-hidden="true"></span> <b>Salvataggio completato!</b>
					  </div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-1"></div>
					  <div class="col-md-10">Questa minuta sarà sempre disponibile al seguente indirizzo:</div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-1">&nbsp;</div>
					  <div class="col-md-10">
						<blockquote>
						<p>
							<span id="data-copy">%url%</span>
							<span class="pull-right">
								<button type="button" class="btn btn-copy btn-default btn-sm btn-tooltip" aria-label="Left Align" 
									style="padding-bottom: 1px;padding-top: 1px;vertical-align: text-bottom;user-select: none;"
									title="Copia indirizzo!" data-clipboard-action="copy" data-clipboard-target="#data-copy" >
									<span class="glyphicon glyphicon-link" aria-hidden="true"></span> 
								</button>
							</span>
						</p>
						</blockquote>
					  </div>
					</div>
					<div class="row">
					  <div class="col-md-1"></div>
					  <div class="col-md-10">Puoi tornare in qualsiasi momento per modificare i dati della minuta e generare un nuovo documento word/pdf.</div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-12">
						  <span class="pull-right">
							<button type="button" class="btn btn-primary" data-dismiss="modal">Prosegui</button>
						  </span>
					  </div>
					</div>
				</div>
				<div id="commModalContent_create" style="">
					<div class="row">
					  <div class="col-md-1"></div>
					  <div class="col-md-10">
						  <span class="glyphicon glyphicon-flash" aria-hidden="true"></span> <b>Esporta Minuta</b>
					  </div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-1"></div>
					  <div class="col-md-10">Scegli il formato di esportazione:</div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-2">&nbsp;</div>
					  <div class="col-md-4 text-center"><a class="link-word" href="javascript:genWord()"><img src="img/docx-icon.png" alt="Word"></a></div>
					  <div class="col-md-4 text-center"><a class="link-pdf disabled" href="javascript:genPdf()"><img src="img/pdf-icon-greyed.png" alt="Pdf"></a></div>
					  <div class="col-md-2">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-1">&nbsp;</div>
					  <div class="col-md-11">Potrai sempre tornare alla pagina precedente per modificare i dati della minuta e generare un nuovo documento.</div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-12">
						  <span class="pull-right">
							<button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
						  </span>
					  </div>
					</div>
				</div>
				<div id="commModalContent_create_alert" style="">
					<div class="row">
					  <div class="col-md-1"></div>
					  <div class="col-md-10">
						  <span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> <b>Attenzione!</b>
					  </div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-10 col-md-offset-1">Sono presenti modifiche non salvate. Vuoi salvarle prima di procedere con la creazione del documento?</div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-2">&nbsp;</div>
					  <div class="col-md-4 text-center">
								<button type="button" class="btn btn-default" aria-label="Left Align" 
									title="Salva le modifiche" onclick="javascript:reqDoc('save')">
									Salva le modifiche!
								</button>
					  </div>
					  <div class="col-md-4 text-center">
								<button type="button" class="btn btn-default" aria-label="Left Align" 
									title="Ignora le modifiche" onclick="javascript:reqDoc('ignore')">
									Ignora le modifiche
								</button>
					  </div>
					  <div class="col-md-2">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-12">&nbsp;</div>
					</div>
					<div class="row">
					  <div class="col-md-12">
						  <span class="pull-right">
							<button type="button" class="btn btn-default" data-dismiss="modal">Annulla</button>
						  </span>
					  </div>
					</div>

				</div>
		  </div>
		</div>
	  </div>
	  </div>
	</div>

    <!-- Bootstrap Core JavaScript -->
	<script src="/wform/js/bootstrap.min.js" 
	integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" 
	crossorigin="anonymous"></script>

</body>

</html>