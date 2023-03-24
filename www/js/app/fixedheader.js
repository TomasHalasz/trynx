/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 16.6.2016 - 9:02:08
 * 
 */



	//fixed header for tables
	//05.12.2015
	if ($(window).width() < 1100)
	{
	    offset = $(window).scrollTop() - 65;
	}else{
	    offset = $(window).scrollTop();
	}

	if (offset >=70){
	    $( ".baselistHead.hmodal1:not(.actionHeader)" ).animate( {top: offset-75}, function() {
		      // Animation complete.
		      setActionHeaderColumn();
			});			
	    }else{
		setActionHeaderColumn();
	    }
	setModal1();	    	
	
	if (offset >=136){
	    $( ".baselistHead.hmodal:not(.actionHeader)" ).animate( {top: offset-138}, function() {
		      // Animation complete.
		      setActionHeaderColumn();
			});			
	    }
	setModal();	
	//setActionHeaderColumn();
	
	$( document ).on("scroll",function() {
	    if ($(window).width() < 1100)
	    {
		offset = $(window).scrollTop() - 65;
	    }else{
		offset = $(window).scrollTop();
	    }
	    if (offset >=70){
		setModal1();		
		$( ".baselistHead.hmodal1:not(.actionHeader)" ).css( "top", (offset-75)+"px" );			    				
	    }else{
		$( ".baselistHead.hmodal1:not(.actionHeader)" ).css( "position", "initial" );		
		$( ".baselistHead.hmodal1:not(.actionHeader)" ).css( "outline", "none");					    						
	    }
	    if (offset >=136){	    
		$( ".baselistHead.hmodal:not(.actionHeader)" ).css( "top", (offset-138)+"px" );			    				
		setModal();
	    }else{
		$( ".baselistHead.hmodal:not(.actionHeader)" ).css( "position", "initial" );						
		$( ".baselistHead.hmodal:not(.actionHeader)" ).css( "outline", "none");					    				
	    }

	    setActionHeaderColumn();
	}); 
	

	function setModal1()
	{
	    varColor = $('.table-bordered > tbody > tr > td').css('border-top-color');	    	    
	    $( ".baselistHead.hmodal1:not(.actionHeader)" ).css( "position", "relative" );				
	    $( ".baselistHead.hmodal1:not(.actionHeader)" ).css( "outline", "thin solid "+varColor );	
	}
	
	function setModal()
	{
	    varColor = $('.table-bordered > tbody > tr > td').css('border-top-color');	    
	    $( ".baselistHead.hmodal:not(.actionHeader)" ).css( "position", "relative" );					    
	    $( ".baselistHead.hmodal:not(.actionHeader)" ).css( "outline", "thin solid "+varColor );					    			    
	}


	function setActionHeaderColumn()
	{
	    if ($('.baselistHead').length > 0)
	    {
		//alert('tedf');
		    headHeight   = parseInt($('.baselistHead').css('height')) + 1;
		    if ($('.baselistHead.hmodal').length > 0)
		    {
				$(".baselistHead.hmodal.actionColumnReplace").css('position','absolute');
				$(".baselistHead.hmodal.actionColumnReplace").css('right','15px');		
				$(".baselistHead.hmodal.actionColumnReplace").css('height',headHeight+'px');	    			    
		    }
		    if ($('.baselistHead.hmodal1').length > 0)
		    {
				$(".baselistHead.hmodal1.actionColumnReplace").css('position','absolute');
				$(".baselistHead.hmodal1.actionColumnReplace").css('right','15px');		
				$(".baselistHead.hmodal1.actionColumnReplace").css('height',headHeight+'px');	    			    
		    }		    
		    if ($(window).width() < 1100)
		    {
				offset = $(window).scrollTop() - 65;
		    }else{
				offset = $(window).scrollTop();
		    }
		    //offset = offset + 1;
		    //javascript: console.log(offset);		    

			//headPosition = $('.baselistHead').position();
			//alert(headPosition);
			if ($('.baselistHead.hmodal').length > 0 &&  offset >=136)
			    $(".baselistHead.hmodal.actionColumnReplace").css('top',offset+'px');	    			    
			else
			    $(".baselistHead.hmodal.actionColumnReplace").css('top','');
			
			if ($('.baselistHead.hmodal1').length > 0 &&  offset >=60)
			    $(".baselistHead.hmodal1.actionColumnReplace").css('top',offset+'px');	    			    			
			else
			    $(".baselistHead.hmodal1.actionColumnReplace").css('top','');
			

			//$(".baselistHead.hmodal.actionColumnReplace").css('top','');

	    }   
		
	    //fixed width for action column
	    width=$( ".actionColumn" ).css( "width");
		//alert(width);
	    $( ".actionColumnReplace" ).css( "min-width",width);
		//color = $(".actionColumn").css("background-color","red");
		
		$('.actionColumn').each(function() {
			color = $(this).parent().find('td').css("background-color");
			if (color == "transparent" || color == "rgba(0, 0, 0, 0)" )
				color = $(this).parent().css("background-color");
			if (color == "transparent" || color == "rgba(0, 0, 0, 0)" )
				color = "white";
			//alert(color);
			$(this).css("background-color",color);
			height = $(this).parent().css("height");
			$(this).css("height",height);
			width = parseInt($('.actionHeader').css('width'));
			//alert($('.baselistHead').css('width'));
			$(this).css("width",width+'px');			
		});
		
		$('.actionColumnWrap').each(function() {
			height = parseInt($(this).parent().css("height"))/2;
			btnHeight = parseInt($(this).find('.btn').css("height"))/2;
			//alert(btnHeight);
			$(this).css("padding-top",(height-btnHeight-2)+'px');			
		});		
	    
	}
	

	//end of fixed header