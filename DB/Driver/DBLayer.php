<?php

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
	 * @var resource
	 */
	public $connection = null;

	public $LAST_PERFORM_RESULT;

	/**
	 * todo: use setter & getter method
	 *
	 * contains query builder class used as mixin.
	 *
	 * @var null
	 */
	public $qb = null;

	public $AFFECTED_ROWS = null;

	/**
	 * @var MemcacheArray
	 */
	protected $mcaTableColumns;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * Transaction count because three are no nested transactions
	 * @var int
	 */
	protected $inTransaction = 0;

	/**
	 * @var string DB name
	 */
	public $db;

	public $reserved = [
		'SELECT', 'LIKE', 'TO',
	];

	protected $user;

	protected $pass;

	public $host;

	protected $lastBacktrace;

	/**
	 * @param string $dbName
	 * @param string $user
	 * @param string $pass
	 * @param string $host
	 * @throws Exception
	 */
	public function __construct($dbName = null, $user = null, $pass = null, $host = "localhost")
	{
		$this->database = $dbName;
//		pre_print_r($this->dbName);
		$this->user = $user;
		$this->pass = $pass;
		$this->host = $host;
		if ($dbName) {
			$this->connect($dbName, $user, $pass, $host);

			if ($this->getVersion() >= 8.4) {
				$query = "select * from pg_get_keywords() WHERE catcode IN ('R', 'T')";
				$words = $this->fetchAll($query, 'word');
				$this->reserved = array_keys($words);
				$this->reserved = array_map('strtoupper', $this->reserved); // important
			}
		}
		if (DEVELOPMENT) {
			$this->queryLog = new QueryLog();
		}
	}

	public function getVersion()
	{
		$version = pg_version($this->connection);
		return (float)$version['server'];
	}

	/**
	 * @return bool
	 */
	public function isConnected()
	{
		return !!$this->connection
			&& pg_connection_status($this->connection) === PGSQL_CONNECTION_OK;
	}

	public function getConnection()
	{
		return $this->connection;
	}

	public function reconnect()
	{
		$this->connect($this->database, $this->user, $this->pass, $this->host);
	}

	public function connect($database = null, $user = null, $pass = null, $host = null)
	{
		if ($database) {
			$this->database = $database;
		}
		if ($user) {
			$this->user = $user;
		}
		if ($pass) {
			$this->pass = $pass;
		}
		if ($host) {
			$this->host = $host;
		}
		$string = "host={$this->host} dbname={$this->database} user={$this->user} password={$this->pass} connect_timeout=4";
		$this->connection = pg_connect($string);
		if (!$this->connection) {
			throw new DatabaseException("No PostgreSQL connection to $host. " . json_encode(error_get_last()));
			//printbr('Error: '.pg_errormessage());	// Warning: pg_errormessage(): No PostgreSQL link opened yet
		}

		$this->perform("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;");
		return true;
	}

	public function reportIfLastQueryFailed()
	{
		if (false === $this->LAST_PERFORM_RESULT) {
			$backtrace = array_map(static function ($el) {
				unset($el['object']);
				unset($el['args']);
				return $el;
			}, $this->lastBacktrace);
			$backtrace = array_map(static function (array $el) {
				return ifsetor($el['class']) . ifsetor($el['type']) . ifsetor($el['function']) .
					' in ' . basename(ifsetor($el['file'])) . ':' . ifsetor($el['line']);
			}, $backtrace);
//			debug($this->lastQuery.'', pg_errormessage($this->connection));
//			die(pg_errormessage($this->connection));
			throw new DatabaseException(
				'Last query has failed.' . PHP_EOL .
				$this->lastQuery . PHP_EOL .
				pg_errormessage($this->connection) . PHP_EOL .
				implode(PHP_EOL, $backtrace)
			);
		}
	}

	/**
	 * @param string $query
	 * @param array $params
	 * @return resource|null
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function perform($query, array $params = [])
	{
//		llog(SQLSelectQuery::trim($query), $params);
		$prof = new Profiler();

		$this->lastQuery = $query;
		if (!$this->connection) {
			throw new DatabaseException('No connection in ' . __METHOD__);
		}

		if ($query instanceof SQLSelectQuery) {
			$params = $query->getParameters();
			$query = $query->__toString();
//			debug($query, $params);
		}

		try {
			if ($params) {
				$ok = pg_prepare($this->connection, '', $query);
				if (!$ok) {
					throw new DatabaseException($query . ' can not be prepared');
				}
				$this->LAST_PERFORM_RESULT = pg_execute($this->connection, '', $params);
			} else {
				$this->LAST_PERFORM_RESULT = pg_query($this->connection, $query);
				$lastError = pg_last_error($this->connection);
				if ($lastError) {
					// setQuery will be called in the catch below
					throw new DatabaseException($lastError);
				}
			}
			$this->queryTime = $prof->elapsed();
			if ($this->logToLog) {
				llog($query . '' . ' => ' . $this->LAST_PERFORM_RESULT);
			}
		} catch (Exception $exception) {
			$e = new DatabaseException(
				get_class($exception) . ' [' . $exception->getCode() . '] ' . $exception->getMessage(),
				$exception->getCode(), $exception
			);
			$e->setQuery($query);
			throw $e;
		}

		if (!$this->LAST_PERFORM_RESULT) {
			$e = new DatabaseException(
				pg_errormessage($this->connection) .
				'Query: ' . $query
			);
			$e->setQuery($query);
			throw $e;
		}

		$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
		if ($this->queryLog) {
			$this->queryLog->log($query, $prof->elapsed(), $this->AFFECTED_ROWS, $this->LAST_PERFORM_RESULT);
		}

		$this->logQuery($query);    // uses $this->queryTime

		$this->lastQuery = $query;
		$this->queryCount++;

		// this calls getPref() somehow and leads to supplied resource is not a valid PostgreSQL result resource
//		$this->lastBacktrace = debug_backtrace();
		return $this->LAST_PERFORM_RESULT;
	}

	public function performWithParams($query, $params)
	{
		$prof = new Profiler();
		$this->lastQuery = $query;
		$this->LAST_PERFORM_RESULT = pg_query_params($this->connection, $query, $params);
		if (!$this->LAST_PERFORM_RESULT) {
			debug($query);
			debug_pre_print_backtrace();
			throw new Exception(pg_errormessage($this->connection) . BR . $query);
		}

		$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
		if ($this->queryLog) {
			$this->queryLog->log($query, $prof->elapsed(), $this->AFFECTED_ROWS);
		}
		$this->queryCount++;
		return $this->LAST_PERFORM_RESULT ?: null;
	}

	/**
	 * Return one dimensional array
	 * @param string $table
	 * @return array
	 */
	public function getTableColumns($table)
	{
		if (!$table) {
			debug_pre_print_backtrace();
		}
		$meta = pg_meta_data($this->connection, $table);
		if (is_array($meta)) {
			return array_keys($meta);
		}

		error("Table not found: <strong>$table</strong>");
		exit();
	}

	public function getTableColumnsEx($table)
	{
		return pg_meta_data($this->connection, $table);
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
			$meta = pg_meta_data($this->connection, $table);
			if (is_array($meta)) {
				$cache[$table] = array_keys($meta);
			} else {
				error("Table not found: <strong>$table</strong>");
				exit();
			}
		}
		$return = $cache[$table];
		TaylorProfiler::stop(__METHOD__);
		// used to only attach columns in bug list
		$pageAttachCustom = ['BugLog', 'Filter'];
		if (in_array($_REQUEST['pageType'], $pageAttachCustom)) {
			$cO = CustomCatList::getInstance($_SESSION['sesProject']);
			if (is_array($cO->customColumns)) {
				foreach ($cO->customColumns as $cname) {
					$return[] = $cname;
				}
			}
		}
		return $return;
	}

	public function getColumnTypes($table)
	{
		$meta = pg_meta_data($this->connection, $table);
		if (is_array($meta)) {
			$return = [];
			foreach ($meta as $col => $m) {
				$return[$col] = $m['type'];
			}
			return $return;
		}

		error("Table not found: <strong>$table</strong>");
		exit();
	}

	public function getTableDataEx($table, $where = "", $what = "*")
	{
		$query = "select " . $what . " from $table";
		if (!empty($where)) {
			$query .= " where $where";
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
	 * @return array
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getTableDataSql($query, $key = null, $val = null)
	{
		if (is_string($query)) {
			$result = $this->perform($query);
		} else {
			$result = $query;
		}
		$return = [];
		while ($row = pg_fetch_assoc($result)) {
			if ($val) {
				$value = $row[$val];
			} else {
				$value = $row;
			}

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
	public function getTables()
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
	public function getViews()
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

	public function describeView($viewName)
	{
		return first(
			$this->fetchAssoc(
				$this->perform("select pg_get_viewdef($1, true)", [
					$viewName
				])
			)
		);
	}

	public function getColumnDefault($table)
	{
		$query = "SELECT *
		FROM information_schema.columns
		where table_name = '" . $table . "'
		ORDER BY ordinal_position";
		$data = $this->fetchAll($query);
		foreach ($data as &$row) {
			if (contains($row['column_default'], 'nextval')) {
				$parts = trimExplode("'", $row['column_default']);
				if (sizeof($parts) >= 2) {
					$row['sequence'] = $parts[1];
					$row['sequence'] = str_replace('"', '', $row['sequence']);  // can be quoted
				}
			}
		}
		return ArrayPlus::create($data)->IDalize('column_name')->getData();
	}

	public function dataSeek($res, $number)
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
		if ($this->inTransaction) {
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
		if ($this->inTransaction) {
			//error('ROLLBACK inTransaction: '.$this->inTransaction);
			return true;
		}
//		print('[[ROLLBACK]]'.BR);
		return $this->perform("rollback");
	}

	/**
	 * @param mixed $value
	 * @param null $key
	 * @return string
	 * @throws MustBeStringException
	 */
	public function quoteSQL($value, $key = null)
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

		if (is_scalar($value)) {
			return "'" . $this->escape($value) . "'";
		}

		debug($key, $value);
		throw new MustBeStringException('Must be string.');
	}

	/**
	 * Overrides because of pg_fetch_all
	 * @param resource|string $result
	 * @param string|null $key
	 * @return array
	 * @throws Exception
	 */
	public function fetchAll($result, $key = null)
	{
		$params = [];
		if ($result instanceof SQLSelectQuery) {
			/** @var SQLSelectQuery $queryObj */
			$queryObj = $result;
			$result = $queryObj->getQuery();
			$params = $queryObj->getParameters();
//			llog('converted to string', $result, $params);
		}
		if (is_string($result)) {
			//debug($result);
			$result = $this->perform($result, $params);
		}
		//debug($this->numRows($result));
		$res = pg_fetch_all($result);
		pg_free_result($result);
		if (ifsetor($_REQUEST['d']) == 'q') {
			debug($this->lastQuery, sizeof($res));
		}
		if (!$res) {
			$res = [];
		} elseif ($key) {
			$ap = ArrayPlus::create($res)->IDalize($key)->getData();
			//debug(sizeof($res), sizeof($ap));
			$res = $ap;
		}

		return $res;
	}

	/**
	 * @param resource/query $result
	 * @return array|null
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function fetchAssoc($res)
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
		return $row ?: null; // pg_fetch_assoc returns false on no rows
	}

	/**
	 * Called after dataSeek()
	 * @param resource $res
	 * @return array
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function fetchAssocSeek($res)
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

	public function getFirstRow($query)
	{
		$result = $this->perform($query);
		return pg_fetch_assoc($result);
	}

	public function getFirstValue($query)
	{
		$result = $this->perform($query);
		$row = pg_fetch_row($result);
		return $row[0];
	}

	public function numRows($query = null)
	{
		if (is_string($query)) {
			$query = $this->perform($query);
		}
		return pg_num_rows($query);
	}

	public function getLastInsertID($res = null, $table = 'not required since 8.1')
	{
		$pgv = pg_version();
//		llog('pg_version', $pgv);
		if ((float)$pgv['server'] >= 8.1) {
			return $this->lastval();
		}

		throw new RuntimeException('Upgrade PostgreSQL to 8.1 or higher');
//		$oid = pg_last_oid($res);
//		$id = $this->sqlFind('id', $table, "oid = '" . $oid . "'");
//		return $id;
	}

	/**
	 * Compatibility.
	 * @param resource $res
	 * @param string $table - optional
	 * @return null
	 */
	public function lastInsertID($res, $table = null)
	{
		return $this->getLastInsertID($res, $table);
	}

	protected function lastval()
	{
		$res = $this->perform('SELECT LASTVAL() AS lastval');
		$row = $this->fetchAssoc($res);
		return $row['lastval'];
	}

	/**
	 * Uses find_in_set function which is not built-in
	 * @param array $options
	 * @param string $field
	 * @return string
	 * @see SQLBuilder::array_intersect()
	 *
	 */
	public function getArrayIntersect(array $options, $field = 'list_next')
	{
		$bigOR = [];
		foreach ($options as $n) {
			$bigOR[] = "FIND_IN_SET('" . $n . "', {$field})";
		}
		$bigOR = "(" . implode(' OR ', $bigOR) . ")";
		return $bigOR;
	}

	public function escape($str)
	{
		return pg_escape_string($this->connection, $str);
	}

	/**
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($method, array $params)
	{
		if (method_exists($this->getQb(), $method)) {
			return call_user_func_array([$this->getQb(), $method], $params);
		}

		throw new Exception('Method ' . __CLASS__ . '::' . $method . ' doesn\'t exist.');
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
			if ($isFunc && $this->isConnected()) {
				$key = pg_escape_identifier($this->connection, $key);
			} else {
				$key = '"' . $key . '"';
			}
		} elseif ($key instanceof AsIs) {
			$key .= '';
		}// else it can be functions (of something)
		return $key;
	}

	public function getCallerFunction()
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
		while (sizeof($debug) && in_array($debug[0]['function'], $skipFunctions)) {
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
		$content = implode(' < ', $content);
		return $content;
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
		where indrelid = \'' . $table . '\'::regclass');
	}

	public function free($res)
	{
		if (is_resource($res)) {
			pg_free_result($res);
		}
	}

	public function escapeBool($value)
	{
		return $value ? 'true' : 'false';
	}

	public function setQb(SQLBuilder $qb = null)
	{
		$this->qb = $qb;
	}

	public function getQb()
	{
		if (!isset($this->qb)) {
			$this->setQb(new SQLBuilder($this));
		}

		return $this->qb;
	}

	public function affectedRows($res = null)
	{
		return pg_affected_rows($res);
	}

	public function getScheme()
	{
		return 'postgresql';
	}

	public function getResultFields($res)
	{
		$fields = [];
		for ($f = 0; $f < pg_num_fields($res); $f++) {
			$newField = pg_fieldname($res, $f);
			$fields[$newField] = pg_field_type($res, $f);
		};
		return $fields;
	}

	public function getForeignKeys($table)
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

	public function isPostgres()
	{
		return true;
	}

	/**
	 * @param string $table
	 * @param array $columns
	 * @return string
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function getReplaceQuery($table, array $columns)
	{
		if ($this->getVersion() < 9.5) {
			throw new DatabaseException(__METHOD__ . ' is not working in PG < 9.5. Use runReplaceQuery()');
		}
		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		$q = "INSERT INTO {$table} ({$fields}) VALUES ({$values})
			ON CONFLICT UPDATE SET ";
		return $q;
	}

	/**
	 * @param string $table Table name
	 * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
	 * @param array $primaryKeys ['id', 'id_profile']
	 * @return string
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	public function runReplaceQuery($table, array $columns, array $primaryKeys = [])
	{
//		debug($table, $columns, $primaryKeys, $this->getVersion(), $this->getVersion() >= 9.5);
		if ($this->getVersion() >= 9.5) {
			$q = $this->getReplaceQuery($table, $columns);
			die($q);
			return $this->perform($q);
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
		if ($rows) {
			$this->runUpdateQuery($table, $columns, $where);
		} else {
			$this->runInsertQuery($table, $columns);
		}
		//return $this->commit();
	}

	public function isTransaction()
	{
		return pg_transaction_status($this->connection) === PGSQL_TRANSACTION_INTRANS;
	}

	/**
	 * @return array
	 */
	public function getInfo()
	{
		return pg_version($this->connection) + [
				'options' => pg_options($this->connection),
				'busy' => pg_connection_busy($this->connection),
				'status' => pg_connection_status($this->connection),
				'status_ok' => PGSQL_CONNECTION_OK,
				'status_bad' => PGSQL_CONNECTION_BAD,
				'transaction' => pg_transaction_status($this->connection),
				'client_encoding' => pg_client_encoding($this->connection),
				'host' => pg_host($this->connection),
				'port' => pg_port($this->connection),
			];
	}

	public function getDSN()
	{
		return 'pgsql://' . $this->user . '@' . $this->host . '/' . $this->database;
	}

}
