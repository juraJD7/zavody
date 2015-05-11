$(function () {
    $.nette.init();
});

$("#frm-seasonForm-season").on('change',function(event) {			
	$.cookie('season', $(this).val(), { expires: 90, path: '/' });
	location.reload();
});

$( document ).ajaxError(function( event, jqxhr, settings, thrownError ) {
	alert(jQuery.parseJSON(jqxhr.responseText).message);
	location.reload();
});