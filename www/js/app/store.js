/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 22.6.2016 - 9:35:28
 * 
 */



    //
	    //Tomas Halasz
	    //příjemka na sklad
		//$(document).on('change','#cm_header_show', function(e) {
	    $(document).on('blur', "#frm-storeListgrid-editLine-price_in, #frm-storeListgrid-editLine-s_in", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				if ($('#frm-storeListgrid-editLine-vat').length>0)
					vat = $('#frm-storeListgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;
				price_in	 = $('#frm-storeListgrid-editLine-price_in').val().split(' ').join('');
				price_in_vat = Math.round((price_in * (1+(vat/100)))*100)/100;		    
				$('#frm-storeListgrid-editLine-price_in_vat').val(price_in_vat);
				$('#frm-storeListgrid-editLine-price_in_vat').autoNumeric('update')

				var quantity = $('#frm-storeListgrid-editLine-s_in').val().split(' ').join('');
				$('#frm-storeListgrid-editLine-price_e2').val(price_in * quantity);
				$('#frm-storeListgrid-editLine-price_e2').autoNumeric('update')
				$('#frm-storeListgrid-editLine-price_e2_vat').val(price_in_vat * quantity);
				$('#frm-storeListgrid-editLine-price_e2_vat').autoNumeric('update');
			lGridCalcProfitStore();
			//}
	    });	    
	    $(document).on('blur', "#frm-storeListgrid-editLine-price_in_vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				if ($('#frm-storeListgrid-editLine-vat').length>0)
					vat = $('#frm-storeListgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;
				price_in_vat = $('#frm-storeListgrid-editLine-price_in_vat').val().split(' ').join('');
				price_in	 = Math.round((price_in_vat / (1+(vat/100)))*100)/100;		    
				$('#frm-storeListgrid-editLine-price_in').val(price_in);
				$('#frm-storeListgrid-editLine-price_in').autoNumeric('update')

				var quantity = $('#frm-storeListgrid-editLine-s_in').val().split(' ').join('');
				$('#frm-storeListgrid-editLine-price_e2').val(price_in * quantity);
				$('#frm-storeListgrid-editLine-price_e2').autoNumeric('update')
				$('#frm-storeListgrid-editLine-price_e2_vat').val(price_in_vat * quantity);
				$('#frm-storeListgrid-editLine-price_e2_vat').autoNumeric('update');
			lGridCalcProfitStore();
			//}
	    });



	    //14.06.2017 - příjemka z výdejky - volba datumu přijetí a změna skladu
	    $(document).on('click', '#nhCreate_income', function (e) {
		urlString  = $(this).data('url-get-stores');
		urlString2 = $(this).data('url-make-income');
		$.nette.ajax({
			url: urlString,
			type: 'get',
			context: this,
			dataType: 'json',
			success: function(data) {
			    console.log(data);
			    bootbox.prompt({
				locale: "cs",
				title: "Vyberte sklad, na který má být výdejka naskladněna",
				inputType: 'select',
				inputOptions: data,
				callback: function (result) {
				    console.log(result);
				    if (result != null)
				    {
					//console.log(urlString2);
					$.ajax({
						url: urlString2,
						data: 'cl_storage_id='+result,
						type: 'get',
						context: this,
						dataType: 'json',
						success: function(data) {
						//    console.log(data.url);
						    setTimeout(function(){
							window.location.href = data.url;
							}, 200);						    
						}
					});						
				    }
				}
			    });
			    
			    
			    }
			});		

	    });
	    //11.06.2017 - uložení datumu příjemky při jeho změně, protože správný datum potřebujeme při práci s položkami příjemky
	    //pro výpočty VAP cen
	    //také se provede v případě změny datumu update VAP všech položek na skladě
	    $(document).on('change', '#frm-edit-doc_date', function (e) {
		urlString  = $(this).data('url-change_doc_date');
		docDate = $(this).val();
		$.ajax({
			url: urlString,
			data: 'doc_date='+docDate,
			type: 'get',
			context: this,
			dataType: 'json',
			success: function(data) {
			    }
			});		
	    });
	    
	    //11.06.2017 - při změně výchozího skladu musíme tuto změnu uložit hned, aby vkládané položky pracovaly se správným skladem
	    $(document).on('change', '#frm-edit-cl_storage_id', function (e) {
		urlString  = $(this).data('url-change_storage');
		storageId = $(this).val();
		$.ajax({
			url: urlString,
			data: 'cl_storage_id='+storageId,
			type: 'get',
			context: this,
			dataType: 'json',
			success: function(data) {
				if (data.result == 'FALSE'){
					bootbox.alert("Výchozí sklad musí být vybrán.");
					e.preventDefault();
				}
			    }
			});
			e.stopPropagation();
	    });
	    

	    //
	    //konec příjemky na sklad
	    
	    //
	    //Tomas Halasz
	    //výdejka ze skladu
	    
	    //zobrazeni poctu kusů skladem
	    function updateQuantity()
	    {
			if ($("#frm-storeListgrid-editLine-cl_storage_id").length > 0)
			{
				//$(".customUrl").data("url-getquantity")
				if($(".customUrl").data('url-getquantity') !== undefined){
					url = $(".customUrl").data('url-getquantity');
					cl_storage_id = $("#frm-storeListgrid-editLine-cl_storage_id").val();
					id = $("#frm-storeListgrid-editLine input[type=hidden][name=id]").val();
					$.ajax({
						url: url,
						data: 'cl_storage_id='+cl_storage_id+'&cl_store_move_id='+id,
						type: 'get',
						context: this,
						dataType: 'json',
						success: function(data) {
							//$("#loading").hide();
							//alert(data.quantity);
							//var retData = $.parseJSON(data);
							var varQuant = data.quantity;
							var varPrice = data.price_s;
							$("#frm-storeListgrid-editLine-cl_store__quantity").val(varQuant);
							$('#frm-storeListgrid-editLine-cl_store__quantity').autoNumeric('update');
							$("#frm-storeListgrid-editLine-price_s").val(varPrice);
							$('#frm-storeListgrid-editLine-price_s').autoNumeric('update');														    
						    }
						}); 			
					}		    

			}
	    }
	    
	    $(document).on('change', "#frm-storeListgrid-editLine-cl_storage_id", function (e) {
			updateQuantity();
	    });

		//updateQuantity();
	    //nastaveni 1 jako default mnozstvi k vydeji
	    
	    //vpocet prodejni ceny pri zmene nakupni ceny nebo pri zmene zisku
	    $(document).on('blur', "#frm-storeListgrid-editLine-profit, #frm-storeListgrid-editLine-price_s", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var profit = $('#frm-storeListgrid-editLine-profit').val().split(' ').join('').replace(',','.');
				var price_s = $('#frm-storeListgrid-editLine-price_s').val().split(' ').join('').replace(',','.');
				if (price_s > 0 && profit >0)
				{
					price_e = (price_s * (1+(profit/100)));
					$('#frm-storeListgrid-editLine-price_e').val(price_e);
					$('#frm-storeListgrid-editLine-price_e').autoNumeric('update');
					lGridCalcStore();
					lGridCalcProfitStore()
				}
			//}
	    });	    
	    
	    $(document).on('blur', "#frm-storeListgrid-editLine-price_s", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalcProfitStore();
			lGridCalcProfitStore()
			//}
	    });	    	    

	    //vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
	    $(document).on('blur', "#frm-storeListgrid-editLine-s_out, #frm-storeListgrid-editLine-price_e, #frm-storeListgrid-editLine-discount, #frm-storeListgrid-editLine-vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalcStore();
				lGridCalcProfitStore();
			//}
	    });




//vypocet celkove ceny za polozky a celkove ceny s DPH
	    //vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
	    function lGridCalcStore(){
			if ($('#frm-storeListgrid-editLine-s_out').length>0)
		    	var quantity = $('#frm-storeListgrid-editLine-s_out').val().split(' ').join('').replace(',','.');

			if ($('#frm-storeListgrid-editLine-s_in').length>0)
				var quantity = $('#frm-storeListgrid-editLine-s_in').val().split(' ').join('').replace(',','.');

		    var price_e = $('#frm-storeListgrid-editLine-price_e').val().split(' ').join('').replace(',','.');
		    var discount = $('#frm-storeListgrid-editLine-discount').val().split(' ').join('').replace(',','.');
		    price_e2 = quantity * (price_e * (1-(discount/100)));
		    $('#frm-storeListgrid-editLine-price_e2').val(price_e2);
		    $('#frm-storeListgrid-editLine-price_e2').autoNumeric('update')
		    if ($('#frm-storeListgrid-editLine-vat').length>0)
			vat = $('#frm-storeListgrid-editLine-vat').val().split(' ').join('').replace(',','.');
		    else
			vat = 0;
		    
		    var price_e2 = $('#frm-storeListgrid-editLine-price_e2').val().split(' ').join('').replace(',','.');
		    var price_e2_vat = Math.round((price_e2 * (1+(vat/100)))*100)/100;
		    $('#frm-storeListgrid-editLine-price_e2_vat').val(price_e2_vat);
		    $('#frm-storeListgrid-editLine-price_e2_vat').autoNumeric('update');
	    }

		//vypocet zisku
	    function lGridCalcProfitStore(){
	    	if ( $('#frm-storeListgrid-editLine-price_in').length > 0)
	    	{
	    		price_s = $('#frm-storeListgrid-editLine-price_in').val().split(' ').join('').replace(',','.');
			}else{
				price_s = $('#frm-storeListgrid-editLine-price_s').val().split(' ').join('').replace(',','.');

			}
		    if (price_s > 0)
		    {
		    	if ($('#frm-storeListgrid-editLine-cl_pricelist__price').length > 0){
					price_e = $('#frm-storeListgrid-editLine-cl_pricelist__price').val().split(' ').join('').replace(',','.');
				}else{
					price_e = $('#frm-storeListgrid-editLine-price_e').val().split(' ').join('').replace(',','.');
				}

				profit = ((price_e/price_s)-1)*100;
				if ($('#frm-storeListgrid-editLine-profit').length>0) {
					$('#frm-storeListgrid-editLine-profit').val(profit);
					$('#frm-storeListgrid-editLine-profit').autoNumeric('update');
				}
		    }		    		
	    }
	    //vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
	    $(document).on('blur', "#frm-storeListgrid-editLine-price_e2_vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				if ($('#frm-storeListgrid-editLine-s_out').length>0)
					var quantity = $('#frm-storeListgrid-editLine-s_out').val().split(' ').join('');

				if ($('#frm-storeListgrid-editLine-s_in').length>0)
					var quantity = $('#frm-storeListgrid-editLine-s_in').val().split(' ').join('');

				var price_e2_vat = $('#frm-storeListgrid-editLine-price_e2_vat').val().split(' ').join('');
				if ($('#frm-storeListgrid-editLine-vat').length>0)
					vat = $('#frm-storeListgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				if ($('#frm-storeListgrid-editLine-discount').length>0)
					var discount = $('#frm-storeListgrid-editLine-discount').val().split(' ').join('');
				else
					var discount = 0;

				price_e2 = price_e2_vat/(1+(vat/100));
				price_e = (price_e2/(1-(discount/100))) / quantity;
				if ($('#frm-storeListgrid-editLine-price_e2').length>0) {
					$('#frm-storeListgrid-editLine-price_e2').val(price_e2);
					$('#frm-storeListgrid-editLine-price_e2').autoNumeric('update')
				}
				if ($('#frm-storeListgrid-editLine-price_e').length>0) {
					$('#frm-storeListgrid-editLine-price_e').val(price_e);
					$('#frm-storeListgrid-editLine-price_e').autoNumeric('update')
				}
				if ($('#frm-storeListgrid-editLine-price_in').length>0) {
					$('#frm-storeListgrid-editLine-price_in').val(price_e);
					$('#frm-storeListgrid-editLine-price_in').autoNumeric('update')
				}
				if ($('#frm-storeListgrid-editLine-price_in_vat').length>0) {
					$('#frm-storeListgrid-editLine-price_in_vat').val(price_e2_vat / quantity);
					$('#frm-storeListgrid-editLine-price_in_vat').autoNumeric('update')
				}

				lGridCalcProfitStore();
			//}
	    });	    
	    //vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH	    
	    $(document).on('blur', "#frm-storeListgrid-editLine-price_e2", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				if ($('#frm-storeListgrid-editLine-s_out').length>0)
					var quantity = $('#frm-storeListgrid-editLine-s_out').val().split(' ').join('');

				if ($('#frm-storeListgrid-editLine-s_in').length>0)
					var quantity = $('#frm-storeListgrid-editLine-s_in').val().split(' ').join('');

				var price_e2 = $('#frm-storeListgrid-editLine-price_e2').val().split(' ').join('');
				if ($('#frm-storeListgrid-editLine-vat').length>0)
					vat = $('#frm-storeListgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				if ($('#frm-storeListgrid-editLine-discount').length>0)
					var discount = $('#frm-storeListgrid-editLine-discount').val().split(' ').join('');
				else
					var discount = 0;

				var price_e2_vat = Math.round((price_e2 * (1+(vat/100)))*100)/100;		    
				price_e = (price_e2/(1-(discount/100))) / quantity;
				if ($('#frm-storeListgrid-editLine-price_e2_vat').length>0) {
					$('#frm-storeListgrid-editLine-price_e2_vat').val(price_e2_vat);
					$('#frm-storeListgrid-editLine-price_e2_vat').autoNumeric('update')
				}

				if ($('#frm-storeListgrid-editLine-price_e').length>0) {
					$('#frm-storeListgrid-editLine-price_e').val(price_e);
					$('#frm-storeListgrid-editLine-price_e').autoNumeric('update')
				}

				if ($('#frm-storeListgrid-editLine-price_in_vat').length>0) {
					$('#frm-storeListgrid-editLine-price_in_vat').val(price_e2_vat / quantity);
					$('#frm-storeListgrid-editLine-price_in_vat').autoNumeric('update')
				}
				if ($('#frm-storeListgrid-editLine-price_in').length>0) {
					$('#frm-storeListgrid-editLine-price_in').val(price_e);
					$('#frm-storeListgrid-editLine-price_in').autoNumeric('update')
				}

				lGridCalcProfitStore();
			//}
	    });	    	    
	    
	    
	    
	    //
	    //konec výdejky ze skladu
	    
	    
	    
//14.02.2019 - create income doc from outcome

$(document).on('click','#outToIncome', function (e) {
    
    var items=[];
    $('.checkstoreListgridSelect:checked').each(function( index ) {
	items[index] = $(this).data('id');
      });
    var itemsToCheck = $('.checkstoreListgridSelect:not(:checked)').length;
    
    if ( itemsToCheck > 0  && items.length === 0 )
    {
	bootbox.alert("Nevybrali jste žádné položky. Nejprve vyberte položky výdejky, které chcete naskladnit.");						
    }else{
	outToIncome(items);
    }
//	   e.preventDefault();
    e.stopImmediatePropagation();	
});



function outToIncome(items)
{
    var storageVal = $('#frm-storages-cl_storage_id').val();
    if ( storageVal === "")
    {
	bootbox.alert("Nevybrali jste sklad. Pro úspěšné naskladnění musí být vybrán!");    
    }else{
    	var objConfig = jQuery.parseJSON(jQuery('#storeconfig').text());	
	var url = objConfig.createIncome;     
	var bscId = objConfig.bscId;
	finalUrl = url + '&bscId='+bscId+'&cl_storage_id='+storageVal+'&dataItems='+JSON.stringify(items);
	$.ajax({
	    url: finalUrl,
	    success: function(payload) {
		//console.log(payload.tax);
		setTimeout(function(){
			var ab = document.createElement('a');
			finalUrl = objConfig.redirectStore;
			ab.href = finalUrl + "edit/" + payload.id;
			//alert(ab.href);
			//a.setAttribute('data-transition', transition);
			ab.setAttribute('data-history', 'true');
			ab.setAttribute('data-ajax', 'false');
			_context.invoke(function(di) {
			    di.getService('page').openLink(ab).then( function(){ 

			    });
			});		    
		    }, 150);			    
	    }
	});	    
	$('#createIncomeModal').modal('hide');    
    }
}

$(document).on('click','#InToOutgoing', function (e) {
    var items=[];
    $('.checkstoreListgridSelect:checked').each(function( index ) {
	items[index] = $(this).data('id');
      });
    var itemsToCheck = $('.checkstoreListgridSelect:not(:checked)').length;
    
    if ( itemsToCheck > 0  && items.length === 0 )
    {
	bootbox.alert("Nevybrali jste žádné položky. Nejprve vyberte položky příjemky, které chcete vydat.");						
    }else{
	inToOutgoing(items);
    }
//	   e.preventDefault();
    e.stopImmediatePropagation();	
});

function inToOutgoing(items)
{
    var partnerVal = $('#frm-partners-cl_partners_book_id').val();
    if ( partnerVal === "")
    {
	bootbox.alert("Nevybrali jste odběratele. Pro úspěšné naskladnění musí být vybrán!");    
    }else{    
	var objConfig = jQuery.parseJSON(jQuery('#storeconfig').text());	
	var url = objConfig.createOutgoing;     
	var bscId = objConfig.bscId;
	finalUrl = url + '&bscId='+bscId+'&cl_partners_book_id='+partnerVal+'&dataItems='+JSON.stringify(items);
	$.ajax({
	    url: finalUrl,
	    success: function(payload) {
		//console.log(payload.tax);
		setTimeout(function(){
			var ab = document.createElement('a');
			finalUrl = objConfig.redirectStore;
			console.log(finalUrl);
			ab.href = finalUrl + "edit/" + payload.id;
			//alert(ab.href);
			 //a.setAttribute('data-transition', transition);
			ab.setAttribute('data-history', 'true');
			ab.setAttribute('data-ajax', 'false');
			_context.invoke(function(di) {
			    di.getService('page').openLink(ab).then( function(){
			    });
			});		    
		    }, 150);			    
	    }
	});	    
	$('#createOutgoingModal').modal('hide');    
    }
}



	   