/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 17.6.2016 - 11:25:56
 * 
 */


	//vypocty v karte zakazky

	//set DueDate on change invoice date
	$(document).on('blur','#frm-edit-cm_date', function(e) {	
		//console.log('inv_date change');
		setDueDate(false);
	    //e.preventDefault();
	    e.stopImmediatePropagation();

	});	

	    $(document).on('change','#cm_header_show', function(e) {
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
	    

	    $(document).on('change','#cm_description_show', function(e) {
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
	    


	    //change of vat rate
	    $(document).on('change','#frm-edit-vat', function(e) {
			recalcVat();

	    });
	    
	    //recalc preliminary prices
	    $(document).on('blur','#frm-edit-price_pe2_base', function(e) {
			//var charCode = e.charCode || e.keyCode;
			var price_e_type = $('input[name=price_e_type]').val();
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
			    recalcVat();
			//}
	    });
	    $(document).on('blur','#frm-edit-price_pe2_vat', function(e) {
			//var charCode = e.charCode || e.keyCode;
			var price_e_type = $('input[name=price_e_type]').val();
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
			    var vat = parseFloat($('#frm-edit-vat').val().split(' ').join('').replace(',','.'));
			    var priceVat = parseFloat($(this).autoNumeric('get'));
			    var calcBase = priceVat / (1+(vat/100));
			    $('#frm-edit-price_pe2_base').val(calcBase);
			    $('#frm-edit-price_pe2_base').autoNumeric('update');
			//}
	    });	    
	    function recalcVat()
	    {
		    var vat = parseFloat($('#frm-edit-vat').val().split(' ').join('').replace(',','.'));
		    var priceBase = parseFloat($("#frm-edit-price_pe2_base").autoNumeric('get'));
		    var calcVat = priceBase * (1+(vat/100));
		    $('#frm-edit-price_pe2_vat').val(calcVat);
		    $('#frm-edit-price_pe2_vat').autoNumeric('update');
	    }
	    
		
		
   //
	    //
	    //evidence zakazek
	    //
	    //
	    //
	    //calculate function above edit lince
	    //mnozstvi frm-listgrid-editLine-quantity
	    //1)pokud zadam mnozstvi, cenu za jednotku nebo slevu pak:
	    //celkem po sleve bez DPH 
	    //frm-listgrid-editLine-price_e2 = frm-listgrid-editLine-quantity * (frm-listgrid-editLine-price_e * (1-(frm-listgrid-editLine-discount/100)))
	    //celkem po sleve s DPH
	    //frm-listgrid-editLine-price_e2_vat = frm-listgrid-editLine-price_e2 * (1+(frm-listgrid-editLine-vat/100)))
	    //2)pokud zadam cenu celkem bez DPH pak:
	    //nejprve cenu bez slevy
	    //frm-listgrid-editLine-price_e = ((frm-listgrid-editLine-price_e2/(1-(frm-listgrid-editLine-discount/100)))*100) / frm-listgrid-editLine-quantity 
	    //3)pokud zadam cenu celkem s DPH pak:
	    //nejprve cenu bez dph
	    //frm-listgrid-editLine-price_e2 = ((frm-listgrid-editLine-price_e2_vat/(1+(frm-listgrid-editLine-vat/100)))
	    //pak cenu bez slevy 
	    //frm-listgrid-editLine-price_e = ((frm-listgrid-editLine-price_e2/(1-(frm-listgrid-editLine-discount/100)))*100) / frm-listgrid-editLine-quantity 
	    
	    //zisk frm-listgrid-editLine-profit
	    //nakup frm-listgrid-editLine-price_s
	    
	    //vypocet prodejni ceny pri zmene nakupni ceny nebo pri zmene zisku
		$(document).on('blur','#frm-listgrid-editLine-profit, #frm-listgrid-editLine-price_s,#frm-listgridSel-editLine-profit, #frm-listgridSel-editLine-price_s', function (e) {
			//var charCode = e.charCode || e.keyCode;
			var price_e_type = $('input[name=price_e_type]').val();
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var profit = parseFloat($('input[name=profit]').val().split(' ').join('').replace(',','.'));
				var price_s = parseFloat($('input[name=price_s]').val().split(' ').join('').replace(',','.'));
				if ($('select[name=vat]').length>0)
					var vat = parseFloat($('select[name=vat]').val().split(' ').join('').replace(',','.'));
				else
					var vat = 0;
				
				if (price_s > 0 )  //&& profit >0
				{
					if (price_e_type == 0)
					{//cena za jednotku je bez DPH					
						var price_e = (price_s * (1+(profit/100)));
					}else{//cena za jednotku je s DPH
						var calcVat = ((price_s * (1+(profit/100))) * (vat / 100));
						var price_e = (price_s * (1+(profit/100))) + calcVat;
					}
					$('input[name=price_e]').val(price_e);
					$('input[name=price_e]').autoNumeric('update');
					lGridCalc(this);
				}
			//}
	    });	    
	    
	    $(document).on('blur',"#frm-listgrid-editLine-price_s,#frm-listgridSel-editLine-price_s", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalcProfit();
			//}
	    });	    	    

	    //vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
	    $(document).on('blur',"#frm-listgrid-editLine-quantity, #frm-listgrid-editLine-price_e, #frm-listgrid-editLine-discount, #frm-listgrid-editLine-vat, #frm-listgridSel-editLine-quantity, #frm-listgridSel-editLine-price_e, #frm-listgridSel-editLine-discount, #frm-listgridSel-editLine-vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalc(this);
				lGridCalcProfit();
				e.stopImmediatePropagation();
				//e.preventDefault();
				//var inputs = $(this).closest('form').find(':input:visible:not([readonly])');
				//inputs.eq( inputs.index(this)+ 1 ).select().focus();				
			//}
	    });
	    /*$(document).on('mouseup',"#frm-listgrid-editLine-quantity, #frm-listgrid-editLine-price_e, #frm-listgrid-editLine-discount, #frm-listgrid-editLine-vat, #frm-listgridSel-editLine-quantity, #frm-listgridSel-editLine-price_e, #frm-listgridSel-editLine-discount, #frm-listgridSel-editLine-vat", function (e) {	    
				lGridCalc(this);
				lGridCalcProfit();
	    });	    */
	    
	    //vypocet celkove ceny za polozky a celkove ceny s DPH
	    //vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
	    function lGridCalc(obj){
		    var price_e_type	= parseFloat($(obj).parents('form:first').find('input[name=price_e_type]').val());			
		    var quantity	= parseFloat($('input[name=quantity]').val().split(' ').join('').replace(',','.'));
		    var price_e		= parseFloat($('input[name=price_e]').val().split(' ').join('').replace(',','.'));
		    var discount	= parseFloat($('input[name=discount]').val().split(' ').join('').replace(',','.'));
			if (isNaN(discount))
				discount = 0;

			//console.log(discount);

		    if ($('#frm-listgridSel-editLine-vat').length>0)
				vat = parseFloat($('#frm-listgridSel-editLine-vat').val().split(' ').join('').replace(',','.'));
		    else
				vat = 0;
			
			if (price_e_type == 0)
			{//cena za jednotku je bez DPH
				var price_e2 = quantity * (price_e * ( 1 - ( discount / 100 )));
				var calcVat = (price_e2 * (( vat / 100)));
				var price_e2_vat = Math.round((price_e2 + calcVat ) * 100 ) / 100;				
			}else{
			 //cena za jednotku je s DPH
				var price_e2_vat = quantity * ( price_e * (1  - (discount/100))) ;			 
				var calcVat = (price_e2_vat / (1 + (vat / 100)) * (vat / 100));
				var price_e2 = Math.round((price_e2_vat - calcVat ) * 100) / 100 ;

			}
			$('input[name=price_e2]').val(price_e2);
			$('input[name=price_e2]').autoNumeric('update');				
			$('input[name=price_e2_vat]').val(price_e2_vat);
			$('input[name=price_e2_vat]').autoNumeric('update');				

		    //var price_e2 = $('#frm-listgrid-editLine-price_e2').val().split(' ').join('').replace(',','.');
	    }

	    //vypocet zisku
	    function lGridCalcProfit(){
			var price_e_type = $('input[name=price_e_type]').val();			
		    price_s = parseFloat($('input[name=price_s]').val().split(' ').join('').replace(',','.'));
		    if (price_s > 0)
		    {
				price_e = parseFloat($('input[name=price_e]').val().split(' ').join('').replace(',','.'));
				
				if ($('#frm-listgridSel-editLine-vat').length>0)
					vat = parseFloat($('#frm-listgridSel-editLine-vat').val().split(' ').join('').replace(',','.'));
				else
					vat = 0;

				
				if (price_e_type == 0)
				{//cena za jednotku je bez DPH
					var profit = ((price_e / price_s)-1)*100;
				}else{//cena za jednotku je s DPH
					var calcVat = (price_e / (1 + (vat / 100)) * (vat / 100));
					var profit = (((price_e - calcVat) / price_s)-1)*100;
				}


				$('input[name=profit]').val(profit);
				$('input[name=profit]').autoNumeric('update');
		    }		    		
	    }
	    //vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
	    $(document).on('blur', "input[name=price_e2_vat]", function (e) {
			var price_e_type = $('input[name=price_e_type]').val();						
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var quantity = parseFloat($('input[name=quantity]').val().split(' ').join(''));
				var price_e2_vat = parseFloat($('input[name=price_e2_vat]').val().split(' ').join(''));
				if ($('#frm-listgridSel-editLine-vat').length>0)
					vat = parseFloat($('#frm-listgridSel-editLine-vat').val().split(' ').join(''));
				else
					vat = 0;

				var discount = parseFloat($('input[name=discount]').val().split(' ').join(''));
				if (isNaN(discount))
					discount = 0;

				if (price_e_type == 0)
				{//cena za jednotku je bez DPH		
					var calcVat = (price_e2_vat / ( 1 + ( vat / 100 ) ) * ( vat / 100 ));
					var price_e2 = price_e2_vat - calcVat;
					var price_e = (price_e2 / ( 1 - ( discount / 100 ) ) ) / quantity;
				}else{//cena za jednotku je s DPH
					var calcVat = ( price_e2_vat / ( 1 + ( vat /100 )) * ( vat /100 ));
					var price_e2 = price_e2_vat - calcVat;
					var price_e = (price_e2_vat / ( 1 - ( discount/ 100 ) ) )  / quantity;					
				}
				$('input[name=price_e2]').val(price_e2);
				$('input[name=price_e2]').autoNumeric('update');
				$('input[name=price_e]').val(price_e);
				$('input[name=price_e]').autoNumeric('update');
				lGridCalcProfit();
			//}
	    });	    
	    //vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH	    
	    $(document).on('blur',"input[name=price_e2]", function (e) {
			var price_e_type = $('input[name=price_e_type]').val();			
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var quantity = parseFloat($('input[name=quantity]').val().split(' ').join(''));
				var price_e2 = parseFloat($('input[name=price_e2]').val().split(' ').join(''));
				if ($('#frm-listgridSel-editLine-vat').length>0)
					var vat = parseFloat($('#frm-listgridSel-editLine-vat').val().split(' ').join(''));
				else
					var vat = 0;

				var discount = parseFloat($('input[name=discount]').val().split(' ').join(''));
				if (isNaN(discount))
					discount = 0;

				var calcVat = ((price_e2 * ( vat / 100 ) ) );
				var price_e2_vat = Math.round((price_e2 + calcVat ) * 100 ) / 100;
				
				if (price_e_type == 0)
				{//cena za jednotku je bez DPH								
					var price_e = (price_e2/(1-(discount/100))) / quantity;
				}else{//cena ze jednotku je s DPH
					var price_e = (price_e2_vat/(1-(discount/100))) / quantity;
				}
				$('input[name=price_e2_vat]').val(price_e2_vat);
				$('input[name=price_e2_vat]').autoNumeric('update');
				$('input[name=price_e]').val(price_e);
				$('input[name=price_e]').autoNumeric('update');
				lGridCalcProfit();
			//}
	    });	    	    
	    
	    //vypocet doby prace
	    $(document).on('blur',"#frm-listgridWork-editLine-work_date_s, #frm-listgridWork-editLine-work_date_e", function (e) {
                    //var charCode = e.charCode || e.keyCode;
                    //if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
			    
                            startDate = $('#frm-listgridWork-editLine-work_date_s').val().split(" ");
                            endDate = $('#frm-listgridWork-editLine-work_date_e').val().split(' ');

                            dateParts = startDate[0].split(".");
                            timeParts = startDate[1].split(":");
                            dateS = new Date(dateParts[2], dateParts[1], dateParts[0], timeParts[0], timeParts[1], 0);
                            dateParts = endDate[0].split(".");
                            timeParts = endDate[1].split(":");		    
                            dateE = new Date(dateParts[2], dateParts[1], dateParts[0], timeParts[0], timeParts[1], 0);		    
                            hours = (dateE-dateS)/1000/60/60;
                            $('#frm-listgridWork-editLine-work_time').val(hours);
                            $('#frm-listgridWork-editLine-work_time').autoNumeric('update')
                    //}
	    });
	    
	    //vypocet doby ukolu - 22.08.2021 - not used because task is only for planing. Real hours come from work
/*	    $(document).on('blur',"#frm-listgridTask-editLine-work_date_s, #frm-listgridTask-editLine-work_date_e", function (e) {
                            startDate = $('#frm-listgridTask-editLine-work_date_s').val().split(" ");
                            endDate = $('#frm-listgridTask-editLine-work_date_e').val().split(' ');
                            dateParts = startDate[0].split(".");
                            timeParts = startDate[1].split(":");
                            dateS = new Date(dateParts[2], dateParts[1], dateParts[0], timeParts[0], timeParts[1], 0);
                            dateParts = endDate[0].split(".");
                            timeParts = endDate[1].split(":");		    
                            dateE = new Date(dateParts[2], dateParts[1], dateParts[0], timeParts[0], timeParts[1], 0);		    
                            hours = (dateE-dateS)/1000/60/60;
                            $('#frm-listgridTask-editLine-work_time').val(hours);
                            $('#frm-listgridTask-editLine-work_time').autoNumeric('update');
	    });
 */

	    //získání správné sazby pro pracovníka
	    $(document).on('change', "#frm-listgridWork-editLine-cl_users_id", function (e) {
		var userId = $(this).val();
		//console.log('change worker');
		//console.log(userId);
		var objConfig = jQuery.parseJSON(jQuery('#commissionconfig').text());	
		var url = objConfig.getWorkerTaxlink;
		//console.log(url);
		finalUrl = url + "&cl_users_id="+userId;
		$.ajax({
		    url: finalUrl,
		    success: function(payload) {
			//console.log(payload.tax);
			$('#frm-listgridWork-editLine-work_rate').val(payload.tax).autoNumeric('update');
		    }
		});		
	    });
	    
	    $(document).on('change', "#frm-listgridTask-editLine-cl_users_id", function (e) {
		var userId = $(this).val();
		var objConfig = jQuery.parseJSON(jQuery('#commissionconfig').text());	
		var url = objConfig.getWorkerTaxlink;
		//console.log(url);
		finalUrl = url + "&cl_users_id="+userId;
		$.ajax({
		    url: finalUrl,
		    success: function(payload) {
			//console.log(payload.tax);
			$('#frm-listgridTask-editLine-work_rate').val(payload.tax).autoNumeric('update');
		    }
		});		
	    });	    


//09.03.2019 - create invoice from commission
$(document).on('click','#commissionToInvoice', function (e) {
    
    var items=[];
    $('.checklistgridItemsSelSelect:checked:visible').each(function( index ) {
	items[index] = $(this).data('id');
      });
    var works=[];
    $('.checklistgridWorksSelect:checked:visible').each(function( index ) {
	works[index] = $(this).data('id');
      });      
    var itemsToCheck = $('.checklistgridItemsSelSelect:not(:checked):visible').length;
    var worksToCheck = $('.checklistgridWorksSelect:not(:checked):visible').length;

	var newInvoice = $(this).data('newinvoice');

    if ( ( itemsToCheck > 0 ||  worksToCheck > 0 ) && (items.length === 0  && works.length === 0 ) )
    {
	bootbox.dialog({
		message: "Nevybrali jste žádné položky ani práci, opravdu chcete pokračovat?",
		title: "Dotaz",
		buttons: {
			  success: {
				label: "Ano",
				className: "btn-success",
				callback: function() {
				    commissionToInvoice(items,works,newInvoice);
				}
			},
			 cancel: {
				label: "Ne",
				className: "btn-primary",
				callback: function() {
				
				}
			  }
		}	

	});						
    }else{
	if (items.length  > 0 || works.length  > 0){
	    commissionToInvoice(items,works,newInvoice);
	}else{
	    bootbox.dialog({
		    message: "Faktura musí obsahovat položky.",
		    title: "Zpráva",
		    buttons: {
			     cancel: {
				    label: "Zavřít",
				    className: "btn-primary",
				    callback: function() {

				    }
			      }
		    }	

	    });	    
	}	
    }
//	   e.preventDefault();
    e.stopImmediatePropagation();	
});



function commissionToInvoice(items,works,newInvoice)
{
	$('#createInvoiceModal').modal('hide');
    var objConfig = jQuery.parseJSON(jQuery('#commissionconfig').text());	
    var url = objConfig.createInvoice;
	$("#loading").show();
    finalUrl = url + '&dataItems='+JSON.stringify(items) + '&dataWorks='+JSON.stringify(works) + '&newInvoice='+newInvoice;
   /* $.ajax({
	url: finalUrl,
	success: function(payload) {
		setTimeout(function(){
		    var ab = document.createElement('a');
		    finalUrl = objConfig.redirectInvoice;
		    ab.href = finalUrl + "edit/" + payload.id; //+'?do=showBsc';
		    window.location.href = ab.href;
		}, 150);
	}
    });	 */


	var ab = document.createElement('a');
	finalUrl = url + '&dataItems='+JSON.stringify(items) + '&dataWorks='+JSON.stringify(works) + '&newInvoice='+newInvoice;
	ab.href = finalUrl;
	//ab.setAttribute('data-history', 'true');
	//ab.setAttribute('data-ajax', 'false');
	_context.invoke(function(di) {
		di.getService('page').openLink(ab).then( function(){
		});
	});

}



//09.03.2019 - create store out from commission
$(document).on('click','#commissionToStoreOut, #commissionToStoreOutDN', function (e) {
    var makedn = 0;
    if ($(this).prop('id') == 'commissionToStoreOutDN'){
    	makedn = 1;
	}
    var itemsSel=[];
    $('.checklistgridItemsSelSelect:checked:visible').each(function( index ) {
	itemsSel[index] = $(this).data('id');
      });
    var items=[];
    $('.checklistgridItemsSelect:checked:visible').each(function( index ) {
	items[index] = $(this).data('id');
      });      

    var itemsSelToCheck = $('.checklistgridItemsSelSelect:not(:checked):visible').length;
    var itemsToCheck = $('.checklistgridItemsSelect:not(:checked):visible').length;    
    
    if ( ( itemsToCheck > 0 || itemsSelToCheck > 0 ) && (items.length === 0 && itemsSel.length === 0) )
    {
	bootbox.dialog({
		message: "Nevybrali jste žádné položky, není možné pokračovat.",
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
	if ( items.length > 0 || itemsSel.length > 0 ){
	    commissionToStoreOut(itemsSel,items,makedn);
	}else{
		bootbox.dialog({
			message: "Výdejka musí obsahovat položky.",
			title: "Zpráva",
			buttons: {
				 cancel: {
					label: "Zavřít",
					className: "btn-primary",
					callback: function() {

					}
				  }
			}	

		});	    
	    }	
    }	
//	   e.preventDefault();
    e.stopImmediatePropagation();	
});



function commissionToStoreOut(itemsSel, items, makedn)
{
	$('#createStoreOutModal').modal('hide');
    var objConfig = jQuery.parseJSON(jQuery('#commissionconfig').text());
    var url = objConfig.createStoreOut;
	$("#loading").show();

/*    $.ajax({
	url: finalUrl,
	success: function(payload) {
	    setTimeout(function(){
		    var ab = document.createElement('a');
		    finalUrl = objConfig.redirectStoreOut;
		    ab.href = finalUrl + "edit/" + payload.id;
		    if (payload.id != null)
			window.location.href = ab.href;
		}, 150);
	}
    });*/

	var ab = document.createElement('a');
	finalUrl = url + '&dataItemsSel='+JSON.stringify(itemsSel) + '&dataItems='+JSON.stringify(items)+'&makeDN='+makedn;
	ab.href = finalUrl;
	//ab.setAttribute('data-history', 'true');
	//ab.setAttribute('data-ajax', 'false');
	_context.invoke(function(di) {
		di.getService('page').openLink(ab).then( function(){
		});
	});
}



//20.04.2019 - create invoice from commission
$(document).on('click','#commissionToStoreOutUpdate, #commissionToStoreOutUpdateDN', function (e) {
	var makedn = 0;
	if ($(this).prop('id') == 'commissionToStoreOutUpdateDN'){
		makedn = 1;
	}

    var itemsSel=[];
    $('.checklistgridItemsSelSelect:checked:visible').each(function( index ) {
	itemsSel[index] = $(this).data('id');
      });
    var items=[];
    $('.checklistgridItemsSelect:checked:visible').each(function( index ) {
	items[index] = $(this).data('id');
      });            

    var itemsSelToCheck = $('.checklistgridItemsSelSelect:not(:checked):visible').length;
    var itemsToCheck = $('.checklistgridItemsSelSelect:not(:checked):visible').length;
    
  if ( ( itemsToCheck > 0 || itemsSelToCheck > 0 ) && (items.length === 0 && itemsSel.length === 0) )
    {
	bootbox.dialog({
		message: "Nevybrali jste žádné položky, není možné pokračovat.",
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
	if ( items.length > 0 || itemsSel.length > 0 ){
	    commissionToStoreOutUpdate(itemsSel,items,makedn);
	}else{
		bootbox.dialog({
			message: "Výdejka musí obsahovat položky.",
			title: "Zpráva",
			buttons: {
				 cancel: {
					label: "Zavřít",
					className: "btn-primary",
					callback: function() {

					}
				  }
			}	

		});	    
	    }		

    }
//	   e.preventDefault();
    e.stopImmediatePropagation();	
});



function commissionToStoreOutUpdate(itemsSel, items, makedn)
{
	$('#createStoreOutUpdateModal').modal('hide');
    var objConfig = jQuery.parseJSON(jQuery('#commissionconfig').text());
    var url = objConfig.createStoreOutUpdate;
	$("#loading").show();

    /*$.ajax({
	url: finalUrl,
	success: function(payload) {
	    setTimeout(function(){
		    var ab = document.createElement('a');
		    finalUrl = objConfig.redirectStoreOut;
		    ab.href = finalUrl + "edit/" + payload.id;
		    window.location.href = ab.href;
		}, 150);
	}
    });*/

	var ab = document.createElement('a');
	finalUrl = url + '&dataItemsSel='+JSON.stringify(itemsSel) + '&dataItems='+JSON.stringify(items)+'&makeDN='+makedn;
	ab.href = finalUrl;
	//ab.setAttribute('data-history', 'true');
	//ab.setAttribute('data-ajax', 'false');
	_context.invoke(function(di) {
		di.getService('page').openLink(ab).then( function(){
		});
	});


}


//20.07.2022 - create invoice advance from commission
$(document).on('click','#commissionToInvoiceAdvance', function (e) {

	var items=[];
	$('.checklistgridItemsSelSelect:checked:visible').each(function( index ) {
		items[index] = $(this).data('id');
	});
	var works=[];
	$('.checklistgridWorksSelect:checked:visible').each(function( index ) {
		works[index] = $(this).data('id');
	});
	var itemsToCheck = $('.checklistgridItemsSelSelect:not(:checked):visible').length;
	var worksToCheck = $('.checklistgridWorksSelect:not(:checked):visible').length;

	var newInvoice = $(this).data('newinvoice');

	if ( ( itemsToCheck > 0 ||  worksToCheck > 0 ) && (items.length === 0  && works.length === 0 ) )
	{
		bootbox.dialog({
			message: "Nevybrali jste žádné položky ani práci, opravdu chcete pokračovat?",
			title: "Dotaz",
			buttons: {
				success: {
					label: "Ano",
					className: "btn-success",
					callback: function() {
						commissionToInvoiceAdvance(items,works,newInvoice);
					}
				},
				cancel: {
					label: "Ne",
					className: "btn-primary",
					callback: function() {

					}
				}
			}

		});
	}else{
		if (items.length  > 0 || works.length  > 0){
			commissionToInvoiceAdvance(items,works,newInvoice);
		}else{
			bootbox.dialog({
				message: "Zálohová faktura musí obsahovat položky.",
				title: "Zpráva",
				buttons: {
					cancel: {
						label: "Zavřít",
						className: "btn-primary",
						callback: function() {

						}
					}
				}

			});
		}
	}
//	   e.preventDefault();
	e.stopImmediatePropagation();
});



function commissionToInvoiceAdvance(items,works,newInvoice)
{
	$('#createInvoiceAdvanceModal').modal('hide');
	var objConfig = jQuery.parseJSON(jQuery('#commissionconfig').text());
	var url = objConfig.createInvoiceAdvance;
	$("#loading").show();
	finalUrl = url + '&dataItems='+JSON.stringify(items) + '&dataWorks='+JSON.stringify(works) + '&newInvoice='+newInvoice;
	/* $.ajax({
     url: finalUrl,
     success: function(payload) {
         setTimeout(function(){
             var ab = document.createElement('a');
             finalUrl = objConfig.redirectInvoice;
             ab.href = finalUrl + "edit/" + payload.id; //+'?do=showBsc';
             window.location.href = ab.href;
         }, 150);
     }
     });	 */


	var ab = document.createElement('a');
	finalUrl = url + '&dataItems='+JSON.stringify(items) + '&dataWorks='+JSON.stringify(works) + '&newInvoice='+newInvoice;
	ab.href = finalUrl;
	//ab.setAttribute('data-history', 'true');
	//ab.setAttribute('data-ajax', 'false');
	_context.invoke(function(di) {
		di.getService('page').openLink(ab).then( function(){
		});
	});

}




//
//konec evidence zakazek
//		

	    
