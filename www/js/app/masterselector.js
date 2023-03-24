$(document).on('click','#PaymentOrder', function (e) {
    var items=[];
    var cmpName="";
    $('.listGridCheck:checked:visible').each(function( index ) {
        items[index] = $(this).data('id');
        cmpName = $(this).data('cmpname');
    });
    var itemsToCheck = $('.listGridCheck:not(:checked):visible').length;
    if ( ( itemsToCheck > 0 ) && (items.length === 0 ) )
    {
        bootbox.dialog({
            message: "Nevybrali jste žádné faktury. Platební příkaz není možné vytvořit.",
            title: "Varování",
            buttons: {
                cancel: {
                    label: "Ok",
                    className: "btn-primary",
                    callback: function() {
                    }
                }
            }
        });
    }else{
        paymentOrder(items, cmpName);
    }
//	   e.preventDefault();
    e.stopImmediatePropagation();
});

function paymentOrder(items, cmpName)
{
    var objConfig = jQuery.parseJSON(jQuery('#'+cmpName+'-configMasterSelector').text());
    var url = objConfig.insertItems;
    var objConfigMain = jQuery.parseJSON(jQuery('#configMain').text());
    var urlFlash = objConfigMain.showFlashNow;
    finalUrl = url + '&'+cmpName+'-name='+cmpName + '&'+cmpName+'-dataItems='+JSON.stringify(items) ;
    $("#loading").show();
    $.ajax({
        url: finalUrl,
        success: function(payload) {
                if (payload.snippets) {
                    for (var i in payload.snippets) {
                        $('#' + i).html(payload.snippets[i]);
                    }
                }
                    setTimeout(function(){
                var ab = document.createElement('a');
                    $("#loading").hide();
                    //21.01.2021 - call nittro url for show correct flash message
                    var ab = document.createElement('a');
                    strData = JSON.stringify(payload.status);
                    ab.href = urlFlash + '&arrData=' + strData;

                    ab.setAttribute('data-history', 'false');
                    _context.invoke(function(di) {
                        di.getService('page').openLink(ab).then( function(){
                            $("#loading").hide();
                        });
                    });
            }, 150);
        }
    });
    //$('#invoiceArrivedselectorModal').modal('hide');
    $('.masterselector').modal('hide');
}