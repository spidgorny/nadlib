<?php

class SQLQuery extends PHPSQL\Parser {

	public $parsed;

	public function __construct($sql = false, $calcPositions = false) {
		if ($sql instanceof SQLQuery) {
			$this->parsed = $sql->parsed;
		} else {
			parent::__construct($sql, $calcPositions);
		}
	}

	function __toString() {
		return $this->getQuery();
	}

	function getQuery() {
		$psc = new \PHPSQL\Creator($this->parsed);
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
