// import AutoNumeric from "./AutoNumeric/AutoNumeric";

function menuUpdate() {
		intervalID = setInterval(function(){
			var objConfig = jQuery.parseJSON(jQuery('#configMain').text());				
                        var badgeUpdate = $('.badgeUpdate');
                        if (badgeUpdate.length > 0)
                        {
                            $.ajax({
                                    url: objConfig.menuBadgeUpdateUrl,
                                        type: 'get',
                                        context: this,
                                        dataType: 'json',
                                            off: ['unique'],
                                            start: function(data){
                                                    $("#loading").hide();
                                            },
                                        success: function(payload) {
						if (payload.snippets) {
						for (var i in payload.snippets) {
							$('#'+i).html(payload.snippets[i]);
						}					    
                                            }
					}
				    });
						}
                }, 120000);		    		    
                        

}


$(document).ready(function(){
	Dropzone.options.fileDropzone = false;
        //initSelect2Partner();
	initExtensions(); 
	startCountDown();
	//showDropdownAfterClick();

	//scroolBars();
	menuUpdate();
	storageReviewChangeStore();
	initGrid();
	confirm_nav();
	btNavigate();
	resizeChosen();//now select2
	$('[data-toggle="tooltip"]').tooltip();


	$(window).on('resize', resizeChosen);
	$(window).on('resize', function() {
		correctWindow();
		//$('.panel-body-fullsize').css('min-height', $(window).height()-75);
	});

	//unlock doc number button
	$(document).on('click','.unlock-doc-number',function(e)
	{
	    obj = $(this).parent().parent().find('input');
	    obj.prop('readonly','');
	    obj.focus();
	});
	
	//users rules settings
	$(document).on('click','[id*=enable_all]', function(e){
		$dataGroup = $(this).data('group');
		$('[id*=_enabled][data-group="' + $dataGroup + '"]').prop('checked',$(this).prop('checked'));
	});
	$(document).on('click','[id*=write_all]', function(e){
		$dataGroup = $(this).data('group');
		$('[id*=_write][data-group="' + $dataGroup + '"]').prop('checked',$(this).prop('checked'));
	});	
	$(document).on('click','[id*=erase_all]', function(e){
		$dataGroup = $(this).data('group');
		console.log($dataGroup);
		$('[id*=_erase][data-group="' + $dataGroup + '"]').prop('checked',$(this).prop('checked'));
	});	
	$(document).on('click','[id*=edit_all]', function(e){
		$dataGroup = $(this).data('group');
	    $('[id*=_edit][data-group="' + $dataGroup + '"]').prop('checked',$(this).prop('checked'));
	});	
	$(document).on('click','[id*=report_all]', function(e){
		$dataGroup = $(this).data('group');
		$('[id*=_report][data-group="' + $dataGroup + '"]').prop('checked',$(this).prop('checked'));
	});		
	$(document).on('click','[id*=cl_all]', function(e){
		$dataGroup = $(this).data('group');
		$('[id^=frm-edit-cl_][data-group="' + $dataGroup + '"]').prop('checked',$(this).prop('checked'));
	});	
	

	
	//look if user email exists
	$('#frm-registrationForm-email').on('keyup click blur focus select paste', function(e){
			var urlString = $(this).data('urlString');
			var valueToCheck = $(this).val();
			if (valueToCheck.indexOf('@') > -1  && valueToCheck.indexOf('.') > -1)
			{
			$.ajax({
				url: urlString,
				    type: 'get',
				    context: this,
				    data: 'email=' + valueToCheck ,
				    dataType: 'json',
				    success: function(data) {
					$(this).popover('destroy');	
					var myObj = $(this);
					if (data)
					    {
						timeoutID = window.setTimeout(function(e){
						    myObj.popover({content: 'Pro zadaný email již existuje účet.',placement: 'bottom', toggle: 'popover'});	    
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
			}else{
			    //if (valueToCheck.length>0)
//				$(this).popover('destroy');
			}
	});
	$('#frm-registrationForm-email').bind('autocompleteSelect', function() {
	  // Value chosen in autocomplete field.
	  // Do whatever you need to do.
	  //var chosen_value = $(this).val();
	  $(this).click();
	});	



	  //show and hide message window
	  var hideTimer = 0;
	  $(document).on('click','#btnMessage', function(e) {
		$('#defaultCountdown').countdown('destroy');
		startCountDown();  
	      if ($('#messBox').is(':hidden'))
	      {
		    $('#messBox').fadeIn();
		    hideTimer = setTimeout(function(){messBoxHide($('#messBox'));}, 5000);		    		    
		}
		else
		{
		    $('#messBox').fadeOut();

		}

	    });
	    $(document).on('mouseleave','#messBox', function(e){
			hideTimer = setTimeout(function(){messBoxHide($('#messBox'));}, 2000);
	    });
	    $(document).on('mouseenter','#messBox', function(e){
			clearTimeout(hideTimer);
	    });	    

	    function messBoxHide(e)
	    {
			e.fadeOut();
	    }
	    
	    
        //show and hide grid setting 
	  $(document).on('click','#btnGridSet', function(e) {
		$('#defaultCountdown').countdown('destroy');
		startCountDown();
	      if ($('#gridSetBox').is(':hidden'))
	      {
		    $('#gridSetBox').fadeIn();
		    hideTimer = setTimeout(function(){messBoxHide($('#gridSetBox'));}, 5000);		    		    
		}
		else
		{
		    $('#gridSetBox').fadeOut();
		}
	    });
	    
	    $(document).on('mouseleave','#gridSetBox', function(e){
			hideTimer = setTimeout(function(){messBoxHide($('#gridSetBox'));}, 2000);
	    });
	    $(document).on('mouseenter','#gridSetBox', function(e){
			clearTimeout(hideTimer);
	    });
		$(document).on('click','#setGridRows', function(e) {
			var gridRowsValue = $('#grid_rows_value').val();
			var enableAutoPaging = $('#enableAutoPaging').prop('checked');
			var urlString  = $(this).data('url');

			urlString = urlString + '&gridRowsValue=' + gridRowsValue + '&enableAutoPaging=' + enableAutoPaging;
			//console.log(urlString);

			window.location.href = urlString;
			/*$.ajax({
				url: urlString,
				type: 'get',
				context: this,
				data: 'gridRowsValue=' + gridRowsValue,
				dataType: 'text',
				success: function(payload) {
					$("#loading").hide();
					if (payload.snippets) {
						for (var i in payload.snippets) {
							$('#' + i).html(payload.snippets[i]);
						}
					}
				}
			});*/
		});

		//send number of rows after user hits enter on number of rows input
		$(document).on('keypress', '#grid_rows_value', function (e) {
			var charCode = e.charCode || e.keyCode;
			if (charCode  == 13 ) { //Enter, tab key's keycode|| charCode  == 9
				$(this).parent().find('#setGridRows').focus().click();
				//$(this).parent().find('#setGridRows').click();
			}
			e.stopPropagation();
		});

	    
	    
	//grid setting interaction
	//grid set

	//edit set
	 $(document).on('click','.editSetChk', function(e) {
			var key  = $(this).data('key');
			var urlString  = $(this).data('urlajax');
			$.ajax({
				url: urlString,
				    type: 'get',
				    context: this,
				    data: 'key=' + key + '&value=' + this.checked ,
				    dataType: 'json',
				    success: function(data) {
					$("#loading").hide();
					//$('#gridSetBox').show();
					}
				    }); 
	 });	 



	//update snippet with pricelis after selecting partner
	$(document).on('select2:select','#frm-edit-cl_partners_book_id', function(e) {
	    //if (e.originalEvent) {
		urlString  = $('.customUrl').data('url-pricelist2');
		data = $(this).val();


		//console.log(urlString);
		//console.log(data);
		$.ajax({
			url: urlString,
			    type: 'get',
			    context: this,
			    data: 'cl_partners_book_id=' + data,
			    dataType: 'json',
			    success: function(data) {
					//$('#gridSetBox').show();
					if (typeof setDueDate !== 'undefined'){
						    setDueDate(true);
						}
					}
			    }); 	
		//$("#loading").show();				    

		urlString2  = $(this).data('url-update-partner-in-form');
		//alert(urlString2);
		data = $(this).val();

		$.ajax({
			url: urlString2,
			    type: 'get',
			    context: this,
			    data: 'cl_partners_book_id=' + data ,
			    dataType: 'json',
			    success: function(data) {
				console.log(data['cl_users_id']);
				if (data['cl_users_id'] != null) {
					$('#frm-edit-cl_users_id').val(data['cl_users_id']).trigger('change');
				}
				
				dropdown = $('#frm-edit-cl_partners_book_workers_id');
				dropdown.empty();

				  $.each(data['cl_partners_book_workers_id_values'], function (key, entry) {
				      dropdown.append($('<optgroup></optgroup>').attr('label', key));
				      $.each(entry, function (key2, entry2) {
					dropdown.append($('<option></option>').attr('value', key2).text(entry2));
				      });
				  });
				  
				  dropdown = $('#frm-edit-cl_partners_branch_id');
				  dropdown.empty();				  
				  $.each(data['cl_partners_branch_id_values'], function (key, entry) {
					dropdown.append($('<option></option>').attr('value', key).text(entry));
				  });

					dropdown = $('#frm-edit-cl_partners_account_id');
					if (dropdown.length > 0) {
						dropdown.empty();
						$.each(data['cl_partners_account_id_values'], function (key, entry) {
							dropdown.append($('<option></option>').attr('value', key).text(entry));
						});
					}


				$('#frm-edit-cl_partners_book_workers_id').val(data['cl_partners_book_workers_id']).trigger('change');

				$('#partner_card').prop('href', data['partnerCard']);
				$('#partner_card').data('href', data['partnerCardData']);
				
				//$('#gridSetBox').show();
				}
			    });

				showComment();

		//$("#loading").show();				    
		e.preventDefault();
		e.stopImmediatePropagation();				
	   // }	    
	});
	

	
	//enable public_event
	 $(document).on('click','#public_event', function(e) {
			if ($('#public_event').prop('checked'))
			    data = 1;
			else
			    data = 0;
			
			var urlString  = $(this).data('urlajax');
			var dataName = encodeURI($('#frm-edit-name').val());
			
			var a = document.createElement('a');
			finalUrl = urlString + "&value="+ data + '&name=' + dataName;
			a.href = finalUrl;
			//a.setAttribute('data-transition', transition);
			a.setAttribute('data-history', 'false');
			_context.invoke(function(di) {
			    di.getService('page').openLink(a);
			});				
			
	 });
    
	//enable public storage
	 $(document).on('click','#public_storage', function(e) {
			if ($('#public_storage').prop('checked'))
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
	enterToTab();
	textAreaEnter();
	//submit edit form via button outside form
	$("#nhSend").click(function() {
           $('#frm-edit input[name="send"]').click();
	});	
	$("#nhSend_fin").click(function() {
	    if ($(this).data('onclick') !== undefined)
	    {
		strMessage = $(this).data('onclick');
		if (strMessage.length > 0)
		{
		    if (confirm(strMessage))
		    {
			$('#frm-edit input[name="send_fin"]').click();
		    }
		}
	    }else{
		$('#frm-edit input[name="send_fin"]').click();
	    }
	});		
	$("#nhCreate_invoice").click(function() {
           $('#frm-edit input[name="create_invoice"]').click();
	});		
	$("#nhStore_out").click(function() {
           $('#frm-edit input[name="store_out"]').click();
	});			
	$("#nhSave_pdf").click(function() {
           $('#frm-edit input[name="save_pdf"]').click();
	});		
	$("#nhBack").click(function() {
           $('#frm-edit input[name="back"]').click();
	});		
	
	
	//modal windows
	$(document).on('click',".modalClick", function(e){
	    e.preventDefault();
		if ($('#myModal').hasClass("min")){
			showMinModal();
			$('#myModal').toggleClass("min");
		}
	    $('#myModal iframe').contents().find('html').html('');
	    $('#ifrm').prop('src',$(this).data('href'));
	    $('#myModal h4').html($(this).data('title'));
	    //e.preventDefault();
	    e.stopPropagation();
	    $('#myModal').modal();
	});

	//universal close button for modal
	$(document).on('click', "#CloseModal", function(e){
		parent.$('#myModal').modal('hide');
	});
	
        
	$('#myModal').on('hidden.bs.modal', function (e) {
	    //on end of work with modal window update needed snippets
	    if($("#snippetsUrl").data('url-snippetsupdate') !== undefined){
		url = $("#snippetsUrl").data('url-snippetsupdate');		
		$.ajax({
			url: url,
			type: 'get',
			context: this,
			data: '',
			dataType: 'json',
			success: function(data) {
			    //$("#loading").hide();
			    //$('#gridSetBox').show();
			    }
			}); 		
		}

	});

	//16.02.2020 - extract modal
	$(document).on('click', '.modalExtract', function(e){
		if ($('#ifrm').length > 0){
			url = $('#ifrm').prop('src');
		}
		if ($('#ifrmh').length > 0) {
			url = $('#ifrmh').prop('src');
		}
		window.open(url);
	});

	//16.02.2020 - minimize and maximaze modal
	$(document).on('click', '.modalMinimize', function(e){
		$modalCon = $(".modal2min").attr('id');
		$minTitle = $(this).parent().closest('.modal-header').html();
		$modal = "#" + $modalCon;
		$($modal).toggleClass("min");
		if ($($modal).hasClass("min") ){
			$('#myModal').css("position","absolute");
			$('#myModal').css("height","99vh");
			$(".modal-backdrop").addClass("display-none");
			//$('.modal2min').slideUp("fast", function() {
			$('.modal2min').animate({"top": "100%" },500 , function() {
				//alert('ted');
				$(".modal2min").addClass("display-none");
				$(".minmaxCon").html($minTitle);
				$(".modalMinimize.minimize").addClass("display-none");
				$(".modalClose").addClass("display-none");
				$(".modalMinimize.maximize").removeClass("display-none");
				$(this).find("i").toggleClass( 'fa-minus').toggleClass( 'fa-clone');
				$('.minmaxCon').show();
				}
			);
		} else {
			$('#myModal').css("position","fixed");
			showMinModal();
		};
	});

	function showMinModal(){
		$('.minmaxCon').hide();
		$(".modal-backdrop").removeClass("display-none");
		$(".modal2min").removeClass("display-none");
		$(".modalMinimize.maximize").addClass("display-none");
		$(".modalMinimize.minimize").removeClass("display-none");
		$(".modalClose").removeClass("display-none");

		//$('.modal2min').slideDown("fast", function() {
		$('.modal2min').animate({"top": "0" },500 , function() {

				//$(this).find("i").toggleClass( 'fa-clone').toggleClass( 'fa-minus');
			}
		);
	}

	//show and hide filters row
	$(document).on('mouseup', '#filterButton', function(e)
	{
	    if ($('.filterColumns').css('display') == 'none')
	    {
		//$('.filterColumns').css('display','inline');
		$('.filterColumns').show(250, function() {
		    
		    //console.log('show filters');   
		});
	    }else{
		$('.filterColumns').hide(250, function () {
		});
		var urlString = $(this).data('href');
		var a = document.createElement('a');
		finalUrl = urlString;
		a.href = finalUrl;
		//a.setAttribute('data-transition', transition);
		a.setAttribute('data-history', 'false');
		_context.invoke(function(di) {
		    di.getService('page').openLink(a);
		});					    

	    }
	});

	//select report
	$(document).on('change','.report-select', function(e) {
		var urlString  = $(this).data('url');
		var data = $(this).val();
		$.ajax({
			url: urlString,
			type: 'get',
			context: this,
			data: 'index=' + data ,
			dataType: 'json',
			success: function(data) {
				$("#loading").hide();
				//$('#gridSetBox').show();
			}
		});
	});




});

	function btNavigate() {
		$(document).on('click', '#bottomAnchor', function (e) {
			//scroll to top
			//window.scrollTo(0, 0);
			$('html, body').animate({
				scrollTop: ($('body').offset().top)
			}, 500);
		});

		$(document).on('click', '#topAnchor', function (e) {
			posY = parseFloat($('#snippet--bsc-child').offset().top);
			lastActive = parseFloat($('#snippet--bsc-child').position().top);
			var rowpos = lastActive - 10;
			if (rowpos > 0) {
				//var rowpos = 60;
				//window.scrollTo(0, rowpos);
				$('html, body').animate({
					scrollTop: rowpos
				}, 500);
			}
		});
	}
		$(window).scroll(function()
		{
			//return;
			$topPanel = $('#snippet--content>div.containerMy>div>div.panel-heading');
			$topPanel2 = $('#snippet--content>div.containerMy>div');
			$bottomPanel = $('#snippet--bsc-child>div>div.panel-body-fullsize');
			//console.log('data:' + $topPanel.data('position_top'));
			//console.log('now:' + $topPanel.position().top);
			test = window.pageYOffset - $('.table-wraper').height();
				//$topPanel2.position().top;
			//console.log(test);
			if (test > 0 && $topPanel.css('position') == 'sticky'){
				console.log('jsme nahore');
				$topPanel.css('position','fixed');
				$topPanel.css('width',$topPanel2.width()-2 + 'px');
				$bottomPanel.css('position', 'relative');
				$bottomPanel.css('top', '40px');
				//$('.panel-body-fullsize').css('height', ($('.table-wraper').height() + 60) + 'px');
			}else{
				if ( test < 0){
					console.log('nejsme nahore');
					$topPanel.css('position','sticky');
					$bottomPanel.css('position', 'initial');
					$bottomPanel.css('top', 'initial');
				}
			}

			if ($topPanel.length > 0 && $bottomPanel.length > 0) {
				$topPanel.data('position_top', $topPanel.position().top);
				$bottomPanel.data('position_top', $bottomPanel.position().top);
			}
		});


	function hideTopMenu(obj)
	{
		if ( $('.navbar-toggle :visible').length==0 && obj.data('showed') == 0 )
		{
		    obj.animate({top:"-32px"}, 200, function(e) {
			// Animation complete.
			    obj.find('.navbar').css("margin-bottom","0px");
			    obj.find('.navbar').animate({height:"58px"}, 100);					    
			    $("#myTopMenu").find('.navbar span.glyphicon').css('visibility','hidden');		    
			    $("#myTopMenu").removeClass('myMenuShow');
			    $("#myTopMenu").addClass('myMenu');		    
			    obj.data('showed',0);
			    //e.stopImmediatePropagation();
			});
		}	    
	    
	}

function showComment() {
	var objConfig = jQuery.parseJSON(jQuery('#configMain').text());
	var idPartner = $('#frm-edit-cl_partners_book_id').val();
	if (idPartner !== undefined) {
		var urlString = objConfig.showCommentUrl;
		$.ajax({
			url: urlString,
			type: 'get',
			context: this,
			data: 'cl_partners_book_id=' + idPartner,
			dataType: 'json',
			success: function (payload) {
				if (payload.snippets) {
					for (var i in payload.snippets) {
						$('#' + i).html(payload.snippets[i]);
					}
					$( "#showComment" ).draggable({
						start: function() {

						},
						drag: function() {

						},
						stop: function() {
							var urlString = objConfig.saveCommentW;
							obj = $('#showComment');
							var posX = parseInt(obj.css('left'));
							var posY = parseInt(obj.css('top'));
							$.ajax({
								url: urlString,
								type: 'get',
								context: this,
								dataType: 'json',
								data: 'posX=' + posX + '&posY=' + posY,
								success: function (payload) {


								}
							});
						}
					});

					$("#loading").hide();
				}
			}
		});
	}

}


function enterToTab()
	{
	    //turn off enter submit on inputs
	   $(document).on('keypress', "input[type!='submit'], select", function (e) {
			//Determine where our character code is coming from within the event
			var charCode = e.charCode || e.keyCode;
			if (charCode  == 13) { //Enter key's keycode
				e.preventDefault();
				var inputs = $(this).closest('form').find(':input:visible:not([readonly])');
				var thisId = $(this).prop('id');
				var founded = false;
				inputs.each(function(index) {
					if (thisId == $(this).prop('id')){
						founded = true;
					}
					if (founded && thisId != $(this).prop('id') && ( $(this).data('e100p') == undefined || parseFloat($(this).data('e100p')) > 0 || $(this).prop('name') == 'sendLine'))
					{
						$(this).select().focus();
						return false;
					}
				});
			}
	    });

	   	selectCombo = $('.chzn-select-req, .chzn-select');
		selectCombo.on('select2:close', function (e) {
			//move focus to next element
			select = $('form.editLine:visible').find('.chzn-select-req');
			//select.select2('close');
			var inputs = select.parent().parent().find(':input:visible:not([readonly])');
			setTimeout(function() {
				inputs.eq(inputs.index(select) + 1).focus();
			}, 500);
			e.stopImmediatePropagation();
		});

		$(document).on('keyup', '.datepicker:focus', function(e) {
			if (e.which === 32 && $(this).val().length == 0) {
				//e.preventDefault();
				//var d = new Date();
				var d = new Date().toLocaleString("cs-CZ", {year: 'numeric', month: '2-digit', day: '2-digit'});
				d = d.replaceAll(' ','');
				//var strDate =  d.getDate() + "." + (d.getMonth()+1) + "." + d.getFullYear();
				$(this).val(d);
			}
		});
	}

	function textAreaEnter()
	{
		$(document).on('keypress', "textarea", function (e) {
			//Determine where our character code is coming from within the event
			var charCode = e.charCode || e.keyCode;
			if (charCode == 13) { //Enter key's keycode
				hTextArea = parseInt($(this).css('height'));
				if (hTextArea < 30){
					e.preventDefault();
					var inputs = $(this).closest('form').find(':input:visible:not([readonly])');
					var thisId = $(this).prop('id');
					var founded = false;
					inputs.each(function(index) {
						if (thisId == $(this).prop('id')){
							founded = true;
						}
						if (founded && thisId != $(this).prop('id') && ( $(this).data('e100p') == undefined || parseFloat($(this).data('e100p')) > 0 || $(this).prop('name') == 'sendLine'))
						{
							$(this).select().focus();
							return false;
						}
					});
				}

			}
		});
	}
	
	function initListGrid()
	{

	    //confirm dialog with disabling nette ajax on false
	    $(".myConfirm").on('click', function(e){
		lResult = confirm($(this).data('onclick'));
		if (lResult)
		{
		    return;
		}else
		{
		    e.preventDefault();
		    e.stopImmediatePropagation();
		    return false;
		}
	    });
	    
	    //vse pro listgridy
	    $('.listgrid input.number').autoNumeric('init',{aSep: ' ', aDec: '.'});
//		new AutoNumeric('.listgrid input.number', {aSep: ' ', aDec: '.'});
		$('.listgrid input.text-number').autoNumeric('init',{aSep: '', aDec: ' ', aPad: false, vMax: '9999999', vMin: '0'});
	    
	    
	    $("input[type='text'], input[type='number']").on("focus", function () {
			$(this).select();
	    });	    	    
	    
	    //prevent selectbox witch is readonly before dropdown
	    $('select[readonly=readonly]').on('focus mousedown', function(e) {
		    this.blur();
		    window.focus();
		    e.preventDefault();
		
	    });
	    //partnersky cenik
	    $(document).on('focusout','#frm-partnerPriceListGrid-editLine-price, #frm-partnerPriceListGrid-editLine-vat', function (e) {	    
		    vat = $('#frm-partnerPriceListGrid-editLine-vat').val().split(' ').join('').replace(',','.');
		    priceVat = (1+(vat/100)) * $('#frm-partnerPriceListGrid-editLine-price').val().split(' ').join('').replace(',','.');
		    priceVat = Math.round(priceVat * 100) / 100;	
		    $("#frm-partnerPriceListGrid-editLine-price_vat").val(priceVat);
		    $("#frm-partnerPriceListGrid-editLine-price_vat").autoNumeric('update');		    
	    });

	    $(document).on('focusout','#frm-partnerPriceListGrid-editLine-price_vat', function (e) {	    
		    vat = $('#frm-partnerPriceListGrid-editLine-vat').val().split(' ').join('').replace(',','.');
		    price = $('#frm-partnerPriceListGrid-editLine-price_vat').val().split(' ').join('').replace(',','.') / (1+(vat/100));
		    price = Math.round(price * 100) / 100;	
		    $("#frm-partnerPriceListGrid-editLine-price").val(price);
		    $("#frm-partnerPriceListGrid-editLine-price").autoNumeric('update');		    
	    });
	    //konec partnerskeho ceniku

	}
	
	function initGrid()
	{
	    initAutocomplete();
	  //hide and unhide 
	  $(document).on('click', '.packingRow', function (e) {
		  id = $(this).data('id');
			urlString = $(this).data('href');
			if ($('.hiderow'+id).css('display') == 'none')
			{
				data = 0;
				$(this).find('i').removeClass('glyphicon-collapse-down');		  
				$(this).find('i').addClass('glyphicon-collapse-up');				
				$(this).prop('title','zabalit');
			}
			else
			{
				data = 1;
				$(this).find('i').removeClass('glyphicon-collapse-up');		  
				$(this).find('i').addClass('glyphicon-collapse-down');								
				$(this).prop('title','rozbalit');
			}
			
		   $.ajax({
				  url: urlString,
					  type: 'get',
					  context: this,
					  data: 'packed=' + data,
					  dataType: 'json',
					  off: ['unique'],
						start: function(){
						$("#loading").hide();
						},					  
					  success: function(data) {

					  }			  
				  });		  
		  $('.hiderow'+id).each(function() {
			  displayed = $(this).css('display');
			  if ( displayed == 'none')
			  {
				$(this).show(250);
			  }else{
				$(this).hide(250);  
			  }

		  });

		  
		  e.preventDefault();
		  e.stopImmediatePropagation();
			//$('.hiderow235').hide(1000)	  		  
	  });

	    //grid search function for tables
	    $(document).on('click', '.filterColumns button', function(e) {
		filterColumn = $(this).data('filterColumn');
		filterValue = $(this).parent().parent().find('input').val();
		url = $(this).data('url');
		window.location.href=url+'&filterColumn='+filterColumn+'&filterValue='+filterValue;

	    });

	    //click after hit Enter in input box with filter column
	    $(document).on('keypress','.filterColumns .input-filter-table input', function(e)
	    {
		 //alert(e.value());
			if (e.which == 13)
			{
				filterColumn = $(this).data('filterColumn');
				filterValue = $(this).parent().parent().find('input').val();

				//console.log(filterValue);
				strUrl = $(this).data('url');
				callFilter(strUrl, filterColumn, filterValue);
			}
	    });

	    $(document).on('click', '.btn-dtm-filter', function(e){
			//11.11.2021 - solution for date range filter
			oDtmFrom = $(this).parent().find('input[name="dateFrom"]');
			var dtmFrom = oDtmFrom.val();
			var dtmTo = $(this).parent().find('input[name="dateTo"]').val();
			strUrl = oDtmFrom.data('url');
			filterValue = dtmFrom + '-' + dtmTo;
			filterColumn = oDtmFrom.data('filterColumn');

			callFilter(strUrl, filterColumn, filterValue);
		});

	    function callFilter(strUrl, filterCol, filterVal){
			url = strUrl + '&filterColumn=' + filterCol + '&filterValue=' + filterVal;
			var a = document.createElement('a');
			finalUrl = url;
			var params = [];
			params.filterColumn = filterCol;
			params.filterValue = filterVal;
			a.href = finalUrl;
			a.setAttribute('data-history', 'false');
			_context.invoke(function(di) {
				di.getService('page').openLink(a);
			});

		}

	    //filter after selecting from quickfiter
	    $(document).on('select2:select','.quickfilter', function(e) {
				filterColumn = $(this).data('filtercolumn');
				filterValue = $(this).val();
				url = $(this).data('url')+'&filterColumn='+filterColumn+'&filterValue='+filterValue;

				var a = document.createElement('a');
				finalUrl = url;
				var params = [];
				params.filterColumn = filterColumn;
				params.filterValue = filterValue;
				a.href = finalUrl;
				a.setAttribute('data-history', 'false');
				_context.invoke(function(di) {
					di.getService('page').openLink(a);
				});

				}
	    );	      
	    //open edit url
	    $(document).on("click", '.openEdit', function() {
		    if($(this).data('url') !== undefined){
			document.location = $(this).data('url');
		    }
	    });	
	}
	
	function initAutocomplete()
	{
	  //grid autocomplete function
		$(".autocomplete").each(function () {
		var self = $(this);

		self.easyAutocomplete({
			minCharNumber: 3,
		    url: function(phrase) {
			    acSource = self.data('acSource');
			    return "?do=search&acSource=" + acSource + "&term=" + phrase;
		    },
		    getValue: "label",

		    theme: "square",
		    list: {
			onShowListEvent: function() {
			    console.log('autocomplete showlist event');
			    $('.floatThead-container').css('height','100%');
				},
			onChooseEvent: 	 function() {
			    console.log('autocomplete onchooseevent');
			    $('.floatThead-container').css('height','auto');
			    var e = jQuery.Event("keypress");
			    e.which = 13; // # Some key code value
			    self.trigger(e);			    
			    }
			}


		});
	    });	    
	    

	}

	
	
	function scroolGrid()
	{
	    //alert('tedt');
	    posY = $('.lastActive').position();
	    if (posY.top < 60)
	    {
		$('.table-wraper').scrollTop(0);
	    }
	    if (posY.top > 250)
	    {
		$('.table-wraper').scrollTop(250);
	    }
	}

	function scroolBars()
	{
	    $('.scrollBox .panel-body').enscroll({
		showOnHover: false,
		verticalTrackClass: 'track3',
		verticalHandleClass: 'handle3'
	    });	    
	    
	    $('.scrollBoxSmall').enscroll({
		showOnHover: false,
		verticalTrackClass: 'track3',
		verticalHandleClass: 'handle3'
	    });	    	    
	    $('.scrollBoxGrid').enscroll({
		showOnHover: false,
		verticalScrolling: false,
		horizontalScrolling: true,
		verticalTrackClass: 'track3',
		verticalHandleClass: 'handle3'
	    });	    	    	    
	}
	
	function resizeChosen() {
	   $(".select2-container").each(function() {
	      $(this).attr('style', 'width: 100%');
	   });
		oPartnersBook = $('#frm-edit-cl_partners_book_id').next().find('.select2-selection--single');
		selectWidth = oPartnersBook.parent().parent().parent().parent().parent().find('#partner_group').width();
		addonWidth = oPartnersBook.parent().parent().parent().find('.myaddon').width();
		oPartnersBook.width(selectWidth - addonWidth + 10);

	}

	function startCountDown()
	{
		var actDate = Date(); 	    
		    $('#defaultCountdown').countdown({ until: dateAdd(actDate,'second',7200), compact: true, onTick: warnUser, 
			layout: '{hnn}{sep}{mnn}{sep}{snn} {desc}', 
			description: ''});	
	}

	function warnUser(periods) { 
                if ($.countdown.periodsToSeconds(periods) == 300) { 
                    alert("Více než 120 minut jste nebyli aktivní. Za 5 minut budete automaticky odhlášeni!"); 
                } 
                if ($.countdown.periodsToSeconds(periods) == 0) { 
                    //alert("Více než 15 minut jste nebyli aktivní. Za 5 minut budete automaticky odhlášeni!"); 
		    var objConfig = jQuery.parseJSON(jQuery('#configMain').text());
		    //location.href = objConfig.baseUrl + '/application/?do=logout';
		    location.reload();
                } 		
            }      
	    
	function dateAdd(date, interval, units) {
	  var ret = new Date(date); //don't change original date
	  switch(interval.toLowerCase()) {
	    case 'year'   :  ret.setFullYear(ret.getFullYear() + units);  break;
	    case 'quarter':  ret.setMonth(ret.getMonth() + 3*units);  break;
	    case 'month'  :  ret.setMonth(ret.getMonth() + units);  break;
	    case 'week'   :  ret.setDate(ret.getDate() + 7*units);  break;
	    case 'day'    :  ret.setDate(ret.getDate() + units);  break;
	    case 'hour'   :  ret.setTime(ret.getTime() + units*3600000);  break;
	    case 'minute' :  ret.setTime(ret.getTime() + units*60000);  break;
	    case 'second' :  ret.setTime(ret.getTime() + units*1000);  break;
	    default       :  ret = undefined;  break;
	  }
	  return ret;
	}	   

function storageReviewChangeStore()
{
	//review_cl_storage_id
	$(document).on('change','#review_cl_storage_id', function(e) {
	    urlString  = $('#review_cl_storage_id').data('url-ajax');
	    data = $('#review_cl_storage_id').val();
	    var a = document.createElement('a');
	    finalUrl = urlString;
	    a.href = finalUrl+"&storageId=" + data;
	    a.setAttribute('data-history', 'false');
	    _context.invoke(function(di) {
		di.getService('page').openLink(a).then( function(){ 
		});
	    });
	});		
}

//23.06.2016 - Tomas Halasz
//confirmation on changed form in nav tabs 
	var confirm_nav_ignore = [];
	function confirm_nav(forceConfirm = false) {
		window.onbeforeunload = function(){
			counterForm = 0;
			$('form:visible').each(function(){
				var $form = $(this);
				var old_state = strip($form.data('old_state'));
				var new_state = strip(mySerialize($form));
				if (new_state !== old_state && old_state != undefined && new_state != undefined ) {
					counterForm++;
					if (counterForm > 1)
						return;
					if (old_state == 'xxx')
					{
						lcMess = "Aktuálně otevřený formulář je potřeba uzavřít abyste mohli pokračovat dále.";
					}else{
						lcMess = "Pokud jste na stránce něco změnili, je potřeba změny uložit, pokud o ně nechcete přijít.";
					}
					bootbox.alert({
						message: lcMess,
						callback: function () {
							counterForm = 0;
						}
					});
				}

			});
			if (counterForm >= 1)
				return 'dddd';
		};

		function save_form_state($form) {
			if ($form.data('old_state') != 'xxx')
			{
				var old_state = mySerialize($form);
				$form.data('old_state', old_state);
			}
		}

		function mySerialize($form){
			var myform = $form;
			// Find disabled inputs, and remove the "disabled" attribute
			var disabled = myform.find(':input:disabled').removeAttr('disabled');
			// serialize the form
			var serialized = myform.serialize();
			// re-disabled the set of inputs that you previously enabled
			disabled.attr('disabled','disabled');

			return serialized;
		}

		// On load, save form current state
		$('form:visible').each(function(){
			save_form_state($(this));
		});

		// On submit, save form current state
		$('form:visible').submit(function(){
			save_form_state($(this));
		});
		// strip fields that we should ignore
		function strip(form_data) {
			for (var i=0; i<confirm_nav_ignore.length; i++) {
				var field = confirm_nav_ignore[i];
				form_data = form_data.replace(new RegExp("\\b"+field+"=[^&]*"), '');
			}
			return form_data;
		}

		$('a[data-toggle="tab"].bscTab').on('shown.bs.tab', function (e) {
			urldata = $(e.target).data('urltab');
			$.ajax({
				url: urldata,
				type: 'get',
				context: this,
				success: function(data) {
					resizeChosen();
					$('form:visible').each(function(){
						save_form_state($(this));
					});
				}
			});
		})

		counterForm = 0;		
		$('a[data-toggle="tab"][data-notCheck!="1"]').on('hide.bs.tab', function (e) {
			var tabNew = $(e.relatedTarget).attr('aria-controls');
			var tabCheck = $(e.target).attr('aria-controls');
			$('#'+tabCheck).find('form').each(function(){
				var $form = $(this);
				var old_state = strip($form.data('old_state'));
				var new_state = strip(mySerialize($form));
				if (new_state !== old_state && old_state != undefined && new_state != undefined) {
					counterForm++;
					if (counterForm > 1)
						return;

					if (old_state == 'xxx')
					{
						lcMess = "Aktuálně otevřený formulář je potřeba uzavřít abyste mohli pokračovat dále.";
					}else{
						lcMess = "Ve formuláři jste něco změnili. Je potřeba jej uložit, pokud nechcete o změny přijít.";
					}
					e.preventDefault();
					e.stopImmediatePropagation();
					bootbox.alert({
						message: lcMess,
						callback: function () {
							counterForm = 0;
							$('a[href="#'+tabCheck+'"]').click();
							$('a[href="#'+tabCheck+'"]').focus();

						}
					});
				}   			
				});
			});



		//20.02.2019 - this part is for bschild 
		counterForm = 0;		
		$('a[data-toggle="tab"][data-notCheck!="1"] .bscTab').on('click', function (e) {
			var tabNew = $(this).attr('data-key');
			var tabCheck = $(this).parent().parent().find('.active').find('.bscTab').attr('data-key');
			$('#'+tabCheck).find('form').each(function(){
				var $form = $(this);
				var old_state = strip($form.data('old_state'));
				var new_state = strip(mySerialize($form));

				if (new_state !== old_state  && old_state != undefined && new_state != undefined) {
					counterForm++;
					if (counterForm > 1)
						return;

					if (old_state == 'xxx')
					{
						lcMess = "Aktuálně otevřený formulář je potřeba uzavřít abyste mohli pokračovat dále.";
					}else{
						lcMess = "Ve formuláři jste něco změnili. Je potřeba jej uložit, pokud nechcete o změny přijít.";
					}
					e.preventDefault();
					e.stopImmediatePropagation();
					bootbox.alert({
						message: lcMess,
						callback: function () {
							counterForm = 0;
							$('a[href="#'+tabCheck+'"]').click();
							$('a[href="#'+tabCheck+'"]').focus();

						}
					});
				}   			
				});
			});
			
			
		//20.02.2019 - this part is for master grid for bschild and for any other link
		counterForm = 0;		
		$(document).on('click', 'td.openEdit2, a:not(.bscTab, .modalClick, .unlock-doc-number, .color-btn, a[data-toggle="tab"], a[data-not-check="1"], a[data-color], a[data-toggle="dropdown"], a[id="aresLink"], #partner_card),' +
								'.btn:not(#aresLink, .trumbowyg-save, .unlock-doc-number, #frm-sendOrder [type="submit"], [data-not-check="1"], ' +
								'#frm-edit [type="submit"], #frm-chat-edit [type="submit"], #frm-editSettings [type="submit"], #frm-email-emailForm [type="submit"], form.editLine .btn, #frm-groupActions [type="submit"], .spinner div button, a[data-not-check="1"])', function(e){

									//#frm-chat-edit,
			$('form:visible:not(#frm-userFilter, #frm-searchStore, #frm-searchCommission, form[id^="frm-report"], form[id$="-search"], form[id$="-searchItem"], form[id$="-priceList"])').each(function(){
				var $form = $(this);
				var old_state = strip($form.data('old_state'));
				var new_state = strip(mySerialize($form));

				if (new_state !== old_state && old_state != undefined && new_state != undefined) {
					counterForm++;
					if (counterForm > 1)
						return;

					e.preventDefault();
					e.stopImmediatePropagation();

					if (old_state == 'xxx')
					{
						lcMess = "Aktuálně otevřený formulář je potřeba uzavřít abyste mohli pokračovat dále.";
					}else{
						lcMess = "Ve formuláři jste něco změnili. Je potřeba jej uložit, pokud nechcete o změny přijít.";
					}

					bootbox.alert({
						message: lcMess,
						callback: function () {
							counterForm = 0;
						}
					});
					}
				});
			});

	function visibleFormCheck()
	{
		$('form:visible').each(function(){
			var $form = $(this);
			var old_state = strip($form.data('old_state'));
			var new_state = strip(mySerialize($form));

			if (new_state !== old_state && old_state != undefined && new_state != undefined) {
				counterForm++;
				if (counterForm > 1)
					return;
				//alert('Ve formuláři jsou neuložené změny! Nejprve jej uložte');
				e.preventDefault();
				e.stopImmediatePropagation();

				bootbox.alert({
					message: "Ve formuláři jste něco změnili. Je potřeba jej uložit, pokud nechcete o změny přijít.",
					callback: function () {
						counterForm = 0;

					}
				});
			}
		});
	}

	}
		
		
$(document).ready(function(){
	sortableDefTableHeader();
});

function sortableDefTableHeader(){

//modifier for sorting jquery
	var fixHelperModified = function(e, tr) {
	var $originals = tr.children();
	var $helper = tr.clone();
	$helper.children().each(function(index)
	{
	  $(this).width($originals.eq(index).width())
	});
	return $helper;
	};	    

	//user sorting functions
	
	$('.changeOrderTableHeader  tbody').sortable({
		start: function(event, ui) {
				var start_pos = ui.item.index();
				ui.item.data('oldOrder', start_pos);
			},		
		update: function( event, ui ) {
			var newOrder = ui.item.index();
			var controlname = ui.item.data('controlname');
			var url = ui.item.data('url');
			var oldOrder = ui.item.data('oldOrder');
			var tablename = ui.item.data('table-name');
			var cols = [];
			var i = 0;
			$("#table-"+controlname+' tr').each(function(){
			    $(this).find('td:first').each(function(){
				//do your stuff, you can use $(this) to get current cell
				var tmpKey = $(this).parent().data('rowid');
				var tmpData = $(this).parent().data('rowdata');
				if (tmpKey != '')
				{
					cols[i] = [tmpKey,tmpData];
				    i++;
				}
				
			    })
			})
			var a = document.createElement('a');
			finalUrl = url + '&table_name=' + tablename + '&columns=' + JSON.stringify(cols);
			console.log(finalUrl);
			a.href = finalUrl;
			a.setAttribute('data-history', 'false');
			_context.invoke(function(di) {
			    di.getService('page').openLink(a);
			});
		},
		helper: fixHelperModified
	});		
}

function correctWindow()
{
	if ($('.panel-body-fullsize').length > 0) {
		$('body').height($(window).height()); //important for support-form screenshots
		$lnHeight = $(window).height();
		$lnHeight = $lnHeight - $('.panel-body-fullsize').position().top - 50;
		if ($('.quicksums').length > 0)
			$lnHeight = $lnHeight - $('.quicksums').height();

		$('.panel-body-fullsize').css('height', $lnHeight);
		$('.table-wraper').css('height', $lnHeight);

	}
}


