<?php

class DBLayerJSON extends DBLayerBase implements DBInterface {

	var $folderName;

	var $tables = [];

	var $currentQuery;

	function __construct($folderName)
	{
		$this->folderName = $folderName;
	}

	/**
	 * @param $name
	 * @return DBLayerJSONTable
	 */
	function getTable($name)
	{
		if (!isset($this->tables[$name])) {
			$this->tables[$name] = new DBLayerJSONTable(cap($this->folderName).$name.'.json');
		}
		return $this->tables[$name];
	}

	function fetchAll($res_or_query, $index_by_key = NULL)
	{
		if ($res_or_query instanceof DBLayerJSONTable) {
			return $res_or_query->fetchAll($res_or_query, $index_by_key);
		} else {
			debug($res_or_query, $this->currentQuery);
			$table = $this->extractTable($res_or_query ?: $this->currentQuery);
			if ($table) {
				$t = $this->getTable($table);
				return $t->fetchAll($res_or_query, $index_by_key);
			} else {
				debug($res_or_query);
				throw new InvalidArgumentException('Unable to find table name after FROM in SQL');
			}
		}
	}

	function extractTable($res_or_query)
	{
		$del = " \n\t";
		$tokens = [];
		for ($tok = strtok($res_or_query, $del); $tok !== false; $tok = strtok($del)) {
			$tokens[] = $tok;
		}
		$iFROM = array_search('FROM', $tokens);
		$table = ifsetor($tokens[$iFROM+1]);
		return $table;
	}

	function fetchAssoc($res)
	{
		if ($res instanceof DBLayerJSONTable) {
			return $res->fetchAssoc($res);
		} else {
			throw new InvalidArgumentException('fetchAssoc needs to have reference to the DBLayerJSONTable');
		}
	}

	function numRows($res = NULL)
	{
		//debug(gettype2($res));
		if (!($res instanceof DBLayerJSONTable)) {
			$table = $this->extractTable($res);
			if ($table) {
				$t = $this->getTable($table);
			} else {
				throw new InvalidArgumentException(__METHOD__);
			}
		} else {
			/** @var DBLayerJSONTable $t */
			$t = $res;
		}
		return $t->numRows($res);
	}

	function runInsertQuery($table, array $data)
	{
		$t = $this->getTable($table);
		return $t->runInsertQuery($table, $data);
	}

	function runUpdateQuery($table, array $data, array $where)
	{
		$t = $this->getTable($table);
		return $t->runUpdateQuery($table, $data, $where);
	}

	function getSelectQuery($table, array $where = array(), $order = '', $addSelect = NULL)
	{
		$query = parent::getSelectQuery($table, $where, $order, $addSelect);
		$this->currentQuery = $query;
		return $query;
	}

	function runSelectQuery($table, array $where = array(), $order = '', $addSelect = '')
	{
		$res = parent::runSelectQuery($table, $where, $order, $addSelect);
		$t = $this->getTable($table);
		return $t;
	}

	function __call($method, array $params)
	{
//		echo $method, BR;
		return parent::__call($method, $params);
	}

	function perform($query, array $params = [])
	{
		$this->currentQuery = $query;
		parent::perform($query, $params);
		$table = $this->extractTable($query);
		return $this->getTable($table);
	}

}
