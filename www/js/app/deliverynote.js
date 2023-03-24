/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 21.6.2016 - 14:31:06
 * 
 */
	//price_off
	$(document).on('click',"#frm-edit-price_off", function(e) {
	    value = $('#frm-edit-price_off').prop('checked');
	    console.log(this);
	    url = $(this).data('urlajax');		
		$.ajax({
			url: url,
			type: 'get',
			context: this,
			data: 'value='+value,
			dataType: 'json',
			success: function(data) {
			    //$("#loading").hide();
			    //$('#gridSetBox').show();
			    }
			});	    
	});
	
	
	//vypocty v karte dodacího listu
	function initDeliveryNote()
	{

	}
	    //Tomas Halasz
	    //faktura a jeji obsa
	    //



	    //vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
	    $(document).on('blur', "#frm-deliveryNotelistgrid-editLine-quantity, #frm-deliveryNotelistgrid-editLine-price_e, #frm-deliveryNotelistgrid-editLine-discount, #frm-deliveryNotelistgrid-editLine-vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalcInvoice();
			//}
	    });
	    //vypocet celkove ceny za polozky a celkove ceny s DPH
	    //vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
	    function lGridCalcInvoice(){
			var price_e_type = $('input[name=price_e_type]').val();
		    var quantity = parseFloat($('#frm-deliveryNotelistgrid-editLine-quantity').val().split(' ').join('').replace(',','.'));
		    var price_e = parseFloat($('#frm-deliveryNotelistgrid-editLine-price_e').val().split(' ').join('').replace(',','.'));
		    var discount = parseFloat($('#frm-deliveryNotelistgrid-editLine-discount').val().split(' ').join('').replace(',','.'));
			if (isNaN(discount))
				discount = 0;

		    if ($('#frm-deliveryNotelistgrid-editLine-vat').length>0)
				vat = parseFloat($('#frm-deliveryNotelistgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
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
			$('#frm-deliveryNotelistgrid-editLine-price_e2').val(price_e2);
			$('#frm-deliveryNotelistgrid-editLine-price_e2').autoNumeric('update');							
			$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').val(price_e2_vat);
			$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').autoNumeric('update');				
			
	    }

	    //vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
	     $(document).on('blur', "#frm-deliveryNotelistgrid-editLine-price_e2_vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();				
				var quantity = parseFloat($('#frm-deliveryNotelistgrid-editLine-quantity').val().split(' ').join(''));
				var price_e2_vat = parseFloat($('#frm-deliveryNotelistgrid-editLine-price_e2_vat').val().split(' ').join(''));
				if ($('#frm-deliveryNotelistgrid-editLine-vat').length > 0)
					vat = $('#frm-deliveryNotelistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				var discount = parseFloat($('#frm-deliveryNotelistgrid-editLine-discount').val().split(' ').join(''));
				 if (isNaN(discount))
					 discount = 0;

				var calc_vat = (price_e2_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );
				var price_e2 = price_e2_vat - calc_vat;
				$('#frm-deliveryNotelistgrid-editLine-price_e2').val(price_e2);
				$('#frm-deliveryNotelistgrid-editLine-price_e2').autoNumeric('update');
				if (price_e_type == 0)
				{				
					price_e = (price_e2/(1-(discount/100))) / quantity;					

				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
				$('#frm-deliveryNotelistgrid-editLine-price_e').val(price_e);
				$('#frm-deliveryNotelistgrid-editLine-price_e').autoNumeric('update');				
				//lGridCalcProfit();
			//}
	    });	    
	    //vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH	    
	     $(document).on('blur', "#frm-deliveryNotelistgrid-editLine-price_e2", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();								
				var quantity = parseFloat($('#frm-deliveryNotelistgrid-editLine-quantity').val().split(' ').join(''));
				var price_e2 = parseFloat($('#frm-deliveryNotelistgrid-editLine-price_e2').val().split(' ').join(''));
				if ($('#frm-deliveryNotelistgrid-editLine-vat').length > 0)
					vat = $('#frm-deliveryNotelistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				var discount = parseFloat($('#frm-deliveryNotelistgrid-editLine-discount').val().split(' ').join(''));
				 if (isNaN(discount))
					 discount = 0;
				var calc_vat = (price_e2 * ( vat / 100 ));		    
				var price_e2_vat = Math.round((price_e2 + calc_vat) * 100 ) / 100;		    
				$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').val(price_e2_vat);
				$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').autoNumeric('update');
				if (price_e_type == 0)
				{								
					price_e = (price_e2/(1-(discount/100))) / quantity;					
				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
				$('#frm-deliveryNotelistgrid-editLine-price_e').val(price_e);
				$('#frm-deliveryNotelistgrid-editLine-price_e').autoNumeric('update');				
				//lGridCalcProfit();
			//}
	    });	    	    
	    //
	    //konec faktury
	    //	
	    
	    
	    //20.11.2018 - invoice listgrid back
	    //
	    //vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
	    $(document).on('blur', "#frm-deliveryNoteBacklistgrid-editLine-quantity, #frm-deliveryNoteBacklistgrid-editLine-price_e, #frm-deliveryNoteBacklistgrid-editLine-discount, #frm-deliveryNoteBacklistgrid-editLine-vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalcInvoiceBack();
			//}
	    });
	    //vypocet celkove ceny za polozky a celkove ceny s DPH
	    //vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
	    function lGridCalcInvoiceBack(){
			var price_e_type = $('input[name=price_e_type]').val();
		    var quantity = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-quantity').val().split(' ').join('').replace(',','.'));
		    var price_e = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-price_e').val().split(' ').join('').replace(',','.'));
		   // var discount = parseFloat($('#frm-invoiceBacklistgrid-editLine-discount').val().split(' ').join('').replace(',','.'));
		    discount = 0;


		    if ($('#frm-deliveryNoteBacklistgrid-editLine-vat').length>0)
				vat = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
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
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2').val(price_e2);
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2').autoNumeric('update');							
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').val(price_e2_vat);
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').autoNumeric('update');				
			
	    }

	    //vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
	     $(document).on('blur', "#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();				
				var quantity = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-quantity').val().split(' ').join(''));
				var price_e2_vat = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').val().split(' ').join(''));
				if ($('#frm-deliveryNoteBacklistgrid-editLine-vat').length > 0)
					vat = $('#frm-deliveryNoteBacklistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;


				//var discount = parseFloat($('#frm-invoiceBacklistgrid-editLine-discount').val().split(' ').join(''));
				discount = 0;
				
				var calc_vat = (price_e2_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );
				var price_e2 = price_e2_vat - calc_vat;
				$('#frm-deliveryNoteBacklistgrid-editLine-price_e2').val(price_e2);
				$('#frm-deliveryNoteBacklistgrid-editLine-price_e2').autoNumeric('update');
				if (price_e_type == 0)
				{				
					price_e = (price_e2/(1-(discount/100))) / quantity;					

				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
				$('#frm-deliveryNoteBacklistgrid-editLine-price_e').val(price_e);
				$('#frm-deliveryNoteBacklistgrid-editLine-price_e').autoNumeric('update');				
				//lGridCalcProfit();
			//}
	    });	    
	    //vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH	    
	     $(document).on('blur', "#frm-deliveryNoteBacklistgrid-editLine-price_e2", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();								
				var quantity = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-quantity').val().split(' ').join(''));
				var price_e2 = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-price_e2').val().split(' ').join(''));
				if ($('#frm-deliveryNoteBacklistgrid-editLine-vat').length > 0)
					vat = $('#frm-deliveryNoteBacklistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				//var discount = parseFloat($('#frm-invoiceBacklistgrid-editLine-discount').val().split(' ').join(''));
				var discount = 0;
				var calc_vat = (price_e2 * ( vat / 100 ));		    
				var price_e2_vat = Math.round((price_e2 + calc_vat) * 100 ) / 100;		    
				$('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').val(price_e2_vat);
				$('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').autoNumeric('update');
				if (price_e_type == 0)
				{								
					price_e = (price_e2/(1-(discount/100))) / quantity;					
				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
				$('#frm-deliveryNoteBacklistgrid-editLine-price_e').val(price_e);
				$('#frm-deliveryNoteBacklistgrid-editLine-price_e').autoNumeric('update');				
				//lGridCalcProfit();
			//}
	    });	
	    
	    
//28.03.2019 - create invoice from deliverynote
$(document).on('click','#deliveryNoteToInvoice', function (e) {
    
    var items=[];
    $('.checklistGridDeliveryNoteSelect:checked:visible').each(function( index ) {
		items[index] = $(this).data('id');
      });
  
    var itemsToCheck = $('.checklistGridDeliveryNoteSelect:not(:checked):visible').length;

    
    if ( ( itemsToCheck > 0 ) && (items.length === 0 ) )
    {
	bootbox.dialog({
		message: "Nevybrali jste žádné dodací listy. Fakturu není možné vytvořit.",
		title: "Varování",
		buttons: {
			 cancel: {
				label: "Ok",
				className: "btn-primary",
				callback: function() {
				
				}
			  }
		}	

	});						
    }else{
	deliveryNoteToInvoice(items);
    }
//	   e.preventDefault();
    e.stopImmediatePropagation();	
});
	    
	    
function deliveryNoteToInvoice(items,works)
{
    var objConfig = jQuery.parseJSON(jQuery('#deliveryNoteconfig').text());	
    var url = objConfig.createInvoice;
	var objConfigMain = jQuery.parseJSON(jQuery('#configMain').text());
	var urlFlash = objConfigMain.showFlashNow;

	finalUrl = url + '&dataItems='+JSON.stringify(items) ;
	$("#loading").show();
    $.ajax({
	url: finalUrl,
	success: function(payload) {
	    //console.log(payload);
	    setTimeout(function(){
		    var ab = document.createElement('a');
		    if (payload.id != undefined) {
				finalUrl = objConfig.redirectInvoice;
				$posQ = finalUrl.indexOf('?');
				if ( $posQ > 1){
					ab.href = finalUrl.substr(0, $posQ) + "/" + payload.id;
				}else{
					ab.href = finalUrl + "/" + payload.id;
				}

				//a.setAttribute('data-transition', transition);
				//ab.setAttribute('data-history', 'true');
				//ab.setAttribute('data-ajax', 'false');
				//_context.invoke(function(di) {
				//di.getService('page').openLink(ab).then( function(){
				//});
				//});
				window.location.href = ab.href;
			}else{
				$("#loading").hide();
				//21.01.2021 - call nittro url for show correct flash message
				var ab = document.createElement('a');
				strData = JSON.stringify(payload.status);
				ab.href = urlFlash + '&arrData=' + strData;

				ab.setAttribute('data-history', 'false');
				_context.invoke(function(di) {
					di.getService('page').openLink(ab).then( function(){
						$("#loading").hide();
					});
				});
				//window.location.reload();
			}
		}, 150);
	}
    });	    
    $('#createInvoiceModal').modal('hide');    
}
	    