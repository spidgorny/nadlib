<?php

class SQLSelectQuery {

	/**
	 * @var dbLayerBase|dbLayer
	 * @protected to hide from debug
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
	 * @var SQLJoin
	 */
	public $join;

	/**
	 * @var SQLWhere
	 */
	public $where;

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

	function __construct($select = NULL, $from = NULL, $where = NULL, $join = NULL, $group = NULL, $having = NULL, $order = NULL, $limit = NULL) {
		if ($select) 	$this->setSelect($select);
		if ($from) 		$this->setFrom($from);
		if ($where) 	$this->setWhere($where);
		if ($join) 		$this->setJoin($join);
			else 		$this->join = new SQLJoin();
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

	public static function getDistance($lat, $lon, $latitude = 'latitude', $longitude = 'longitude') {
		return "( 6371 * acos( cos( radians($lat) ) * cos( radians( $latitude ) )
		* cos( radians( $longitude ) - radians($lon) ) + sin( radians($lat) ) * sin(radians($latitude)) ) ) AS distance";
	}

	function getQuery() {
		$query = trim("SELECT
{$this->select}
FROM {$this->from}
{$this->join}
{$this->where}
{$this->group}
{$this->having}
{$this->order}
{$this->limit}");
		return $query;
	}

	function __toString() {
		try {
			return $this->getQuery();
		} catch (Exception $e) {
			echo '<strong>', $e->getMessage(), '</strong>', BR;
			//echo '<strong>', $e->getPrevious()->getMessage(), '</strong>', BR;
			pre_print_r($e->getTraceAsString());
		}
	}

	static function sqlSH($sql) {
		$res = '';
		$words = array('SELECT', 'FROM', 'WHERE', 'GROUP', 'BY', 'ORDER', 'HAVING', 'AND', 'OR', 'LIMIT', 'OFFSET', 'LEFT', 'OUTER', 'INNER', 'RIGHT', 'JOIN', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END', 'AS', 'DISTINCT', 'ON', 'NATURAL');
		$breakAfter = array('SELECT', 'BY', 'OUTER', 'ON', 'DISTINCT', 'AS', 'WHEN', 'NATURAL');
		$sql = str_replace("(", " ( ", $sql);
		$sql = str_replace(")", " ) ", $sql);
		$level = 0;
		$open = FALSE;
		$tok = strtok($sql, " \n\t");
		while ($tok !== FALSE) {
			$tok = trim($tok);
			if ($tok == "(") {
				$level++;
				$res .= " (" . "<br>" . str_repeat("&nbsp;", $level*4);
			} else if ($tok == ")") {
				if ($level > 0) {
					$level--;
				}
				$res .= "<br>" . str_repeat("&nbsp;", $level*4) . ") ";
			} elseif ($tok && ($tok{0} == "'" || $tok{strlen($tok)-1} == "'" || $tok == "'")) {
				$res .= " ";
				if ($tok{0} == "'" && !$open) {
					$res .= '<font color="green">';
					$open = TRUE;
				}
				$res .= $tok;
				if ($tok{strlen($tok)-1} == "'" && $open) {
					$res .= '</font>';
					$open = FALSE;
				}
			} else if (is_numeric($tok)) {
				$res .= ' <font color="red">' . $tok . '</font>';
			} else if (in_array(strtoupper($tok), $words)) {
				$br = strlen($res) ? '<br>' : '';
				$strange = $tok == 'SELECT' ? '' : ' ';
				$res .= (!in_array($tok, $breakAfter)
						? ' ' . $br . str_repeat("&nbsp;", $level*4)
						: $strange);
				$res .= '<font color="blue">' . strtoupper($tok) . '</font>';
			} else {
				$res .= " " . $tok;
			}
			//print('toc: '.$tok.' ');
			$tok = strtok(" \n\t");
		}
		$res = trim($res);
		return new htmlString($res);
	}

	function getParameters() {
		if ($this->where) {
			$params = $this->where->getParameters();
		} else {
			$params = array();
		}
		if ($this->from instanceof SQLSubquery) {
			$params += $this->from->getParameters();
		}
		return $params;
	}

	/**
	 * A way to perform a query with parameter without making a SQL
	 */
	function perform() {
		return $this->db->perform($this->getQuery(), $this->getParameters());
	}

	function fetchAssoc() {
		return $this->db->fetchAssoc($this->perform());
	}

	function fetchAll() {
		return $this->db->fetchAll($this->perform());
	}

	public function unsetOrder() {
		$this->order = NULL;
	}

	public function injectDB($db) {
		$this->db = $db;
	}

}
