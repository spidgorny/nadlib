<?php

class ModelQuery implements IteratorAggregate
{

	/**
	 * @var DBInterface
	 */
	var $db;

	/**
	 * @var Model
	 */
	var $itemInstance;

	/**
	 * @var string
	 */
	var $itemClassName;

	/**
	 * @var array
	 */
	var $where = [];

	public $table;

	public function __construct(DBInterface $db, Model $instanceClass)
	{
		$this->db = $db;
		$this->itemInstance = $instanceClass;
		$this->itemClassName = get_class($instanceClass);
	}

	public function getQuery(array $where = [], $orderBy = 'ORDER BY id DESC')
	{
		$this->where($where);
		return SQLSelectQuery::getSelectQueryP($this->db, $this->table, $this->where, $orderBy);
	}

	/**
	 * @param array $where
	 * @param string $orderBy
	 * @return array[]
	 */
	public function queryData(array $where, $orderBy = 'ORDER BY id DESC')
	{
		$this->where($where);
		$data = $this->db->fetchAllSelectQuery($this->itemInstance->table, $this->where, $orderBy);
		return $data;
	}

	/**
	 * @param array $where
	 * @param string $orderBy
	 * @return ArrayPlus
	 */
	public function queryObjects(array $where = [], $orderBy = 'ORDER BY id DESC')
	{
		$this->where($where);
		$data = $this->queryData($this->where, $orderBy);
		$list = new ArrayPlus();
		foreach ($data as $row) {
			$list->append(new $this->itemClassName($this->db, $row));
		}
		return $list;
	}

	public function where(array $where)
	{
		$this->where += $where;
		return $this;
	}

	/**
	 * @return ArrayPlus|Traversable|Model[]
	 */
	function getIterator()
	{
		return $this->queryObjects();
	}

}
