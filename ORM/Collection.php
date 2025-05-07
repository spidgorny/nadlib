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
	 * @var string
	 */
	public $table = __CLASS__;

	public $idField = 'uid';

	public $parentID;

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
	 * objectify() without parameters will try this class name
	 * Default is NULL in order to check whether it's set or not.
	 * @var string
	 */
	public static $itemClassName;

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
	 * @var int Total amount of data retrieved (not limited by Pager)
	 */
	public $count;

	/**
	 * Lists columns for the SQL query
	 * @var string
	 * @default "DISTINCT table.*"
	 */
	public $select;

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
	 * @var callable
	 */
	public $prepareRenderRow;

	/**
	 * Gives warnings if 'id' column in the data is not set.
	 * Potentially saves you from trouble futher down the processing.
	 * @var bool
	 */
	public $allowMerge = false;

	public $objectifyByInstance = false;

	/**
	 * In case of MSSQL it needs to be set from outside
	 * @var DBInterface
	 * @protected because it's visible in debug
	 * use injection if you need to modify it
	 */
	protected $db;

	protected $parentField = 'pid';

	/**
	 * Retrieved rows from DB
	 * Protected in order to force usage of getData()
	 * @var ArrayPlus
	 * @note should not be |array because it's used as ArrayPlus
	 */
	protected $data;

	/**
	 * objectify() stores objects generated from $this->data here
	 * array of objects converted from $this->data // convert to public
	 * @var array
	 */
	protected $members = [];

	/**
	 * Should it be here? Belongs to the controller?
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Controller
	 */
	protected $controller;

	/**
	 * @var CollectionView
	 */
	protected $view;

	/**
	 * @var bool - if preprocessData() is called
	 */
	protected $processed = false;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @param int /-1 $pid
	 *        if -1 - will not retrieve data from DB
	 *        if 00 - will retrieve all data
	 *        if >0 - will retrieve data where PID = $pid
	 * @param array|SQLWhere $where
	 * @param string $order - appended to the SQL
	 * @throws Exception
	 */
	public function __construct(
		$pid = null, /*array/SQLWhere*/
		$where = [],
		$order = '',
		DBInterface $db = null,
		?Controller $controller = null
	)
	{
		//$taylorKey = get_class($this).'::'.__FUNCTION__." ({$this->table})";
		$taylorKey = Debug::getBackLog(5, 0, BR, false);
		TaylorProfiler::start($taylorKey);
		$this->db = $db;
//		$this->table = $config->prefixTable($this->table);
		$this->controller = $controller;

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
		$this->orderBy = $order ?: $this->orderBy;
		$this->request = Request::getInstance();

		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : ['name' => $val];
		}

		$this->translateThes();

		if (!empty($this->parentID)) {    // > 0 will fail on string ID
			if ($this->parentID instanceof Date) {
				$this->where[$this->parentField] = $this->parentID->getMySQL();
			} elseif ($this->parentID instanceof OODBase) {
				$this->where[$this->parentField] = $this->parentID->id;
			} else {
				$this->where[$this->parentField] = is_array($this->parentID)
					? new SQLIn($this->parentID)
					: $this->parentID;
			}
		}

		TaylorProfiler::stop($taylorKey);
	}

	public function log($action, $data = []): void
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

	public function isFetched(): bool
	{
		return $this->query && $this->data !== null;
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
		$this->log(get_class($this) . '::' . __FUNCTION__ . '(preProcess=' . $preProcess . ')');
		$this->log(__METHOD__, 'collection Where' . json_encode($this->where, JSON_THROW_ON_ERROR));
		$this->log(__METHOD__, 'query: ' . $this->query . '');
		$this->log(__METHOD__, [
			'data' => $this->data
				? count($this->data)
				: '-',
			'data->count' =>
				$this->data === null ? 'NULL' : count($this->data),
			'isFetched' => $this->isFetched(),
		]);
		if (!$this->isFetched()) {
			$this->retrieveData();
			if ($preProcess) {
				$this->preprocessData();
				$this->log(__METHOD__, 'rows: ' . count($this->data));
			}
		}

		// although this is ugly - SoftwareGrid still needs that
		if (!($this->data instanceof ArrayPlus)) {
			$this->data = ArrayPlus::create($this->data);

			// PROBLEM! This is supposed to be the total amount
			// Don't uncomment
			//$this->count = sizeof($this->data);
		}

		return $this->data;
	}

	/**
	 * A function to fake the data as if it was retrieved from DB
	 * @param $data array|ArrayPlus
	 */
	public function setData($data): void
	{
		$this->log(get_class($this) . '::' . __FUNCTION__ . '(' . count($data) . ')');
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

	/**
	 * id => -1 will prevent data retrieval
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 * @throws Exception
	 */
	public function retrieveData(): void
	{
		$method = get_class($this) . '::' . __FUNCTION__;
		$this->log($method, [
			'allowMerge' => $this->allowMerge,
			'collection Where' => json_encode($this->where, JSON_THROW_ON_ERROR)
		]);
		$cq = $this->getCollectionQuery();
		$data = $cq->retrieveData();
		$this->log = array_merge($this->log, $cq->log);

//		llog($method, [
//			'rows' => count($data),
//			'idealize by' => $this->idField, //'data' => $data,
//			'idList' => collect($data)->pluck($this->idField)->toArray(),
//		]);
//		debug_pre_print_backtrace();
//		debug(get_class($this->db), $isMySQL, $this->query, $data, $this->log);
		$this->data = ArrayPlus::create($data);
		//$this->log(__METHOD__, $this->data->pluck('id'));
		$this->data->IDalize($this->idField, $this->allowMerge);
		$this->log($method, ['rows after idealize' => count($this->data)]);
	}

	/**
	 * @throws JsonException
	 */
	public function getCollectionQuery(): CollectionQuery
	{
		static $cq = [];
		$hash = implode(':', [
			spl_object_hash($this),
			spl_object_hash($this->db),
			sha1(json_encode($this->table, JSON_THROW_ON_ERROR)),
			sha1(json_encode($this->join, JSON_THROW_ON_ERROR)),
			sha1(json_encode($this->where, JSON_THROW_ON_ERROR)),
			sha1(json_encode($this->orderBy, JSON_THROW_ON_ERROR)),
			sha1(json_encode($this->select, JSON_THROW_ON_ERROR)),
			$this->pager ? spl_object_hash($this->pager) : '',
		]);
		$this->log(__METHOD__, substr(sha1($hash), 0, 6) . json_encode($this->where, JSON_THROW_ON_ERROR));
		if (!ifsetor($cq[$hash])) {
			$cq[$hash] = new CollectionQuery(
				$this->db,
				$this->table,
				$this->join,
				$this->where,
				$this->orderBy,
				$this->select,
				$this->pager
			);
		}

		return $cq[$hash];
	}

	/**
	 * Make sure to retrieveData() before
	 * We can't fetchData here as it will make an infinite loop
	 * @return ArrayPlus|null
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function preprocessData()
	{
		$profiler = get_class($this) . '::' . __FUNCTION__ . sprintf(' (%s): ', $this->table) . $this->getCount();
		TaylorProfiler::start($profiler);
		$this->log(get_class($this) . '::' . __FUNCTION__ . '()');
		if (!$this->processed) {
			$count = $this->getCount();
			// Iterator by reference
			$data = $this->getData()->getArrayCopy();
			foreach ($data as &$row) {
				$row = $this->preprocessRow($row);
			}

			$this->data->setData($data);
			$this->log(__METHOD__, 'rows: ' . count($this->data));
			$this->processed = true;
			$this->count = $count;
		}

		$this->log(get_class($this) . '::' . __FUNCTION__ . '() done');
		TaylorProfiler::stop($profiler);
		return $this->data;    // return something else if you augment $this->data
	}

	/**
	 * Don't update $this->query otherwise getData() will think we have
	 * retrieved nothing.
	 * Count can be a heavy operation, we should only query the count once.
	 * But if the query changes, the count needs to be updated.
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getCount(): int
	{
//		$this->log('this->query', $this->query.'');
//		$this->log('getQueryWithLimit', $this->getQueryWithLimit().'');
		$queryIsTheSame = ($this->query . '') === ($this->getQueryWithLimit() . '');
//		llog(['count before' => $this->count, '$queryIsTheSame' => $queryIsTheSame]);
		if ($this->count !== null && $queryIsTheSame) {
			return (int)$this->count;
		}

		$this->query = $this->getQueryWithLimit();     // will init pager
		if ($this->pager && $this->pager->numberOfRecords) {
			$this->count = $this->pager->numberOfRecords;
		} else {
			$cq = $this->getCollectionQuery();
			$counter = new SQLCountQuery($cq);
			$this->count = $counter->getCount();
		}

//		llog(['$this->count', $this->count]);
		return (int)$this->count;
	}

	public function getQueryWithLimit()
	{
		$cq = $this->getCollectionQuery();
		return $cq->getQueryWithLimit();
	}

	/**
	 * Override me to make changes
	 */
	public function preprocessRow(array $row): array
	{
		return $row;
	}

	public function translateThes(): void
	{
		if (is_array($this->thes)) {
			foreach ($this->thes as &$trans) {
				if (is_string($trans) && $trans) {
					$trans = __($trans);
				}
			}
		}

		//debug_pre_print_backtrace();
	}

	/**
	 * @param string $table
	 * @param string $orderBy
	 * @throws Exception
	 */
	public static function createForTable(DBInterface $db, $table, array $where = [], $orderBy = ''): self
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

	public function getOptions(): array
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

	public function getProcessedData()
	{
//		llog(get_class($this), Debug::getCaller(5));
		if ($this->processed) {
			return $this->data;
		}

//		llog('$this->data', (bool)$this->data);
		if ($this->data) {
			$this->preprocessData();
			return $this->data;
		}

		$this->getData();
//		llog('count($this->data) after getData', count($this->data));
		$this->preprocessData();
//		llog('count($this->data) after processing', count($this->data));
		return $this->data;
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
	 * @throws Exception
	 */
	public function getLinks(): array
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
	 * Will detect double-call and do nothing.
	 *
	 * @param string $class - required, but is supplied by the subclasses
	 * @param bool $byInstance - will call getInstance() instead of "new"
	 * @return object[]|OODBase[]
	 * @throws Exception
	 */
	public function objectify($class = null, $byInstance = false)
	{
		$class = $class ?: static::$itemClassName;
		if ($this->members) {
			return $this->members;
		}

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

		return $this->members;
	}

	public function getObjects()
	{
		return $this->objectify();
		// what to composer request for this?
//		return collect($this->objectify());
	}

	/**
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
			if ($intersect1 === $intersect2) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * @return array - of matching rows
	 * @throws Exception
	 */
	public function findAllInData(array $where): array
	{
		$result = [];
		foreach ($this->getData() as $row) {
			$intersect1 = array_intersect_key($row, $where);
			$intersect2 = array_intersect_key($where, $row);
			if ($intersect1 === $intersect2) {
				$result[] = $row;
			}
		}

		return $result;
	}

	public function renderList(): ?\UL
	{
		$list = [];
		if ($this->getCount() === 0) {
			return null;
		}

//		llog('$this->getProcessedData()', $this->getProcessedData()->count());
		foreach ($this->getProcessedData() as $id => $row) {
			if (static::$itemClassName) {
				$list[$id] = $this->renderListItem($row);
			} elseif ($this->thes) {
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

	public function renderListItem(array $row)
	{
		/** @var OODBase $obj */
		$class = static::$itemClassName;
		$obj = method_exists($class, 'getInstance') ? $class::getInstance($row) : new $class($row);

		if (method_exists($obj, 'render')) {
			$content = $obj->render();
		} elseif (method_exists($obj, 'getSingleLink')) {
			$link = $obj->getSingleLink();
			$content = $link ? new HTMLTag('a', [
				'href' => $link,
			], $obj->getName()) : $obj->getName();
		} else {
			$content = $obj->getName();
		}

		return $content;
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

	public function getView()
	{
		if (!$this->view) {
			$this->view = new CollectionView($this);
		}

		return $this->view;
	}

	public function setView($view): static
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
		return $this->getView()->renderMembers();
	}


	public function objectifyAsPlus(): \ArrayPlus
	{
		return ArrayPlus::create(
			$this->objectify(static::$itemClassName, $this->objectifyByInstance)
		);
	}

	public function __toString(): string
	{
		return $this->render() . '';
	}

	/**
	 * Wrap output in <form> manually if necessary
	 * @param string $idFieldName Optional param to define a different ID field to use as checkbox value
	 * @throws Exception
	 */
	public function addCheckboxes($idFieldName = ''): void
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
			$id = empty($idFieldName) ? $row[$this->idField] : $row[$idFieldName];
			$checked = ifsetor($_SESSION[$class][$id])
				? 'checked="checked"' : '';
			$row['checked'] = '
			<input type="checkbox" name="' . $class . '[' . $id . ']"
			value="' . $id . '" ' . $checked . ' />';
		}

		// <form method="POST">
		$this->setData($data);
		$this->count = $count;
		$this->log(get_class($this) . '::' . __FUNCTION__ . ' done');
	}

	/**
	 * Uses array_merge to prevent duplicates
	 * @throws Exception
	 */
	public function mergeData(Collection $c2): void
	{
		array_keys($this->getData()->getData());
		//$this->data = array_merge($this->data, $c2->data);	// don't preserve keys
		$myObjects = $this->objectify();
		$data2 = $c2->getData()->getData();
		$this->data = $this->getData()->merge_recursive_overwrite($data2);
		$this->members = $myObjects + $c2->objectify();
		$this->count += $c2->count;
		//debug($before, array_keys($c2->data), array_keys($this->data));
	}

	/**
	 * @return mixed[]
	 */
	public function getObjectInfo(): array
	{
		$list = [];
		foreach ($this->members as $obj) {
			/** @var OODBase $obj */
			$list[] = $obj->getObjectInfo();
		}

		return $list;
	}

	public function getLazyIterator(): \DatabaseResultIteratorAssoc
	{
		$query = $this->getCollectionQuery()->getQuery();
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
	public function getLazyMemberIterator($class = null): \LazyMemberIterator
	{
		if (!$class) {
			$class = static::$itemClassName;
		}

		$arrayIterator = $this->getLazyIterator();
		$memberIterator = new LazyMemberIterator($arrayIterator, $class);
		$memberIterator->count = $arrayIterator->count();
		return $memberIterator;
	}

	public function clearInstances(): void
	{
		unset($this->data);
		unset($this->members);
	}

	public function getJson(): array
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

	public function reload(): void
	{
		$this->reset();
		$this->retrieveData();
	}

	/**
	 * Make sure we re-query data from DB
	 */
	public function reset(): void
	{
		$this->count = null;
		$this->query = null;
		$this->data = null;
		$this->members = null;
	}

	/**
	 * @param object $obj
	 * @throws Exception
	 */
	public function contains($obj): bool
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
	public function getIterator(): Traversable
	{
		return new ArrayPlus($this->objectify(static::$itemClassName, $this->objectifyByInstance));
	}

	public function get($id)
	{
		$members = $this->objectify();
		return ifsetor($members[$id]);
	}

	public function setMembers(array $countries): void
	{
		$this->members = $countries;
		$this->count = count($this->members);
		$this->query = __METHOD__;

		$this->data = ArrayPlus::create();
		foreach ($this->members as $obj) {
			$this->data[$obj->id] = $obj->data;
		}
	}

	public function first(): mixed
	{
		return first($this->objectify());
	}

	public function containsID($id): bool
	{
		return in_array($id, $this->getIDs());
	}

	public function getIDs(): array
	{
		return $this->getData()->getKeys()->getData();
	}

	public function containsName($name): bool
	{
		foreach ($this->getData() as $row) {
			if ($row[$this->titleColumn] === $name) {
				return true;
			}
		}

		return false;
	}

	public function setDB(DBInterface $ms): void
	{
		$this->db = $ms;
	}

	public function unobjectify(): void
	{
		foreach ($this->objectify() as $i => $el) {
			$this->data[$i] = $el->data;
		}
	}

	public function getLogger()
	{
		return $this->logger;
	}

	public function setLogger($log): void
	{
		$this->logger = $log;
	}

	public function isTrue($value): bool
	{
		return $value === 't' || $value === true;
	}

}
