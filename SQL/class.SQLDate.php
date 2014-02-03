<?php

class SQLDate {
	/**
	 *
	 * @var Date
	 */
	protected $date;

	function __construct(Date $d) {
		$this->date = $d;
	}

	function __toString() {
		return $this->date->format('Y-m-d');
	}

}
