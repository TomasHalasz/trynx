/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$('document').ready(function(){
    _context.invoke(function(di) {
	/*di.getService('ajax').on('request-created', function(evt) {
	    $("#loading").show();
	});*/
	var myProgress;
	di.getService('page').on('transaction-created', function(evt) {
	    //if ('do=menuBadgeUpdate' in evt.data.url)
	    //if (evt.data.url.search('do=menuBadgeUpdate') > -1)
	    if ((typeof evt.data.context.element != "undefined" && typeof evt.data.context.element.href != "undefined" && evt.data.context.element.href.search('do=menuBadgeUpdate') > -1) ||
			(typeof evt.data.context.element != "undefined" && typeof evt.data.context.element.href != "undefined" && evt.data.context.element.href.search('do=saleUpdate') > -1) ||
			(typeof evt.data.context.element != "undefined" && typeof evt.data.context.element.href != "undefined" && evt.data.context.element.href.search('do=saveTableHeight') > -1) ||
			(typeof evt.data.context.element != "undefined" && typeof evt.data.context.element.href != "undefined" && evt.data.context.element.href.search('do=scrollBottom') > -1) ||
			(typeof evt.data.context.element != "undefined" && typeof evt.data.context.element.href != "undefined" && evt.data.context.element.href.search('do=scrollTop') > -1) ||
			(typeof evt.data.context.element != "undefined" && typeof evt.data.context.element.href != "undefined" && evt.data.context.element.href.search('do=showAgreement') > -1 ||
			(typeof evt.data.context.element != "undefined" && typeof evt.data.context.element.href != "undefined" && evt.data.context.element.href.search('do=showFlashNow') > -1) ||
			(typeof evt.data.context.element != "undefined" && typeof evt.data.context.element.href != "undefined" && evt.data.context.element.href.search('do=showFlash') > -1) ||
			(typeof evt.data.context.element != "undefined" && typeof evt.data.context.element.href != "undefined" && evt.data.context.element.href.search('do=checkDuplicity') > -1))
		)
	    {
			//console.log('menu update');
	    }else{
			//$("#progressBox").hide();
			//$('#progressBar').hide();
			$("#loading").show();
			if(myProgress == undefined) {
					myProgress = setInterval(function () {
					console.log("Hello");
					var objConfig = jQuery.parseJSON(jQuery('#configMain').text());
					url = objConfig.progressUpdateUrl;
					//$("#progressBox").show();
					$('#progressBar').show();
					$.ajax({
						url: url,
						type: 'get',
						context: this,
						dataType: 'json',
						success: function (data) {
							console.log(data);
							$('#progressValue').html(data.progress_val);
							$('#progressMax').html(data.progress_max);
							if (data.progressMessage != '') {
								$("#progressBox").show();
								$('#progressMessage').html(data.progress_message);
							}

							percentVal = Math.round((data.progress_val / data.progress_max) * 100);
							$('#progressBar').css('width', percentVal);
							$('#progressBar').html(percentVal + ' %');
							//$("#loading").hide();
							//$('#gridSetBox').show();
						}
					});
				}, 3500);
			}
			//clearInterval(myProgress);


	    }
	});
	
	di.getService('snippetManager').on('after-update', function(evt) {
		clearInterval(myProgress);
		myProgress = undefined;
		percentVal = 0;
		$('#progressBar').css('width', percentVal);
		$('#progressBar').html(percentVal + ' %');

	    if (('update' in evt.data)) {
			// pokud data neobsahují klíč "update", jedná se o "prázdný" after-update při úvodním načtení stránky
			$("#loading").hide();

			//this is for focus and select first input box in edited listgrid
			//$('.listgrid input[type="text"]:not([readonly]):not(".datetimepicker"):not(".datepicker"), .listgrid textarea:not([readonly])').first().select().focus();

			toFocus = $('.listgrid select, .listgrid input[type="text"]:not([readonly]), .listgrid textarea:not([readonly])').first();
			if ((toFocus.hasClass('chzn-select') || toFocus.hasClass('select2'))  && toFocus.val() == ""){
				setTimeout(function(){
					toFocus.select2('open');
				}, 750);
			}else{
				$('.listgrid input[type="text"]:not([readonly]):not(".datetimepicker"):not(".datepicker"), .listgrid textarea:not([readonly])').first().select().focus();
			}

			setTimeout(function(){
				$('input.number, input.integer, input.currency_rate').autoNumeric('update');
				resizeChosen();
			}, 50);
			//if exists bschilds box with summary then recalc his position
			 //if ($( ".flowingMy").length > 0){
				//flowingMy();
			//}
			setTimeout(axleFF.hideFlashes, 3000);

//			 console.log( $('[data-rowid="42280"]').offset().top);
			//$('.table-responsive').animate({ scrollTop: $('[data-rowid="42280"]').offset().top-300 }, 10);

	    }
	});	

	/*var width = null;
	di.getService('page').getSnippet('snippet--content')
	    .setup(
		function(elem) {
		    // 'elem' is the snippet's DOM element
		    width = elem.offsetWidth;
		},
		function(elem) {
		    //elem.textContent = 'This snippet is ' + width + ' pixels wide.';

		}
	    )
	    .teardown(
		function() {
		    // cleanup
		    width = null;
		    //$(':input.number').autoNumeric('init',{aSep: ' ', aDec: '.'});  
		    //console.log('mynitroa5 afterupdate'); 		    
		}
	    );*/
	
    });	
    
//$(document).ajaxComplete(function() {
//  console.log( "Triggered ajaxComplete handler." );
//});
});