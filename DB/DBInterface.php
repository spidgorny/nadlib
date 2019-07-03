<?php

interface DBInterface {

	//function __construct();

	// parameters are different
	//function connect();

	public function perform($query, array $params = []);

	public function numRows($res = NULL);

	public function affectedRows($res = NULL);

	public function getTables();

	public function lastInsertID($res, $table = NULL);

	public function free($res);

	public function quoteKey($key);

	public function quoteKeys(array $keys);

	public function escapeBool($value);

	public function fetchAssoc($res);

	public function transaction();

	public function commit();

	public function rollback();

	public function getScheme();

	public function getTablesEx();

	public function getTableColumnsEx($table);

	public function getIndexesFrom($table);

	public function dataSeek($resource, $index);

	public function escape($string);

	public function fetchAll($res_or_query, $index_by_key = null);

	public function isConnected();

	/** @return array */
	public function getInfo();

}
