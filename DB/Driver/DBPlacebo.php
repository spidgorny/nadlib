<?php

/**
 * Class dbPlacebo
 * @mixin SQLBuilder
 */
class DBPlacebo extends DBLayerBase
{
	public $lastQuery;

	public array $queries = [];

	/**
	 * @var array
	 */
	protected $returnNextTime = [];

	protected $insertedRow = [];

	protected array $queuedResults = [];

	protected int $nextInsertId = 1000;

	protected int $lastInsertId = 0;

	public function __construct()
	{
		$this->qb = new SQLBuilder($this);
	}

	protected function normalizeQuery(string $query): string
	{
		return trim((string) preg_replace('/\s+/', ' ', $query));
	}

	protected function recordQuery($query)
	{
		$this->queries[] = $this->normalizeQuery((string)$query);
		$this->lastQuery = $query;
		return $query;
	}

	protected function normalizeColumns(array $columns): array
	{
		foreach ($columns as $key => $value) {
			if (is_bool($value)) {
				$columns[$key] = new AsIs($value ? 'true' : 'false');
			}
		}

		return $columns;
	}

	protected function dequeueResult(): mixed
	{
		if ($this->queuedResults !== []) {
			return array_shift($this->queuedResults);
		}

		$return = $this->returnNextTime;
		$this->returnNextTime = [];
		return $return;
	}

	public function clearQueries(): void
	{
		$this->queries = [];
	}

	public function queueResult(mixed $result): void
	{
		$this->queuedResults[] = $result;
	}

	public static function getFirstWord($asd)
	{
		return $asd;
	}

	public function perform($query, array $params = []): string
	{
		$this->recordQuery($query);
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
		return $this->lastInsertId;
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

	public function escapeBool($value)
	{
		return $value ? 'true' : 'false';
	}

	public function fetchAssoc($res, array $args = [])
	{
		return $this->dequeueResult();
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
		return self::class . '://';
	}

	public function getTablesEx(): array
	{
		return [];
	}

	public function getTableColumnsEx($table): array
	{
		return [];
	}

	public function getIndexesFrom($table): array
	{
		return [];
	}

	public function fetchOneSelectQuery($table, array $where = [], $order = '', $selectPlus = '')
	{
		$this->getSelectQuery($table, $where, $order, $selectPlus);
		return $this->dequeueResult();
	}

	public function getSelectQuery($table, array $where = [], $order = '', $selectPlus = '')
	{
		$query = $this->qb->getSelectQuery($table, $where, $order, $selectPlus);
		return $this->recordQuery($query);
	}

	public function getInsertQuery($table, array $columns, array $where = []): string
	{
		$query = $this->qb->getInsertQuery($table, $this->normalizeColumns($columns), $where);
		return $this->recordQuery($query);
	}

	public function getUpdateQuery($table, $columns, array $where, string $orderBy = ''): string
	{
		return $this->qb->getUpdateQuery($table, $this->normalizeColumns($columns), $where, $orderBy);
	}

	public function fetchAll($res_or_query, $index_by_key = null): array
	{
		$return = $this->dequeueResult();
		return is_array($return) ? $return : [];
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
			$columns['id'] = $this->nextInsertId++;
		}

		$this->lastInsertId = $columns['id'];
		$this->insertedRow = $columns;
		array_unshift($this->queuedResults, $columns);
	}

	public function getVersion(): void
	{
		// TODO: Implement getVersion() method.
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
