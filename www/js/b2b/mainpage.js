$(document).on('change','#frm-search-pricelistGroupId, #frm-search-search ', function(e) {
    $('#frm-search').find('input[name=send]').click();
});

$(document).on('click','.selectcompany', function(e) {
    $finalUrl = $(this).data('href');
    $branchObj = '#' + $(this).data('brs');
    $finalUrl = $finalUrl + '&cl_partners_branch_id=' + $($branchObj).val();
    window.location.href = $finalUrl;
});
$(document).on('click', ".btn-buy", function(e){
        $quantity = $(this).parent().find('input[type="number"]').prop('value');
        $quantity = parseInt($quantity);
        if ($quantity <= 0 || isNaN($quantity)){
            bootbox.dialog({
                message: "Zadali jste chybně množství. Množství musí být číslo větší než 0.",
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
            $url = $(this).attr('href');
            $url = $url + '&quantity='+$quantity;
            //var a = document.createElement('a');
            //a.href = $url;
            //a.setAttribute('data-transition', transition);
            //a.setAttribute('data-history', 'false');
            //_context.invoke(function(di) {
            //    di.getService('page').openLink(a);
            //});
            $.ajax({
                url: $url,
                type: 'get',
                context: this,
                dataType: 'json',
                success: function(data) {
                    //$("#loading").hide();
                    $.each(data.snippets, function(i, val) {
                            $('#' + i).html(val);
                    });
                    $oldQuant = $(this).parent().parent().find('.quantity_basket').data('quantity_basket');
                    $oldUnits = $(this).parent().parent().find('.quantity_basket').data('quantity_units');
                    if ($oldQuant == "") {
                        $oldQuant = 0;
                    }
                    $newQuant = parseInt($oldQuant) + $quantity;
                    $(this).parent().parent().find('.quantity_basket').data('quantity_basket', $newQuant);
                    $(this).parent().parent().find('.quantity_basket').text($newQuant.toString() + " " + $oldUnits);
                    $.each($('.lastRow'), function(i, val){
                            $(this).removeClass('lastRow');
                    });
                    $(this).parent().parent().addClass('lastRow');

                }
            });
        }
        e.preventDefault();
        e.stopImmediatePropagation();

});

$( window ).resize(function() {
    b2bPricelistSet();
});

function b2bPricelistSet(){
    $winHeight = $(window).height();
    $('#b2b-pricelist').css('height', $winHeight - 150 );
    //$('.b2b-table').css('height', $winHeight - 150 );
}

//add to cart after enter on quantity on mainpage
$(document).on('keypress', '.toBasket[name=quantity]', function (e) {
    var charCode = e.charCode || e.keyCode;
    if (charCode  == 13 ) { //Enter, tab key's keycode|| charCode  == 9
        $(this).parent().find('.btn-buy').click();
    }
    e.stopPropagation();
});

//change quantity at basket
$(document).on('keypress', '.basket[name=quantity]', function (e) {
    var charCode = e.charCode || e.keyCode;
    if (charCode  == 13 ) { //Enter, tab key's keycode|| charCode  == 9
        $quantity = $(this).prop('value');
        $quantity = parseInt($quantity);
        var thisObject = $(this);
        if ($quantity <= 0 || isNaN($quantity)){
            bootbox.dialog({
                message: "Zadali jste chybně množství. Množství musí být číslo větší než 0.",
                title: "Varování",
                buttons: {
                    cancel: {
                        label: "Zpět",
                        className: "btn-primary",
                        callback: function() {
                            thisObject.prop('value',thisObject.data('oldval'));
                        }
                    }
                }

            });
        }else{
            $url = $(this).data('change-quantity');
            $url = $url + '&quantity='+$quantity;
            var a = document.createElement('a');
            a.href = $url;
            //a.setAttribute('data-transition', transition);
            console.log(thisObject.parent().parent().prop('id'));
            a.setAttribute('data-scroll-to', '#' + thisObject.parent().parent().prop('id'));
            a.setAttribute('data-history', 'false');
            _context.invoke(function(di) {
                di.getService('page').openLink(a);
            });
        }
        e.preventDefault();
        e.stopImmediatePropagation();
    }

});

