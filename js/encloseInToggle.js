$(document).ready(function () {
	var showHide = $('.show_hide');
	if (showHide.length) {
		showHide.showHide({
			showText: '&#x25BA;',
			hideText: '&#x25BC;',
			changeText: true
		});
	}
});
