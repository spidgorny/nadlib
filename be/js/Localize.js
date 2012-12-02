$(document).ready(function () {
	$('.inlineEdit').editable('?c=Localize', {
		indicator : 'Saving...',
		tooltip   : 'Click to edit...',
		cancel    : 'Cancel',
		submit    : 'OK'
	});
});
