/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 22.6.2016 - 13:07:57
 * 
 */



	//$(document).on('keydown', "#frm-orderlistgrid-editLine-price_e2_vat", function (e) {	    	
	$(document).on('click', '.kdb-title', function (e) {
		id = $(this).data('id');
		urlString = $(this).data('href');
		if ($('#actions'+id).css('display') != 'none')
		{
			$('#actions'+id).hide('fast');
			$('#descr'+id).hide('fast');
			$('#descr'+id).html('');
			$('#kdb-panel'+id).hide('fast');
		}else{
			$.ajax({
			  url: urlString,
				  type: 'get',
				  context: this,
				  dataType: 'json',
				  off: ['unique'],
				  start: function(){
					$("#loading").hide();
					},					  
				  success: function(data) {
					  $('#descr'+id).html(data['description']);
					  $('#descr'+id).show('fast');
					  $('#actions'+id).show('fast');
					  $('#kdb-panel'+id).show('fast');					  
				  }			  
			  });		  		
		}
  });
  $(document).on('blur','#frm-search-searchTxt', function (e) {
		//var charCode = e.charCode || e.keyCode;
		//if (charCode  == 13) { //Enter key's keycode
			$('#frm-search input[name="send"]').click();
			//}
	    });	  	    

/*	$(document).on('click', '.kdb-list', function (e) {
		urlString = $(this).data('href');

			$.ajax({
			  url: urlString,
				  type: 'get',
				  context: this,
				  dataType: 'json',
				  off: ['unique'],
				  start: function(){
					$("#loading").hide();
					},					  
				  success: function(data) {
				  }			  
			  });		  		
		  e.preventDefault();
		  e.stopImmediatePropagation();			  

  });*/
	
