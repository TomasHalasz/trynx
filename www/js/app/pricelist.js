/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 8.7.2016 - 6:46:18
 * 
 */

$(document).ready(function(){
	//initImageDropzone();
	//initSimpleLightBox();
	//calcProfit();



});

//a[data-toggle="tab"]
$('a[aria-controls="tab10"]').on('shown.bs.tab', function (e) {
	//e.target // newly activated tab
	//e.relatedTarget // previous active tab
	showPriceStats();
	showSalesStats();
})

$('a[aria-controls="tab10"]').on('hidden.bs.tab', function (e) {
	//e.target // newly activated tab
	//e.relatedTarget // previous active tab
	hideStats();
});

function hideStats(){
	$('#graphVolumeMy').children().remove();
	$('#graphSalesMy').children().remove();

}


	function showPriceStats()
	{
			if (typeof graphVolume['price_s'] != 'undefined')
			{
				var arrPriceS =  $.parseJSON(graphVolume['price_s']);
				if (arrPriceS.length > 0 )
				{
					$('#graphVolumeMy').parent().find('.graphNothing').hide();
					$.jqplot.sprintf.thousandsSeparator = ' ';
					$.jqplot.sprintf.decimalMark = ',';
					plot1 = $.jqplot('graphVolumeMy', [arrPriceS], {
						seriesColors: ["rgba(100, 101, 245, 1)"],
						// Turns on animatino for all series in this plot.
						animate: true,
						// Will animate plot on calls to plot1.replot({resetAxes:true})
						animateReplot: true,
						highlighter: {
							show: true,
							sizeAdjust: 1,
							tooltipOffset: 9
						},
						seriesDefaults: {
							rendererOptions: {
								smooth: true,
								animation: {
									show: true
								}
							},
							showMarker: true
						},
						gridPadding: {left: 80},
						axesDefaults: {
							rendererOptions: {
								baselineWidth: 1.5,
								baselineColor: '#444444',
								drawBaseline: false
							}
						},
						axes:{
							xaxis:{
								renderer:$.jqplot.DateAxisRenderer,
								tickRenderer: $.jqplot.CanvasAxisTickRenderer,
								tickOptions:{formatString:'%m/%Y',
									fontSize: '11px'
								},
								tickInterval:'1 month'
							},
							yaxis: {
								renderer: $.jqplot.LogAxisRenderer,
								//pad: 0,
								rendererOptions: {
									minorTicks: 1
								},
								tickOptions: {
									formatString: "%'.0f "+varCurrency,
									fontSize: '11px',
									showMark: false
								},
								rendererOptions: {
									//forceTickAt0: true
								}
							}
						},
						highlighter: {
							show: true,
							showLabel: true,
							tooltipAxes: 'y',
							sizeAdjust: 7.5 , tooltipLocation : 'ne'
						}
					});
					$('.jqplot-highlighter-tooltip').addClass('ui-corner-all');
				}
			}
	}

	function showSalesStats()
	{
		if (typeof graphSales['sales'] != 'undefined')
		{
			var arrSales =  $.parseJSON(graphSales['sales']);
			if (arrSales.length > 0 )
			{
				$('#graphSalesMy').parent().find('.graphNothing').hide();
				$.jqplot.sprintf.thousandsSeparator = ' ';
				$.jqplot.sprintf.decimalMark = ',';
				plot1 = $.jqplot('graphSalesMy', [arrSales], {
					seriesColors: ["rgb(126, 255, 28)"],
					// Turns on animatino for all series in this plot.
					animate: true,
					// Will animate plot on calls to plot1.replot({resetAxes:true})
					animateReplot: true,
					highlighter: {
						show: true,
						sizeAdjust: 1,
						tooltipOffset: 9
					},
					seriesDefaults: {
						rendererOptions: {
							smooth: true,
							animation: {
								show: true
							}
						},
						showMarker: true
					},
					gridPadding: {left: 80},
					axesDefaults: {
						rendererOptions: {
							baselineWidth: 1.5,
							baselineColor: '#444444',
							drawBaseline: false
						}
					},
					axes:{
						xaxis:{
							renderer:$.jqplot.DateAxisRenderer,
							tickRenderer: $.jqplot.CanvasAxisTickRenderer,
							tickOptions:{formatString:'%m/%Y',
								fontSize: '11px'
							},
							tickInterval:'1 month'
						},
						yaxis: {
							renderer: $.jqplot.LogAxisRenderer,
							//pad: 0,
							rendererOptions: {
								minorTicks: 1
							},
							tickOptions: {
								formatString: "%'.0f ",
								fontSize: '11px',
								showMark: false
							},
							rendererOptions: {
								//forceTickAt0: true
							}
						}
					},
					highlighter: {
						show: true,
						showLabel: true,
						tooltipAxes: 'y',
						sizeAdjust: 7.5 , tooltipLocation : 'ne'
					}
				});
				$('.jqplot-highlighter-tooltip').addClass('ui-corner-all');
			}
		}
	}



		$(document).on('blur', '#frm-edit-identification.validate', function(e){
			var urlString = $(this).data('urlstring');
			var valueToCheck = $(this).val();

			$.ajax({
				url: urlString,
				type: 'get',
				context: this,
				data: 'identification=' + valueToCheck ,
				dataType: 'json',
				success: function(data) {
					$(this).popover('destroy');
					//alert(data['result']);
					var myObj = $(this);
					if (data['result'])
					{
						timeoutID = window.setTimeout(function(e){
							myObj.popover({content: 'Zadaný kód nelze použít, již je obsazen.',placement: 'bottom', toggle: 'popover'});
							myObj.popover('show');
							myObj.toggleClass('form-control-error',true);
						}, 250);

					}else{
						timeoutID = window.setTimeout(function(e){
							myObj.popover('destroy');
							myObj.toggleClass('form-control-error',false);
						}, 250);

					}
				}
			});

		});

	    //vypocty 
	    $(document).on('change','#frm-edit-price, #frm-edit-vat', function (e) {	    
		//charCode = e.charCode || e.keyCode;
		//alert(charCode);
		//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode		
		    vat = $('#frm-edit-vat').val().split(' ').join('').replace(',','.');
		    priceVat = (1+(vat/100)) * $('#frm-edit-price').val().split(' ').join('').replace(',','.');
		    priceVat = Math.round(priceVat * 100) / 100;	
		    $("#frm-edit-price_vat").val(priceVat);
		    $("#frm-edit-price_vat").autoNumeric('update');		    
		//}
	    });
	    $(document).on('change','#frm-edit-price_vat', function (e) {	    
		//charCode = e.charCode || e.keyCode;
		//if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode		
		    vat = $('#frm-edit-vat').val().split(' ').join('').replace(',','.');
		    price = $('#frm-edit-price_vat').val().split(' ').join('').replace(',','.') / (1+(vat/100));
		    price = Math.round(price * 100) / 100;	
		    $("#frm-edit-price").val(price);
		    $("#frm-edit-price").autoNumeric('update');
		//}
	    });	    
	    //zmena skupiny = zmena ciselne rady
	    $(document).on('select2:select','#frm-edit-cl_pricelist_group_id', function(e) {
			var objMy = $(this);
			var obj = '';
			var urlString  = objMy.data('urlajax');
			$.ajax({
				url: urlString,
				type: 'get',
				context: objMy,
				data: 'cl_pricelist_group_id=' + objMy.val(),
				dataType: 'text',
				success: function(data) {
					obj = JSON.parse(data);
					if (obj.number != ''){

						bootbox.dialog({
							message: "Použít nové identifikační číslo dle nastavení skupiny?",
							title: "Dotaz",
							buttons: {
								success: {
									label: "Použít nové",
									className: "btn-success",
									callback: function() {
										$('#frm-edit-identification').val(obj.number);
										$('input[name=cl_number_series_id]').val(obj.id);
									}
								},
								cancel: {
									label: "Nechat tak",
									className: "btn-primary",
									callback: function() {
										inv_date_counter = 0;
									}
								}
							}
						});


					}

				}
			});

		  e.preventDefault();
		  e.stopImmediatePropagation();		
	    
	    });    
	    
	function initSimpleLightBox()
	{
	    var lightbox = $('.gallery a.image').simpleLightbox();
	    
	}

//cl_storage_id change = new cl_storage_places
$(document).on('select2:select','#frm-edit-cl_storage_id', function(e) {
	var objMy = $(this);
	var obj = '';
	var urlString  = objMy.data('urlajax');
	$.ajax({
		url: urlString,
		type: 'get',
		context: objMy,
		data: 'cl_storage_id=' + objMy.val(),
		dataType: 'json',
		success: function(data) {
			//obj = JSON.parse(data);
			console.log(data);
			var $dropdown = $('#frm-edit-cl_storage_id');
//			$.each(data, function(index) {
				//$dropdown.append($("<option />").val(index).text(this));
//				console.log(index);
//				console.log(this);
//				updateSelect("#frm-edit-cl_storage_places_id", data);
//			});
			updateSelect("#frm-edit-cl_storage_places_id", data);
			//$('').trigger("chosen:updated");
			$("#frm-edit-cl_storage_places_id").select2();
		}
	});

	e.preventDefault();
	e.stopImmediatePropagation();

});

function updateSelect(select, data)
{
	$(select).empty();
	//console.log(data);
	$.each(data, function(index) {
			$('<option>').attr('value', index).text(this).appendTo(select);
	});
}

$(document).ready(function() {

	$(document).on('change', '.listGridCheck', function (e) {
		$id = $(this).data('id');
		$val = $(this).prop('checked');
			var objConfig = jQuery.parseJSON(jQuery('#configPricelist').text());
			urlString = objConfig.setNotActivePrep;
			$.ajax({
				url: urlString,
				type: 'get',
				data: 'idPricelist=' + $id + '&value=' + $val,
				dataType: 'json',
				success: function(data) {
					$.each(data.snippets, function(index) {
						$('#' + index).html(data.snippets[index]);
					});
				}
			});
	});

	$(document).on('change', '.listGridCheckAll', function (e){
		$val = $(this).prop('checked');
		if ($val == true){
			$('#frm-notActiveSetForm [type=submit]').click();
		}else{
			var objConfig = jQuery.parseJSON(jQuery('#configPricelist').text());
			urlString = objConfig.setNotActivePrepAll;
			$.ajax({
				url: urlString,
				type: 'get',
				data: 'value=' + $val,
				dataType: 'json',
				success: function (data) {
					$.each(data.snippets, function(index) {
						$('#' + index).html(data.snippets[index]);
					});
				}
			});
		}
	});

	$(document).on('blur', "#frm-edit-price, #frm-edit-price_vat, #frm-edit-price_s", function (e) {
		calcProfit();
	});

	$(document).on('blur', "#frm-edit-price_s", function (e) {
		if ($(this).prop('readonly') == false) {
			var objConfig = jQuery.parseJSON(jQuery('#configPricelist').text());
			urlString = objConfig.setPriceS;
			var $val = $(this).val();
			$.ajax({
				url: urlString,
				type: 'get',
				data: 'value=' + $val,
				dataType: 'json',
				success: function (data) {
					$.each(data.snippets, function (index) {
						$('#' + index).html(data.snippets[index]);
					});
					$("#frm-edit-price_s").prop('readonly', true);
				}
			});
		}
	});


});
function calcProfit(){
	if ($('#frm-edit-price_s').length > 0) {
		$price_s = $('#frm-edit-price_s').val().split(' ').join('');
		$price = $('#frm-edit-price').val().split(' ').join('');
		if ($price_s > 0) {
			$profit_per = ($price / $price_s - 1) * 100;
		} else {
			if ($price > 0) {
				$profit_per = 100;
			} else {
				$profit_per = 0;
			}
		}
		$profit_abs = $('#frm-edit-price').val().split(' ').join('') - $('#frm-edit-price_s').val().split(' ').join('');
		$('#frm-edit-profit_per').val(Math.round($profit_per * 10, 1) / 10);
		$('#frm-edit-profit_abs').val(Math.round($profit_abs * 10, 1) / 10);
	}

}
	    


