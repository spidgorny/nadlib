<?php

/**
 * Class dbPlacebo
 * @mixin SQLBuilder
 */
class DBPlacebo extends DBLayerBase implements DBInterface {

	var $lastQuery;

	function __construct()
	{
		// recursion:
		//$this->qb = Config::getInstance()->getQb();
	}

	function perform($query, array $params = [])
	{
		return '';
	}

	function fetchOptions($a)
	{
		return '';
	}

	function fetchAll($res_or_query, $index_by_key = NULL)
	{
		return array();
	}

	function __call($method, array $params)
	{
		if (method_exists($this->qb, $method)) {
			return call_user_func_array(array($this->qb, $method), $params);
		} else {
			debug(typ($this->qb));
			throw new Exception($method . ' not found in dbPlacebo and SQLBuilder');
		}
	}

	function numRows($res = NULL)
	{
		// TODO: Implement numRows() method.
	}

	function affectedRows($res = NULL)
	{
		// TODO: Implement affectedRows() method.
	}

	function getTables()
	{
		// TODO: Implement getTables() method.
	}

	function lastInsertID($res = NULL, $table = NULL)
	{
		// TODO: Implement lastInsertID() method.
	}

	function free($res)
	{
		// TODO: Implement free() method.
	}

	function quoteKey($key)
	{
		// TODO: Implement quoteKey() method.
	}

	function escape($string)
	{
		return $string;
	}

	function escapeBool($value)
	{
		// TODO: Implement escapeBool() method.
	}

	function fetchAssoc($res)
	{
		// TODO: Implement fetchAssoc() method.
	}

	function transaction()
	{
		// TODO: Implement transaction() method.
	}

	function commit()
	{
		// TODO: Implement commit() method.
	}

	function rollback()
	{
		// TODO: Implement rollback() method.
	}

	public function getScheme()
	{
		// TODO: Implement getScheme() method.
	}

	function getTablesEx()
	{
		// TODO: Implement getTablesEx() method.
	}

	function getTableColumnsEx($table)
	{
		// TODO: Implement getTableColumnsEx() method.
	}

	function getIndexesFrom($table)
	{
		// TODO: Implement getIndexesFrom() method.
	}

	function fetchOneSelectQuery($table, $where = array(), $order = '', $selectPlus = '')
	{
		return array();
	}

	function getPlaceholder()
	{
		return '?';
	}

}
