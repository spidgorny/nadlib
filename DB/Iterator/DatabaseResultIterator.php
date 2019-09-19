<?php

/**
 * This class is a replacement for fetchAll - foreach combination and should be used to reduce the memory
 * requirements of the script. It's ment to mimic Iterator classes in PHP5, but doesn't inherit the interface
 * completely. (wrong!?)
 *
 * The correct order is
 * rewind()
 *
 * [
 *    next()
 *    valid()
 *    current()
 * ]
 */
class DatabaseResultIterator implements Iterator, Countable
{

	/**
	 * If defined it will influence the key() method return value
	 * @var string
	 */
	public $defaultKey;

	/**
	 * Query result
	 * @var resource
	 */
	public $dbResultResource;

	/**
	 * Must be false to indicate no results
	 * @var array
	 */
	public $row = FALSE;

	/**
	 * Amount. Must be NULL for the first time.
	 * @var int
	 */
	public $rows = NULL;

	/**
	 * Will return the value of the current row corresponding to $this->defaultKey
	 * or number 0, 1, 2, 3, ... otherwise
	 * @var int
	 */
	public $key = 0;

	/**
	 * @var DBInterface
	 */
	protected $db;

	public $query;

	public $debug = false;

	function __construct(DBInterface $db, $defaultKey = NULL)
	{ // 'uid'
		$this->db = $db;
		$this->defaultKey = $defaultKey;
	}

	function setResult($res)
	{
		$this->dbResultResource = $res;
		$this->rows = $this->count();
	}

	function perform($query)
	{
		$this->log(__METHOD__);
		$this->query = $query;
		$params = [];
		if ($query instanceof SQLSelectQuery) {
			$params = $query->getParameters();
//			debug($query, $params);
		}
		$this->dbResultResource = $this->db->perform($query, $params);
		$this->log(__METHOD__, ['dbResultResource' => $this->dbResultResource]);
		$this->rows = $this->count();
		//$this->rewind();
	}

	function count()
	{
		$this->log(__METHOD__);
		if (is_null($this->rows)) {
			$numRows = $this->db->numRows($this->dbResultResource);
			$this->log(__METHOD__, ['numRows' => $numRows]);
			return $numRows;
		} else {
			return $this->rows;
		}
	}

	function rewind()
	{
		$this->log(__METHOD__);
		if ($this->rows) {
			$this->key = 0;
			$this->db->dataSeek($this->dbResultResource, 0);
			$this->next();
		}
	}

	function current()
	{
		$this->log(__METHOD__);
		return $this->row;
	}

	function key()
	{
		return $this->key;
	}

	function next()
	{
		$this->log(__METHOD__);
		$this->row = $this->retrieveRow();
		if (is_array($this->row)) {
			if ($this->defaultKey) {
				$this->key = ifsetor($this->row[$this->defaultKey]);
			} else {
				$this->key++;
			}
		}
		//debug($this->key, $this->row);
	}

	function retrieveRow()
	{
		$this->log(__METHOD__);
		$row = $this->db->fetchRow($this->dbResultResource);
//		debug(__METHOD__, $row);
		return $row;
	}

	function valid()
	{
		$this->log(__METHOD__);
		return $this->row !== NULL && $this->row !== FALSE;
	}

	/**
	 * Should not be used - against the purpose, but nice for debugging
	 * @return array
	 */
	function fetchAll()
	{
		$this->log(__METHOD__);
		$data = array();
		foreach ($this as $row) {
			$data[] = $row;
		}
		return $data;
	}

	function __destruct()
	{
		$this->log(__METHOD__);
		$this->db->free($this->dbResultResource);
	}

	function skip($rows)
	{
		$this->log(__METHOD__, $rows);
		while ($rows) {
			$this->next();
			$rows--;
		}
		$this->key += $rows;
		return $this;
	}

	function log($method, $data = NULL)
	{
		if ($this->debug) {
			if ($data) {
				debug($method, $data);
			} else {
				debug($method);
			}
		}
	}

}
