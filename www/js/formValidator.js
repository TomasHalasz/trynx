/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(function () {
	$('#frm-settingsHelpdeskForm-email_income').on('blur', function(e){
			var urlString = $(this).data('urlstring');
			var valueToCheck = $(this).val();
			if (valueToCheck.indexOf('@') > -1  && valueToCheck.indexOf('.') > -1)
			{
			$.ajax({
				url: urlString,
				    type: 'get',
				    context: this,
				    data: 'email=' + valueToCheck ,
				    dataType: 'json',
				    success: function(data) {
						$(this).popover('destroy');	
						//alert(data['result']);
						var myObj = $(this);
						if (data['result'])
							{
							timeoutID = window.setTimeout(function(e){
								myObj.popover({content: 'Zadaný email nelze použít, již je obsazen.',placement: 'bottom', toggle: 'popover'});	    
								myObj.popover('show');	
								myObj.toggleClass('form-control-error',true);						    
							}, 250);						

							}else{
								timeoutID = window.setTimeout(function(e){
									myObj.popover('destroy');
									myObj.toggleClass('form-control-error',false);
								}, 250);												

							}
					}
				    }); 
			}else{
			    //if (valueToCheck.length>0)
//				$(this).popover('destroy');
			}
	});
});

