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
 * @mixin dbLayerBase
 */
class SQLBuilder {

	/**
	 * Update/Insert is storing the found row for debugging
	 * @var mixed
	 */
	public $found;

	/**
	 * @var MySQL
	 */
	public $db;

	/**
	 * @var Config
	 */
	public $config;

	function __construct(dbLayerBase $db) {
		if (class_exists('Config')) {
			$this->config = Config::getInstance();
		}
		$this->db = $db;
	}

	function getDB() {
		return $this->db = $this->db ?: $this->config->getDB();
	}

	function quoteKey($key) {
		$reserved = $this->getReserved();
		if (in_array(strtoupper($key), $reserved)) {
			$key = $this->db->quoteKey($key);
		}
		return $key;
	}

	/**
	 * Used to really quote different values so that they can be attached to "field = "
	 *
	 * @param $value
	 * @param null $key
	 * @throws MustBeStringException
	 * @return string
	 */
	function quoteSQL($value, $key = NULL) {
		if ($value instanceof AsIsOp) {     // check subclass first
			$value->injectDB($this->db);
			$value->injectQB($this);
			$value->injectField($key);
			$result = $value->__toString();
			return $result;
		} elseif ($value instanceof AsIs) {
			$value->injectDB($this->db);
			$value->injectQB($this);
			$value->injectField($key);
			return $value->__toString();
		} elseif ($value instanceof SQLOr) {
			return $value->__toString();
		} elseif ($value instanceof IndTime) {
			return $this->quoteSQL($value->getMySQL(), $key);
		} elseif ($value instanceof SQLDate) {
			$content = "'".$this->db->escape($value->__toString())."'";
			//debug($content, $value);
			return $content;
		} else if ($value instanceof Time) {
			$content = "'".$this->db->escape($value->toSQL())."'";
			//debug($content);
			return $content;
		} else if ($value instanceof SimpleXMLElement && $this->getScheme() == 'mysql') {
			return "COMPRESS('".$this->db->escape($value->asXML())."')";
		} elseif (is_object($value)) {
			return "'".$this->db->escape($value)."'";
		} elseif ($value === NULL) {
			return "NULL";
		} elseif (is_numeric($value) && !$this->isExp($value)) {
			//$set[] = "($key = ".$val." OR {$key} = '".$val."')";
			return "'".$value."'";	// quoting will not hurt, but will keep leading zeroes if necessary
			// /* numeric */";		// this makes SQLQuery not work
		} elseif (is_bool($value)) {
			return $this->db->escapeBool($value);
		} elseif (is_scalar($value)) {
			$sql = "'".$this->db->escape($value)."'";
			if ($this->db->getScheme() == 'ms') {
				$sql = 'N'.$sql;	// UTF-8 encoding
			}
			return $sql;
		} else {
			debug($key, $value);
			throw new MustBeStringException('Must be string.');
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
		foreach ($a as $key => $b) {
			$c[] = SQLBuilder::quoteSQL($b, $key);
		}
		return $c;
	}

	function quoteKeys($a) {
		$c = array();
		foreach ($a as $b) {
			$c[] = $this->quoteKey($b);
		}
		return $c;
	}

	/**
	 * Quotes the values as quoteValues does, but also puts the key out and the correct comparison.
	 * In other words, it takes care of col = 'NULL' situation and makes it 'col IS NULL'
	 *
	 * @param array $where
	 * @throws Exception
	 * @throws MustBeStringException
	 * @return array
	 */
	function quoteWhere(array $where) {
		$set = array();
		foreach ($where as $key => $val) {
			if (!strlen($key) || (strlen($key) && $key{strlen($key)-1} != '.')) {
				$key = $this->quoteKey($key);
//				debug($key);
				if (false) {

				} elseif ($val instanceof AsIsOp) {       // check subclass first
					$val->injectDB($this->db);
					$val->injectField($key);
					if (is_numeric($key)) {
						$set[] = $val;
					} else {
						$set[] = $key . ' ' . $val;
					}
				} elseif ($val instanceof AsIs) {
					$val->injectDB($this->db);
					$val->injectQB($this);
					$val->injectField($key);
					$set[] = $key . ' = ' . $val;
				} elseif ($val instanceof SQLBetween) {
					$val->injectQB($this);
					$val->injectField($key);
					$set[] = $val->toString($key);
				} elseif ($val instanceof SQLWherePart) {
					$val->injectQB($this);
					if (!is_numeric($key)) {
						$val->injectField($key);
					}
					$set[] = $val->__toString();
				} elseif ($val instanceof SimpleXMLElement) {
					$set[] = $val->asXML();
				//} else if (is_object($val)) {	// what's that for? SQLWherePart has been taken care of
				//	$set[] = $val.'';
				} elseif (isset($where[$key.'.']) && ifsetor($where[$key.'.']['asis'])) {
					if (strpos($val, '###FIELD###') !== FALSE) {
						$val = str_replace('###FIELD###', $key, $val);
						$set[] = $val;
					} else {
						$set[] = '('.$key . ' ' . $val.')';	// for GloRe compatibility - may contain OR
					}
				} elseif ($val === NULL) {
					$set[] = "$key IS NULL";
				} elseif ($val === 'NOTNULL') {
					$set[] = "$key IS NOT NULL";
				} elseif (in_array($key{strlen($key)-1}, array('>', '<'))
                    || in_array(substr($key, -2), array('!=', '<=', '>=', '<>'))) {
					list($key, $sign) = explode(' ', $key); // need to quote separately
					$key = $this->quoteKey($key);
					$set[] = "$key $sign '$val'";
				} elseif (is_bool($val)) {
					$set[] = ($val ? "" : "NOT ") . $key;
				} elseif (is_numeric($key)) {		// KEY!!!
					$set[] = $val;
				} elseif (is_array($val) && $where[$key.'.']['makeIN']) {
					$set[] = $key." IN ('".implode("', '", $val)."')";
				} elseif (is_array($val) && $where[$key.'.']['makeOR']) {
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
					try {
						$val = SQLBuilder::quoteSQL($val);
					} catch (MustBeStringException $e) {
						debug($key);
						throw $e;
					}
					$set[] = "$key = $val";
				}
			}
		}
		//debug($set);
		return $set;

	}

	/**
	 * @param string $table Table name
	 * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
	 * @return string
	 */
	function getInsertQuery($table, $columns) {
		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		$q = "INSERT INTO {$table} ({$fields}) VALUES ({$values})";
		return $q;
	}

	/**
	 * @param string $table Table name
	 * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
	 * @return string
	 */
	function getReplaceQuery($table, $columns) {
		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		$q = "REPLACE INTO {$table} ({$fields}) VALUES ({$values})";
		return $q;
	}

	/**
	 *
	 * @param $columns  [a => b, c => d]
	 * @param $like     "$key ILIKE '%$val%'"
	 * @return array    [a ILIKE '%b%', c ILIKE '%d%']
	 * @throws MustBeStringException
	 */
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

	/**
	 * @param string $table
	 * @param array $columns
	 * @param array $where
	 * @return string
	 */
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
		$table1 = trimExplode(' ', $table);
		$table0 = $table1[0];
		$table1 = trimExplode("\t", $table0);
		$table0 = $table1[0];
		$table1 = trimExplode("\n", $table0);
		$table0 = $table1[0];
		//debug($table, $table1, $table0);
		return $table0;
	}

	function getSelectQueryString($table, array $where = array(), $order = "", $addSelect = '') {
		$table1 = $this->getFirstWord($table);
		if ($table == $table1) {
			$from = $this->db->quoteKey($table);    // table name always quoted
		} else {
			$from = $table; // not quoted
		}
		$select = $addSelect ? $addSelect : $this->quoteKey($table1).".*";
		$q = "SELECT $select\nFROM " . $from;
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= "\nWHERE\n" . implode("\nAND ", $set);
		}
		$q .= "\n".$order;
		return $q;
	}

	function getSelectQuery($table, array $where = array(), $order = '', $addSelect = NULL) {
		return $this->getSelectQueryP($table, $where, $order, $addSelect);
	}

	/**
	 * @param        $table
	 * @param array  $where
	 * @param string $order
	 * @param null   $addSelect
	 * @return SQLSelectQuery
	 */
	function getSelectQueryP($table, array $where = array(), $order = '', $addSelect = NULL) {
		$table1 = $this->getFirstWord($table);
		if ($table == $table1) {
			$from = $this->db->quoteKey($table);    // table name always quoted
		} else {
			$from = $table; // not quoted
		}
		$select = $addSelect ? $addSelect : $this->quoteKey($table1).".*";
		$select = new SQLSelect($select);
		$select->db = $this->db;
		$from = new SQLFrom($from);
		$from->db = $this->db;
		$where = new SQLWhere($where);
		$where->db = $this->db;
		$order = new SQLORder($order);
		$order->db = $this->db;
		$sq = new SQLSelectQuery($select, $from, $where, NULL, NULL, NULL, $order);
		$sq->db = $this->db;
		return $sq;
	}

	function getSelectQuerySW($table, SQLWhere $where, $order = "", $addSelect = '') {
		$table1 = $this->getFirstWord($table);
		$select = $addSelect ? $addSelect : $this->quoteKey($table1).".*";
		$q = "SELECT $select\nFROM " . $this->quoteKey($table);
		$q .= $where->__toString();
		$q .= "\n".$order;
		return $q;
	}

	/**
	 * @param $table
	 * @param array $where
	 * @param string $what [LOW_PRIORITY] [QUICK] [IGNORE]
	 * @return string
	 * @throws MustBeStringException
	 */
	function getDeleteQuery($table, $where = array(), $what = '') {
		$q = "DELETE ".$what." FROM ".$this->db->quoteKey($table)." ";
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
	 * 2010/09/12: modified according to mantis request 0001812    - 4th argument added
	 * @param $array
	 * @param $field
	 * @param string $joiner
	 * @param string $conditioner
	 * @return string
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
		TaylorProfiler::start(__METHOD__);
		$this->db->transaction();
		$res = $this->runSelectQuery($table, $where);
		$this->found = $this->fetchAssoc($res);
		if ($this->db->numRows($res)) {
			$query = $this->getUpdateQuery($table, $fields, $where);
			$this->perform($query);
			$inserted = $this->found['id'];
		} else {
			$query = $this->getInsertQuery($table, $fields + $where + $insert);
			// array('ctime' => NULL) #TODO: make it manually now
			$res = $this->perform($query);
			$inserted = $this->db->lastInsertID($res, $table);
		}
		//debug($query);
		$this->db->commit();
		TaylorProfiler::stop(__METHOD__);
		return $inserted;
	}

	/**
	 * Inserts only if not yet found.
	 *
	 * @param $table
	 * @param array $fields
	 * @param array $insert
	 * @throws Exception
	 * @return resource
	 */
	function runInsertNew($table, array $fields, array $insert = array()) {
		TaylorProfiler::start(__METHOD__);
		$resInsert = NULL;
		$res = $this->runSelectQuery($table, $fields);
		if (!$this->db->numRows($res)) {
			$query = $this->getInsertQuery($table, $fields + $insert);
			//debug($query);
			$resInsert = $this->db->perform($query);
		}
		TaylorProfiler::stop(__METHOD__);
		return $resInsert;
	}

	function runInsertQuery($table, array $columns) {
		TaylorProfiler::start(__METHOD__.'('.$table.')');
		$query = $this->getInsertQuery($table, $columns);
		$ret = $this->db->perform($query);
		TaylorProfiler::stop(__METHOD__.'('.$table.')');
		return $ret;
	}

	function runReplaceQuery($table, array $columns) {
		TaylorProfiler::start(__METHOD__.'('.$table.')');
		$query = $this->getReplaceQuery($table, $columns);
		$ret = $this->db->perform($query);
		TaylorProfiler::stop(__METHOD__.'('.$table.')');
		return $ret;
	}

	function getFoundOrLastID($inserted) {
		if ($inserted) {
			$authorID = $this->db->lastInsertID($inserted);
		} else {
			$authorID = $this->found['id'];
		}
		return $authorID;
	}

	/**
	 * Return ALL rows
	 * This used to retrieve a single row !!!
	 * @param string $table
	 * @param array $where
	 * @param string $order
	 * @param string $addFields
	 * @param string $idField   - will return data as assoc indexed by this column
	 * @return array <type>
	 */
	function fetchSelectQuery($table, $where = array(), $order = '', $addFields = '', $idField = NULL) {
		// commented to allow working with multiple MySQL objects (SQLBuilder instance contains only one)
		//$res = $this->runSelectQuery($table, $where, $order, $addFields);
		$query = $this->getSelectQuery($table, $where, $order, $addFields);

		//debug($query); if ($_COOKIE['debug']) { exit(); }

		$res = $this->perform($query);
		$data = $this->fetchAll($res, $idField);
		return $data;
	}

	function runUpdateQuery($table, array $columns, array $where) {
		$query = $this->getUpdateQuery($table, $columns, $where);
		return $this->db->perform($query);
	}

	/**
	 * Originates from BBMM
	 * @param string $sword
	 * @param array $fields
	 * @return AsIs
	 */
	function getSearchWhere($sword, array $fields) {
		$where = array();
		$words = $this->getSplitWords($sword);
		foreach ($words as $word) {
			$like = array();
			foreach ($fields as $field) {
				$like[] = $field . " LIKE '%".$this->db->escape($word)."%'";
			}
			$where[] = new AsIsOp(' ('.implode(' OR ', $like).')');
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
		$in = false;
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
		$delete = $this->getDeleteQuery($table, $where);
		//debug($delete);
		return $this->db->perform($delete);
	}

	function __call($method, array $params) {
		return call_user_func_array(array($this->getDB(), $method), $params);
	}

	function getTableOptions($table, $titleField, $where = array(), $order = NULL, $idField = 'id', $prefix = NULL) {
		$prefix = $prefix ?: $table.'.';
		$query = $this->getSelectQuery($table, $where, $order,
			'DISTINCT   '.$prefix.$this->quoteKey($titleField).' AS title, '.
			$prefix.'*, '.$prefix.$this->quoteKey($idField).' AS id_field');
		//debug('Query', $query);
		$res = $this->perform($query);
		$data = $this->fetchAll($res, 'id_field');
		$keys = array_keys($data);
		$values = array_map(create_function('$arr', 'return $arr["title"];'), $data);
		//d($keys, $values);
		if ($keys && $values) {
			$options = array_combine($keys, $values);
		} else {
			$options = array();
		}
		//debug($this->db->lastQuery, @$this->db->numRows($res), $titleField, $idField, $data, $options);
		//		$options = AP($data)->column_assoc($idField, $titleField)->getData();
		return $options;
	}

	/**
	 * @param resource|string $res
	 * @param string $key can be set to NULL to avoid assoc array
	 * @return array
	 */
	function fetchAll($res, $key = NULL) {
		TaylorProfiler::start(__METHOD__);
		if (is_string($res)) {
			$res = $this->db->perform($res);
		}

		$data = array();
		do {
			$row = $this->db->fetchAssoc($res);
			if ($row === FALSE || $row == array()) {
				break;
			}
			if ($key) {
				$data[$row[$key]] = $row;
			} else {
				$data[] = $row;
			}
		} while (true);
		//debug($this->lastQuery, sizeof($data));
		//debug_pre_print_backtrace();
		$this->db->free($res);
		TaylorProfiler::stop(__METHOD__);
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

	function fetchOneSelectQuery($table, $where = array(), $order = '', $selectPlus = '') {
		$query = $this->getSelectQuery($table, $where, $order, $selectPlus);
		$res = $this->db->perform($query);
		$data = $this->db->fetchAssoc($res);
		return $data;
	}

	function runUpdateInsert($table, $set, $where) {
		$found = $this->runSelectQuery($table, $where);
		if ($this->db->numRows($found)) {
			$res = 'update';
			$this->runUpdateQuery($table, $set, $where);
		} else {
			$res = 'insert';
			$this->runInsertQuery($table, $set + $where);
		}
		return $res;
	}

	/**
	 * @param string $table
	 * @param array $where
	 * @param string $order
	 * @param string $selectPlus
	 * @param $key
	 * @return array[]
	 */
	function fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = NULL) {
		$res = $this->runSelectQuery($table, $where, $order, $selectPlus);
		$rows = $this->db->fetchAll($res, $key);
		return $rows;
	}

	function getWhereString(array $where) {
		$set = $this->quoteWhere($where);
		return implode(' AND ', $set);
	}

}
