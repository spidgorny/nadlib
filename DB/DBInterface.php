<?php

interface DBInterface
{

	//function __construct();

	// parameters are different
	//function connect();

	function perform($query, array $params = []);

	function numRows($res = NULL);

	function affectedRows($res = NULL);

	function getTables();

	function lastInsertID($res, $table = NULL);

	function free($res);

	function quoteKey($key);

	function quoteKeys(array $keys);

	function escapeBool($value);

	function fetchAssoc($res);

	function transaction();

	function commit();

	function rollback();

	public function getScheme();

	function getTablesEx();

	function getTableColumnsEx($table);

	function getIndexesFrom($table);

	function dataSeek($resource, $index);

	function escape($string);

	function fetchAll($res_or_query, $index_by_key = NULL);

	function isConnected();

}
