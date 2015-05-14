$(function () {
    $.nette.init();
});

$("#frm-seasonForm-season").on('change',function(event) {			
	$.cookie('season', $(this).val(), { expires: 90, path: '/' });
	this.form.submit();
});

$( document ).ajaxError(function( event, jqxhr, settings, thrownError ) {
	alert(jQuery.parseJSON(jqxhr.responseText).message);
	location.reload();
});

$(document).ajaxStart(function(){
	// zobrazení spinneru a nastavení jeho pozice
    $("#ajax-spinner").show()
		.css({
			position: "absolute",
			left: 0,
			top: 0
		})
		.height($(document).height());
});

$(document).ajaxStop(function() {
	$("#ajax-spinner").hide();
});
$(function () {
    // vhodně nastylovaný div vložím po načtení stránky
    $('<div id="ajax-spinner"></div>').appendTo("body").hide();   
});