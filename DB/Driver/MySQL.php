<?php

/**
 * Class MySQL
 * @mixin SQLBuilder
 */
class MySQL extends dbLayerBase implements DBInterface
{

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * @var resource
	 */
	public $lastResult;

	/**
	 * @var resource
	 */
	public $connection;

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * Reserved MySQL words
	 * @var array
	 */
	protected $reserved = [
		0 => 'ACCESSIBLE',
		1 => 'ADD',
		2 => 'ALL',
		3 => 'ANALYZE',
		4 => 'AND',
		5 => 'AS',
		6 => 'ASC',
		7 => 'ASENSITIVE',
		8 => 'BEFORE',
		9 => 'BETWEEN',
		10 => 'BIGINT',
		11 => 'BINARY',
		12 => 'BLOB',
		13 => 'BOTH',
		14 => 'CALL',
		15 => 'CASCADE',
		16 => 'CASE',
		17 => 'CHANGE',
		18 => 'CHAR',
		19 => 'CHARACTER',
		20 => 'CHECK',
		21 => 'COLLATE',
		22 => 'COLUMN',
		23 => 'CONDITION',
		24 => 'CONSTRAINT',
		25 => 'CONVERT',
		26 => 'CREATE',
		27 => 'CROSS',
		28 => 'CURRENT_DATE',
		29 => 'CURRENT_TIME',
		30 => 'CURRENT_TIMESTAMP',
		31 => 'CURRENT_USER',
		32 => 'CURSOR',
		33 => 'DATABASES',
		34 => 'DAY_HOUR',
		35 => 'DAY_MICROSECOND',
		36 => 'DAY_MINUTE',
		37 => 'DAY_SECOND',
		38 => 'DEC',
		39 => 'DECIMAL',
		40 => 'DECLARE',
		41 => 'DELAYED',
		42 => 'DELETE',
		43 => 'DESC',
		44 => 'DESCRIBE',
		45 => 'DETERMINISTIC',
		46 => 'DISTINCT',
		47 => 'DISTINCTROW',
		48 => 'DIV',
		49 => 'DROP',
		50 => 'DUAL',
		51 => 'EACH',
		52 => 'ELSE',
		53 => 'ELSEIF',
		54 => 'ENCLOSED',
		55 => 'ESCAPED',
		56 => 'EXISTS',
		57 => 'EXPLAIN',
		58 => 'FALSE',
		59 => 'FETCH',
		60 => 'FLOAT',
		61 => 'FLOAT4',
		62 => 'FLOAT8',
		63 => 'FOR',
		64 => 'FORCE',
		65 => 'FOREIGN',
		66 => 'FROM',
		67 => 'FULLTEXT',
		68 => 'GROUP',
		69 => 'HAVING',
		70 => 'HIGH_PRIORITY',
		71 => 'HOUR_MICROSECOND',
		72 => 'HOUR_MINUTE',
		73 => 'HOUR_SECOND',
		74 => 'IF',
		75 => 'IGNORE',
		76 => 'INDEX',
		77 => 'INFILE',
		78 => 'INNER',
		79 => 'INOUT',
		80 => 'INSENSITIVE',
		81 => 'INSERT',
		82 => 'INT',
		83 => 'INT1',
		84 => 'INT2',
		85 => 'INT3',
		86 => 'INT4',
		87 => 'INTEGER',
		88 => 'INTERVAL',
		89 => 'INTO',
		90 => 'IS',
		91 => 'ITERATE',
		92 => 'JOIN',
		93 => 'KEY',
		94 => 'KEYS',
		95 => 'KILL',
		96 => 'LEADING',
		97 => 'LEAVE',
		98 => 'LIKE',
		99 => 'LIMIT',
		100 => 'LINEAR',
		101 => 'LINES',
		102 => 'LOAD',
		103 => 'LOCALTIME',
		104 => 'LOCALTIMESTAMP',
		105 => 'LOCK',
		106 => 'LONG',
		107 => 'LONGBLOB',
		108 => 'LONGTEXT',
		109 => 'LOW_PRIORITY',
		110 => 'MASTER_SSL_VERIFY_SERVER_CERT',
		111 => 'MATCH',
		112 => 'MEDIUMBLOB',
		113 => 'MEDIUMINT',
		114 => 'MIDDLEINT',
		115 => 'MINUTE_MICROSECOND',
		116 => 'MINUTE_SECOND',
		117 => 'MOD',
		118 => 'MODIFIES',
		119 => 'NATURAL',
		120 => 'NOT',
		121 => 'NO_WRITE_TO_BINLOG',
		122 => 'NUMERIC',
		123 => 'ON',
		124 => 'OPTIMIZE',
		125 => 'OPTION',
		126 => 'OPTIONALLY',
		127 => 'OR',
		128 => 'ORDER',
		129 => 'OUT',
		130 => 'OUTER',
		131 => 'OUTFILE',
		132 => 'PRECISION',
		133 => 'PROCEDURE',
		134 => 'PURGE',
		135 => 'RANGE',
		136 => 'READ',
		137 => 'READS',
		138 => 'READ_WRITE',
		139 => 'REAL',
		140 => 'REFERENCES',
		141 => 'REGEXP',
		142 => 'RELEASE',
		143 => 'RENAME',
		144 => 'REPLACE',
		145 => 'REQUIRE',
		146 => 'RESTRICT',
		147 => 'RETURN',
		148 => 'REVOKE',
		149 => 'RIGHT',
		150 => 'RLIKE',
		151 => 'SCHEMA',
		152 => 'SCHEMAS',
		153 => 'SECOND_MICROSECOND',
		154 => 'SELECT',
		155 => 'SEPARATOR',
		156 => 'SET',
		157 => 'SHOW',
		158 => 'SMALLINT',
		159 => 'SPATIAL',
		160 => 'SPECIFIC',
		161 => 'SQL',
		162 => 'SQLEXCEPTION',
		163 => 'SQLWARNING',
		164 => 'SQL_BIG_RESULT',
		165 => 'SQL_CALC_FOUND_ROWS',
		166 => 'SQL_SMALL_RESULT',
		167 => 'SSL',
		168 => 'STARTING',
		169 => 'STRAIGHT_JOIN',
		170 => 'TABLE',
		171 => 'THEN',
		172 => 'TINYBLOB',
		173 => 'TINYINT',
		174 => 'TINYTEXT',
		175 => 'TO',
		176 => 'TRAILING',
		177 => 'TRIGGER',
		178 => 'TRUE',
		179 => 'UNION',
		180 => 'UNIQUE',
		181 => 'UNLOCK',
		182 => 'UNSIGNED',
		183 => 'UPDATE',
		184 => 'USAGE',
		185 => 'USE',
		186 => 'USING',
		187 => 'UTC_DATE',
		188 => 'UTC_TIME',
		189 => 'UTC_TIMESTAMP',
		190 => 'VARBINARY',
		191 => 'VARCHAR',
		192 => 'VARCHARACTER',
		193 => 'VARYING',
		194 => 'WHEN',
		195 => 'WHERE',
		196 => 'WHILE',
		197 => 'WITH',
		198 => 'WRITE',
		199 => 'XOR',
		200 => 'YEAR_MONTH',
		201 => 'ZEROFILL',
		'ALTER',
		'BY',
		'CONTINUE',
		'DATABASE',
		'DEFAULT',
		'DOUBLE',
		'EXIT',
		'GRANT',
		'IN',
		'INT8',
		'LEFT',
		'LOOP',
		'MEDIUMTEXT',
		'NULL',
		'PRIMARY',
		'REPEAT',
		'SENSITIVE',
		'SQLSTATE',
		'TERMINATED',
		'UNDO',
		'VALUES',
	];

	function __construct($db = NULL, $host = '127.0.0.1', $login = 'root', $password = '')
	{
		TaylorProfiler::start(__METHOD__);
		$this->database = $db;
		if ($this->database) {
			$this->connect($host, $login, $password, true);
		}
		if (DEVELOPMENT) {
			$this->queryLog = new QueryLog();
		}
		TaylorProfiler::stop(__METHOD__);
	}

	function connect($host, $login, $password, $newConnection = false)
	{
		TaylorProfiler::start(__METHOD__);
		//echo __METHOD__.'<br />';
		//ini_set('mysql.connect_timeout', 3);
		if ($newConnection) {
			$this->connection = mysql_connect($host, $login, $password, $newConnection);
		} else {
			// important to say new_link = true for MSSQL
			$this->connection = @mysql_pconnect($host, $login, $password);
		}
		if (!$this->connection) {
			TaylorProfiler::stop(__METHOD__);
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_select_db($this->database, $this->connection);
		if (!$res) {
			TaylorProfiler::stop(__METHOD__);
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_set_charset('utf8', $this->connection);
		if (!$res) {
			TaylorProfiler::stop(__METHOD__);
			throw new Exception(mysql_error(), mysql_errno());
		}
		TaylorProfiler::stop(__METHOD__);
	}

	function perform($query, array $params = [])
	{
		if (isset($GLOBALS['profiler'])) {
			$c = 2;
			do {
				$caller = Debug::getCaller($c);
				$c++;
			} while (in_array($caller, [
				'MySQL::fetchSelectQuery',
				'MySQL::runSelectQuery',
				'OODBase::findInDB',
				'MySQL::fetchAll',
				'FlexiTable::findInDB',
				'MySQL::getTableColumns',
				'MySQL::perform',
				'OODBase::fetchFromDB',
			]));
			$profilerKey = __METHOD__ . " (" . $caller . ")";
			TaylorProfiler::start($profilerKey);
		}
		if ($this->logToLog) {
			$runTime = number_format(microtime(true) - $_SERVER['REQUEST_TIME'], 2);
			error_log($runTime . ' ' . str_replace("\n", ' ', $query));
		}

		$start = microtime(true);

		if ($params) {
			die('Old MySQL PHP extension does not support parameters');
		}
		$res = $this->lastResult = @mysql_query($query, $this->connection);


		if (!is_null($this->queryLog)) {
			$diffTime = microtime(true) - $start;
			$this->queryLog->log($query, $diffTime, $this->numRows($res));
		}
		$this->lastQuery = $query;

		if (isset($profilerKey)) {
			TaylorProfiler::stop($profilerKey);
		}
		if (!$res || mysql_errno($this->connection)) {
			if (DEVELOPMENT) {
				debug([
					'code' => mysql_errno($this->connection),
					'text' => mysql_error($this->connection),
					'query' => $query,
				]);
			}
			debug_pre_print_backtrace();
			$e = new DatabaseException(mysql_errno($this->connection) . ': ' . mysql_error($this->connection) .
				(DEVELOPMENT ? '<br>Query: ' . $this->lastQuery : '')
				, mysql_errno($this->connection));
			$e->setQuery($this->lastQuery);
			throw $e;
		}
		return $res;
	}

	function fetchAssoc($res)
	{
		$key = __METHOD__ . ' (' . $this->lastQuery . ')';
		//TaylorProfiler::start($key);
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		if (is_resource($res)) {
			$row = mysql_fetch_assoc($res);
		} else {
			debug('is not a resource', $this->lastQuery, $res);
			debug_pre_print_backtrace();
			exit();
		}
		//TaylorProfiler::stop($key);
		return $row;
	}

	function fetchAssocSeek($res)
	{
		return $this->fetchAssoc($res);
	}

	function fetchRow($res)
	{
		TaylorProfiler::start(__METHOD__);
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$row = mysql_fetch_row($res);
		TaylorProfiler::stop(__METHOD__);
		return $row;
	}

	function free($res)
	{
		if (is_resource($res)) {
			mysql_free_result($res);
		}
	}

	function numRows($res = NULL)
	{
		if (is_resource($res ? $res : $this->lastResult)) {
			return mysql_num_rows($res ? $res : $this->lastResult);
		}
		return NULL;
	}

	function dataSeek($res, $number)
	{
		return mysql_data_seek($res, $number);
	}

	function lastInsertID($res, $table = NULL)
	{
		return mysql_insert_id($this->connection);
	}

	function transaction()
	{
		//echo '<div class="error">Transaction BEGIN</div>';
		return $this->perform('BEGIN');
	}

	function commit()
	{
		//echo '<div class="error">Transaction COMMIT</div>';
		return $this->perform('COMMIT');
	}

	function rollback()
	{
		//echo '<div class="error">Transaction ROLLBACK</div>';
		return $this->perform('ROLLBACK');
	}

	function escape($string)
	{
		return mysql_real_escape_string($string, $this->connection);
	}

	function quoteSQL($string)
	{
		if ($string instanceof Time) {
			$string = $string->getMySQL();
		}
		return "'" . $this->escape($string) . "'";
	}

	function getDatabaseCharacterSet()
	{
		return current($this->fetchAssoc('show variables like "character_set_database"'));
	}

	/**
	 * @return string[]
	 */
	function getTables()
	{
		$list = $this->fetchAll('SHOW TABLES');
		foreach ($list as &$row) {
			$row = current($row);
		}
		return $list;
	}

	function getTableCharset($table)
	{
		$query = "SELECT CCSA.* FROM information_schema.`TABLES` T,
    	information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
		WHERE CCSA.collation_name = T.table_collation
		/*AND T.table_schema = 'schemaname'*/
		AND T.table_name = '" . $table . "'";
		$row = $this->fetchAssoc($query);
		return $row;
	}

	function getTableColumns($table)
	{
		$details = $this->getTableColumnsEx($table);
		$keys = array_keys($details);
		$columns = array_combine($keys, $keys);
		return $columns;
	}

	/**
	 * Return a 2D array
	 * @param $table
	 * @return array
	 */
	function getTableColumnsEx($table)
	{
		TaylorProfiler::start(__METHOD__ . " ({$table})" . Debug::getCaller());
		if ($this->numRows($this->perform("SHOW TABLES LIKE '" . $this->escape($table) . "'"))) {
			$query = "SHOW FULL COLUMNS FROM " . $this->quoteKey($table);
			$res = $this->perform($query);
			$columns = $this->fetchAll($res, 'Field');
		} else {
			$columns = [];
		}
		TaylorProfiler::stop(__METHOD__ . " ({$table})" . Debug::getCaller());
		return $columns;
	}

	function __call($method, array $params)
	{
		if (!$this->qb) {
			if (class_exists('Config')) {
				$this->qb = Config::getInstance()->getQb();
			} else {
				$this->qb = new SQLBuilder($this);
			}
		}
		if (method_exists($this->qb, $method)) {
			return call_user_func_array([$this->qb, $method], $params);
		} else {
			debug(get_class($this->qb));
			throw new Exception($method . '() not found in ' . get_class($this) . ' and SQLBuilder');
		}
	}

	function quoteKey($key)
	{
		if (in_array($key, $this->reserved)) {
			$key = '`' . trim($key) . '`';
		}
		return $key;
	}

	function switchDB($db)
	{
		$this->database = $db;
		mysql_select_db($this->database);
	}

	function affectedRows($res = NULL)
	{
		return mysql_affected_rows();
	}

	function getIndexesFrom($table)
	{
		return $this->fetchAll('SHOW INDEXES FROM ' . $table, 'Key_name');
	}

	function escapeBool($value)
	{
		return intval(!!$value);
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
	function fetchPartitionMySQL($res, $start, $limit)
	{
		$data = [];
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

	function uncompress($value)
	{
		return @gzuncompress(substr($value, 4));
	}

	function getScheme()
	{
		return strtolower(get_class($this));
	}

	function unsetQueryLog()
	{
		$this->queryLog = NULL;
	}

	function getInfo()
	{
		return mysql_info($this->connection);
	}

	/**
	 * @param $field
	 * @return string
	 * @deprecated not supported by MySQL PHP extension
	 */
	function getPlaceholder($field)
	{
		$slug = URL::getSlug($field);
		$slug = str_replace('-', '_', $slug);
		return '@' . $slug;
	}

}
