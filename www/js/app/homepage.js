/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 19.6.2016 - 7:55:09
 * 
 */

	$(document).ready(function () {

		//var arrInvoice = $.parseJSON(sInvoice);
		//var arrCommission = $.parseJSON(sCommission);
		//var arrOrder = $.parseJSON(sOrder);		
		
		if (typeof graphVolume['invoice'] != 'undefined')		
		{
			var arrInvoice =  $.parseJSON(graphVolume['invoice']);
			var arrCommission =  $.parseJSON(graphVolume['commission']);
			var arrOrder =  $.parseJSON(graphVolume['order']);		

			if (arrInvoice.length > 0 || arrCommission.length > 0 || arrOrder.length > 0)
			{
				$('#graphVolumeMy').parent().find('.graphNothing').hide();
				$.jqplot.sprintf.thousandsSeparator = ' ';
				$.jqplot.sprintf.decimalMark = ',';				
				plot1 = $.jqplot('graphVolumeMy', [arrInvoice,arrCommission,arrOrder], {
					seriesColors: ["rgba(100, 101, 245, 1)", "rgb(126, 255, 28)", "rgb(161, 212, 144)"],
					// Turns on animatino for all series in this plot.
					animate: false,
					// Will animate plot on calls to plot1.replot({resetAxes:true})
					animateReplot: true,
					highlighter: {
						  show: true,
						  sizeAdjust: 1,
						  tooltipOffset: 9
					  },					
				legend: {
					renderer: jQuery.jqplot.EnhancedLegendRenderer,			
					show: true,
					placement: 'outside'			
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
				gridPadding: {left: 50},
				series:[
						{
							label: 'Faktury',					
							lineWidth: 4, 
							markerOptions:{style:'square'}
						}, 
						{
							label: 'Zakázky',
							lineWidth: 4, 
							markerOptions:{style:'square'}			
						},
						{
							label: 'Objednávky',
							lineWidth: 4, 
							markerOptions:{style:'square'}			
						},					

				],
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
							  pad: 0,
							  rendererOptions: {
								  minorTicks: 1
							  },
							  tickOptions: {
								  formatString: "%'.0f "+varCurrency,
								  showMark: false
							  }
						  },
					y2axis: {
							  renderer: $.jqplot.LogAxisRenderer,
							  pad: 0,
							  rendererOptions: {
								  minorTicks: 1
							  },
							  tickOptions: {
								  formatString: "%'.0f "+varCurrency,
								  showMark: false
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


		if (typeof graphHelpdesk['hdCount'] != 'undefined')
		{
			var arrHdCount =  $.parseJSON(graphHelpdesk['hdCount']);
			var arrHdSum =  $.parseJSON(graphHelpdesk['hdSum']);
			if (arrHdCount.length > 0 || arrHdSum.length > 0)
			{
				$('#graphHelpdeskMy').parent().find('.graphNothing').hide();
				$.jqplot.sprintf.thousandsSeparator = ' ';
				$.jqplot.sprintf.decimalMark = ',';				
				plot2 = $.jqplot('graphHelpdeskMy', [arrHdCount,arrHdSum], {
					seriesColors: ["rgba(240, 114, 72, 0.7)", "rgb(140, 242, 39)"],
					// Turns on animatino for all series in this plot.
					animate: false,
					// Will animate plot on calls to plot1.replot({resetAxes:true})
					animateReplot: true,
					highlighter: {
						  show: true,
						  sizeAdjust: 1,
						  tooltipOffset: 9
					  },					
				legend: {
					renderer: jQuery.jqplot.EnhancedLegendRenderer,
					show: true,
					location: 's',
					placement: 'outsideGrid',
					 rendererOptions: {
					   numberRows: '1',
					 },			
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
				gridPadding: {left: 50, right:10 },
				series:[
						{
							label: 'Počet',					
							lineWidth: 4, 
							yaxis: 'yaxis',
							markerOptions:{style:'square'}
						}, 
						{
							label: 'Trvání / hodiny',
							lineWidth: 4, 
							right: 20,
							yaxis: 'y2axis',
							markerOptions:{style:'square'}			
						},					

				],
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
							  label: 'Počet',
							  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
							  pad: 0,
							  drawMajorGridlines: false,
							  rendererOptions: {
								  minorTicks: 1
							  },
							  tickOptions: {
								  formatString: "%'.0f ",
								  showMark: false
							  }
						  },
					y2axis: {
							  renderer: $.jqplot.LogAxisRenderer,
							  label: 'Trvání / hodiny',
							  labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
							  pad: 0,
							  drawMajorGridlines: false,
							  rendererOptions: {
								  minorTicks: 1
							  },
							  tickOptions: {
								  formatString: "%'.0f ",
								  showMark: true
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
				$('.jqplot-y2axis').css('right','0px')
			}
		}



//	initTree();
    $( ".columnMy" ).sortable({
		helper:"clone",
		opacity: 0.5,
		connectWith: ".columnMy",
		revert: true,
		placeholder: "placeholder",
		tolerance: "pointer",
		forceHelperSize: true,
        forcePlaceholderSize: true,		
		start: function(e, ui){
                console.log("start event");

                     ui.placeholder.height(ui.item.height());
				 },
           stop: function(e, ui) {
                console.log("end event");
				var retArr = new Array();
				var retArr2 = new Array();
				$.each($('#col1').find('.panel'), function(index,value){
					//console.log($(value).prop('id'));
					var show = 1;
					if ($(value).hasClass('hidden'))
					{
						show = 0;
					}
					retArr[index] = new Array($(value).prop('id'),show);
				});
				$.each($('#col2').find('.panel'), function(index,value){
					//console.log($(value).prop('id'));
					var show = 1;
					if ($(value).hasClass('hidden'))
					{
						show = 0;
					}					
					retArr2[index] = new Array($(value).prop('id'),show);
				});

				
				var urlString = $('#col1').data('urlstring');
				var myParams = new Object();
				myParams.data = new Array(retArr,retArr2);
				console.log(myParams);
				//$.getJSON(urlString, myParams, function (data) {
				//		console.log(data);
						//set default 
				//	}); 						
					//$("#loading").hide();
					
				$.ajax({
						url: urlString,
						type: 'get',
						context: this,
						data: myParams,
						dataType: 'json',
						start: function(data) {
							$("#loading").hide();
					}});										
									
            },
            change:function(e,ui){
                console.log("start change event");
            }				 
		}
	);
    $( "#sortable" ).disableSelection();
	
	
	$('.boxSetShow');
	
	});	