<?php

class SQLQuery extends PHPSQLParser\PHPSQLParser {

	public $parsed;

	public function __construct($sql = false, $calcPositions = false) {
		parent::__construct($sql, $calcPositions);
	}

	function __toString() {
		$psc = new PHPSQLParser\PHPSQLCreator($this->parsed);
		return $psc->created.'';
	}

}
