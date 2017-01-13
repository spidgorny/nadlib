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
		$content[] = '<table class="'.__CLASS__.'"><tr><td>';
		$content[] = '<select name="'.$fieldStringM.'" class="form-control">';
		$content[] = $this->showMonthOptions();
		$content[] = '</select>';
		$content[] = '</td><td style="padding-left: 1em;">';
		$content[] = '<input type="number"
			name="'.$fieldStringY.'"
			value="'.$this->year.'"
			size="5"
			placeholder="год"
			class="form-control"
			min="1900" max="2100"/>';
		$content[] = '</td></tr></table>';
		return $content;
	}

	function setValue($value) {
		$this->selMonth = $value['month'];
		$this->year = $value['year'];
	}

	function validate() {
		return $this->year ? NULL : 'Введите год';
	}

	function showMonthOptions() {
		$content = array();
		foreach ($this->months as $m => $mon) {
			$content[] = new HTMLTag('option', array(
					'value' => $m,
				) +	($this->selMonth == $m ? array('selected' => true) : array()),
				$mon);
		}
		return $content;
	}

}
