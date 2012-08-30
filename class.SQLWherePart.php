<?php

/**
 * Base class in order to check instanceof SQLWherePart
 */

class SQLWherePart {

	/**
	 * @var SQLBuilder
	 */
	protected $qb;

	protected $sql = '';
	protected $field;

	function __construct($sql = '') {
		$this->sql = $sql;
	}

	function __toString() {
		return $this->sql;
	}
	
	function injectQB(SQLBuilder $qb) {
		$this->qb = $qb;
	}

	function injectField($field) {
		$this->field = $field;
	}

}
