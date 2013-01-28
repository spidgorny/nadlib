<?php

class OODBase {
	/**
	 * @var MySQL
	 */
	protected $db;

	public $table;
	protected $idField = 'id';
	protected $titleColumn = 'name';
	public $id;
	public $data = array();

	/**
	 * @var array of visible fields which serves as a definition for a corresponding Collection
	 * and maybe to HTMLFormTable as well
	 */
	public $thes = array();

	/**
	 * @var self
	 */
	static $instance = array();

	/**
	 * @var string - saved after insertUpdate
	 */
	public $lastQuery;

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
		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : array('name' => $val);
		}
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
		} else if (is_scalar($id)) {
			$this->id = $id;
			$this->findInDB(array($this->idField => $this->id));
		} else if (!is_null($id)) {
			debug($id);
			throw new Exception(__METHOD__);
		}
	}

	function getName() {
		return $this->data[$this->titleColumn] ? $this->data[$this->titleColumn] : $this->id;
	}

	/**
	 * Returns $this
	 *
	 * @param array $data
	 * @return OODBase
	 */
	function insert(array $data) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		//$data['ctime'] = new AsIs('NOW()');
		$qb = Config::getInstance()->qb;
		$query = $qb->getInsertQuery($this->table, $data);
		$res = $this->db->perform($query);
		$this->lastQuery = $this->db->lastQuery;	// save before commit
		$id = $this->db->lastInsertID($res, $this->table);
		$this->init($id ? $id : $this->id);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this;
	}

	/**
	 * Returns nothing!!!
	 *
	 * @param array $data
	 * @throws Exception
	 * @return resource
	 */
	function update(array $data) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($this->id) {
			//$data['mtime'] = new AsIs('NOW()');
			//$data['muser'] = $GLOBALS['i']->user->id;					// TODO: add to DB
			$qb = Config::getInstance()->qb;
			$query = $qb->getUpdateQuery($this->table, $data, array($this->idField => $this->id));
			//debug($query);
			$res = $this->db->perform($query);
			$this->lastQuery = $this->db->lastQuery;	// save before commit
			// If the input arrays have the same string keys,
			// then the later value for that key will overwrite the previous one.
			//$this->data = array_merge($this->data, $data);
			$this->init($this->id);
		} else {
			$this->db->rollback();
			debug_pre_print_backtrace();
			throw new Exception(__('Updating is not possible as there is no ID defined.'));
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $res;
	}

	function delete(array $where = NULL) {
		if (!$where) {
			$where = array($this->idField => $this->id);
		}
		$qb = Config::getInstance()->qb;
		$query = $qb->getDeleteQuery($this->table, $where);
		//debug($query);
		return $this->db->perform($query);
	}

	/**
	 *
	 * @param array $where
	 * @param string $orderby
	 * @return boolean (id) of the found record
	 */
	function findInDB(array $where, $orderby = '') {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$rows = $this->db->fetchSelectQuery($this->table, $where, $orderby);
		if (is_array($rows)) {
			if (is_array(current($rows))) {
				$data = current($rows);
			} else {
				$data = $rows;
			}
		} else {
			$data = array();
		}
		$this->init($data); // array, otherwise infinite loop
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this->id;
	}

	/**
	 * Still searches in DB with findInDB, but makes a new object for you
	 *
	 * @param array $where
	 * @param null $static
	 * @return mixed
	 * @throws Exception
	 */
	static function findInstance(array $where, $static = NULL) {
		if (!$static) {
			if (function_exists('get_called_class')) {
				$static = get_called_class();
			} else {
				throw new Exception('__METHOD__ requires object specifier until PHP 5.3.');
			}
		}
		$obj = new $static();
		$obj->findInDB($where);
		return $obj;
	}

	/**
	 *
	 * @param SQLWhere $where
	 * @param string $orderby
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

	/**
	 * Depends on $this->id and $this->data will be saved into DB
	 * @return string
	 */
	function insertOrUpdate() {
		if ($this->id) {
			$ret = $this->update($this->data);
			$action = 'UPD';
		} else {
			$ret = $this->insert($this->data);
			$action = 'INS';
		}
		return $action;
	}

	/**
	 * Searches for the record defined in $where and then creates or updates.
	 *
	 * @param array $fields
	 * @param array $where
	 * @return string
	 */
	function insertUpdate(array $fields, array $where) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->db->transaction();
		$this->findInDB($where);
		//debug($this->db->lastQuery);
		if ($this->id) { // found
			$this->update($fields);
			$op = 'UPD '.$this->id;
		} else {
			//debug($where, $this->db->lastQuery); exit();
			$this->insert($fields + $where);
			$this->findInDB($where);
			$op = 'INS';
		}
		$this->db->commit();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $op;
	}

	function renderAssoc() {
		//debug($this->thes);
		if ($this->thes) {
			$assoc = array();
			foreach ($this->thes as $key => $desc) {
				$desc = is_array($desc) ? $desc : array('name' => $desc);
				if ($desc['showSingle'] !== false) {
					$assoc[$key] = array(
						0 => $desc['name'],
						'' => $this->data[$key],
						'.' => $desc,
					);
				}
			}
			$s = new slTable($assoc. '', array(0 => '', '' => array('no_hsc' => true)));
		} else {
			$assoc = $this->data;
			foreach ($assoc as $key => $val) {
				if (!$val) {
					unset($assoc[$key]);
				}
			}
			$s = slTable::showAssoc($assoc);
		}
		return $s;
	}

	/**
	 * @param $id
	 * @return OODBase
	 */
	static function getInstance($id) {
		if (is_scalar($id)) {
			$inst = &self::$instance[$id];
			if (!$inst) {
				//debug('new ', get_called_class(), $id, array_keys(self::$instance));
				$inst = new static();	// don't put anything else here
				$inst->init($id);		// separate call to avoid infinite loop in ORS
			}
		} else {
			$static = get_called_class();
			$inst = new $static($id);
		}
		return $inst;
	}

	function getObjectInfo() {
		return get_class($this).': "'.$this->getName().'" (id:'.$this->id.' #'.spl_object_hash($this).')';
	}

}
