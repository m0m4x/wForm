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
		
		function copyTextToClipboard(text) {
		  var textArea = document.createElement("textarea");
		  // Place in top-left corner of screen regardless of scroll position.
		  textArea.style.position = 'fixed';
		  textArea.style.top = 0;
		  textArea.style.left = 0;
		  // Ensure it has a small width and height. Setting to 1px / 1em
		  // doesn't work as this gives a negative w/h on some browsers.
		  textArea.style.width = '2em';
		  textArea.style.height = '2em';
		  // We don't need padding, reducing the size if it does flash render.
		  textArea.style.padding = 0;
		  // Clean up any borders.
		  textArea.style.border = 'none';
		  textArea.style.outline = 'none';
		  textArea.style.boxShadow = 'none';
		  // Avoid flash of white box if rendered for any reason.
		  textArea.style.background = 'transparent';
		  textArea.value = text;
		  document.body.appendChild(textArea);
		  textArea.select();
		  try {
			var successful = document.execCommand('copy');
			var msg = successful ? 'successful' : 'unsuccessful';
			console.log('Copying text command was ' + msg);
		  } catch (err) {
			console.log('Oops, unable to copy');
		  }
		  document.body.removeChild(textArea);
		}
		
		//Notifiche
		
		function form_change_notify(saved){
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
			  form_change_notify(false);
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
								view_alert("fail","fail:"+data,false);
							}else {
									view_alert("fail","data:"+data+" "+getID(),false);
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
		form_change_notify(true);
		
		//Check Button
		enableBtnCreate();
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
							//redirect
							window.location.replace("/"+basepath+"/"+data.substring(1));
						}else if(data.substring(0, 1)=="=") {
							view_alert("success","Operazione eseguita correttamente! ",false);
						}else{
							view_alert("alert","L'operazione ha restituito il seguente messaggio: "+data,false);
							form_change_notify(true);
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
	
	
	


 
 