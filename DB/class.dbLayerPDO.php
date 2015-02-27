<?php

/**
 * Class dbLayerPDO
 * @mixin SQLBuilder
 */
class dbLayerPDO extends dbLayerBase implements DBInterface {

	/**
	 * @var PDO
	 */
	public $connection;

	/**
	 * @var PDOStatement
	 */
	public $lastResult;

	/**
	 * @var string
	 */
	public $dsn;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * @var null|int
	 */
	protected $dataSeek = NULL;

	function __construct($user = NULL, $password = NULL, $scheme = NULL, $driver = NULL, $host = NULL, $db = NULL, $port = 3306) {
		if ($user) {
			$this->connect($user, $password, $scheme, $driver, $host, $db, $port);
		}

		//$this->setQB(); // must be injected outside (inf loop)
//		debug_pre_print_backtrace();
	}

	static function getAvailableDrivers() {
		return PDO::getAvailableDrivers();
	}

	/**
	 * @param $user
	 * @param $password
	 * @param $scheme
	 * @param $driver        string IBM DB2 ODBC DRIVER
	 * @param $host
	 * @param $db
	 * @param int $port
	 */
	function connect($user, $password, $scheme, $driver, $host, $db, $port = 3306) {
		//$dsn = $scheme.':DRIVER={'.$driver.'};DATABASE='.$db.';SYSTEM='.$host.';dbname='.$db.';HOSTNAME='.$host.';PORT='.$port.';PROTOCOL=TCPIP;';
		if ($scheme == 'sqlite') {
			$this->dsn = $scheme.':'.$db;
			$this->database = basename($db);
		} else {
			$this->dsn = $scheme . ':' . $this->getDSN(array(
					'DRIVER' => '{' . $driver . '}',
					'DATABASE' => $db,
					'host' => $host,
					'SYSTEM' => $host,
					'dbname' => $db,
					'HOSTNAME' => $host,
					'PORT' => $port,
					'PROTOCOL' => 'TCPIP',
				));
			$this->database = $db;
		}
		//debug($this->dsn);
		$profiler = new Profiler();
		$this->connectDSN($this->dsn, $user, $password);
		$this->queryTime += $profiler->elapsed();
		if ($this->isMySQL()) {
			$my = new MySQL();
			$this->reserved = $my->getReserved();
		}
	}

	function isConnected() {
		return !!$this->connection;
	}

	function connectDSN($dsn, $user = NULL, $password = NULL) {
		$this->dsn = $dsn;
		$this->connection = new PDO($this->dsn, $user, $password);
		$this->connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		//$this->connection->setAttribute( PDO::ATTR_EMULATE_PREPARES, false);
		$url = parse_url($this->dsn);
		$this->database = basename($url['path']);
	}

	function perform($query, array $params = array()) {
		$this->lastQuery = $query;
		if ($this->getScheme() == 'mysql') {
			$params[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
		}
		$profiler = new Profiler();
		try {
			$this->lastResult = $this->connection->prepare($query, $params);
		} catch (PDOException $e) {
			debug($query, $params, $e->getMessage());
			throw $e;
		}
		$this->queryTime += $profiler->elapsed();
		if ($this->logToLog) {
			$runTime = number_format(microtime(true)-$_SERVER['REQUEST_TIME'], 2);
			error_log($runTime.' '.$query);
		}
		if ($this->lastResult) {
			$profiler = new Profiler();
			try {
				$ok = $this->lastResult->execute($params);
			} catch (PDOException $e) {
				debug($query, $params, $e->getMessage());
				throw $e;
			}
			$this->queryTime += $profiler->elapsed();
			if (!$ok) {
				debug(array(
					'class' => get_class($this),
					'ok' => $ok,
					'code' => $this->connection->errorCode(),
					'errorInfo' => $this->connection->errorInfo(),
					'query' => $query,
					'connection' => $this->connection,
					'result' => $this->lastResult,
				));
				$e = new DatabaseException(getDebug(array(
						'class' => get_class($this),
						'ok' => $ok,
						'code' => $this->connection->errorCode(),
						'errorInfo' => $this->connection->errorInfo(),
						'query' => $query,
						'connection' => $this->connection,
						'result' => $this->lastResult,
					)),
					$this->connection->errorCode() ?: 0);
				$e->setQuery($query);
				throw $e;
			}
		} else {
			$e = new DatabaseException(getDebug(array(
					'class' => get_class($this),
					'code' => $this->connection->errorCode(),
					'errorInfo' => $this->connection->errorInfo(),
					'query' => $query,
					'connection' => $this->connection,
					'result' => $this->lastResult,
				)),
				$this->connection->errorCode() ?: 0);
			$e->setQuery($query);
			throw $e;
		}
		return $this->lastResult;
	}

	/**
	 * @param $res PDOStatement
	 * @return array|mixed
	 */
	function numRows($res = NULL) {
		$count = $res->rowCount();
		//debug($this->lastQuery, $count, $this->getScheme());
		if ($count == -1 || $this->getScheme() == 'sqlite') {
			$countQuery = 'SELECT count(*) FROM ('.$res->queryString.') AS sub1';
			$rows = $this->fetchAll($countQuery);
			//debug($countQuery, $rows);
			$count = first(first($rows));
		}
		return $count;
	}

	function affectedRows($res = NULL) {
		return $this->lastResult->rowCount();
	}

	function getScheme() {
		$scheme = parse_url($this->dsn);
		$scheme = $scheme['scheme'];
		return $scheme;
	}

	function isMySQL() {
		return $this->getScheme() == 'mysql';
	}

	function isPostgres() {
		return $this->getScheme() == 'psql';
	}

	function isSQLite() {
		return $this->getScheme() == 'sqlite';
	}

	function getTables() {
		$tables = $this->getTablesEx();
		$names = array_keys($tables);
		return $names;
	}

	/**
	 * Keys must be table names
	 * @return array|null
	 * @throws DatabaseException
	 * @throws Exception
	 */
	function getTablesEx() {
		$tables = array();
		$scheme = $this->getScheme();
		if ($scheme == 'mysql') {
			$res = $this->perform('show tables');
			$tables = $res->fetchAll();
			$tables = ArrayPlus::create($tables)->column('0')->getData(); // "Tables_is_DBname"
			$keys = $tables;
			foreach ($tables as &$name) {
				$name = array('table' => $name);
			}
			$tables = array_combine($keys, $tables);
		} elseif ($scheme == 'odbc') {
			$res = $this->perform('db2 list tables for all');
			$tables = $res->fetchAll();
		} elseif ($scheme == 'sqlite') {
			try {
				$file = $this->dsn;
				$file = str_replace('sqlite:', '', $file);
				$db2 = new dbLayerSQLite($file);
				$db2->connect();
				$db2->setQB(new SQLBuilder($db2)); // different DB inside
				$tables = $db2->getTablesEx();
			} catch (Exception $e) {
				throw $e;
			}
		}
		return $tables;
	}

	function lastInsertID($res, $table = NULL) {
		return $this->connection->lastInsertId();
	}

	/**
	 * @param PDOStatement $res
	 */
	function free($res) {
		$res->closeCursor();
	}

	function quoteKey($key) {
		return '`'.$key.'`';
	}

	function escapeBool($value) {
		return intval(!!$value);
	}

	/**
	 * @param $res PDOStatement
	 * @return mixed
	 */
	function fetchAssoc($res) {
		$row = $res->fetch(PDO::FETCH_ASSOC);
		return $row;
	}

	function dataSeek($res, $int) {
		$this->dataSeek = $int;
	}

	function fetchAssocSeek(PDOStatement $res) {
		return $res->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $this->dataSeek);
	}

	function getTableColumnsEx($table) {
		switch ($this->getScheme()) {
			case 'mysql':
				$this->perform('show columns from '.$this->quoteKey($table));
				$tableInfo = $this->fetchAll($this->lastResult, 'Field');
				break;
			case 'sqlite':
				$this->perform('PRAGMA table_info('.$this->quoteKey($table).')');
				$tableInfo = $this->fetchAll($this->lastResult, 'name');
				foreach ($tableInfo as &$row) {
					$row['Field'] = $row['name'];
					$row['Type'] = $row['type'];
					$row['Null'] = $row['notnull'] ? 'NO' : 'YES';
				}
				//debug($tableInfo);
				break;
		}
		return $tableInfo;
	}

	/**
	 * Avoid this as hell, just for compatibility
	 * @param $str
	 * @return string
	 */
	function escape($str) {
		$quoted = $this->connection->quote($str);
		if ($quoted{0} == "'") {
			$quoted = substr($quoted, 1, -1);
		}
		return $quoted;
	}

	function fetchAll($stringOrRes, $key = NULL) {
		if (is_string($stringOrRes)) {
			$res = $this->perform($stringOrRes);
		} else {
			$res = $stringOrRes;
		}
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		//debug($this->lastQuery, $this->result, $data);

		if ($key) {
			$copy = $data;
			$data = [];
			foreach ($copy as $row) {
				$data[$row[$key]] = $row;
			}
		}
		return $data;
	}

	/**
	 * http://stackoverflow.com/questions/15637291/how-use-mysql-data-seek-with-pdo
	 * Will start with 0 and skip rows until $start.
	 * Will end with $start+$limit.
	 * @param $res
	 * @param $start
	 * @param $limit
	 * @return array
	 */
	function fetchPartitionMySQL($res, $start, $limit) {
		$data = array();
		for ($i = 0; $i < $start + $limit; $i++) {
			$row = $this->fetchAssoc($res);
			if ($row !== false) {
				if ($i >= $start) {
					$data[] = $row;
				}
			} else {
				break;
			}
		}
		$this->free($res);
		return $data;
	}

	function uncompress($value) {
		return @gzuncompress(substr($value, 4));
	}

	function transaction() {
		$this->perform('BEGIN');
	}

	function commit() {
		return $this->perform('COMMIT');
	}

	function rollback() {
		return $this->perform('ROLLBACK');
	}

	function getTableColumns($table) {
		$query = "SELECT * FROM ".$table." LIMIT 1";
		$res = $this->perform($query);
		$row = $this->fetchAssoc($res);
		$columns = array_keys($row);
		$columns = array_combine($columns, $columns);
		return $columns;
	}

	function getIndexesFrom($table) {
		return array();
	}

}
