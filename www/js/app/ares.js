/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 5.7.2016 - 10:18:37
 * 
 */


function getAres(ico)
{
		urlAres = "http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?ico=" + ico;
		//alert(urlAres);
		$.nette.ajax({
			url: urlAres,
			    type: 'get',
			    context: this,
			    dataType: 'json',
				start: function(){
					//$("#loading").hide();
				},
			    success: function(data) {
					alert('ted');
					}
			    }); 			
		

}