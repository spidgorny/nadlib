<?php

class OODBase {
	/**
	 *
	 *
	 * @var MySQL
	 */
	protected $db;
	protected $table;
	protected $idField = 'id';
	protected $titleColumn = 'name';
	public $id;
	public $data = array();
	public $query;	// for debug

	/**
	 * Enter description here...
	 *
	 * @param integer/array $id - can be ID in the database or the whole records
	 * as associative array
	 */
	function __construct($id = NULL) {
		$config = Config::getInstance();
		$this->table = $config->prefixTable($this->table);
		$this->db = $config->db;
		$this->init($id);
		new AsIs('whatever'); // autoload will work from a different path when in destruct()
	}

	function init($id) {
		if (is_array($id)) {
			$this->data = $id;
			$this->id = $this->data[$this->idField];
			//debug(__METHOD__, $this->id, $this->data);
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
	 * @return self
	 */
	function insert(array $data) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		//$data['ctime'] = new AsIs('NOW()');
		$qb = Config::getInstance()->qb;
		$this->query = $qb->getInsertQuery($this->table, $data);
		$this->db->perform($this->query);
		$this->init($this->db->lastInsertID());
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this;
	}

	/**
	 * Returns nothing!!!
	 *
	 * @param array $data
	 * @return resource
	 */
	function update(array $data) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($this->id) {
			//$data['mtime'] = new AsIs('NOW()');
			//$data['muser'] = $GLOBALS['i']->user->id;					// TODO: add to DB
			$qb = Config::getInstance()->qb;
			$this->query = $qb->getUpdateQuery($this->table, $data, array($this->idField => $this->id));
			//debug($query);
			$res = $this->db->perform($this->query);
			$this->data = array_merge($this->data, $data); // should overwrite
		} else {
			$this->db->rollback();
			throw new Exception(__('Updating is not possible as there is no ID defined.'));
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $res;
	}

	function delete(array $where = NULL) {
		if (!$where) {
			$where = array($this->idField => $this->id);
		}
		$qb = Config::getInstance()->qb;
		$this->query = $qb->getDeleteQuery($this->table, $where);
		//debug($query);
		return $this->db->perform($this->query);
	}

	/**
	 *
	 * @param array $where
	 * @param <type> $orderby
	 * @return boolean (id) of the found record
	 */
	function findInDB(array $where, $orderby = '') {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$rows = $this->db->fetchSelectQuery($this->table, $where, $orderby);
		$this->query = $this->db->lastQuery;
		//d($rows);
		if (is_array($rows)) {
			if (is_array(current($rows))) {
				$this->data = current($rows);
			} else {
				$this->data = $rows;
			}
		} else {
			$data = array();
		}
		$this->init($data); // array, otherwise infinite loop
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this->id;
	}

	static function findInstance(array $where, $static = 'Assignment') {
		//$static = get_called_class();
		//$static = 'Assignment'; // PHP 5.3 required
		//debug($static);
		$obj = new $static();
		$obj->findInDB($where);
		return $obj;
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
		return $this->getName().'';
	}

	function insertOrUpdate() {
		if ($this->id) {
			$this->update($this->data);
		} else {
			$this->insert($this->data);
		}
	}

	function insertUpdate(array $fields, array $where) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->db->transaction();
		$this->findInDB($where);
		if ($this->id) { // found
			$this->update($fields);
			$op = 'UPD '.$this->id;
		} else {
			$this->insert($fields + $where);
			$this->findInDB($where);
			$op = 'INS';
		}
		$this->db->commit();
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $op;
	}

	function renderAssoc() {
		$assoc = $this->data;
		foreach ($assoc as $key => $val) {
			if (!$val) {
				unset($assoc[$key]);
			}
		}
		return slTable::showAssoc($assoc);
	}

}
