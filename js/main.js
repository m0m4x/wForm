/*!
 * 
 * 
 * 
 */	
	var basepath = "wform";
 
 
	/* Avvio */
	
		$(document).ready( function() { 
			
			//carica Documento
			if(getID()!=""){ loadDoc(getID()); }
			
			//abilita Clipboard.js
			var clipboard = new Clipboard('.btn-copy');
				clipboard.on('success', function(e) {
											e.clearSelection();
											$(".btn-tooltip").attr('data-original-title','Link copiato negli appunti!');
											$(".btn-tooltip").tooltip('show');
											setTimeout(function () {
												$(".btn-tooltip").tooltip('hide');
											}, 1000);
										});
										
			$(".btn-tooltip").tooltip({placement : 'top', trigger : 'manual' });

		}); 
		
		//OnError
		window.onerror = function(msg, url, line)
		{
			window.alert("Errore: msg " + msg + " url:" + url + " line:" + line);
		  /*
		  var req = new XMLHttpRequest();
		  var params = "msg=" + encodeURIComponent(msg) + '&amp;url=' + encodeURIComponent(url) + "&amp;line=" + line;
		  req.open("POST", "/scripts/logerror.php");
		  req.send(params);
		  */
		};

 
 	/* Funzioni */
	  
		//Testo
	  
		function rawurlencode(str) {
		  str = (str + '').toString();
		  return encodeURIComponent(str)
			.replace(/!/g, '%21')
			.replace(/'/g, '%27')
			.replace(/\(/g, '%28')
			.replace(/\)/g, '%29')
			.replace(/\*/g, '%2A');
		}
		
		function getMOD() {
			return currentMOD;
			/*
			var href = document.location.href;
			var parm = href.substring(href.indexOf(basepath)+6).split("/");
			if(parm.length>1){
				return parm[0];
			} else {
				return "";
			}
			*/
		}
		
		function getID() {
			return currentID;
			/*
			var href = document.location.href;
			var parm = href.substring(href.indexOf(basepath)+6).split("/");
			if(parm.length>1){
				return parm[1];
			} else {
				//alert("n");
				return "";
			}
			*/
		}
		
		
		//Notifiche
		
		function ui_update_changes_notify(saved){
			if(getID()==""){
				//	alert_form_notsaved
				$("#alert_form_modified").hide();
				$("#alert_form_saved").hide();
				$("#alert_form_notsaved").show();
			}else if(!saved){
				//	alert_form_modified
				$("#alert_form_notsaved").hide();
				$("#alert_form_saved").hide();
				$("#alert_form_modified").show();
			} else{
				//	alert_form_saved
				$("#alert_form_notsaved").hide();
				$("#alert_form_modified").hide();
				$("#alert_form_saved").show();
			}
		}
		
		//Alert
		
		function view_alert(type,message,delay){
			if(typeof delay === 'undefined') delay = 2000;
			var n = noty({
				layout: 'topRight',
				theme: 'relax',
				type: type,
				text: message,
				timeout: delay,
				animation: {
					open: {height: 'toggle'}, // jQuery animate function property object
					close: {height: 'toggle'}, // jQuery animate function property object
					easing: 'swing', // easing
					speed: 300 // opening & closing animation speed
				}
			});
		}
    
	/* Modifiche a Form Bootstrap */
	
		$(document).ready( function() { 
			
			//Eventi
			$('.btn-salva').click(saveDoc);
			$('.btn-carica').click(loadDoc);
			$('.btn-crea').click(reqDoc);
			
			//notifica cambiamento
			$("form :input").change(function() {
			  ui_update_changes_notify(false);
			});
			
			//dropdown testo in input
			$('.dropdown-menu li a').click(function(e) {
				//window.alert($(this).attr('input'));
				$("input#"+$(this).attr('input')).val($(this).text());
			});
			
		}); 
	
	/* Carica */
	
	function loadDoc(){
		var form_data;
		
		$.ajax({
			url: "/"+basepath+"/lib/req_db.php", 
			cache: false,
			context: document.body, 
			success: function(data){
						if(data!=""){
							if(data.substring(0, 1)=="#"){
									view_alert("fail","Errore dal server: "+data,false);
							}else {
									form_data = jQuery.parseJSON( data );
									loadData(form_data);
							}
						}
					 },
			error: function(data){
						view_alert("fail","Errore generico:"+data.responseText,false);
					 },
			data: 'action=get&id='+rawurlencode(getID())+''
		});
	}
	
	function loadData(form_data){
		//svuota
		$(':input','form#mainform').not(':button, :submit, :reset, :hidden, :checkbox, :radio')
							 .val('');
		$(':input','form#mainform').not(':button, :submit, :reset, :hidden, :text')
							 .removeAttr('checked')
							 .removeAttr('selected');
		
		//view_alert("info",form_data,false);
		
		//carica
		for (var i in form_data) {
			var obj = form_data[i];
			//alert(obj['name']);
			
			//alert($(':input[name="'+obj['name']+'"]').attr('type'));
			switch($('input[name="'+obj['name']+'"]').attr('type')) {
				case 'radio':
				case 'checkbox':
					$('input[name="'+obj['name']+'"][value='+obj['value']+']').prop('checked', true);
					break;
				default:
					$('input[name="'+obj['name']+'"]').val(obj['value']);
			} 
			
		}
		
		//notify icon
		ui_update_changes_notify(true);
		
		//Check Button
		enableBtnCreate();
		
		//aggiorna UI
		ui_update_subject(form_data);
	}
	
	
	/* Salva */
	function saveDoc(){
		var form_data = $("form#mainform").serializeArray();
		
		//Add System data
		form_data.unshift({id:getID(), mod:getMOD()});
		
		var form_data_json = JSON.stringify(form_data);	//console.log(form_data_json);
		$.ajax({
			url: "/"+basepath+"/lib/req_db.php",
			cache: false,
			context: document.body, 
			success: function(data){
						if(data.substring(0, 1)=="#"){
							view_alert("fail",data,false);
						}else if(data.substring(0, 1)==">") {
							//Crea Modal Salvataggio riuscito
							viewSaveModal(data.substring(1));
						}else if(data.substring(0, 1)=="=") {
							view_alert("success","Documento salvato!");
							//aggiorna UI
							ui_update_subject(form_data);
							ui_update_changes_notify(true);
						}else{
							view_alert("alert","L'operazione di salvataggio ha restituito il seguente messaggio: "+data,false);
							ui_update_changes_notify(true);
						}
					 },
			error: function(data){
						view_alert("fail","Errore generico:"+data.responseText,false);
					 },
			data: 'action=put&id='+rawurlencode(getID())+'&data='+rawurlencode(form_data_json)+''
		});
		
		//Check Button
		enableBtnCreate();
	}
	
	function viewSaveModal(id){

			// crea Modal
	
			// applica url
			$('#data-copy').each(	function(){
										this.textContent = this.textContent.replace('%url%', document.location.origin + '/' + basepath + '/' + id);
									});
			
			// redirect alla chiusura
			$('#commModal').on('hidden.bs.modal', function () {
				//redirect
				window.location.replace("/"+basepath+"/"+id);
			})
			// apri Modal
			$('#commModal').modal('show');
	}
	
	/* User Interface */
	
	function ui_update_subject(form_data){
		if(typeof form_subject_var === 'undefined') return;
		if(form_subject_var == '') {
			$('#doc_title').html('Minuta '+currentMOD.toUpperCase());
			$('#doc_subtitle').html('');
		} else {
			form_data.forEach(function(entry) {
				if("name" in entry){
					if(entry.name == form_subject_var){
						if(entry.value != ""){
							$('#doc_title').html('Minuta '+entry.value.toUpperCase());
							$('#doc_subtitle').html('('+currentMOD.toUpperCase()+')');
						} else {
							$('#doc_title').html('Minuta '+currentMOD.toUpperCase());
							$('#doc_subtitle').html('');
						}
					}
				}
			});
		};
		
		
	}
	
	
	/* Crea Minuta */ 
	
	function enableBtnCreate(){
		if($('input:radio:checked').length == $('.input-group-radio').size()){
			//Ok
			$('.btn-crea').prop('disabled', false);
		} else {
			// At least one group isn't checked
			$('.btn-crea').prop('disabled', true);
		}
	}
	
	function reqDoc(){
		
	}
	
	
	


 
 