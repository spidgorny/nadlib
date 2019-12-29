<?php

use Psr\Log\LoggerInterface;

/**
 * Base class for storing datasets or datarows or tabular data or set
 * or array of OODBase based objects.
 *
 */
/*abstract*/ // commented because of createForTable()
class Collection implements IteratorAggregate, ToStringable
{

	/**
	 * In case of MSSQL it needs to be set from outside
	 * @var DBInterface
	 * @protected because it's visible in debug
	 * use injection if you need to modify it
	 */
	protected $db;

	/**
	 * @var string
	 */
	public $table = __CLASS__;

	public $idField = 'uid';

	public $parentID = null;

	protected $parentField = 'pid';

	/**
	 * Retrieved rows from DB
	 * Protected in order to force usage of getData()
	 * @var ArrayPlus
	 * @note should not be |array because it's used as ArrayPlus
	 */
	protected $data = [];

	public $thes = [];

	public $titleColumn = 'title';

	/**
	 * Basic where SQL params to be included in every SQL by default
	 * @var array
	 */
	public $where = [];

	/**
	 * for LEFT OUTER JOIN queries
	 * @var string
	 */
	public $join = '';

	/**
	 * Initialize in postInit() to run paged SQL
	 * initialize if necessary with = new Pager(); in postInit()
	 * @var Pager|null
	 */
	public $pager;

	/**
	 * @var PageSize
	 */
	public $pageSize;

	/**
	 * objectify() stores objects generated from $this->data here
	 * array of objects converted from $this->data // convert to public
	 * @var array
	 */
	protected $members = [];

	/**
	 * objectify() without parameters will try this class name
	 * Default is NULL in order to check whether it's set or not.
	 * @var string
	 */
	public $itemClassName;

	/**
	 * SQL part
	 * @var string
	 */
	public $orderBy = "ORDER BY id";

	/**
	 * getQuery() stores the final query here for debug
	 * is null until initialized
	 * @var string|null
	 */
	public $query;

	/**
	 * Is NULL until it's set to 0 or more
	 * @var integer Total amount of data retrieved (not limited by Pager)
	 */
	public $count = NULL;

	/**
	 * Should it be here? Belongs to the controller?
	 * @var Request
	 */
	protected $request;

	/**
	 * Lists columns for the SQL query
	 * @var string
	 * @default "DISTINCT table.*"
	 */
	public $select;

	public $prevText = '&#x25C4;';
	public $nextText = '&#x25BA;';

	/**
	 * @var Controller
	 */
	protected $controller;

	public $doCache = true;

	/**
	 * @var array
	 */
	public $log = [];

	/**
	 * HTMLFormTable
	 * @var array
	 */
	public $desc = [];

	/**
	 * @var CollectionView
	 */
	protected $view;

	/**
	 * @var callable
	 */
	public $prepareRenderRow;

	/**
	 * @var bool - if preprocessData() is called
	 */
	protected $processed = false;

	/**
	 * Gives warnings if 'id' column in the data is not set.
	 * Potentially saves you from trouble futher down the processing.
	 * @var bool
	 */
	public $allowMerge = false;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	public $objectifyByInstance = false;

	/**
	 * @param                integer /-1 $pid
	 *        if -1 - will not retrieve data from DB
	 *        if 00 - will retrieve all data
	 *        if >0 - will retrieve data where PID = $pid
	 * @param array|SQLWhere $where
	 * @param string $order - appended to the SQL
	 * @param DBInterface $db
	 * @throws Exception
	 */
	public function __construct($pid = null, /*array/SQLWhere*/
								$where = [], $order = '', DBInterface $db = null)
	{
		//$taylorKey = get_class($this).'::'.__FUNCTION__." ({$this->table})";
		$taylorKey = Debug::getBackLog(5, 0, BR, false);
		TaylorProfiler::start($taylorKey);
		$config = Config::getInstance();
		$this->db = $db ?: $config->getDB();
		$this->table = $config->prefixTable($this->table);

		if (!$this->select) {
			$firstWordFromTable = $this->db->getFirstWord($this->table);
//			$firstWordFromTable2 = SQLBuilder::getFirstWord($this->table);
//			debug($this->table, $firstWordFromTable, $firstWordFromTable2, typ($this->db));
			$this->select =
				// DISTINCT is 100 times slower, add it manually if needed
				//?: 'DISTINCT /*auto*/ '.$this->db->getFirstWord($this->table).'.*';
				$this->db->quoteKey($firstWordFromTable) . '.*';
		}
		$this->parentID = $pid;

		if (is_array($where)) {
			// array_merge should be use instead of array union,
			// in order to prevent existing entries with numeric keys being ignored in $where
			// simple array_merge will reorder numeric keys which is not good
			$this->where = ArrayPlus::create($this->where)->merge_recursive_overwrite($where)->getData();
		} elseif ($where instanceof SQLWhere) {
			$this->where = $where->addArray($this->where);
		}

		//debug($this->where);
		$this->orderBy = $order ? $order : $this->orderBy;
		$this->request = Request::getInstance();
		$this->postInit();

		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : ['name' => $val];
		}
		$this->translateThes();
		TaylorProfiler::stop($taylorKey);
	}

	public function postInit()
	{
		//$this->pager = new Pager();
		if (class_exists('Index', false)) {
			$index = Index::getInstance();
			$this->controller = &$index->controller;
		}
		//debug(get_class($this->controller));
	}

	/**
	 * -1 will prevent data retrieval
	 * @param bool $preProcess
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function retrieveData($preProcess = true)
	{
		$this->log(get_class($this) . '::' . __FUNCTION__, [
			'allowMerge' => $this->allowMerge,
			'preprocess' => $preProcess,
		]);
		//debug(__METHOD__, $allowMerge, $preprocess);
		$isMySQL = phpversion() > 5.3 && (
				$this->db instanceof MySQL
				|| ($this->db instanceof DBLayerPDO
					&& $this->db->isMySQL())
			);
		if ($isMySQL) {
			$data = $this->retrieveDataFromMySQL();
		} else {
			$data = $this->retrieveDataFromDB();
		}
		$this->log(__METHOD__, 'rows: ' . sizeof($data));
		$this->log(__METHOD__, 'idealize by ' . $this->idField);
//		debug_pre_print_backtrace();
//		debug(get_class($this->db), $isMySQL, $this->query, $data, $this->log);
		$this->data = ArrayPlus::create($data);
		//$this->log(__METHOD__, $this->data->pluck('id'));
		$this->data->IDalize($this->idField, $this->allowMerge);
		$this->log(__METHOD__, 'rows: ' . sizeof($this->data));
		if ($preProcess) {
			$this->preprocessData();
			$this->log(__METHOD__, 'rows: ' . sizeof($this->data));
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	private function retrieveDataFromDB()
	{
		$taylorKey = get_class($this) . '::' . __FUNCTION__ . '#' . __LINE__ . BR .
			Debug::getBackLog(15, 0, BR, false);
		TaylorProfiler::start($taylorKey);
		$this->log(__METHOD__);

		$this->query = $this->getQueryWithLimit();
		//debug($this->query);

		// in most cases we don't need to rasterize the query to SQL
		if ($most_cases = true) {
			$data = $this->db->fetchAll($this->query);
		} else {
			// legacy case - need SQL string
			if ($this->query instanceof SQLSelectQuery) {
				$res = $this->query->perform();
			} else {
				$res = $this->db->perform($this->query);
			}


			if ($this->pager) {
				$this->count = $this->pager->numberOfRecords;
			} else {
				$this->count = $this->db->numRows($res);
			}

			$data = $this->db->fetchAll($res);
		}
		// fetchAll does implement free()
//		$this->db->free($res);
		TaylorProfiler::stop($taylorKey);
		return $data;
	}

	/**
	 * https://dev.mysql.com/doc/refman/5.0/en/information-functions.html#function_found-rows
	 * @requires PHP 5.3
	 * @return array
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function retrieveDataFromMySQL()
	{
		$taylorKey = get_class($this) . '::' . __FUNCTION__ .
			" (" . $this->parentField . ':' . (is_array($this->parentID)
				? json_encode($this->parentID)
				: $this->parentID) . ")";
		TaylorProfiler::start($taylorKey);
		/** @var SQLSelectQuery $query */
		$query = $this->getQuery();
		if (class_exists('PHPSQL\Parser') && false) {
			$sql = new SQLQuery($query);
			$sql->appendCalcRows();
			$this->query = $sql->__toString();
		} else {
			//$this->query = str_replace('SELECT ', 'SELECT SQL_CALC_FOUND_ROWS ', $query);	// subquery problem
			$this->query = preg_replace('/SELECT /', 'SELECT SQL_CALC_FOUND_ROWS ', $query, 1);
		}

		if ($query instanceof SQLSelectQuery) {
			$params = $query->getParameters();
			$res = $this->db->perform($this->query, $params);
		} else {
			$res = $this->db->perform($this->query);
		}

		if ($this->pager) {
			$this->pager->setNumberOfRecords(PHP_INT_MAX);
			$this->pager->detectCurrentPage();
			//$this->pager->debug();
		}
		$start = $this->pager ? $this->pager->getStart() : 0;
		$limit = $this->pager ? $this->pager->getLimit() : PHP_INT_MAX;

		//debug($sql.'', $start, $limit);
		$data = $this->db->fetchPartition($res, $start, $limit);

		$resFoundRows = $this->db->perform('SELECT FOUND_ROWS() AS count');
		$countRow = $this->db->fetchAssoc($resFoundRows);
		$this->count = $countRow['count'];

		if ($this->pager) {
			$this->pager->setNumberOfRecords($this->count);
			$this->pager->detectCurrentPage();
			//$this->pager->debug();
		}
		TaylorProfiler::stop($taylorKey);
		return $data;
	}

	/**
	 * Wrapper for retrieveDataFromDB() to store/retrieve data from the cache file
	 * @param bool $allowMerge
	 * @throws Exception
	 */
	public function retrieveDataFromCache($allowMerge = false)
	{
		if (!$this->data) {                                                    // memory cache
			$this->query = $this->getQuery();
			if ($this->doCache) {
				// this query is intentionally without
				if ($this->pager) {
					$this->pager->setNumberOfRecords(PHP_INT_MAX);
					$this->pager->detectCurrentPage();
					//$this->pager->debug();
				}
				$fc = new MemcacheOne($this->query . '.' . $this->pager->currentPage, 60 * 60);            // 1h
				$this->log('key: ' . substr(basename($fc->map()), 0, 7));
				$cached = $fc->getValue();                                    // with limit as usual
				if ($cached && sizeof($cached) == 2) {
					list($this->count, $this->data) = $cached;
					if ($this->pager) {
						$this->pager->setNumberOfRecords($this->count);
						$this->pager->detectCurrentPage();
					}
					$this->log('found in cache, age: ' . $fc->getAge());
				} else {
					$this->retrieveData($allowMerge);    // getQueryWithLimit() inside
					$fc->set([$this->count, $this->data]);
					$this->log('no cache, retrieve, store');
				}
			} else {
				$this->retrieveData($allowMerge);
			}
			if ($_REQUEST['d']) {
				//debug($cacheFile = $fc->map($this->query), $action, $this->count, filesize($cacheFile));
			}
		}
	}

	public function log($action, $data = [])
	{
		if ($this->logger) {
			if (!is_array($data)) {
				$data = ['data' => $data];
			}
			$this->logger->info($action, $data);
		} else {
			$this->log[] = new LogEntry($action, $data);
		}
	}

	/**
	 * @param array /SQLWhere $where
	 * @return string|SQLSelectQuery
	 */
	public function getQuery($where = [])
	{
		TaylorProfiler::start($profiler = get_class($this) . '::' . __FUNCTION__ . " ({$this->table})");
		if (!$this->db) {
			debug_pre_print_backtrace();
		}
		if (!$where) {
			$where = $this->where;
		}
		if (!empty($this->parentID)) {    // > 0 will fail on string ID
			if ($this->parentID instanceof Date) {
				$where[$this->parentField] = $this->parentID->getMySQL();
			} elseif ($this->parentID instanceof OODBase) {
				$where[$this->parentField] = $this->parentID->id;
			} else {
				$where[$this->parentField] = is_array($this->parentID)
					? new SQLIn($this->parentID)
					: $this->parentID;
			}
		}
		// bijou old style - each collection should care about hidden and deleted
		//$where += $GLOBALS['db']->filterFields($this->filterDeleted, $this->filterHidden, $GLOBALS['db']->getFirstWord($this->table));
		if ($where instanceof SQLWhere) {
			$query = $this->db->getSelectQuerySW($this->table . ' ' . $this->join, $where, $this->orderBy, $this->select);
		} else {
			//debug($where);
			if ($this->join) {
				$query = $this->db->getSelectQuery(
					$this->table . ' ' . $this->join,
					$where,
					$this->orderBy,
					$this->select
				);
			} else {
				// joins are not implemented yet (IMHO)
				$query = $this->db->getSelectQuerySW(
					$this->table,
					$where instanceof SQLWhere ? $where : new SQLWhere($where),
					$this->orderBy,
					$this->select
				);
			}
		}
		if (DEVELOPMENT) {
//			$index = Index::getInstance();
//			$controllerCollection = ifsetor($index->controller->collection);
//			if ($this == $controllerCollection) {
//				header('X-Collection-' . $this->table . ': ' . str_replace(["\r", "\n"], " ", $query));
//			}
		}
		TaylorProfiler::stop($profiler);
		return $query;
	}

	public function getQueryWithLimit()
	{
		$query = $this->getQuery();
		if ($this->pager) {
			// do it only once
			if (!$this->pager->numberOfRecords) {
				//debug($this->pager->getObjectInfo());
				//			debug($query);
				$this->pager->initByQuery($query);
				//			debug($query, $this->query);
				$this->count = $this->pager->numberOfRecords;
			}
			$query = $this->pager->getSQLLimit($query);
			//debug($query.''); exit();
		}
		//debug($query);
		//TaylorProfiler::stop(__METHOD__." ({$this->table})");
		return $query;
	}

	public function preprocessData()
	{
		$profiler = get_class($this) . '::' . __FUNCTION__ . " ({$this->table}): " . $this->getCount();
		TaylorProfiler::start($profiler);
		$this->log(get_class($this) . '::' . __FUNCTION__ . '()');
		if (!$this->processed) {
			$count = $this->getCount();
			// Iterator by reference
			$data = $this->getData();
			foreach ($data as $i => &$row) {
				$row = $this->preprocessRow($row);
			}
			$this->setData($data);
			$this->log(__METHOD__, 'rows: ' . sizeof($this->data));
			$this->processed = true;
			$this->count = $count;
		}
		$this->log(get_class($this) . '::' . __FUNCTION__ . '() done');
		TaylorProfiler::stop($profiler);
		return $this->data;    // return something else if you augment $this->data
	}

	public function preprocessRow(array $row)
	{
		return $row;
	}

	/**
	 * @return string - returns the slTable if not using Pager
	 * @throws Exception
	 */
	public function render()
	{
		$view = $this->getView();
		return $view->renderTable();
	}

	public function isFetched()
	{
		return $this->query && !is_null($this->data);
		// we may have fetched only 0 rows
		//|| !$this->data->count())) {
	}

	/**
	 * @param bool $preProcess
	 * @return ArrayPlus
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getData($preProcess = true)
	{
		$this->log(get_class($this) . '::' . __FUNCTION__ . '()');
		$this->log(__METHOD__, [
			'query' => $this->query
				? substr($this->query, 0, 25) . '...'
				: '-'
		]);
		$this->log(__METHOD__, [
			'data' => $this->data
				? sizeof($this->data)
				: '-'
		]);
		$this->log(__METHOD__, [
			'data->count' =>
			is_null($this->data) ? 'NULL' :	count($this->data)
		]);
		if (!$this->isFetched()) {
			$this->retrieveData($preProcess);
		}
		if (!($this->data instanceof ArrayPlus)) {
			$this->data = ArrayPlus::create($this->data);

			// PROBLEM! This is supposed to be the total amount
			// Don't uncomment
			//$this->count = sizeof($this->data);
		}
		return $this->data;
	}

	public function getProcessedData()
	{
		if ($this->processed) {
			return $this->data;
		} elseif ($this->data) {
			$this->preprocessData();
			return $this->data;
		} else {
			$this->getData();
			$this->preprocessData();
			return $this->data;
		}
	}

	/**
	 * A function to fake the data as if it was retrieved from DB
	 * @param $data array|ArrayPlus
	 */
	public function setData($data)
	{
		$this->log(get_class($this) . '::' . __FUNCTION__ . '(' . sizeof($data) . ')');
		$this->log(__METHOD__, ['from' => Debug::getCaller(2)]);
		//debug_pre_print_backtrace();
		//$this->log(__METHOD__, get_call_stack());
		if ($data instanceof ArrayPlus) {
			$this->data = $data;    // preserve sorting
		} else {
			$this->data = ArrayPlus::create((array)$data);
		}
		// PROBLEM! This is supposed to be the total amount
		// Don't uncomment
		//$this->count = count($this->data);
		$this->count = __METHOD__;    // we need to disable getCount()

		// this is needed to not retrieve the data again
		// after it was set (see $this->getData()
		// which is called in $this->render())
		$this->query = $this->query ?: __METHOD__;
	}

	public function prepareRenderRow(array $row)
	{
		if (is_callable($this->prepareRenderRow)) {
			$closure = $this->prepareRenderRow;
			$row = $closure($row);
		}
		return $row;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		$options = [];
		//debug(get_class($this), $this->table, $this->titleColumn, $this->getCount());
		foreach ($this->getProcessedData() as $row) {
			//if ( !in_array($row[$this->idField], $blackList) ) {
			$options[$row[$this->idField]] = $row[$this->titleColumn];
			//}
		}
		return $options;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getLinks()
	{
		$options = [];
		//debug(get_class($this), $this->table, $this->titleColumn, $this->getCount());
		//debug($this->itemClassName, $this->idField, $this->titleColumn, sizeof($this->members), first($this->getData()->getData()));
		foreach ($this->objectify() as $obj) {
			//debug($obj->id, $obj->getName());
			$options[$obj->id] = $obj->getNameLink();
		}
		return $options;
	}

	/**
	 * @param array $where
	 * @return mixed - single row
	 * @throws Exception
	 */
	public function findInData(array $where)
	{
		//debug($where);
		//echo new slTable($this->data);
		foreach ($this->getData() as $row) {
			$intersect1 = array_intersect_key($row, $where);
			$intersect2 = array_intersect_key($where, $row);
			if ($intersect1 == $intersect2) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * @param array $where
	 * @return array - of matching rows
	 * @throws Exception
	 */
	public function findAllInData(array $where)
	{
		$result = [];
		foreach ($this->getData() as $row) {
			$intersect1 = array_intersect_key($row, $where);
			$intersect2 = array_intersect_key($where, $row);
			if ($intersect1 == $intersect2) {
				$result[] = $row;
			}
		}
		return $result;
	}

	public function renderList()
	{
		$list = [];
		if ($this->getCount()) {
			foreach ($this->getProcessedData() as $id => $row) {
				if ($this->itemClassName) {
					$list[$id] = $this->renderListItem($row);
				} else
					if ($this->thes) {
						$row = $this->prepareRenderRow($row);   // add link
						$item = '';
						foreach ($this->thes as $key => $_) {
							$item .= $row[$key] . ' ';
						}
						$list[$id] = $item;
					} else {
						$list[$id] = $row[$this->titleColumn];
					}
			}
			return new UL($list);
		}
		return null;
	}

	public function renderListItem(array $row)
	{
		/** @var OODBase $obj */
		$class = $this->itemClassName;
		if (method_exists($class, 'getInstance')) {
			$obj = $class::getInstance($row);
		} else {
			$obj = new $class($row);
		}

		if (method_exists($obj, 'render')) {
			$content = $obj->render();
		} elseif (method_exists($obj, 'getSingleLink')) {
			$link = $obj->getSingleLink();
			if ($link) {
				$content = new HTMLTag('a', [
					'href' => $link,
				], $obj->getName());
			} else {
				$content = $obj->getName();
			}
		} else {
			$content = $obj->getName();
		}
		return $content;
	}

	public function getView()
	{
		if (!$this->view) {
			$this->view = new CollectionView($this);
		}
		return $this->view;
	}

	public function setView($view)
	{
		$this->view = $view;
		return $this;
	}

	/**
	 * Calls __toString on each member
	 * @return string
	 */
	public function renderMembers()
	{
		$view = $this->getView();
		return $view->renderMembers();
	}

	public function translateThes()
	{
		if (is_array($this->thes)) {
			foreach ($this->thes as &$trans) {
				if (is_string($trans) && $trans) {
					$trans = __($trans);
				}
			}
		}
		//debug_pre_print_backtrace();
		$this->prevText = __($this->prevText);
		$this->nextText = __($this->nextText);
	}

	/**
	 * @param DBInterface $db
	 * @param string $table
	 * @param array $where
	 * @param string $orderBy
	 * @return Collection
	 * @throws Exception
	 */
	public static function createForTable(DBInterface $db, $table, array $where = [], $orderBy = '')
	{
		$c = new self();
		$c->db = $db;
		$c->table = $table;
		$c->where = $where;
		$c->orderBy = $orderBy;
		/** @var DBLayerBase $db */
		//$db = Config::getInstance()->getDB();
		$firstWord = SQLBuilder::getFirstWord($c->table);
		$firstWord = $db->quoteKey($firstWord);
		$c->select = ' ' . $firstWord . '.*';
		assert($db === $c->db);
		return $c;
	}

	/**
	 * Will detect double-call and do nothing.
	 *
	 * @param string $class - required, but is supplied by the subclasses
	 * @param bool $byInstance - will call getInstance() instead of "new"
	 * @return object[]|OODBase[]
	 * @throws Exception
	 */
	public function objectify($class = null, $byInstance = false)
	{
		$class = $class ? $class : $this->itemClassName;
		if (!$this->members) {
			$this->log(__METHOD__, ['class' => $class, 'instance' => $byInstance]);
			$this->members = [];   // somehow necessary
			foreach ($this->getData() as $row) {
				$key = $row[$this->idField];
				if ($byInstance) {
					//$this->members[$key] = call_user_func_array(array($class, 'getInstance'), array($row));
					$this->members[$key] = call_user_func($class . '::getInstance', $row);
				} else {
					$this->members[$key] = new $class($row);
				}
			}
		}
		return $this->members;
	}

	public function objectifyAsPlus()
	{
		return ArrayPlus::create(
			$this->objectify($this->itemClassName, $this->objectifyByInstance)
		);
	}

	public function __toString()
	{
		return $this->render() . '';
	}

	/**
	 * Wrap output in <form> manually if necessary
	 * @param string $idFieldName Optional param to define a different ID field to use as checkbox value
	 * @throws Exception
	 */
	public function addCheckboxes($idFieldName = '')
	{
		$this->log(get_class($this) . '::' . __FUNCTION__);
		$this->thes = ['checked' => [
				'name' => '<a href="javascript:void(0)">
					<input type="checkbox"
					id="checkAllAuto"
					name="checkAllAuto"
					onclick="checkAll(this)" /></a>', // if we need sorting here just add ""
				'align' => "center",
				'no_hsc' => true,
			]] + $this->thes;
		$class = get_class($this);
		$data = $this->getData();
		$count = $this->getCount();
		foreach ($data as &$row) {
			$id = !empty($idFieldName) ? $row[$idFieldName] : $row[$this->idField];
			$checked = ifsetor($_SESSION[$class][$id])
				? 'checked="checked"' : '';
			$row['checked'] = '
			<input type="checkbox" name="' . $class . '[' . $id . ']"
			value="' . $id . '" ' . $checked . ' />';
		} // <form method="POST">
		$this->setData($data);
		$this->count = $count;
		$this->log(get_class($this) . '::' . __FUNCTION__ . ' done');
	}

	/**
	 * Uses array_merge to prevent duplicates
	 * @param Collection $c2
	 * @throws Exception
	 */
	public function mergeData(Collection $c2)
	{
		$before = array_keys($this->getData()->getData());
		//$this->data = array_merge($this->data, $c2->data);	// don't preserve keys
		$myObjects = $this->objectify();
		$data2 = $c2->getData()->getData();
		$this->data = $this->getData()->merge_recursive_overwrite($data2);
		$this->members = $myObjects + $c2->objectify();
		$this->count += $c2->count;
		//debug($before, array_keys($c2->data), array_keys($this->data));
	}

	/**
	 * Still buggy. This has to be much more simple.
	 * @param Grid $grid
	 * @return string
	 */
	/*	function getNextPrevBrowser(OODBase $model) {
			$content = '';

			//if ($this->data[$model->id]) { // current page contains current element

			$ap = AP($this->data);
			//debug($this->pager->currentPage, implode(', ', array_keys($this->data)));

			$prev = $ap->getPrevKey($model->id);
			if ($prev) {
				$prev = $this->getNextPrevLink($this->data[$prev], '&#x25C4;');
			} else if ($this->pager) {
				$copy = clone $this;
				$copy->pager->setCurrentPage($copy->pager->currentPage-1);
				$copy->retrieveDataFromDB();
				$copy->preprocessData();

				$ap2 = AP($copy->data);
				$prev2 = $ap2->getPrevKey($model->id);
				$prev = $this->getNextPrevLink($copy->data[$prev2] ?: end($copy->data), '&#x25C4;');
				//debug('prev', $copy->pager->currentPage, implode(', ', array_keys($copy->data)));
			}

			$next = $ap->getNextKey($model->id);
			if ($next) {
				$next = $this->getNextPrevLink($this->data[$next], '&#x25BA;');
			} else if ($this->pager) {
				$copy = clone $this;
				$copy->pager->setCurrentPage($copy->pager->currentPage+1);
				$copy->retrieveDataFromDB();
				$copy->preprocessData();

				$ap2 = AP($copy->data);
				$next2 = $ap2->getNextKey($model->id);
				$next = $this->getNextPrevLink($copy->data[$next2] ?: first($copy->data), '&#x25BA;');
				//debug('next', $copy->pager->currentPage, implode(', ', array_keys($copy->data)));
			}

			$content = $prev.' '.$model->getName().' '.$next;
			return $content;
		}*/

	/**
	 * Only $model->id is used to do ArrayPlus::getNextKey() and $mode->getName() for display
	 *
	 * If pager is used then it tries to retrieve page before and after to make sure that first and last
	 * elements on the page still have prev and next elements. But it's SLOW!
	 *
	 * @param OODBase $model
	 * @throws LoginException
	 * @throws Exception
	 * @return string
	 */
	public function getNextPrevBrowser(OODBase $model)
	{
		if ($this->pager) {
			//$this->pager->debug();
			if ($this->pager->currentPage > 0) {
				$copy = clone $this;
				$copy->pager->setCurrentPage($copy->pager->currentPage - 1);
				$copy->retrieveDataFromCache();
				$copy->preprocessData();
				$prevData = $copy->getData()->getData();
			} else {
				$prevData = [];
			}

			$pageKeys = AP($this->data)->getKeys()->getData();
			if ($this->pager->currentPage < $this->pager->getMaxPage() &&
				end($pageKeys) == $model->id    // last element on the page
			) {
				$copy = clone $this;
				$copy->pager->setCurrentPage($copy->pager->currentPage + 1);
				$copy->retrieveData();
				$copy->preprocessData();
				$nextData = $copy->getData()->getData();
			} else {
				$nextData = [];
			}
		} else {
			$prevData = $nextData = [];
		}

		$central = ($this->data instanceof ArrayPlus)
			? $this->data->getData()
			: ($this->data ? $this->data : [])  // NOT NULL
		;

		nodebug($model->id,
			str_replace($model->id, '*' . $model->id . '*', implode(', ', array_keys((array)$prevData))),
			str_replace($model->id, '*' . $model->id . '*', implode(', ', array_keys((array)$this->data))),
			str_replace($model->id, '*' . $model->id . '*', implode(', ', array_keys((array)$nextData)))
		);
		$data = $prevData + $central + $nextData; // not array_merge which will reindex
		$ap = ArrayPlus::create($data);
		//debug($data);

		$prev = $ap->getPrevKey($model->id);
		if ($prev) {
			$prev = $this->getNextPrevLink($data[$prev], $this->prevText);
		} else {
			$prev = '<span class="muted">' . $this->prevText . '</span>';
		}

		$next = $ap->getNextKey($model->id);
		if ($next) {
			$next = $this->getNextPrevLink($data[$next], $this->nextText);
		} else {
			$next = '<span class="muted">' . $this->nextText . '</span>';
		}

		$content = $this->renderPrevNext($prev, $model, $next);

		// switch page for the next time
		if (isset($prevData[$model->id])) {
			$this->pager->setCurrentPage($this->pager->currentPage - 1);
			$this->pager->saveCurrentPage();
		}
		if (isset($nextData[$model->id])) {
			$this->pager->setCurrentPage($this->pager->currentPage + 1);
			$this->pager->saveCurrentPage();
		}

		return $content;
	}

	/**
	 * Override to make links from different type of objects
	 * @param $prev
	 * @param $arrow
	 * @return HTMLTag
	 */
	protected function getNextPrevLink(array $prev, $arrow)
	{
		if ($prev['singleLink']) {
			$content = new HTMLTag('a', [
				'href' => $prev['singleLink'],
				'title' => ifsetor($prev['name']),
			],
				//'&lt;',			// <
				//'&#x21E6;',			// ⇦
				//'&#25C0;',		// ◀
				//'&#x25C4;',		// ◄
				$arrow,
				true);
		} else {
			$content = $arrow;
		}
		return $content;
	}

	/**
	 * @param $prev
	 * @param $model OODBase
	 * @param $next
	 * @return string
	 */
	protected function renderPrevNext($prev, $model, $next)
	{
		return $prev . ' ' . $model->getName() . ' ' . $next;
	}

	public function getObjectInfo()
	{
		$list = [];
		foreach ($this->members as $obj) {
			/** @var $obj OODBase */
			$list[] = $obj->getObjectInfo();
		}
		return $list;
	}

	public function getLazyIterator()
	{
		$query = $this->getQuery();
		//debug($query);

		$lazy = new DatabaseResultIteratorAssoc($this->db, $this->idField);
		$lazy->perform($query);
		$this->query = $lazy->query;

		return $lazy;
	}

	/**
	 * @param string $class
	 * @return LazyMemberIterator|$class[]
	 */
	public function getLazyMemberIterator($class = null)
	{
		if (!$class) {
			$class = $this->itemClassName;
		}
		$arrayIterator = $this->getLazyIterator();
		$memberIterator = new LazyMemberIterator($arrayIterator, $class);
		$memberIterator->count = $arrayIterator->count();
		return $memberIterator;
	}

	/**
	 * Don't update $this->query otherwise getData() will think we have
	 * retrieved nothing.
	 * TODO: Create a new class called SQLCountQuery() and use it here to transform SQL to count(*)
	 * @return int
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getCount()
	{
		$this->log(get_class($this) . '::' . __FUNCTION__, $this->count);
		if (is_null($this->count)) {
			if ($this->pager) {
				$this->getQueryWithLimit();     // will init pager
				// and set $this->count
			} else {
				$queryWithLimit = $this->getQueryWithLimit();
//				debug(__METHOD__, $queryWithLimit.'');
				if (contains($queryWithLimit, 'LIMIT')) {    // no pager - no limit
					// we do not preProcessData()
					// because it's irrelevant for the count
					// but can make the processing too slow
					// like in QueueEPES
					$this->retrieveData(false);
					// will set the count
					// actually it doesn't
					$this->count = sizeof($this->data);
				} elseif ($queryWithLimit instanceof SQLSelectQuery) {
					$this->count = $this->db->getCount($queryWithLimit);
				} else {
					// this is the same query as $this->retrieveData() !
					$query = $this->getQuery();
					//debug('performing', $query);
					if (is_string($query)) {
						xdebug_break();
					}
					$res = $query->perform();
					$this->count = $this->db->numRows($res);
				}
			}
		}
		$this->log(get_class($this) . '::' . __FUNCTION__, $this->count);
		return $this->count;
	}

	public function clearInstances()
	{
		unset($this->data);
		unset($this->members);
	}

	public function getJson()
	{
		$members = [];
		foreach ($this->objectify() as $id => $member) {
			$members[$id] = $member->getJson();
		}
		return [
			'class' => get_class($this),
			'count' => $this->getCount(),
			'members' => $members,
		];
	}

	public function reload()
	{
		$this->reset();
		$this->retrieveData();
	}

	/**
	 * @param object $obj
	 * @return bool
	 * @throws Exception
	 */
	public function contains($obj)
	{
		foreach ($this->objectify() as $mem) {
			if ($mem == $obj) {
				return true;
			}
		}
		return false;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return ArrayPlus Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @throws Exception
	 */
	public function getIterator()
	{
		return new ArrayPlus($this->objectify($this->itemClassName, $this->objectifyByInstance));
	}

	public function get($id)
	{
		$members = $this->objectify();
		return ifsetor($members[$id]);
	}

	public function setMembers(array $countries)
	{
		$this->members = $countries;
		$this->count = sizeof($this->members);
		$this->query = __METHOD__;

		$this->data = [];
		foreach ($this->members as $obj) {
			$this->data[$obj->id] = $obj->data;
		}
	}

	/**
	 * Make sure we re-query data from DB
	 */
	public function reset()
	{
		$this->count = null;
		$this->query = null;
		$this->data = null;
		$this->members = null;
	}

	public function getIDs()
	{
		return $this->getData()->getKeys()->getData();
	}

	public function first()
	{
		//debug($this->getQuery());
		return first($this->objectify());
	}

	public function containsID($id)
	{
		return in_array($id, $this->getIDs());
	}

	public function containsName($name)
	{
		foreach ($this->getData() as $row) {
			if ($row[$this->titleColumn] == $name) {
				return true;
			}
		}
		return false;
	}

	public function setDB(DBInterface $ms)
	{
		$this->db = $ms;
	}

	public static function hydrate($source)
	{
		$class = $source->class;
		/** @var Collection $object */
		$object = new $class();
		$object->count = $source->count;
		$memberClass = $object->itemClassName;
		foreach ($source->members as $id => $m) {
			$child = new $memberClass();
			$child->id = $id;
			$child->data = (array)$m->data;
			$object->members[$id] = $child;
		}
		return $object;
	}

	public function unobjectify()
	{
		foreach ($this->objectify() as $i => $el) {
			$this->data[$i] = $el->data;
		}
	}

	public function setLogger($log)
	{
		$this->logger = $log;
	}

	public function getLogger()
	{
		return $this->logger;
	}

}
