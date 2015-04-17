$(function () {
    $.nette.init();
});

$("#frm-seasonForm-season").on('change',function(event) {			
	$.cookie('season', $(this).val(), { expires: 90 });				
});