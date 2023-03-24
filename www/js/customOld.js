/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


		var mySlider = $(".time_slider").each(function(i,val){
				    $(this).slider({
					    value: parseInt($(this).val()),
					    formatter:function(value){ 
					    return value + ' minut';
				    }
				});
			    });
		var mySlider = $(".time_slider_disabled").each(function(i,val){
				    $(this).slider('disable');
			    });				
			    $(document).on('slideStop','.time_slider', function(e)
				    {
					numDuration = e.value;
					//alert(numDuration);
					startDate=$('#frm-eventForm-date').val();
					partsOne = startDate.split(' ');
					partsDate = partsOne[0].split('.');
					partsTime = partsOne[1].split(':');
					newDate = new Date(partsDate[2], partsDate[1]-1, partsDate[0], partsTime[0], partsTime[1]);
					newDate = new Date(newDate.setTime(newDate.getTime() + numDuration*60000));					
					
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
					//if (newYear < 10)
					  //  newYear = '0'+newYear;

					$(this).parent().parent().parent().find('#frm-eventForm-date_to').val(newDay+'.'+newMonth+'.'+newYear+' '+newHours+':'+newMinutes);
					//alert(newDate);
				    }
			    );
		    
			
			//16.02.2016 - nove reseni datumu a casu pro udalost
			$('#frm-eventForm-date_to:not([readonly])').datetimepicker({
				formatTime:'H:i',
				format:'d.m.Y H:i',
				formatDate:'Y.m.d',
				dayOfWeekStart : 1,
				lang:'cs',	    
			  onClose:function(dp,$input){
				  //onChangeDateTime
			      //startDate=$('#frm-eventForm-date').val();
			      startDate=$(this).parent().parent().parent().find('#frm-eventForm-date').val();
				partsOne = startDate.split(' ');
				partsDate = partsOne[0].split('.');
				partsTime = partsOne[1].split(':');
				newStartDate = new Date(partsDate[2], partsDate[1]-1, partsDate[0], partsTime[0], partsTime[1]);			      
			      //endDate=$('#frm-eventForm-date_to').val();
			      endDate=$(this).parent().parent().parent().find('#frm-eventForm-date_to').val();
				partsOne = endDate.split(' ');
				partsDate = partsOne[0].split('.');
				partsTime = partsOne[1].split(':');
				newEndDate = new Date(partsDate[2], partsDate[1]-1, partsDate[0], partsTime[0], partsTime[1]);			      			      
				workTime = (newEndDate - newStartDate) / 60000;
				//objSlider = $('#frm-eventForm-date_to').parent().parent().parent().find('.time_slider');
				objSlider = $(this).parent().parent().parent().find('.time_slider');
				maxWorkTime = objSlider.slider('getAttribute')['max'];
				objSlider.slider('destroy');
				if (parseInt(workTime) > maxWorkTime)
				{
				    maxWorkTime = workTime;
				}
				else
				{
				    maxWorkTime = maxWorkTime;
				}
				
				objSlider.slider({
					    value: parseInt(workTime),
					    max: maxWorkTime,
					    ticks: [0,15, 30, 45, 60, 90, maxWorkTime],
					    ticks_labels: ["0","15", "30", "45", "60", "90", maxWorkTime],
					    formatter:function(value){ 
					    return value + ' minut';
				    }
				});		
		
			  }
			});		   			
			
			
			//16.02.2016 - nove reseni datumu a casu pro udalost
			$('#frm-eventForm-date:not([readonly])').datetimepicker({
				formatTime:'H:i',
				format:'d.m.Y H:i',
				formatDate:'Y.m.d',
				dayOfWeekStart : 1,
				lang:'cs',	    
			  onClose:function(dp,$input){
				  //ChangeDateTime
				curWorkTime = $(this).parent().parent().parent().find('.time_slider').val();
				//numDuration = $('.time').val();
				//alert(curWorkTime);
				startDate=$('#frm-eventForm-date').val();
				partsOne = startDate.split(' ');
				partsDate = partsOne[0].split('.');
				partsTime = partsOne[1].split(':');
				newDate = new Date(partsDate[2], partsDate[1]-1, partsDate[0], partsTime[0], partsTime[1]);
				newDate = new Date(newDate.setTime(newDate.getTime() + curWorkTime*60000));					

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
				//if (newYear < 10)
				  //  newYear = '0'+newYear;

				$(this).parent().parent().parent().find('#frm-eventForm-date_to').val(newDay+'.'+newMonth+'.'+newYear+' '+newHours+':'+newMinutes);
				
				//alert(workTime);
			    //dp.preventDefault();
			    //dp.stopImmediatePropagation();	
			  }
			});		    			    
	    			