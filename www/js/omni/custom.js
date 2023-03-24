/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 16.8.2016 - 7:24:35
 * 
 */


$(document).ajaxStart(function(e) {
     $("#loading").show();
});

$(document).ajaxComplete(function() {
    $("#loading").hide();
});
