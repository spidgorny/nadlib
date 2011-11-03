<?php

class HTMLFormTimeRange extends HTMLFormType {
	protected $form;
	public $name;
	public $fullname;
	public $value;
	public $div = '1';
	public $min = 0;
	public $max = 1440;		// 24*60
	/**
	 * Enter description here...
	 *
	 * @var Time
	 */
	public $start; // = 1000;
	/**
	 * Enter description here...
	 *
	 * @var Time
	 */
	public $end; // = 1730;
	public $step = 30;

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $name
	 * @param array $value				- array of minutes
	 */
	function __construct($name, array $value) {
		$this->name = $name;
		list($this->start, $this->end) = $value;
		$this->div = uniqid();
	}

	function setForm(HTMLFormTable $f) {
		$this->form = $f;
		$this->fullname = $this->form->getName($this->name, '', TRUE);
	}

	/**
	 * It's a string value which needs to be parsed into the minutes!!!
	 *
	 * @param unknown_type $value - 10:00-13:30
	 */
	function setValue($value) {
		if ($value) {
			list($this->start, $this->end) = $this->parseRange($value);
		}
	}

	function parseRange($value) {
		if (strlen($value) == 11) {
			$parts = explode('-', $value);
			if (sizeof($parts) == 2) {
				$s = new IndTime($parts[0]);
				$e = new IndTime($parts[1]);
			} else {
				throw new Exception('Unable to parse time range: '.$value);
			}
		} else {
			throw new Exception('Unable to parse time range: '.$value);
		}
		return array($s, $e);
	}

	function render() {
		$content .= new View('HTMLFormTimeRange.phtml', $this);
		return $content;
	}

}