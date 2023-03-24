/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 12.6.2016 - 13:29:55
 * 
 */

$(document).ready(function(){
	//spinner for bootstrap3
	    $('.spinner input, .spinner-sm input').each(function( index ) {
	      //console.log( index + ": " + $( this ).text() );
			if ($(this).prop('readonly') == true)
			{
				 $(this).next('.input-group-btn-vertical').prop('style','opacity: 0.5;z-index:100');
				 $(document).on('click','.spinner .btn, .spinner-sm .btn', function(e) {
				e.preventDefault(); // prevents default
					return false; // also prvents default (i like to add both but just the e.preventDefault is required :) )		    
				 });
			 }
	    });	
		$('.spinner button, .spinner-sm button').prop('tabindex',-1) //disable tabindex on buttons
		     
		$(document).on('click','.spinner .btn:first-of-type, .spinner-sm .btn:first-of-type', function(e) {
		  incVal = $(this).parent().parent().data( "incStep" );
		  maxVal = $(this).parent().parent().data( "incMax" );
		  if (incVal == null)
		  {
			incVal = 1;
		  }
		  //alert(incVal);
		  //curVal = parseInt(jQuery('.spinner input').val(), 10);
		  curVal = parseInt($(this).parent().parent().find('input').val(), 10)  || 0;

		  newVal = curVal + incVal;

		  if (curVal < maxVal || maxVal == null)
		  {
			//jQuery('.spinner input').val( newVal);
			$(this).parent().parent().find('input').val(newVal);
			$(this).parent().parent().find('input').change();
		  }
		  $(this).blur();
		  e.preventDefault(); // prevents default
		  return false; // also prvents default (i like to add both but just the e.preventDefault is required :) )
		});
		
		$(document).on('click','.spinner .btn:last-of-type, .spinner-sm .btn:last-of-type', function(e) {
		  incVal = $(this).parent().parent().data( "incStep" );
		  minVal = $(this).parent().parent().data( "incMin" );	    
		  if (incVal == null)
		  {
			  incVal = 1;
		  }	      
		  //curVal = parseInt(jQuery('.spinner input').val(), 10);
		  curVal = parseInt($(this).parent().parent().find('input').val(), 10);
		  newVal = curVal - incVal;
		  if (curVal > minVal || minVal == null)
		  {
			//jQuery('.spinner input').val( newVal);
			$(this).parent().parent().find('input').val(newVal);		
			$(this).parent().parent().find('input').change();			
		  }	    
		  $(this).blur();
		  e.preventDefault(); // prevents default
		  return false; // also prvents default (i like to add both but just the e.preventDefault is required :) )
		});    
	});

		     