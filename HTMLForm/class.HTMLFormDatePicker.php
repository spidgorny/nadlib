<?php

class HTMLFormDatePicker extends HTMLFormType {
	public $format = 'Y-m-d';
	public $jsFormat = 'yy-mm-dd';

	/**
	 * @internal param string $name
	 * @internal param array $value - array of minutes
	 */
	function __construct() {
		Index::getInstance()->addJQueryUI();	// for the picker
		Index::getInstance()->addJS('nadlib/js/HTMLFormDatePicker.js');
	}

	function render() {
		if ($this->value) {
			$val = strtotime($this->value);
			$val = date($this->format, $val);
		} else {
			$val = '';
		}
		$this->form->input($this->field, $val, 'format="'.$this->jsFormat.'"', 'date', 'datepicker');
	}

}
