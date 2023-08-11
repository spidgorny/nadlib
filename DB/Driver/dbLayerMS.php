<?php

use PHPSQLParser\builders\OrderByBuilder;

class dbLayerMS extends dbLayerBase implements DBInterface
{

	/**
	 * @var string
	 */
	public $server, $database, $user, $password;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * @var resource
	 */
	public $connection;

	/**
	 * @var dbLayerMS
	 */
	protected static $instance;

	/**
	 * Will output every query
	 * @var bool
	 */
	public $debug = true;

	/**
	 * In MSSQL mssql_select_db() is returning the following as error messages
	 * @var array
	 */
	public $ignoreMessages = [
		"Changed database context to 'DEV_LOTCHECK'.",
		"Changed database context to 'PRD_LOTCHECK'.",
	];

	public static function getInstance()
	{
		//$c = Config::getInstance();
		//if (!self::$instance) self::$instance = ;
		return self::$instance;
	}

	function __construct($server, $database, $user, $password)
	{
		ini_set('mssql.charset', 'UTF-8');
		$this->server = $server;
		$this->database = $database;
		$this->user = $user;
		$this->password = $password;
		$this->connect();
	}

	function connect()
	{
		$this->connection = mssql_connect($this->server, $this->user, $this->password);
		mssql_select_db($this->database);
	}

	function close()
	{
		mssql_close($this->connection);
	}

	function perform($query, array $arguments = [])
	{
		//if (date('s') == '00') return false;    // debug
		foreach ($arguments as $ar) {
			$query = str_replace('?', $ar, $query);
		}
		//$query = $this->fixQuery($query);
		$profiler = new Profiler();
		$res = mssql_query($query, $this->connection);
		$msg = mssql_get_last_message();
		if (!$res && $this->debug) {
			debug([
				'method' => __METHOD__,
				'query' => $query,
				'numRows' => is_resource($res)
					? $this->numRows($res)
					: ($res ? 'TRUE' : 'FALSE'),
				'elapsed' => $profiler->elapsed(),
				'msg' => $msg,
				'this' => gettype2($this),
				'this->qb' => gettype2($this->qb),
				'this->qb->db' => gettype2($this->qb->db),
			]);
		}
		if ($msg && !in_array($msg, $this->ignoreMessages)) {
			//debug($msg, $query);
			$msg2 = mssql_fetch_assoc(
				mssql_query(
					'SELECT @@ERROR AS ErrorCode',
					$this->connection))['ErrorCode'];
			$this->close();
			$this->connect();
			debug($msg2, $msg, $query);
			throw new Exception(__METHOD__ . ': ' . $msg . BR . $query . BR . $msg2);
		}
		$this->lastQuery = $query;
		return $res;
	}

	/**
	 * Remove space from WHERE SUBSTRING(GameCode,1,3) = N 'CTR')
	 * @param $original
	 * @return SQLQuery|string
	 */
	function fixQuery($original)
	{
		$query = new SQLQuery($original);
		if (isset($query->parsed['WHERE'])) {
			foreach ($query->parsed['WHERE'] as $i => $part) {
				if ($part['expr_type'] == 'colref'
					&& $part['base_expr'] == 'N'
				) {
					unset($query->parsed['WHERE'][$i]);
					$query->parsed['WHERE'][$i + 1]['base_expr'] = 'N' . $query->parsed['WHERE'][$i + 1]['base_expr'];
				}
			}
		}
		$fixed = $query->getQuery();
		//debug(gettype($original), $original, $query->parsed['WHERE'], $fixed);
		return $fixed;
	}

	function fetchAssoc($res)
	{
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		if (!is_resource($res)) {
			debug(__METHOD__, 'InvalidArgumentException', $res, $this->lastQuery);
			throw new InvalidArgumentException(__METHOD__ . ' received a ' . gettype($res) . ' as an argument instead of a resource.');
		}
		return mssql_fetch_assoc($res);
	}

	function fetchAll($res, $keyKey = NULL)
	{
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$table = [];
		$rows = mssql_num_rows($res);
		$i = 0;
		do {
			while (($row = $this->fetchAssoc($res)) != false) {
				if ($keyKey) {
					$key = $row[$keyKey];
					$table[$key] = $row;
				} else {
					$table[] = $row;
				}
			}
		} while (mssql_next_result($res) && $i++ < $rows);
		$this->free($res);
		return $table;
	}

	/**
	 *
	 * @return array ('name' => ...)
	 */
	function getTables()
	{
		$res = $this->perform("select * from sysobjects
		where xtype = 'U'");
		$tables = $this->fetchAll($res);
		return $tables;
	}

	/**
	 *
	 * @param $table
	 * @return array ('name' => ...)
	 */
	function getFields($table)
	{
		//mssql_meta - doesn't exist
		$res = $this->perform("
SELECT
	syscolumns.name,
	systypes.name AS stype,
	syscolumns.*
FROM syscolumns
LEFT OUTER JOIN systypes ON (systypes.xtype = syscolumns.xtype)
WHERE id = (SELECT id
FROM sysobjects
WHERE type = 'U'
AND name = '?')", [$table]);
		$tables = $this->fetchAll($res);
		return $tables;
	}

	function escape($val)
	{
		return $this->mssql_escape_string($val);
	}

	function mssql_escape_string($data)
	{
		if (!isset($data) or empty($data)) return '';
		if (is_numeric($data)) return $data;

		$non_displayables = [
			'/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
			'/%1[0-9a-f]/',             // url encoded 16-31
			'/[\x00-\x08]/',            // 00-08
			'/\x0b/',                   // 11
			'/\x0c/',                   // 12
			'/[\x0e-\x1f]/'             // 14-31
		];
		foreach ($non_displayables as $regex) {
			$data = preg_replace($regex, '', $data);
		}
		$data = str_replace("'", "''", $data);
		return $data;
	}

	/* *
	 * Return ALL rows
	 * @param <type> $table
	 * @param <type> $where
	 * @param <type> $order
	 * @return <type>
	 * /
	function fetchSelectQuery($table, array $where = [], $order = '') {
		$res = $this->runSelectQuery($table, $where, $order);
		$data = $this->fetchAll($res);
		return $data;
	}

	function runSelectQuery($table, array $where, $order = '') {
		$qb = Config::getInstance()->qb;
		$res = $qb->runSelectQuery($table, $where, $order);
		return $res;
	}

	function fetchSelectQuerySW($table, SQLWhere $where, $order = '') {
		$res = $this->runSelectQuerySW($table, $where, $order);
		$data = $this->fetchAll($res);
		return $data;
	}

	function runSelectQuerySW($table, SQLWhere $where, $order = '') {
		$qb = Config::getInstance()->qb;
		$res = $qb->runSelectQuerySW($table, $where, $order);
		return $res;
	}
*/
	function numRows($res = NULL)
	{
		return mssql_num_rows($res);
	}

	function quoteKey($key)
	{
		if (!str_contains($key, '(')) {    // functions
//			debug($key);
			$key = '[' . $key . ']';
		}
		return $key;
	}

	function lastInsertID($res, $table = NULL)
	{
		$lq = $this->lastQuery;
		$query = 'SELECT SCOPE_IDENTITY()';
		$res = $this->perform($query);
		$row = $this->fetchAssoc($res);
		//debug($lq, $row);
		$val = $row['computed'];
		return $val;
	}

	function __call($method, array $params)
	{
		if (method_exists($this->qb, $method)) {
			return call_user_func_array([$this->qb, $method], $params);
		} else {
			throw new Exception($method . ' not found in ' . get_class($this) . ' and SQLBuilder');
		}
	}

	function free($res)
	{
		mssql_free_result($res);
		if (error_get_last()) {
			debug_pre_print_backtrace();
		}
	}

	function escapeBool($value)
	{
		return $value ? 1 : 0;
	}

	function affectedRows($res = NULL)
	{
		return mssql_rows_affected($this->connection);
	}

	function switchDB($name)
	{
		mssql_select_db($name);
		$this->database = $name;
	}

	public function disconnect()
	{
		mssql_close($this->connection);
	}

	function addLimit($query, $howMany, $startingFrom)
	{
		$version = first($this->fetchAssoc('SELECT @@VERSION'));
		if ($version >= 'Microsoft SQL Server 2012') {
			$query .= ' OFFSET ' . $startingFrom . ' ROWS
    			FETCH NEXT ' . $howMany . ' ROWS ONLY';
		} else {
			$query = $this->addLimitOldVersion($query, $howMany, $startingFrom);
		}
		return $query;
	}

	/**
	 * Will
	 * * extract ORDER BY xxx
	 * * remove it from the original query
	 * (The ORDER BY clause is invalid in subqueries)
	 * * Create query array for SELECT ROW_NUMBER()
	 * * Append this to the existing query SELECT
	 * * Wrap everything in outside SELECT FROM ()
	 * * Add append WHERE RowNumber BETWEEN
	 * @param $query
	 * @param $howMany
	 * @param $startingFrom
	 * @return mixed|SQLQuery|string
	 */
	function addLimitOldVersion($query, $howMany, $startingFrom)
	{
		$query = new SQLQuery($query . '');
		$builder = new OrderByBuilder();
		$orderBy = $builder->build($query->parsed['ORDER']);
		unset($query->parsed['ORDER']);

		//$query .= ' WHERE a BETWEEN 10 AND 10 AND isok';
		$querySelect = new SQLQuery('SELECT
		ROW_NUMBER() OVER (
			' . $orderBy . '
		) AS RowNumber
		FROM asd');
		//debug($querySelect->parsed);

		$lastIndex = sizeof($query->parsed['SELECT']) - 1;
		$query->parsed['SELECT'][$lastIndex]['delim'] = ',';
		$query->parsed['SELECT'] = array_merge(
			$query->parsed['SELECT'], [
			$querySelect->parsed['SELECT'][0] // ROW_NUMBER()...
		]);
		//debug($query->parsed['SELECT']);
		$query = $this->fixQuery($query);

		$outside = new SQLQuery('SELECT * FROM (subquery123) AS zxc');
		$outside->parsed['WHERE'] = array_merge(
		//ifsetor($query->parsed['WHERE'], []),
			[],
			[
				[
					'expr_type' => 'colref',
					'base_expr' => 'RowNumber',
					'no_quotes' =>
						[
							'delim' => false,
							'parts' =>
								[
									0 => 'RowNumber',
								],
						],
					'sub_tree' => false,
				],
				[
					'expr_type' => 'operator',
					'base_expr' => 'BETWEEN',
					'sub_tree' => false,
				],
				[
					'expr_type' => 'const',
					'base_expr' => $startingFrom,
					'sub_tree' => false,
				],
				[
					'expr_type' => 'operator',
					'base_expr' => 'AND',
					'sub_tree' => false,
				],
				[
					'expr_type' => 'const',
					'base_expr' => $startingFrom + $howMany,
					'sub_tree' => false,
				],
			]
		);
		//debug($query->parsed['WHERE']);
		$outside = $outside->getQuery();
		$outside = str_replace('subquery123', $query, $outside);
		return $outside;
	}

}
