/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 21.6.2016 - 14:31:06
 * 
 */




	
	//set DueDate on change invoice date
	$(document).on('blur','#frm-edit-inv_date', function(e) {	
		//console.log('inv_date change');
		setDueDate(false);
	    //e.preventDefault();
	    e.stopImmediatePropagation();

	});
	//$('#frm-edit-inv_date').datetimepicker({ 
	//	onChangeDateTime:function(dp,$input){
	//		console.log('ted');
	//		setDueDate(false);
	//	    .preventDefault();
	//		e.stopImmediatePropagation();			    			
	//	}
	//  });
	
	//pdp 
	$(document).on('click',"#frm-edit-pdp", function(e) {
	    value = $('#frm-edit-pdp').prop('checked');
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
		//e.preventDefault();
		e.stopImmediatePropagation();
	});

	//export
	$(document).on('click',"#frm-edit-export", function(e) {
		value = $('#frm-edit-export').prop('checked');
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
		//e.preventDefault();
		e.stopImmediatePropagation();
	});


	//vypocty v karte faktury
	function initInvoice()
	{


            
	    $(document).on('change','#inv_header_show', function(e) {
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
	    $(document).on('change','#inv_footer_show', function(e) {
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
	    
	    //zmena druhu faktury  = zmena ciselne rady
		//19.10.2019 - not used, type of invoice is choosen in time when created
		// Store the current value on focus and on change
		/*$(document).on('select2:opening', '#frm-edit-cl_invoice_types_id', function (e) {
			$('#frm-edit-cl_invoice_types_id').data('previous', $('#frm-edit-cl_invoice_types_id').val());
		});

		$(document).on('select2:select', '#frm-edit-cl_invoice_types_id', function (e) {
			bootbox.dialog({
				message: "Opravdu chcete změnit typ dokladu?",
				title: "Dotaz",
				buttons: {
					success: {
						label: "Ano",
						className: "btn-success",
						callback: function() {

							var urlString  = $('#frm-edit-cl_invoice_types_id').data('urlajax');
							$.ajax({
								url: urlString,
								type: 'get',
								context: this,
								data: 'cl_invoice_types_id=' + $('#frm-edit-cl_invoice_types_id').val(),
								dataType: 'text',
								success: function(data) {
									obj = JSON.parse(data);
									$('#frm-edit-inv_number').val(obj.number);
									$('input[name=cl_number_series_id]').val(obj.id);
									$('#frm-edit-var_symb').val(obj.number.replace(/\D/g,''));
									//$('#gridSetBox').show();
								}
							});

						}
					},
					cancel: {
						label: "Ne",
						className: "btn-primary",
						callback: function() {
							$('#frm-edit-cl_invoice_types_id').val($('#frm-edit-cl_invoice_types_id').data('previous'));
							$('#frm-edit-cl_invoice_types_id').select2();
							e.preventDefault();
						}
					}
				}

			});
			//e.preventDefault();
			e.stopImmediatePropagation();
			});
		*/
		}

	    //Tomas Halasz
	    //faktura a jeji obsa
	    //

	    //vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
	    $(document).on('blur', "#frm-invoicelistgrid-editLine-quantity, #frm-invoicelistgrid-editLine-price_e, #frm-invoicelistgrid-editLine-discount, #frm-invoicelistgrid-editLine-vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalcInvoice();
			//}
	    });
		$(document).on('select2:select','#frm-invoicelistgrid-editLine-vat', function(e) {
			lGridCalcInvoice();
		});

	    //vypocet celkove ceny za polozky a celkove ceny s DPH
	    //vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
	    function lGridCalcInvoice(){
			var price_e_type = $('input[name=price_e_type]').val();
			if ($('#frm-invoicelistgrid-editLine-quantity').length > 0)
		    	var quantity = parseFloat($('#frm-invoicelistgrid-editLine-quantity').val().split(' ').join('').replace(',','.'));
			else
				var quantity = 1;

			if ($('#frm-invoicelistgrid-editLine-price_e').length>0)
		    	var price_e = parseFloat($('#frm-invoicelistgrid-editLine-price_e').val().split(' ').join('').replace(',','.'));
			else
				var price_e = parseFloat($('#frm-invoicelistgrid-editLine-price_e2').val().split(' ').join('').replace(',','.'));

			if ($('#frm-invoicelistgrid-editLine-discount').length>0)
		    	var discount = parseFloat($('#frm-invoicelistgrid-editLine-discount').val().split(' ').join('').replace(',','.'));
			else
				var discount = 0;

			if (isNaN(discount))
				discount = 0;

		    if ($('#frm-invoicelistgrid-editLine-vat').length>0)
				vat = parseFloat($('#frm-invoicelistgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
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
			$('#frm-invoicelistgrid-editLine-price_e2').val(price_e2);
			$('#frm-invoicelistgrid-editLine-price_e2').autoNumeric('update');							
			$('#frm-invoicelistgrid-editLine-price_e2_vat').val(price_e2_vat);
			$('#frm-invoicelistgrid-editLine-price_e2_vat').autoNumeric('update');				
			
	    }

	    //vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
	     $(document).on('blur', "#frm-invoicelistgrid-editLine-price_e2_vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();
			 	if ($('#frm-invoicelistgrid-editLine-quantity').length > 0)
					var quantity = parseFloat($('#frm-invoicelistgrid-editLine-quantity').val().split(' ').join(''));
				else
					var quantity = 1;

			 	var price_e2_vat = parseFloat($('#frm-invoicelistgrid-editLine-price_e2_vat').val().split(' ').join(''));
				if ($('#frm-invoicelistgrid-editLine-vat').length > 0)
					vat = $('#frm-invoicelistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

			 	if ($('#frm-invoicelistgrid-editLine-discount').length > 0)
					var discount = parseFloat($('#frm-invoicelistgrid-editLine-discount').val().split(' ').join(''));
				else
					var discount = 0;

				 if (isNaN(discount))
					 discount = 0;
				var calc_vat = (price_e2_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );
				var price_e2 = price_e2_vat - calc_vat;
				$('#frm-invoicelistgrid-editLine-price_e2').val(price_e2);
				$('#frm-invoicelistgrid-editLine-price_e2').autoNumeric('update');
				if (price_e_type == 0)
				{				
					price_e = (price_e2/(1-(discount/100))) / quantity;					

				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
			 	if ($('#frm-invoicelistgrid-editLine-price_e').length > 0) {
					$('#frm-invoicelistgrid-editLine-price_e').val(price_e);
					$('#frm-invoicelistgrid-editLine-price_e').autoNumeric('update');
				}
				//lGridCalcProfit();
			//}
	    });	    
	    //vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH	    
	     $(document).on('blur', "#frm-invoicelistgrid-editLine-price_e2", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();

				if ($('#frm-invoicelistgrid-editLine-quantity').length > 0)
					var quantity = parseFloat($('#frm-invoicelistgrid-editLine-quantity').val().split(' ').join(''));
				else
					var quantity = 1;

				var price_e2 = parseFloat($('#frm-invoicelistgrid-editLine-price_e2').val().split(' ').join(''));

				if ($('#frm-invoicelistgrid-editLine-vat').length > 0)
					vat = $('#frm-invoicelistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				if ($('#frm-invoicelistgrid-editLine-discount').length > 0)
					var discount = parseFloat($('#frm-invoicelistgrid-editLine-discount').val().split(' ').join(''));
				else
					var discount = 0;

				 if (isNaN(discount))
					 discount = 0;
				var calc_vat = (price_e2 * ( vat / 100 ));		    
				var price_e2_vat = Math.round((price_e2 + calc_vat) * 100 ) / 100;		    
				$('#frm-invoicelistgrid-editLine-price_e2_vat').val(price_e2_vat);
				$('#frm-invoicelistgrid-editLine-price_e2_vat').autoNumeric('update');

				if (price_e_type == 0)
				{								
					price_e = (price_e2/(1-(discount/100))) / quantity;					
				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
			 	if ($('#frm-invoicelistgrid-editLine-price_e').length > 0) {
					$('#frm-invoicelistgrid-editLine-price_e').val(price_e);
					$('#frm-invoicelistgrid-editLine-price_e').autoNumeric('update');
				}
				//lGridCalcProfit();
			//}
	    });	    	    
	    //
	    //konec faktury
	    //	
	    
	    
	    //20.11.2018 - invoice listgrid back
	    //
	    //vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
	    $(document).on('blur', "#frm-invoiceBacklistgrid-editLine-quantity, #frm-invoiceBacklistgrid-editLine-price_e, #frm-invoiceBacklistgrid-editLine-discount, #frm-invoiceBacklistgrid-editLine-vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalcInvoiceBack();
			//}
	    });

		$(document).on('select2:select','#frm-invoiceBacklistgrid-editLine-vat', function(e) {
			lGridCalcInvoiceBack();
		});

	    //vypocet celkove ceny za polozky a celkove ceny s DPH
	    //vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
	    function lGridCalcInvoiceBack(){
			var price_e_type = $('input[name=price_e_type]').val();
		    var quantity = parseFloat($('#frm-invoiceBacklistgrid-editLine-quantity').val().split(' ').join('').replace(',','.'));
		    var price_e = parseFloat($('#frm-invoiceBacklistgrid-editLine-price_e').val().split(' ').join('').replace(',','.'));
		   // var discount = parseFloat($('#frm-invoiceBacklistgrid-editLine-discount').val().split(' ').join('').replace(',','.'));
		    discount = 0;


		    if ($('#frm-invoiceBacklistgrid-editLine-vat').length>0)
				vat = parseFloat($('#frm-invoiceBacklistgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
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
			$('#frm-invoiceBacklistgrid-editLine-price_e2').val(price_e2);
			$('#frm-invoiceBacklistgrid-editLine-price_e2').autoNumeric('update');							
			$('#frm-invoiceBacklistgrid-editLine-price_e2_vat').val(price_e2_vat);
			$('#frm-invoiceBacklistgrid-editLine-price_e2_vat').autoNumeric('update');				
			
	    }

	    //vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
	     $(document).on('blur', "#frm-invoiceBacklistgrid-editLine-price_e2_vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();				
				var quantity = parseFloat($('#frm-invoiceBacklistgrid-editLine-quantity').val().split(' ').join(''));
				var price_e2_vat = parseFloat($('#frm-invoiceBacklistgrid-editLine-price_e2_vat').val().split(' ').join(''));
				if ($('#frm-invoiceBacklistgrid-editLine-vat').length > 0)
					vat = $('#frm-invoiceBacklistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;


				//var discount = parseFloat($('#frm-invoiceBacklistgrid-editLine-discount').val().split(' ').join(''));
				discount = 0;
				
				var calc_vat = (price_e2_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );
				var price_e2 = price_e2_vat - calc_vat;
				$('#frm-invoiceBacklistgrid-editLine-price_e2').val(price_e2);
				$('#frm-invoiceBacklistgrid-editLine-price_e2').autoNumeric('update');
				if (price_e_type == 0)
				{				
					price_e = (price_e2/(1-(discount/100))) / quantity;					

				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
				$('#frm-invoiceBacklistgrid-editLine-price_e').val(price_e);
				$('#frm-invoiceBacklistgrid-editLine-price_e').autoNumeric('update');				
				//lGridCalcProfit();
			//}
	    });	    
	    //vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH	    
	     $(document).on('blur', "#frm-invoiceBacklistgrid-editLine-price_e2", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				var price_e_type = $('input[name=price_e_type]').val();								
				var quantity = parseFloat($('#frm-invoiceBacklistgrid-editLine-quantity').val().split(' ').join(''));
				var price_e2 = parseFloat($('#frm-invoiceBacklistgrid-editLine-price_e2').val().split(' ').join(''));
				if ($('#frm-invoiceBacklistgrid-editLine-vat').length > 0)
					vat = $('#frm-invoiceBacklistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				//var discount = parseFloat($('#frm-invoiceBacklistgrid-editLine-discount').val().split(' ').join(''));
				var discount = 0;
				var calc_vat = (price_e2 * ( vat / 100 ));		    
				var price_e2_vat = Math.round((price_e2 + calc_vat) * 100 ) / 100;		    
				$('#frm-invoiceBacklistgrid-editLine-price_e2_vat').val(price_e2_vat);
				$('#frm-invoiceBacklistgrid-editLine-price_e2_vat').autoNumeric('update');
				if (price_e_type == 0)
				{								
					price_e = (price_e2/(1-(discount/100))) / quantity;					
				}else{
					price_e = (price_e2_vat/(1-(discount/100))) / quantity;					
				}
				$('#frm-invoiceBacklistgrid-editLine-price_e').val(price_e);
				$('#frm-invoiceBacklistgrid-editLine-price_e').autoNumeric('update');				
				//lGridCalcProfit();
			//}
	    });

$(document).on('select2:select','#frm-edit-cl_currencies_id,#frm-edit-cl_bank_accounts_id', function(e) {
	var urlString  	= $(this).data('urlajaxaccount');
	var type  		= $(this).data('type');

	$.ajax({
		url: urlString,
		type: 'get',
		context: this,
		data: 'idData=' + $(this).val(),
		dataType: 'json',
		success: function(data) {
			if (data['error'])
			{
				console.log('chyba: ' + data['error']);
			}
			if (data['type'] == 'account') {
				$('#frm-edit-cl_bank_accounts_id').val(data['id']);
				$('#frm-edit-cl_bank_accounts_id').trigger('change');
			}
			if (data['type'] == 'currency') {
				$('#frm-edit-cl_currencies_id').val(data['id']);
				$('#frm-edit-cl_currencies_id').trigger('change');
				$('#frm-edit-cl_currencies_id').trigger({
					type: 'select2:select'});
			}
			//console.log(data);
			$("#loading").hide();
		}
	});
});