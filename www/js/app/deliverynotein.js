/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 21.6.2016 - 14:31:06
 * 
 */

	//vypocty v karte dodacího listu
	function initDeliveryNote()
	{

	}
	    //vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
	    $(document).on('blur', "#frm-deliveryNotelistgrid-editLine-quantity, #frm-deliveryNotelistgrid-editLine-price_in, #frm-deliveryNotelistgrid-editLine-vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
				lGridCalcInvoiceDNIN();
			//}
	    });
	    //vypocet celkove ceny za polozky a celkove ceny s DPH
	    //vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
	    function lGridCalcInvoiceDNIN(){
		    var quantity = parseFloat($('#frm-deliveryNotelistgrid-editLine-quantity').val().split(' ').join('').replace(',','.'));
		    var price_in = parseFloat($('#frm-deliveryNotelistgrid-editLine-price_in').val().split(' ').join('').replace(',','.'));
			var price_e2 = parseFloat($('#frm-deliveryNotelistgrid-editLine-price_e2').val().split(' ').join('').replace(',','.'));

		    if ($('#frm-deliveryNotelistgrid-editLine-vat').length>0)
				vat = parseFloat($('#frm-deliveryNotelistgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
		    else
				vat = 0;
		    


			if (price_in == 0 && price_e2 > 0)
				var price_in = Math.round((price_e2 / quantity) * 100 ) / 100;

			var price_e2 = Math.round((price_in *  quantity) * 100 ) / 100;

			var calc_in_vat = (price_in * vat / 100);
			var calc_e2_vat = (price_e2 * vat / 100);

			var price_in_vat = Math.round((price_in + calc_in_vat ) * 100 ) / 100;
			var price_e2_vat = Math.round((price_e2 + calc_e2_vat ) * 100 ) / 100;

			$('#frm-deliveryNotelistgrid-editLine-price_in').val(price_in);
			$('#frm-deliveryNotelistgrid-editLine-price_in').autoNumeric('update');
			$('#frm-deliveryNotelistgrid-editLine-price_in_vat').val(price_in_vat);
			$('#frm-deliveryNotelistgrid-editLine-price_in_vat').autoNumeric('update');

			$('#frm-deliveryNotelistgrid-editLine-price_e2').val(price_e2);
			$('#frm-deliveryNotelistgrid-editLine-price_e2').autoNumeric('update');
			$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').val(price_e2_vat);
			$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').autoNumeric('update');
			
	    }

	    //vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
	     $(document).on('blur', "#frm-deliveryNotelistgrid-editLine-price_e2_vat", function (e) {
				var quantity = parseFloat($('#frm-deliveryNotelistgrid-editLine-quantity').val().split(' ').join(''));
			 	var price_e2_vat = parseFloat($('#frm-deliveryNotelistgrid-editLine-price_e2_vat').val().split(' ').join(''));

				if ($('#frm-deliveryNotelistgrid-editLine-vat').length > 0)
					vat = $('#frm-deliveryNotelistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				var price_in_vat = Math.round((price_e2_vat / quantity) * 100 ) / 100;

				var calc_in_vat = (price_in_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );
			 	var calc_e2_vat = (price_e2_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );

				var price_in = price_in_vat - calc_in_vat;
			 	var price_e2 = price_e2_vat - calc_e2_vat;

				$('#frm-deliveryNotelistgrid-editLine-price_in').val(price_in);
				$('#frm-deliveryNotelistgrid-editLine-price_in').autoNumeric('update');
				$('#frm-deliveryNotelistgrid-editLine-price_in_vat').val(price_in_vat);
				$('#frm-deliveryNotelistgrid-editLine-price_in_vat').autoNumeric('update');
				$('#frm-deliveryNotelistgrid-editLine-price_e2').val(price_e2);
				$('#frm-deliveryNotelistgrid-editLine-price_e2').autoNumeric('update');
			 	$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').val(price_e2_vat);
			 	$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').autoNumeric('update');
			//}
	    });

			//vypocet ceny celkem s DPH, celkem bez DPH a ceny za jednotku pri zmene ceny s DPH za jednotku
			$(document).on('blur', "#frm-deliveryNotelistgrid-editLine-price_in_vat", function (e) {
				var quantity = parseFloat($('#frm-deliveryNotelistgrid-editLine-quantity').val().split(' ').join(''));
				var price_in_vat = parseFloat($('#frm-deliveryNotelistgrid-editLine-price_in_vat').val().split(' ').join(''));

				if ($('#frm-deliveryNotelistgrid-editLine-vat').length > 0)
					vat = $('#frm-deliveryNotelistgrid-editLine-vat').val().split(' ').join('');
				else
					vat = 0;

				var price_e2_vat = Math.round((price_in_vat * quantity) * 100 ) / 100;

				var calc_in_vat = (price_in_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );
				var calc_e2_vat = (price_e2_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );

				var price_in = price_in_vat - calc_in_vat;
				var price_e2 = price_e2_vat - calc_e2_vat;

				$('#frm-deliveryNotelistgrid-editLine-price_in').val(price_in);
				$('#frm-deliveryNotelistgrid-editLine-price_in').autoNumeric('update');
				$('#frm-deliveryNotelistgrid-editLine-price_in_vat').val(price_in_vat);
				$('#frm-deliveryNotelistgrid-editLine-price_in_vat').autoNumeric('update');
				$('#frm-deliveryNotelistgrid-editLine-price_e2').val(price_e2);
				$('#frm-deliveryNotelistgrid-editLine-price_e2').autoNumeric('update');
				$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').val(price_e2_vat);
				$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').autoNumeric('update');
				//}
			});


//vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH
		$(document).on('blur', "#frm-deliveryNotelistgrid-editLine-price_in,#frm-deliveryNotelistgrid-editLine-price_e2 ", function (e) {
			var quantity = parseFloat($('#frm-deliveryNotelistgrid-editLine-quantity').val().split(' ').join(''));
			var price_e2 = parseFloat($('#frm-deliveryNotelistgrid-editLine-price_e2').val().split(' ').join(''));

			if ($('#frm-deliveryNotelistgrid-editLine-vat').length > 0)
				vat = $('#frm-deliveryNotelistgrid-editLine-vat').val().split(' ').join('');
			else
				vat = 0;

			var price_in = Math.round((price_e2 / quantity) * 100 ) / 100;

			var calc_in_vat = (price_in * vat / 100);
			var calc_e2_vat = (price_e2 * vat / 100);

			var price_in_vat = Math.round((price_in + calc_in_vat ) * 100 ) / 100;
			var price_e2_vat = Math.round((price_e2 + calc_e2_vat ) * 100 ) / 100;

			$('#frm-deliveryNotelistgrid-editLine-price_in').val(price_in);
			$('#frm-deliveryNotelistgrid-editLine-price_in').autoNumeric('update');
			$('#frm-deliveryNotelistgrid-editLine-price_in_vat').val(price_in_vat);
			$('#frm-deliveryNotelistgrid-editLine-price_in_vat').autoNumeric('update');
			$('#frm-deliveryNotelistgrid-editLine-price_e2').val(price_e2);
			$('#frm-deliveryNotelistgrid-editLine-price_e2').autoNumeric('update');
			$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').val(price_e2_vat);
			$('#frm-deliveryNotelistgrid-editLine-price_e2_vat').autoNumeric('update');
			//}
		});


		//27.12.2022 - delivery note in listgrid back
		//
		//vypocty pri zmene poctu kusu, ceny za kus, slevy nebo sazby DPH
		$(document).on('blur', "#frm-deliveryNoteBacklistgrid-editLine-quantity, #frm-deliveryNoteBacklistgrid-editLine-price_e, #frm-deliveryNoteBacklistgrid-editLine-vat", function (e) {
			lGridCalcInvoiceBack();
		});
		//vypocet celkove ceny za polozky a celkove ceny s DPH
		//vola se po zmene mnozstvi, zmene ceny za jednotku, zmene slevy, zmene zisku, zmene nakupni ceny nebo zmene sazby DPH
		function lGridCalcInvoiceBack(){
			var quantity = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-quantity').val().split(' ').join('').replace(',','.'));
			var price_e = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-price_e').val().split(' ').join('').replace(',','.'));
			discount = 0;

			if ($('#frm-deliveryNoteBacklistgrid-editLine-vat').length>0)
				vat = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-vat').val().split(' ').join('').replace(',','.'));
			else
				vat = 0;

			var price_e2 = quantity * (price_e * (1-(discount/100)));
			var calc_vat = (price_e2 * vat / 100);
			var price_e2_vat = Math.round((price_e2 + calc_vat ) * 100 ) / 100;

			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2').val(price_e2);
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2').autoNumeric('update');
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').val(price_e2_vat);
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').autoNumeric('update');

		}

		//vypocet ceny celkem bez DPH a ceny za jednotku pri zmene celkove ceny s DPH
		$(document).on('blur', "#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
			var quantity = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-quantity').val().split(' ').join(''));
			var price_e2_vat = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').val().split(' ').join(''));
			if ($('#frm-deliveryNoteBacklistgrid-editLine-vat').length > 0)
				vat = $('#frm-deliveryNoteBacklistgrid-editLine-vat').val().split(' ').join('');
			else
				vat = 0;


			//var discount = parseFloat($('#frm-invoiceBacklistgrid-editLine-discount').val().split(' ').join(''));
			discount = 0;

			var calc_vat = (price_e2_vat / ( 1 + ( vat / 100 ))* ( vat / 100 ) );
			var price_e2 = price_e2_vat - calc_vat;
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2').val(price_e2);
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2').autoNumeric('update');
			price_e = (price_e2/(1-(discount/100))) / quantity;
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e').val(price_e);
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e').autoNumeric('update');
			//lGridCalcProfit();
			//}
		});
		//vypocet ceny celkem s DPH a ceny za jednotku pri zmene celkove ceny bez DPH
		$(document).on('blur', "#frm-deliveryNoteBacklistgrid-editLine-price_e2", function (e) {
			//var charCode = e.charCode || e.keyCode;
			//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode
			var quantity = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-quantity').val().split(' ').join(''));
			var price_e2 = parseFloat($('#frm-deliveryNoteBacklistgrid-editLine-price_e2').val().split(' ').join(''));
			if ($('#frm-deliveryNoteBacklistgrid-editLine-vat').length > 0)
				vat = $('#frm-deliveryNoteBacklistgrid-editLine-vat').val().split(' ').join('');
			else
				vat = 0;

			var discount = 0;
			var calc_vat = (price_e2 * ( vat / 100 ));
			var price_e2_vat = Math.round((price_e2 + calc_vat) * 100 ) / 100;
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').val(price_e2_vat);
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e2_vat').autoNumeric('update');
			price_e = (price_e2/(1-(discount/100))) / quantity;
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e').val(price_e);
			$('#frm-deliveryNoteBacklistgrid-editLine-price_e').autoNumeric('update');
			//lGridCalcProfit();
			//}
		});



/*     $(document).on('blur', "#frm-deliveryNotelistgrid-editLine-price_e2", function (e) {
            var price_e_type = $('input[name=price_e_type]').val();
            var quantity = parseFloat($('#frm-deliveryNotelistgrid-editLine-quantity').val().split(' ').join(''));
            var price_e2 = parseFloat($('#frm-deliveryNotelistgrid-editLine-price_e2').val().split(' ').join(''));
            if ($('#frm-deliveryNotelistgrid-editLine-vat').length > 0)
                vat = $('#frm-deliveryNotelistgrid-editLine-vat').val().split(' ').join('');
            else
                vat = 0;

            var discount = parseFloat($('#frm-deliveryNotelistgrid-editLine-discount').val().split(' ').join(''));
             if (isNaN(discount))
                 discount = 0;
            var calc_vat = (price_e2 * ( vat / 100 ));
            var price_e2_vat = Math.round((price_e2 + calc_vat) * 100 ) / 100;
            $('#frm-deliveryNotelistgrid-editLine-price_e2_vat').val(price_e2_vat);
            $('#frm-deliveryNotelistgrid-editLine-price_e2_vat').autoNumeric('update');
            if (price_e_type == 0)
            {
                price_e = (price_e2/(1-(discount/100))) / quantity;
            }else{
                price_e = (price_e2_vat/(1-(discount/100))) / quantity;
            }
            $('#frm-deliveryNotelistgrid-editLine-price_e').val(price_e);
            $('#frm-deliveryNotelistgrid-editLine-price_e').autoNumeric('update');
    });*/
	    //
	    //konec faktury
	    //	
	    

	    