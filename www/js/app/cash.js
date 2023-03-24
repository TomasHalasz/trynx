/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 17.6.2016 - 11:25:56
 * 
 */


	//vypocty v pokladna



	    //change of vat rate
	    $(document).on('change','#frm-edit-vat', function(e) {
			recalcVat();

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
//konec pokladny
//		

	    
