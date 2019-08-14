<?php

class HTMLFormTimeRange extends HTMLFormType
{
	public $div = '1';
	public $min = 0;
	public $max = 1440;        // 24*60
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
	 * @param string $field
	 * @param array $value - array of minutes
	 */
	function __construct($field, array $value)
	{
		$this->field = $field;
		list($this->start, $this->end) = $value;
		$this->div = uniqid();
	}

	/**
	 * It's a string value which needs to be parsed into the minutes!!!
	 *
	 * @param string $value - 10:00-13:30
	 */
	function setValue($value)
	{
		if ($value) {
			list($this->start, $this->end) = $this->parseRange($value);
		}
	}

	static function parseRange($value)
	{
		if (strlen($value) == 11) {
			$parts = explode('-', $value);
			if (sizeof($parts) == 2) {
				$s = new IndTime($parts[0]);
				$e = new IndTime($parts[1]);
			} else {
				throw new Exception('Unable to parse time range: ' . $value);
			}
		} else {
			throw new Exception('Unable to parse time range: ' . $value);
		}
		return array($s, $e);
	}

	function render()
	{
		assert($this->step);
		$content = new View('nadlib/HTMLForm/HTMLFormTimeRange.phtml', $this);
		Index::getInstance()->addJS('nadlib/HTMLForm/HTMLFormTimeRange.js');
		return $content;
	}

}
