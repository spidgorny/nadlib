<?php

class FlexiTable extends OODBase {
	protected $columns = array();

	function __construct($id = NULL) {
		parent::__construct($id);
		//$this->checkCreateTable();
	}

	function insert(array $row) {
		$row['ctime'] = new Time();
		$row['cuser'] = $GLOBALS['i']->user->id;
		$this->checkAllFields($row);
		$ret = parent::insert($row);
		return $ret;
	}

	function checkAllFields(array $row) {
		return;
		$this->fetchColumns();
		foreach ($row as $field => $value) {
			$this->checkCreateField($field, $value);
		}
	}

	function fetchColumns() {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table}) <- ".$this->db->getCaller(5));
		$this->columns = $this->db->getTableColumns($this->table);
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table}) <- ".$this->db->getCaller(5));
	}

	function checkCreateTable() {
		$this->fetchColumns();
		if (!$this->columns) {
			$this->db->perform('CREATE TABLE '.$this->db->escape($this->table).' (id integer auto_increment, PRIMARY KEY (id))');
			$this->fetchColumns();
		}
	}

	function checkCreateField($field) {
		//debug($this->columns);
		$field = strtolower($field);
		if (strtolower($this->columns[$field]['Field']) != $field) {
			$this->db->perform('ALTER TABLE '.$this->db->escape($this->table).' ADD COLUMN '.$this->db->escape($field).' '.$this->getType($value));
			$this->fetchColumns();
		}
	}

	function getType($value) {
		if (is_int($value)) {
			$type = 'integer';
		} else if ($value instanceof Time) {
			$type = 'timestamp';
		} else if (is_numeric($value)) {
			$type = 'float';
		} else {
			$type = 'VARCHAR (255)';
		}
		return $type;
	}

	function findInDB(array $where, $orderby = '') {
		$this->checkAllFields($where);
		parent::findInDB($where, $orderby);
	}

	function update(array $row) {
		$row['mtime'] = new Time();
		$row['mtime'] = $row['mtime']->format('Y-m-d H:i:s');
		$row['muser'] = $GLOBALS['i']->user->id;
		$this->checkAllFields($row);
		return parent::update($row);
	}

}