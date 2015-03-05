<?php

/**
 * This class is the base class for all classes based on OOD. It contains only things general to all descendants.
 * It contain all the information from the database related to the project as well as methods to manipulate it.
 *
 */

abstract class OODBase {

	/**
	 * @var MySQL|dbLayer|dbLayerDB|dbLayerPDO|dbLayerMS
	 * public to allow unset($o->db); before debugging
	 */
	protected $db;

	/**
	 * database table name for referencing everywhere. MUST BE OVERRIDEN IN SUBCLASS!
	 * @var string
	 */
	/**
	 * Help to identify missing table value
	 */
	public $table = 'OODBase_undefined_table';

	public $idField = 'id';

	protected $titleColumn = 'name';

	/**
	 * @var int database ID
	 */
	public $id = NULL;

	/**
	 * @var array data from DB
	 */
	public $data = array();

	/**
	 * @var array of visible fields which serves as a definition for a corresponding Collection
	 * and maybe to HTMLFormTable as well
	 */
	public $thes = array();

	/**
	 * to allow extra filtering
	 * @var array
	 */
	protected $where = array();
	
	/**
	 * array[get_called_class()][$id]
	 */
	static $instances = array();

	/**
	 * @var string - saved after findInDB
	 */
	public $lastSelectQuery;

	/**
	 * @var string - saved after insert/update
	 */
	public $lastQuery;

	/**
	 * For parent retrieval in getParent()
	 * @var string
	 */
	public $parentField = 'pid';

	/**
	 * @var ?
	 */
	public $forceInit;

	/**
	 * Constructor should be given the ID of the existing record in DB.
	 * If you want to use methods without knowing the ID, the call them statically like this Version::insertRecord();
	 *
	 * @param integer|array|SQLWhere $id - can be ID in the database or the whole records
	 * as associative array
	 * @return OODBase
	 */
	function __construct($id = NULL) {
		//debug(get_called_class(), __FUNCTION__, $id);
		if (class_exists('Config')) {
			$config = Config::getInstance();
			$this->table = $config->prefixTable($this->table);
			if (!$this->db) {
				$this->db = $config->db;
			}
		} else {
			$this->db = isset($GLOBALS['db']) ? $GLOBALS['db'] : NULL;
		}
		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : array('name' => $val);
		}
		$this->init($id);
	}

	/**
	 * Retrieves data from DB.
	 *
	 * @param int|array|SQLWhere $id
	 * @param bool $fromFindInDB
	 * @throws Exception
	 */
	function init($id, $fromFindInDB = false) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (is_array($id)) {
			if (is_scalar($this->idField) || $fromFindInDB) {
				$this->initByRow($id);
			} else {
				$this->id = $id;
				//debug($id, $fromFindInDB, $this->id);
				$this->findInDB($this->id);	// will call init()
				if (!$this->data) {
					$this->id = NULL;
				}
			}
		} else if ($id instanceof SQLWhere) {
			$where = $id->getAsArray();
			$this->findInDB($where);
		} else if (is_scalar($id)) {
			$this->id = $id;
			$this->findInDB(array($this->idField => $this->id));
			if (!$this->data) {
				$this->id = NULL;
			}
		} else if (!is_null($id)) {
			debug($id);
			if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
			throw new Exception(__METHOD__);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function getName() {
		return ifsetor($this->data[$this->titleColumn], $this->id);
	}

	function initByRow(array $row) {
		$this->data = $row;
		if (is_array($this->idField)) {
			$this->id = array();
			foreach ($this->idField as $field) {
				$this->id[$field] = $this->data[$field];
			}
		} else if (ifsetor($this->data[$this->idField])) {
			$this->id = $this->data[$this->idField];
		} else {
			throw new InvalidArgumentException(get_class($this).'::'.__METHOD__);
		}
	}

	/**
	 * Returns $this
	 *
	 * @param array $data
	 * @throws Exception
	 * @return OODBase
	 */
	function insert(array $data) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		Index::getInstance()->log(get_called_class().'::'.__FUNCTION__, $data);
		//$data['ctime'] = new SQLNow();
		$query = $this->db->getInsertQuery($this->table, $data);
		//debug($query);
		$res = $this->db->perform($query);
		$this->lastQuery = $this->db->lastQuery;	// save before commit
		$id = $this->db->lastInsertID($res, $this->table);
		if ($id) {
			$this->init($id ? $id : $this->id);
		} else {
			throw new Exception('OODBase for '.$this->table.' no insert id after insert');
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this;
	}

	/**
	 * Updates current record ($this->id)
	 *
	 * @param array $data
	 * @throws Exception
	 * @return resource result from the runUpdateQuery
	 */
	function update(array $data) {
		if ($this->id) {
			if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
			Index::getInstance()->log(get_called_class().'::'.__FUNCTION__.'('.$this->id.')', $data);
			$where = array();
			if (is_array($this->idField)) {
				foreach ($this->idField as $field) {
					$where[$field] = $this->data[$field];
				}
			} else {
				$where[$this->idField] = $this->id;
			}
			$query = $this->db->getUpdateQuery($this->table, $data, $where);
			$this->lastQuery = $query;
			$res = $this->db->perform($query);
			//debug($query, $res, $this->db->lastQuery, $this->id);
			$this->lastQuery = $this->db->lastQuery;	// save before commit
			// If the input arrays have the same string keys,
			// then the later value for that key will overwrite the previous one.
			//$this->data = array_merge($this->data, $data);
			$this->init($this->id);
			if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		} else {
			//$this->db->rollback();
			debug_pre_print_backtrace();
			throw new Exception(__('Updating '.$this->table.' is not possible as there is no ID defined.'));
		}
		return $res;
	}

	function delete(array $where = NULL) {
		if (!$where) {
			$where = array($this->idField => $this->id);
		}
		Index::getInstance()->log(get_called_class().'::'.__FUNCTION__, $where);
		$query = $this->db->getDeleteQuery($this->table, $where);
		$this->lastQuery = $query;
		return $this->db->perform($query);
	}

	/**
	 * Retrieves a record from the DB and calls $this->init()
	 * @param array $where
	 * @param string $orderByLimit
	 * @return bool of the found record
	 * @throws Exception
	 */
	function findInDB(array $where, $orderByLimit = '') {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__.' ('.$this->table.')');
		$rows = $this->db->fetchOneSelectQuery($this->table,
			$this->where + $where, $orderByLimit);
		$this->lastSelectQuery = $this->db->lastQuery;
		if (is_array($rows)) {
			$data = $rows;
			$this->init($data, true);
		} else {
			$data = array();
			if ($this->forceInit) {
				$this->init($data, true);
			}
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__.' ('.$this->table.')');
		return $data;
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
		/** @var static $obj */
		$obj = new $static();
		$obj->findInDB($where);
		if ($obj->id) {
			self::$instances[$static][$obj->id] = $obj;
		}
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
		//debug($action, $this->db->lastQuery); exit();
		return $action;
	}

	/**
	 * Searches for the record defined in $where and then creates or updates.
	 *
	 * @param array $fields
	 * @param array $where
	 * @param array $insert - additional insert fields not found in $fields
	 * @param array $update - additional update fields not found in $fields
	 * @return string whether the record already existed
	 */
	function insertUpdate(array $fields,
						  array $where = array(),
						  array $insert = array(),
						  array $update = array()
	) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->db->transaction();
		if ($where) {
			$this->findInDB($where);
		}
		//debug($this->id, $this->data); exit();
		if ($this->id) { // found
			$left = array_intersect_key($this->data, $fields);		// keys need to have same capitalization
			$right = array_intersect_key($fields, $this->data);
			//debug($left, $right); exit();
			if ($left == $right) {
				$op = 'SKIP';
			} else {
				$this->update($fields + $update);
				$op = 'UPDATE '.$this->id;
			}
		} else {
			//debug($this->id, $this->data);
			$this->insert($fields + $where + $insert);
			//debug($where, $this->id, $this->data, $fields + $where + $insert, $this->lastQuery);
			$op = 'INSERT '.$this->id;
			//debug($this->id, $this->data, $op, $this->db->lastQuery);
		}
		$this->db->commit();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $op;
	}

	/**
	 * Uses $this->thes if available
	 * Hides fields without values
	 * @param array $assoc
	 * @param bool $recursive
	 * @param bool $skipEmpty
	 * @return slTable
	 */
	function renderAssoc(array $assoc = NULL, $recursive = false, $skipEmpty = true) {
		$assoc = $assoc ? $assoc : $this->data;
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
			$s = new slTable($assoc, 'class="table table-striped"', array(
				0 => '',
				'' => array('no_hsc' => true)
			));
		} else {
			foreach ($assoc as $key => &$val) {
				if (!$val && $skipEmpty) {
					unset($assoc[$key]);
				} else if (is_array($val) && $recursive) {
					$val = self::renderAssoc($val, $recursive);
				}
			}
			$s = slTable::showAssoc($assoc);
		}
		return $s;
	}

	function showAssoc(array $thes = array('id' => 'ID', 'name' => 'Name')) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$content = '<div class="showAssoc">
		<h3>'.get_class($this).':</h3>';
			foreach ($thes as $key => $name) {
				$name = is_array($name) ? $name['name'] : $name;
				$val = $this->data[$key];
				$content .= '<div class="prefix10">'.$name.':</div>';
				$content .= $val.'<br clear="all" />';
			}
		$content .= '</div>';
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $content;
	}

	/**
	 * // TODO: initialization by array should search in $instances as well
	 * @param $id int
	 * @return self|$this|static
	 */
	public static function getInstance($id) {
		$static = get_called_class();
		/*nodebug(array(
			__METHOD__,
			'class' => $static,
			'instances' => sizeof(self::$instances[$static]),
			'id' => $id,
			'exists' => self::$instances[$static]
				? implode(', ', array_keys(self::$instances[$static]))
				: NULL,
		));*/
		if (is_scalar($id)) {
			$inst = ifsetor(self::$instances[$static][$id]);
			if (!$inst) {
				//debug('new ', get_called_class(), $id, array_keys(self::$instances));
				if (false) {
					$inst = new $static($id);	// VersionInfo needs it like this
				} else {
												// NewRequest needs it like this
					$inst = new $static();		// don't put anything else here
					self::$instances[$static][$id] = $inst; // BEFORE init() to avoid loop
					$inst->init($id);			// separate call to avoid infinite loop in ORS
				}
			}
		} else {
			$inst = new $static();
			$inst->init($id);
			if ($inst->id) {
				self::$instances[$static][$inst->id] = $inst;
			}
		}
		return $inst;
	}

	static function clearInstances() {
		self::$instances[get_called_class()] = array();
		gc_collect_cycles();
	}

	function getObjectInfo() {
		return get_class($this).': "'.$this->getName().'" (id:'.$this->id.' #'.spl_object_hash($this).')';
	}

	/**
	 * Is cached in instances
	 * @param string $name
	 * @param null $field
	 * @return static
	 */
	static function getInstanceByName($name, $field = NULL) {
		$self = get_called_class();
		//debug(__METHOD__, $self, $name, count(self::$instances[$self]));

		$c = NULL;
		// first search instances
		if (is_array(ifsetor(self::$instances[$self]))) {
			foreach (self::$instances[$self] as $inst) {
				$field = $field ? $field : $inst->titleColumn;
				if ($inst->data[$field] == $name) {
					$c = $inst;
					break;
				}
			}
		}

		if (!$c) {
			$c = new $self();
			/** @var $c OODBase */
			$field = $field ? $field : $c->titleColumn;
			$c->findInDBsetInstance(array(
				$field => $name,
			));
		}
		return $c;
	}

	/**
	 * @param $insert
	 * @param $class
	 * @return static
	 * @throws Exception
	 */
	static function createRecord($insert, $class) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		//$insert = $this->db->getDefaultInsertFields() + $insert; // no overwriting?
		//debug($insert);

		Index::getInstance()->log(get_called_class().'::'.__FUNCTION__, $insert);
		$db = Config::getInstance()->db;
		$query = $db->getInsertQuery(constant($class.'::table'), $insert);
		//t3lib_div::debug($query);
		$res = $db->perform($query);
		if ($res) {
			$id = $db->getLastInsertID($res, constant($class.'::table'));
			//t3lib_div::debug($id);

			if ($class) {
				$object = new $class($id);
			} else {
				$object = $id;
			}
		} else {
			$object = NULL;
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $object;
	}

	function getURL(array $params) {
		$c = Index::getInstance()->controller;
		return $c->getURL($params);
	}

	function getVarType($name) {
		$r = new ReflectionClass($this);
		$p = $r->getProperty($name);
		$modifiers = $p->getModifiers();
		$aModStr = Reflection::getModifierNames($modifiers);
		$content = '@'.implode(' @', $aModStr);
		$content .= ' '.gettype($this->$name);
		switch (gettype($this->$name)) {
			case 'array':  $content .= '['.sizeof($this->$name).']'; break;
			case 'object': $content .= ' '.get_class($this->$name); break;
		}
		return $content;
	}

	/**
	 * Prevents infinite loop Sigi->Ruben->Sigi->Ruben
	 * by adding a new Person object to the self::$instances registry
	 * BEFORE calling init().
	 * @param array $where
	 * @param string $orderByLimit
	 * @return array
	 */
	function findInDBsetInstance(array $where, $orderByLimit = '') {
		$data = $this->db->fetchOneSelectQuery($this->table,
			$this->where + $where, $orderByLimit);
		if (is_array($data)) {
			$className = get_called_class();
			$id = $data[$this->idField];
			self::$instances[$className][$id] = $this;   //!!!
			nodebug(__METHOD__, $className, $id,
				sizeof(self::$instances[$className]),
				isset(self::$instances[$className][$id]));
			$this->init($data, true);
			return $data;
		}
	}

	function getParent() {
		$id = $this->data[$this->parentField];
		if ($id) {
			$obj = self::getInstance($id);
		} else {
			$obj = NULL;
		}
		return $obj;
	}

	function getJson() {
		return array(
			'data' => $this->data,
		);
	}

}
