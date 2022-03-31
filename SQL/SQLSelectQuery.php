<?php

class SQLSelectQuery extends SQLWherePart
{

	/**
	 * @var dbLayerBase|dbLayer|MySQL|dbLayerPDO
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

	function __construct($select = NULL, $from = NULL, $where = NULL, $join = NULL, $group = NULL, $having = NULL, $order = NULL, $limit = NULL)
	{
		if ($select) $this->setSelect($select);
		if ($from) $this->setFrom($from);
		if ($where) $this->setWhere($where);
		if ($join) $this->setJoin($join);
		else    $this->join = new SQLJoin();
		if ($group) $this->setGroup($group);
		if ($having) $this->setHaving($having);
		if ($order) $this->setOrder($order);
		if ($limit) $this->setLimit($limit);
	}

	function injectDB(DBInterface $db)
	{
		//debug(__METHOD__, gettype2($db));
		$this->db = $db;
	}

	function setSelect(SQLSelect $select)
	{
		$this->select = $select;
	}

	function setFrom(SQLFrom $from)
	{
		$this->from = $from;
	}

	function setWhere(SQLWhere $where)
	{
		$this->where = $where;
	}

	function setJoin(SQLJoin $join)
	{
		$this->join = $join;
	}

	function setGroup(SQLGroup $group)
	{
		$this->group = $group;
	}

	function setHaving(SQLHaving $having)
	{
		$this->having = $having;
	}

	function setOrder(SQLOrder $order)
	{
		$this->order = $order;
	}

	function setLimit(SQLLimit $limit)
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
		} else {
			return "( 6371 * acos( cos( radians($lat) ) * cos( radians( $latitude ) )
			* cos( radians( $longitude ) - radians($lon) ) + sin( radians($lat) ) * sin(radians($latitude)) ) ) AS distance";
		}
	}

	function getQuery()
	{
		$from = ($this->from);
		$query = trim("SELECT
{$this->select}
FROM {$from}
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

	function __toString()
	{
		try {
			return $this->getQuery();
		} catch (Exception $e) {
			echo '<strong>', $e->getMessage(), '</strong>', BR;
			//echo '<strong>', $e->getPrevious()->getMessage(), '</strong>', BR;
			pre_print_r($e->getTraceAsString());
		}
	}

	static function sqlSH($sql)
	{
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
				$res .= " (" . "<br>" . str_repeat("&nbsp;", $level * 4);
			} else if ($tok == ")") {
				if ($level > 0) {
					$level--;
				}
				$res .= "<br>" . str_repeat("&nbsp;", $level * 4) . ") ";
			} elseif ($tok && ($tok[0] == "'" || $tok[strlen($tok) - 1] == "'" || $tok == "'")) {
				$res .= " ";
				if ($tok[0] == "'" && !$open) {
					$res .= '<font color="green">';
					$open = TRUE;
				}
				$res .= $tok;
				if ($tok[strlen($tok) - 1] == "'" && $open) {
					$res .= '</font>';
					$open = FALSE;
				}
			} else if (is_numeric($tok)) {
				$res .= ' <font color="red">' . $tok . '</font>';
			} else if (in_array(strtoupper($tok), $words)) {
				$br = strlen($res) ? '<br>' : '';
				$strange = $tok == 'SELECT' ? '' : ' ';
				$res .= (!in_array($tok, $breakAfter)
					? ' ' . $br . str_repeat("&nbsp;", $level * 4)
					: $strange);
				$res .= '<font color="blue">' . strtoupper($tok) . '</font>';
			} else {
				$res .= " " . $tok;
			}
			//print('toc: '.$tok.' ');
			$tok = strtok(" \n\t");
		}
		$res = trim($res);
		$res = str_replace("(<br><br>)", '()', $res);
		$res = str_replace("(<br>&nbsp;&nbsp;&nbsp;&nbsp;<br>)", '()', $res);
		return new htmlString($res);
	}

	function getParameters()
	{
		if ($this->where) {
			$params = $this->where->getParameters();
		} else {
			$params = array();
		}
		if ($this->from instanceof SQLSubquery) {
			$subParams = $this->from->getParameters();
//			debug($subParams);
			$params += $subParams;
		}
		return $params;
	}

	/**
	 * A way to perform a query with parameter without making a SQL
	 */
	function perform()
	{
		$sQuery = $this->getQuery();
		$aParams = $this->getParameters();
//		debug(['where' => $this->where, 'sql' => $sQuery, 'params' => $aParams]);
		return $this->db->perform($sQuery, $aParams);
	}

	function fetchAssoc()
	{
		return $this->db->fetchAssoc($this->perform());
	}

	function fetchAll()
	{
		return $this->db->fetchAll($this->perform());
	}

	public function unsetOrder()
	{
		$this->order = NULL;
	}

}
