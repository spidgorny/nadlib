<?php
/**
 * This class is a replacement for fetchAll - foreach combination and should be used to reduce the memory
 * requirements of the script. It's ment to mimic Iterator classes in PHP5, but doesn't inherit the interface
 * completely. (wrong!?)
 */

class DatabaseResultIterator implements Iterator, Countable {

	/**
	 * If defined it will influence the key() method return value
	 * @var string
	 */
	var $defaultKey;

	/**
	 * Query result
	 * @var resource
	 */
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

	/**
	 * Will return the value of the current row corresponding to $this->defaultKey
	 * or number 0, 1, 2, 3, ... otherwise
	 * @var int
	 * First ++ will set it to 0
	 */
	var $key = -1;

	/**
	 * @var MySQL|dbLayer|dbLayerODBC|dbLayerPDO
	 */
	var $db;

	function __construct(dbLayerBase $db, $defaultKey = NULL) { // 'uid'
		$this->defaultKey = $defaultKey;
		//$this->db = Config::getInstance()->db;
		$this->db = $db;
	}

	function perform($query) {
		$this->dbResultResource = $this->db->perform($query);
		$this->rows = $this->count();
		debug($this->rows);
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
			++$this->key;
		}
		return $this->row;
	}

	function retrieveRow() {
		$row = $this->db->fetchRow($this->dbResultResource);
		return $row;
	}

	/**
	 * dbLayer returns [] if pg_fetch_assoc() returns FALSE
	 * @return bool
	 */
	function valid() {
		return $this->row !== FALSE && $this->row !== [];
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
