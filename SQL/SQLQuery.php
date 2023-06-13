<?php

class SQLQuery extends \PHPSQLParser\PHPSQLParser {

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
		$psc = new PHPSQLParser\PHPSQLCreator($this->parsed);
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

	public function appendCalcRows() {
		//debug($sql->parsed['SELECT']);
		array_unshift($this->parsed['SELECT'], [
			'expr_type' => 'reserved',
			'base_expr' => 'SQL_CALC_FOUND_ROWS',
			'delim'     => ' ',
		]);
		//debug($sql->parsed);
		if ($this->parsed['ORDER'] && $this->parsed['ORDER'][0]['base_expr'] != 'FIELD') {
			$this->parsed['ORDER'][0]['expr_type'] = 'colref';
		}
		//debug($sql->parsed);
	}

}
