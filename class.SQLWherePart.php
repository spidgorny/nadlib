<?php

/**
 * Base class in order to check instanceof SQLWherePart
 */

class SQLWherePart {

	/**
	 * @var SQLBuilder
	 */
	protected $qb;

	protected $field;

	function indectQB($qb) {
		$this->qb = $qb;
	}

	function injectField($field) {
		$this->field = $field;
	}

}