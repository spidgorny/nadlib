<?php

use Psr\Log\LoggerInterface;
use spidgorny\nadlib\HTTP\URL;

require_once __DIR__ . '/CachedGetInstance.php';

/**
 * This class is the base class for all classes based on OOD. It contains only things general to all descendants.
 * It contains all the information from the database related to the project as well as methods to manipulate it.
 *
 */
abstract class OODBase
{

	use CachedGetInstance;
	use MagicDataProps;

	/**
	 * @var DBLayerBase|DBInterface|SQLBuilder|DBLayerPDO|null
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
	public $id = null;

	/**
	 * @var array data from DB
	 */
	public $data = [];

	/**
	 * @var array of visible fields which serves as a definition for a corresponding Collection
	 * and maybe to HTMLFormTable as well
	 */
	public $thes = [];

	/**
	 * to allow extra filtering
	 * @var array
	 */
	protected $where = [];

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
	 * @var bool findInDB() will call init
	 */
	public $forceInit;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Constructor should be given the ID of the existing record in DB.
	 * If you want to use methods without knowing the ID, the call them statically like this Version::insertRecord();
	 *
	 * @param integer|array|SQLWhere|DBInterface $id - can be ID in the database or the whole records
	 * as associative array
	 *
	 * @throws Exception
	 */
	public function __construct($id = null)
	{
//		debug(get_called_class(), __FUNCTION__, $id);
		$this->guessDB($id);
		//echo get_class($this).'::'.__FUNCTION__, ' ', gettype2($this->db), BR;
		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : ['name' => $val];
		}
		if (!($id instanceof DBInterface)) {
			$this->init($id);
		}

//		$class = get_class($this);
//		if ($this->id && isset(self::$instances[$class][$this->id])) {
//			$from = Debug::getCaller();
		//debug('made new existing instance of '.$class.' from '.$from);
//		}
	}

	public function guessDB($id)
	{
		if ($id instanceof DBInterface) {
			$this->db = $id;
		} else {
			if (class_exists('Config')) {
				$config = Config::getInstance();
				$this->table = $config->prefixTable($this->table);
				if (!$this->db) {
					$this->db = $config->getDB();
				}
				//debug(get_class($this), $this->table, gettype2($this->db));
			} else {
				$this->db = $GLOBALS['db'] ?? null;
			}
		}
	}

	/**
	 * Retrieves data from DB.
	 *
	 * @param int|array|SQLWhere $id
	 * @throws Exception
	 */
	public function init($id)
	{
		TaylorProfiler::start(__METHOD__);
		if (is_array($id)) {
			$this->initByRow($id);
		} elseif ($id instanceof SQLWhere) {
			$where = $id->getAsArray();
			$this->findInDB($where);
		} elseif (is_scalar($id)) {
//			debug('set id', $id);
			$this->id = $id;
			if (is_array($this->idField)) {
				// TODO
				throw new InvalidArgumentException(__METHOD__ . '->idField is an array. Init failed.');
			} else {
				// will do $this->init()
				$this->findByID($this->id);
			}
//			debug('data set', $this->data);
			if (!$this->data) {
				$this->id = null;
			}
		} elseif (!is_null($id)) {
			debug($id);
			TaylorProfiler::stop(__METHOD__);
			throw new Exception(get_class($this) . '::' . __FUNCTION__);
		}
		TaylorProfiler::stop(__METHOD__);
	}

	public function getName()
	{
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

	public function initByRow(array $row)
	{
		// to prevent $this->>update() to loose all fields calculated
		$this->data = array_merge($this->data, $row);
		$idField = $this->idField;

		if (!is_array($idField)) {
			$parts = trimExplode('.', $idField);
			if (sizeof($parts) == 2) {    //table.id
				$idField = $parts[1];
			}
		}

		if (is_array($idField)) {
			$this->id = [];
			foreach ($idField as $field) {
				$this->id[$field] = $this->data[$field];
			}
			//} else if (igorw\get_in($this->data, array($this->idField))) {   // not ifsetor
		} elseif (isset($this->data[$idField])
			&& $this->data[$idField]) {
			$this->id = $this->data[$idField];
//			assert($this->id);
		} else {
			debug([
				'class' => static::class,
				'typ' => typ($row) . '',
				'idField' => $this->idField,
				'id' => $idField,
				'data' => $this->data]);
			throw new InvalidArgumentException(get_class($this) . '::' . __METHOD__);
		}
	}

	public function log($action, $data = null)
	{
		if ($this->logger) {
			$this->logger->info($action, $data);
		}
	}

	/**
	 * Returns $this
	 *
	 * @param array $data
	 * @return OODBase
	 * @throws Exception
	 */
	public function insert(array $data)
	{
		TaylorProfiler::start(__METHOD__);
		$this->log(static::class . '::' . __FUNCTION__, $data);
		//$data['ctime'] = new SQLNow();
		$query = $this->db->getInsertQuery($this->table, $data);
		//debug($query);
		// for DBPlacebo to return the same data back
		$res = $this->db->runInsertQuery($this->table, $data);
		$this->lastQuery = $this->db->getLastQuery();    // save before commit

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
			$this->init($id ?: $this->id);
		} else {
			//debug($this->lastQuery, $this->db->lastQuery);
			$errorMessage = 'OODBase for ' . $this->table . ' no insert id after insert. ';
			$errorCode = null;
			if ($this->db instanceof DBLayerPDO) {
				$errorMessage .= $this->db->getConnection()->errorInfo();
				$errorCode = $this->db->getConnection()->errorCode();
			}
			$e = new DatabaseException($errorMessage, $errorCode);
			$e->setQuery($query);
			throw $e;
		}
		TaylorProfiler::stop(__METHOD__);
		return $this;
	}

	/**
	 * Updates current record ($this->id)
	 *
	 * @param array $data
	 * @return PDOStatement|false result from the runUpdateQuery
	 * @throws Exception
	 */
	public function update(array $data)
	{
		if (!$this->id) {
			//$this->db->rollback();
			debug_pre_print_backtrace();
			$msg = __(
				'Updating [$1] is not possible as there is no ID defined. idField: $2',
				$this->table,
				$this->idField
			);
			throw new DatabaseException($msg);
		}

		TaylorProfiler::start(__METHOD__);
		$action = static::class . '::' . __FUNCTION__ . '(id: ' . json_encode($this->id, JSON_THROW_ON_ERROR) . ')';
		$this->log($action, $data);
		$where = [];
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
		//debug($query);
		//echo $query, BR;
		$this->lastQuery = $query;
		$res = $this->db->perform($query);
		//debug($query, $res, $this->db->lastQuery, $this->id);
		$this->lastQuery = $this->db->lastQuery;    // save before commit
		// If the input arrays have the same string keys,
		// then the later value for that key will overwrite the previous one.
		//$this->data = array_merge($this->data, $data);

		// may lead to infinite loop
		//$this->init($this->id);
		// will call init($fromFindInDB = true)
		if (is_array($this->idField)) {
			if (is_array($this->id)) {
				$this->findInDB($this->id);
			} else {
				debug_pre_print_backtrace();
				throw new RuntimeException(__METHOD__ . ':' . __LINE__);
			}
		} else {
			$this->findInDB([
				$this->idField => $this->id,
			]);
		}
		TaylorProfiler::stop(__METHOD__);
		return $res;
	}

	/**
	 * @param array|NULL $where
	 * @return null
	 * @throws MustBeStringException
	 * @throws DatabaseException
	 */
	public function delete(array $where = null)
	{
		if (!$where) {
			if ($this->id) {
				$where = [$this->idField => $this->id];
			} else {
				return null;
			}
		}
		$this->log(get_called_class() . '::' . __FUNCTION__, $where);
		$query = $this->db->getDeleteQuery($this->table, $where);
		$this->lastQuery = $query;
		$res = $this->db->perform($query);
		$this->data = null;
		$this->id = null;
		return $res;
	}

	/**
	 * Retrieves a record from the DB and calls $this->init()
	 * But it's rarely called directly.
	 * @param array $where
	 * @param string $orderByLimit
	 *
	 * @param null $selectPlus
	 * @return array of the found record
	 * @throws Exception
	 */
	public function findInDB(array $where, $orderByLimit = '', $selectPlus = null)
	{
		$taylorKey = Debug::getBackLog(15, 0, BR, false);
		if (!$this->db) {
			//debug($this->db, $this->db->fetchAssoc('SELECT database()'));
			//debug($this);
		}
		//debug(get_class($this->db));
		$rows = $this->db->fetchOneSelectQuery(
			$this->table,
			$this->where + $where,
			$orderByLimit,
			$selectPlus
		);
		//debug($this->where + $where, $this->db->lastQuery);
		$this->lastSelectQuery = $this->db->lastQuery;
		$this->log(__METHOD__, $this->lastSelectQuery . '');
//		debug($rows, $this->lastSelectQuery);
		if (is_array($rows) && $rows) {
			$data = $rows;
			$this->initByRow($data);
		} else {
			$data = [];
			if ($this->forceInit) {
				$this->init($data);
			}
		}
		TaylorProfiler::stop($taylorKey);
		return $data;
	}

	/**
	 * @param $id
	 * @return array
	 * @throws Exception
	 */
	public function findByID($id)
	{
		return $this->findInDB([
			$this->idField => $id
		]);
	}

	/**
	 * Still searches in DB with findInDB, but makes a new object for you
	 *
	 * @param array $where
	 * @param null $static
	 * @return mixed
	 * @throws Exception
	 */
	public static function findInstance(array $where, $static = null)
	{
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
	 * @throws Exception
	 */
	public function findInDBbySQLWhere(SQLWhere $where, $orderby = '')
	{
		$rows = $this->db->fetchSelectQuerySW($this->table, $where, $orderby);
		//debug($rows);
		if ($rows) {
			$this->data = $rows[0];
		} else {
			$this->data = [];
		}
		$this->init($this->data); // array, otherwise infinite loop
		return $this->id;
	}

	public function __toString()
	{
		try {
			return $this->getName() . '';
		} catch (Exception $e) {
			debug_pre_print_backtrace();
			echo $e->getFile() . '#' . $e->getLine(), BR;
			die($e->getMessage());
		}
	}

	/**
	 * Depends on $this->id and $this->data will be saved into DB
	 * @return string
	 * @throws Exception
	 */
	public function insertOrUpdate()
	{
		if ($this->id) {
			$this->update($this->data);
			$action = 'UPD';
		} else {
			$this->insert($this->data);
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
	 * @throws Exception
	 */
	public function insertUpdate(
		array $fields,
		array $where = [],
		array $insert = [],
		array $update = []
	)
	{
		TaylorProfiler::start(__METHOD__);
		//echo get_class($this), '::', __FUNCTION__, ' begin', BR;
		$this->db->transaction();
		if ($where) {
			$this->findInDB($where);
		}
		//debug($this->id, $this->data);
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
			if ($this->id) {
				$op = 'INSERT ' . $this->id;
			} else {
				debug($this->lastQuery);
				$op = $this->db->lastQuery;    // for debug
			}
//			debug($this->id, $this->data, $op, $this->db->lastQuery);
//			exit();
		}
		$this->db->commit();
		//echo get_class($this), '::', __FUNCTION__, ' commit', BR;
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
	public function renderAssoc(array $assoc = null, $recursive = false, $skipEmpty = true)
	{
		$assoc = $assoc ?: $this->data;
		//debug($this->thes);
		if ($this->thes) {
			$assoc = [];
			foreach ($this->thes as $key => $desc) {
				$desc = is_array($desc) ? $desc : ['name' => $desc];
				if (ifsetor($desc['showSingle']) !== false) {
					$assoc[$key] = [
						0 => $desc['name'],
						'' => ifsetor($this->data[$key]),
						'.' => $desc,
					];
				}
			}
			$s = new slTable($assoc, 'class="table table-striped"', [
				0 => '',
				'' => ['no_hsc' => true]
			]);
		} else {
			foreach ($assoc as $key => &$val) {
				if (!$val && $skipEmpty) {
					unset($assoc[$key]);
				} elseif (is_array($val) && $recursive) {
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
	 * @param null $title
	 * @return ShowAssoc
	 */
	public function showAssoc(
		array $thes = [
			'id' => 'ID',
			'name' => 'Name'
		],    $title = null
	)
	{
		$ss = new ShowAssoc($this->data);
		$ss->setThes($thes);
		$ss->setTitle($title ?: get_class($this));
		return $ss;
	}

	/**
	 * Caching
	 * @param $id
	 * @return mixed
	 * @throws Exception
	 */
	public static function getInstanceCached($id)
	{
		if (true) {
			$file = 'cache/' . URL::friendlyURL(__METHOD__) . '-' . $id . '.serial';
			if (file_exists($file) && filemtime($file) > (time() - 100)) {
				$size = filesize($file);
				if ($size < 1024 * 4) {
					$content = file_get_contents($file);
					/** @noinspection UnserializeExploitsInspection */
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

	public function getObjectInfo()
	{
		return get_class($this) . ': "' . $this->getName() . '" (id:' . $this->id . ' ' . $this->getHash() . ')';
	}

	public function getHash($length = null)
	{
		$hash = spl_object_hash($this);
		if ($length) {
			$hash = sha1($hash);
			$hash = substr($hash, 0, $length);
		}
		return '#' . $hash;
	}

	/**
	 * Used by bijou.
	 * @param array $insert
	 * @param $class
	 * @return int|null
	 * @throws Exception
	 */
	public static function createRecord(array $insert, $class = null)
	{
		TaylorProfiler::start(__METHOD__);
		//$insert = $this->db->getDefaultInsertFields() + $insert; // no overwriting?
		//debug($insert);
		$class = $class ?: get_called_class();

		/** @var DBLayerBase $db */
		$db = Config::getInstance()->getDB();
		$query = $db->getInsertQuery(constant($class . '::table'), $insert);
		//t3lib_div::debug($query);
		$res = $db->perform($query);
		if ($res) {
			$id = $db->lastInsertID($res, constant($class . '::table'));
			//t3lib_div::debug($id);

			if ($class) {
				$object = new $class($id);
			} else {
				$object = $id;
			}
		} else {
			$object = null;
		}
		TaylorProfiler::stop(__METHOD__);
		return $object;
	}

	public function getURL(array $params)
	{
		$c = Index::getInstance()->controller;
		return $c->getURL($params);
	}

	/**
	 * @param $name
	 * @return string
	 * @throws ReflectionException
	 */
	public function getVarType($name)
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

	/**
	 * Prevents infinite loop Sigi->Ruben->Sigi->Ruben
	 * by adding a new Person object to the self::$instances registry
	 * BEFORE calling init().
	 * @param array $where
	 * @param string $orderByLimit
	 * @return array
	 * @throws Exception
	 */
	public function findInDBsetInstance(array $where, $orderByLimit = '')
	{
//		llog(__METHOD__, $this->where);
//		llog(__METHOD__, $this->where.'');
//		llog(__METHOD__, $where);
//		llog(__METHOD__, $where.'');
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
			$this->init($data);
		}
		return $data;
	}

	/**
	 * @return OODBase
	 * @throws Exception
	 */
	public function getParent()
	{
		$id = ifsetor($this->data[$this->parentField]);
		if ($id) {
			$obj = self::getInstance($id);
		} else {
			$obj = null;
		}
		return $obj;
	}

	/**
	 * Override if collection name is different
	 * @param array $where
	 * @return DatabaseResultIteratorAssoc|array
	 */
	public function getChildren(array $where = [])
	{
		$collection = get_class($this) . 'Collection';
		if (class_exists($collection)) {
			return new $collection($this->id, $where);
		}

		$iterator = new DatabaseResultIteratorAssoc($this->db, $this->idField);
		$iterator->perform($this->db->getSelectQuery($this->table, $where));
		return $iterator;
	}

	public function getJson()
	{
		return [
			'class' => get_class($this),
			'data' => $this->data,
		];
	}

	public function getSingleLink()
	{
		return get_class($this) . '/' . $this->id;
	}

	public function getNameLink()
	{
		return new HTMLTag('a', [
			'href' => $this->getSingleLink(),
		], $this->getName());
	}

	/**
	 * Give it array of IDs and it will give you an array of objects
	 * @param array $ids
	 * @return ArrayPlus
	 * @throws Exception
	 */
	public static function makeInstances(array $ids)
	{
		foreach ($ids as &$id) {
			$id = static::getInstance($id);
		}
		return new ArrayPlus($ids);
	}

	public static function makeInstance(array $row)
	{
		return new static($row);
	}

	/**
	 * @param array $where
	 * @throws Exception
	 */
	public function ensure(array $where)
	{
		$this->findInDB($where);
		if (!$this->id) {
			$this->insert($where);
		}
	}

	/**
	 * It was called getCollection in the past
	 * @param array $where
	 * @param null $orderBy
	 * @return mixed
	 * @throws Exception
	 */
	public function queryInstances(array $where, $orderBy = null)
	{
		$data = $this->db->fetchAllSelectQuery($this->table, $where, $orderBy);
		foreach ($data as &$row) {
			$row = static::getInstance($row);
		}
		return $data;
	}

	public function getCollection(array $where = [], $orderBy = null)
	{
		$collection = Collection::createForTable($this->db, $this->table, $where, $orderBy);
		$collection->idField = $this->idField;
		$static = get_called_class();
		$collection->itemClassName = $static;
		return $collection;
	}

	/**
	 * http://stackoverflow.com/questions/8707235/how-to-create-new-property-dynamically
	 * @param $name
	 * @param $value
	 */
	public function createProperty($name, $value = null)
	{
		if (isset($this->{$name}) && $value === null) {
			//$this->{$name} = $this->{$name};
		} else {
			$this->{$name} = $value;
		}
	}

	/**
	 * @param array|NULL $where
	 * @return resource|string
	 * @throws Exception
	 */
	public function save(array $where = null)
	{
		if ($this->id) {
			$res = $this->update($this->data);
		} else {
			// this 99.9% insert
			$res = $this->insertUpdate($this->data, $where ?: $this->data, $this->data, $this->data);
		}
		return $res;
	}

	public function get($name)
	{
		return ifsetor($this->data[$name]);
	}

	public function getInt($name)
	{
		return (int)ifsetor($this->data[$name]);
	}

	public function setLogger($log)
	{
		$this->logger = $log;
	}

	public function getID()
	{
		return (int)$this->id;
	}

	public function getBoolField(string $fieldName)
	{
		return self::getBool($this->data[$fieldName] ?? null);
	}

	public static function getBool($value)
	{
		if (is_bool($value)) {
			return $value;
		}

		if (is_int($value)) {
			return $value !== 0;
		}

		if (is_numeric($value)) {
			return (int)$value !== 0;
		}

		if (is_string($value)) {
			return $value && $value[0] === 't';
		}

//		throw new InvalidArgumentException(__METHOD__.' ['.$value.']');
		return false;
	}

	public function setDB(DBInterface $db)
	{
		$this->db = $db;
	}

	public function getDB()
	{
		return $this->db;
	}

	public function hash()
	{
		return spl_object_hash($this);
	}

	public function oid()
	{
		return get_class($this) . '-' . $this->getID() . '-' . substr(md5($this->hash()), 0, 8);
	}

	public function dehydrate()
	{
		return [
			'class' => get_class($this),
			'id' => $this->id,
			'data' => $this->data,
		];
	}

	/**
	 * @param array|string $data
	 * @return OODBase
	 */
	public static function hydrate($data)
	{
		if (is_string($data)) {
			/** @noinspection UnserializeExploitsInspection */
			$data = unserialize($data);
		}
		$el = (object)$data;
		$class = $el->class;
		$obj = new $class();
		foreach ($el as $key => $val) {
			if (is_array($val) && isset($val['class'])) {
				$val = self::hydrate($val);
			}
			/** @noinspection PhpVariableVariableInspection */
			$obj->$key = $val;
		}
		unset($obj->class);    // special case, see above
		return $obj;
	}

}
