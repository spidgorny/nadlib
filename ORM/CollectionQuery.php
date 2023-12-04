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
	public function __construct(DBInterface $db, $table, $join, array $where, $orderBy, $select, Pager $pager = null)
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
			}
			$query = $this->pager->getSQLLimit($query);
			//debug($query.''); exit();
		}
		//debug($query);
		//TaylorProfiler::stop(__METHOD__." ({$this->table})");
		return $query;
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
//		$this->log(__METHOD__, str_replace("\n", " ", str_replace("\t", " ", $this->query . '')));

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
		TaylorProfiler::stop($taylorKey);
		return $data;
	}

	protected function log($action, ...$something)
	{
		llog($action, $something);
		$logEntry = new LogEntry($action, $something);
		$this->log[] = $logEntry;
//		llog($logEntry);
	}

}
