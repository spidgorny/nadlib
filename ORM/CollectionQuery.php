<?php

class CollectionQuery
{

	/** @var DBInterface */
	public $db;
	public $log = [];
	protected $table;
	protected $join;
	protected $where;
	protected $orderBy;
	protected $select;
	protected $query;
	/**
	 * @var Pager
	 */
	protected $pager;

	/**
	 * @param DBInterface $db
	 * @param $table
	 * @param $join
	 * @param $where SQLWhere|array
	 * @param $orderBy
	 * @param $select
	 * @param Pager|null $pager
	 */
	public function __construct(DBInterface $db, $table, $join, $where, $orderBy, $select, Pager $pager = null)
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
		$isMySQL = (float)PHP_VERSION > 5.3 && (
				$this->db instanceof MySQL
				|| ($this->db instanceof DBLayerPDO
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

		$this->query = $this->getQueryWithLimit();
//		$this->log($taylorKey, [
//			'type' => get_debug_type($this->query),
//			'query' => $this->query
//		]);

		// in most cases we don't need to rasterize the query to SQL
		$most_cases = true;
		if ($most_cases) {
			$data = $this->db->fetchAll($this->query);
		} else {
			// legacy case - need SQL string
			if ($this->query instanceof SQLSelectQuery) {
				$res = $this->query->perform();
			} else {
				$res = $this->db->perform($this->query);
			}
			$data = $this->db->fetchAll($res);
		}
		// fetchAll does implement free()
//		$this->db->free($res);
//		$this->log($taylorKey, [
//			'fetched fetchAll rows' => count($data),
//		]);
		TaylorProfiler::stop($taylorKey);
		return $data;
	}

	public function getQueryWithLimit(): SQLSelectQuery
	{
		$query = $this->getQuery();
		if ($this->pager) {
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
	 * @param array /SQLWhere $where
	 * @return string|SQLSelectQuery
	 */
	public function getQuery($where = [])
	{
		TaylorProfiler::start($profiler = get_class($this) . '::' . __FUNCTION__ . " ({$this->table})");
		if (!$this->db) {
			throw new DatabaseException('DB is not set in ' . get_class($this));
		}
		if (!$where) {
			$where = $this->where;
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

	protected function log($action, ...$something)
	{
		llog($action, $something);
		$logEntry = new LogEntry($action, $something);
		$this->log[] = $logEntry;
//		llog($logEntry);
	}

}
