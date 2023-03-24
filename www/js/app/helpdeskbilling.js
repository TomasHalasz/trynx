/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 05.05.2017 - 10:44:57
 * 
 */


$(document).ready(function(){
   checkAll();
});


$(document).on('click','.selectAll', function(e){
    var id = $(this).prop('id');
    var status = $(this).prop('checked');
    var objConfig = jQuery.parseJSON(jQuery('#configHelpdeskBilling').text());	
    var urlString = objConfig.urlSelectAll;
    jsonObj = [];
    $('.partner_'+id).not('.selectAll').each( function( index, element ){
	//console.log( $( this ).text() );
	item = {};
	item['id'] = $(this).prop('id');	    
	item['state'] = status;	
	jsonObj.push(item);	
    });
    //console.log(jsonObj);
    //console.log(JSON.stringify(jsonObj));

    //var urlString = $(this).data('href');
    var a = document.createElement('a');
    finalUrl = urlString+'&data='+JSON.stringify(jsonObj);
    a.href = finalUrl;
    //a.setAttribute('data-transition', transition);
    a.setAttribute('data-history', 'false');
    _context.invoke(function(di) {
	di.getService('page').openLink(a);
	//di.getService('page').one('transaction-created', function (event) {
	di.getService('snippetManager').one('after-update', function(event) {	    
				checkAll();
			});		

    });	

    /*$.ajax({
	    url: urlString,
	    type: 'get',
	    context: this,
	    data: 'data='+JSON.stringify(jsonObj),
	    dataType: 'json',
	    success: function() {
		    $("#loading").hide();
		},
	    complete: function(){
		    //console.log(status);
		    //$('#'+id).prop('checked', status);
		    checkAll();
		}
	    }); 	*/

});

$(document).on('click','.selectOne', function(e){
    var objConfig = jQuery.parseJSON(jQuery('#configHelpdeskBilling').text());	
    var urlString = objConfig.urlSelect;
    jsonObj = [];
    item = {};
   
    item['id'] = $(this).prop('id');	    
    item['state'] = $(this).prop('checked');
    jsonObj.push(item);	
    
    //var urlString = $(this).data('href');
    var a = document.createElement('a');
    finalUrl = urlString+'&data='+JSON.stringify(jsonObj);
    a.href = finalUrl;
    //a.setAttribute('data-transition', transition);
    a.setAttribute('data-history', 'false');
    _context.invoke(function(di) {
	di.getService('page').openLink(a);
	//di.getService('page').one('transaction-created', function (event) {
	di.getService('snippetManager').one('after-update', function(event) {	    
				checkAll();
			});		

    });	    
    
    /*$.ajax({
	    url: urlString,
	    type: 'get',
	    context: this,
	    data: 'data='+JSON.stringify(jsonObj),
	    dataType: 'json',
	    start: function(data) {
		$("#loading").hide();
		//console.log(data);
		},
	    complete: function(){
		    checkAll();
	    }
	    }); 	    */
});

$(document).on('click','.selectOneLine', function(e){
     var objCheckbox = $(this).parent().find('input:checkbox');
     objCheckbox.click();
});



function checkAll(){
    //check partner checkbox if all childs are checked
   $('.selectAll').each( function( index, element ){
	//console.log( $( this ).text() );
	var id = $(this).prop('id');	
	console.log($('.partner_'+id+':input:checked').length);
	console.log($('.partner_'+id).length);
	if ($('.partner_'+id+':input:checked').length === $('.partner_'+id).length)
	{
	    $(this).prop('checked',true);
	}
    });    
}


