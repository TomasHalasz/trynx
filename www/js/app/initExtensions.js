/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

	//inicitalizace ruznych rozsireni

	function initExtensions()
	{
            //input mask for hours:minutes
       $(".hrsmns").inputmask("99:99");
           
	    initChznSelectMy();


        listgridCounts(); //write records counter from listgrid to joined tabpanel

        showDropdownAfterClick();


        $('.datetimepicker:not([readonly])').datetimepicker({
			formatTime:'H:i',
			format:'d.m.Y H:i',
			formatDate:'Y.m.d',
			dayOfWeekStart : 1,
			lang:'cs',
            scrollMonth : false,
            scrollInput : false
		});
	    $('.datepicker:not([readonly])').datetimepicker({
			format:'d.m.Y',
			formatDate:'Y.m.d',
			dayOfWeekStart : 1,
			lang:'cs',
			timepicker:false,
            scrollMonth : false,
            scrollInput : false
		});	   
		
	    
	    $('input.number').autoNumeric('init',{aSep: ' ', aDec: '.'});
        $('input.currency_rate').autoNumeric('init',{aSep: ' ', aDec: '.', mDec: '3', aPad: false});
        $('.listgrid input.text-number').autoNumeric('init',{aSep: '', aDec: ' ', aPad: false, vMax: '9999999', vMin: '0'});
	    //$('input.number').autoNumeric('update');

	    
	    $('.datepicker').inputmask({ mask: "99.99.9999"});
            $('.datetimepicker').inputmask({ mask: "99.99.9999 99:99"});            
            
	    
	    $('.trumbowyg-edit').trumbowyg({
	       btns: [
		   ['viewHTML'],
		    ['foreColor', 'backColor'],		   
		   ['undo', 'redo'], // Only supported in Blink browsers
		   ['formatting'],
		   ['strong', 'em', 'del'],
		   ['superscript', 'subscript'],
		   ['link'],
		   ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
		   ['unorderedList', 'orderedList'],
		   ['horizontalRule'],
		   ['removeformat'],
		   ['fullscreen']
		   ],
		lang: 'cs'
	    });
	    
	    
	  if (typeof $.SmartMenus != 'undefined')
	  {
	    $.SmartMenus.Bootstrap.init();
	  }
	    
	  // initSelect2();
	    
	    
	    bootbox.setDefaults({
	      /**
	       * @optional String
	       * @default: en
	       * which locale settings to use to translate the three
	       * standard button labels: OK, CONFIRM, CANCEL
	       */
	      locale: "cs",

	      /**
	       * @optional Boolean
	       * @default: true
	       * whether the dialog should be shown immediately
	       */
	      show: true,

	      /**
	       * @optional Boolean
	       * @default: true
	       * whether the dialog should be have a backdrop or not
	       */
	      backdrop: true,

	      /**
	       * @optional Boolean
	       * @default: true
	       * show a close button
	       */
	      closeButton: true,

	      /**
	       * @optional Boolean
	       * @default: true
	       * animate the dialog in and out (not supported in < IE 10)
	       */
	      animate: true,

	      /**
	       * @optional String
	       * @default: null
	       * an additional class to apply to the dialog wrapper
	       */
	      className: "my-modal"

	    });	    
	    
	}


// 13.02.2020 - switched off for now. It seem's to be better show dropdown menu after hover and click too.
function showDropdownAfterClick(){
 /*   $(document).on('mouseenter mouseleave', '.dropdown', function (e){
        e.preventDefault();
        e.stopImmediatePropagation();
    });*/
    $(document).on('click tap', '.dropdown', function (e){
        //$(this).toggleClass("open");
        e.stopImmediatePropagation();
    });
}
	
function initChznSelectMy()
{
	selectItems = $(".chzn-select, .chzn-selectModal");
	selectItems.select2({language: "cs",
	    allowClear: true,
	    dropdownAutoWidth : true,
	    templateResult: function (data) {    //12.08.2018 - update for indentation nested levels of selectbox for example store-review
		// We only really care if there is an element to pull classes from
		if (!data.element) {
		  return data.text;
		}

		var $element = $(data.element);

		var $wrapper = $('<span></span>');
		$wrapper.addClass($element[0].className);

		$wrapper.text(data.text);

		return $wrapper;
	      }
	});
	//for set disabled in case of readonly selectbox
	$('.chzn-select[readonly="readonly"] option:not(:selected)').attr('disabled', 'disabled');	

	selectItems = $(".chzn-select-req");
	selectItems.select2({language: "cs",
	    allowClear: false,
	    dropdownAutoWidth : true,
	    templateResult: function (data) {    //12.08.2018 - update for indentation nested levels of selectbox for example store-review
		// We only really care if there is an element to pull classes from
		if (!data.element) {
		  return data.text;
		}

		var $element = $(data.element);

		var $wrapper = $('<span></span>');
		$wrapper.addClass($element[0].className);

		$wrapper.text(data.text);

		return $wrapper;
	      }
	});
	//for set disabled in case of readonly selectbox
	$('.chzn-select2-req[readonly="readonly"] option:not(:selected)').attr('disabled', 'disabled');

    resizeChosen();
}
	

function initSelect2()
{
     //return;
        //select2 ajax definition for edit form
            var urlStrPartner  = $("#frm-edit-cl_partners_book_id:not(.noSelect2), #frm-edit2-cl_partners_book_id:not(.noSelect2)").data('urlajax');
            if (urlStrPartner == undefined)
            {
                var urlStrPartner  = $("#frm-eventForm-cl_partners_book_id:not(.noSelect2)").data('urlajax');
            }
            var selectTextWriten = "";
            //console.log('select2 init');
            selectPartners = $("#frm-edit-cl_partners_book_id:not(.noSelect2), #frm-edit2-cl_partners_book_id:not(.noSelect2), #frm-eventForm-cl_partners_book_id:not(.noSelect2)");
            selectPartners.select2({
                language: {
                    "errorLoading":function(){
                        return "Výsledky nemohly být načteny.";
                    },
                    "inputTooLong":function(t){
                        var n=t.input.length-t.maximum;
                        return n==1?"Prosím zadejte o jeden znak méně":n<=4?"Prosím zadejte o "+n+" znaky méně":"Prosím zadejte o "+n+" znaků méně";
                    },
                    "inputTooShort":function(t){
                        var n=t.minimum-t.input.length;return n==1?"Prosím zadejte ještě jeden znak":n<=4?"Prosím zadejte ještě další "+n+" znaky":"Prosím zadejte ještě dalších "+n+" znaků";
                    },
                    "loadingMore":function(){
                        return "Načítají se další výsledky…";
                    },
                    "maximumSelected":function(t){
                        var n=t.maximum;
                        return n==1?"Můžete zvolit jen jednu položku":n<=4?"Můžete zvolit maximálně "+n+" položky":"Můžete zvolit maximálně "+n+" položek";
                    },
                    "noResults": function(e){
                        var objConfig = jQuery.parseJSON(jQuery('#configMain').text());
                       // console.log($(this).text());
                        data = {company:selectTextWriten};
                        lcUrl = objConfig.newPartnerUrl + "&defData=" + JSON.stringify(data);
			//console.log(e);
                        return "Partner nebyl nalezen  <a href='"+lcUrl+"' class='modalClick newRecordOpener' data-not-check='1' data-href='"+lcUrl+"' data-title='Kniha partnerů'>Zapsat nového</a>";
                    },
                    "searching":function(){
                        return"Vyhledávání…";
                    }                                        
                },

                 escapeMarkup: function (markup) {
                     return markup;
                 },            
                ajax: {
                  url: urlStrPartner,
                  dataType: 'json',
                  delay: 250,
                  data: function (params) {

                      var objConfig = jQuery.parseJSON(jQuery('#configMain').text());
                      // console.log($(this).text());
                      selectTextWriten = params.term;
                      ///dataW = {company:selectTextWriten};
                      dataW = [];
                      dataW['data'] ={company:selectTextWriten};
                      lcUrl = objConfig.newPartnerUrl + "&" + JSON.stringify(dataW);
                      $('.newRecordOpener').prop('href', lcUrl);

                    return {
                      q: params.term, // search term
                      page: params.page
                    };
                  },
                  processResults: function (data, params) {
                    // parse the results into the format expected by Select2
                    // since we are using custom formatting functions we do not need to
                    // alter the remote JSON data, except to indicate that infinite
                    // scrolling can be used
                    params.page = params.page || 1;
                    return {
                      results: data.items,
                      pagination: {
                        more: (params.page * 30) < data.total_count
                      }
                    };
                  },
                  cache: true
                },
                minimumInputLength: 3,
                width: 'resolve'
            });


	    
	    $(document).on('click','.newRecordOpener', function(e) {		    
		//console.log('new');
	    });
        //select2 ajax definition for pricelist select
            //console.log(data);
            var cmpName = $(".select2Pricelist").data('cmpname');        
            var urlStrPartner  = $(".select2Pricelist").data('urlajax');
            $(".select2Pricelist").select2({ 
                language: {
                    "errorLoading":function(){
                        return "Výsledky nemohly být načteny.";
                    },
                    "inputTooLong":function(t){
                        var n=t.input.length-t.maximum;
                        return n==1?"Prosím zadejte o jeden znak méně":n<=4?"Prosím zadejte o "+e(n,!0)+" znaky méně":"Prosím zadejte o "+n+" znaků méně";
                    },
                    "inputTooShort":function(t){
                        var n=t.minimum-t.input.length;return n==1?"Prosím zadejte ještě jeden znak":n<=4?"Prosím zadejte ještě další "+n+" znaky":"Prosím zadejte ještě dalších "+n+" znaků";
                    },
                    "loadingMore":function(){
                        return "Načítají se další výsledky.";
                    },
                    "maximumSelected":function(t){
                        var n=t.maximum;
                        return n==1?"Můžete zvolit jen jednu položku":n<=4?"Můžete zvolit maximálně "+n+" položky":"Můžete zvolit maximálně "+n+" položek";
                    },
                    "noResults": function(e){
                        var objConfig = jQuery.parseJSON(jQuery('#configMain').text());
                        lcUrl = objConfig.newPriceListUrl;
                      if ($('#frm-salelistgrid-priceList').length > 0)
                      {
                          return "Položka nebyla nalezena";
                      }else{
                          return "Položka nebyla nalezena  <a href='"+lcUrl+"' class='modalClick' data-href='"+lcUrl+"' data-title='Ceník'>Zapsat novou položku</a>";			    
                      }

                    },
                    "searching":function(){
                        return"Vyhledávání…";
                    }                                        
                },

                 escapeMarkup: function (markup) {
                     return markup;
                 },          
                templateResult: formatRepo,
                templateSelection: formatRepoSelection,
                ajax: {
                  url: urlStrPartner,
                  dataType: 'json',
                  delay: 250,
                  data:  function (params) {
                    return {
                      q: params.term, // search term
                      page: params.page
                    };
                  },
                  processResults: function (data, params) {
                    // parse the results into the format expected by Select2
                    // since we are using custom formatting functions we do not need to
                    // alter the remote JSON data, except to indicate that infinite
                    // scrolling can be used
                    params.page = params.page || 1;


                    return {
                      results: data.items,
                      pagination: {
                        more: (params.page * 30) < data.total_count
                      }
                    };
                  },
                  cache: true
                },
                minimumInputLength: 3
              });


    function formatRepo (repo) {
      if (repo.loading) return repo.text;

      var markup = "<div class='item_box'><div class='select_left'><div class='select_code'>Kód: " + repo.identification;
      if (repo.ean_code != '')
      { 
          markup += " EAN:" + repo.ean_code;
      }
       markup += "</div>";
      markup += "<div class='select_name'>" + repo.item_label + "</div>";
      markup += "</div>";
      
      markup += "<div class='select_right'><div class='select_2'>Skladem: " + repo.quantity + " " + repo.unit + "</div>";
      markup += "<div class='select_3'>Cena: " + repo.price_vat + " " + repo.currency_name + "</div>";
      markup += "</div>";
      markup += "</div>";
      return markup;
    }

    function formatRepoSelection (repo) {
      return repo.full_name || repo.text;
    }
}
	
	