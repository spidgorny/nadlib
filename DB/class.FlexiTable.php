<?php

class FlexiTable extends OODBase {
	protected $columns = array();
	protected $doCheck = false;

	/**
	 * array(
	 * 		$table => array('id' => ...)
	 * )
	 *
	 * @var array
	 */
	static protected $tableColumns = array();

	function __construct($id = NULL) {
		parent::__construct($id);
		//debug(Config::getInstance()->config[__CLASS__]);
		$this->doCheck = Config::getInstance()->config[__CLASS__]['doCheck'];
		if ($this->doCheck) {
			$this->checkCreateTable();
		}
	}

	function insert(array $row) {
		$row['ctime'] = new AsIs('now()');
		$row['cuser'] = Config::getInstance()->user->id;
		if ($this->doCheck) {
			$this->checkAllFields($row);
		}
		$ret = parent::insert($row);
		return $ret;
	}

	function update(array $row) {
		$mtime = new Time();
		$row['mtime'] = $mtime->format('Y-m-d H:i:s');
		$row['muser'] = Config::getInstance()->user->id;
		if ($this->doCheck) {
			$this->checkAllFields($row);
		}
		return parent::update($row);
	}

	function findInDB(array $where, $orderby = '') {
		if ($this->doCheck) {
			$this->checkAllFields($where);
		}
		parent::findInDB($where, $orderby);
	}

/*********************/

	function checkAllFields(array $row) {
		$this->fetchColumns();
		foreach ($row as $field => $value) {
			$this->checkCreateField($field, $value);
		}
	}

	function fetchColumns($force = false) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table}) <- ".Debug::getCaller(5));
		if (!self::$tableColumns[$this->table] || $force) {
			self::$tableColumns[$this->table] = $this->db->getTableColumns($this->table);
		}
		$this->columns = self::$tableColumns[$this->table];
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table}) <- ".Debug::getCaller(5));
	}

	function checkCreateTable() {
		$this->fetchColumns();
		if (!$this->columns) {
			$this->db->perform('CREATE TABLE '.$this->db->escape($this->table).' (id integer auto_increment, PRIMARY KEY (id))');
			$this->fetchColumns(true);
		}
	}

	function checkCreateField($field, $value) {
		//debug($this->columns);
		$qb = Config::getInstance()->qb;
		$field = strtolower($field);
		if (strtolower($this->columns[$field]['Field']) != $field) {
			$this->db->perform('ALTER TABLE '.$this->db->escape($this->table).' ADD COLUMN '.$qb->quoteKey($field).' '.$this->getType($value));
			$this->fetchColumns(true);
		}
	}

	function getType($value) {
		if (is_int($value)) {
			$type = 'integer';
		} else if ($value instanceof Time) {
			$type = 'timestamp';
		} else if (is_numeric($value)) {
			$type = 'float';
		} else if ($value instanceof SimpleXMLElement) {
			$type = 'text';
		} else {
			$type = 'VARCHAR (255)';
		}
		return $type;
	}

	/**
	 * Can't store large amount of data in MySQL column
	 * Data may be either compressed - then we try to uncompress it
	 * Or it may be XML, then we convert it to the SimpleXML object
	 * Both operations take $this->data['field'] as a source
	 * and save the result into $this->$field
	 * @param bool $debug
	 */
	function expand($debug = false) {
		static $stopDebug = false;
		$this->fetchColumns();
		foreach ($this->columns as $field => &$info) {
			if (in_array($info['Type'], array('blob', 'text')) && $this->data[$field]) {
				$info['uncompress'] = 'try';
				$uncompressed = $this->db->uncompress($this->data[$field]);
				if (!$uncompressed) {
					/*debug($info+array(
						'error' => $php_errormsg,
						'value' => $this->data[$field],
					)); exit();*/
					// didn't unzip - then it's plain text
					$uncompressed = $this->data[$field];
					$info['uncompress'] = 'Not necessary';
				}
				$this->data[$field] = $uncompressed;
				$info['first'] = $this->data[$field]{0};
				if ($this->data[$field]{0} == '<') {
					//$uncompressed = html_entity_decode($uncompressed, ENT_QUOTES, "utf-8");
					$this->$field = @simplexml_load_string($uncompressed);
					unset($this->data[$field]);
					$info['unxml'] = 'true';
				} elseif ($this->data[$field]{0} == '{') {
					$this->$field = json_decode($uncompressed, false);	// make it look like SimpleXML
					unset($this->data[$field]);
					$info['unjson'] = 'true';
				}
			}
		}
		if ($debug && !$stopDebug) {
			debug($this->table, $this->columns);
			$stopDebug = true;
		}
		unset($this->data['xml']);
		unset($this->data['xml2']);
	}

}
