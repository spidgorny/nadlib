<?php

class SQLSelect {

	/**
	 * @var DBInterface
	 */
	var $db;

	protected $parts = array();

	/**
	 * SQLSelect constructor.
	 *
	 * @param $parts array|string
	 */
	function __construct($parts) {
		if (is_array($parts)) {
			$this->parts = $parts;
		} elseif ($parts) {
			$this->parts[] = $parts;
		} else {
			$this->parts[] = '*';
		}
	}

	function injectDB(DBInterface $db) {
		$this->db = $db;
	}

	function __toString() {
		return implode(', ', $this->parts);
	}

}
