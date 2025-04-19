<?php

/**
 * Class dbPlacebo
 * @mixin SQLBuilder
 * @method  describeView($viewName)
 * @method  fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = null)
 * @method  getFirstValue($query)
 * @method  performWithParams($query, $params)
 * @method  getConnection()
 * @method  getViews()
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 */
class DBPlacebo extends DBLayerBase
{
	public $lastQuery;

	/**
	 * @var array
	 */
	protected $returnNextTime = [];

	protected $insertedRow = [];

	public function __construct()
	{
		//llog(__METHOD__, Debug::getCaller());
		// recursion:
		//$this->qb = Config::getInstance()->getQb();
	}

	public static function getFirstWord($asd)
	{
		return $asd;
	}

	public function perform($query, array $params = []): string
	{
		return '';
	}

	public function fetchOptions($a): string
	{
		return '';
	}

	public function __call($method, array $params)
	{
		if (method_exists($this->qb, $method)) {
			return call_user_func_array([$this->qb, $method], $params);
		} else {
//			debug(typ($this->qb));
			throw new RuntimeException($method . ' not found in dbPlacebo and SQLBuilder');
		}
	}

	public function numRows($res = null): int
	{
		return count($this->returnNextTime);
	}

	public function affectedRows($res = null): void
	{
		// TODO: Implement affectedRows() method.
	}

	public function getTables(): void
	{
		// TODO: Implement getTables() method.
	}

	public function lastInsertID($res = null, $table = null): int
	{
		return rand();
	}

	public function free($res): void
	{
		// TODO: Implement free() method.
	}

	public function quoteKey($key): string
	{
		return '"' . $key . '"';
	}

	public function escape($string)
	{
		return $string;
	}

	public function escapeBool($value): void
	{
		// TODO: Implement escapeBool() method.
	}

	public function fetchAssoc($res)
	{
		$return = $this->returnNextTime;
		$this->returnNextTime = [];
		return $return;
	}

	public function transaction(): void
	{
		// TODO: Implement transaction() method.
	}

	public function commit(): void
	{
		// TODO: Implement commit() method.
	}

	public function rollback(): void
	{
		// TODO: Implement rollback() method.
	}

	public function getScheme(): string
	{
		return get_class($this) . '://';
	}

	public function getTablesEx(): void
	{
		// TODO: Implement getTablesEx() method.
	}

	public function getTableColumnsEx($table): void
	{
		// TODO: Implement getTableColumnsEx() method.
	}

	public function getIndexesFrom($table): void
	{
		// TODO: Implement getIndexesFrom() method.
	}

	public function fetchOneSelectQuery($table, array $where = [], $order = '', $selectPlus = '')
	{
		$query = $this->getSelectQuery($table, $where, $order, $selectPlus);
		$this->lastQuery = $query;
		return $this->fetchAll(null);
	}

	public function getSelectQuery($table, array $where = [], $order = '', $selectPlus = '')
	{
		$query = $this->qb->getSelectQuery($table, $where, $order, $selectPlus);
		$this->lastQuery = $query;
		return $query;
	}

	public function fetchAll($res_or_query, $index_by_key = null): void
	{
		$return = $this->returnNextTime;
		//debug(__METHOD__, typ($this), $return);
		$this->returnNextTime = [];
		return $return;
	}

	public function getSelectQuerySW($table, SQLWhere $where, $order = '', $selectPlus = '')
	{
		$query = $this->getSelectQuery($table, [
			new AsIsOp($where->__toString()),
		], $order, $selectPlus);
		$this->lastQuery = $query;
		return $query;
	}

	public function getPlaceholder($field): string
	{
		return '?';
	}

	public function getInfo(): array
	{
		return ['class' => get_class($this)];
	}

	public function returnNextTime(array $rows): void
	{
		$this->returnNextTime = $rows;
	}

	public function runInsertQuery($table, array $columns): void
	{
		if (!ifsetor($columns['id'])) {
			$columns['id'] = rand();
		}

		$this->insertedRow = $columns;
		$this->returnNextTime = $columns;
	}

	public function getVersion(): void
	{
		// TODO: Implement getVersion() method.
	}
}
