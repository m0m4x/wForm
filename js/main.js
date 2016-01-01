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
			
			//tooltip
			$(".btn-tooltip").tooltip({placement : 'top', trigger : 'manual' });
			//$(".btn-crea-tooltip").tooltip({placement : 'top' });
			
			//history
			

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
			  ui_update_updtstatus(false);
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
		
		//Check Button
		ui_update_enablecreate();
		
		//aggiorna UI
		ui_update_updtstatus(true);
		ui_update_title(form_data);
		
		//salva history
		history_set( getID(), getMOD() , ui_text('title',form_data) );
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
							//Salvataggio fallito, Errore gestito
							view_alert("fail",data,false);
						}else if(data.substring(0, 1)==">") {
							//Ok, Crea Modal per Reindirizzamento (primo salvataggio)
							viewSaveModal(data.substring(1));
						}else if(data.substring(0, 1)=="=") {
							//Ok, Salvataggio completato
							view_alert("success","Documento salvato!");
							//aggiorna UI
							ui_update_title(form_data);
							ui_update_updtstatus(true);
						}else{
							//Salvataggio fallito, Errore non gestito
							view_alert("alert","Errore non gestito: L'operazione di salvataggio ha restituito il seguente messaggio: "+data,false);
							//aggiorna UI
							ui_update_title(form_data);
							ui_update_updtstatus(true);
						}
					 },
			error: function(data){
						view_alert("fail","Errore generico:"+data.responseText,false);
					 },
			data: 'action=put&id='+rawurlencode(getID())+'&data='+rawurlencode(form_data_json)+''
		});
		
		//Check Button
		ui_update_enablecreate();
		
		//salva history
		history_set( getID(), getMOD() , ui_text('title',form_data) );
	}
	
	function viewSaveModal(id){

			// view Save modal content
			$('#commModalContent_save').show();
			$('#commModalContent_create').hide();
	
			// applica url
			$('#data-copy').each(	function(){
										this.textContent = this.textContent.replace('%url%', document.location.origin + '/' + basepath + '/' + id);
									});
			
			// redirect alla chiusura
			$('#commModal').on('hidden.bs.modal', function () {
				//redirect
				window.location.replace("/"+basepath+"/"+id);
			})
			// show Modal
			$('#commModal').modal('show');
	}
	
	/*Request Doc */
	function reqDoc(){
		
			// view Create modal content
			$('#commModalContent_save').hide();
			$('#commModalContent_create').show();
			
			// show Modal
			$('#commModal').modal('show');
			
	}
		function genWord(){
			if(getID()=='') return;
			window.location.href = "lib/req_word.php?action=gen&format=docx&id="+getID();
		}
		function genPdf(){
			return;
			if(getID()=='') return;
			window.location.href = "lib/req_word.php?action=gen&format=pdf&id="+getID();
		}
	
	/* User Interface */
	
		//titolo minuta
		function ui_update_title(form_data){
			if(typeof form_subject_var === 'undefined') return;
			$('#doc_title').html(ui_text('title',form_data));
			$('#doc_subtitle').html(ui_text('subtitle',form_data));
		}
		function ui_text(type,form_data){
			var ret;
			if(form_subject_var == '') {
				if(type=='title')
					ret = 'Minuta '+currentMOD.toUpperCase();
				else if(type=='subtitle')
					ret = '';
			} else {
				form_data.forEach(function(entry) {
					if("name" in entry){
						if(entry.name == form_subject_var){
							if(entry.value != ""){
								if(type=='title') {
									ret = entry.value;
								} else if(type=='subtitle') {
									ret = '('+currentMOD.toUpperCase()+')';
								}
							} else {
								if(type=='title') {
									ret = 'Minuta '+currentMOD.toUpperCase();
								} else if(type=='subtitle') {
									ret = '';
								}
							}
						}
					}
				});
			};
			return ret;
		}
		
		//update status 
		function ui_update_updtstatus(saved){
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
		
		//alert exit page
		function ui_alertonleave(e) {
			if( $("#alert_form_modified").is(":visible") ){
				//Alert salvataggio dei dati
				
				if(!e) e = window.event;
				//e.cancelBubble is supported by IE - this will kill the bubbling process.
				e.cancelBubble = true;
				e.returnValue = 'Attenzione, i dati non salvati potrebbero andare persi!\nSei sicuro di voler continuare?';

				//e.stopPropagation works in Firefox.
				if (e.stopPropagation) {
					e.stopPropagation();
					e.preventDefault();
				}
			}
		}
		window.onbeforeunload=ui_alertonleave; 
		
		//check enable button create
		function ui_update_enablecreate(){
			if(getID()==''){
				$('.btn-crea').prop('disabled', true);
				//$('.btn-crea-tooltip').tooltip('enable');
			}else {
				if($('input:radio:checked').length == $('.input-group-radio').size()){
					//Ok
					$('.btn-crea').prop('disabled', false);
					//$('.btn-crea-tooltip').tooltip('disable');
				} else {
					// At least one group isn't checked
					$('.btn-crea').prop('disabled', true);
					//$('.btn-crea-tooltip').tooltip('enable');
				}
			}
		}
	
	
	/* History */
	function history_set(id,mod,title) {
		if(id === '') return;
	
		// elimina storage
		//$.jStorage.deleteKey("h_id");
		//$.jStorage.deleteKey("h_data");
	
		// prendi dati da storage
		h_id = $.jStorage.get("h_id", [])
		h_data = $.jStorage.get("h_data", [])

		// elimina voce corrente
		var found = jQuery.inArray(id, h_id);
		if (found >= 0) {
			// Element was found, remove it.
			h_id.splice(found, 1);
			h_data.splice(found, 1);
		}

		var currentdate = new Date(); 
		var datetime = formatDate(currentdate);
		
		// aggiungi voce corrente
		h_id.unshift(id);
		h_data.unshift([	mod.toUpperCase(),
							title, 
							datetime,
							!$('.btn-crea').prop('disabled')
						]);
						
		// limita a 20
		h_id = h_id.slice(0,20);
		h_data= h_data.slice(0,20);
		
		// salva in storage
		$.jStorage.set("h_id", h_id)
		$.jStorage.set("h_data", h_data)
	
	}
	function formatDate(date) {
	  var hours = date.getHours();
	  var minutes = date.getMinutes();
	  hours = hours % 12;
	  hours = hours ? hours : 12; // the hour '0' should be '12'
	  hours = pad2(hours);
	  minutes = minutes < 10 ? '0'+minutes : minutes;
	  var strTime = hours + ':' + minutes;
	  return pad2(date.getDate()) + "/" + date.getMonth()+1 + "/" + date.getFullYear() + "  " + strTime;
	}
	function pad2(number) {
		 return (number < 10 ? '0' : '') + number
	}


 
 