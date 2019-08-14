<?php

/**
 * This class is the base class for all classes based on OOD. It contains only things general to all descendants.
 * It contain all the information from the database related to the project as well as methods to manipulate it.
 *
 */

abstract class OODBase
{
	/**
	 * @var MySQL|dbLayer|dbLayerDB|dbLayerPDO
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
* @var self
	 * array[get_called_class()][$id]

	 */
	static $instances = array();

	/**
	 * @var string - saved after insertUpdate
	 */
	public $lastQuery;

	/**
	 * Constructor should be given the ID of the existing record in DB.
	 * If you want to use methods without knowing the ID, the call them statically like this Version::insertRecord();
	 *
	 * @param integer|array|SQLWhere $id - can be ID in the database or the whole records
	 * as associative array
	 * @return OODBase
	 */
	function __construct($id = NULL)
	{
		if (class_exists('Config')) {
			$config = Config::getInstance();
			$this->table = $config->prefixTable($this->table);
			if (!$this->db) {
				$this->db = $config->db;
			}
		} else {
			$this->db = $GLOBALS['db'];
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
	function init($id, $fromFindInDB = false)
	{
		if (isset($GLOBALS['prof'])) $GLOBALS['prof']->startTimer(__METHOD__);
		if (is_array($id)) {
			if (is_scalar($this->idField) || $fromFindInDB) {
				$this->initByRow($id);
			} else {
				$this->id = $id;
				//debug($id, $fromFindInDB, $this->id);
				$this->findInDB($this->id);    // will call init()
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
			if (isset($GLOBALS['prof'])) $GLOBALS['prof']->stopTimer(__METHOD__);
			throw new Exception(__METHOD__);
		}
		if (isset($GLOBALS['prof'])) {
			$GLOBALS['prof']->stopTimer(__METHOD__);
		}
	}

	function getName()
	{
		return $this->data[$this->titleColumn] ? $this->data[$this->titleColumn] : $this->id;
	}

	function initByRow(array $row)
	{
		$this->data = $row;
		if (is_array($this->idField)) {
			$this->id = array();
			foreach ($this->idField as $field) {
				$this->id[$field] = $this->data[$field];
			}
		} else {
			if (!isset($this->data[$this->idField])) {
				//throw new InvalidArgumentException(__METHOD__.' has no value in '.$this->idField);
			}
			$this->id = ifsetor($this->data[$this->idField]);
		}
	}

	/**
	 * Returns $this
	 *
	 * @param array $data
	 * @return OODBase
	 * @throws Exception
	 */
	function insert(array $data)
	{
		if (isset($GLOBALS['profiler'])) {
			$GLOBALS['profiler']->startTimer(__METHOD__);
		}
		//$data['ctime'] = new AsIs('NOW()');
		$query = $this->db->getInsertQuery($this->table, $data);
		//debug($query);
		$res = $this->db->perform($query);
		$this->lastQuery = $this->db->lastQuery;    // save before commit
		$id = $this->db->lastInsertID($res, $this->table);
		if ($id) {
			$this->init($id ? $id : $this->id);
		} else {
			throw new Exception('OODBase for ' . $this->table . ' no insert id after insert');
		}
		if (isset($GLOBALS['profiler'])) {
			$GLOBALS['profiler']->stopTimer(__METHOD__);
		}
		return $this;
	}

	/**
	 * Updates current record ($this->id)
	 *
	 * @param array $data
	 * @return resource result from the runUpdateQuery
	 * @throws Exception
	 */
	function update(array $data)
	{
		if ($this->id) {
			if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
			$where = array();
			if (is_array($this->idField)) {
				foreach ($this->idField as $field) {
					$where[$field] = $this->data[$field];
				}
			} else {
				$where[$this->idField] = $this->id;
			}
			$query = $this->db->getUpdateQuery($this->table, $data, $where);
			$res = $this->db->perform($query);
			//debug($query, $res, $this->db->lastQuery, $this->id);
			$this->lastQuery = $this->db->lastQuery;    // save before commit
			// If the input arrays have the same string keys,
			// then the later value for that key will overwrite the previous one.
			//$this->data = array_merge($this->data, $data);
			$this->init($this->id);
			if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		} else {
			//$this->db->rollback();
			debug_pre_print_backtrace();
			throw new Exception(__('Updating ' . $this->table . ' is not possible as there is no ID defined.'));
		}
		return $res;
	}

	function delete(array $where = NULL)
	{
		if (!$where) {
			$where = array($this->idField => $this->id);
		}
		$query = $this->db->getDeleteQuery($this->table, $where);
		//debug($query);
		return $this->db->perform($query);
	}

	/**
	 * Retrieves a record from the DB and calls $this->init()
	 * @param array $where
	 * @param string $orderByLimit
	 * @return boolean (id) of the found record
	 * @throws Exception
	 */
	function findInDB(array $where, $orderByLimit = '')
	{
		if (isset($GLOBALS['profiler'])) {
			$GLOBALS['profiler']->startTimer(__METHOD__ . ' (' . $this->table . ')');
		}
		if (!$this->db) {
			debug_print_backtrace();
			throw new RuntimeException(__METHOD__ . ' has no DB');
		}
		$rows = $this->db->fetchSelectQuery($this->table, $this->where + $where, $orderByLimit);
		if (is_array($rows)) {
			if (is_array(first($rows))) {
				$data = first($rows);
			} else {
				$data = $rows;
			}
		} else {
			$data = array();
		}
		$this->init($data, true);
		if (isset($GLOBALS['profiler'])) {
			$GLOBALS['profiler']->stopTimer(__METHOD__ . ' (' . $this->table . ')');
		}
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
	static function findInstance(array $where, $static = NULL)
	{
		if (!$static) {
			if (function_exists('get_called_class')) {
				$static = get_called_class();
			} else {
				throw new Exception('__METHOD__ requires object specifier until PHP 5.3.');
			}
		}
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
	function findInDBbySQLWhere(SQLWhere $where, $orderby = '')
	{
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

	function __toString()
	{
		//return new slTable(array(array_keys($this->data), array_values($this->data))).'';
		return $this->getName() . '';
	}

	/**
	 * Depends on $this->id and $this->data will be saved into DB
	 * @return string
	 */
	function insertOrUpdate()
	{
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
	)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->db->transaction();
		if ($where) {
			$this->findInDB($where);
		}
		//debug($this->id, $this->data); exit();
		if ($this->id) { // found
			$left = array_intersect_key($this->data, $fields);        // keys need to have same capitalization
			$right = array_intersect_key($fields, $this->data);
			//debug($left, $right); exit();
			if ($left == $right) {
				$op = 'SKIP';
			} else {
				$this->update($fields + $update);
				$op = 'UPDATE ' . $this->id;
			}
		} else {
			//debug($this->id, $this->data);
			$this->insert($fields + $where + $insert);
			//debug($where, $this->id, $this->data, $fields + $where + $insert, $this->lastQuery);
			$op = 'INSERT ' . $this->id;
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
	function renderAssoc(array $assoc = NULL, $recursive = false, $skipEmpty = true)
	{
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
			$s = new slTable($assoc, '', array(
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

	function showAssoc(array $thes = array('id' => 'ID', 'name' => 'Name'))
	{
		if (isset($GLOBALS['prof'])) $GLOBALS['prof']->startTimer(__METHOD__);
		$content = '<div class="showAssoc">
		<h3>' . get_class($this) . ':</h3>';
		foreach ($thes as $key => $name) {
			$name = is_array($name) ? $name['name'] : $name;
			$val = $this->data[$key];
			$content .= '<div class="prefix10">' . $name . ':</div>';
			$content .= $val . '<br clear="all" />';
		}
		$content .= '</div>';
		if (isset($GLOBALS['prof'])) $GLOBALS['prof']->stopTimer(__METHOD__);
		return $content;
	}

	/**
	 * // TODO: initialization by array should search in $instances as well
	 * @param $id int
	 * @return self|$this|static
	 * @return static
	 */
	public static function getInstance($id)
	{
		$static = get_called_class();
		//debug($static, sizeof(self::$instances[$static]));
		if (is_scalar($id)) {
			$inst = &self::$instances[$static][$id];
			if (!$inst) {
				//debug('new ', get_called_class(), $id, array_keys(self::$instances));
				if (false) {
					$inst = new $static($id);    // VersionInfo needs it like this
				} else {
					// NewRequest needs it like this
					$inst = new $static();        // don't put anything else here
					//die(__METHOD__.'#'.__LINE__);
					$inst->init($id);            // separate call to avoid infinite loop in ORS
					//die(__METHOD__.'#'.__LINE__);
				}
			}
		} else {
			$inst = new $static($id);
			if ($inst->id) {
				self::$instances[$static][$inst->id] = $inst;
			}
		}
		return $inst;
	}

	function clearInstances()
	{
		self::$instances[get_class($this)] = array();
		gc_collect_cycles();
	}

	function getObjectInfo()
	{
		return get_class($this) . ': "' . $this->getName() . '" (id:' . $this->id . ' #' . spl_object_hash($this) . ')';
	}

	/**
	 * Is cached in instances
	 * @param string $name
	 * @param null $field
	 * @return static
	 */
	static function getInstanceByName($name, $field = NULL)
	{
		$self = get_called_class();
		//debug($self, $name, count(self::$instances[$self]));

		// first search instances
		$c = null;
		if (is_array(ifsetor(self::$instances[$self]))) {
			foreach (self::$instances[$self] as $inst) {
				$field = $field ?: $inst->titleColumn;
				if ($inst->data[$field] == $name) {
					$c = $inst;
					break;
				}
			}
		}

		if (!$c) {
			$c = new $self();
			/** @var $c OODBase */
			$field = $field ?: $c->titleColumn;
			$c->findInDB(array(
				$field => $name,
			));

			// store back so it can be found
			if ($c) {
				self::$instances[$self][$c->id] = $c;
			}
		}
		return $c;
	}

	//abstract function createRecord($data);
	static function createRecord($insert, $class)
	{
		if (isset($GLOBALS['prof'])) $GLOBALS['prof']->startTimer(__METHOD__);
		//$insert = $this->db->getDefaultInsertFields() + $insert; // no overwriting?
		//debug($insert);

		$query = $GLOBALS['db']->getInsertQuery(constant($class . '::table'), $insert);
		//t3lib_div::debug($query);
		$res = $GLOBALS['db']->perform($query);
		if ($res) {
			$id = $GLOBALS['db']->getLastInsertID($res, constant($class . '::table'));
			//t3lib_div::debug($id);

			if ($class) {
				$object = new $class($id);
			} else {
				$object = $id;
			}
		} else {
			$object = NULL;
		}
		if (isset($GLOBALS['prof'])) $GLOBALS['prof']->stopTimer(__METHOD__);
		return $object;
	}

	function getURL(array $params)
	{
		$c = Index::getInstance()->controller;
		return $c->getURL($params);
	}

	function getVarType($name)
	{
		$r = new ReflectionClass($this);
		$p = $r->getProperty($name);
		$modifiers = $p->getModifiers();
		$aModStr = Reflection::getModifierNames($modifiers);
		$content = '@' . implode(' @', $aModStr);
		$content .= ' ' . gettype($this->$name);
		switch (gettype($this->$name)) {
			case 'array':
				$content .= '[' . sizeof($this->$name) . ']';
				break;
			case 'object':
				$content .= ' ' . get_class($this->$name);
				break;
		}
		return $content;
	}

	public function setDB($db)
	{
		$this->db = $db;
	}

	public function getID()
	{
		return $this->id;
	}

	public function get($name)
	{
		return ifsetor($this->data[$name]);
	}

}
