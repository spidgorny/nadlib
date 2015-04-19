<?php

class HTMLFormMonthYear extends HTMLFormType {

	var $months = array(
		NULL => 'месяц', 1 =>
		'Jan', 'Feb', 'Mar',
		'Apr', 'May', 'Jun',
		'Jul', 'Aug', 'Sep',
		'Oct', 'Nov', 'Dec');

	var $selMonth;

	var $year;

	/**
	 * @param string $field
	 */
	function __construct($field) {
		parent::__construct();
		$this->field = $field;
	}

	function render() {
		if (!$this->form) {
			debug_pre_print_backtrace();
		}
		$fieldStringM = $this->form->getNameField(array_merge($this->field, array('month')), '', true);
		$fieldStringY = $this->form->getNameField(array_merge($this->field, array('year')), '', true);
		$content[] = '<table><tr><td>';
		$content[] = '<select name="'.$fieldStringM.'">';
		foreach ($this->months as $m => $mon) {
			$content[] = new HTMLTag('option', array(
				'value' => $m,
			) +	($this->selMonth == $m ? array('selected' => true) : array()),
			$mon);
		}
		$content[] = '</select>';
		$content[] = '</td><td style="padding-left: 1em;">';
		$content[] = '<input name="'.$fieldStringY.'" value="'.$this->year.'" size="5" placeholder="год"/>';
		$content[] = '</td></tr></table>';
		return $content;
	}

	function setValue($value) {
		$this->selMonth = $value['month'];
		$this->year = $value['year'];
	}
}
