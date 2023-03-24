/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 11.6.2016 - 16:00:55
 * 
 */

//initFilesDropzone();

	
	//events - update category price after changing cl_partners_event_method_id
	$(document).on('select2:select','#frm-edit-cl_partners_event_method_id', function(e) {
		var frm_cl_partners_category_id = $(this).data('master_partners_category_id');
		var cl_partners_category = this.form[frm_cl_partners_category_id];
		
		updateCategory(cl_partners_category);		
		e.preventDefault();
		e.stopImmediatePropagation();					    
	});	

	//events - update react time after changing cl_partners_category_id
	$(document).on('select2:select','#frm-edit-cl_partners_category_id', function(e) {
		getReactTime();
		e.preventDefault();
		e.stopImmediatePropagation();					    
	});

	//events - update cl_partners_book_workers list
	$(document).on('select2:select','select[data-partnersbook]', function(e) {
		var partners_book = this;
		var myParams = new Object();		
		var urlString  = $(partners_book).data('url-ajax');
		myParams.cl_partners_book_id = $(partners_book).val();

                //set new id to href in url to open edit card
                var tmpHref = $('#partner_card').attr('href');
                tmpHref = tmpHref.substr(0,tmpHref.lastIndexOf("/"));
                tmpHref = tmpHref + "/" + $(partners_book).val();
                $('#partner_card').attr('href',tmpHref);
                var tmpDataHref = $('#partner_card').data('href');
                tmpDataHref1 = tmpDataHref.substr(0,tmpDataHref.lastIndexOf("/edit"));
                tmpDataHref2 = tmpDataHref.substr(tmpDataHref.indexOf("?"));
                tmpDataHref = tmpDataHref1 + "/edit/" + $(partners_book).val() + tmpDataHref2;                
                $('#partner_card').data('href',tmpDataHref);
		
		//update worker info
		urlString  = $('#frm-edit-cl_partners_book_workers_id').data('url-ajax');
		data = $('#frm-edit-cl_partners_book_workers_id').val();

		$.ajax({
			url: urlString,
			    type: 'get',
			    context: this,
			    data: 'cl_partners_book_workers_id=' + data,
			    dataType: 'json',
			    success: function(data) {
						updateWorkerInfo(data);
					}
			    }); 			

		var frm_cl_partners_category_id = $(partners_book).data('master_partners_category_id');
		var cl_partners_category = partners_book.form[frm_cl_partners_category_id];
		updateCategory(cl_partners_category);

		updateCommission();
		//e.preventDefault();
		//e.stopImmediatePropagation();

	});

	function updateCommission(){
		var urlCommission  = $('#frm-edit-cl_commission_id').data('url-commission');
		var myParams = new Object();
		var partners_book  = $('#frm-edit-cl_partners_book_id').val();

		myParams.cl_partners_book_id = partners_book;
		//myParams.cl_partners_event_method_id = $('#' + event_method).val();
		$.getJSON(urlCommission, myParams, function (data) {
			updateSelect($('#frm-edit-cl_commission_id'), data);

		});

	}

	function updateInfoLink(data)
	{
		console.log(data);
	}

	function updateSelect(select, data)
	{
		$(select).empty();
		//console.log(data);
		for (var id in data['arrData']) {
			if (id == data['def'])
			{
				$('<option>').attr('value', id).attr('selected','selected').text(data['arrData'][id]).appendTo(select);
			}else{
				$('<option>').attr('value', id).text(data['arrData'][id]).appendTo(select);
			}
		}
	}	
	
	
	function updateCategory(cl_partners_category){
		//update partners_category
		var urlCategory  = $(cl_partners_category).data('url-category');					
		var myParams = new Object();		
		var partners_book  = $(cl_partners_category).data('partners_book_id');					
		var event_method  = $(cl_partners_category).data('event_method_id');
		
		myParams.cl_partners_book_id = $('#' + partners_book).val();
		myParams.cl_partners_event_method_id = $('#' + event_method).val();
		$.getJSON(urlCategory, myParams, function (data) {
				updateSelect(cl_partners_category, data);
				getReactTime();
			}); 				
	}
	
	function updateBranch(cl_partners_branch){
		//update partners_branch
		var urlBranch  = $(cl_partners_branch).data('url-branch');					
		var myParams = new Object();		
		var partners_book  = $(cl_partners_branch).data('partners_book_id');					
		myParams.cl_partners_book_id = $('#' + partners_book).val();
		$.getJSON(urlBranch, myParams, function (data) {
				updateSelect(cl_partners_branch, data);
				
			}); 				
	}	
	
	
	//events - update info at worker change
	$(document).on('change','#frm-edit-cl_partners_book_workers_id', function(e) {
		urlString  = $('#frm-edit-cl_partners_book_workers_id').data('url-ajax');
		data = $(this).val();

		$.ajax({
			url: urlString,
			    type: 'get',
			    context: this,
			    data: 'cl_partners_book_workers_id=' + data,
			    dataType: 'json',
			    success: function(data) {
						updateWorkerInfo(data);
					}
			    }); 	

		e.preventDefault();
		e.stopImmediatePropagation();					    
	});

	function updateWorkerInfo(data){
		if (data['worker_position'] != '')
			lcString = 'Pozice: '+data['worker_position']+'<br>';
		else
			lcString = '';

		if (data['worker_phone'] != '')
			lcString = lcString + 'Telefon: '+data['worker_phone']+'<br>';

		if (data['worker_email'] != '')
			lcString = lcString + 'Email: <a href="mailto:'+data['worker_email']+'">'+data['worker_email']+'</a><br>';						

		if (data['worker_skype'] != '')
			lcString = lcString + 'Skype: '+data['worker_skype']+'<br>';												

		if (data['worker_other'] != '')
			lcString = lcString + 'Jiný: '+data['worker_other']+'<br>';																	

		$('#worker_info').attr('data-content',lcString);
	}
	
	//events - update main events listbox
	$(document).on('change','#frm-edit-cl_partners_event_type_id', function(e) {
		urlString  = $('#frm-edit-cl_partners_event_type_id').data('url-ajax');
		data = $(this).val();
		data2 = $('#frm-edit-cl_partners_book_id').val();
		data3 = $('#frm-edit input[name=id]').val();
		$.ajax({
			url: urlString,
			    type: 'get',
			    context: this,
			    data: 'cl_partners_event_type_id=' + data + '&cl_partners_book_id=' + data2 + '&id2=' + data3,
			    dataType: 'json',
			    success: function(data) {

				}
			    }); 	

		e.preventDefault();
		e.stopImmediatePropagation();					    
	});	
	
	
	function initEventTimes(){
		//02.03.2016 - nastaveni reakcni doby pri zmene datum prijeti
		$('#frm-edit-date_rcv:not([readonly])').datetimepicker({
			formatTime:'H:i',
			format:'d.m.Y H:i',
			formatDate:'Y.m.d',
			dayOfWeekStart : 1,
			lang:'cs',
			scrollMonth : false,
			scrollInput : false,
		  onClose:function(dp,$input){
			getReactTime();
		  }			  
		});		    	

		$('#frm-edit-date_to:not([readonly])').datetimepicker({
			formatTime:'H:i',
			format:'d.m.Y H:i',
			formatDate:'Y.m.d',
			dayOfWeekStart : 1,
			scrollMonth : false,
			scrollInput : false,
			lang:'cs'});

		$('#frm-edit-date:not([readonly])').datetimepicker({
			formatTime:'H:i',
			format:'d.m.Y H:i',
			formatDate:'Y.m.d',
			dayOfWeekStart : 1,
			scrollMonth : false,
			scrollInput : false,
			lang:'cs'});	    			
			
	}
	
	function getReactTime()
	{
	      //startDate=$('#frm-eventForm-date').val();
		urlString  = $('#frm-edit-date_rcv').data('url-ajax');
		//data = $('#frm-eventForm-cl_partners_book_id').val();
		data = $('#frm-edit-cl_partners_category_id').val();
		data2 = $('#frm-edit-cl_partners_book_id').val();

		$.ajax({
			url: urlString,
			    type: 'get',
			    context: this,
			    data: 'cl_partners_category_id=' + data + '&cl_partners_book_id=' + data2,
			    dataType: 'json',
			    off: ['unique'],
			    success: function(data) {
				    //alert(data['react_time']);
				    numDuration = data['react_time'];

					startDate=$('#frm-edit-date_rcv').val();
					//alert(startDate);
					partsOne = startDate.split(' ');
					partsDate = partsOne[0].split('.');
					partsTime = partsOne[1].split(':');
					newDate = new Date(partsDate[2], partsDate[1]-1, partsDate[0], partsTime[0], partsTime[1]);						    
					//alert(newDate);
				    if (parseInt(numDuration))
					{								
						newDate = new Date(newDate.setTime(newDate.getTime() + numDuration*60000*60));

						newMinutes = newDate.getMinutes();
						if (newMinutes<10)
							newMinutes = '0'+newMinutes;
							newHours = newDate.getHours();
						if (newHours<10)
							newHours = '0'+newHours;
							newDay = newDate.getDate();
						if (newDay < 10)
							newDay = '0'+newDay;
							newMonth = (newDate.getMonth()+1);
						if (newMonth < 10)
							newMonth = '0'+newMonth;		
							newYear = newDate.getFullYear();

						$('#frm-edit-date_end').val(newDay+'.'+newMonth+'.'+newYear+' '+newHours+':'+newMinutes);									
					}


				    //$('#partner_info').popover('destroy');
				    if (data['category_name'] != '')
						lcString = 'Kategorie: '+data['category_name']+'<br>';
				    else
						lcString = '';

				    if (data['person'] != '')
						lcString = lcString + 'Kontakt: '+data['person']+'<br>';

				    if (data['email'] != '')
						lcString = lcString + 'Email: '+data['email']+'<br>';						

				    if (data['phone'] != '')
						lcString = lcString + 'Tel.: '+data['phone']+'<br>';												

				    $('#partner_info').attr('data-content',lcString);

					$('#partner_card').removeClass('hidden');
					$('#partner_card').attr('data-href', data['url-data']);
					//alert(objConfig.baseUrl+data['url']);
					$('#partner_card').attr('href', data['url']);

				}
			    }); 	

	}	
	
//$(document).on('keydown', "#frm-helpdeskEventsgrid-editLine-work_time_hours, #frm-helpdeskEventsgrid-editLine-work_time_minutes", function (e) {	    
//    var charCode = e.charCode || e.keyCode;
//    if (charCode  == 13 || charCode  == 9 ) { //Enter, tab key's keycode		
	//lHelpdeskCalc();
//    }
//});
//vypocet celkoveho casu
//vola se po zmene hodin nebo minut
//function lHelpdeskCalc(){
//	var hours = $('#frm-helpdeskEventsgrid-editLine-work_time_hours').val().split(' ').join('').replace(',','.');
//	var minutes = $('#frm-helpdeskEventsgrid-editLine-work_time_minutes').val().split(' ').join('').replace(',','.');
//	totalTime = hours + (minutes/60);
//	$('#frm-helpdeskEventsgrid-editLine-work_time').val(parseFloat(totalTime).toFixed(2));
//}	
 