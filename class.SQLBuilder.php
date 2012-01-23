<?php

class SQLBuilder {
	public $found;
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
	 * Enter description here...
	 *
	 * @var MySQL
	 */
	protected $db;

	function __construct(DIContainer $di) {
		if ($di->db) {
			$this->db = $di->db;
		} else {
			$this->db = $GLOBALS['i']->db;
		}
	}

	function quoteKey($key) {
		if (in_array(strtoupper($key), $this->reserved)) {
			$key = '`'.$key.'`';
		}
		return $key;
	}

	function quoteSQL($value) {
		if ($value instanceof AsIs) {
			return $value->__toString();
		} elseif ($value instanceof Time) {
			return "'".$value->__toString()."'";
		} else if ($value === NULL) {
			return "NULL";
		} else if (is_numeric($value)) {
			return $value;
		} else if ($value instanceof AsIs) {
			return $value.'';
		} else if (is_bool($value)) {
			return $value ? 'true' : 'false';
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
	 * Quotes the complete array if neccessary.
	 *
	 * @param unknown_type $a
	 * @return unknown
	 */
	function quoteValues($a) {
		$c = array();
		foreach($a as $b) {
			$c[] = SQLBuilder::quoteSQL($b);
		}
		return $c;
	}

	/**
	 * Quotes the values as quoteValues does, but also puts the key out and the correct comparison.
	 * In other words, it takes care of col = 'NULL' situation and makes it col IS NULL
	 *
	 * @param array $where
	 */
	function quoteWhere(array $where) {
		$set = array();
		foreach ($where as $key => $val) {
			if ($key{strlen($key)-1} != '.') {
				$key = $this->quoteKey($key);
				if ($val instanceof AsIs) {
					$set[] = $key . ' = ' . $val;
				} elseif ($val instanceof AsIsOp) {
					$set[] = $key . ' ' . $val;
				} else if (isset($where[$key.'.']) && $where[$key.'.']['asis']) {
					$set[] = $key . ' ' . $val;
				} else if ($val === NULL) {
					$set[] = "$key IS NULL";
				} else if (in_array($key{strlen($key)-1}, array('>', '<', '<>', '!=', '<=', '>='))) { // TODO: double chars not working
					list($key, $sign) = explode(' ', $key); // need to quote separately
					$key = $this->quoteKey($key);
					$set[] = "$key $sign $val";
				} else if ($val instanceof SQLWherePart) {
					$set[] = $val->__toString();
				} else if (is_bool($val)) {
					$set[] = ($val ? "" : "NOT ") . $key;
				} else if (is_numeric($key)) {
					$set[] = $val;
				} else {
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

		$q = "insert into ".$this->quoteKey($table)." ($set) values ($values)";
		return $q;
	}

	function quoteLike($columns, $like) {
		$set = array();
		foreach ($columns as $key => $val) {
			$key = $this->quoteKey($key);
			$val = $this->quoteSQL($val);
			$from = array('$key', '$val');
			$to = array($key, $val);
			$set[] = str_replace($from, $to, $like);
		}
		return $set;
	}

	function getUpdateQuery($table, $columns, $where) {
		//$columns['mtime'] = date('Y-m-d H:i:s');
		$q = "update $table set ";
		$set = $this->quoteLike($columns, '$key = $val');
		$q .= implode(", ", $set);
		$q .= " where ";
		$q .= implode(" and ", $this->quoteWhere($where));
		return $q;
	}

	function getFirstWord($table) {
		$table1 = explode(' ', $table);
		$table1 = $table1[0];
		return $table1;
	}

	function getSelectQuery($table, array $where = array(), $order = "", $addSelect = '', $exclusiveAdd = FALSE) {
		$table1 = $this->getFirstWord($table);
		$select = $exclusiveAdd ? $addSelect : $this->quoteKey($table1).".* ".$addSelect;
		$q = "SELECT $select FROM " . $this->quoteKey($table);
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= " WHERE " . implode(" AND ", $set);
		}
		$q .= " ".$order;
		return $q;
	}

	function getSelectQuerySW($table, SQLWhere $where, $order = "", $addSelect = '', $exclusiveAdd = FALSE) {
		$table1 = $this->getFirstWord($table);
		$select = $exclusiveAdd ? $addSelect : $this->quoteKey($table1).".* ".$addSelect;
		$q = "SELECT $select FROM " . $this->quoteKey($table);
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= " WHERE " . implode(" AND ", $set);
		}
		$q .= " ".$order;
		return $q;
	}

	function getDeleteQuery($table, $where = array(), $order = "") {
		$q = "DELETE FROM $table ";
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= " WHERE " . implode(" AND ", $set);
		} else {
			$q .= ' WHERE 1 = 0'; // avoid truncate()
		}
		return $q;
	}

	function getDefaultInsertFields() {
		return array();
	}

	function array_intersect($array, $field) {
		//$res[] = "(string_to_array('".implode(',', $value)."', ',')) <@ (string_to_array(bug.".$field.", ','))";
		// why didn't it work and is commented?

		//debug($array);
		if (sizeof($array)) {
			$or = array();
			foreach ($array as $langID) {
				$or[] = "'" . $langID . "' = ANY(string_to_array(".$field.", ','))";
			}
			$content = '('.implode(' OR ', $or).')';
		} else {
			$content = ' 1 = 1 ';
		}
		return $content;
	}

	function runSelectQuery($table, array $where = array(), $order = '', $addSelect = '', $exclusiveAdd = FALSE) {
		$query = $this->getSelectQuery($table, $where, $order, $addSelect, $exclusiveAdd);
		//debug($query);
		$res = $this->db->perform($query);
		return $res;
	}

	function runSelectQuerySW($table, SQLWhere $where, $order = '', $addSelect = '', $exclusiveAdd = FALSE) {
		$query = $this->getSelectQuerySW($table, $where, $order, $addSelect, $exclusiveAdd);
		//debug($query);
		$res = $this->db->perform($query);
		return $res;
	}

	function runInsertUpdateQuery($table, array $fields, array $where) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->db->transaction();
		$res = $this->runSelectQuery($table, $where);
		if ($this->db->numRows($res)) {
			$query = $this->getUpdateQuery($table, $fields, $where);
			$inserted = 2;
		} else {
			$query = $this->getInsertQuery($table, $fields + array('ctime' => NULL));
			$inserted = TRUE;
		}
		//debug($query);
		$this->found = $this->db->fetchAssoc($res);
		$res = $this->db->perform($query);
		$this->db->commit();
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $inserted;
	}

	function runInsertNew($table, array $fields) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$res = $this->runSelectQuery($table, $fields);
		if (!$this->db->numRows($res)) {
			$query = $this->getInsertQuery($table, $fields);
			//debug($query);
			$resInsert = $this->db->perform($query);
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
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

	function fetchSelectQuery($table, array $where, $order = "", $addSelect = '', $exclusiveAdd = FALSE) {
		$query = $this->getSelectQuery($table, $where, $order, $addSelect, $exclusiveAdd);
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
			$where[] = new AsIs('('.implode(' OR ', $like).')');
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

}
