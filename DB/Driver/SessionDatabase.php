<?php

namespace nadlib;

use ArrayPlus;
use DBInterface;
use nadlib\HTTP\Session;
use RuntimeException;
use SQLSelectQuery;
use SQLWhere;

/**
 */
class SessionDatabase implements DBInterface
{

	/**
	 * @var ?static
	 */
	protected static $instance;

	/**
	 * @var array
	 */
	public $data = [];

	protected Session $session;

	/**
	 * @phpstan-consistent-constructor
	 */
	public function __construct()
	{
		$this->session = new Session(__CLASS__);
		$data = $this->session->getAll();
		foreach ($data as $table => $rows) {
			$this->data[$table] = $rows;
		}
	}

	public static function initialize()
	{
		if (!self::$instance) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function __destruct()
	{
		foreach ($this->data as $table => $rows) {
			$this->session->save($table, $rows);
		}
	}

	public function perform($query, array $params = [])
	{
		return $query;
	}

	public function numRows($res = null): void
	{
		if (is_string($res)) {
			debug($res);

		}
	}

	public function affectedRows($res = null): void
	{
		debug(__METHOD__);
	}

	public function getTables()
	{
		return array_keys($this->data);
	}

	public function lastInsertID($res, $table = null): void
	{
		debug(__METHOD__);
	}

	public function free($res): void
	{
		debug(__METHOD__);
	}

	public function quoteKey($key)
	{
		return $key;
	}

	public function quoteKeys(array $keys): array
	{
		return $keys;
	}

	public function escapeBool($value): void
	{
		debug(__METHOD__);
	}

	public function fetchAssoc($res): void
	{
		debug(__METHOD__);
	}

	public function transaction(): void
	{
		debug(__METHOD__);
	}

	public function commit(): void
	{
		debug(__METHOD__);
	}

	public function rollback(): void
	{
		debug(__METHOD__);
	}

	public function getScheme(): void
	{
		debug(__METHOD__);
	}

	public function getTablesEx(): void
	{
		debug(__METHOD__);
	}

	public function getTableColumnsEx($table): void
	{
		debug(__METHOD__);
	}

	public function getIndexesFrom($table): void
	{
		debug(__METHOD__);
	}

	public function dataSeek($resource, $index): void
	{
		debug(__METHOD__);
	}

	public function escape($string): void
	{
		debug(__METHOD__);
	}

	public function fetchAll($res_or_query, $index_by_key = null)
	{
		if ($res_or_query instanceof SQLSelectQuery) {
			$table = first($res_or_query->getFrom()->getAll());
			return $this->data[$table];
		} else {
			//throw new NotImplementedException(__METHOD__);
			debug($res_or_query);
		}

		return null;
	}

	public function isConnected(): bool
	{
		return true;
	}

	public function runInsertQuery($table, array $data): void
	{
//		debug('runInsertQuery', sizeof($this->data[$table]));
		$this->data[$table][] = $data;
//		debug('runInsertQuery', sizeof($this->data[$table]));
	}

	public function runUpdateQuery($table, array $set, array $where): void
	{
		$data = ArrayPlus::create($this->data[$table]);
		$data->filterBy($where);
		foreach ($data as $key => $row) {
			$this->data[$table][$key] = array_merge($this->data[$table][$key], $set);
		}
	}

	public function getSelectQuery($table, array $where, $orderBy = null): never
	{
//		return SQLSelectQuery::getSelectQueryP($this, $table, $where, $orderBy);
		throw new RuntimeException('Not implemented');
	}

	public function getSelectQuerySW($table, SQLWhere $where, $orderBy = '', $addSelect = ''): never
	{
//		return SQLSelectQuery::getSelectQueryP($this, $table, $where->getAsArray(), $orderBy);
		throw new RuntimeException('Not implemented');
	}

	public function getCount(SQLSelectQuery $query): int
	{
		$table = first($query->getFrom()->getAll());
		$where = $query->getWhere();
		if ($where->getAsArray()) {
			//throw new NotImplementedException(__METHOD__);
		}

		return count($this->data[$table]);
	}

	public function fetchOneSelectQuery($table, array $where)
	{
		$data = ArrayPlus::create($this->data[$table]);
		$data->filterBy($where);
		return $data->count() !== 0 ? $data->first() : null;
	}

	public function fetchAllSelectQuery($table, array $where = [])
	{
		$rows = $this->data[$table];
//		debug($table, $rows);
		if (!is_array($rows)) {
			$rows = [];
		}

		$data = ArrayPlus::create($rows);
		$data->filterBy($where);
		return $data;
	}

	public function createTable($table): void
	{
		if (!isset($this->data[$table])) {
			$this->data[$table] = [];
		}
	}

	public function getRowsIn($table): int
	{
		return count(ifsetor($this->data[$table], []));
	}

	public function hasData(): float|int
	{
		return array_reduce($this->data, function ($acc, array $rows): float|int {
			return $acc + count($rows);
		}, 0);
	}

	/**
	 * Don't set the whole data to [] because in this case session will not be updated.
	 */
	public function clearAll(): void
	{
		foreach ($this->data as $table => $_) {
			$this->data[$table] = [];
		}
	}

	public function quoteSQL($value, $key = null): void
	{
		// TODO: Implement quoteSQL() method.
	}

	public function clearQueryLog(): void
	{
		// TODO: Implement clearQueryLog() method.
	}

	public function getLastQuery(): void
	{
		// TODO: Implement getLastQuery() method.
	}

	public function getDSN(): string
	{
		return '';
	}

	public function getInfo(): array
	{
		return ['class' => get_class($this)];
	}

	public function getDatabaseName(): string
	{
		return get_class($this);
	}

	public function getVersion(): void
	{
		// TODO: Implement getVersion() method.
	}

	public function __call($name, $arguments)
	{
		// TODO: Implement @method  runDeleteQuery($table, array $where)
	}

	public function getPlaceholder($field): void
	{
		// TODO: Implement getPlaceholder() method.
	}

	public function fixRowDataTypes($res, array $row)
	{
		// TODO: Implement fixRowDataTypes() method.
	}

	public function getMoney($source = '$1,234.56'): float
	{
		return (float)$source;
	}

	public function getComment($table, $column)
	{
		// TODO: Implement getComment() method.
	}

	public function getForeignKeys(string $table)
	{
		// TODO: Implement getForeignKeys() method.
	}
}
