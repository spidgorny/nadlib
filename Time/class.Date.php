<?php

class Date extends Time {
	const HUMAN = 'd.m.Y';
	const SYSTEM = 'Y-m-d';

	var $format = 'd.m.Y';

	function __construct($input = NULL, $relativeTo = NULL) {
		parent::__construct($input, $relativeTo);
		//$this->modify('Y-m-d \G\M\T'); // very slow!
		$this->time = mktime(0, 0, 0, date('m', $this->time), date('d', $this->time), date('Y', $this->time));
		$this->updateDebug();
		if (is_null($relativeTo)) {
			//debug_pre_print_backtrace();
			//assert($this->time >= 0);
		}
	}

	/**
	 * Copy/paste because to 'static'
	 * @param int $input
	 * @param int $relativeTo
	 * @return static
	 */
	static function make($input = NULL, $relativeTo = NULL) {
		return new self($input, $relativeTo);
	}

	function getMySQL() {
		return gmdate('Y-m-d', $this->time);
	}

	function getISO() {
		return date('Y-m-d', $this->time);
	}

	function getGMT() {
		return gmdate('Y-m-d', $this->time);
	}

	function updateDebug() {
		$this->debug = gmdate('Y-m-d H:i \G\M\T', $this->time);
		$this->human = $this->getHumanDateTime();
	}

	static function fromEurope($format) {
		$parts = explode('.', $format);
		$parts = array_reverse($parts);
		$parts = implode('-', $parts);
		return new self($parts);
	}

	/**
	 * Doesn't modify self
	 *
	 * @param string $formula
	 * @return Time
	 */
	function math($formula) {
		return new self(strtotime($formula, $this->time));
	}

	/**
	 * @param string $format d.m.Y
	 * @return htmlString
	 */
	function html($format = 'd.m.Y') {
		return new htmlString('<time datetime="'.$this->getISO().'">'.$this->format($format).'</time>');
	}

	function days() {
		return $this->getTimestamp() / 60 / 60 / 24;
	}

	function getSystem() {
		return $this->format('Y-m-d');
	}

	function plusDur(Duration $plus) {
		return new self($this->time + $plus->getTimestamp());
	}

	public function minusDur($day1) {
		return new self($this->time - $day1->getTimestamp());
	}

	static public function fromHuman($str) {
		return new Date(strtotime($str));
	}

	function getHumanMerged() {
		return date('Ymd', $this->time);
	}

	public function fromMerged($date) {
		$y = substr($date, 0, 4);
		$m = substr($date, 4, 2);
		$d = substr($date, 6, 2);
		$this->time = strtotime($y.'-'.$m.'-'.$d);
	}

	/**
	 * Mon, Tue, Wed, Thu, Fri, Sat, Sun
	 * @return string
	 */
	public function getDOW() {
		return date('D', $this->time);
	}

	function getApproximate() {
		return $this->getHTMLDate();
	}

}
