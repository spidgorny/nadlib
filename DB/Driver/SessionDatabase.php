<?php

namespace nadlib;

use Nette\NotImplementedException;
use SQLBuilder;

/**
 * @method  fetchSelectQuery($table, $where = [], $order = '', $addFields = '', $idField = null)
 * @method  describeView($viewName)
 * @method  getFirstValue($query)
 * @method  performWithParams($query, $params)
 * @method  getInfo()
 * @method  getConnection()
 * @method  getViews()
 */
class SessionDatabase implements \DBInterface
{

	/**
	 * @var \Session
	 */
	protected $session;

	/**
	 * @var array
	 */
	public $data = [];

	/**
	 * @var static
	 */
	static protected $instance;

	static function initialize()
	{
		if (!self::$instance) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	function __construct()
	{
		$this->session = new \Session(__CLASS__);
		$data = $this->session->getAll();
		foreach ($data as $table => $rows) {
			$this->data[$table] = $rows;
		}
	}

	function __destruct()
	{
		foreach ($this->data as $table => $rows) {
			$this->session->save($table, $rows);
		}
	}

	function perform($query, array $params = [])
	{
		return $query;
	}

	function numRows($res = NULL)
	{
		if (is_string($res)) {
			debug($res);

		}
	}

	function affectedRows($res = NULL)
	{
		debug(__METHOD__);
	}

	function getTables()
	{
		return array_keys($this->data);
	}

	function lastInsertID($res, $table = NULL)
	{
		debug(__METHOD__);
	}

	function free($res)
	{
		debug(__METHOD__);
	}

	function quoteKey($key)
	{
		return $key;
	}

	function quoteKeys(array $keys)
	{
		return $keys;
	}

	function escapeBool($value)
	{
		debug(__METHOD__);
	}

	function fetchAssoc($res)
	{
		debug(__METHOD__);
	}

	function transaction()
	{
		debug(__METHOD__);
	}

	function commit()
	{
		debug(__METHOD__);
	}

	function rollback()
	{
		debug(__METHOD__);
	}

	public function getScheme()
	{
		debug(__METHOD__);
	}

	function getTablesEx()
	{
		debug(__METHOD__);
	}

	function getTableColumnsEx($table)
	{
		debug(__METHOD__);
	}

	function getIndexesFrom($table)
	{
		debug(__METHOD__);
	}

	function dataSeek($resource, $index)
	{
		debug(__METHOD__);
	}

	function escape($string)
	{
		debug(__METHOD__);
	}

	function fetchAll($res_or_query, $index_by_key = NULL)
	{
		if ($res_or_query instanceof \SQLSelectQuery) {
			$table = first($res_or_query->getFrom()->getAll());
			return $this->data[$table];
		} else {
			//throw new NotImplementedException(__METHOD__);
			debug($res_or_query);
		}
	}

	function isConnected()
	{
		return true;
	}

	function runInsertQuery($table, array $data)
	{
//		debug('runInsertQuery', sizeof($this->data[$table]));
		$this->data[$table][] = $data;
//		debug('runInsertQuery', sizeof($this->data[$table]));
	}

	function runUpdateQuery($table, array $set, array $where)
	{
		$data = \ArrayPlus::create($this->data[$table]);
		$data->filterBy($where);
		foreach ($data as $key => $row) {
			$this->data[$table][$key] = array_merge($this->data[$table][$key], $set);
		}
	}

	function getSelectQuery($table, array $where, $orderBy = null)
	{
		return \SQLSelectQuery::getSelectQueryP($this, $table, $where, $orderBy);
	}

	function getSelectQuerySW($table, \SQLWhere $where, $orderBy = null)
	{
		return \SQLSelectQuery::getSelectQueryP($this, $table, $where->getAsArray(), $orderBy);
	}

	function getCount(\SQLSelectQuery $query)
	{
		$table = first($query->getFrom()->getAll());
		$where = $query->getWhere();
		if ($where->getAsArray()) {
			//throw new NotImplementedException(__METHOD__);
		}
		return count($this->data[$table]);
	}

	function fetchOneSelectQuery($table, array $where)
	{
		$data = \ArrayPlus::create($this->data[$table]);
		$data->filterBy($where);
		return $data->count() ? $data->first() : null;
	}

	function fetchAllSelectQuery($table, array $where = [])
	{
		$rows = $this->data[$table];
//		debug($table, $rows);
		if (!is_array($rows)) {
			$rows = [];
		}
		$data = \ArrayPlus::create($rows);
		$data->filterBy($where);
		return $data;
	}

	function createTable($table)
	{
		if (!isset($this->data[$table])) {
			$this->data[$table] = [];
		}
	}

	public function getRowsIn($table)
	{
		return count(ifsetor($this->data[$table], []));
	}

	function hasData()
	{
		$totalRows = array_reduce($this->data, function ($acc, array $rows) {
			return $acc + sizeof($rows);
		}, 0);
		return $totalRows;
	}

	/**
	 * Don't set the whole data to [] because in this case session will not be updated.
	 */
	public function clearAll()
	{
		foreach ($this->data as $table => $_) {
			$this->data[$table] = [];
		}
	}

	public function quoteSQL($value, $key = null)
	{
		// TODO: Implement quoteSQL() method.
	}

	public function clearQueryLog()
	{
		// TODO: Implement clearQueryLog() method.
	}

	public function getLastQuery()
	{
		// TODO: Implement getLastQuery() method.
	}

	public function __call($name, $arguments)
	{
		// TODO: Implement @method  fetchSelectQuery($table, $where = [], $order = '', $addFields = '', $idField = null)
		// TODO: Implement @method  describeView($viewName)
		// TODO: Implement @method  getFirstValue($query)
		// TODO: Implement @method  performWithParams($query, $params)
		// TODO: Implement @method  getInfo()
		// TODO: Implement @method  getConnection()
		// TODO: Implement @method  getViews()
	}
}
