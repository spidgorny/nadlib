$(document).ready(function () {
	if (!Modernizr.inputtypes.date) {
		var self = $('input.datepicker');
		self.datepicker({
			dateFormat: self.attr('format'),
			changeMonth: true,
			changeYear: true
		});
	}
});
