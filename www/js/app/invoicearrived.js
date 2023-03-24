/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 21.6.2016 - 14:31:06
 * 
 */



	
	//set DueDate on change invoice date
	$(document).on('blur','#frm-edit-inv_date', function(e) {	
		//console.log('inv_date change');
		setDueDate(true);
	   // e.preventDefault();
	   // e.stopImmediatePropagation();			    

	});
	
	//pdp 
	/*$(document).on('click',"#frm-edit-pdp", function(e) {
	    value = $('#frm-edit-pdp').prop('checked');
	    url = $(this).data('urlajax');		
		$.nette.ajax({
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
	});*/
	
	//find duplicity in invoice arrived , #frm-edit-price_e2_vat
	$(document).on('blur','#frm-edit-rinv_number', function(e) {
	    //var charCode = e.charCode || e.keyCode;
	    //if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode

		//set variable symbol if it is empty
		if ($('#frm-edit-var_symb').val().length == 0)
		{
		    var varSymb = $('#frm-edit-rinv_number').val();
		    $('#frm-edit-var_symb').val(varSymb.replace(/\D/g,''));
		}

		checkDuplicity();		
	    //}
		 e.preventDefault();
		 e.stopImmediatePropagation();

		


	});


function checkDuplicity()
{
	rinv_number = $('#frm-edit-rinv_number').val();
	if ($('#frm-edit-price_e2_vat').length > 0)
	{
		price_e2_vat = Number($('#frm-edit-price_e2_vat').autoNumeric('get'));
	}else{
		price_e2_vat = 0;
	}
	price_e2			= Number($('#frm-edit-price_e2').autoNumeric('get'));
	url					= $('#frm-edit-rinv_number').data('checkduplicity');
	id					= $('#frm-edit').find("[name='id']").val();
	partners_book_id	= $('#frm-edit').find("[name='cl_partners_book_id']").val();

	//var ab = document.createElement('a');
	finalUrl = url + '&check_id='+id+'&rinv_number='+rinv_number+'&price_e2_vat='+price_e2_vat+'&price_e2='+price_e2+'&partners_book_id='+partners_book_id;
	//console.log(finalUrl);
	//ab.href = finalUrl;

	$.ajax({
		url: finalUrl,
		type: 'get',
		context: this,
		dataType: 'text',
		success: function(data) {
			$("#loading").hide();
//			flash = JSON.parse(data).flashes;

			obj = JSON.parse(data).data;
			//console.log('jsme tu');

			//$('#frm-edit-inv_number').val(obj.number);
			//$('input[name=cl_number_series_id]').val(obj.id);
			//$('#frm-edit-var_symb').val(obj.number.replace(/\D/g,''));


			$('#frm-edit-price_e2_vat').removeClass("alert-danger");
			$('#frm-edit-price_e2').removeClass("alert-danger");
			$('#frm-edit-rinv_number').removeClass("alert-danger");
			$('#frm-edit-cl_partners_book_id').removeClass("alert-danger");
			$("[aria-labelledby=select2-frm-edit-cl_partners_book_id-container]").removeClass("alert-danger");

			//obj = transaction.data.response._.payload.data;
			if (obj.result == 1)
			{
				//shoda v čísle, ceně i dodavateli
				$('#frm-edit-rinv_number').addClass("alert-danger");
				if ($('#frm-edit-price_e2_vat').length > 0)
				{
					$('#frm-edit-price_e2_vat').addClass("alert-danger");
				}
				$('#frm-edit-price_e2').addClass("alert-danger");
				$("[aria-labelledby=select2-frm-edit-cl_partners_book_id-container]").addClass("alert-danger");
			}else if (obj.result == 2){
				//shoda v čísle, ceně
				$('#frm-edit-rinv_number').addClass("alert-danger");
				if ($('#frm-edit-price_e2_vat').length > 0)
				{
					$('#frm-edit-price_e2_vat').addClass("alert-danger");
				}
				$('#frm-edit-price_e2').addClass("alert-danger");
			}else if (obj.result == 3){
				//shoda v čísle a dodavateli frm-edit-cl_partners_book_id
				$('#frm-edit-rinv_number').addClass("alert-danger");
				$('#frm-edit-cl_partners_book_id').addClass("alert-danger");
				$("[aria-labelledby=select2-frm-edit-cl_partners_book_id-container]").addClass("alert-danger");
			}else if (obj.result == 4){
				//shoda v čísle
				$('#frm-edit-rinv_number').addClass("alert-danger");

			}

			//31.08.2019 - call nittro url for show correct flash message
			urlString  = $('.customUrl').data('url-showflash');
			var ab = document.createElement('a');
			ab.href = urlString+"&result="+obj.result;
			ab.setAttribute('data-history', 'false');
			_context.invoke(function(di) {
				di.getService('page').openLink(ab).then( function(){
					$("#loading").hide();
				});
			});

		}
	});


}

	//vypocty v karte faktury
		$(document).on('change','#frm-edit-price_vat1,#frm-edit-price_vat2,#frm-edit-price_vat3,' +
			'#frm-edit-price_correction, #frm-edit-price_base0, #frm-edit-price_base1, #frm-edit-price_base2, #frm-edit-price_base3,' +
			'#frm-edit-price_e2, #frm-edit-price_e2_vat', function (e) {
			if (!$('#frm-edit-recalc_disabled').prop('checked'))
				recalcInvoiceArrived(this);
			totalSum();
		});

	$(document).on('change','#frm-edit-price_total1, #frm-edit-price_total2, #frm-edit-price_total3', function (e) {
		if (!$('#frm-edit-recalc_disabled').prop('checked'))
			recalcInvoiceArrived2(this);
		totalSum();
	});

	function totalSum(){
		var totalSum_e2 = Number($('#frm-edit-price_base1').autoNumeric('get')) + Number($('#frm-edit-price_base2').autoNumeric('get'))
			+ Number($('#frm-edit-price_base3').autoNumeric('get')) + Number($('#frm-edit-price_base0').autoNumeric('get'))
			+ Number($('#frm-edit-price_correction').autoNumeric('get'));
		var totalSum_e2_vat = totalSum_e2 + Number($('#frm-edit-price_vat1').autoNumeric('get')) + Number($('#frm-edit-price_vat2').autoNumeric('get'))
			+ Number($('#frm-edit-price_vat3').autoNumeric('get'));
		$('#frm-edit-price_e2').val(totalSum_e2).autoNumeric('update');
		$('#frm-edit-price_e2_vat').val(totalSum_e2_vat).autoNumeric('update');
	}

	function recalcInvoiceArrived2(objThis) {
		if ($('#frm-edit-price_e2_vat').length > 0) {
			var base1 = Number($('#frm-edit-price_base1').autoNumeric('get'));
			var vatval1 = Number($('#frm-edit-price_vat1').autoNumeric('get'));
			//if (base1 == 0 || vatval1 == 0){
				var vat1	= getVat($('#frm-edit-price_base1'));
				base1		= $('#frm-edit-price_total1').autoNumeric('get') / (1 + vat1 / 100);
				vatval1		= base1 * vat1 / 100;
				$('#frm-edit-price_base1').val(base1).autoNumeric('update');
				$('#frm-edit-price_vat1').val(vatval1).autoNumeric('update');
			//}
			var base2 = Number($('#frm-edit-price_base2').autoNumeric('get'));
			var vatval2 = Number($('#frm-edit-price_vat2').autoNumeric('get'));
			//if (base2 == 0 || vatval2 == 0){
				var vat2	= getVat($('#frm-edit-price_base2'));
				base2		= $('#frm-edit-price_total2').autoNumeric('get') / (1 + vat2 / 100);
				vatval2	= base2 * vat2 / 100;
				$('#frm-edit-price_base2').val(base2).autoNumeric('update');
				$('#frm-edit-price_vat2').val(vatval2).autoNumeric('update');
			//}
			var base3 = Number($('#frm-edit-price_base3').autoNumeric('get'));
			var vatval3 = Number($('#frm-edit-price_vat3').autoNumeric('get'));
			//if (base3 == 0 || vatval3 == 0){
				var vat3	= getVat($('#frm-edit-price_base3'));
				base3		= $('#frm-edit-price_total3').autoNumeric('get') / (1 + vat3 / 100);
				vatval3		= base3 * vat3 / 100;
				$('#frm-edit-price_base3').val(base3).autoNumeric('update');
				$('#frm-edit-price_vat3').val(vatval3).autoNumeric('update');
			//}

			checkDuplicity();
		}
	}


	function recalcInvoiceArrived(objThis)
	{
	    var jsonData = {};
	    if ($('#frm-edit-price_e2_vat').length > 0)
	    {
			getVatVal($('#' + objThis.id));

			var total1 = Number($('#frm-edit-price_base1').autoNumeric('get')) + Number($('#frm-edit-price_vat1').autoNumeric('get'));
			var total2 = Number($('#frm-edit-price_base2').autoNumeric('get')) + Number($('#frm-edit-price_vat2').autoNumeric('get'));
			var total3 = Number($('#frm-edit-price_base3').autoNumeric('get')) + Number($('#frm-edit-price_vat3').autoNumeric('get'));

			$('#frm-edit-price_total1').val(total1).autoNumeric('update');
			$('#frm-edit-price_total2').val(total2).autoNumeric('update');
			$('#frm-edit-price_total3').val(total3).autoNumeric('update');

			jsonData.price_base1 = Number($('#frm-edit-price_base1').autoNumeric('get'));
			jsonData.price_base2 = Number($('#frm-edit-price_base2').autoNumeric('get'));
			jsonData.price_base3 = Number($('#frm-edit-price_base3').autoNumeric('get'));
			jsonData.price_total1 = Number($('#frm-edit-price_total1').autoNumeric('get'));
			jsonData.price_total2 = Number($('#frm-edit-price_total2').autoNumeric('get'));
			jsonData.price_total3 = Number($('#frm-edit-price_total3').autoNumeric('get'));
			jsonData.price_base0 = Number($('#frm-edit-price_base0').autoNumeric('get'));
			jsonData.price_vat1 = Number($('#frm-edit-price_vat1').autoNumeric('get'));
			jsonData.price_vat2 = Number($('#frm-edit-price_vat2').autoNumeric('get'));
			jsonData.price_vat3 = Number($('#frm-edit-price_vat3').autoNumeric('get'));
			jsonData.price_correction = Number($('#frm-edit-price_correction').autoNumeric('get'));
			jsonData.price_e2_vat = Number($('#frm-edit-price_e2_vat').autoNumeric('get'));
	    }
	    jsonData.price_e2 = Number($('#frm-edit-price_e2').autoNumeric('get'));	    	    

	    jsonData2 = JSON.stringify(jsonData); 
		checkDuplicity();

	}
	
	function getVatVal(obj)
	{
	    var vatValue = $(obj).data('vat');
	    var objVat = '#' + $(obj).data('vatval');
	    //if ($(objVat).val() == 0)
	    //{
		var vatCalc = $(obj).autoNumeric('get') * (vatValue/100);
		$(objVat).val(vatCalc).autoNumeric('update');

	    //}
	}

	function getVat(obj)
	{
		return $(obj).data('vat');
	}
	
	