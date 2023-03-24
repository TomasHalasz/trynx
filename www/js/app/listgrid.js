/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 14.6.2016 - 13:40:22
 * 
 */

$('document').ready(function(){
    if(window.jQuery)
    {
		listgridInit();
		sortableDef();
		fixedHeader();
		resizableHeader();
		resizableTable();
		var objConfigMain = jQuery.parseJSON(jQuery('#configMain').text());
		if ( objConfigMain.enableAutoPaging == 1)
			dataScroll();

    }
});

function listgridInit(){
		listgridCounts(); //write records counter from listgrid to joined tabpanel

	    //open editLine url
	    $(document).on("click",'.openEditLine', function(e) {
		    if($(this).data('url') !== undefined){
			e.preventDefault();
			e.stopImmediatePropagation();
			//$("#loading").show();
			url = $(this).data('url');
			$.ajax({
				url: url,
				type: 'get',
				context: this,
				dataType: 'json',
				success: function(data) {
				    $("#loading").hide();
				    },
				complete: function(data) {
					$('.listgrid input[type="text"]:not([readonly])').first().select().focus(); 					
					//sortableDef();
					//initExtensions();
				    }					
				}); 			
		    }
	    });	
		
		$(document).ajaxComplete(function() {
			//sortování listgridu po provedeném ajaxu, který mohl vyvolat update snippetu
			//02.02.2019 - vypnuto, kvůli nittru už se nepoužívá
			//sortableDef();
			//fixedHeader();
			
		});



	$(document).on('click', '#chkAll', function(e) {
		//e.preventDefault();
		e.stopImmediatePropagation();
		$('[id*=chkrow]').prop('checked',$(this).prop('checked'));
		sendSelectedRec($(this), true);
	});

	$(document).on('click', '.chkrow', function(e) {
		e.stopImmediatePropagation();
		sendSelectedRec($(this));
	});

	$(document).on('click', '#frm-groupActionsBtn', function(e) {
		e.stopImmediatePropagation();
		//e.preventDefault();
		$("#loading").hide();
		bootbox.dialog({
			message: 'Opravdu chcete spustit vybranou hromadnou akci?',
			title: 'Hromadná akce - dotaz',
			className: 'bbErase',
			buttons: {
				confirm: {
					label: 'Ano - spustit',
					className: 'btn-danger',
					callback: function() {
							$('#frm-groupActions input[type="submit"]').click();
					}
				},
				cancel: {
					label: 'Ne - nespouštět',
					className: 'btn-primary',
					callback: function() {
						return;
					}
				}
			}
		});
	});
}



function sendSelectedRec($obj, $all = false){
	if($obj.data('urlajax') !== undefined){

		url = $obj.data('urlajax');
		var $objSet = new Object();
		$objSet.checked = new Object();
		$objSet.notchecked = new Object();
		//if ($all) {
			var $objChecked = new Object();
			var $objNotChecked = new Object();
			$(".chkrow:checked").each(function (i, val) {
				console.log($(this).data('id'));
				$objChecked[$(this).data('id')] = $(this).data('id');
			});
			$(".chkrow:not(:checked)").each(function (i, val) {
				$objNotChecked[$(this).data('id')] = $(this).data('id');
			});
			$objSet.checked = $objChecked;
			$objSet.notchecked = $objNotChecked;
/*		}else{
			if ($obj.prop('checked')) {
				$objSet.checked[$obj.data('id')] = $obj.data('id');
			}else{
				$objSet.notchecked[$obj.data('id')] = $obj.data('id');
			}*/
		//}
		//console.log($objSet.checked);
		//alert('ted');
		/*if (Object.keys($objSet.checked).length == 0){
			bootbox.dialog({
				message: "Nevybrali jste žádné záznamy, není možné pokračovat.",
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
		}else {*/
			$.ajax({
				url: url,
				type: 'get',
				data: 'data=' + JSON.stringify($objSet),
				context: this,
				dataType: 'json',
				success: function (data) {
					$("#loading").hide();
				},
				complete: function (data) {
					//$('.listgrid input[type="text"]:not([readonly])').first().select().focus();
				}
			});
		//}
	}
}


	//set focus or better click on submit button after selecting from .select2Pricelist
	$(document).on('select2:select','.select2Pricelist', function(e) {
		//console.log('selected from select2pricelist');
		$(this).parent().next().find('.btn.pricelistinsert:visible').click();

	});

	//last active row in listgrid to bee selected
	$(document).on("click",".openEdit2", function () {
	    $(this).parent().parent().find('tr').each(function() {
			$(this).removeClass('lastActive');
	    });
	    $(this).parent().addClass('lastActive');
		//moveToCard();
	});

	//move down to card BSC
	function moveToCard(){
		//return;
		if ($('#snippet--bsc-child').length > 0) {
			posY = parseFloat($('#snippet--bsc-child').offset().top);
			lastActive = parseFloat($('#snippet--bsc-child').position().top);
			var rowpos = lastActive - 10;
			console.log('moveToCard now');
			if (rowpos > 0) {
				$('html, body').animate({
					scrollTop: rowpos
				},500);
			}
		}
	}

	//push out button for resize grid
	/*$(document).on("mouseover","#resizable-bottom", function () {
		//console.log('teda');
		var posY = $('#resizable-bottom').data('bottom');
		if (posY == undefined)
		{
		    posY = parseFloat($('#resizable-bottom').css('bottom'));
		    $('#resizable-bottom').data('bottom', posY);
		}
		var posYCurr = parseFloat($('#resizable-bottom').css('bottom'));
		if (posY == posYCurr)
		{
		    $('.table-wraper').css('overflow-y','auto');
		    $('.table-wraper').css('overflow-x','hidden');
		    var posY =  parseFloat($('#resizable-bottom').data('bottom')) + 25;
		    $('#resizable-bottom').animate({
			bottom: posY
			},400
			);
		}
	});
*/

	//set showed property on mouse click. It is start of dragging, so we don't hide resizable-bottom till the mouse button is released
/*	$(document).on("mousedown","#resizable-bottom", function (e) {
	    $('#resizable-bottom').data('showed',1);
	});
*/

/*
	//mouse button is released, we can unset showed property
	$(document).on("mouseup","#resizable-bottom", function (e) {
	    var obj = $('#resizable-bottom');
	    obj.data('showed',0);
	    hideResizableBottom(obj);	    
	    
	});	
*/
/*
	//push in button for resize grid
	$(document).on("mouseleave","#resizable-bottom", function (e) {
		var obj = $('#resizable-bottom');
		hideResizableBottom(obj);
	});
*/	
	function hideResizableBottom(obj)
	{
		var posY =  parseFloat(obj.data('bottom'));
		var posYCurr =  parseFloat(obj.css('bottom'));
		var showed = obj.data('showed');
		if (showed == undefined)
		{		
		    obj.data('showed',0);		    
		    showed = 0;
		}
		if (posY != posYCurr && showed == 0)
		{
		    $('#resizable-bottom').animate({
			bottom: posY
			}, { duration: 400 ,
			complete: function() {
			    $('.table-wraper').css('overflow-y','auto');
			    $('.table-wraper').css('overflow-x','auto');
			}
			}
			);
		}	    
	}


	function fixedHeader(){
	//fixed header
	//return;
	    var table = $('#baseListTable');
	    table.floatThead({
		scrollContainer: function(table){
		    return table.closest('.table-wraper');
		}
	    });	  
	    /*var $table = $('#baseListTable');
	    $table.floatThead({
		scrollContainer: function($table){
		    return $table.closest('.table-wraper');
		}
	    });*/	  	    
	    
	    
	}	
	//$('#th-cm_title').width(200)
	//$table.trigger('reflow')
	// {
	//		    'e,w': '.resizable-th-left'
	//	}
	function resizableHeader(){
	    $('.resizable-th').resizable({
		handles:'e',
		start: function( evetn, ui) {
		    $('.floatThead-container').css('height','100%');
		    $('.resizable-th .ui-resizable-handle').css('height','600px');
		    
		},
		stop: function( event, ui ) { 
		    $('.floatThead-container').css('height','auto');
		    $('.resizable-th .ui-resizable-handle').css('height','40px');
		    var diff = ui.originalSize.width - ui.size.width;
		    //console.log(diff);
		    var original = $(ui.element).parent().width();
		    var newSize = original - diff ;
		    $(ui.element).parent().width(newSize);
		    var $table = $('#baseListTable');
		    $table.trigger('reflow');
		    //save new size

		    var url = $('#baseListTable').data('url-save-header');
		    var a = document.createElement('a');
		    var thId = $(ui.element).parent().attr('id');
		    var tableName = $(ui.element).closest('.main-table-wraper').data('table-name');
		    finalUrl = url + "&table_name="+tableName+"&header_id="+thId+"&size="+newSize;
		    a.href = finalUrl;
		    //a.setAttribute('data-transition', transition);
		    a.setAttribute('data-history', 'false');
		    _context.invoke(function(di) {
			di.getService('page').openLink(a);
		    });		    
		    
		}
	    });	
	}
	function resizableTable(){
	    $('.table-wraper').resizable({
		handles:{
		    's': '#resizable-bottom',
		    'n': '#resizable-bottom'
		},
		stop: function (event, ui){
		    var newHeight = ui.size.height;
		    //23.03.2020 maxheight have to be set to height of my_table= height of table with data
			//it's necessarily for correct function of loading table content on demand
			maxHeight = $('.table-wraper').find('#snippet--my_table').height();
			if (newHeight > maxHeight){
				newHeight = maxHeight;
				$('.table-wraper').height(maxHeight);
			}
		    var url = $('#baseListTable').data('url-save-tableheight');
		    var a = document.createElement('a');
		    var tableName = $(ui.element).closest('.main-table-wraper').data('table-name');
		    finalUrl = url + "&table_name="+tableName+"&height="+newHeight;
		    a.href = finalUrl;
		    a.setAttribute('data-history', 'false');
		    _context.invoke(function(di) {
				di.getService('page').openLink(a);
				$("#loading").hide();
		    });		    		    
		}
	    });
	}

	function strToArray($search, $offset){
		//if ($search.length > 1) {
		if ($search.length > 1){
			if ( $search[1].indexOf('page_b=') < 0) {
				$search[1] = $search[1] + "&page_b=1";
			}
			$sPageURL = $search[1].split('&');
		}else{
			$sPageURL = ['page_b=1'];
		}
		$stopScroll = false;
		//are we on last or first page? then stop
		$paginator = $('.paginationToolbar').data();
		if (($paginator.lastpage == "1" && $offset == 1) || ($paginator.firstpage == "1" && $offset == -1) ){
			$stopScroll = true;
		}

		$page_b = 0;
		$page_b_e = false;
		$do_e = false;

		$arrParams = [];
		for (i = 0; i < $sPageURL.length; i++) {
			$sParameter = $sPageURL[i].split('=');
			if ($sParameter[0] == "page_b") {
				$newPage = (parseInt($sParameter[1]) + $offset);
				if ($newPage == 0) {
					$newPage = 1;
					$stopScroll = true;
				}

				$arrParams[i] = $sParameter[0] + "=" + $newPage;
				$page_b = $newPage;
				$page_b_e = true;
			}else{

				if ($sParameter[0] == "do") {
					if ($offset == 1 && !$stopScroll){
						$arrParams[i] = "do=scrollBottom";
					}
					if ($offset == -1 && !$stopScroll){
						$arrParams[i] = "do=scrollTop";
					}
					$do_e = true;
				}else {
					$arrParams[i] = $sParameter[0] + "=" + $sParameter[1];
				}
			}
		}



		if ($offset == 1){
			if (!$page_b_e) {
				$arrParams[i++] = 'page_b=2';
			}
			if (!$do_e && !$stopScroll){
				$arrParams[i++] = 'do=scrollBottom';
			}
		}
		if ($offset == -1){
			if (!$page_b_e) {
				$arrParams[i++] = 'page_b=1';
			}
			if (!$do_e && !$stopScroll){
				$arrParams[i++] = 'do=scrollTop';
			}
		}

		return $arrParams;
	}

	function arrayToStr($arrParams){
		$query = "";
		for (i = 0; i < $arrParams.length; i++) {
			if (i >= 1){
				$query = $query + "&";
			}
			$query = $query + $arrParams[i];
		}
		return $query;
	}

	function dataScroll(){
		//return;

		$scrollingNow = false;
		$(document).on("wheel",".table-wraper", function (e) {
			//checkCurrentPage();
			var objConfig = jQuery.parseJSON(jQuery('#configMain').text());
			if ($scrollingNow) {
				//e.stopPropagation();
				//e.preventDefault();
				/*setTimeout(function()
					{
						//do something special
						//$scrollingNow = false;

					}, 200);

				 */
				return false;
			}
			//alternative options for wheelData: wheelDeltaX & wheelDeltaY //scroll down
			if($scrollingNow == false && $(this).scrollTop() + $(this).height() + 10 + 75 >= $('#baseListTable').height() && e.originalEvent.deltaY > 0 ) {
				//console.log("bottom!");
				$scrollingNow = true;
				//$search = objConfig.scrollBottomUrl.split('?');
				$search = window.location.toString().split('?');
				$arrParams = [];
				$arrParams = strToArray($search, 1);
				//$arrParams[$arrParams.length] = 'do=scrollBottom';

				$query = arrayToStr($arrParams);
				if ($query.indexOf('do=') > 0) {
					$url = $search[0] + '?' + $query;
					getDataScroll($url, 'down');
					e.preventDefault();
				}else{
					$scrollingNow = false;
				}
				//history.pushState({page: 1}, "title 1", "?"+$query)
			}else{
				//checkCurrentPage();
			}
			if($scrollingNow == false && $(this).scrollTop() < 145 && e.originalEvent.deltaY < 0 ) {
				//console.log("top!");
				$scrollingNow = true;
				//$url = objConfig.scrollTopUrl;
				$search = window.location.toString().split('?');
				$arrParams = [];
				$arrParams = strToArray($search, -1);
				//$arrParams[$arrParams.length] = 'do=scrollTop';

				$query = arrayToStr($arrParams);
				if ($query.indexOf('do=') > 0) {
					$url = $search[0] + '?' + $query;
					getDataScroll($url, 'top');
					e.preventDefault();
				}else{
					$scrollingNow = false;
				}
				//history.pushState({page: 1}, "title 1", "?"+$query);
			}else{
				//checkCurrentPage();
			}
			checkCurrentPage();
			//e.preventDefault();
			e.stopPropagation();
		});
	}

	function getDataScroll($finalUrl, $direction){
		$.ajax({
			url: $finalUrl,
			type: 'get',
			context: this,
			dataType: 'json',
			off: ['unique'],
			start: function(data) {
				$("#loading").hide();
			},
			success: function(data) {
				$("#loading").hide();
				$count = 0;
				$rows = [];
				$keys = [];
				$.each(data.snippets, function(i, val) {
					//console.log(i);
					if (i.substr(0,13) == 'snippet--row_') {
						$rows[$count] = val;
						$keys[$count] = i;
						//$('#baseListTable tbody tr:first').remove();
						$count++;
					}
					if (i == 'snippet--paginator_top'){
						$('#snippet--paginator_top').html(val);
					}
				});
				if ($direction == 'top') {
					$dataWork = $rows.reverse();
					$dataKeys = $keys.reverse();
					$oldHeight = $('#baseListTable').height();
				}
				if ($direction == 'down') {
					$dataWork = $rows;
					$dataKeys = $keys;
				}
				//$.each($dataWork, function(i, val) {
				for (i = 0; i < $dataWork.length; i++) {
					oRow = $.parseHTML('<tr id="' + $dataKeys[i] + '">' + $dataWork[i] + '</tr>');
					$class = $(oRow).find('.tr_config').data('class');
					$(oRow).addClass($class);

					$style = $(oRow).find('.tr_config').data('style');
					$arrStyle = $style.split(';');
					$.each($arrStyle, function ($i2, $val2) {
						if ($val2 != '') {
							$styleN = $val2.split(':');
							$(oRow).css($styleN[0], $styleN[1]);
						}
					});
					if ($direction == 'down') {
						$('#baseListTable tbody').append(oRow);
					}
					if ($direction == 'top') {
						$('#baseListTable tbody').prepend(oRow);
					}
				};
				if ($direction == 'top') {
					$newHeight = $('#baseListTable').height();
					$('.table-wraper').scrollTop($newHeight - $oldHeight);
				}
				$scrollingNow = false;
				checkCurrentPage();
			}
		});
	}

	/*check if paginator is set to current page
	* current page is the one set in first tbody->tr->td->data->page */
	function checkCurrentPage(){
		//find first visible row
		$rowData = $('#baseListTable tbody tr:first td:first').data();
		$('#baseListTable tbody tr').each(function (index) {
			if ($(this).offset().top >= 200)
			{
				$rowData = $(this).find('td:first').data();
				return false;
			}
		});

		$paginator = $('.paginationToolbar').data();

		$search = window.location.toString().split('?');
		$arrParams = searchToArr($search);
		//if page_b in url is different then appropriate page of most top row
		//then we have to change page_b in url and update paginator
		//also we have to remove other pages then actual +- 2 pages
		if ($rowData.page != $arrParams['page_b']){
			$arrParams['page_b'] = $rowData.page;
			$query = arrayToStr2($arrParams);
			history.pushState("", "title 1", "?"+$query);

			$arrParams['do'] = 'paginatorUpdate';
			$query = arrayToStr2($arrParams);
			$finalUrl = window.location.origin + window.location.pathname + "?" + $query;

			$query = arrayToStr2($arrParams);
			$.ajax({
				url: $finalUrl,
				type: 'get',
				context: this,
				dataType: 'json',
				off: ['unique'],
				start: function (data) {
					$("#loading").hide();
				},
				success: function (data) {
					$("#loading").hide();
					$.each(data.snippets, function(i, val) {
						if (i == 'snippet--paginator_top') {
							$('#snippet--paginator_top').html(val);
						}
					});
				}
			});

			$toRemove = $rowData.page - 2;
			$('#baseListTable tbody tr [data-page="' + $toRemove + '"]').parent().remove();
			$toRemove = $rowData.page + 2;
			$('#baseListTable tbody tr [data-page="' + $toRemove + '"]').parent().remove();


		}
	}

	function arrayToStr2($arrParams){
		$query = "";
		//for (i = 0; i < $arrParams.length; i++) {
		//$.each($arrParams, function ($key, $val) {
		//$arrParams.forEach( function ($key, $val) {
		Object.keys($arrParams).forEach( function ($key, $strKey) {
			if ($query.length > 1){
				$query = $query + "&";
			}
			$query = $query + $key + "=" + $arrParams[$key];
		});
		return $query;
	}

	function searchToArr($search)
	{
		if ($search.length > 1) {
			$sPageURL = $search[1].split('&');
		}else{
			$sPageURL = ['page_b=1'];
		}
		$arrParams = [];
		for (i = 0; i < $sPageURL.length; i++) {
			if ($sPageURL[i] != "") {
				$sParameter = $sPageURL[i].split('=');
				//$arrParams[i] = $sParameter[0] + "=" + $sParameter[1];
				$arrParams[$sParameter[0]] = $sParameter[1];
			}
		}
		if ($search.length > 1 && $search[1].indexOf('page_b=') < 0)
		{
			$arrParams['page_b'] = "1";
		}
		return $arrParams;
	}

function sortableDef(){
//modifier for sorting jquery
	var fixHelperModifiedLG = function(e, tr) {
		var $originals = tr.children();
		var $helper = tr.clone();
		$helper.children().each(function(index)
		{
		  $(this).width($originals.eq(index).width())
		  //$(this).width($(this).width());
		});
		return $helper;
	};



	//user sorting functions
	$('.changeOrder  tbody').sortable({
		start: function(event, ui) {
				var start_pos = ui.item.index();
				ui.item.data('oldOrder', start_pos);
			},
		beforeStop: function(event, ui){
			console.log('pred stopem');
		},
		update: function( event, ui ) {
			/*var newOrder = ui.item.index();
			var controlname = ui.item.data('controlname');
			var url = ui.item.data('url');
			var oldOrder = ui.item.data('oldOrder');
			$.ajax({
			url: url,
			type: 'get',
			context: this,
			data: controlname+'-newOrder='+newOrder+'&'+controlname+'-oldOrder='+oldOrder,
			dataType: 'json',
			off: ['unique'],
			start: function(data){
				$("#loading").hide();
			},
			success: function(data) {
				$("#loading").hide();
				}
			});*/
			var a = document.createElement('a');

			var controlname = ui.item.data('controlname');
			var url = ui.item.data('url');
			var page_lg = ui.item.data('page_lg');
			var page_items = ui.item.data('page_items');
			var newOrder = ((page_lg - 1) * page_items) + ui.item.index() ;
			var oldOrder = ((page_lg - 1) * page_items) + ui.item.data('oldOrder');
		    
			finalUrl = url + "&"+controlname + '-newOrder='+newOrder+'&'+controlname+'-oldOrder='+oldOrder+'&'+controlname+'-page_lg='+page_lg;
			//console.log(finalUrl);
			a.href = finalUrl;
			//a.setAttribute('data-transition', transition);
			a.setAttribute('data-history', 'false');
			_context.invoke(function(di) {
			    di.getService('page').openLink(a);
			});	
		    
//		    event.preventDefault(); // prevents default
//		    return false; // also prvents default (i like to add both but just the e.preventDefault is required :) )			

		},
		helper: fixHelperModifiedLG,
		forceHelperSize: true
	});		
}


//scroll to last active row
$('document').ready(function(){
    if(window.jQuery)
    {
     scrollToLastActive();
    }
});

function scrollToLastActive()
{
    if ($('#baseListTable .lastActive').length > 0)
    {
    	//console.log('scrollllled');
    	//return;
    	posY = parseFloat($('#snippet--baselist').offset().top);
		//var rowpos = parseFloat($('#baseListTable .lastActive').position().top) - 150;
		lastActive = parseFloat($('#baseListTable .lastActive').position().top);
		var rowpos = lastActive - posY;
		if (rowpos > 0 )
		{//var rowpos = 60;
		   $('.table-wraper').animate({
			scrollTop: rowpos
			},900
			);
		}
    }
}

function listgridCounts()
{
	//show counts in tab
	$('.listgridCount').each(function() {
		tmpCount = $(this).data('counter');
		tempId = $(this).closest('[role=tabpanel]').attr('id');
		tmpBadge =  $('[aria-controls='+tempId+']').find('.badge');
		if (tmpCount > 0) {
			if (tmpBadge.length > 0) {
				tmpBadge.html(tmpCount);
			} else
				$('[aria-controls=' + tempId + ']').append(' &nbsp; <span class="badge badge-listgrid">' + tmpCount + '</span>');
		}
		//return false;

	});
}
