/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 11.6.2016 - 16:00:55
 * 
 */

	$(document).on('click','.moduleChk', function(e) {
		var inputName = $(this).data('inputname');
		var price = $(this).data('price');
		var priceId = '#' + $(this).data('priceid');
		if($(this).prop('checked'))
		{
			$('input[name=' + inputName + ']').val(1).autoNumeric('update');
			//.autoNumeric('update');
			$(priceId).val(price).autoNumeric('update');
		}else{
			$('input[name=' + inputName + ']').val('');
				//.autoNumeric('update');
		}

		//e.preventDefault();
		e.stopImmediatePropagation();
	});


	$('input.number').autoNumeric('init',{aSep: ' ', aDec: '.', mDec: '0'});


	$(document).on('change','.cuInput', function(e) {
		var chkName = $(this).data('chkname');
		var moCheck = $('input[name=' + chkName + ']');
		if (!moCheck.prop('checked') && parseFloat($(this).val()) > 0){
			moCheck.prop('checked', true);
		}
		if (moCheck.prop('checked') && parseFloat($(this).val()) <= 0){
			moCheck.prop('checked', false);
		}
	});

	$(document).ready(function(){
		$('.moduleChk').each(function(index){
			recalcOrder($(this));
		});

	});

	//events - update category price after changing cl_partners_event_method_id
	$(document).on('change','.moduleChk, .cuInput, input[name=total_duration]', function(e) {
		recalcOrder($(this));
		e.preventDefault();
		e.stopImmediatePropagation();					    
	});

	function recalcOrder(objOrder){
		var myParams = new Object();
		myParams.total_duration = $('input[name=total_duration]').val();
		if ( (myParams.total_duration / 12) % 1 > 0){
			$('input[name=total_duration]').val(Math.ceil(myParams.total_duration / 12) * 12);
		}

		myParams.total_duration = $('input[name=total_duration]').val();
		myParams.total_amount = 0;
		myParams.discount = 0;
		var totalAmount = 0;
		$('.moduleChk:checked').each(function( index ) {
			var inputName = $(this).data('inputname');
			var price = $(this).data('price');
			var price2 = $(this).data('price2');
			var priceId = '#' + $(this).data('priceid');
			var amount = price + (price2 * (parseFloat($('input[name=' + inputName + ']').val()) - 1));
			$(priceId).val(amount).autoNumeric('update');
			myParams.total_amount += amount;
		});
		$('.moduleChk:not(:checked)').each(function( index ) {
			var priceId = '#' + $(this).data('priceid');
			$(priceId).val('').autoNumeric('update');
		});
		myParams.total_amount *= parseFloat(myParams.total_duration);
		$('input[name=amount_before]').val(myParams.total_amount);
		$('input[name=amount_before]').autoNumeric('update');
		/*if (myParams.total_duration >= 12)
		{
			myParams.discount = 5;
		}*/
		/*if (myParams.total_duration >= 24)
		{
			myParams.discount = 7;
		}*/
		myParams.total_amount *= 1 - (myParams.discount/100) ;
		$('input[name=discount]').val(myParams.discount);
		$('input[name=discount]').autoNumeric('update');
		$('input[name=amount]').val(myParams.total_amount);
		$('input[name=amount]').autoNumeric('update');
		$('input[name=amount_total]').val(myParams.total_amount * 1.21);
		$('input[name=amount_total]').autoNumeric('update');
	}

