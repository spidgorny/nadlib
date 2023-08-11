<?php

class HTMLFormDatePopup2
{
	/**
	 * @var $form HTMLForm
	 */
	protected $form;

	protected $name;
	protected $value;
	protected $desc;
	public $id;

	function __construct(HTMLForm $form, $name, $value, array $desc = [
		'phpFormat' => 'Y-m-d',
	])
	{
		$this->form = $form;
		$this->name = $name;
		if ($value instanceof Time) {
			$value = $value->getTimestamp();
		} else if (-1 != ($parse = strtotime($value))) {
			$value = $parse;
		}
		$this->value = $value;
		$this->desc = $desc;
		$this->id = uniqid();
	}

	function render()
	{
		$fullname = $this->form->getName($this->name, '', TRUE);
		$printValue = $this->value
			? date($this->desc['phpFormat'], $this->value)
			: '';
		$GLOBALS['HTMLHEADER']['datepopup'] = '
			<link rel="stylesheet" type="text/css" href="lib/JSCal2-1.7/src/css/jscal2.css" />
			<link rel="stylesheet" type="text/css" href="lib/JSCal2-1.7/src/css/border-radius.css" />
			<script type="text/javascript" src="lib/JSCal2-1.7/src/js/jscal2.js"></script>
			<script type="text/javascript" src="lib/JSCal2-1.7/src/js/lang/en.js"></script>';
		$content = '
			<input type="text" name="' . $fullname . '" id="id_field_' . $this->id . '" value="' . $printValue . '" class="datepopup2"/>
			<button type="button" id="id_button_' . $this->id . '">...</button>
			<script type="text/javascript">
			    Calendar.setup({
			        inputField:		"id_field_' . $this->id . '",		// id of the input field
			        trigger:		"id_button_' . $this->id . '",   	// trigger for the calendar (button ID)
					weekNumbers:	true,
					onSelect:		function() { this.hide() },
					//min: 			Calendar.dateToInt(new Date),
					//date:			Calendar.dateToInt(new Date(' . $this->value . '*1000)),
					date:			' . ($this->value ? date('Ymd', $this->value) : date('Ymd')) . ',
					selection:		Calendar.dateToInt(new Date(' . intval($this->value) . '*1000)),
					"Bc/Bc/Bc/Bc/Bc"' .
			$this->desc['plusConfig'] . '
			    })
			</script>';
		return $content;
	}

	function __toString()
	{
		return $this->render() . '';
	}

}
