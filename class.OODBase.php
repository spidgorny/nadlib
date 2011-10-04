<?php

class OODBase {
	protected $db;
	public $table;
	protected $titleColumn = 'name';
	public $idField = 'id';
	public $id;
	public $data = array();

	/**
	 * Enter description here...
	 *
	 * @param integer/array $id - can be ID in the database or the whole records as associative array
	 */
	function __construct($id = NULL) {
		$this->db = $GLOBALS['i']->db;
		$this->init($id);
	}

	function init($id) {
		if (is_array($id)) {
			$this->data = $id;
			$this->id = $this->data[$this->idField];
		} else if ($id) {
			$this->findInDB(array($this->idField => $id));
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
		$qb = new SQLBuilder();
		$data['cuser'] = $GLOBALS['i']->user->id;
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
			$data['muser'] = $GLOBALS['i']->user->id;
			$qb = new SQLBuilder();
			$query = $qb->getUpdateQuery($this->table, $data, array($this->idField => $this->id));
			//print($query); exit();
			$this->db->perform($query);
			if ($_COOKIE['debug']) {
				//debug($query); exit();
			}
			$this->data = array_merge($this->data, $data); // should overwrite
		} else {
			throw new Exception('Updating is not possible as there is no ID defined.');
		}
	}

	function delete(array $data) {
		$qb = new SQLBuilder();
		$query = $qb->getDeleteQuery($this->table, $data);
		$this->db->perform($query);
	}

	function findInDB(array $where, $orderby = '') {
		$rows = $this->db->fetchSelectQuery($this->table, $where, $orderby);
		if ($rows) {
			$this->data = $rows[0];
		} else {
			$this->data = array();
		}
		$this->id = $this->data[$this->idField];
	}

	function __toString() {
		return $this->getName().'';
	}

}
