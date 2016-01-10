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
			$("form :input").change(checkformChange);
			
			//dropdown testo in input
			$('.dropdown-menu li a').on('click', function(e) {
				$("input#"+$(this).attr('input')).val($(this).text());
				//checkformChange nel contesto di input
				var dupfunction = $.proxy( checkformChange, $("input#"+$(this).attr('input')) );
				dupfunction();
			});
			
		}); 
		
	/* Modifiche al form */
	
	function checkformChange() {
		
		console.log("Cambiato: "+$(this).attr('id'));
		
		//Check se abilita o nascondi variabile
		ui_show_var($(this));
		
		//Aggiurna status documento
		ui_update_updtstatus(false);
	}
	
	function ui_show_var(dom){

		console.log('check richiesto da dom '+dom.attr('id'));
		
		//prendi dati
		var type = dom.attr('type');
			//Check se è input snoppa
			if (type == "input") return;
			//Check se è presente in *
				//non necessario?
		var name = dom.attr('name');
		var id = dom.attr('id');
		var dom_val = get_dom_val(dom);
		//view_alert("info",dom_val);
		
		//parsa relazioni
			//per ogni relazione controlla condizioni e mostra/nascondi di conseguenza
		var todisable = [];
		var toenable = [];
		if( name in field_relations ) {
			console.log("Analizzo "+name);
			//per ogni campo relazionato
			for (var related_key in field_relations[name]) {
				console.log(" Campo relazionato: "+field_relations[name][related_key]);
				var related = field_relations[name][related_key];
				var to_hide = true;
				// related è l'id del campo relazionato a quello modificato
				// prendo tutte le condizioni e controllo i valori
				for(var condition in field_validity[related]){
					// prendo il valore del campo condizione
					var c_dom = get_dom_byid(condition);
					if(!c_dom.length){ /*valore del dom non trovato*/ }
					var c_dom_val = get_dom_val(c_dom);
					//debug
					console.log("                                      condizione:"+condition);
					console.log("             valore attuale del campo condizione:"+c_dom_val);
					//per ogni valore condizione
					for(var value_key in field_validity[related][condition]){
						var value = field_validity[related][condition][value_key];
						console.log("                         valore della condizione:"+value);
						//attenzione a valori che iniziano con !
						if(c_dom_val==value){
							to_hide = false;
							break; //basta che 1 sia vera
						}
					}
				}	
				//aggiungi a hide o show
				if(to_hide){
					todisable.push(related);
					console.log('da disabilitare '+related);
				} else {
					toenable.push(related);
					console.log('da abilitare '+related);
				}
			}
		}
		return;
		
		
		//parsa relazioni
		if( name in form_validity_rel ) {
			//parsa relazioni
			var todisable = [];
			var toenable = [];
			//per ogni valore confronta con valore attuale e decidi se abilitare e disabilitare
			for (var choiche_val in form_validity_rel[name]) {
				console.log(">>"+choiche_val+" "+ dom_val + " ");
				var c_bool = true;
				var c_val = choiche_val;
				if(choiche_val.substring(0, 1)=="!"){
					c_bool = false;
					c_val = choiche_val.substring(1);
				}
				if((c_val==dom_val) === c_bool){
					//attuale uguale a questa
					//abilita
					for (i = 0; i < form_validity_rel[name][choiche_val].length; i++) {
						//console.log(' +' + form_validity_rel[name][choiche_val][i] );
						//if(toenable.indexOf(form_validity_rel[name][choiche_val][i])==-1)
							toenable.push(form_validity_rel[name][choiche_val][i]);
							console.log('da abilitare '+form_validity_rel[name][choiche_val][i]);
					}
				} else {
					//attuale diverso da questa
					//disabilita nascondi ogni id
					for (i = 0; i < form_validity_rel[name][choiche_val].length; i++) {
						//if(toenable.indexOf(form_validity_rel[name][choiche_val][i])==-1){
						//	if(todisable.indexOf(form_validity_rel[name][choiche_val][i])==-1){
								todisable.push(form_validity_rel[name][choiche_val][i]);
								console.log('da disabilitare '+form_validity_rel[name][choiche_val][i]);
						//	}
						//}
					}
				}
			}
			return;
			//Tra quelli da disabilitare controlla che non siano necessari da altre scelte
			//var i;
			//for (i = 0; i < todisable.length; ++i) {
			//	var id_disable = todisable[i];
			//	console.log(" >"+id_disable);
				for (var input_id in form_validity_rel) {
					//console.log(" >"+input_id);
					if(input_id!="*"){
						for (var input_val in form_validity_rel[input_id]) {
							//console.log(" "+input_val);
							//per ogni valore attivato da questa variabile e valore
							for (var input_rel in form_validity_rel[input_id][input_val]) {
								//è un oggetto key => id
								var todisable_index = todisable.indexOf(form_validity_rel[input_id][input_val][input_rel].toString());
								//console.log("  "+form_validity_rel[input_id][input_val][input_rel] + " "+ test);
								if(todisable_index != -1){
									console.log("  "+todisable[todisable_index]+" è necessario al campo "+input_id+" se il suo valore è"+input_val);
									var c_dom = get_dom_byid(input_id);
									if(c_dom.length){
										var c_val = get_dom_val(c_dom);
										if(c_val==input_val){
											todisable.splice(todisable_index, 1);
										}
									}
								}
							}
						}
					}
				} 
			//}
			/*
			for (var choice_id in form_validity_rel) {
				if(choice_id!="*"){
					var c_dom = get_dom_byid(choice_id);
					if(c_dom.length){
						var c_val = get_dom_val(c_dom);
						//console.log(">"+choice_id+">"+c_val);
						if(c_val in form_validity_rel[choice_id]) {
							//per ogni id necessario 
							for (i = 0; i < form_validity_rel[choice_id][c_val].length; ++i) {
								console.log("necessario "+form_validity_rel[choice_id][c_val][i]+" da "+choice_id);
								//toglilo da to disable
								var todisable_index = todisable.indexOf(form_validity_rel[choice_id][c_val][i]);
								if(todisable_index!=-1){
									todisable.splice(todisable_index, 1);
								} else {
									console.log("non trovato! "+form_validity_rel[choice_id][c_val][i] );
								}
							}
						}
					}
				}
			}
			*/
			//Nascondi
			
			var i;
			for (i = 0; i < todisable.length; ++i) {
				//view_alert("info","disabilitato "+todisable[i]);
				var dom = get_dom_byid(todisable[i]);
					if(dom.length){ dom.closest('div.form-group').hide(); } else { 
						//console.log('disabilita dom '+todisable[i]+' non trovato'); 
					}
			}
			//Abilita
			var i;
			for (i = 0; i < toenable.length; ++i) {
				//view_alert("info","disabilitato "+todisable[i]);
				var dom = get_dom_byid(toenable[i]);
					if(dom.length){ dom.closest('div.form-group').show(); }  else { 
						//console.log('abilita dom '+toenable[i]+' non trovato');
					}
			}
			//abilita input per valore uguale
			/*if(dom_val in form_validity_rel[name]) {
				var i;
				for (i = 0; i < form_validity_rel[name][dom_val].length; ++i) {
					//view_alert("info","abilitato "+form_validity_rel[name][dom_val][i]);
					var dom = get_dom_byid(form_validity_rel[name][dom_val][i]);
						if(dom.length){ dom.closest('div.form-group').show(); }
				}
			}*/
		}
		
	}
	
	function get_dom_byid(id){
		var dom;
		//cambia dal tipo
		if ( $( '#'+id ).length ) {
			//è checkbox
			dom = $('#'+id);
		} else {
			//è radio
			dom = $('input[name='+id+']:checked');
		}
		if(!(dom.length)){
			console.log(id+"non trovato!");
			return [];
		}
		//console.log(id+"trovato "+dom.length);
		return dom;
	}
	function get_dom_val(dom){
		var val = "";
		//console.log("getVal " + dom.attr('id') + dom.attr('type'));
		switch(dom.attr('type')) {
			case 'checkbox':
				val = 0;
				if($('#'+dom.attr('id')+':checked').prop('checked')) val = 1;
				break;
			case 'radio':
			default:
				val = dom.val();
				break;
		}
		//console.log("getVal" + val);
		return val;
	}
	
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
			var dom ;
			switch($('input[name="'+obj['name']+'"]').attr('type')) {
				case 'radio':
				case 'checkbox':
					dom = $('input[name="'+obj['name']+'"][value='+obj['value']+']');
					dom.prop('checked', true);
					break;
				default:
					dom = $('input[name="'+obj['name']+'"]');
					dom.val(obj['value']);
			}
			
			//Relazioni di validità
			ui_show_var(dom);
			
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
	function saveDoc(callback_function){
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
						//callback functiona after save
						if(typeof callback_function == 'function') {  callback_function(); } else { /*window.alert(" callback non definito");*/  }
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
			$('#commModalContent_create_alert').hide();
	
			// applica url
			$('#data-copy').each(	function(){
										this.textContent = this.textContent.replace('%url%', document.location.origin + '/' + basepath + '/' + id);
									});
			
			// redirect alla chiusura
			$('#commModal').on('hidden.bs.modal', function () {
				
				//Redirect
				ui_alert_disable = true;
				window.location.replace("/"+basepath+"/"+id);
			})
			// show Modal
			$('#commModal').modal('show');
	}
	
	/*Request Doc */
	function reqDoc(handlemod){
			handlemod = handlemod || "";
			
			//Save before requestDoc
			if(handlemod == "save"){
					saveDoc(reqDoc);
					return;
			}
			
			//View Dialogs
			if(ui_alertonleave_haschg() && handlemod != "ignore"){
				// view Alert
				$('#commModalContent_save').hide();
				$('#commModalContent_create').hide();
				$('#commModalContent_create_alert').show();
			}else {
				// view Create
				$('#commModalContent_save').hide();
				$('#commModalContent_create').show();
				$('#commModalContent_create_alert').hide();
			}

			// show Modal
			$('#commModal').modal('show');
			
	}
		function genWord(){
			if(getID()=='') return;
			//Redirect
			ui_alert_disable = true;
			window.location.href = "lib/req_word.php?action=gen&format=docx&id="+getID();
		}
		function genPdf(){
			return; //non disponibile
			//if(getID()=='') return;
			////Redirect
			//ui_alert_disable = true;
			//window.location.href = "lib/req_word.php?action=gen&format=pdf&id="+getID();
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
		var ui_alert_disable = false;
		function ui_alertonleave(e) {
			if (ui_alert_disable) return;
			if( ui_alertonleave_haschg() ){
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
		function ui_alertonleave_haschg(){
			return $("#alert_form_modified").is(":visible") || $("#alert_form_notsaved").is(":visible");
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


 
 