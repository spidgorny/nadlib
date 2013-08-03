$(document).ready(function () {
	var supportsDate = false;
	if (window.Modernizr) {
		supportsDate = Modernizr.inputtypes.date;
	} else {
		// http://diveintohtml5.info/detect.html#input-types
		var i = document.createElement("input");
		i.setAttribute("type", "date");
		supportsDate = i.type !== "text";
	}
	if (!supportsDate) {
		var self = $('input.datepicker');
		self.datepicker({
			dateFormat: self.attr('format'),
			minDate: self.attr('minDate'),
			maxDate: self.attr('maxDate'),
			firstDay: self.attr('firstDay'),
			changeMonth: true,
			changeYear: true
		});
	}
});
