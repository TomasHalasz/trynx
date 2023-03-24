/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 22.6.2016 - 14:06:27
 * 
 */

$(document).ready(function(){
	initLogoStampDropzone();
});

	function initLogoStampDropzone(){

	    
	    // Disabling autoDiscover, otherwise Dropzone will try to attach twice.
	    //Dropzone.autoDiscover = false;	    
	    
	    //var logoDropzone = new Dropzone("#logoDropzone", {
	    //Dropzone.options.logoDropzone = {
	    var logoDropzone = new Dropzone("#logoDropzone", {			
	      paramName: "file", // The name that will be used to transfer the file
	      maxFilesize: 4, // MB
	      acceptedFiles: "image/*",
	      dictDefaultMessage: 'Zde přetáhněte soubor s logem, nebo klikněte a vyberte jej.',
	      dictInvalidFileType: 'Soubor není jpg, bmp, png nebo gif.',
	      dictFileTooBig: 'Soubor je příliš velký. Povoleno je max. {{maxFilesize}} MB',
	      dictMaxFilesExceeded: 'Více souborů není možné nahrát',
	      accept: function(file, done) {
		if (file.name == "justinbieber.jpg") {
		  done("Naha, you don't.");
		}
		else { done(); }
	      },
	      addedfile: function(file) {
		    $("#loading").show();
	      },	      
	      error: function(file,errorMessage) {
	          $("#loading").hide();
		  alert(errorMessage);
	      },
	      success: function(file) {
			  console.log('success upload logo');
		    //refresh image
		    var tmpUrl=$('.imageLogo img').attr('src');
		    $('.imageLogo img').attr('src', tmpUrl + '?00' + new Date().getTime());
		    $('.imageLogo img').css('display','inline-block');		    
		    $('.delLogo').show()		    
		    $("#loading").hide();
	      }
	      
	    });	    
	    
	    //Dropzone.options.stampDropzone = {
		var stampDropzone = new Dropzone("#stampDropzone", {					
	      paramName: "file", // The name that will be used to transfer the file
	      maxFilesize: 4, // MB
	      acceptedFiles: "image/*",
	      //addRemoveLinks: true,
	      dictDefaultMessage: 'Zde přetáhněte soubor s razítkem, nebo klikněte a vyberte jej.',
	      dictInvalidFileType: 'Soubor není jpg, bmp, png nebo gif.',
	      dictFileTooBig: 'Soubor je příliš velký. Povoleno je max. {{maxFilesize}} MB',
	      dictMaxFilesExceeded: 'Více souborů není možné nahrát',
	      addedfile: function(file) {
		    $("#loading").show();
	      },	      
	      error: function(file,errorMessage) {
	          $("#loading").hide();
		  alert(errorMessage);
	      },
	      success: function(file) {
		    //refresh image
		    var tmpUrl=$('.imageStamp img').attr('src');
		    $('.imageStamp img').attr('src', tmpUrl + '&11' + new Date().getTime());
		    $('.imageStamp img').css('display','inline-block');  		    
		    $('.delStamp').show()
		    $("#loading").hide();
	      }
	      
	    });

	    //$('.dropzone').dropzone();

	}
	
	
	$(document).on('click','#aresLink', function(){
		var icoInput = $('[data-ares="ico_input"]');
		var url = $(this).data('href');
		var myParams = new Object();	
		myParams.ico = icoInput.val();
		//getAres(myParams.ico);
		
		$.getJSON(url, myParams, function (data) {
				$('input[name=DIC]').val(data.tin);
				$('input[name=platce_dph]').val(data.vat_pay);
				$('input[name=city]').val(data.city);
				$('input[name=street]').val(data.street + " " + data.house_number);
				$('input[name=zip]').val(data.zip);
				$('input[name=name]').val(data.company);
				$('select[name=cl_countries_id]').val(data.cl_countries_id);				
				$('select[name=cl_countries_id]').trigger("change");
				$('textarea[name=obch_rejstrik]').val("Spisová značka: " + data.court_all);
			}); 						
		
	});
	