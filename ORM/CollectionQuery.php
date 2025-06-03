<?php

class CollectionQuery
{

	/** @var DBInterface */
	public $db;

	public $log = [];

	protected $table;

	protected $join;

	protected array $where;

	protected $orderBy;

	protected $select;

	protected $query;

	protected ?\Pager $pager;

	/**
	 * @param $table
	 * @param $join
	 * @param $where SQLWhere|array
	 * @param $orderBy
	 * @param $select
	 * @param Pager|null $pager
	 */
	public function __construct(DBInterface $db, $table, $join, array $where, $orderBy, $select, ?Pager $pager = null)
	{
		$this->db = $db;
		$this->table = $table;
		$this->join = $join;
		$this->where = $where;
		$this->orderBy = $orderBy;
		$this->select = $select;
		$this->pager = $pager;
	}

	public function retrieveData()
	{
		//debug(__METHOD__, $allowMerge, $preprocess);
		$isMySQL = PHP_VERSION > 5.3 && (
			(($this->db instanceof DBLayerPDO)
				&& $this->db->isMySQL())
			);
		if ($isMySQL) {
			$cq = new CollectionQueryMySQL($this->db,
				$this->table,
				$this->join,
				$this->where,
				$this->orderBy,
				$this->select,
				$this->pager);
			$data = $cq->retrieveDataFromMySQL();
		} else {
			$data = $this->retrieveDataFromDB();
		}

		return $data;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	private function retrieveDataFromDB()
	{
		$taylorKey = get_class($this) . '::' . __FUNCTION__;
		TaylorProfiler::start($taylorKey);
//		$this->log(__METHOD__, Debug::getBackLog(25, 0, null, false));

		$this->query = $this->getQueryWithLimit();
//		$this->log($taylorKey, [
//			'query' => str_replace("\n", " ", str_replace("\t", " ", $this->query . ''))
//		]);

		// in most cases we don't need to rasterize the query to SQL
		$res = $this->db->perform($this->query);
		$data = $this->db->fetchAll($res);

		if (method_exists($this->db, 'fixRowDataTypes')) {
			$data = collect($data)->map(function ($row) use ($res) {
				return $this->db->fixRowDataTypes($res, $row);
			})->toArray();
		}
		// fetchAll does implement free()
		TaylorProfiler::stop($taylorKey);
		return $data;
	}

	public function getQueryWithLimit()
	{
		$query = $this->getQuery();
		if ($this->pager instanceof \Pager) {
			// do it only once
			if (!$this->pager->numberOfRecords) {
				//debug($this->pager->getObjectInfo());
				//			debug($query);
				$this->pager->initByQuery($query);
				//			debug($query, $this->query);
			}

			$query = $this->pager->getSQLLimit($query);
			//debug($query.''); exit();
		}

		//debug($query);
		//TaylorProfiler::stop(__METHOD__." ({$this->table})");
		return $query;
	}

	/**
	 * @param array|SQLWhere $where
	 * @return string|SQLSelectQuery
	 */
	public function getQuery($where = [])
	{
		TaylorProfiler::start($profiler = get_class($this) . '::' . __FUNCTION__ . sprintf(' (%s)', $this->table));
		if (!$this->db) {
			debug_pre_print_backtrace();
		}

		if (!$where) {
			$where = $this->where;
		}

		// bijou old style - each collection should care about hidden and deleted
		//$where += $GLOBALS['db']->filterFields($this->filterDeleted, $this->filterHidden, $GLOBALS['db']->getFirstWord($this->table));
		if ($where instanceof SQLWhere) {
			$query = $this->db->getSelectQuerySW($this->table . ' ' . $this->join, $where, $this->orderBy, $this->select);
		} elseif ($this->join) {
			//debug($where);
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

		//			$index = Index::getInstance();
		//			$controllerCollection = ifsetor($index->controller->collection);
		//			if ($this == $controllerCollection) {
		//				header('X-Collection-' . $this->table . ': ' . str_replace(["\r", "\n"], " ", $query));
		//			}


		TaylorProfiler::stop($profiler);
		return $query;
	}

	protected function log($action, ...$something)
	{
		llog($action, $something);
		$logEntry = new LogEntry($action, $something);
		$this->log[] = $logEntry;
//		llog($logEntry);
	}

}
