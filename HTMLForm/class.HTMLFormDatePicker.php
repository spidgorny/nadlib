<?php

class HTMLFormDatePicker extends HTMLFormType
{
	/**
	 * PHP Format
	 * @var string
	 */
	public $format = 'Y-m-d';

	/**
	 * JS format
	 * @var string
	 */
	public $jsFormat = 'yy-mm-dd';

	public $jsParams = array();

	public $inputType = 'date';

	/**
	 * @internal param string $name
	 * @internal param array $value - array of minutes
	 */
	function __construct()
	{
		Index::getInstance()->addJQueryUI();    // for the picker
		Index::getInstance()->addJS('vendor/spidgorny/nadlib/js/HTMLFormDatePicker.js');
	}

	function render()
	{
		if ($this->value && $this->value != '0000-00-00') {
			if (is_integer($this->value) || is_numeric($this->value)) {
				$val = date($this->format, $this->value);
			} else {
				$val = strtotime($this->value);    // hope for Y-m-d
				$val = date($this->format, $val);
			}
		} else {
			$val = '';
		}
		$this->form->input($this->field, $val, array(
				'format' => $this->jsFormat
			) + $this->jsParams,
			$this->inputType, 'datepicker');
	}

	/**
	 * Convert to timestamp using the supplied format
	 * @param $value
	 * @return int
	 */
	function getISODate($value)
	{
		//debug($value, is_integer($value), is_numeric($value), strtotime($value));
		if ($value && (is_integer($value) || is_numeric($value))) {
			$val = intval($value);
		} else if ($value && is_string($value) && $this->jsFormat == 'dd.mm.yy') {
			$val = explode('.', $value);
			$val = array_reverse($val);
			$val = implode('-', $val);
			$val = strtotime($val);
		} else if ($value) {
			$val = $value;
			$val = strtotime($val);
		} else {
			$val = NULL;    // time();
		}
		//debug($this->jsFormat, $value, $val);
		return $val;
	}

}
