<?php

class HTMLFormDatePicker extends HTMLFormType {
	public $format = 'Y-m-d';
	public $jsFormat = 'yy-mm-dd';

	public $jsParams = array();

	/**
	 * @internal param string $name
	 * @internal param array $value - array of minutes
	 */
	function __construct() {
		Index::getInstance()->addJQueryUI();	// for the picker
		Index::getInstance()->addJS('vendor/spidgorny/nadlib/js/HTMLFormDatePicker.js');
	}

	function render() {
		if ($this->value) {
			$val = strtotime($this->value);
			$val = date($this->format, $val);
		} else {
			$val = '';
		}
		$this->form->input($this->field, $val, array(
			'format' => $this->jsFormat
		) + $this->jsParams,
		'date', 'datepicker');
	}

}
