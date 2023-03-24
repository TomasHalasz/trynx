   //
    // Disable workers to avoid yet another cross-origin issue (workers need the URL of
    // the script to be loaded, and dynamically loading a cross-origin script does
    // not work)
    //

    //
    // Asynchronous download PDF as an ArrayBuffer
    //
//    var pdf = document.getElementById('fileDropzone');
//    pdf.onchange = function(ev) {
 //     if (file = document.getElementById('fileDropzone').files[0]) {
 function readPDF(file)
 {
        pdfjsLib.disableWorker = true;
        fileReader = new FileReader();
        fileReader.onload = function(ev) {
          console.log(ev);
          pdfjsLib.getDocument(fileReader.result).then(function getPdfHelloWorld(pdf) {
            //
            // Fetch the first page
            //
            console.log(pdf)
            pdf.getPage(1).then(function getPageHelloWorld(page) {
              var scale = window.devicePixelRatio;
	      var scale = 4;
              var viewport = page.getViewport(scale);
              var canvas = document.getElementById('pdf-canvas');
              var context = canvas.getContext('2d');
              canvas.height = viewport.height;//2*842;
              canvas.width = viewport.width; //2*595;//
	      //console.log('-----');
	      //console.log(canvas.height);
	      //console.log(canvas.width);
	      //console.log('-----');
	      context.imageSmoothingEnabled       = false;
	      context.webkitImageSmoothingEnabled = false;
	      context.mozImageSmoothingEnabled    = false;
	      context.msImageSmoothingEnabled     = false;
	      context.oImageSmoothingEnabled      = false;


              var task = page.render({canvasContext: context, viewport: viewport})
              task.promise.then(function(){
		 //uri = canvas.toDataURL('image/jpeg');
                console.log(context);
		uri = canvas.toDataURL('');
		try {

		    qrcode.debug = false;
		    qrcode.callback = function (result) {
			  console.log(result);
			  parseQR(result);
		    };
		    qrcode.decode(uri);

		}
		catch(err) {
		    console.log(err.message);
		}		

		
		
              });
            });
          }, function(error){
            console.log(error);
	    
          });
        };
	
        fileImageReader = new FileReader();
        fileImageReader.onload = function(ev) {
          console.log(ev);
	    //var img = new ImageData(fileImageReader.result,200,200);
	    var img = new Image();
            var canvas = document.getElementById('pdf-canvas');
            var context = canvas.getContext('2d');


	    //context.putImageData(img, 2*842,2*595);	      	     
	    img.src = fileImageReader.result;
	    img.onload = function ()
	    {
		canvas.height = img.height;
		canvas.width = img.width;//
		context.drawImage(img,0, 0);	
		//context.
		
		uri = canvas.toDataURL('image/jpeg');
		try {

		    qrcode.debug = false;
		    qrcode.callback = function (result) {
			  console.log(result);
			  parseQR(result);
		    };
		    qrcode.decode(uri);
		}
		catch(err) {
		    console.log(err.message);
		}		

		

	    }

	    

	    //img.src = fileImageReader.result;	    
	    
	    


          }, function(error){
            console.log(error);
	    
          }
	  

        	
	
	
	if (file.type == "application/pdf")
	{
	    fileReader.readAsArrayBuffer(file);
	}else if (file.type.indexOf("image") > -1)
	{
	    fileImageReader.readAsDataURL(file);
	   // fileImageReader.readAsArrayBuffer(file);
	    //img.src = URL.createObjectURL(file);	  
	    
	    
	}
    }
	function parseQR(strQr, force = false)
	{
	   //strQr="SPD*1.0*AM:1713*X-VS:8618*DT:20180809*CC:CZK*ACC:CZ8701000000197361390237*MSG:platba pro 2H C.S. s.r.o.*X-INV:SID%2A1.0%2AID:ZF8618%2ADD:20180809%2AAM:1713%2ATP:0%2ATD:0%2ASA:0%2AMSG:00036 predplatne aktualizaci - 36 mesicu pro HCS Fakturace 4 plus%2AON:%2AVII:CZ25398989%2AINI:25398989%2AVIR:CZ6959051748%2AINR:63872498%2ADUZP:%2ADPPD:%2ATB0:1415.70%2AT0:297.30%2AIB1:0.00%2AT1:0.00%2ATB2:0.00%2AT2:0.00%2ANTB:0.00%2AFX:1.0000%2AFXA:1%2AX-SW:Klienti.cz - 2H C.S. s.r.o.*";
	    var strInv = "";
	    var strPay = "";
	    var arrRes = [];
	    
	    if (checkQR(strQr)) {

		if (strQr.indexOf("SID*") == 0) //QR faktura
		{
		    strInv = strQr;
		    strPay = "";
		}else if (strQr.indexOf("SPD*") == 0) //QR platba+f
		{
		    n = strQr.indexOf("*X-INV");
		    strPay = strQr.substr(0,n);
		    strInv = strQr.substr(n+1).replace(/%2A/g,"*");
		}

		arrInv = strInv.split("*");
		arrPay = strPay.split("*");
		arrTotal = arrInv.concat(arrPay); 		
		
		console.log(arrInv);
		console.log(arrPay);
		
		arrTP = {0: "běžné plnění", 1: "přenesená daňová povinnost", 2: "smíšené plnění"};
		arrTD = {0: "nedaňový", 1: "opravný daňový", 2: "doklad k přijaté platbě", 3: "splátkový kalendář", 4: "platební kalendář", 5: "souhrnný daňový doklad", 9: "ostatní"};
		arrRes['strID']	    = findPart(arrTotal,"ID:").split(":")[1];	//číslo dokladu
		strDD		    = findPart(arrTotal,"DD:").split(":")[1];	//datum vystavení dokladu
		arrRes['strDD']	    = strDD.substr(6,2) + "." + strDD.substr(4,2) + "." + strDD.substr(0,4);
		arrRes['strTP']	    = findPart(arrTotal,"TP:").split(":")[1];	//typ plnění 0-běžný 1-pdp 2-smíšený
		arrRes['strTD']	    = findPart(arrTotal,"TD:").split(":")[1];	//typ dokladu 0-nedaňový 1-opravný daňový 2-doklad k přijaté platbě 3-splátkový kalendář 4-platební kalendář 5-souhrnný dańový doklad 9-ostatní 
		arrRes['strSA']	    = findPart(arrTotal,"SA:").split(":")[1];	//0-neobsahuje zúčtování záloh 1-obsahuje zúčtování záloh
		arrRes['strMSG']    = findPart(arrTotal,"MSG:").split(":")[1];	//textovy popis
		arrRes['strMSG']    = decode_utf8(arrRes['strMSG']);
		arrRes['strON']	    = findPart(arrTotal,"ON:").split(":")[1];	//číslo objednávky
		arrRes['strVII']    = findPart(arrTotal,"VII:").split(":")[1];	// DIČ výstavce
		arrRes['strINI']    = findPart(arrTotal,"INI:").split(":")[1];	//IČ výstavce
		arrRes['strVIR']    = findPart(arrTotal,"VIR:").split(":")[1];	//DIČ příjemce
		arrRes['strINR']    = findPart(arrTotal,"INR:").split(":")[1];	//IČ příjemce
		strDUZP		    = findPart(arrTotal,"DUZP:").split(":")[1];	//datum dph
		arrRes['strDUZP']   = strDUZP.substr(6,2) + "." + strDUZP.substr(4,2) + "." + strDUZP.substr(0,4);
		strDPPD		    = findPart(arrTotal,"DPPD:").split(":")[1];	//datum povinnosti přiznat daň
		arrRes['strDPPD']   = strDPPD.substr(6,2) + "." + strDPPD.substr(4,2) + "." + strDPPD.substr(0,4);		
		arrRes['strTB0']    = parseFloat(findPart(arrTotal,"TB0:").split(":")[1]);	//základ v základní sazbě
		if (isNaN(arrRes['strTB0'])) arrRes['strTB0'] = 0;
		arrRes['strT0']	    = parseFloat(findPart(arrTotal,"T0:").split(":")[1]);	//daň v základní sazbě
		if (isNaN(arrRes['strT0'])) arrRes['strT0'] = 0;
		arrRes['strTB1']    = parseFloat(findPart(arrTotal,"TB1:").split(":")[1]);	//základ v první snížené sazbě
		if (isNaN(arrRes['strTB1'])) arrRes['strTB1'] = 0;
		arrRes['strT1']	    = parseFloat(findPart(arrTotal,"T1:").split(":")[1]);	//daň v první snížené sazbě		
		if (isNaN(arrRes['strT1'])) arrRes['strT1'] = 0;
		arrRes['strTB2']    = parseFloat(findPart(arrTotal,"TB2:").split(":")[1]);	//základ v druhé snížené sazbě
		if (isNaN(arrRes['strTB2'])) arrRes['strTB2'] = 0;
		arrRes['strT2']	    = parseFloat(findPart(arrTotal,"T2:").split(":")[1]);	//daň v druhé snížené sazbě		
		if (isNaN(arrRes['strT2'])) arrRes['strT2'] = 0;
		arrRes['strNTB']    = parseFloat(findPart(arrTotal,"NTB:").split(":")[1]);	//osvobozeno včetně haléřového vyrovnání
		if (isNaN(arrRes['strNTB'])) arrRes['strNTB'] = 0;
		arrRes['strFX']	    = findPart(arrTotal,"FX:").split(":")[1];			// směnný kurz
		arrRes['strFXA']    = findPart(arrTotal,"FXA:").split(":")[1];			//počet jednotek cizí měny
		arrRes['strAM']	    = parseFloat(findPart(arrTotal,"AM:").split(":")[1]);	//fakturovaná částka
		if (isNaN(arrRes['strAM'])) arrRes['strAM'] = 0;
		arrRes['strVS']	    = findPart(arrTotal,"X-VS:").split(":")[1];			//variabilní symbol
		strDT		    = findPart(arrTotal,"DT:").split(":")[1];			//datum splatnosti
		arrRes['strDT']	    = strDT.substr(6,2) + "." + strDT.substr(4,2) + "." + strDT.substr(0,4);		
		arrRes['strCC']	    = findPart(arrTotal,"CC:").split(":")[1];			//měna
		arrRes['strMSG1']   = findPart(arrTotal,"MSG1:").split(":")[1];			//zpráva k platbě
		arrRes['strMSG1']   = decode_utf8(arrRes['strMSG1']);
		arrRes['strACC']    = findPart(arrTotal,"ACC:").split(":")[1];			//účet

		if (!force) {
			bootbox.dialog({
				message: "Byl nalezen QR kód s následujícím obsahem: <br><br>"+
					 "<table><tr><td>Typ dokladu: &nbsp; </td><td> "+arrTD[arrRes['strTD']]+"</td></tr>"+
					 "<tr><td>Typ plnění: &nbsp; </td><td> "+arrTP[arrRes['strTP']]+"</td></tr>"+
					 "<tr><td>Číslo dokladu: &nbsp; </td><td> "+arrRes['strID']+"</td></tr>"+
					 "<tr><td>DIČ dodavatele: &nbsp; </td><td> "+arrRes['strVII']+"</td></tr>"+
					 "<tr><td>IČ dodavatele: &nbsp; </td><td> "+arrRes['strINI']+"</td></tr>"+
					 "<tr><td>Částka: &nbsp; </td><td>"+arrRes['strAM']+" "+arrRes['strCC']+"</td></tr>"+
					 "<tr><td>Datum vystavení: &nbsp; </td><td> "+arrRes['strDD']+"</td></tr>"+
					 "<tr><td>Datum DPH: &nbsp; </td><td> "+arrRes['strDUZP']+"</td></tr>"+
					 "<tr><td>Datum splatnosti: &nbsp; </td><td> "+arrRes['strDT']+"</td></tr>"+
					 "<tr><td>Text: &nbsp; </td><td> "+arrRes['strMSG']+"</td></tr></table>"+
					 "<br><br>Chcete tyto údaje použít pro vyplnění karty faktury?", 
				title: "Dotaz",
				buttons: {
					 cancel: {
						label: "Zpět",
						className: "btn-primary",
						callback: function() {
							//recalcCall(curval,previous,0);
						}
					  },			    
					  success: {
						label: "Použít",
						className: "btn-success",
						callback: function() {
							//switch to card tab

							/*var a = document.createElement('a');
							finalUrl = $('[data-key=card]').prop('href');
							console.log(finalUrl);
							a.href = finalUrl;
							//a.setAttribute('data-transition', transition);
							a.setAttribute('data-history', 'false');
							_context.invoke(function(di) {
							    di.getService('page').openLink(a).then( function(){ 
								console.log('updated')
								updateCard(arrRes);

							    });
							});*/
							updateCard(arrRes);
						}
					}
				}					
			});				
		}else{
		    updateCard(arrRes);
		}
		
	    }else{
		console.log("QR invoice was not found");
	    }

	    
	}

	function checkQR(strQr){
	    
	    if (typeof strQr !== 'undefined' && (strQr.indexOf("SID*") == 0 || strQr.indexOf("SPD*") == 0)) //QR faktura nebo QR platba+f
		{
		    ret = true;
		}else{
		    ret = false;
		}
	    return ret;
	}

	function updateCard(arrRes){
	    $('[name="rinv_number"]').val(arrRes['strID']);
	    $('[name="od_number"]').val(arrRes['strON']);
	    $('[name="arv_date"]').val(arrRes['strDD']);
	    $('[name="inv_date"]').val(arrRes['strDD']);
	    $('[name="vat_date"]').val(arrRes['strDUZP']);
	    $('[name="due_date"]').val(arrRes['strDT']);
	    if (arrRes['strMSG'] == ""){
		$('[name="inv_title"]').val(arrRes['strMSG1']);
	    }else{
		$('[name="inv_title"]').val(arrRes['strMSG']);
	    }
	    $('[name="price_e2"]').val((arrRes['strTB0'])+(arrRes['strTB1'])+(arrRes['strTB2'])+(arrRes['strNTB']));
	    $('[name="price_e2_vat"]').val(arrRes['strAM']);
	    $('[name="price_base1"]').val(arrRes['strTB0']);
	    $('[name="price_vat1"]').val(arrRes['strT0']);
	    $('[name="price_base2"]').val(arrRes['strTB1']);
	    $('[name="price_vat2"]').val(arrRes['strT1']);						
	    $('[name="price_base3"]').val(arrRes['strTB2']);
	    $('[name="price_vat3"]').val(arrRes['strT2']);						
	    $('[name="price_base0"]').val(arrRes['strNTB']);
	    if (arrRes['strTP'] == "0"){
		$('[name="pdp"]').prop('checked',0);						    
	    }else{
		$('[name="pdp"]').prop('checked',1);						    
	    }
	    $('[name="currency_rate"]').val(arrRes['strFX']);
	    $('.number').autoNumeric('update');

	    //měna strCC
	    //počet jednotek cizí měny? strFXA
	    //
	    //druh dokladu strTD - 11.8.2018 - we do not solve it, because it is most accounting then anything else
	    //
	    //dodavatel strVII a strINI
	    //dodavatel IBAN a účet strACC

	    //11.08.2018 - send ajax request to get currency ID and company id
	    var objConfig = jQuery.parseJSON(jQuery('#invarrivedconfig').text());	
	    var url = objConfig.processQrDatalink;
	    console.log(url);
		$.ajax({
			url: url,
			    type: 'get',
			    context: this,
			    data: 'cc=' + arrRes['strCC'] + '&vii=' + arrRes['strVII'] + '&ini=' + arrRes['strINI'] + '&acc=' + arrRes['strACC'],
			    dataType: 'text',
			    success: function(data) {
				obj = JSON.parse(data);

				$('[name="cl_currencies_id"]').val(obj.data.currency_id).trigger('change');										    

				var newOptions = {};
				newOptions[obj.data.partners_book_id] = obj.data.company;   

				var $el = $('[name="cl_partners_book_id"]');
				$el.empty(); // remove old options
				$.each(newOptions, function(value,key) {
				  $el.append($("<option></option>")
				     .attr("value", value).text(key));
				});
				$('[name="cl_partners_book_id"]').val(obj.data.partners_book_id).trigger('change');		

				//trigger select2 to get all changes in form like user make change
				$('#frm-edit-cl_partners_book_id').trigger('select2:select');
				checkDuplicity();
				}
			    }); 								

	}
	   
	function findPart(myArray, myString) {
	    var strRet = ":";
	    $.each(myArray, function(i, val) {
		if (val.indexOf(myString) == 0) {
		    strRet = val;
		}
	    });
	    return strRet;
	}	
	

	function decode_utf8(s) {
	    try{
		s = decodeURIComponent(escape(s));
	    }catch(err) {
		console.log(err.message);
		s = "";
	    }		

	  return s;
	}
	
     $(document).off('mouseup').on('mouseup', "#qrInvoice", function (e) {	
	 bootbox.prompt({ 
		size: "large",
		className: "qrInvoice",
		inputType: "textarea",
		title: "Naskenujte QR kód.", 
		buttons: {
		    confirm: {
			    label: "Použít",
			    className: "btn-success"
			    },
		    cancel: {
			    label: "Zpět",
			    className: "btn-primary"
			    }
			},
		callback: function(result){ /* result = String containing user input if OK clicked or null if Cancel clicked */ 
				    console.log("general callback");
				    console.log(result);
				    if (result != null){
					parseQR(result,true);
				    }
				}   
			    
			      
	      });
	    var checkExist = setInterval(function() {
	       if ($('.qrInvoice').length) {
		   console.log('exits');
		  $('.qrInvoice').find('.bootbox-input-textarea').css("height","80px");;
		  clearInterval(checkExist);
	       }
	    }, 100); 	      
     });
     
     $(document).on('keyup', '.qrInvoice .bootbox-input-textarea', function (e) {
	 console.log('keyup na .bootbox-input-textarea');
	strCheck = $('.qrInvoice').find('.bootbox-input-textarea').val();
	$( "#qrinfo" ).remove();
	if (checkQR(strCheck)) {
	    console.log('nalezena Qr faktura');
	     $('.qrInvoice').find('.bootbox-input-textarea').after('<div id="qrinfo"><br><p class="bg-success"><i class="glyphicon glyphicon-ok " aria-hidden="true"></i> QR kód obsahuje fakturu.</p></div>');
	}else{
	    console.log('není Qr faktura');
	    $('.qrInvoice').find('.bootbox-input-textarea').after('<div id="qrinfo"><br><p class="bg-danger"><i class="glyphicon glyphicon-ok " aria-hidden="true"></i> QR kód neobsahuje fakturu.</p></div>');
	}
     });
     
     

//     }
//    }