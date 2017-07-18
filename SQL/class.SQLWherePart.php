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
		$this->qb = Config::getInstance()->getQb();
	}

	function __toString() {
		if ($this->field && !is_numeric($this->field)) {
			$part1 = $this->qb->quoteWhere(
				array($this->field => $this->sql)
			);
			return implode('', $part1);
		} else {
			return $this->sql.'';
		}
	}
	
	function injectQB(SQLBuilder $qb) {
		$this->qb = $qb;
	}

	function injectField($field) {
		$this->field = $field;
	}

	function debug() {
		return $this->sql;
	}

}
