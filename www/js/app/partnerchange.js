/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 22.6.2016 - 10:12:18
 * 
 */


	inv_date_counter = 0;
	
	//set due date and spec. symbol
	function setDueDate(force)
	{
		inv_date_counter++;
		console.log(inv_date_counter);
		if (inv_date_counter > 1)
		{
		    //return;
		}
		
		urlString  = $('.customUrl').data('url-duedate');
		if ($('#frm-edit-inv_date').length > 0) {
			data = $('#frm-edit-inv_date').val();
		}
		if ($('#frm-edit-issue_date').length > 0) {
			data = $('#frm-edit-issue_date').val();
		}

		//$("#loading").show();		
		$.ajax({
			    url: urlString,
			    type: 'get',
			    context: this,
			    data: 'invdate=' + data,
			    dataType: 'json',
			    start: function(){
					$("#loading").show();
			    },
			    success: function(data) {
					$("#loading").hide();
					if ( (data['due_date'] != $('#frm-edit-due_date').val() && force == false))
					{
						//console.log('bootbox start');
						bootbox.dialog({
							message: "Opravdu chcete změnit datum splatnosti?",
							title: "Dotaz",
							buttons: {
								  success: {
									label: "Změnit",
									className: "btn-success",
									callback: function() {
										$('#frm-edit-due_date').val(data['due_date']);
										inv_date_counter = 0;
										//done();
									}
								},
								 cancel: {
									label: "Nechat tak",
									className: "btn-primary",
									callback: function() {
										inv_date_counter = 0;
									}
								  }
							}	
							
						});						
						
					}else {
					    //11.08.2018 - updating due date only when we are working otherelse then invoice-arrived
						if (window.urlString != undefined) {
							actualUrl = window.urlString;
							if (actualUrl.indexOf('invoice-arrived') == -1) {
								$('#frm-edit-due_date').val(data['due_date']);
							}
						}
					}
					//console.log('cl_payment_types_id: ' + data['cl_payment_types_id']);
					$('#frm-edit-cl_payment_types_id').val(data['cl_payment_types_id']);
					$('#frm-edit-cl_payment_types_id').trigger('change');
					$('#frm-edit-spec_symb').val(data['spec_symb']);
					if ($('#frm-edit-inv_memo').length>0)
					{
					    $('#frm-edit-inv_memo').val(data['inv_memo']);
					}

					//$('#gridSetBox').show();
				}
			}); 	    	    
	}