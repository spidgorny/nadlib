<?php

class SQLSelectQuery extends SQLWherePart
{

	/**
	 * @var SQLJoin
	 */
	public $join;
	/**
	 * @var SQLWhere
	 */
	public $where;
	/**
	 * @var DBLayerBase|DBLayer|MySQL|DBLayerPDO
	 * @protected to prevent debug output
	 */
	protected $db;
	/**
	 * @var SQLSelect
	 */
	protected $select;
	/**
	 * @var SQLFrom
	 */
	protected $from;
	/**
	 * @var  SQLGroup
	 */
	protected $group;

	/**
	 * @var SQLHaving
	 */
	protected $having;

	/**
	 * @var SQLOrder
	 */
	protected $order;

	/**
	 * @var SQLLimit
	 */
	protected $limit;

	/**
	 * SQLSelectQuery constructor.
	 * @param SQLSelect $select
	 * @param SQLFrom $from
	 * @param SQLWhere $where
	 * @param SQLJoin $join
	 * @param SQLGroup $group
	 * @param SQLHaving $having
	 * @param SQLOrder $order
	 * @param SQLLimit $limit
	 */
	public function __construct($select = null, $from = null, $where = null, $join = null, $group = null, $having = null, $order = null, $limit = null)
	{
		if ($select) {
			$this->setSelect($select);
		}
		if ($from) {
			$this->setFrom($from);
		}
		if ($where) {
			$this->setWhere($where);
		}
		if ($join) {
			$this->setJoin($join);
		} else {
			$this->join = new SQLJoin();
		}
		if ($group) {
			$this->setGroup($group);
		}
		if ($having) {
			$this->setHaving($having);
		}
		if ($order) {
			$this->setOrder($order);
		}
		if ($limit) {
			$this->setLimit($limit);
		}
	}

	public function setJoin(SQLJoin $join)
	{
		$this->join = $join;
	}

	public function setGroup(SQLGroup $group)
	{
		$this->group = $group;
	}

	public function setHaving(SQLHaving $having)
	{
		$this->having = $having;
	}

	public function setOrder(SQLOrder $order)
	{
		$this->order = $order;
	}

	public function setLimit(SQLLimit $limit)
	{
		$this->limit = $limit;
	}

	public function getDistance($lat, $lon, $latitude = 'latitude', $longitude = 'longitude')
	{
		if ($this->db->isSQLite()) {
			$this->db->getConnection()->sqliteCreateFunction('sqrt', function ($a) {
				return sqrt($a);
			}, 1);
			return "sqrt(($latitude - ($lat))*($latitude - ($lat)) + ($longitude - ($lon))*($longitude - ($lon))) AS distance";
		}

			return "( 6371 * acos( cos( radians($lat) ) * cos( radians( $latitude ) )
			* cos( radians( $longitude ) - radians($lon) ) + sin( radians($lat) ) * sin(radians($latitude)) ) ) AS distance";
	}

	/**
	 * @return string
	 */
	public function getQuery()
	{
		$query = trim("SELECT
{$this->select}
FROM {$this->from}
{$this->join}
{$this->where}
{$this->group}
{$this->having}
{$this->order}
{$this->limit}");
		// http://stackoverflow.com/a/709684
		$query = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $query);
		//		debug($this->where, $query, $this->getParameters());
		return $query;
	}

	public function __toString()
	{
		try {
			return $this->getQuery();
		} catch (Exception $e) {
			echo '<strong>', $e->getMessage(), '</strong>', BR;
			//echo '<strong>', $e->getPrevious()->getMessage(), '</strong>', BR;
			pre_print_r($e->getTraceAsString());
			return '<strong>' . $e->getMessage() . '</strong>' . BR;
		}
	}

	public function getFrom()
	{
		return $this->from;
	}

	public function setFrom(SQLFrom $from)
	{
		$this->from = $from;
	}

	public function getWhere()
	{
		return $this->where;
	}

	public function setWhere(SQLWhere $where)
	{
		$this->where = $where;
	}

	public function join($table, $on)
	{
		$this->join = new SQLJoin('LEFT OUTER JOIN ' . $table . ' ON (' . $on . ')');
		return $this;
	}

	public function select($what)
	{
		$this->select = new SQLSelect($what);
		return $this;
	}

	public function getSelect()
	{
		return $this->select;
	}

	public function setSelect(SQLSelect $select)
	{
		$this->select = $select;
	}

}
