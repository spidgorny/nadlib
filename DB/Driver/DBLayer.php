<?php

use PgSql\Connection;
use PgSql\Result;

/**
 * Class dbLayer
 * @mixin SQLBuilder
 * @method  fetchOneSelectQuery($table, $where = [], $order = '', $selectPlus = '')
 * @method  fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = null)
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 */
class DBLayer extends DBLayerBase
{

	/**
	 * @var Connection|resource
	 */
	public $connection;

	/** @var Result */
	public $lastResult;

	/**
	 * todo: use setter & getter method
	 *
	 * contains query builder class used as mixin.
	 */
	public $qb;

	public $AFFECTED_ROWS;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * @var string DB name
	 */
	public $db;

	public $reserved = [
		'SELECT', 'LIKE', 'TO',
	];

	public $dbName;

	public $host;

	public $port;

	/**
	 * @var MemcacheArray
	 */
	protected $mcaTableColumns;

	/**
	 * Transaction count because three are no nested transactions
	 * @var int
	 */
	protected $inTransaction = 0;

	protected $user;

	protected $pass;

	protected $lastBacktrace;

	/**
	 * @param string $dbName
	 * @param string $user
	 * @param string $pass
	 * @param string $host
	 * @throws Exception
	 */
	public function __construct($dbName = null, $user = null, $pass = null, $host = "localhost", $port = 5432)
	{
		$this->dbName = $dbName;
		$this->user = $user;
		$this->pass = $pass;
		$this->host = $host;
		$this->port = $port;
		//			$this->connect($dbName, $user, $pass, $host);
		if ($dbName && ($this->isConnected() && $this->getVersion() >= 8.4)) {
			$query = "select * from pg_get_keywords() WHERE catcode IN ('R', 'T')";
			$words = $this->fetchAll($query, 'word');
			$this->reserved = array_keys($words);
			$this->reserved = array_map('strtoupper', $this->reserved);
			// important
		}

		$this->queryLog = new QueryLog();
	}

	public function isConnected(): bool
	{
		return (bool)$this->connection
			&& pg_connection_status($this->connection) === PGSQL_CONNECTION_OK;
	}

	public function getVersion(): int|string|null
	{
		$version = pg_version($this->getConnection());
		return $version['server'];
	}

	public function getConnection()
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		return $this->connection;
	}

	/**
	 * @throws MustBeStringException
	 * @throws DatabaseException
	 * @throws JsonException
	 */
	public function connect(): ?bool
	{
		if ($this->isConnected()) {
			return null;
		}

		$string = sprintf('host=%s port=%s dbname=%s user=%s password=%s', $this->host, $this->port, $this->dbName, $this->user, $this->pass);
//		llog('pg_connect', $string);
		$this->connection = pg_connect($string);
		if (!$this->connection) {
			throw new DatabaseException(sprintf('No PostgreSQL connection to %s. ', $this->host) . json_encode(error_get_last(), JSON_THROW_ON_ERROR));
			//printbr('Error: '.pg_errormessage());	// Warning: pg_errormessage(): No PostgreSQL link opened yet
		}

		$this->perform("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;");
		//print(pg_client_encoding($this->getConnection()));
		return true;
	}

	/**
	 * @param string $query
	 * @return Result|null|resource
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function perform($query, array $params = [])
	{
		$this->connect();
		$prof = new Profiler();

//		$this->reportIfLastQueryFailed();
		$this->lastQuery = $query;
		if (!$this->getConnection()) {
			throw new DatabaseException('No connection', 0, null, $query);
		}

		if ($query instanceof SQLSelectQuery) {
			$params = $query->getParameters();
			$query = $query->__toString();
		}

		if ($params !== []) {
			$ok = pg_prepare($this->getConnection(), '', $query);
			if (!$ok) {
				throw new DatabaseException('Query can not be prepared because: ' . pg_last_error($this->getConnection()), 1, null, $query);
			}

			$this->lastResult = pg_execute($this->getConnection(), '', $params);
		} else {
			$this->lastResult = pg_query($this->getConnection(), $query);
			$lastError = pg_last_error($this->getConnection());
			if ($lastError !== '' && $lastError !== '0') {
				// setQuery will be called in the catch below
				throw new DatabaseException($lastError, 2, null, $query);
			}
		}

		$this->queryTime = $prof->elapsed();
		if ($this->logToLog) {
			llog($query . '' . ' => ' . $this->lastResult);
		}

//			$this->reportIfLastQueryFailed();


		if (!$this->lastResult) {
			//debug_pre_print_backtrace();
			//debug($query);
			//debug($this->queryLog->queryLog);
			throw new DatabaseException(pg_last_error($this->getConnection()), 3, null, $query);
		}

		$this->AFFECTED_ROWS = pg_affected_rows($this->lastResult);
		if ($this->queryLog) {
			$this->queryLog->log($query, $prof->elapsed(), $this->AFFECTED_ROWS, $this->lastResult);
		}

		$this->logQuery($query);    // uses $this->queryTime

		$this->lastQuery = $query;
		$this->queryCount++;
		$this->lastBacktrace = debug_backtrace();
		return $this->lastResult;
	}

	/**
	 * Overrides because of pg_fetch_all
	 * @param resource|string $result
	 * @throws Exception
	 */
	public function fetchAll($result, $key = null): array
	{
		$params = [];
		if ($result instanceof SQLSelectQuery) {
			/** @var SQLSelectQuery $queryObj */
			$queryObj = $result;
			$result = $queryObj->getQuery();
			$params = $queryObj->getParameters();
		}

		if (is_string($result)) {
			//debug($result);
			$result = $this->perform($result, $params);
		}

		//debug($this->numRows($result));
		$res = pg_fetch_all($result);
		pg_free_result($result);
		if (ifsetor($_REQUEST['d']) === 'q') {
			debug($this->lastQuery, count($res));
		}

		if ($res === []) {
			$res = [];
		} elseif ($key) {
			$ap = ArrayPlus::create($res)->IDalize($key)->getData();
			//debug(sizeof($res), sizeof($ap));
			$res = $ap;
		}

		return $res;
	}

	/**
	 * http://www.postgresql.org/docs/9.3/static/datatype-money.html
	 * @param string $source
	 */
	public static function getMoney($source = '$1,234.56'): float
	{
		$source = str_replace('$', '', $source);
		$source = str_replace(',', '', $source);
		return (float)$source;
	}

	public function reconnect(): void
	{
		$this->connect();
	}

	public function reportIfLastQueryFailed(): void
	{
		if (false === $this->lastResult) {
			$backtrace = array_map(static function (array $el): array {
				unset($el['object']);
				unset($el['args']);
				return $el;
			}, $this->lastBacktrace);
			$backtrace = array_map(function (array $el): string {
				return ifsetor($el['class']) . ifsetor($el['type']) . ifsetor($el['function']) .
					' in ' . basename(ifsetor($el['file'])) . ':' . ifsetor($el['line']);
			}, $backtrace);
//			debug($this->lastQuery.'', pg_errormessage($this->getConnection()));
//			die(pg_errormessage($this->getConnection()));
			throw new DatabaseException(
				'Last query has failed.' . PHP_EOL .
				$this->lastQuery . PHP_EOL .
				pg_last_error($this->getConnection()) . PHP_EOL .
				implode(PHP_EOL, $backtrace)
			);
		}
	}

	public function performWithParams(string $query, $params)
	{
		$prof = new Profiler();
		$this->lastQuery = $query;
		$this->lastResult = pg_query_params($this->getConnection(), $query, $params);
		if (!$this->lastResult) {
			debug($query);
			debug_pre_print_backtrace();
			throw new DatabaseException(pg_last_error($this->getConnection()) . BR . $query);
		}

		$this->AFFECTED_ROWS = pg_affected_rows($this->lastResult);
		if ($this->queryLog) {
			$this->queryLog->log($query, $prof->elapsed(), $this->AFFECTED_ROWS);
		}

		$this->queryCount++;
		return $this->lastResult;
	}

	/**
	 * Return one dimensional array
	 * @param string $table
	 * @return array
	 */
	public function getTableColumns($table)
	{
		invariant($table, 'Table name must be provided');
		$meta = pg_meta_data($this->getConnection(), $table);
		if (is_array($meta)) {
			return array_keys($meta);
		}

		throw new DatabaseException(sprintf('Table not found: <strong>%s</strong>', $table));
	}

	public function getTableColumnsEx($table): array|false
	{
		return pg_meta_data($this->getConnection(), $table);
	}

	public function getTableColumnsCached($table)
	{
		//debug($table); exit;
		TaylorProfiler::start(__METHOD__);
		if (!$this->mcaTableColumns) {
			$this->mcaTableColumns = new MemcacheArray(__CLASS__ . '.' . __FUNCTION__, 24 * 60 * 60);
		}

		$cache =& $this->mcaTableColumns->data;
		//debug($cache); exit;

		if (!$cache[$table]) {
			$meta = pg_meta_data($this->getConnection(), $table);
			if (is_array($meta)) {
				$cache[$table] = array_keys($meta);
			} else {
				throw new DatabaseException(sprintf('Table not found: <strong>%s</strong>', $table));
			}
		}

		$return = $cache[$table];
		TaylorProfiler::stop(__METHOD__);
		return $return;
	}

	public function getColumnTypes($table)
	{
		$meta = pg_meta_data($this->getConnection(), $table);
		if (is_array($meta)) {
			$return = [];
			foreach ($meta as $col => $m) {
				$return[$col] = $m['type'];
			}

			return $return;
		}

		throw new DatabaseException(sprintf('Table not found: <strong>%s</strong>', $table));
	}

	public function getTableDataEx(string $table, ?string $where = "", string $what = "*")
	{
		$query = "select " . $what . (' from ' . $table);
		if ($where !== null && $where !== '' && $where !== '0') {
			$query .= ' where ' . $where;
		}

		return $this->fetchAll($query);
	}

	/**
	 * @param string $table
	 * @param string $column
	 * @param string $where
	 * @param string $order
	 * @param string $key
	 * @return array
	 * @throws Exception
	 * @throws MustBeStringException
	 * @deprecated
	 * @see SQLBuilder::getTableOptions
	 *
	 * function getTableOptions($table, $column, $where = "", $order = NULL, $key = 'id') {
	 * $tableName = $this->getFirstWord($table);
	 * if (is_array($where) && $where) {
	 * $where = $this->quoteWhere($where);
	 * $where = implode(' AND ', $where);
	 * } elseif (!$where) {
	 * $where = '1 = 1';
	 * }
	 * if ($order) {
	 * $where .= ' '.$order;
	 * }
	 * $a = $this->getTableDataEx($table, $where, $tableName.'.*, '.$column);
	 *
	 * // select login.*, coalesce(name, '') || ' ' || coalesce(surname, '') AS combined from login where relcompany = '47493'
	 * $as = trimExplode(' AS ', $column);
	 * if ($as[1]) {
	 * $column = $as[1];
	 * }
	 *
	 * $b = array();
	 * foreach ($a as $row) {
	 * $b[$row[$key]] = $row[$column];
	 * }
	 * return $b;
	 * }
	 * /**/
	/**
	 * fetchAll() equivalent with $key and $val properties
	 * @param string $query
	 * @param string $key
	 * @param mixed $val
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getTableDataSql($query, $key = null, $val = null): array
	{
		$result = is_string($query) ? $this->perform($query) : $query;

		$return = [];
		while ($row = pg_fetch_assoc($result)) {
			$value = $val ? $row[$val] : $row;

			if ($key) {
				$return[$row[$key]] = $value;
			} else {
				$return[] = $value;
			}
		}

		pg_free_result($result);
		return $return;
	}

	/**
	 * Returns a list of tables in the current database
	 * @return string[]
	 * @throws DatabaseException
	 */
	public function getTables(): array
	{
		$query = "select relname
		from pg_class
		where not relname ~ 'pg_.*'
		and not relname ~ 'sql_.*'
		and relkind = 'r'
		ORDER BY relname";
		$result = $this->perform($query);
		$return = pg_fetch_all($result);
		pg_free_result($result);
		return ArrayPlus::create($return)->column('relname')->getData();
	}

	/**
	 * Returns a list of tables in the current database
	 * @return string[]
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getViews(): array
	{
		$query = "select relname
		from pg_class
		where not relname ~ 'pg_.*'
		and not relname ~ 'sql_.*'
		and relkind = 'v'
		ORDER BY relname";
		$result = $this->perform($query);
		$return = pg_fetch_all($result);
		pg_free_result($result);
		return ArrayPlus::create($return)->column('relname')->getData();
	}

	public function describeView($viewName): mixed
	{
		return first(
			$this->fetchAssoc(
				$this->perform("select pg_get_viewdef($1, true)", [
					$viewName
				])
			)
		);
	}

	/**
	 * @param resource/query $result
	 * @return array
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function fetchAssoc($res): array|false
	{
		if (is_string($res)) {
			$res = $this->perform($res);
		}

//		error_log(__METHOD__ . ' [' . $res . ']');
		$row = pg_fetch_assoc($res);
		/*      // problem in OODBase
		 * 		if (!$row) {
					$row = array();
				}*/
		return $row;
	}

	public function getColumnDefault(string $table): array
	{
		$query = "SELECT *
		FROM information_schema.columns
		where table_name = '" . $table . "'
		ORDER BY ordinal_position";
		$data = $this->fetchAll($query);
		foreach ($data as &$row) {
			if (contains($row['column_default'], 'nextval')) {
				$parts = trimExplode("'", $row['column_default']);
				if (count($parts) >= 2) {
					$row['sequence'] = $parts[1];
					$row['sequence'] = str_replace('"', '', $row['sequence']);  // can be quoted
				}
			}
		}

		return ArrayPlus::create($data)->IDalize('column_name')->getData();
	}

	public function dataSeek($res, $number): bool
	{
		return pg_result_seek($res, $number);
	}

	public function transaction($serializable = false)
	{
		if ($this->inTransaction) {
			//error('BEGIN inTransaction: '.$this->inTransaction.'+1');
			$this->inTransaction++;
			return true;
		}

		$this->inTransaction++;
		if ($serializable) {
			$this->perform('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
		}

//		print('[[BEGIN]]'.BR);
		return $this->perform("BEGIN");
	}

	public function commit()
	{
		$this->inTransaction--;
		if ($this->inTransaction !== 0) {
			//error('COMMIT inTransaction: '.$this->inTransaction);
			//debug_pre_print_backtrace();
			return true;
		}

//		print('[[COMMIT]]'.BR);
		//debug_pre_print_backtrace();
		return $this->perform("commit");
	}

	public function rollback()
	{
		$this->inTransaction--;
		if ($this->inTransaction !== 0) {
			//error('ROLLBACK inTransaction: '.$this->inTransaction);
			return true;
		}

//		print('[[ROLLBACK]]'.BR);
		return $this->perform("rollback");
	}

	/**
	 * Called after dataSeek()
	 * @param resource $res
	 * @return array
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function fetchAssocSeek($res): array|false
	{
		return $this->fetchAssoc($res);
	}

	/**
	 * @param $query
	 * @return array
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 * @deprecated
	 * @use fetchAll()
	 */
	public function getAllRows($query)
	{
		$result = $this->perform($query);
		return $this->fetchAll($result);
	}

	public function getFirstRow($query): array|false
	{
		$result = $this->perform($query);
		return pg_fetch_assoc($result);
	}

	public function getFirstValue($query): ?string
	{
		$result = $this->perform($query);
		$row = pg_fetch_row($result);
		return $row[0];
	}

	/**
	 * Compatibility.
	 * @param resource $res
	 * @param string $table - optional
	 */
	public function lastInsertID($res, $table = null)
	{
		return $this->getLastInsertID($res, $table);
	}

	public function getLastInsertID($res = null, $table = 'not required since 8.1')
	{
//		$row = $this->fetchAssoc("SELECT currval('".$table."') AS currval");
//		return $row['currval'];
		$pgv = pg_version($this->getConnection());
//		llog('pg_version', $pgv);
		if ((float)$pgv['server'] >= 8.1) {
			return $this->lastval();
		}

		throw new RuntimeException('Upgrade PostgreSQL to 8.1 or higher');
	}

	protected function lastval()
	{
		$res = $this->perform('SELECT LASTVAL() AS lastval');
		$row = $this->fetchAssoc($res);
		return $row['lastval'];
	}

	public function getComment($table, $column)
	{
		$query = 'select
	 a.attname  as "colname"
	,a.attrelid as "tableoid"
	,a.attnum   as "columnoid"
	,col_description(a.attrelid, a.attnum) as "comment"
from
	pg_catalog.pg_attribute a
	inner join pg_catalog.pg_class c on a.attrelid = c.oid
where
        c.relname = ' . $this->quoteSQL($table) . '
	and a.attnum > 0
	and a.attisdropped is false
	and pg_catalog.pg_table_is_visible(c.oid)
order by a.attnum';
		$rows = $this->fetchAll($query);
		$assoc = ArrayPlus::create($rows)->column_assoc('colname', 'comment')->getData();
		//debug($query, $rows, $assoc, $comment);
		return $assoc[$column];
	}

	/**
	 * @param mixed $value
	 * @return string
	 * @throws MustBeStringException
	 */
	public function quoteSQL($value, $key = null): int|string
	{
		if ($value === null) {
			return "NULL";
		}

		if ($value === false) {
			return "'f'";
		}

		if ($value === true) {
			return "'t'";
		}

		if (is_int($value)) {  // is_numeric - bad: operator does not exist: character varying = integer
			return $value;
		}

		if (is_bool($value)) {
			return $value ? "'t'" : "'f'";
		}

//		if ($value instanceof SQLParam) {
//			return $value;
//		}

		if (is_scalar($value)) {
			return "'" . $this->escape($value) . "'";
		}

		debug($key, $value);
		throw new MustBeStringException('Must be string.');
	}

	public function escape($str): string
	{
		$this->connect();
		return pg_escape_string($this->getConnection(), $str);
	}

	/**
	 * Uses find_in_set function which is not built-in
	 * @param string $field
	 * @see SQLBuilder::array_intersect()
	 *
	 */
	public function getArrayIntersect(array $options, $field = 'list_next'): string
	{
		$bigOR = [];
		foreach ($options as $n) {
			$bigOR[] = "FIND_IN_SET('" . $n . sprintf("', %s)", $field);
		}

		return "(" . implode(' OR ', $bigOR) . ")";
	}

	/**
	 * @param string $method
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($method, array $params)
	{
		if (method_exists($this->getQb(), $method)) {
			return call_user_func_array([$this->getQb(), $method], $params);
		}

		throw new Exception('Method ' . __CLASS__ . '::' . $method . " doesn't exist.");
	}

	public function getQb()
	{
		if ($this->qb === null) {
			$this->setQb(new SQLBuilder($this));
		}

		return $this->qb;
	}

	public function setQb(SQLBuilder $qb = null): void
	{
		$this->qb = $qb;
	}

	public function getCallerFunction(): string
	{
		$skipFunctions = [
			'runSelectQuery',
			'fetchSelectQuery',
			'sqlFind',
			'getAllRows',
			'perform',
			'fetchFromDB',
			'findInDB',
			'retrieveData',
			'init',
			'__construct',
			'getInstance',
		];
		$debug = debug_backtrace();
		$prev = array_shift($debug);    // getCallerFunction
		while (count($debug) && in_array($debug[0]['function'], $skipFunctions)) {
			$prev = array_shift($debug);
		}

		reset($debug);
		$content = [];
		/** @noinspection PhpUnusedLocalVariableInspection */
		foreach (range(1, 2) as $_) {
			$func = current($debug);
			$func['line'] = $prev['line'];    // line is from the parent function?
			$content[] = $func['class'] . '::' . $func['function'] . '#' . $func['line'];
			next($debug);
		}

		return implode(' < ', $content);
	}

	/**
	 * @param string $table
	 * @return array
	 * @throws Exception
	 */
	public function getIndexesFrom($table)
	{
		return $this->fetchAll('select *, pg_get_indexdef(indexrelid)
		from pg_index
		where indrelid = \'' . $table . "'::regclass");
	}

	public function free($res): void
	{
		if (is_resource($res)) {
			pg_free_result($res);
		}
	}

	public function escapeBool($value): string
	{
		return $value ? 'true' : 'false';
	}

	public function affectedRows($res = null): int
	{
		return pg_affected_rows($res);
	}

	public function getScheme(): string
	{
		return 'postgresql';
	}

	/**
	 * @return string[]
	 */
	public function getResultFields($res): array
	{
		$fields = [];
		for ($f = 0; $f < pg_num_fields($res); $f++) {
			$newField = pg_fieldname($res, $f);
			$fields[$newField] = pg_field_type($res, $f);
		}

		return $fields;
	}

	public function getForeignKeys(string $table)
	{
		return $this->fetchAll(
			"SELECT
	tc.constraint_name, tc.table_name, kcu.column_name,
	ccu.table_name AS foreign_table_name,
	ccu.column_name AS foreign_column_name,
	constraint_type
FROM
	information_schema.table_constraints AS tc
	JOIN information_schema.key_column_usage AS kcu
	  ON tc.constraint_name = kcu.constraint_name
	JOIN information_schema.constraint_column_usage AS ccu
	  ON ccu.constraint_name = tc.constraint_name
WHERE ccu.table_name='" . $table . "'");
	}

	public function getPlaceholder($field): string
	{
		return '$0$';
	}

	public function isPostgres(): bool
	{
		return true;
	}

	/**
	 * @param string $table Table name
	 * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
	 * @param array $primaryKeys ['id', 'id_profile']
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function runReplaceQuery($table, array $columns, array $primaryKeys = [])
	{
//		debug($table, $columns, $primaryKeys, $this->getVersion(), $this->getVersion() >= 9.5);
		if ($this->getVersion() >= 9.5) {
			$q = $this->getReplaceQuery($table, $columns);
			die($q);
		}

//			debug($this->isTransaction());
		//$this->transaction();
//			debug($this->isTransaction());
		$key_key = array_combine($primaryKeys, $primaryKeys);
		$where = array_intersect_key($columns, $key_key);
		$find = $this->runSelectQuery($table, $where);
		$rows = $this->numRows($find);
//			debug($rows, $table, $columns, $where);
//			exit;
		if ($rows !== 0) {
			return $this->runUpdateQuery($table, $columns, $where);
		} else {
			return $this->runInsertQuery($table, $columns);
		}

		//return $this->commit();
	}

	/**
	 * @param string $table
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getReplaceQuery($table, array $columns): string
	{
		if ($this->getVersion() < 9.5) {
			throw new DatabaseException(__METHOD__ . ' is not working in PG < 9.5. Use runReplaceQuery()');
		}

		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		return "INSERT INTO {$table} ({$fields}) VALUES ({$values})
			ON CONFLICT UPDATE SET ";
	}

	/**
	 * Will quote simple key names.
	 * If the key contains special chars,
	 * it thinks it's a function call like trim(field)
	 * and quoting is not done.
	 * @param string|AsIs $key
	 * @return string
	 */
	public function quoteKey($key)
	{
		if (ctype_alpha($key)) {
			$isFunc = function_exists('pg_escape_identifier');
			$key = $isFunc && $this->isConnected() ? pg_escape_identifier($this->getConnection(), $key) : '"' . $key . '"';
		} elseif ($key instanceof AsIs) {
			$key .= '';
		}

		// else it can be functions (of something)
		return $key;
	}

	public function numRows($query = null): int
	{
		if (is_string($query)) {
			$query = $this->perform($query);
		}

		return pg_num_rows($query);
	}

	public function isTransaction(): bool
	{
		return pg_transaction_status($this->getConnection()) === PGSQL_TRANSACTION_INTRANS;
	}

	/**
	 * @return array
	 */
	public function getInfo()
	{
		return pg_version($this->getConnection()) + [
				'options' => pg_options($this->getConnection()),
				'busy' => $this->getConnection()($this->getConnection()),
				'status' => pg_connection_status($this->getConnection()),
				'status_ok' => PGSQL_CONNECTION_OK,
				'status_bad' => PGSQL_CONNECTION_BAD,
				'transaction' => pg_transaction_status($this->getConnection()),
				'client_encoding' => pg_client_encoding($this->getConnection()),
				'host' => pg_host($this->getConnection()),
				'port' => pg_port($this->getConnection()),
			];
	}

	public function getDSN(): string
	{
		return 'pgsql://' . $this->user . '@' . $this->host . '/' . $this->dbName;
	}

}
