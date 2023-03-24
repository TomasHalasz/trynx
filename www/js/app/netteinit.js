/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 14.6.2016 - 17:20:30
 * 
 */


$(function () {
	//$.nette.init();
	// And you fly...
	$.nette.init({
		load: function (rh) {
			$(this.linkSelector).off('click.nette', rh).on('click.nette', rh);
			$(this.formSelector).off('submit.nette', rh).on('submit.nette', rh)
				.off('click.nette', ':image', rh).on('click.nette', ':image', rh)
				.off('click.nette', ':submit', rh).on('click.nette', ':submit:not(.noAjax)', rh);
			$(this.buttonSelector).closest('form')
				.off('click.nette', this.buttonSelector, rh).on('click.nette', this.buttonSelector, rh);
		}
	}, {
		linkSelector: 'a.ajax',
		formSelector: 'form.ajax',
		buttonSelector: 'input.ajax[type="submit"], button.ajax[type="submit"], input.ajax[type="image"]'
	});	
	

}); 	    

(function($, undefined) {
	$.nette.ext({
		before: function (xhr, settings) {
			if (!settings.nette) {
				return;
			}

			var question = settings.nette.el.data('confirm');
			if (question) {
				return confirm(question);
			}
		}
	});
})(jQuery);
    

(function ($, undefined) {
	
    $.nette.ext({
        load: function () {
            $('[data-confirm]').click(function (event) {
                var obj = this;
                event.preventDefault();
                event.stopImmediatePropagation();
                $("<div id='dConfirm' class='modal fade'></div>").appendTo('body');
                $('#dConfirm').html("<div id='dConfirmDialog' class='modal-dialog'></div>");
                $('#dConfirmDialog').html("<div id='dConfirmContent' class='modal-content'></div>");
                $('#dConfirmContent').html("<div id='dConfirmHeader' class='modal-header'></div><div id='dConfirmBody' class='modal-body'></div><div id='dConfirmFooter' class='modal-footer'></div>");
                $('#dConfirmHeader').html("<a class='close' data-dismiss='modal' aria-hidden='true'>×</a><h4 class='modal-title' id='dConfirmTitle'></h4>");
                $('#dConfirmTitle').html($(obj).data('confirm-title'));
                $('#dConfirmBody').html("<p>" + $(obj).data('confirm-text') + "</p>");
                $('#dConfirmFooter').html("<a id='dConfirmOk' class='btn " + $(obj).data('confirm-ok-class') + "' data-dismiss='modal'>Ano</a><a id='dConfirmCancel' class='btn " + $(obj).data('confirm-cancel-class') + "' data-dismiss='modal'>Ne</a>");
                if ($(obj).data('confirm-header-class')) {
                    $('#dConfirmHeader').addClass($(obj).data('confirm-header-class'));
                }
                if ($(obj).data('confirm-ok-text')) {
                    $('#dConfirmOk').html($(obj).data('confirm-ok-text'));
                }
                if ($(obj).data('confirm-cancel-text')) {
                    $('#dConfirmCancel').html($(obj).data('confirm-cancel-text'));
                }
                $('#dConfirmOk').on('click', function () {
		    if ($(obj).data('script') == 'on') {		    
			var name = $(obj).data('script-name');
			eval(name);
		    }
                    var tagName = $(obj).prop("tagName");
                    if (tagName == 'INPUT') {
                        var form = $(obj).closest('form');
                        form.submit();
                    } else {
                        if ($(obj).data('ajax') == 'on') {
                            $.nette.ajax({
                                url: obj.href
                            });
                        } else {
                            document.location = obj.href;
                        }
                    }
                });
                $('#dConfirm').on('hidden.bs.modal', function () {
                    $('#dConfirm').remove();
                });
                $('#dConfirm').modal('show');
                return false;
            });
        }
    });	

})(jQuery);