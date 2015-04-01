<?php

class HTMLFormMonthYear extends HTMLFormType {

	var $months = array('месяц', 1 =>
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
		$fieldString = $this->form->getName($this->field, '', true);
		$content[] = '<table><tr><td>';
		$content[] = '<select name="'.$fieldString.'_mon">';
		foreach ($this->months as $m => $mon) {
			$content[] = '<option '.($this->selMonth == $m ? 'selected' : '').'>'.$mon.'</option>';
		}
		$content[] = '</select>';
		$content[] = '</td><td style="padding-left: 1em;">';
		$content[] = '<input name="'.$fieldString.'_year" value="'.$this->year.'" size="5" placeholder="год"/>';
		$content[] = '</td></tr></table>';
		return $content;
	}

}
