$(document).ready(function () {
	updateHere = function () {
		var uh = $('.updateHere');
		if (uh.length) {
			setTimeout(function () {
				uh.load(uh.attr('src'));
			}, 1000);
		}
	}

	updateHere();
});

