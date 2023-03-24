$(function() {

	// ==============
	// subscribe form
	// ==============
	$('#subscribtion-form').submit(function(){
										
		msg = 'Prosím doplňte svou správnou emailovou adresu.';
        error = 0;
        var pattern = new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
        if (!pattern.test($.trim($('#subscribtion-form input[name="email"]').val()))) {error = 1;}

        if (error){
            $('.form-popup .text').text(msg);
			$('.form-popup').fadeIn(300);
			formPopupTimeout = setTimeout(function(){$('.form-popup').fadeOut(300);}, 3000);
        }else{
            url = 'sendmail';
            email = $('#subscribtion-form input[name="email"]').val();
            $.post(url,{'email':email, 'mailto':'info@faktury.cz'},function(data){
			    $('.form-popup .text').text('Děkujeme za přihlášení. Budeme Vás informovat o novinkách. Uvidíme se brzy!');
			    $('.form-popup').fadeIn(300);
			    $('#subscribtion-form').append('<input type="reset" class="reset-button"/>');
			    $('.reset-button').click().remove();
			});
			return false;
        }
	  	return false;
	});

	$('footer .subscribe form').submit(function(){
										
		msg = 'Prosím doplňte svou správnou emailovou adresu.';
        error = 0;
        var pattern = new RegExp(/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
        if (!pattern.test($.trim($('footer .subscribe form input[type="email"]').val()))) {error = 1;}

        if (error){
            $('.form-popup .text').text(msg);
			$('.form-popup').fadeIn(300);
			formPopupTimeout = setTimeout(function(){$('.form-popup').fadeOut(300);}, 3000);
        }else{
            url = 'sendmail';
            email = $('footer .subscribe form input[type="email"]').val();
            $.post(url,{'email':email, 'mailto':'info@faktury.cz'},function(data){
			    $('.form-popup .text').text('Děkujeme za přihlášení. Budeme Vás informovat o novinkách. Uvidíme se brzy!');
			    $('.form-popup').fadeIn(300);
			    $('footer .subscribe form').append('<input type="reset" class="reset-button"/>');
			    $('.reset-button').click().remove();
			});
			return false;
        }
	  	return false;
	});

});