<?php

/**
 * Class SQLBuilder - contains database unspecific (general) SQL functions.
 * It has $this->db a database specific (PostgreSQL, MySQL, SQLite, Oracle, PDO) class
 * which is performing the actual queries.
 * This $db class has a back reference to this as $this->db->qb == $this.
 * Usage in controllers/models:
 * $this->db = new MySQL();
 * $this->db->qb = new SQLBuilder();
 * $this->db->qb->db = $this->db;
 * $this->db->fetchSelectQuery(...);
 *
 * Note that the creation of objects above is handled by DIContainer
 * but it's not shown above for comprehensibility.
 */
class SQLBuilder {

	/**
	 * Update/Insert is storing the found row for debugging
	 * @var mixed
	 */
	public $found;

	/**
	 * Reserved MySQL words
	 * @var array
	 */
	protected $reserved = array (
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
	);

	/**
	 * @var MySQL|dbLayerBase
	 */
	public $db;

	function __construct(dbLayerBase $db) {
		$this->db = $db;
	}

	function quoteKey($key) {
		if (in_array(strtoupper($key), $this->reserved)) {
			$key = $this->db->quoteKey($key);
		}
		return $key;
	}

	/**
	 * Used to really quote different values so that they can be attached to "field = "
	 *
	 * @param $value
	 * @throws Exception
	 * @internal param $key
	 * @return string
	 */
	function quoteSQL($value) {
		if ($value instanceof AsIs) {
			return $value->__toString();
		} else if ($value instanceof AsIsOp) {
			return $value->__toString();
		} else if ($value instanceof SQLOr) {
			return $value->__toString();
		} else if ($value instanceof Time) {
			return "'".$this->db->escape($value->__toString())."'";
		} else if ($value instanceof SQLDate) {
			return "'".$this->db->escape($value->__toString())."'";
		} else if ($value instanceof AsIs) {
			return $value.'';
		} else if ($value instanceof SimpleXMLElement) {
			return "COMPRESS('".$this->db->escape($value->asXML())."')";
		} else if (is_object($value)) {
			return "'".$this->db->escape($value)."'";
		} else if ($value === NULL) {
			return "NULL";
		} else if (is_numeric($value) && !$this->isExp($value)) {
			//$set[] = "($key = ".$val." OR {$key} = '".$val."')";
			return "'".$value."'";		// quoting will not hurt, but will keep leading zeroes if necessary
		} else if (is_bool($value)) {
			return $this->db->escapeBool($value);
		} else {
			if (is_scalar($value)) {
				return "'".$this->db->escape($value)."'";
			} else {
				debug($value);
				throw new Exception('Must be string.');
			}
		}
	}

	/**
	 * http://stackoverflow.com/a/4964120
	 * @param $number
	 * @return bool
	 */
	function isExp($number) {
		return is_numeric($number) && $number != number_format($number, 0, '', '');
	}

	/**
	 * Quotes the complete array if necessary.
	 *
	 * @param array $a
	 * @return array
	 */
	function quoteValues(array $a) {
		$c = array();
		foreach($a as $key => $b) {
			$c[] = SQLBuilder::quoteSQL($b, $key);
		}
		return $c;
	}

	/**
	 * Quotes the values as quoteValues does, but also puts the key out and the correct comparison.
	 * In other words, it takes care of col = 'NULL' situation and makes it col IS NULL
	 *
	 * @param array $where
	 * @return array
	 */
	function quoteWhere(array $where) {
		$set = array();
		foreach ($where as $key => $val) {
			if ($key{strlen($key)-1} != '.') {
				$key = $this->quoteKey($key);
				if ($val instanceof AsIs) {
					$set[] = $key . ' = ' . $val;
				} elseif ($val instanceof AsIsOp) {
					if (is_numeric($key)) {
						$set[] = $val;
					} else {
						$set[] = $key . ' ' . $val;
					}
				} else if ($val instanceof SQLBetween) {
					$val->injectQB($this);
					$val->injectField($key);
					$set[] = $val->toString($key);
				} else if ($val instanceof SQLWherePart) {
					$val->injectQB($this);
					$val->injectField($key);
					$set[] = $val->__toString();
				} else if ($val instanceof SimpleXMLElement) {
					$set[] = $val->asXML();
				//} else if (is_object($val)) {	// what's that for? SQLWherePart has been taken care of
				//	$set[] = $val.'';
				} else if (isset($where[$key.'.']) && $where[$key.'.']['asis']) {
					if (strpos($val, '###FIELD###') !== FALSE) {
						$val = str_replace('###FIELD###', $key, $val);
						$set[] = $val;
					} else {
						$set[] = '('.$key . ' ' . $val.')';	// for GloRe compatibility - may contain OR
					}
				} else if ($val === NULL) {
					$set[] = "$key IS NULL";
				} else if ($val === 'NOTNULL') {
					$set[] = "$key IS NOT NULL";
				} else if (in_array($key{strlen($key)-1}, array('>', '<'))
                    || in_array(substr($key, -2), array('!=', '<=', '>=', '<>'))) {
					list($key, $sign) = explode(' ', $key); // need to quote separately
					$key = $this->quoteKey($key);
					$set[] = "$key $sign '$val'";
				} else if (is_bool($val)) {
					$set[] = ($val ? "" : "NOT ") . $key;
				} else if (is_numeric($key)) {		// KEY!!!
					$set[] = $val;
				} else if (is_array($val) && $where[$key.'.']['makeIN']) {
					$set[] = $key." IN ('".implode("', '", $val)."')";
				} else if (is_array($val) && $where[$key.'.']['makeOR']) {
					foreach ($val as &$row) {
						if (is_null($row)) {
							$row = $key .' IS NULL';
						} else {
							$row = $key . " = '" . $row . "'";
						}
					}
					$or = new SQLOr($val);
					$or->injectQB($this);
					$set[] = $or;
				} else {
					//debug_pre_print_backtrace();
					$val = SQLBuilder::quoteSQL($val);
					$set[] = "$key = $val";
				}
			}
		}
		return $set;

	}

	function getInsertQuery($table, array $columns) {
		$set = $this->quoteLike($columns, '$key');
		$set = implode(", ", $set);

		//$values = $this->quoteLike($columns, '$val');
		$values = array_values($columns);
		$values = $this->quoteValues($values);
		$values = implode(", ", $values);

		$q = "INSERT INTO ".$this->quoteKey($table)."\n($set)\nVALUES ($values)";
		return $q;
	}

	function quoteLike($columns, $like) {
		$set = array();
		foreach ($columns as $key => $val) {
			$key = $this->quoteKey($key);
			$val = $this->quoteSQL($val, $key);
			$from = array('$key', '$val');
			$to = array($key, $val);
			$set[] = str_replace($from, $to, $like);
		}
		//d($_POST, $_REQUEST, $columns, $set, ini_get('magic_quotes_gpc'), get_magic_quotes_gpc(), get_magic_quotes_runtime());
		return $set;
	}

	function getUpdateQuery($table, $columns, $where) {
		//$columns['mtime'] = date('Y-m-d H:i:s');
		$q = "UPDATE $table\nSET ";
		$set = $this->quoteLike($columns, '$key = $val');
		$q .= implode(",\n", $set);
		$q .= "\nWHERE\n";
		$q .= implode("\nAND ", $this->quoteWhere($where));
		return $q;
	}

	function getFirstWord($table) {
		$table1 = explode(' ', $table);
		$table0 = $table1[0];
		//debug($table, $table1, $table0);
		return $table0;
	}

	function getSelectQuery($table, array $where = array(), $order = "", $addSelect = '') {
		$table1 = $this->getFirstWord($table);
		$select = $addSelect ? $addSelect : $this->quoteKey($table1).".*";
		$q = "SELECT $select\nFROM " . $this->quoteKey($table);
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= "\nWHERE\n" . implode("\nAND ", $set);
		}
		$q .= "\n".$order;
		return $q;
	}

	function getSelectQuerySW($table, SQLWhere $where, $order = "", $addSelect = '') {
		$table1 = $this->getFirstWord($table);
		$select = $addSelect ? $addSelect : $this->quoteKey($table1).".*";
		$q = "SELECT $select\nFROM " . $this->quoteKey($table);
		$q .= $where->__toString();
		$q .= "\n".$order;
		return $q;
	}

	function getDeleteQuery($table, $where = array(), $what = '') {
		$q = "DELETE ".$what." FROM $table ";
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= "\nWHERE " . implode(" AND ", $set);
		} else {
			$q .= "\nWHERE 1 = 0"; // avoid truncate()
		}
		return $q;
	}

	function getDefaultInsertFields() {
		return array();
	}

	/**
	 * 2010/09/12: modified according to mantis request 0001812	- 4th argument added
	 */
	static function array_intersect($array, $field, $joiner = 'OR', $conditioner = 'ANY') {
		//$res[] = "(string_to_array('".implode(',', $value)."', ',')) <@ (string_to_array(bug.".$field.", ','))";
		// why didn't it work and is commented?

		//debug($array);
		if (sizeof($array)) {
			$or = array();
			foreach ($array as $langID) {
				//2010/09/12: modified according to mantis request 0001812	- if/else condition for 4th argument added
				if ($conditioner == 'ANY') {
					$or[] = "'" . $langID . "' = ANY(string_to_array(".$field.", ','))"; // this line is the original one
				} else {
					$or[] = "'" . $langID . "' = ".$field." ";
				}
			}
			$content = '('.implode(' '.$joiner.' ', $or).')';
		} else {
			$content = ' 1 = 1 ';
		}
		return $content;
	}

	function runSelectQuery($table, array $where = array(), $order = '', $addSelect = '') {
		$query = $this->getSelectQuery($table, $where, $order, $addSelect);
		//debug($query);
		$res = $this->db->perform($query);
		return $res;
	}

	function runSelectQuerySW($table, SQLWhere $where, $order = '', $addSelect = '') {
		$query = $this->getSelectQuerySW($table, $where, $order, $addSelect);
		//debug($query);
		$res = $this->db->perform($query);
		return $res;
	}

	/**
	 * Will search for $where and then either
	 * - update $fields + $where or
	 * - insert $fields + $where + $insert
	 * @param $table
	 * @param array $fields
	 * @param array $where
	 * @param array $insert
	 * @return bool|int
	 */
	function runInsertUpdateQuery($table, array $fields, array $where, array $insert = array()) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->db->transaction();
		$res = $this->runSelectQuery($table, $where);
		if ($this->db->numRows($res)) {
			$query = $this->getUpdateQuery($table, $fields, $where);
			$inserted = 2;
		} else {
			$query = $this->getInsertQuery($table, $fields + $where + $insert);
			// array('ctime' => NULL) #TODO: make it manually now
			$inserted = TRUE;
		}
		//debug($query);
		$this->found = $this->db->fetchAssoc($res);
		$this->db->perform($query);
		$this->db->commit();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $inserted;
	}

	/**
	 * Inserts only if not yet found.
	 *
	 * @param $table
	 * @param array $fields
	 * @param array $insert
	 * @return resource
	 */
	function runInsertNew($table, array $fields, array $insert = array()) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$res = $this->runSelectQuery($table, $fields);
		if (!$this->db->numRows($res)) {
			$query = $this->getInsertQuery($table, $fields + $insert);
			//debug($query);
			$resInsert = $this->db->perform($query);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $resInsert;
	}

	function runInsertQuery($table, array $columns) {
		$query = $this->getInsertQuery($table, $columns);
		return $this->db->perform($query);
	}

	function getFoundOrLastID($inserted) {
		if ($inserted) {
			$authorID = $this->db->lastInsertID();
		} else {
			$authorID = $this->found['id'];
		}
		return $authorID;
	}

	function fetchSelectQuery($table, array $where, $order = "", $addSelect = '') {
		$query = $this->getSelectQuery($table, $where, $order, $addSelect);
		$data = $this->db->fetchAll($this->db->perform($query));
		return $data;
	}

	function runUpdateQuery($table, array $columns, array $where) {
		$query = $this->getUpdateQuery($table, $columns, $where);
		return $this->db->perform($query);
	}

	/**
	 * Originates from BBMM
	 * @param type $sword
	 * @param array $fields
	 * @return AsIs
	 */
	function getSearchWhere($sword, array $fields) {
		$where = array();
		$words = $this->getSplitWords($sword);
		foreach ($words as $word) {
			$like = array();
			foreach ($fields as $field) {
				$like[] = $field . " LIKE '%".mysql_real_escape_string($word)."%'";
			}
			$where['0'] = new AsIsOp(' = 0 AND ('.implode(' OR ', $like).')');
		}
		//debug($where);
		return $where;
	}

	function getSplitWords($sword) {
		$sword = trim($sword);
		$words = explode(' ', $sword);
		$words = array_map('trim', $words);
		$words = array_filter($words);
		$words = array_unique($words);
		//$words = $this->combineSplitTags($words);
		$words = array_values($words);
		return $words;
	}

	function combineSplitTags($words) {
		$new = array();
		$i = 0;
		foreach ($words as $word) {
			$word = new String($word);
			if ($word->contains('[')) {
				++$i;
				$in = true;
			}
			$new[$i] = $new[$i] ? $new[$i] . ' ' . $word : $word.'';
			if (!$in || ($in && $word->contains(']'))) {
				++$i;
				$in = false;
			}
		}
		//debug(array($words, $new));
		return $new;
	}

	function runDeleteQuery($table, array $where) {
		return $this->db->perform($this->getDeleteQuery($table, $where));
	}

	function __call($method, array $params) {
		return call_user_func_array(array($this->db, $method), $params);
	}

	function getTableOptions($table, $titleField, $where = array(), $order = NULL, $idField = NULL) {
		$res = $this->runSelectQuery($table, $where, $order,
			'DISTINCT '.$this->quoteKey($titleField).' AS title'.
			($idField ? ', '.$this->quoteKey($idField).' AS id_field' : ''),
			true);
		//debug($this->db->lastQuery, $this->db->numRows($res), $idField);
		if ($idField) {
			$data = $this->fetchAll($res, 'id_field');
		} else {
			$data = $this->fetchAll($res, 'title');
		}
		$keys = array_keys($data);
		$values = array_map(create_function('$arr', 'return $arr["title"];'), $data);
		//d($keys, $values);
		if ($keys && $values) {
			$options = array_combine($keys, $values);
		} else {
			$options = array();
		}
		//		$options = AP($data)->column_assoc($idField, $titleField)->getData();
		return $options;
	}

	/**
	 * @param resource|string $res
	 * @param string $key can be set to NULL to avoid assoc array
	 * @return array
	 */
	function fetchAll($res, $key = NULL) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (is_string($res)) {
			$res = $this->perform($res);
		}

		$data = array();
		while (($row = $this->fetchAssoc($res)) !== FALSE) {
			if ($key) {
				$data[$row[$key]] = $row;
			} else {
				$data[] = $row;
			}
		}
		//debug($this->lastQuery, sizeof($data));
		//debug_pre_print_backtrace();
		$this->free($res);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $data;
	}

	/**
	 * @var string $query
	 * @return resource
	 */
	function getIterator($query) {
		if ($this->db instanceof dbLayerPDO) {
			$res = $this->db->perform($query);
			return $res;
		} else {
			$f = new DatabaseResultIteratorAssoc($this->db);
			$f->perform($query);
			return $f;
		}
	}

}
