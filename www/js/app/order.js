/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 21.6.2016 - 13:06:42
 * 
 */


//vypocty v karte objednávky 

	function saveEditor(form){
		
		var objectForm = $(form).parents('form:first');
		var objectName = form.data('edit');
		
		var content = tinyMCE.get(objectName).getContent();
		$('#' + objectName).val(content);
		$('#' + form.data('modal')).modal('hide');
		$(objectForm).find('input[name=send]').click();
		tinymce.remove();
	}


	function initOrder()
	{
	    
	    $(document).on('change','#order_header_show', function(e) {
		var urlString  = $(this).data('urlajax');
		    $.ajax({
			    url: urlString,
			    type: 'get',
			    context: this,
			    data: 'value=' + $(this).prop('checked'),
			    dataType: 'text',
			    success: function(data) {
				}
			    }); 		    
	    });    	    
	    
	    
	    //get currency rate on change select
/*	    $(document).on('change','#frm-edit-cl_currencies_id', function(e) {
		var urlString  = $(this).data('urlajax');
		    $.nette.ajax({
			    url: urlString,
				type: 'get',
				context: this,
				data: 'idCurrency=' + $(this).val() ,
				dataType: 'json',
				success: function(data) {
				    if (data != $('#frm-edit-currency_rate').val())
				    {
					var urlString2  = $(this).data('urlrecalc');					
					if (confirm('Změna kurzu, přepočítat položky?'))
					{
					    recalc = 1;
					}else{
					    recalc = 0;
					}
					$("#loading").show();
					$.nette.ajax({
						    url: urlString2,
						    type: 'get',
						    context: this,
						    data: 'idCurrency=' + $(this).val() + '&rate=' + data + '&oldrate=' + $('#frm-edit-currency_rate').val() + '&recalc=' + recalc,
						    dataType: 'json',
						    success: function(data) {
						    $("#loading").hide();
						}});					
					    
				    }
				    $('#frm-edit-currency_rate').val(data);
				    $("#loading").hide();
				    //$('#gridSetBox').show();
				    }
				}); 
	    });
	    var previous;
	    $(document).on('focus','#frm-edit-currency_rate',  function () {
		// Store the current value on focus and on change
		previous = this.value;
		}).on('change','#frm-edit-currency_rate', function(e) {
		    if (this.value == 0)
		    {
			alert("Kurz nemůže být 0");
			$(this).val(1);
		    }else{
			if (confirm('Změna kurzu, přepočítat položky?'))
			{
			    recalc = 1;
			}else{
			    recalc = 0;
			}			
			var urlString2  = $(this).data('urlrecalc');
			$("#loading").show();
			$.nette.ajax({
				    url: urlString2,
				    type: 'get',
				    context: this,
				    data: 'idCurrency=' + $('#frm-edit-cl_currencies_id').val() + '&rate=' + $(this).val() + '&oldrate=' + previous + '&recalc=' + recalc,
				    dataType: 'json',
				    success: function(data) {
				    $("#loading").hide();
				}});					
		    }

	    });		   */

	    
 //objednavka
	    //vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
	    $(document).on('blur', "#frm-orderlistgrid-editLine-quantity, #frm-orderlistgrid-editLine-price_e, #frm-orderlistgrid-editLine-vat", function (e) {
		//var charCode = e.charCode || e.keyCode;
		//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
		    lOrderGridCalc();
		//}
	    });
	    //vypocet celkove ceny za polozky a celkove ceny s DPH
	    //vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
	    function lOrderGridCalc(){
		    var quantity = $('#frm-orderlistgrid-editLine-quantity').val().split(' ').join('').replace(',','.');
		    var price_e = $('#frm-orderlistgrid-editLine-price_e').val().split(' ').join('').replace(',','.');
		    price_e2 = quantity * price_e;
		    $('#frm-orderlistgrid-editLine-price_e2').val(price_e2);
		    $('#frm-orderlistgrid-editLine-price_e2').autoNumeric('update')
		    if ($('#frm-orderlistgrid-editLine-vat').length>0)
			vat = $('#frm-orderlistgrid-editLine-vat').val().split(' ').join('').replace(',','.');
		    else
			vat = 0;
		    
		    var price_e2 = $('#frm-orderlistgrid-editLine-price_e2').val().split(' ').join('').replace(',','.');
		    var price_e2_vat = Math.round((price_e2 * (1+(vat/100)))*100)/100;
		    $('#frm-orderlistgrid-editLine-price_e2_vat').val(price_e2_vat);
		    $('#frm-orderlistgrid-editLine-price_e2_vat').autoNumeric('update');
	    }
		
	    //vypocty pri zmene celkove ceny bez DPH
	    $(document).on('blur', "#frm-orderlistgrid-editLine-price_e2", function (e) {
		//var charCode = e.charCode || e.keyCode;
		//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
		    var quantity = $('#frm-orderlistgrid-editLine-quantity').val().split(' ').join('').replace(',','.');
		    var price_e2 = $('#frm-orderlistgrid-editLine-price_e2').val().split(' ').join('').replace(',','.');
		    price_e = price_e2 / quantity;
		    $('#frm-orderlistgrid-editLine-price_e').val(price_e);
		    $('#frm-orderlistgrid-editLine-price_e').autoNumeric('update')
		    
		    if ($('#frm-orderlistgrid-editLine-vat').length>0)
			vat = $('#frm-orderlistgrid-editLine-vat').val().split(' ').join('').replace(',','.');
		    else
			vat = 0;
		    
		    var price_e2_vat = Math.round((price_e2 * (1+(vat/100)))*100)/100;
		    $('#frm-orderlistgrid-editLine-price_e2_vat').val(price_e2_vat);
		    $('#frm-orderlistgrid-editLine-price_e2_vat').autoNumeric('update');
		//}
	    });	    
		
	    //vypocty pri zmene celkove ceny s DPH
	    $(document).on('blur', "#frm-orderlistgrid-editLine-price_e2_vat", function (e) {
		//var charCode = e.charCode || e.keyCode;
		//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
		    var quantity = $('#frm-orderlistgrid-editLine-quantity').val().split(' ').join('').replace(',','.');
		    var price_e2_vat = $('#frm-orderlistgrid-editLine-price_e2_vat').val().split(' ').join('').replace(',','.');

		    if ($('#frm-orderlistgrid-editLine-vat').length>0)
			vat = $('#frm-orderlistgrid-editLine-vat').val().split(' ').join('').replace(',','.');
		    else
			vat = 0;
		    
		    var price_e2 = Math.round((price_e2_vat / (1+(vat/100)))*100)/100;
		    $('#frm-orderlistgrid-editLine-price_e2').val(price_e2);
		    $('#frm-orderlistgrid-editLine-price_e2').autoNumeric('update');		    
		    
		    price_e = price_e2 / quantity;
		    $('#frm-orderlistgrid-editLine-price_e').val(price_e);
		    $('#frm-orderlistgrid-editLine-price_e').autoNumeric('update')
		    


		//}
	    });	    	    
	    
	    
	    //konec objednavky
	    
	    		
		
		
	}
	
	
//09.03.2019 - create invoice from commission
$(document).on('click','#orderToStore', function (e) {
    
    var items=[];
    $('.checkorderlistgridSelect:checked:visible').each(function( index ) {
	items[index] = $(this).data('id');
      });
    var itemsToCheck = $('.checkorderlistgridSelect:not(:checked):visible').length;
    //( itemsToCheck > 0 ) && 
    if ( (items.length === 0  ) )
    {
	bootbox.dialog({
		message: "Nevybrali jste žádné položky k naskladnění!",
		title: "Zpráva",
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
	orderToStoreIn(items);
    }
//	   e.preventDefault();
    e.stopImmediatePropagation();	
});



function orderToStoreIn(items)
{
    var objConfig = jQuery.parseJSON(jQuery('#orderconfig').text());	
    var url = objConfig.createStoreIn;     

    finalUrl = url + '&dataItems='+JSON.stringify(items);
    $.ajax({
	url: finalUrl,
	success: function(payload) {
	    //console.log(payload.tax);
	    setTimeout(function(){
		    var ab = document.createElement('a');
		    finalUrl = objConfig.redirectStore;
		    ab.href = finalUrl + "/" + payload.id+'?do=showBsc';
		      //a.setAttribute('data-transition', transition);
		      ab.setAttribute('data-history', 'true');
		      ab.setAttribute('data-ajax', 'false');
		      _context.invoke(function(di) {
			di.getService('page').openLink(ab).then( function(){ 
			});
		      });		    
		    window.location.href = ab.href;
		}, 150);
	}
    });	    
    $('#createStoreInModal').modal('hide')    
}

	

