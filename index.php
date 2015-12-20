<?php
	//require_once("lib\word.php");
	//print_r($_GET);
	
	//CARICA TIPOLOGIA
	$doc_type = isset($_GET['f']) ? $_GET['f'] : null;
	//$doc_type = ($doc_type != '') ? $doc_type : "co18";	//default co18
	if(is_null($doc_type)){
		//Form non richiesto: Scelta Minuta
		//echo "Reindirizzo a scelta Minuta!";
		header("Location: /wform/list.php");
		die();	
	}
	if(!file_exists("doc/".$doc_type.".docx")){
		//Form richiesto NON ESISTENTE:  
		//echo "Reindirizzo a scelta Minuta!";
		header("Location: /wform/list.php");
		die();	
	}
	
	//carica interprete word 
	require_once("lib/lib_word.php");
	$form = form_load($doc_type);
	
	
	//registra versione se non esiste (array to json in db)
	$doc_ver = 0;
	
	//CARICA MINUTA - DATI da ID
		//apri db
		$dbhandle = new mysqli("127.0.0.1", "php", "php", "wform");
		if ($dbhandle->connect_errno)
			die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		if($dbhandle === false)
			die("Servizio momentaneamente non disponibile! (missing db)"); 

		//filtro dati
		$id = isset($_GET['id']) ? mysqli_real_escape_string($dbhandle,$_GET['id']) : null;
		$id = ($id != '') ? $id : null;
		
		//prendi dati
		if(!is_null($id)){
			$sql = "SELECT * FROM `wform`.`form` WHERE id_form = '".$id."' ";
			$stmt = mysqli_query( $dbhandle, $sql);
			if ( !$stmt ){
				 echo "#Error in statement execution.\n";
				 die( print_r( mysqli_error($dbhandle), true));
			}
			$doc_var = mysqli_fetch_array( $stmt, MYSQLI_ASSOC);
			if(!isset($doc_var)) {
				//Reindirizza a nuova Minuta
				//echo "Nuova minuta!";
				header("Location: /wform/".$doc_type);
				die();	
			}
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

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>


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
                <a class="navbar-brand" href="#">Creazione Minuta per Notaio</a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            
			<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav pull-right">
                    <li>
                        <a href="#">Torna alla lista delle minute</a>
                    </li>
                    <!--
					<li>
                        <a href="#">Services</a>
                    </li>
                    <li>
                        <a href="#">Contact</a>
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
		  <div class="col-md-12">
			  <h4>Minuta <?php echo strtoupper($doc_type); ?> </h4>
		  </div>
		</div>
		<div class="row">
			<small>
			<div class="col-md-8">
			<?php if(!is_null($id)){ ?>
			 Creata alle ore <?php echo date("H:i", strtotime($doc_var['created'])); ?> del <?php echo date("d/m/Y", strtotime($doc_var['created'])); ?>
			<?php } ?> &nbsp;
			</div>
			<div class="col-md-4 text-right">
				
				<div id="alert_form_notsaved" style="display:none;" class="has-warning text-danger"> <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> Il Documento non è ancora stato salvato! </div>
				<div id="alert_form_modified" style="display:none;" class="has-warning text-warning"> <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> Sono presenti modifiche non salvate! </div>
				<div id="alert_form_saved" style="display:none;" class="has-warning text-success"> <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> Non sono presenti modifiche non salvate! </div>
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
						  <h3 class="panel-title">configurazione</h3>
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
						

				  <!-- Row start -->
				  <div class="row">
					<div class="col-md-12 col-sm-12 col-xs-12">
					  <div class="panel panel-default">
						<div class="panel-heading clearfix">
						  <i class="icon-calendar"></i>
						  <h3 class="panel-title">valori</h3>
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
		  <div class="col-md-4"><button type="button" class="btn btn-success btn-block" onClick="saveDoc()" >Salva</button></div>
		  <div class="col-md-4">&nbsp;</div>
		  <div class="col-md-4"><button type="button" class="btn btn-primary btn-block">Crea Minuta</button></div>
		</div>
		
		<div class="row" style="margin-top:20px">
		  <div class="col-md-4"><button type="button" class="btn btn-success btn-block" onClick="loadDoc()" >Carica</button></div>
		  <div class="col-md-4">&nbsp;</div>
		  <div class="col-md-4"></div>
		</div>
		
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

    <!-- Bootstrap Core JavaScript -->
	<script src="/wform/js/bootstrap.min.js" 
	integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" 
	crossorigin="anonymous"></script>

</body>

</html>