<?php

class SQLSelectQuery {
	/**
	 * Enter description here...
	 *
	 * @var SQLSelect
	 */
	protected $select;
	/**
	 * Enter description here...
	 *
	 * @var SQLFrom
	 */
	protected $from;
	/**
	 * Enter description here...
	 *
	 * @var SQLJoin
	 */
	public $join;
	/**
	 * Enter description here...
	 *
	 * @var SQLWhere
	 */
	public $where;
	/**
	 * Enter description here...
	 *
	 * @var  SQLGroup
	 */
	protected $group;
	/**
	 * Enter description here...
	 *
	 * @var SQLHaving
	 */
	protected $having;
	/**
	 * Enter description here...
	 *
	 * @var SQLOrder
	 */
	protected $order;
	/**
	 * Enter description here...
	 *
	 * @var SQLLimit
	 */
	protected $limit;

	function __construct($select = NULL, $from = NULL, $where = NULL, $join = NULL, $group = NULL, $having = NULL, $order = NULL, $limit = NULL) {
		if ($select) 	$this->setSelect($select);
		if ($from) 		$this->setFrom($from);
		if ($where) 	$this->setWhere($where);
		if ($join) 		$this->setJoin($join);		else $this->join = new SQLJoin();
		if ($group) 	$this->setGroup($group);
		if ($having) 	$this->setHaving($having);
		if ($order) 	$this->setOrder($order);
		if ($limit) 	$this->setLimit($limit);
	}

	function setSelect(SQLSelect $select) {
		$this->select = $select;
	}

	function setFrom(SQLFrom $from) {
		$this->from = $from;
	}

	function setWhere(SQLWhere $where) {
		$this->where = $where;
	}

	function setJoin(SQLJoin $join) {
		$this->join = $join;
	}

	function setGroup(SQLGroup $group) {
		$this->group = $group;
	}

	function setHaving(SQLHaving $having) {
		$this->having = $having;
	}

	function setOrder(SQLOrder $order) {
		$this->order = $order;
	}

	function setLimit(SQLLimit $limit) {
		$this->limit = $limit;
	}

	function getQuery() {
		$query = "SELECT
	$this->select
FROM $this->from
$this->join
$this->where
$this->group
$this->having
$this->limit";
		return $query;
	}

	function __toString() {
		return $this->getQuery();
	}

}