/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 14.6.2016 - 21:40:02
 * 
 */


$('#colorselector').colorselector({
	  callback: function (value, color, title) {
	$("input[name=color_hex]").val(color);
	  }
});

color = $("input[name=color_hex]").val();
if (color.length <= 0)
	color = "#FFFFFF";

$("#colorselector").colorselector("setColor", color);

