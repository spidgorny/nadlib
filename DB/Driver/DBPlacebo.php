<?php

/**
 * Class dbPlacebo
 * @mixin SQLBuilder
 * @method  describeView($viewName)
 * @method  fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = null)
 * @method  getFirstValue($query)
 * @method  performWithParams($query, $params)
 * @method  getInfo()
 * @method  getConnection()
 * @method  getViews()
 */
class DBPlacebo extends DBLayerBase implements DBInterface
{
	public $lastQuery;

	public function __construct()
	{
		// recursion:
		//$this->qb = Config::getInstance()->getQb();
	}

	public function perform($query, array $params = [])
	{
		return '';
	}

	public function fetchOptions($a)
	{
		return '';
	}

	public function fetchAll($res_or_query, $index_by_key = null)
	{
		return [];
	}

	public function __call($method, array $params)
	{
		if (method_exists($this->qb, $method)) {
			return call_user_func_array([$this->qb, $method], $params);
		} else {
			debug(typ($this->qb));
			throw new Exception($method . ' not found in dbPlacebo and SQLBuilder');
		}
	}

	public function numRows($res = null)
	{
		// TODO: Implement numRows() method.
	}

	public function affectedRows($res = null)
	{
		// TODO: Implement affectedRows() method.
	}

	public function getTables()
	{
		// TODO: Implement getTables() method.
	}

	public function lastInsertID($res = null, $table = null)
	{
		// TODO: Implement lastInsertID() method.
	}

	public function free($res)
	{
		// TODO: Implement free() method.
	}

	public function quoteKey($key)
	{
		return $key;
	}

	public function escape($string)
	{
		return $string;
	}

	public function escapeBool($value)
	{
		// TODO: Implement escapeBool() method.
	}

	public function fetchAssoc($res)
	{
		// TODO: Implement fetchAssoc() method.
	}

	public function transaction()
	{
		// TODO: Implement transaction() method.
	}

	public function commit()
	{
		// TODO: Implement commit() method.
	}

	public function rollback()
	{
		// TODO: Implement rollback() method.
	}

	public function getScheme()
	{
		// TODO: Implement getScheme() method.
	}

	public function getTablesEx()
	{
		// TODO: Implement getTablesEx() method.
	}

	public function getTableColumnsEx($table)
	{
		// TODO: Implement getTableColumnsEx() method.
	}

	public function getIndexesFrom($table)
	{
		// TODO: Implement getIndexesFrom() method.
	}

	public function fetchOneSelectQuery($table, $where = [], $order = '', $selectPlus = '')
	{
		$query = $this->getSelectQuery($table, $where, $order, $selectPlus);
		$this->lastQuery = $query;
		return [];
	}

	public function getPlaceholder()
	{
		return '?';
	}
}
