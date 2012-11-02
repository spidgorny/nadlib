$(document).ready(function () {
	var self = $('input.datepicker');
	self.datepicker({
		dateFormat: self.attr('format'),
		changeMonth: true,
		changeYear: true
	});
});
