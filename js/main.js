/*!
 * 
 * 
 * 
 */	
 
	/* Avvio */
	
		$(document).ready( function() { 
			//carica Documento
			if(currentID()!=""){ loadDoc(currentID()); }
			
			
			
		}); 
 
 	/* Funzioni */
	  
		function rawurlencode(str) {
		  str = (str + '').toString();
		  return encodeURIComponent(str)
			.replace(/!/g, '%21')
			.replace(/'/g, '%27')
			.replace(/\(/g, '%28')
			.replace(/\)/g, '%29')
			.replace(/\*/g, '%2A');
		}
		
		function currentID() {
			var href = document.location.href;
			return href.substr(href.lastIndexOf('/') + 1);
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
    
	/* Notifica Modifiche */
		$(document).ready( function() {
			
			$("form :input").change(function() {
			  form_change_notify(false);
			});
			
							
		});
		
		function form_change_notify(saved){
			if(currentID()==""){
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
		
	/* Alert */ 
	
	function view_alert(type,message,delay){
		var n = noty({
			layout: 'topRight',
			theme: 'relax',
			type: 'success',
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
	
	/* Carica */
	
	function loadDoc(){
		var form_data;
		
		$.ajax({
			url: "lib/db.php", 
			context: document.body, 
			success: function(data){
						if(data.substring(0, 1)=="#"){
							view_alert("fail",data,false);
						}else {
							if(data!=""){
								form_data = jQuery.parseJSON( data );
								loadData(form_data);
							}
						}
					 },
			error: function(data){
						view_alert("fail","Errore generico:"+data.responseText,false);
					 },
			data: 'action=get&id='+rawurlencode(currentID())+''
		});
	}
	
	function loadData(form_data){
		//svuota
		$(':input','form#mainform').not(':button, :submit, :reset, :hidden, :checkbox, :radio')
							 .val('');
		$(':input','form#mainform').not(':button, :submit, :reset, :hidden, :text')
							 .removeAttr('checked')
							 .removeAttr('selected');
							 
		//carica
		for (var i in form_data) {
			var obj = form_data[i];
			
			switch($('input[name="'+obj['name']+'"]').attr('type')) {
				case 'radio':
				case 'checkbox':
					$('input[name='+obj['name']+'][value='+obj['value']+']').prop('checked', true);
					break;
				default:
					$('input[name="'+obj['name']+'"]').val(obj['value']);
			} 
			
		}
		
		//notify icon
		form_change_notify(true);
	}
	
	
	/* Salva */
	function saveDoc(){
		var form_data = $("form#mainform").serializeArray();
		var form_data_json = JSON.stringify(form_data); 
		console.log(form_data_json);
		
		$.ajax({
			url: "lib/db.php", 
			context: document.body, 
			success: function(data){
						if(data.substring(0, 1)=="#"){
							view_alert("fail",data,false);
						}else if(data.substring(0, 1)==">") {
							//redirect
							window.location.replace(data.substring(1));
						}else{
							view_alert("success","Modifiche salvate correttamente!",5000);
							form_change_notify(true);
						}
					 },
			error: function(data){
						view_alert("fail","Errore generico:"+data.responseText,false);
					 },
			data: 'action=put&id='+rawurlencode(currentID())+'&data='+rawurlencode(form_data_json)+''
		});
		
	}
	
	
	
	/* Crea Minuta */ 
	
	
	
	
  
		// Attiva-Disattiva
		function verify(id,approved){
			$("#loading_img").show();

			$.ajax({
			url: "cambiaRowpub.php", 
			context: document.body, 
			success: function(data){
						var delay = 5000;
						if(data.indexOf('ER:')>-1){
							$("#result").css('background-color','#DC143C');
							$("#result").html(data);
							delay = 60000;
						}else{
							$("#result").css('background-color','lightgreen');
							$("#result").html(data);
							//Reimposta UI
							$("#row"+id).removeClass("verify");
							$("#row"+id).addClass("active");
							$("#row"+id+" td#comandi span.cmd").remove();
							$("#row"+id+" td.modificabile span.prec").remove();
							$("#loading_img").hide();
						}
						$("#result").show();
						$("#loading_img").hide();
						$("#result").delay(delay).fadeOut('5000');
					 },
			error: function(data){
						$('#result').html("<h1>Errore del server</h1> "+data.responseText);
					 },
			data: 'id='+rawurlencode(id)+'&ver='+rawurlencode(approved)
			});
			
		}
		
		// Altri Campi
		var originalcontent = "";
		var chgnoteinuse = false;
		
		// Singola cella
			function changeContent(tablecell)
			{
			  if(chgnoteinuse == false){
				chgnoteinuse = true;
				originalcontent=tablecell.innerHTML;
				//tablecell.innerHTML = "<INPUT type=text name=newname onBlur=\"javascript:submitNewName(this);\" onKeyPress=\"javascript:changeContentKeyPress(event,this)\"  value=\""+tablecell.innerHTML+"\">";
				tablecell.innerHTML = "<textarea type=text name=newname onBlur=\"javascript:submitNewName(this);\" onKeyPress=\"javascript:changeContentKeyPress(event,this)\">"+tablecell.innerHTML+"</textarea>";
				tablecell.firstChild.focus();
			  }
			}
			function changeContentKeyPress(e,textfield){
				var key=e.keyCode || e.which;
				if (key==27){
				  textfield.parentNode.innerHTML=originalcontent;
				  originalcontent = "";
				  chgnoteinuse = false;
				}else if (key==13){
				  submitNewName(textfield);
				  originalcontent = "";
				  chgnoteinuse = false;
				}
			}
			function submitNewName(textfield)
			{
			  //alert('cambia.php?id='+rawurlencode(textfield.parentNode.parentNode.getAttribute("id").replace("row",""))+'&campo='+rawurlencode(textfield.parentNode.getAttribute("name"))+'&val='+rawurlencode(textfield.value)+'');
				if (textfield.value != originalcontent) {
				  $("#loading_img").show();
				  
				  $.ajax({
					url: "cambia.php", 
					context: document.body, 
					success: function(data){
								var delay = 20000;
								if(data.indexOf('ER:')>-1){
								  $("#result").css('background-color','#DC143C');
								  $("#result").html(data);
								  textfield.parentNode.innerHTML=originalcontent;
								  delay = 60000;
								}else{
								  $("#result").css('background-color','lightgreen');
								  $("#result").html(data);
								  textfield.parentNode.innerHTML=textfield.value;
								}
								$("#result").show();
								$("#loading_img").hide();
								$("#result").delay(delay).fadeOut('5000');
							 },
					error: function(data){
								$('#result').html("<h1>Errore del server</h1> "+data.responseText);
							 },
					data: 'id='+rawurlencode(textfield.parentNode.parentNode.getAttribute("id").replace("row",""))+'&campo='+rawurlencode(textfield.parentNode.getAttribute("name"))+'&val='+rawurlencode(textfield.value)+''
				  });
				  
				}else{
				textfield.parentNode.innerHTML=originalcontent;
				}
				chgnoteinuse = false;
			}
		
		// Singola Riga
			var usingnode = "";
			var nodes_original = new Array();
			
			function changeRowContent(tablecell)
			{
			  //Form Compose
			  if(chgnoteinuse == false){
				chgnoteinuse = true;
				usingnode = tablecell.parentNode;
				originalcontent=usingnode.innerHTML;
				var nodes = usingnode.childNodes;
				for(i=0; i<nodes.length; i+=1) {
					var node = nodes[i];
					if (node.nodeName == "TD") {
						if (haveClass(node,'id')) {
							nodes_original[i] = node.innerHTML;
							node.innerHTML = node.innerHTML + "<form id='changerow'>";
						}
						if (haveClass(node,'modificabile')) {
							nodes_original[i] = node.innerHTML;
							//Prendi testo (no figli)
							var text = "";
							//text = node.textContent;
							for (var s = 0; s < node.childNodes.length; s++)
							if (node.childNodes[s].nodeType === 3)
								text += node.childNodes[s].textContent || node.childNodes[s].innerHTML || node.childNodes[s].nodeValue || node.childNodes[s].wholeText || node.childNodes[s].data;
							//TextArea
							node.innerHTML = "<textarea type=text form=\"changerow\" name=\""+node.getAttribute("name")+"\" onKeyPress=\"javascript:changeRowContentKeyPress(event,this)\" maxlenght=\"100\" style=\"overflow:hidden;resize:none;\" >"+text+"</textarea>";
						}
						if (haveClass(node,'comandi')) {
							nodes_original[i] = node.innerHTML;
							node.innerHTML = "</form><span class='cmd save' onClick=\"submitNewRow()\">salva</span><span class='cmd canc' onClick=\"completeChangeRow(true)\">annulla</span>";
						}
						//Apply Autocomplete
						applyAutoComplete();
					}
				}
				
			  }
			}
			
			
			
			function haveClass(element, className){
				return element.className && new RegExp("(^|\\s)" + className + "(\\s|$)").test(element.className);
			}
			
			
			
			function changeRowContentKeyPress(e,textfield){
				var key=e.keyCode || e.which;
				if (key==27){
				  completeChangeRow(true)
				}else if (key==13){
				  submitNewRow();
				}
			}
			function submitNewRow()
			{
				$("#loading_img").show();
				
				//Parametri
				var frm = $( "#changerow" );
				var frm_data = $( "#changerow" ).serialize();
					if (frm_data == "") {
					var frm_container = frm.parents("tr");
					var frm_txt = frm_container.find('textarea');
					frm_txt.each(function(){
						frm_data += "&"+$(this).serialize();
					});
				}

				$("#loading_img").show();
				
				//Richiesta
				$.ajax({
				url: "cambiaRow.php", 
				context: document.body, 
				success: function(data){
							var delay = 20000;
							if(data.indexOf('ER:')>-1){
							  $("#result").css('background-color','#DC143C');
							  $("#result").html(data);
							  completeChangeRow(true);
							  delay = 60000;
							}else{
							  $("#result").css('background-color','lightgreen');
							  $("#result").html(data);
							  completeChangeRow();
							}
							$("#result").show();
							$("#loading_img").hide();
							$("#result").delay(delay).fadeOut('5000');
						 },
				error: function(data){
							$('#result').html("<h1>Errore del server</h1> "+data.responseText);
						 },
				data: 'id='+rawurlencode(usingnode.getAttribute("id").replace("row","")) + '&' + $( "form#changerow" ).serialize()
				});
				
			}
			
			function completeChangeRow(reset){
				var nodes = usingnode.childNodes;
				for(i=0; i<nodes.length; i+=1) {
					var node = nodes[i];
					if (node.nodeName == "TD") {
						if (haveClass(node,'id')) {
							node.innerHTML = nodes_original[i];
						}
						if (haveClass(node,'modificabile')) {
							if(reset){
								node.innerHTML = nodes_original[i];
							}else{
								node.innerHTML = node.getElementsByTagName("TEXTAREA")[0].value;
							}
						}
						if (haveClass(node,'comandi')) {
							node.innerHTML = nodes_original[i];
						}
					}
				}
				usingnode = "";
				originalcontent = "";
				nodes_original = new Array();
				chgnoteinuse = false;
			}
		
		 function applyAutoComplete() {
			 
			 monkeyPatchAutocomplete();
			 
			$( "textarea[name=ufficio]" ).autocomplete({
			  source: "showhint.php",
			  minLength: 2,
			  select: function( event, ui ) {
				/*
				do nothing on selection
				$('#warncontainer').append( ui.item ?
				  "Selected: " + ui.item.value + " aka " + ui.item.id :
				  "Nothing selected, input was " + this.value );*/
			  }
			});
 
			// This patches the autocomplete render so that
			// matching items have the match portion highlighted.
			function monkeyPatchAutocomplete() {

			  // Don't really need to save the old fn,
			  // but I could chain if I wanted to
			  var oldFn = $.ui.autocomplete.prototype._renderItem;

			  $.ui.autocomplete.prototype._renderItem = function( ul, item) {
				  var re = new RegExp("\\b" + this.term, "i") ;
				  var t = item.label.replace(re,"<span style='font-weight:bold;color:Blue;'>" + this.term + "</span>");
				  return $( "<li></li>" )
					  .data( "item.autocomplete", item )
					  .append( "<a>" + t + "</a>" )
					  .appendTo( ul );
			  };
			}

		  };
		  
		  function clearSelection(){
			if (window.getSelection) {
			  if (window.getSelection().empty) {  // Chrome
				window.getSelection().empty();
			  } else if (window.getSelection().removeAllRanges) {  // Firefox
				window.getSelection().removeAllRanges();
			  }
			} else if (document.selection) {  // IE?
			  document.selection.empty();
			}
		  }
	
	
	/* Stampa */	  
		  
	function preparePrint(){
		load_data(10000,function () {
			if (Browser.isIE){
					//IE _ funziona?
					var OLECMDID = 7;
					/* OLECMDID values:
					* 6 - print
					* 7 - print preview
					* 1 - open window
					* 4 - Save As
					*/
					var PROMPT = 1; // 2 DONTPROMPTUSER
					var WebBrowser = '<OBJECT ID="WebBrowser1" WIDTH=0 HEIGHT=0 CLASSID="CLSID:8856F961-340A-11D0-A96B-00C04FD705A2"></OBJECT>';
					document.body.insertAdjacentHTML('beforeEnd', WebBrowser);
					WebBrowser1.ExecWB(OLECMDID, PROMPT);
					WebBrowser1.outerHTML = "";
			} else {
				window.print();
			}
		});
	}

 
 