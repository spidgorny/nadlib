<?php

class OODBase {
	/**
	 *
	 *
	 * @var MySQL
	 */
	protected $db;
	protected $table;
	protected $titleColumn = 'name';
	public $idField = 'id';
	public $id;
	public $data = array();

	/**
	 * Enter description here...
	 *
	 * @param integer/array $id - can be ID in the database or the whole records
	 * as associative array
	 */
	function __construct($id = NULL) {
		$this->table = Config::getInstance()->prefixTable($this->table);
		$this->db = Config::getInstance()->db;
		$this->init($id);
		new AsIs('whatever'); // autoload will work from a different path when in destruct()
	}

	function init($id) {
		if (is_array($id)) {
			$this->data = $id;
			$this->id = $this->data[$this->idField];
		} else if ($id instanceof SQLWhere) {
			$this->findInDB($id->getAsArray());
		} else if ($id) {
			$this->id = $id;
			$this->findInDB(array($this->idField => $this->id));
		}
	}

	function getName() {
		return $this->data[$this->titleColumn];
	}

	/**
	 * Returns $this
	 *
	 * @param array $data
	 * @return unknown
	 */
	function insert(array $data) {
		$qb = Config::getInstance()->qb;
		$query = $qb->getInsertQuery($this->table, $data);
		$this->db->perform($query);
		$this->findInDB($data);
		return $this;
	}

	/**
	 * Returns nothing!!!
	 *
	 * @param array $data
	 */
	function update(array $data) {
		if ($this->id) {
			$qb = Config::getInstance()->qb;
			$query = $qb->getUpdateQuery($this->table, $data, array($this->idField => $this->id));
			$res = $this->db->perform($query);
			$this->db->perform($query);
			if ($_COOKIE['debug']) {
				//debug($query); exit();
			}
			$this->data = array_merge($this->data, $data); // should overwrite
		} else {
			throw new Exception(__('Updating is not possible as there is no ID defined.'));
		}
		return $res;
	}

	function delete(array $where) {
		$qb = Config::getInstance()->qb;
		$query = $qb->getDeleteQuery($this->table, $where);
		//debug($query);
		$this->db->perform($query);
	}

	/**
	 *
	 * @param array $where
	 * @param <type> $orderby
	 * @return boolean (id) of the found record
	 */
	function findInDB(array $where, $orderby = '') {
		$rows = Config::getInstance()->qb->fetchSelectQuery($this->table, $where, $orderby);
		//debug($rows);
		if ($rows) {
			$this->data = $rows[0];
		} else {
			$this->data = array();
		}
		$this->init($this->data); // array, otherwise infinite loop
		return $this->id;
	}

	/**
	 *
	 * @param array $where
	 * @param <type> $orderby
	 * @return boolean (id) of the found record
	 */
	function findInDBbySQLWhere(SQLWhere $where, $orderby = '') {
		$rows = $this->db->fetchSelectQuerySW($this->table, $where, $orderby);
		//debug($rows);
		if ($rows) {
			$this->data = $rows[0];
		} else {
			$this->data = array();
		}
		$this->init($this->data); // array, otherwise infinite loop
		return $this->id;
	}

	function __toString() {
		//return new slTable(array(array_keys($this->data), array_values($this->data))).'';
		return $this->getName();
	}

}
