<?php

/**
 * Class DBLayerLogger
 * @mixin SQLBuilder
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  getSelectQuery(string $table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 * @method  runInsertUpdateQuery($table, array $fields, array $where, array $insert = [])
 */
class DBLayerLogger extends DBLayerBase implements DBInterface
{

	public $logger;

	public $data = [];

	/** @noinspection MagicMethodsValidityInspection */
	public function __construct()
	{
		// recursion:
		//$this->qb = Config::getInstance()->getQb();
	}

	public function perform($query, array $params = []): string
	{
		$this->log($query);
		return '';
	}

	public function log($query): void
	{
		if ($this->logger) {
			$this->logger->log($query);
		} else {
			echo '>> ', $query, BR;
		}
	}

	public function fetchOptions($a): string
	{
		$this->log(__METHOD__);
		return '';
	}

	public function fetchAll($res_or_query, $index_by_key = null): array
	{
		$this->log(__METHOD__);
		return [];
	}

	public function __call($method, array $params)
	{
		if (!$this->qb) {
			throw new Exception(get_called_class() . ' does not have QB');
		}

		if (method_exists($this->qb, $method)) {
			$this->log($method);
			return call_user_func_array([$this->qb, $method], $params);
		} else {
			throw new Exception($method . ' not found in ' . get_called_class() . ' and SQLBuilder');
		}
	}

	public function numRows($res = null): void
	{
		$this->log(__METHOD__);
	}

	public function affectedRows($res = null): void
	{
		$this->log(__METHOD__);
	}

	public function getTables(): void
	{
		$this->log(__METHOD__);
	}

	public function lastInsertID($res = null, $table = null)
	{
		$id = $this->data['id'];
		$this->log(__METHOD__ . ' id: ' . $id);
		return $id;
	}

	public function free($res): void
	{
		$this->log(__METHOD__);
	}

	public function quoteKey($key): void
	{
		$this->log(__METHOD__);
	}

	public function escape($string)
	{
		return $string;
	}

	public function escapeBool($value): void
	{
		$this->log(__METHOD__);
	}

	public function fetchAssoc($res): void
	{
		$this->log(__METHOD__);
	}

	public function transaction(): void
	{
		$this->log(__METHOD__);
	}

	public function commit(): void
	{
		$this->log(__METHOD__);
	}

	public function rollback(): void
	{
		$this->log(__METHOD__);
	}

	public function getScheme(): string
	{
		$this->log(__METHOD__);
		return '';
	}

	public function getTablesEx(): void
	{
		$this->log(__METHOD__);
	}

	public function getTableColumnsEx($table): void
	{
		$this->log(__METHOD__);
	}

	public function getIndexesFrom($table): void
	{
		$this->log(__METHOD__);
	}

	public function fetchOneSelectQuery($table, $where = [], $order = '', $selectPlus = '')
	{
//		$this->log(__METHOD__);
		return $this->data;
	}

	public function getInsertQuery($table, array $columns): string
	{
		$this->log(__METHOD__);
		$this->data = $columns;
		if (!ifsetor($this->data['id'])) {
			$id = random_int(1, 100);
			$this->data['id'] = $id;
			debug($this->data);
		}

		return $this->qb->getInsertQuery($table, $columns);
	}

	public function getVersion(): void
	{
		// TODO: Implement getVersion() method.
	}

	public function getPlaceholder($field): void
	{
		// TODO: Implement getPlaceholder() method.
	}
}
