<?php
/**
 * This class is a replacement for fetchAll - foreach combination and should be used to reduce the memory
 * requirements of the script. It's ment to mimic Iterator classes in PHP5, but doesn't inherit the interface
 * completely. (wrong!?)
 */

class DatabaseResultIterator implements Iterator, Countable {
	var $defaultKey;
	var $dbResultResource;

	/**
	 * Must be false to indicate no results
	 * @var array
	 */
	var $row = FALSE;

	/**
	 * Amount
	 * @var int
	 */
	var $rows = 0;

	var $key = 0;

	/**
	 * @var MySQL|dbLayer
	 */
	var $db;

	function __construct(DIContainer $di, $defaultKey = NULL) { // 'uid'
		$this->defaultKey = $defaultKey;
		//$this->db = Config::getInstance()->db;
		$this->db = $di->db;
	}

	function perform($query) {
		$this->dbResultResource = $this->db->perform($query);
		$this->rows = $this->count();
		$this->rewind();
	}

	function rewind() {
		if ($this->rows) {
			$this->db->dataSeek($this->dbResultResource, 0);
			$this->next();
		}
	}

	function current() {
		return $this->row;
	}

	function key() {
		return $this->key;
	}

	function next() {
		$this->row = $this->retrieveRow();
		if ($this->defaultKey) {
			$this->key = $this->row[$this->defaultKey];
		} else {
			$this->key++;
		}
		return $this->row;
	}

	function retrieveRow() {
		$row = $this->db->fetchRow($this->dbResultResource);
		return $row;
	}

	function valid() {
		return $this->row !== FALSE;
	}

	function count() {
		return $this->db->numRows($this->dbResultResource);
	}

	/**
	 * Should not be used - against the purpose, but nice for debugging
	 * @return array
	 */
	function fetchAll() {
		$data = array();
		foreach ($this as $row) {
			$data[] = $row;
		}
		return $data;
	}

	function __destruct() {
		$this->db->free($this->dbResultResource);
	}

}
