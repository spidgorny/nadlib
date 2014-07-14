<?php

class TimeChart {

	/**
	 * @var DBInterface
	 */
	var $db;

	var $table;

	/**
	 * @var array
	 */
	var $where;

	/**
	 * @var
	 */
	var $groupBy;

	var $options = array(
		'year-month' => '%Y-%m',
		'year' => '%Y',
		'year-month-day' => '%Y-%m-%d',
		'year-week' => '%Y-W%W',
		'dow' => '%w',
	);

	var $barWidths = array(
		'year-month' => 24,
		'year' => 360,
		'year-month-day' => 0.9,
		'year-week' => 6,
		'dow' => 0.9,
	);

	/**
	 * @var array[array]
	 */
	var $data;

	var $title = 'Appointments';

	var $query;

	var $dow = array(
		'1' => 'Monday',
		'2' => 'Tuesday',
		'3' => 'Wednesday',
		'4' => 'Thursday',
		'5' => 'Friday',
		'6' => 'Saturday',
		'0' => 'Sunday',
	);

	function __construct($table, array $where, $timeField, $groupBy = 'year-month') {
		$this->db = Config::getInstance()->db;
		$this->table = $table;
		$this->where = $where;
		$this->timeField = $timeField;
		$this->groupBy = $groupBy;
	}

	function fetch() {
		if (!$this->data) {
			$sqlDate = $this->getSQLForTime();
			$this->query = $this->db->getSelectQuery($this->table, $this->where,
				'GROUP BY '.$sqlDate.'
				 ORDER BY '.$sqlDate,
				'"'.$this->title.'" as line,
				'.$sqlDate.' as "'.$this->groupBy.'", count(*) AS amount');
			$this->data = $this->db->fetchAll($this->query);
		}
	}

	function getSQLForTime() {
		$dateFormat = $this->options[$this->groupBy];
		if (!$dateFormat) {
			throw new Exception(__METHOD__);
		}
		$timeField = $this->db->quoteKey($this->timeField);
		if ($this->db->getSchema() == 'mysql') {
			$content = 'strftime(' . $timeField . ', "' . $dateFormat . '")';
		} elseif ($this->db->getSchema() == 'sqlite') {
			$content = 'strftime("' . $dateFormat . '", ' . $timeField . ')';
		}
		return $content;
	}

	function render() {
		$this->fetch();
		if ($this->data) {
			$f = $this->getFlot();
			$content = $f->render($this->groupBy);
			//$s = new slTable($data);
			//$content .= $s;
		} else {
			debug($this->query);
		}
		return $content;
	}

	function getFlot() {
		$this->fetch();
		$f = new Flot($this->data, 'line', $this->groupBy, 'amount');
		$f->flotPath = 'vendor/flot/flot/';
		$f->width = '100%';
		$f->height = '100px';
		$f->barWidth = 1000 * 60 * 60 * 24 * $this->barWidths[$this->groupBy];
		$f->min *= 1.1;
		$f->max *= 1.1;
		$f->setMinMax();
		return $f;
	}

	function __toString() {
		return $this->render().'';
	}

}
