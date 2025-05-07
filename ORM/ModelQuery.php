<?php

class ModelQuery implements IteratorAggregate
{

	/**
	 * @var DBInterface
	 */
	public $db;

	/**
	 * @var Model
	 */
	public $itemInstance;

	/**
	 * @var string
	 */
	public static $itemClassName;

	/**
	 * @var array
	 */
	public $where = [];

	public $table;

	public function __construct(DBInterface $db, Model $instanceClass)
	{
		$this->db = $db;
		$this->itemInstance = $instanceClass;
		static::$itemClassName = get_class($instanceClass);
	}

	public function getQuery(array $where = [], $orderBy = 'ORDER BY id DESC')
	{
		$this->where($where);
		$sql = $this->db->qb;
		return $sql->getSelectQuery($this->db, $this->table, $this->where, $orderBy);
	}

	/**
	 * @param string $orderBy
	 * @return array[]
	 */
	public function queryData(array $where, $orderBy = 'ORDER BY id DESC')
	{
		$this->where($where);
		return $this->db->fetchAllSelectQuery($this->itemInstance->table, $this->where, $orderBy);
	}

	/**
	 * @param string $orderBy
	 */
	public function queryObjects(array $where = [], $orderBy = 'ORDER BY id DESC'): \ArrayPlus
	{
		$this->where($where);
		$data = $this->queryData($this->where, $orderBy);
		$list = new ArrayPlus();
		foreach ($data as $row) {
			$list->append(new static::$itemClassName($this->db, $row));
		}

		return $list;
	}

	public function where(array $where): static
	{
		$this->where += $where;
		return $this;
	}

	/**
	 * @return ArrayPlus|Traversable|Model[]
	 */
	public function getIterator(): Traversable
	{
		return $this->queryObjects();
	}

}
