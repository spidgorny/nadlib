<?php

namespace nadlib;

use Nette\NotImplementedException;

class SessionDatabase implements \DBInterface {

	/**
	 * @var \Session
	 */
	protected $session;

	/**
	 * @var array
	 */
	public $data = [];

	static function initialize()
	{
		$sdb = new SessionDatabase();
		return $sdb;
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
		debug(__METHOD__);
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
		$this->data[$table][] = $data;
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

	function createTable($table)
	{
		$this->data[$table] = [];
	}

	public function getRowsIn($table)
	{
		return count(ifsetor($this->data[$table], []));
	}

}
