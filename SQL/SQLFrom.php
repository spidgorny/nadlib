<?php

class SQLFrom extends SQLWherePart {

	/**
	 * @var DBInterface
	 */
	var $db;

	protected $parts = array();

	function __construct($from) {
		parent::__construct();
		$this->parts[] = trim($from);
	}

	function __toString() {
		return implode(', ', $this->db->quoteKeys($this->parts));
	}

}
