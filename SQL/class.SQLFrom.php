<?php

class SQLFrom {

	/**
	 * @var DBInterface
	 */
	var $db;

	protected $parts = array();

	function __construct($from) {
		$this->parts[] = trim($from);
	}

	function __toString() {
		return implode(', ', $this->db->quoteKeys($this->parts));
	}

}
