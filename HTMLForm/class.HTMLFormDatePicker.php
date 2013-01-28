<?php

class HTMLFormDatePicker extends HTMLFormType {
	public $format = 'Y-m-d';
	public $jsFormat = 'yy-mm-dd';

	/**
	 * Enter description here...
	 *
	 * @internal param string $name
	 * @internal param array $value - array of minutes
	 */
	function __construct() {
		Index::getInstance()->addJQueryUI();
	}

	function render() {
		Index::getInstance()->addJS('nadlib/js/HTMLFormDatePicker.js');
		if ($this->value) {
			$val = strtotime($this->value);
			$val = date($this->format, $val);
		} else {
			$val = '';
		}
		$this->form->input($this->field, $val, 'class="datepicker"
			format="'.$this->jsFormat.'"');
	}

}
