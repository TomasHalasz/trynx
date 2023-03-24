/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 17.6.2016 - 11:25:56
 * 
 */


//27.06.2019 - insert staff to training
$(document).on('click','#insertStaff', function (e) {

    var itemsSel=[];
    $('.checklistgridStaffSelect:checked:visible').each(function( index ) {
        itemsSel[index] = $(this).data('id');
    });
    var items=[];
    $('.checklistgridItemsSelect:checked:visible').each(function( index ) {
        items[index] = $(this).data('id');
    });

    var itemsSelToCheck = $('.checklistgridItemsSelSelect:not(:checked):visible').length;
    var itemsToCheck = $('.checklistgridItemsSelSelect:not(:checked):visible').length;

    if ( ( itemsToCheck > 0 || itemsSelToCheck > 0 ) && (items.length === 0 && itemsSel.length === 0) )
    {
        bootbox.dialog({
            message: "Nevybrali jste žádné položky, není možné pokračovat.",
            title: "Varování",
            buttons: {
                cancel: {
                    label: "Zpět",
                    className: "btn-primary",
                    callback: function() {

                    }
                }
            }

        });
    }else{
        if ( items.length > 0 || itemsSel.length > 0 ){
            commissionToStoreOutUpdate(itemsSel,items);
        }else{
            bootbox.dialog({
                message: "Výdejka musí obsahovat položky.",
                title: "Zpráva",
                buttons: {
                    cancel: {
                        label: "Zavřít",
                        className: "btn-primary",
                        callback: function() {

                        }
                    }
                }

            });
        }

    }
//	   e.preventDefault();
    e.stopImmediatePropagation();
});



function commissionToStoreOutUpdate(itemsSel, items)
{
    var objConfig = jQuery.parseJSON(jQuery('#trainingconfig').text());
    var url = objConfig.insertStaff;

    finalUrl = url + '&dataItemsSel='+JSON.stringify(itemsSel) + '&dataItems='+JSON.stringify(items);
    //$.ajax({
	//url: finalUrl,
	//success: function(payload) {
	    //console.log(payload.tax);
//	    setTimeout(function(){
		    //var ab = document.createElement('a');
		    //finalUrl = objConfig.redirectInsertStaff;
		    //ab.href = finalUrl;
                //+ "default/" + payload.id+'?do=showBsc';
		    //a.setAttribute('data-transition', transition);
		    //ab.setAttribute('data-history', 'true');
		    //ab.setAttribute('data-ajax', 'false');
		    //_context.invoke(function(di) {
			//di.getService('page').openLink(ab).then( function(){ 
			//});
		    //});		    
		    //window.location.href = ab.href;
//		}, 150);
	//}
    //});

    var ab = document.createElement('a');
    //finalUrl = objConfig.redirectStore;
    ab.href = finalUrl;
    //a.setAttribute('data-transition', transition);
    ab.setAttribute('data-history', 'false');
    ab.setAttribute('data-ajax', 'true');
    _context.invoke(function(di) {
        di.getService('page').openLink(ab).then( function(){
        });
    });
    //window.location.href = ab.href;
    $('#createStaffSelectModal').modal('hide');
}
