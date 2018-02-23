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
//		$config = Config::getInstance();
//		debug(
//			gettype2($this),
//			gettype2($this->db),
//			gettype2($this->db->qb),
//			gettype2($this->db->qb->db),
//			gettype2($config->my),
//			gettype2($config->getDB()),
//			gettype2($config->getDB()->qb)
//		);
		return implode(', ', $this->db->quoteKeys($this->parts));
	}

	function getAll()
	{
		return $this->parts;
	}

}
