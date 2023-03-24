/*
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 21.6.2016 - 9:53:06
 * 
 */
console.log('performance.js loaded');

//20.12.2018 - previous active control before click and show textsUse
var previousActiveElement = null;

$(document).on("mousedown",".showTextsUse", function (e) {
    previousActiveElement = document.activeElement;
});

//11.07.2019 - instant search after enter on search input
$(document).on('keypress', '#frm-userFilter [name=filterValue], [name=searchTxt]', function (e) {
    //$('#frm-userFilter [name=filterValue]').closest('form').find('input[type=submit]').click();
    var charCode = e.charCode || e.keyCode;
    if (charCode  == 13 ) { //Enter, tab key's keycode|| charCode  == 9
        //$(this).closest('form').send();  //15.7.2019 TH - I now that this gives error, but this works.....
        $(this).closest('form').find('[name="send"]').click(); //22.11.2019 TH - this works without error
    }
    e.stopPropagation();
});

//13.07.2019 - close modal on escape
window.closeModal = function(){
    $('.modal').modal('hide');
};
//$(document).on('keypress', window, function (e){
$(window).on('keydown', function (e){
    if (e.keyCode == 27)
        window.parent.closeModal();
});

//17.07.2018 - trumbowyg save
$(document).on("click",".trumbowyg-save", function (e) {
    var idName = $(this).data('id');
    var urlString = $(this).data('url-ajax');
    var data = $('#'+idName).trumbowyg('html');
    $('#'+idName).val(data);

    
    data = encodeURIComponent(data);
    var cmpName = $(this).data('cmpname');

    var a = document.createElement('a');
    if (cmpName.length > 0){
	finalUrl = urlString + "&" + cmpName + '-html=' + data;
    }else{
	finalUrl = urlString + "&html=" + data;
    }
    a.href = finalUrl;
    //a.setAttribute('data-transition', transition);
    a.setAttribute('data-history', 'false');
    a.setAttribute('data-scroll-to', 'false');
    _context.invoke(function(di) {
	di.getService('page').openLink(a);
    });	
    
});

//bootstrap Tabs function with hack of choozen wrong work on hidden elements
$(document).on('click', '#myTabs a, #myTabs2 a',function (e) {
    e.preventDefault();
    $(this).tab('show');
    $('.chzn-select').select2('destroy');
    //$('.chzn-select').select2();
    initChznSelectMy();
    //initExtensions();	  
});

$(document).on('click', '.flowingMy', function (e){
    $url = $(this).data('hover-url');
    $value = 0; //normal 1-sumsHover(hide)
    if ($(this).hasClass('sumsHover'))
    {
        //show and set to normal view
        $('#expandable-to-12').addClass('col-lg-10');
        $(this).removeClass('sumsHover');
        $(this).css('width', '');
        $(this).find('.panel-body.nopaddingMy').show('100');
        $value = 0;
    }else {
        //hide and show in absolute
        $preWidth = parseInt($(this).css('width')) - 12;
        $('#expandable-to-12').removeClass('col-lg-10');
        $(this).addClass('sumsHover');

        $(this).css('width', $preWidth+'px');
        $(this).find('.panel-body.nopaddingMy').hide('100');
        $value = 1;
    }
    $.ajax({
        url: $url,
        type: 'get',
        context: this,
        data: 'value='+$value,
        success: function(data) {
            //$("#loading").hide();
            //$('#gridSetBox').show();
        }
    });
})


  
$(window).scroll(function()
{
       //flowingMy();
    //if ($('#selector').has($(e.target)).length){
    //do what you want here
   //}
});

//for bscchild summary fixing on top during scrolling
function flowingMy()
{
   // return;
	    $( ".flowingMy" ).each(function( index ) {
		elementTop = $('.bscContainer').offset().top;
		windowTop = $(window).scrollTop();
		if (windowTop > elementTop ){
		    elementLeft = $('.flowingMy').offset().left;
		    elementWidth = $('.flowingMy').css('width');
		    $(this).addClass('flowingMyFixed');
		    $(this).css('left', elementLeft + 'px');
		    $(this).css('width', elementWidth);
		}else{
		    $(this).removeClass('flowingMyFixed');
		    $(this).css('left', '0px');
		    $(this).css('top', '0px');
		    //console.log('remove fixed');
	      }
	    });	        
}

//turn off dotted border on clicked checkbox
$(document).on('click', 'input[type=checkbox]', function(e) {
	    $(this).blur();
  });

	
$(document).on("focus","input[type='text']", function (e) {

    text = $(this);
    if ((text.prop('selectionStart') - text.prop('selectionEnd')) === 0){
        $(this).select();
    }

	//e.preventDefault();
	//e.stopImmediatePropagation();			    
});	    
// Chrome workaround Bind an event handler to the "mouseup" JavaScript event
//$(document).on("mouseup", "input[type='text']", function (e) {
	// Default mouseup action is prevented
//	e.preventDefault();
//	e.stopImmediatePropagation();			    
//});	   

//click on line checks input type checkbox in listgrid mode=select
$(document).on('click','.columnlistGridCheck', function (e){
	$(this).parent().find('.listGridCheck').click();
    });
$(document).on('click','.columnlistGridCheckNL', function (e){
	$(this).parent().prev().find('.listGridCheck').click();
    });    
$(document).on('click','.listGridCheckAll', function (e){
	//$(this).parent().parent().parent().parent().find('.listGridCheck').click();
	$(this).parent().parent().parent().parent().find('.listGridCheck').prop('checked', $(this).prop('checked'));
    });
    

$(document).on("click", ".pricelistinsert", function (e){
//        e.preventDefault();

    
   /* $.ajax({
            url: urlString,
            type: 'get',
            context: this,
            data: cmpName + '-cl_pricelist_id=' + data,
            dataType: 'json',
            complete: function() {
                //$('#gridSetBox').show();
                        initExtensions();
                        $("#loading").hide();
                        //alert('tedt');
                        $('.listgrid input[type="text"]:not([readonly])').first().select().focus();

                }
            }); 	*/

    var urlString = $(this).data('url-ajax');
    var data = $(this).parent().parent().find('.select2Pricelist').val();
    var cmpName = $(this).data('cmpname');

    var a = document.createElement('a');
    finalUrl = urlString + "&"+cmpName + '-cl_pricelist_id=' + data;
    //window.location.href = finalUrl;
    a.href = finalUrl;
    //a.setAttribute('data-transition', transition);
    //a.setAttribute('data-ajax', 'false');
    a.setAttribute('data-history', 'false');
    a.setAttribute('data-scroll-to', '.openedEditLine');
    _context.invoke(function(di) {
	di.getService('page').openLink(a).then(function(payload) {
        // spustí se vzápětí po "update" události služby Page
        //console.log('nitro update2');
        // scroll to end
        $('.table-responsive').animate({
            scrollTop: 10000
        }, 10);

        }, function(err) {
            // chyba
            console.log('nitro error');
        });
    });
    e.preventDefault();		        
	    
	    
  //  e.stopImmediatePropagation();	                            
    
});

//29.03.2019 - users rules 

    $(document).on('click','.panel-heading.mySH', function (e) {
	tmpObject = $(this).next('.panel-body:not(.all):visible');
	if (tmpObject.length > 0)
	{	
	    tmpObject.fadeOut();
	}else{
	    $(this).next('.panel-body:not(.all):hidden').fadeIn();
	}
	e.stopPropagation();
	e.preventDefault();
	
    });
    $(document).on('click','#expandAll', function (e) {    
	$('.panel-body').find('.panel-body:not(.all)').fadeIn();	
	e.preventDefault();
	e.stopPropagation();
    });


function erase_confirm($parentObj, $mess, $title, $confirm, $confirmClass, $storno, $stornoClass, $className){
    $("#loading").hide();

    bootbox.dialog({
        message: $mess,
        title: $title,
        className: $className,
        buttons: {
            confirm: {
                label: $confirm,
                className: $confirmClass,
                callback: function() {
                    var a = document.createElement('a');
                    a.href = $parentObj.data('href')
                    $dataScrollTo = $parentObj.data('scroll-to');
                    if (typeof(dataScrollTo) != 'undefined') {
                        a.setAttribute('data-scroll-to', $dataScrollTo)
                    }
                    //a.setAttribute('data-transition', transition);
                    a.setAttribute('data-history', 'false');
                    _context.invoke(function(di) {
                        di.getService('page').openLink(a);
                    });

                }
            },
            cancel: {
                label: $storno,
                className: $stornoClass,
                callback: function() {
                    return;
                }
            }
        }
    });
}

function renameFilter($id, $name, $parentObj){
    bootbox.prompt({
        size: "small",
        className: "",
        inputType: "text",
        title: "Přejmenování filtru",
        value: $name,
        buttons: {
            confirm: {
                label: "Použít",
                className: "btn-success"
            },
            cancel: {
                label: "Zpět",
                className: "btn-primary"
            }
        },
        callback: function(result){
            //console.log(result);
            if (result != null) {
                var a = document.createElement('a');
                a.href = $parentObj.data('href');
                if (a.href.indexOf('?') > 0) {
                    a.href = a.href + '&filterId=' + $id + '&name=' + result;
                }
                $dataScrollTo = $parentObj.data('scroll-to');
                if (typeof (dataScrollTo) != 'undefined') {
                    a.setAttribute('data-scroll-to', $dataScrollTo)
                }
                //a.setAttribute('data-transition', transition);
                a.setAttribute('data-history', 'false');
                _context.invoke(function (di) {
                    di.getService('page').openLink(a);
                });
            }
        }

    });
}


//}
