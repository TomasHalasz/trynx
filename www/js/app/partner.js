/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 23.6.2016 - 10:40:57
 * 
 */



	$(document).on('click','#aresLink', function(e){
		var icoInput = $('[data-ares="ico_input"]');
		var url = $(this).data('href');
		var myParams = new Object();	
		myParams.ico = icoInput.val();
		$("#loading").show();
		$.getJSON(url, myParams, function (data) {
				$("#loading").hide();
				if (data.in == undefined)
				{
					bootbox.dialog({
						message: "Pro zadané IČ nebyla nalezena žádná firma.",
						title: "Informace",
						buttons: {
							cancel: {
								label: "Zavřít",
								className: "btn-primary"
							}
						}
					});
				}else {
					$('input[name=dic]').val(data.tin);
					$('input[name=platce_dph]').prop('checked', data.vat_payer);
					$('input[name=city]').val(data.city);
					$('input[name=street]').val(data.street + ' ' + data.house_number);
					$('input[name=zip]').val(data.zip);
					$('input[name=company]').val(data.company);
					$('select[name=cl_countries_id]').val(data.cl_countries_id);
					$('select[name=cl_countries_id]').trigger("change");
				}
			});
		var urlString  = $(this).data('accounts');
		$.ajax({
			url: urlString,
			type: 'get',
			context: this,
			dataType: 'json',
			success: function(payload) {
				if (payload.snippets) {
					for (var i in payload.snippets) {
						$('#'+i).html(payload.snippets[i]);
					}
					$("#loading").hide();
					//$.nette.load();
				}
				listgridCounts();
				//$('#gridSetBox').show();
			}
		});
	//   e.preventDefault();
	 e.stopImmediatePropagation();
	});

$(document).on('click','#viesLink', function(e){
	var dicInput = $('[data-vies="dic_input"]');
	var url = $(this).data('href');
	var myParams = new Object();
	myParams.dic = dicInput.val();
	$("#loading").show();
	$.getJSON(url, myParams, function (data) {
		$("#loading").hide();
		if (data.company == undefined)
		{

			bootbox.dialog({
				message: "Pro zadané DIČ nebyla nalezena žádná firma.",
				title: "Informace",
				buttons: {
					cancel: {
						label: "Zavřít",
						className: "btn-primary"
					}
				}
			});
		}else {
			if (data.valid == 0)
			{
				bootbox.dialog({
					message: "Zadané DIČ není platné.",
					title: "Informace",
					buttons: {
						cancel: {
							label: "Zavřít",
							className: "btn-primary"
						}
					}
				});
			}else {
				//$('input[name=dic]').val(data.dic);
				$('input[name=platce_dph]').prop('checked', data.valid);
				$('input[name=city]').val(data.city);
				$('input[name=street]').val(data.street);
				$('input[name=zip]').val(data.zip);
				$('input[name=company]').val(data.company);
				$('select[name=cl_countries_id]').val(data.cl_countries_id);
				$('select[name=cl_countries_id]').trigger("change");
			}
		}



	});
//	   e.preventDefault();
	e.stopImmediatePropagation();
});


	$(document).on('change', 'input[name=ico]', function(){
		var logobox = $('#logobox');
		var newUrl = logobox.data('url')+$(this).val();
		console.log(newUrl);
		logobox.prop('src', newUrl);
		
	});

	$(document).on('blur', '#frm-partnerPriceListGrid-editLine-price', function(){
		var $price 		= parseFloat($('input[name=price]').val().split(' ').join('').replace(',','.'));
		var $price_vat 	= parseFloat($('input[name=price_vat]').val().split(' ').join('').replace(',','.'));
		var $vat 		= parseFloat($('input[name=vat]').val().split(' ').join('').replace(',','.'));

		$price_vat = Math.round(($price + ($price * $vat / 100 )) * 100 ) / 100;
		$('input[name=price_vat]').val($price_vat);
		$('input[name=price_vat]').autoNumeric('update');
	});

	$(document).on('blur', '#frm-partnerPriceListGrid-editLine-price_vat', function(){
		var $price 		= parseFloat($('input[name=price]').val().split(' ').join('').replace(',','.'));
		var $price_vat 	= parseFloat($('input[name=price_vat]').val().split(' ').join('').replace(',','.'));
		var $vat 		= parseFloat($('input[name=vat]').val().split(' ').join('').replace(',','.'));

		$price = Math.round(($price_vat / ( 1 + ($vat/100) )) * 100 ) / 100;
		$('input[name=price]').val($price);
		$('input[name=price]').autoNumeric('update');
	});

	//enable pricelist_partner
	$(document).on('click','#pricelist_partner', function(e) {
		if ($('#pricelist_partner').prop('checked'))
			data = 1;
		else
			data = 0;
		var urlString  = $(this).data('urlajax');
		$.ajax({
			url: urlString,
			type: 'get',
			context: this,
			data: 'value=' + data ,
			dataType: 'json',
			success: function(data) {
				$("#loading").hide();
				//$('#gridSetBox').show();
			}
		});
	});

	$(document).on('click','#pricelist_partner_only', function(e) {
		if ($('#pricelist_partner_only').prop('checked'))
			data = 1;
		else
			data = 0;

		var urlString  = $(this).data('urlajax');
		$.ajax({
			url: urlString,
			type: 'get',
			context: this,
			data: 'value=' + data ,
			dataType: 'json',
			success: function(data) {
				$("#loading").hide();
				//$('#gridSetBox').show();
			}
		});
	});

	//B2B enable public access
	$(document).on('click','#b2b_public', function(e) {
		if ($('#b2b_public').prop('checked'))
			data = 1;
		else
			data = 0;
		var urlString  = $(this).data('urlajax');
		$.ajax({
			url: urlString,
			type: 'get',
			context: this,
			data: 'value=' + data ,
			dataType: 'json',
			success: function(payload) {
				if (payload.snippets) {
					for (var i in payload.snippets) {
						$('#'+i).html(payload.snippets[i]);
					}
					$("#loading").hide();
					//$.nette.load();
				}
				//$('#gridSetBox').show();
			}
		});
	});

$(document).on('click','#copylink', function(e) {
	var $urlString  = $(this).data('url');
	copyToClipboard( $urlString)
});
function copyToClipboard(){
	var el = document.createElement('textarea');
	el.value = str;
	el.setAttribute('readonly', '');
	el.style.position = 'absolute';
	el.style.left = '-9999px';
	document.body.appendChild(el);
	el.select();
	document.execCommand('copy');
	document.body.removeChild(el);
	bootbox.dialog({
		message: "Adresa je nyní ve schránce připravena pro vložení pomocí CTR+V. <br> <br><span style='word-wrap: anywhere;'>Url: " + str + "</span>",
		title: "Adresa zkopírována",
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


$('a[aria-controls="tab13"]').on('shown.bs.tab', function (e) {
	var urlString  = $(this).data('url');
	$.ajax({
		url: urlString,
		type: 'get',
		context: this,
		dataType: 'json',
		success: function(payload) {
			if (payload.snippets) {
				for (var i in payload.snippets) {
					$('#'+i).html(payload.snippets[i]);
				}
				$("#loading").hide();
				//$.nette.load();
			}
			//$('#gridSetBox').show();
		}
	});
});

$('a[aria-controls="tab12"]').on('shown.bs.tab', function (e) {
	//e.target // newly activated tab
	//e.relatedTarget // previous active tab
	showInvoiceStats();
	showInvoiceArrivedStats();
});

$('a[aria-controls="tab12"]').on('hidden.bs.tab', function (e) {
	//e.target // newly activated tab
	//e.relatedTarget // previous active tab
	hideStats();
});

function hideStats(){
	$('#graphSalesMy').children().remove();
	$('#graphArrivedSalesMy').children().remove();
}

function showInvoiceStats()
{
	if (typeof graphSales['sales'] != 'undefined')
	{
		var arrPriceS =  $.parseJSON(graphSales['sales']);
		if (arrPriceS.length > 0 )
		{
			$('#graphSales').parent().find('.graphNothing').hide();
			$.jqplot.sprintf.thousandsSeparator = ' ';
			$.jqplot.sprintf.decimalMark = ',';
			plot1 = $.jqplot('graphSalesMy', [arrPriceS], {
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

function showInvoiceArrivedStats()
{
	if (typeof graphArrivedSales['sales'] != 'undefined')
	{
		var arrPriceS =  $.parseJSON(graphArrivedSales['sales']);
		if (arrPriceS.length > 0 )
		{
			$('#graphArrivedSales').parent().find('.graphNothing').hide();
			$.jqplot.sprintf.thousandsSeparator = ' ';
			$.jqplot.sprintf.decimalMark = ',';
			plot2 = $.jqplot('graphArrivedSalesMy', [arrPriceS], {
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
