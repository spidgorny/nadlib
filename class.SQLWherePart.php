<?php

/**
 * Base class in order to check instanceof SQLWherePart
 */

class SQLWherePart {

	protected $sql = '';

	function __construct($sql) {
		$this->sql = $sql;
	}

	function __toString() {
		return $this->sql;
	}

}
