/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 21.6.2016 - 14:31:06
 * sale 
 */



	//vypocty v karte prodeje
	function initSale()
	{


            
	}
	
	/*$(document).on('click','#printbtn', function (e){
	    var strUrl = $(this).data('url-redir');
	    //console.log(strUrl);
	    setTimeout(function(){
		window.location.href = strUrl;
		}, 2000);
	    
	});*/

	
	    //Tomas Halasz
	    //prodejka a jeji obsah
	    //

	    //vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
	    $(document).on('blur', "#frm-salelistgrid-editLine-quantity, #frm-salelistgrid-editLine-price_e, #frm-salelistgrid-editLine-discount, #frm-salelistgrid-editLine-vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalcSale();
			//}
	    });
	    //vypocet celkove ceny za polozky a celkove ceny s DPH
	    //vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
	    function lGridCalcSale(){
			var price_e_type = $('input[name=price_e_type]').val();
		    var quantity = parseFloat($('#frm-salelistgrid-editLine-quantity').val().split(' ').join('').replace(',','.'));
		    var price_e = parseFloat($('#frm-salelistgrid-editLine-price_e').val().split(' ').join('').replace(',','.'));
		    var discount = parseFloat($('#frm-salelistgrid-editLine-discount').val().split(' ').join('').replace(',','.'));


		    if ($('#frm-salelistgrid-editLine-vat').length>0)
				vat = parseFloat($('#frm-salelistgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
		    else
				vat = 0;
		    
			if (price_e_type == 0)
			{
				var price_e2 = quantity * (price_e * (1-(discount/100)));
				var calc_vat = (price_e2 * vat / 100);
				var price_e2_vat = Math.round((price_e2 + calc_vat ) * 100 ) / 100;			
			}else{
				var price_e2_vat = quantity * (price_e * (1-(discount/100)));
				var calc_vat = (price_e2_vat / ( 1 + ( vat / 100 )) * ( vat / 100 ));								
				var price_e2 = Math.round((price_e2_vat - calc_vat) * 100) / 100;
			}
			$('#frm-salelistgrid-editLine-price_e2').val(price_e2);
			$('#frm-salelistgrid-editLine-price_e2').autoNumeric('update');							
			$('#frm-salelistgrid-editLine-price_e2_vat').val(price_e2_vat);
			$('#frm-salelistgrid-editLine-price_e2_vat').autoNumeric('update');				
			
	    }

	    //vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
	     $(document).on('blur', "#frm-salelistgrid-editLine-price_e2_vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();				
				var quantity = parseFloat($('#frm-salelistgrid-editLine-quantity').val().split(' ').join(''));
				var price_e2_vat = parseFloat($('#frm-salelistgrid-editLine-price_e2_vat').val().split(' ').join(''));
				if ($('#frm-salelistgrid-editLine-vat').length > 0)
					vat = $('#frm-salelistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				var discount = parseFloat($('#frm-salelistgrid-editLine-discount').val().split(' ').join(''));
				var calc_vat = (price_e2_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );
				var price_e2 = price_e2_vat - calc_vat;
				$('#frm-salelistgrid-editLine-price_e2').val(price_e2);
				$('#frm-salelistgrid-editLine-price_e2').autoNumeric('update');
				if (price_e_type == 0)
				{				
					price_e = (price_e2/(1-(discount/100))) / quantity;					

				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
				$('#frm-salelistgrid-editLine-price_e').val(price_e);
				$('#frm-salelistgrid-editLine-price_e').autoNumeric('update');				
				//lGridCalcProfit();
			//}
	    });	    
	    //vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH	    
	     $(document).on('blur', "#frm-salelistgrid-editLine-price_e2", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();								
				var quantity = parseFloat($('#frm-salelistgrid-editLine-quantity').val().split(' ').join(''));
				var price_e2 = parseFloat($('#frm-salelistgrid-editLine-price_e2').val().split(' ').join(''));
				if ($('#frm-salelistgrid-editLine-vat').length > 0)
					vat = $('#frm-salelistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				var discount = parseFloat($('#frm-salelistgrid-editLine-discount').val().split(' ').join(''));
				var calc_vat = (price_e2 * ( vat / 100 ));		    
				var price_e2_vat = Math.round((price_e2 + calc_vat) * 100 ) / 100;		    
				$('#frm-salelistgrid-editLine-price_e2_vat').val(price_e2_vat);
				$('#frm-salelistgrid-editLine-price_e2_vat').autoNumeric('update');
				if (price_e_type == 0)
				{								
					price_e = (price_e2/(1-(discount/100))) / quantity;					
				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
				$('#frm-salelistgrid-editLine-price_e').val(price_e);
				$('#frm-salelistgrid-editLine-price_e').autoNumeric('update');				
				//lGridCalcProfit();
			//}
	    });	    	    
	    //
	    //konec faktury
	    //	
	var clicked = null;
	//$(document).on('mousedown', "#discount_abs, #discount, #cash_rec, #customer_name, #inlineRadio1, #inlineRadio2", function (e) {
	//	clicked = e.target;
	//});
	/*$(document).on('focus', "#discount_abs, #discount, #cash_rec, #customer_name, #inlineRadio1, #inlineRadio2", function (e) {
		clicked = e.target;
	});*/
	//$(document).on('keyup', "#discount_abs, #discount, #cash_rec, #customer_name, #inlineRadio1, #inlineRadio2", function (e) {
		//clicked = null;
	//});

    $(document).on('blur', "#discount, #customer_name", function (e) {
			if ($("#discount").val() > 100 ) {
				bootbox.dialog({
					message: "Zadali jste příliš vysokou slevu.",
					title: "Varování",
					buttons: {
						cancel: {
							label: "Zpět",
							className: "btn-primary",
							callback: function () {

							}
						}
					}

				});
				//return false;
			}else {
				saleChange();
			}
			 //  e.preventDefault();
			//e.stopImmediatePropagation();
    	});

	$(document).on('blur', "#discount_abs", function (e) {
		if ($("#discount_abs").val() > 25000 ) {
			bootbox.dialog({
				message: "Zadali jste příliš vysokou slevu.",
				title: "Varování",
				buttons: {
					cancel: {
						label: "Zpět",
						className: "btn-primary",
						callback: function () {

						}
					}
				}

			});
			//return false;
		}else {
			saleChange();
		}
		//  e.preventDefault();
		//e.stopImmediatePropagation();
	});

    $(document).on('blur', '#cash_rec', function (e) {
    		tmpCashRec = parseFloat($('#cash_rec').val().split(' ').join(''));
			tmpTotal = parseFloat($('#total_sum').data('value'));
    		$('#cash_back').val(tmpCashRec - tmpTotal).autoNumeric('update');
			saleChange();
	});

	$(document).on('click', "#inlineRadio1, #inlineRadio2", function () {
			saleChange();
		});

    function saleChange(parentObj)
	{
		if ($("#discount").val() > 100 || $("#discount_abs").val() > 25000 )
		{
			return false;
		}else {


			var obj = {
				discount: $("#discount").val(),
				discount_abs: $("#discount_abs").val(),
				payment_cash: $("#inlineRadio1").prop('checked'),
				payment_card: $("#inlineRadio2").prop('checked'),
				customer: $("#customer_name").val(),
				cash_rec: parseFloat($("#cash_rec").val().split(' ').join(''))
			};
			var data = JSON.stringify(obj);
			//console.log(obj);
			//console.log(data);
			var objConfig = jQuery.parseJSON(jQuery('#saleconfig').text());
			var url = objConfig.saleUpdate;

			var a = document.createElement('a');
			finalUrl = url + '&data=' + data;
			a.href = finalUrl;

			//a.setAttribute('data-transition', transition);
			a.setAttribute('data-history', 'false');
			//a.setAttribute('data-scroll-to', '.openedEditLine');
			//console.log(parentObj);
			_context.invoke(function (di) {
				di.getService('page').openLink(a)
					.then(function (payload) {
						// spustí se vzápětí po "update" události služby Page
						//console.log('nitro update');
						//console.log(parentObj);
						//console.log(clicked);
						//alert('ted');
						if (clicked == null) {
							//$('#' + parentObj.id).parent().parent().next().find('input').focus();
						} else {
							$('#' + clicked.id).focus();
						}
						//.next(':input').focus();
					}, function (err) {
						// chyba
						console.log('nitro error');
					});
				//di.getService('page').openLink(a);


			});
		}
	}

//05.08.2019 - save form after pressing ENTER on quantity
$(document).on('keypress', '#frm-salelistgrid-editLine-quantity', function (e) {
	var charCode = e.charCode || e.keyCode;
	if (charCode  == 13) { //Enter key's keycode
		lGridCalcSale();
		$(this).closest('form').find('[name="sendLine"]').click();
		//$(this).closest('form').find('[name="send"]').click(); //22.11.2019 TH - this works without error
	}
});





//22.05.2019 - save pdf from sale

$(document).on('hidden.bs.modal', '#pdfModal', function (e) {
	// do something...
	var objConfig = jQuery.parseJSON(jQuery('#saleconfig').text());
	var url = objConfig.urlRedir;
	//console.log(strUrl);
	//setTimeout(function(){
	//window.location.href = url;
	//}, 3000);
	var a = document.createElement('a');
	////finalUrl = url + '&data='+data;
	a.href = url;
	////a.setAttribute('data-transition', transition);
	a.setAttribute('data-history', 'false');
	////a.setAttribute('data-ajax', 'false');
	////a.setAttribute('data-scroll-to', '.openedEditLine');
	_context.invoke(function(di) {
		di.getService('page').openLink(a);
	});



})

$(document).on('click', '#printbtn', function (e) {
	var objConfig = jQuery.parseJSON(jQuery('#saleconfig').text());
	var url = objConfig.printSave;
	var url2 = objConfig.newSale;

	var strUrl = $(this).data('url-redir');
	//console.log(strUrl);
	//setTimeout(function(){
	//	window.location.href = strUrl;
	//}, 3000);

	finalUrl = url + '&print_id='+$(this).data('print_id');
	//window.location.href = finalUrl;



	var a = document.createElement('a');
	////finalUrl = url + '&data='+data;
	a.href = finalUrl;
	////a.setAttribute('data-transition', transition);
	a.setAttribute('data-history', 'false');
	////a.setAttribute('data-ajax', 'false');
	////a.setAttribute('data-scroll-to', '.openedEditLine');
	_context.invoke(function(di) {
		di.getService('page').openLink(a);
	});




	//$.ajax({
	//	url: finalUrl,
	//	success: function(payload) {
	//		//console.log(payload.tax);
	//		setTimeout(function(){
	//			//var ab = document.createElement('a');
	//			//finalUrl = objConfig.redirectStoreOutUpdate;
	//			//ab.href = finalUrl + "default/" + payload.id+'?do=showBsc';
	//			//a.setAttribute('data-transition', transition);
	//			//ab.setAttribute('data-history', 'true');
	//			//ab.setAttribute('data-ajax', 'false');
	//			//_context.invoke(function(di) {
	//			//di.getService('page').openLink(ab).then( function(){
	//			//});
	//			//});
//
//			}, 150);
		//}
	//});


	}
);


//17.05.2019 - create correction from sale
$(document).on('click','#saleToCorrection', function (e) {

	var toCorrectionVal = 0;

	//.html()
	$('.id-quantity_back a span[style*="float"]').each(function( index ) {
		console.log(index);

		toCorrectionVal += parseFloat($(this).html());
	});
	console.log(toCorrectionVal);
	if  ( toCorrectionVal == 0 )
	{
		bootbox.dialog({
			message: "Nevybrali jste žádné položky k vrácení, není možné pokračovat.",
			title: "Varování",
			buttons: {
				cancel: {
					label: "Zpět",
					className: "btn-primary",
					callback: function() {

					}
				}
			}

		});
	}else{

		var objConfig = jQuery.parseJSON(jQuery('#salereviewconfig').text());
		var url = objConfig.createCorrection;

		finalUrl = url;
		//$.ajax({
		//	url: finalUrl,
		//	success: function(payload) {
		//		//console.log(payload.tax);
		//	}
		//});
		var a = document.createElement('a');
		//finalUrl = url + '&data='+data;
		a.href = finalUrl;
		//a.setAttribute('data-transition', transition);
		a.setAttribute('data-history', 'false');
		//a.setAttribute('data-ajax', 'false');
		//a.setAttribute('data-scroll-to', '.openedEditLine');
		_context.invoke(function(di) {
			di.getService('page').openLink(a);
		});
		//$.ajax({
		//	url: finalUrl,
		//	success: function(payload) {
		//		//console.log(payload.tax);
		//		setTimeout(function(){
		//			//var ab = document.createElement('a');
		//			//finalUrl = objConfig.redirectStoreOutUpdate;
		//			//ab.href = finalUrl + "default/" + payload.id+'?do=showBsc';
		//			//a.setAttribute('data-transition', transition);
		//			//ab.setAttribute('data-history', 'true');
		//			//ab.setAttribute('data-ajax', 'false');
		//			//_context.invoke(function(di) {
		//			//di.getService('page').openLink(ab).then( function(){
		//			//});
		//			//});
		//			//    window.location.href = ab.href;
		//		}, 150);
		//	}
		//});


		//window.location.href = finalUrl;

		$('#createCorrectionModal').modal('hide');


	}
//	   e.preventDefault();
	e.stopImmediatePropagation();
});


