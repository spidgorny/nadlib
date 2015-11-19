<?php

class SQLQuery extends PHPSQLParser\PHPSQLParser {

	public $parsed;

	public function __construct($sql = false, $calcPositions = false) {
		parent::__construct($sql, $calcPositions);
	}

	function __toString() {
		return $this->getQuery();
	}

	function getQuery() {
		$psc = new \PHPSQLParser\PHPSQLCreator($this->parsed);
		$query = $psc->created.'';
		$query = str_replace([
			'SELECT',
			'FROM',
			'WHERE',
			'GROUP',
			'HAVING',
			'ORDER',
			'LIMIT',
		], [
			"SELECT",
			"\nFROM",
			"\nWHERE",
			"\nGROUP",
			"\nHAVING",
			"\nORDER",
			"\nLIMIT",
		], $query);
		return $query;
	}

}
