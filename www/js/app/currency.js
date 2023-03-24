/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 22.6.2016 - 10:03:03
 * 
 */
		$(document).on('change','#frm-edit-vat_date, #frm-edit-invoice_date', function(e){
			$('#frm-edit-cl_currencies_id').trigger({
				type: 'select2:select'})
		});

	    //get currency rate on change select
	    $(document).on('select2:select','#frm-edit-cl_currencies_id', function(e) {
			/*if (e.originalEvent) {
				// user-triggered event
			}
			else {
				// code-triggered event
				//return;
			}*/
		var urlString  = $(this).data('urlajax');
			//console.log('currency change');
			$obj = $('#frm-edit-vat_date');
			if ($obj.length == 0){
				$obj = $('#frm-edit-invoice_date');
			}
			if ($obj.length == 0){
				$workDate = "";
			}else{
				$workDate = $obj.val();
			}

		    $.ajax({
			    url: urlString,
				type: 'get',
				context: this,
				data: 'idCurrency=' + $(this).val() + '&date=' + $workDate,
				dataType: 'json',
				success: function(data) {
				    if (data != $('#frm-edit-currency_rate').val())
				    {
						previous = $('#frm-edit-currency_rate').val();
						$('#frm-edit-currency_rate').val(data);						
						//recalcQuestion(data,previous);						

				    }

				    $("#loading").hide();
				    }
				}); 
	    });
/*	    var previous;
	    $(document).on('focus','#frm-edit-currency_rate',  function () {
			// Store the current value on focus and on change
				previous = this.value;
				console.log(previous);
			}).on('change','#frm-edit-currency_rate', function(e) {
				if (this.value == 0)
				{
					bootbox.dialog({
						message: "Kurz nemůže být 0",
						title: "Pozor",
						buttons: {
							  success: {
								label: "Ok",
								className: "btn-success",
								callback: function() {
									$('#frm-edit-currency_rate').val(previous);
								}
							}
						}
					});
					$(this).val(1);
				}else{
					recalcQuestion($(this).val(),previous);
				}

	    });		   

	    


	function recalcQuestion(curval,previous)
	{

		bootbox.dialog({
			message: "Změna kurzu, přepočítat položky?",
			title: "Dotaz",
			buttons: {
				  success: {
					label: "Přepočítat",
					className: "btn-success",
					callback: function() {
						recalcCall(curval,previous,1);
					}
				},
				 cancel: {
					label: "Nechat tak",
					className: "btn-primary",
					callback: function() {
						recalcCall(curval,previous,0);
					}
				  }
			}					
		});		
	}

	function recalcCall(curval,previous,recalc)
	{
		var urlString2  = $('#frm-edit-currency_rate').data('urlrecalc');
		$("#loading").show();
		$.ajax({
				url: urlString2,
				type: 'get',
				data: 'idCurrency=' + $('#frm-edit-cl_currencies_id').val() + '&rate=' + curval + '&oldrate=' + previous + '&recalc=' + recalc,
				dataType: 'json',
				success: function(data) {
					$("#loading").hide();
			}});							
	}
*/	