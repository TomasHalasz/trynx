/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 14.6.2016 - 11:24:29
 * 
 */
$(document).ready(function(){
	initFilesDropzone();
});

function initFilesDropzone(){

	// Disabling autoDiscover, otherwise Dropzone will try to attach twice.
	//Dropzone.autoDiscover = false;	    
	//alert('ted');
	//var logoDropzone = new Dropzone("#logoDropzone", {
	//Dropzone.options.sfileDropzone = {
	if ($('#fileDropzone').length == 0)
	{
		return;
	}
	var fileDropzone = new Dropzone("#fileDropzone", {
	  paramName: "file", // The name that will be used to transfer the file
	  maxFilesize: 10, // MB
	  acceptedFiles: "image/*",
	  dictDefaultMessage: 'Zde přetáhněte obrázky',
	  dictInvalidFileType: 'Soubor není obrázek',
	  dictFileTooBig: 'Soubor je příliš velký. Povoleno je max. {{maxFilesize}} MB',
	  dictMaxFilesExceeded: 'Více souborů není možné nahrát',
	  accept: function(file, done) {
		if (file.name == "justinbieber.jpg") {
		  done("Naha, you don't.");
		}
		else { done(); }
		  },	      
	  error: function(file,errorMessage) {
			alert(errorMessage);
	  },
	  complete: function(file) {
		this.removeFile(file);
	  },
	  success: function(file,payload) {
			if (payload.snippets) {
				for (var i in payload.snippets) {
					$('#'+i).html(payload.snippets[i]);
				}
				$("#loading").hide();
				$.nette.load();
			}
	  }

	});	    


	//$('.dropzone').dropzone();

}	
