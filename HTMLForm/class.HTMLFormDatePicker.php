<?php

class HTMLFormDatePicker extends HTMLFormType {
	public $format = 'Y-m-d';
	public $jsFormat = 'yy-mm-dd';

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $name
	 * @param array $value				- array of minutes
	 */
	function __construct() {
		Index::getInstance()->addJQueryUI();
	}

	function render() {
		Index::getInstance()->addJS('nadlib/js/HTMLFormDatePicker.js');
		$val = strtotime($this->value);
		$val = date($this->format, $val);
		$content = $this->form->input($this->field, $val, 'class="datepicker" format="'.$this->jsFormat.'"');
		return $content;
	}

}
