<?php

/**
 * This class is the base class for all classes based on OOD. It contains only things general to all descendants.
 * It contain all the information from the database related to the project as well as methods to manipulate it.
 *
 */
abstract class OODBase {

	/**
	 * @var DBInterface
	 * public to allow unset($o->db); before debugging
	 */
	protected $db;

	/**
	 * database table name for referencing everywhere. MUST BE OVERRIDDEN IN SUBCLASS!
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
				$this->db = $config->getDB();
			}
			//debug(get_class($this), $this->table, gettype2($this->db));
		} else {
			$this->db = isset($GLOBALS['db']) ? $GLOBALS['db'] : NULL;
		}
		//echo get_class($this).'::'.__FUNCTION__, ' ', gettype2($this->db), BR;
		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : array('name' => $val);
		}
		$this->init($id);

		$class = get_class($this);
		if ($this->id && isset(self::$instances[$class][$this->id])) {
			$from = Debug::getCaller();
			//debug('made new existing instance of '.$class.' from '.$from);
		}
	}

	/**
	 * Retrieves data from DB.
	 *
	 * @param int|array|SQLWhere $id
	 * @param bool $fromFindInDB
	 * @throws Exception
	 */
	function init($id, $fromFindInDB = false) {
		TaylorProfiler::start(__METHOD__);
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
		} elseif ($id instanceof SQLWhere) {
			$where = $id->getAsArray();
			$this->findInDB($where);
		} elseif (is_scalar($id)) {
			$this->id = $id;
			if (is_array($this->idField)) {
				// TODO
				throw new InvalidArgumentException(__METHOD__);
			} else {
				$this->findInDB(array($this->idField => $this->id));
				// will do $this->init()
			}
			if (!$this->data) {
				$this->id = NULL;
			}
		} elseif (!is_null($id)) {
			debug($id);
			TaylorProfiler::stop(__METHOD__);
			throw new Exception(get_class($this).'::'.__FUNCTION__);
		}
		TaylorProfiler::stop(__METHOD__);
	}

	function getName() {
		if (is_array($this->titleColumn)) {
			$names = array_reduce($this->titleColumn, function ($initial, $key) {
				return ($initial
					? $initial . ' - '
					: '')
				. ifsetor($this->data[$key]);
			}, '');
			return $names;
		}
		return ifsetor($this->data[$this->titleColumn], $this->id);
	}

	function initByRow(array $row) {
		$this->data = $row;
		$idField = $this->idField;

		if (!is_array($idField)) {
			$parts = trimExplode('.', $idField);
			if (sizeof($parts) == 2) {    //table.id
				$idField = $parts[1];
			}
		}

		if (is_array($idField)) {
			$this->id = array();
			foreach ($idField as $field) {
				$this->id[$field] = $this->data[$field];
			}
		//} else if (igorw\get_in($this->data, array($this->idField))) {   // not ifsetor
		} elseif (isset($this->data[$idField])
			&& $this->data[$idField]) {
			$this->id = $this->data[$idField];
//			assert($this->id);
		} else {
			//debug(gettype2($row), $idField, $this->data);
			throw new InvalidArgumentException(get_class($this).'::'.__METHOD__);
		}
	}

	function log($action, $data = NULL) {
		if (class_exists('Index')) {
			$index = Index::getInstance();
			if ($index) {
				$index->log($action, $data);
			}
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
		TaylorProfiler::start(__METHOD__);
		$this->log(get_called_class() . '::' . __FUNCTION__, $data);
		//$data['ctime'] = new SQLNow();
		$query = $this->db->getInsertQuery($this->table, $data);
		//debug($query);
		$res = $this->db->perform($query);
		$this->lastQuery = $this->db->lastQuery;	// save before commit

		// this needs to be checked first,
		// because SQLite will give some kind of ID
		// even if you provide your own
		if (is_array($this->idField)) {
			$id = $this->db->lastInsertID($res, $this->table);
		} else {
			if (ifsetor($data[$this->idField])) {
				$id = $data[$this->idField];
			} else {
				$id = $this->db->lastInsertID($res, $this->table);
			}
		}

		if ($id) {
			$this->init($id ? $id : $this->id);
		} else {
			//debug($this->lastQuery, $this->db->lastQuery);
			throw new DatabaseException('OODBase for '.$this->table.' no insert id after insert');
		}
		TaylorProfiler::stop(__METHOD__);
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
			TaylorProfiler::start(__METHOD__);
			$action = get_called_class() . '::' . __FUNCTION__ . '(id: ' . json_encode($this->id) . ')';
			$this->log($action, $data);
			$where = array();
			if (is_array($this->idField)) {
				foreach ($this->idField as $field) {
					$where[$field] = $this->data[$field];
				}
			} else {
				$where[$this->idField] = $this->id;
			}

			if (!$this->db) {
				debug_pre_print_backtrace();
				debug(gettypes(get_object_vars($this)));
			}

			$query = $this->db->getUpdateQuery($this->table, $data, $where);
			//debug($query); exit;
			$this->lastQuery = $query;
			$res = $this->db->perform($query);
			//debug($query, $res, $this->db->lastQuery, $this->id);
			$this->lastQuery = $this->db->lastQuery;	// save before commit
			// If the input arrays have the same string keys,
			// then the later value for that key will overwrite the previous one.
			//$this->data = array_merge($this->data, $data);

			// may lead to infinite loop
			//$this->init($this->id);
			// will call init($fromFindInDB = true)
			$this->findInDB(array(
				$this->idField => $this->id,
			));
			TaylorProfiler::stop(__METHOD__);
		} else {
			//$this->db->rollback();
			debug_pre_print_backtrace();
			throw new Exception(__('Updating ['.$this->table.'] is not possible as there is no ID defined. idField: '.$this->idField));
		}
		return $res;
	}

	function delete(array $where = NULL) {
		if (!$where) {
			$where = array($this->idField => $this->id);
		}
		$this->log(get_called_class() . '::' . __FUNCTION__, $where);
		$query = $this->db->getDeleteQuery($this->table, $where);
		$this->lastQuery = $query;
		$res = $this->db->perform($query);
		$this->data = NULL;
		$this->id = NULL;
		return $res;
	}

	/**
	 * Retrieves a record from the DB and calls $this->init()
	 * But it's rarely called directly.
	 * @param array $where
	 * @param string $orderByLimit
	 * @return bool of the found record
	 * @throws Exception
	 */
	function findInDB(array $where, $orderByLimit = '') {
		TaylorProfiler::start($taylorKey = Debug::getBackLog(15, 0, BR, false));
		if (!$this->db) {
			//debug($this->db, $this->db->fetchAssoc('SELECT database()'));
			//debug($this);
		}
		//debug(get_class($this->db));
		$rows = $this->db->fetchOneSelectQuery($this->table,
			$this->where + $where, $orderByLimit);
		//debug($this->where + $where, $this->db->lastQuery);
		$this->lastSelectQuery = $this->db->lastQuery;
//		debug($rows, $this->lastSelectQuery);
		if (is_array($rows)) {
			$data = $rows;
			$this->initByRow($data);
		} else {
			$data = array();
			if ($this->forceInit) {
				$this->init($data, true);
			}
		}
		TaylorProfiler::stop($taylorKey);
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
		try {
			return $this->getName() . '';
		} catch (Exception $e) {
			debug_pre_print_backtrace();
			echo $e->getFile().'#'.$e->getLine(), BR;
			die($e->getMessage());
		}
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
		TaylorProfiler::start(__METHOD__);
		$this->db->transaction();
		if ($where) {
			$this->findInDB($where);
		}
//		debug($this->id, $this->data); exit();
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
			if ($this->id) {
				$op = 'INSERT ' . $this->id;
			} else {
				debug($this->lastQuery);
				$op = $this->db->lastQuery;	// for debug
			}
			//debug($this->id, $this->data, $op, $this->db->lastQuery);
		}
		$this->db->commit();
		TaylorProfiler::stop(__METHOD__);
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
				if (ifsetor($desc['showSingle']) !== false) {
					$assoc[$key] = array(
						0 => $desc['name'],
						'' => ifsetor($this->data[$key]),
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

	/**
	 * Only works when $this->thes is defined or provided
	 * @param array $thes
	 * @param null  $title
	 * @return ShowAssoc
	 */
	function showAssoc(array $thes = array(
			'id' => 'ID',
			'name' => 'Name'
		), $title = NULL) {
		$ss = new ShowAssoc($this->data);
		$ss->setThes($thes);
		$ss->setTitle($title ?: get_class($this));
		return $ss;
	}

	/**
	 * Caching
	 * @param $id
	 * @return mixed
	 */
	static function getInstanceCached($id) {
		if (true) {
			$file = 'cache/' . URL::friendlyURL(__METHOD__) . '-' . $id . '.serial';
			if (file_exists($file) && filemtime($file) > (time() - 100)) {
				$size = filesize($file);
				if ($size < 1024*4) {
					$content = file_get_contents($file);
					$graph = unserialize($content); // faster?
				} else {
					$graph = self::getInstanceByID($id);
				}
			} else {
				$graph = self::getInstanceByID($id);
				file_put_contents($file, serialize($graph));
			}
		} else {
			$graph = self::getInstanceByID($id);
		}
		return $graph;
	}

	/**
	 * @param $id
	 * @return self|$this|static
	 */
	static function getInstance($id) {
		return static::getInstanceByID($id);
	}

	/**
	 * // TODO: initialization by array should search in $instances as well
	 * @param $id|array int
	 * @return $this
	 */
	public static function getInstanceByID($id) {
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
			$inst = isset(self::$instances[$static][$id])
				? self::$instances[$static][$id]
				: NULL;
			if (!$inst) {
				//debug('new ', get_called_class(), $id, array_keys(self::$instances));
				/** @var OODBase $inst */
				// don't put anything else here
				$inst = new $static();
				// BEFORE init() to avoid loop
				self::storeInstance($inst, $id);
				// separate call to avoid infinite loop in ORS
				$inst->init($id);
			}
		} elseif (is_array($id)) {
			/** @var OODBase $inst */
			$inst = new $static();
			$intID = $id[$inst->idField];
			//debug($static, $intID, $id);
			$inst = isset(self::$instances[$static][$intID])
				? self::$instances[$static][$intID]
				: $inst;
			if (!$inst->id) {
				$inst->init($id);	// array
				self::storeInstance($inst, $intID);	// int id
			}
		} elseif ($id) {
			//debug($static, $id);
			/** @var OODBase $inst */
			$inst = new $static();
			$inst->init($id);
			self::storeInstance($inst, $inst->id);
		} else {
			throw new InvalidArgumentException($static.'->'.__METHOD__);
		}
		return $inst;
	}

	static function storeInstance($inst, $newID = NULL) {
		$static = get_called_class();
		$id = $inst->id ?: $newID;
		if ($id) {
			self::$instances[$static][$id] = $inst;
		}
	}

	static function clearInstances() {
		self::$instances[get_called_class()] = array();
		gc_collect_cycles();
	}

	static function clearAllInstances() {
		self::$instances = array();
		gc_collect_cycles();
	}

	function getObjectInfo() {
		return get_class($this).': "'.$this->getName().'" (id:'.$this->id.' '.$this->getHash().')';
	}

	function getHash($length = null) {
		$hash = spl_object_hash($this);
		if ($length) {
			$hash = sha1($hash);
			$hash = substr($hash, 0, $length);
		}
		return '#'.$hash;
	}

	/**
	 * Is cached in instances
	 * @param string $name
	 * @param null   $field
	 * @return self|static
	 */
	static function getInstanceByName($name, $field = NULL) {
		$self = get_called_class();
		//debug(__METHOD__, $self, $name, count(self::$instances[$self]));

		$c = NULL;
		// first search instances
		if (is_array(ifsetor(self::$instances[$self]))) {
			foreach (self::$instances[$self] as $inst) {
				if ($inst instanceof OODBase) {
					$field = $field ? $field : $inst->titleColumn;
					if (ifsetor($inst->data[$field]) == $name) {
						$c = $inst;
						break;
					}
				}
			}
		}

		if (!$c) {
			$c = new $self();
			/** @var $c OODBase */
			$field = $field ? $field : $c->titleColumn;
			if (is_string($field)) {
				$c->findInDBsetInstance(array(
					$field => $name,
				));
			} elseif ($field instanceof AsIs) {
				$c->findInDBsetInstance([
					$field
				]);
			}
		}
		return $c;
	}

	/**
	 * Used by bijou.
	 * @param $insert
	 * @param $class
	 * @return static
	 * @throws Exception
	 */
	static function createRecord(array $insert, $class = NULL) {
		TaylorProfiler::start(__METHOD__);
		//$insert = $this->db->getDefaultInsertFields() + $insert; // no overwriting?
		//debug($insert);
		$class = $class ?: get_called_class();

		/** @var dbLayerBase $db */
		$db = Config::getInstance()->getDB();
		$query = $db->getInsertQuery(constant($class.'::table'), $insert);
		//t3lib_div::debug($query);
		$res = $db->perform($query);
		if ($res) {
			$id = $db->lastInsertID($res, constant($class.'::table'));
			//t3lib_div::debug($id);

			if ($class) {
				$object = new $class($id);
			} else {
				$object = $id;
			}
		} else {
			$object = NULL;
		}
		TaylorProfiler::stop(__METHOD__);
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
			if ($id) {
				self::$instances[$className][$id] = $this;   //!!!
			}
			nodebug(__METHOD__, $className, $id,
				sizeof(self::$instances[$className]),
				isset(self::$instances[$className][$id]));
			$this->init($data, true);
		}
		return $data;
	}

	/**
	 * @return OODBase|LazyPrefs
	 */
	function getParent() {
		$id = ifsetor($this->data[$this->parentField]);
		if ($id) {
			$obj = self::getInstance($id);
		} else {
			$obj = NULL;
		}
		return $obj;
	}

	/**
	 * Override if collection name is different
	 * @return Collection
	 */
	function getChildren() {
		$collection = get_class($this).'Collection';
		return new $collection($this->id);
	}

	function getJson() {
		return array(
			'class' => get_class($this),
			'data' => $this->data,
		);
	}

	function getSingleLink() {
		return get_class($this) . '/' . $this->id;
	}

	function getNameLink() {
		return new HTMLTag('a', array(
			'href' => $this->getSingleLink(),
		), $this->getName());
	}

	/**
	 * Give it array of IDs and it will give you an array of objects
	 * @param array $ids
	 * @return ArrayPlus
	 */
	public static function makeInstances(array $ids) {
		foreach ($ids as &$id) {
			$id = static::getInstance($id);
		}
		return new ArrayPlus($ids);
	}

	function ensure(array $where) {
		$this->findInDB($where);
		if (!$this->id) {
			$this->insert($where);
		}
	}

	public static function getCacheStats() {
		$stats = array();
		foreach (self::$instances as $class => $list) {
			$stats[$class] = sizeof($list);
		}
		return $stats;
	}

	public static function getCacheStatsTable() {
		$stats = OODBase::getCacheStats();
		$stats = ArrayPlus::create($stats)
			->makeTable('count')
			->insertKeyAsColumn('class')
		;
		$max = $stats->column('count')->max();
		if ($max != 0) {
			//debug((array)$stats); exit();
			$stats->addColumn('bar', function ($row, $i) use ($max) {
				return ProgressBar::getImage($row['count'] / $max * 100);
			});
		}
		$stats = $stats->getData();
		$content[] = new slTable($stats, 'class="table"', array(
			'class' => 'Class',
			'count' => 'Count',
			'bar' => array(
				'no_hsc' => true,
			),
		));
		return $content;
	}

	/**
	 * It was called getCollection in the past
	 * @param array $where
	 * @param null  $orderBy
	 * @return mixed
	 */
	public function queryInstances(array $where, $orderBy = NULL) {
		$data = $this->db->fetchAllSelectQuery($this->table, $where, $orderBy);
		foreach ($data as &$row) {
			$row = static::getInstance($row);
		}
		return $data;
	}

	public function getCollection(array $where = [], $orderBy = NULL) {
		$collection = Collection::createForTable($this->table, $where, $orderBy);
		$collection->idField = $this->idField;
		$static = get_called_class();
		$collection->itemClassName = $static;
		return $collection;
	}

	/**
	 * @param $id
	 * @return self
	 */
	static function tryGetInstance($id) {
		try {
			$obj = self::getInstance($id);
		} catch (InvalidArgumentException $e) {
			$class = get_called_class();
			$obj = new $class();
		}
		return $obj;
	}

	/**
	 * http://stackoverflow.com/questions/8707235/how-to-create-new-property-dynamically
	 * @param $name
	 * @param $value
	 */
	public function createProperty($name, $value = NULL) {
		if (isset($this->{$name}) && $value === NULL) {
			//$this->{$name} = $this->{$name};
		} else {
			$this->{$name} = $value;
		}
	}

	function save($where = NULL) {
		if ($this->id) {
			$res = $this->update($this->data);
		} else {
			// this 99.9% insert
			$res = $this->insertUpdate($this->data, $where ?: $this->data, $this->data, $this->data);
		}
		return $res;
	}

	function get($name) {
		return ifsetor($this->data[$name]);
	}

}
