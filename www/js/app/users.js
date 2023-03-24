/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 17.8.2016 - 8:33:19
 * 
 */

$(document).ready(function(){
	initImageDropzone();
});

	function initImageDropzone(){

	    
	    // Disabling autoDiscover, otherwise Dropzone will try to attach twice.
	    //Dropzone.autoDiscover = false;	    
	    
	    //var logoDropzone = new Dropzone("#logoDropzone", {
	    //Dropzone.options.logoDropzone = {
	    var imageDropzone = new Dropzone("#imageDropzone", {			
	      paramName: "file", // The name that will be used to transfer the file
	      maxFilesize: 1, // MB
	      acceptedFiles: "image/*",
	      dictDefaultMessage: 'Zde přetáhněte soubor s fotografií, nebo klikněte a vyberte jej.',
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

	    
	    var imageDropzone = new Dropzone("#stampDropzone", {			
	      paramName: "file", // The name that will be used to transfer the file
	      maxFilesize: 1, // MB
	      acceptedFiles: "image/*",
	      dictDefaultMessage: 'Zde přetáhněte soubor s razítkem, nebo klikněte a vyberte jej.',
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
		    var tmpUrl=$('.imageStamp img').attr('src');
		    $('.imageStamp img').attr('src', tmpUrl + '?00' + new Date().getTime());
		    $('.imageStamp img').css('display','inline-block');		    
		    $('.delLogo').show()		    
		    $("#loading").hide();
	      }
	      
	    });	    	    
	    

	}
	