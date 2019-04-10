<?php

/**
 * Interface DBInterface
 * @mixin SQLBuilder
 * @method fetchSelectQuery($table, $where = [], $order = '', $addFields = '', $idField = null)
 * @method fetchOneSelectQuery($table, $where = [], $order = '', $selectPlus = '')
 * @method describeView($viewName)
 * @method fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = null)
 * @method getFirstValue($query)
 * @method runUpdateQuery($table, array $columns, array $where, $orderBy = '')
 * @method performWithParams($query, $params)
 * @method getInfo();
 * @method getConnection();
 * @method getViews();
 * @method runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 */
interface DBInterface
{

	public function perform($query, array $params = []);

	public function numRows($res = null);

	public function affectedRows($res = null);

	public function getTables();

	public function lastInsertID($res, $table = null);

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

	public function quoteSQL($value, $key = null);

	public function clearQueryLog();

	public function getLastQuery();

}
