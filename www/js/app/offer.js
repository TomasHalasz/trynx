/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */



//change of validity_days
$(document).on('change','#frm-edit-validity_days', function(e) {
    days = parseInt($(this).val());
    startDate = $('#frm-edit-offer_date').val().split(" ");
    dateParts = startDate[0].split(".");
    dateS = new Date(dateParts[2]+"-"+dateParts[1]+"-"+dateParts[0]);    
    

    var newdate = new Date(dateS);
    newdate.setDate(newdate.getDate() + days);
    
    var dd = newdate.getDate();
    if (dd<10) dd="0"+dd;
	
    var mm = newdate.getMonth() + 1;
    if (mm<10) mm="0"+mm;
    
    var y = newdate.getFullYear();
    var formattedDate = dd + '.' + mm + 'm' + y;
    
    $('#frm-edit-validity_date').val(formattedDate);
});


$(document).on('blur','#frm-edit-validity_date', function(e) {
    startDate = $('#frm-edit-offer_date').val().split(" ");
    dateParts = startDate[0].split(".");
    dateS = new Date(dateParts[2]+"-"+dateParts[1]+"-"+dateParts[0]);        
    endDate = $(this).val().split(" ");
    dateParts = endDate[0].split(".");
    dateE = new Date(dateParts[2]+"-"+dateParts[1]+"-"+dateParts[0]);            
    
    days = (dateE-dateS)/1000/60/60/24;
    
    $('#frm-edit-validity_days').val(days);
});

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
    $(document).on('blur','#frm-listgrid-editLine-profit, #frm-listgrid-editLine-price_s', function (e) {
	    //var charCode = e.charCode || e.keyCode;
	    var price_e_type = $('input[name=price_e_type]').val();						
	    //if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
		    var profit = parseFloat($('#frm-listgrid-editLine-profit').val().split(' ').join('').replace(',','.'));
		    var price_s = parseFloat($('#frm-listgrid-editLine-price_s').val().split(' ').join('').replace(',','.'));
		    if ($('#frm-listgrid-editLine-vat').length>0)
			    var vat = parseFloat($('#frm-listgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
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
			    $('#frm-listgrid-editLine-price_e').val(price_e);
			    $('#frm-listgrid-editLine-price_e').autoNumeric('update');
			    lGridCalc();
		    }
	    //}
});	    

$(document).on('blur',"#frm-listgrid-editLine-price_s", function (e) {
	    //var charCode = e.charCode || e.keyCode;
	    //if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
		    lGridCalcProfit();
	    //}
});	    	    

//vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
$(document).on('blur',"#frm-listgrid-editLine-quantity, #frm-listgrid-editLine-price_e, #frm-listgrid-editLine-discount, #frm-listgrid-editLine-vat", function (e) {
	    //var charCode = e.charCode || e.keyCode;
	    //if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
		    lGridCalc();
		    lGridCalcProfit();
		    //e.preventDefault();
		    //var inputs = $(this).closest('form').find(':input:visible:not([readonly])');
		    //inputs.eq( inputs.index(this)+ 1 ).select().focus();				
	    //}
});
//vypocet celkove ceny za polozky a celkove ceny s DPH
//vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
function lGridCalc(){
	var price_e_type = parseFloat($('input[name=price_e_type]').val());
	var quantity = parseFloat($('#frm-listgrid-editLine-quantity').val().split(' ').join('').replace(',','.'));
	var price_e = parseFloat($('#frm-listgrid-editLine-price_e').val().split(' ').join('').replace(',','.'));
	var discount = parseFloat($('#frm-listgrid-editLine-discount').val().split(' ').join('').replace(',','.'));
	if (isNaN(discount))
		discount = 0;

	if ($('#frm-listgrid-editLine-vat').length>0)
		    vat = parseFloat($('#frm-listgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
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
	    $('#frm-listgrid-editLine-price_e2').val(price_e2);
	    $('#frm-listgrid-editLine-price_e2').autoNumeric('update');				
	    $('#frm-listgrid-editLine-price_e2_vat').val(price_e2_vat);
	    $('#frm-listgrid-editLine-price_e2_vat').autoNumeric('update');				


	//var price_e2 = $('#frm-listgrid-editLine-price_e2').val().split(' ').join('').replace(',','.');

}
//vypocet zisku
function lGridCalcProfit(){
	    var price_e_type = $('input[name=price_e_type]').val();			
	price_s = parseFloat($('#frm-listgrid-editLine-price_s').val().split(' ').join('').replace(',','.'));
	if (price_s > 0)
	{
		    price_e = parseFloat($('#frm-listgrid-editLine-price_e').val().split(' ').join('').replace(',','.'));

		    if ($('#frm-listgrid-editLine-vat').length>0)
			    vat = parseFloat($('#frm-listgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
		    else
			    vat = 0;


		    if (price_e_type == 0)
		    {//cena za jednotku je bez DPH
			    var profit = ((price_e / price_s)-1)*100;
		    }else{//cena za jednotku je s DPH
			    var calcVat = (price_e / (1 + (vat / 100)) * (vat / 100));
			    var profit = (((price_e - calcVat) / price_s)-1)*100;
		    }


		    $('#frm-listgrid-editLine-profit').val(profit);
		    $('#frm-listgrid-editLine-profit').autoNumeric('update');
	}		    		
}
//vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
$(document).on('blur', "#frm-listgrid-editLine-price_e2_vat", function (e) {
	    var price_e_type = $('input[name=price_e_type]').val();						
	    //var charCode = e.charCode || e.keyCode;
	    //if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
		    var quantity = parseFloat($('#frm-listgrid-editLine-quantity').val().split(' ').join(''));
		    var price_e2_vat = parseFloat($('#frm-listgrid-editLine-price_e2_vat').val().split(' ').join(''));
		    if ($('#frm-listgrid-editLine-vat').length>0)
			    vat = parseFloat($('#frm-listgrid-editLine-vat').val().split(' ').join(''));
		    else
			    vat = 0;

		    var discount = parseFloat($('#frm-listgrid-editLine-discount').val().split(' ').join(''));
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
		    $('#frm-listgrid-editLine-price_e2').val(price_e2);
		    $('#frm-listgrid-editLine-price_e2').autoNumeric('update');
		    $('#frm-listgrid-editLine-price_e').val(price_e);
		    $('#frm-listgrid-editLine-price_e').autoNumeric('update');
		    lGridCalcProfit();
	    //}
});	    
//vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH	    
$(document).on('blur',"#frm-listgrid-editLine-price_e2", function (e) {
	    var price_e_type = $('input[name=price_e_type]').val();			
	    //var charCode = e.charCode || e.keyCode;
	    //if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
		    var quantity = parseFloat($('#frm-listgrid-editLine-quantity').val().split(' ').join(''));
		    var price_e2 = parseFloat($('#frm-listgrid-editLine-price_e2').val().split(' ').join(''));
		    if ($('#frm-listgrid-editLine-vat').length>0)
			    var vat = parseFloat($('#frm-listgrid-editLine-vat').val().split(' ').join(''));
		    else
			    var vat = 0;

		    var discount = parseFloat($('#frm-listgrid-editLine-discount').val().split(' ').join(''));
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
		    $('#frm-listgrid-editLine-price_e2_vat').val(price_e2_vat);
		    $('#frm-listgrid-editLine-price_e2_vat').autoNumeric('update');
		    $('#frm-listgrid-editLine-price_e').val(price_e);
		    $('#frm-listgrid-editLine-price_e').autoNumeric('update');
		    lGridCalcProfit();
	    //}
});

//25.08.2018 - create commission from offer
$(document).on('click','#offerToCommission', function (e) {
    
    var items=[];
    $('.checklistgridItemsSelect:checked:visible').each(function( index ) {
	items[index] = $(this).data('id');
      });
    var tasks=[];
    $('.checklistgridTasksSelect:checked:visible').each(function( index ) {
	tasks[index] = $(this).data('id');
      });      
    var itemsToCheck = $('.checklistgridItemsSelect:visible:not(:checked)').length;
    var tasksToCheck = $('.checklistgridTasksSelect:visible:not(:checked)').length;
    
    if ( ( itemsToCheck > 0 ||  tasksToCheck > 0 ) && (items.length === 0  && tasks.length === 0 ) )
    {
		bootbox.dialog({
			message: "Nevybrali jste žádné položky ani práci, opravdu chcete pokračovat?",
			title: "Dotaz",
			buttons: {
				  success: {
					label: "Ano",
					className: "btn-success",
					callback: function() {
						offerToCommission(items,tasks);
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
		offerToCommission(items,tasks);
    }
//	   e.preventDefault();
    e.stopImmediatePropagation();	
});



function offerToCommission(items,tasks)
{
    var objConfig = jQuery.parseJSON(jQuery('#offerconfig').text());	
    var url = objConfig.createCommission;
	$("#loading").show();
    finalUrl = url + '&dataItems='+JSON.stringify(items) + '&dataTasks='+JSON.stringify(tasks);
    $.ajax({
	url: finalUrl,
	success: function(payload) {
	    //console.log(payload.tax);
	    setTimeout(function(){
		    var ab = document.createElement('a');
		    finalUrl = objConfig.redirectCommission;
		    ab.href = finalUrl + "edit/" + payload.id; //+'?do=showBsc';
		    //a.setAttribute('data-transition', transition);
		    //ab.setAttribute('data-history', 'true');
		    //ab.setAttribute('data-ajax', 'false');
		    //_context.invoke(function(di) {
			//di.getService('page').openLink(ab).then( function(){ 
			//});
		    //});		    
		    window.location.href = ab.href;
		}, 150)			    
	}
    });	    
    $('#createCommissionModal').modal('hide');    
}


//04.03.2019 - create invoice from offer
$(document).on('click','#offerToInvoice', function (e) {
    
    var items=[];
    $('.checklistgridItemsSelect:checked:visible').each(function( index ) {
	items[index] = $(this).data('id');
      });
    var tasks=[];
    $('.checklistgridTasksSelect:checked:visible').each(function( index ) {
	tasks[index] = $(this).data('id');
      });      
    var itemsToCheck = $('.checklistgridItemsSelect:not(:checked):visible').length;
    var tasksToCheck = $('.checklistgridTasksSelect:not(:checked):visible').length;
    
    if ( ( itemsToCheck > 0 ||  tasksToCheck > 0 ) && (items.length === 0  && tasks.length === 0 ) )
    {
	bootbox.dialog({
		message: "Nevybrali jste žádné položky ani práci, opravdu chcete pokračovat?",
		title: "Dotaz",
		buttons: {
			  success: {
				label: "Ano",
				className: "btn-success",
				callback: function() {
				    offerToInvoice(items,tasks);					
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
	offerToInvoice(items,tasks);
    }
//	   e.preventDefault();
    e.stopImmediatePropagation();	
});



function offerToInvoice(items,tasks)
{
    var objConfig = jQuery.parseJSON(jQuery('#offerconfig').text());	
    var url = objConfig.createInvoice;
	$("#loading").show();
    finalUrl = url + '&dataItems='+JSON.stringify(items) + '&dataTasks='+JSON.stringify(tasks);
    $.ajax({
	url: finalUrl,
	success: function(payload) {
	    //console.log(payload.tax);
	    setTimeout(function(){
		    var ab = document.createElement('a');
		    finalUrl = objConfig.redirectInvoice;
		    ab.href = finalUrl + "edit/" + payload.id; //+'?do=showBsc';
		    //a.setAttribute('data-transition', transition);
		    //ab.setAttribute('data-history', 'true');
		    //ab.setAttribute('data-ajax', 'false');
		    //_context.invoke(function(di) {
			//di.getService('page').openLink(ab).then( function(){ 
			//});
		    //});		    
		    window.location.href = ab.href;
		}, 150);
	}
    });	    
    $('#createInvoiceModal').modal('hide');    
}
