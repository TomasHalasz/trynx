/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 14.6.2016 - 11:24:29
 * 
 */
$(document).ready(function(){
/*    if ($('.imageDropzone').length > 0)
    {    
        if (typeof imageDropzone == 'undefined' )
        {
	    var imageDropzone;
	    //&& imageDropzone.hasOwnProperty('dropzone') == false
            initImagesDropzone();
        }
    }
    if ($('.filedropzone').length > 0)
    {    
	if (typeof fileDropzone == 'undefined' )
	{
	    var fileDropzone;
	    //&& fileDropzone.hasOwnProperty('dropzone') == false
	    initFilesDropzone();
	}
    }
  */
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
	fileDropzone = new Dropzone("#fileDropzone", {
	  paramName: "file", // The name that will be used to transfer the file
	  maxFilesize: 50, // MB
	  acceptedFiles: "image/*,video/*,.msg,.eml,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.csv,.xml,text/xml,text/csv,application/pdf,text/plain,application/msword,application/excel,application/x-compressed",
	  dictDefaultMessage: 'Zde klikněte, nebo sem přetáhněte soubor, který chcete přidat.',
	  dictInvalidFileType: 'Soubor není obrázek, pdf, dokument, tabulka nebo komprimovaný soubor',
	  dictFileTooBig: 'Soubor je příliš velký. Povoleno je max. {{maxFilesize}} MB',
	  dictMaxFilesExceeded: 'Více souborů není možné nahrát',
	  accept: function(file, done) {
	if (file.name == "justinbieber.jpg") {
	  done("Naha, you don't.");
	}
	else { done(); }
	  },
	  addedfile: function(file) {
		//17.08.2018 - we can try decode qr code only if we are in invoice-arrived
		
		if ($('#qr-canvas').length > 0 && (file.type.substring(0,5) == 'image' || file.type == 'application/pdf')){
		    readPDF(file);
		}
		$("#loading").show();
	  },	      
	  error: function(file,errorMessage) {
		  $("#loading").hide();
			alert(errorMessage);
	  },
	  success: function(file,payload) {
		if (payload.snippets) {
		for (var i in payload.snippets) {
			$('#'+i).html(payload.snippets[i]);
		}
		$("#loading").hide();
		//$.nette.load();
		}


	  }

	});	    


	//$('.dropzone').dropzone();

}	

function initImagesDropzone(){

	// Disabling autoDiscover, otherwise Dropzone will try to attach twice.
	//Dropzone.autoDiscover = false;	    
	//alert('ted');
	//var logoDropzone = new Dropzone("#logoDropzone", {
	//Dropzone.options.sfileDropzone = {
	if ($('#imageDropzone').length == 0)
	{
		return;
	}
	imageDropzone = new Dropzone("#imageDropzone", {
	  paramName: "file", // The name that will be used to transfer the file
	  maxFilesize: 4, // MB
	  acceptedFiles: "image/*",
	  dictDefaultMessage: 'Zde klikněte, nebo sem přetáhněte obrázek, který chcete přidat.',
	  dictInvalidFileType: 'Soubor není obrázek',
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
	  success: function(file,payload) {
		if (payload.snippets) {
		for (var i in payload.snippets) {
			$('#'+i).html(payload.snippets[i]);
		}
		$("#loading").hide();
		//$.nette.load();
		}


	  }

	});	    


	//$('.dropzone').dropzone();

}	