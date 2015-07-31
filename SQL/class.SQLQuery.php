<?php

class SQLQuery extends \PHPSQL\Parser {

	public $parsed;

	public function __construct($sql = false, $calcPositions = false) {
		parent::__construct($sql, $calcPositions);
	}

	function __toString() {
		$psc = new \PHPSQL\Creator($this->parsed);
		return $psc->created.'';
	}

}
