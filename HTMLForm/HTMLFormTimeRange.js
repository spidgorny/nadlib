$(document).ready(function () {
	var $slider = $(".time-scale");
	$slider.slider({
		range: true,
		min: $slider.attr('min'),
		max: $slider.attr('max'),
		values: [$slider.attr('start'), $slider.attr('end')],
		step: $slider.attr('step'),
		slide: function (event, ui) {
			var start = ui.values[0];
			start = new Date(start * 60 * 1000);
			start = new String(start.getUTCHours()).zf() + ':' + new String(start.getUTCMinutes()).zf();
			var end = ui.values[1];
			end = new Date(end * 60 * 1000);
			end = new String(end.getUTCHours()).zf() + ':' + new String(end.getUTCMinutes()).zf();
			$("#" + $slider.attr('field')).val(start + '-' + end);
		}
	});
	//$("#<?= $this->field ?>").val($("#slider-range-<?= $this->div ?>").slider("values", 0) + '-' + $("#slider-range-<?= $this->div ?>").slider("values", 1));
});
