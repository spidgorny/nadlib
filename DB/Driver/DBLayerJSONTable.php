<?php

class DBLayerJSONTable extends DBLayerBase implements DBInterface {

	var $filename;

	var $data = [];

	var $where = [];

	function __construct($filename)
	{
		$this->filename = $filename;
		if (is_file($this->filename)) {
			$json = file_get_contents($this->filename);
			$this->data = json_decode($json, true);
		} else {
			file_put_contents($this->filename, '[]');
		}
	}

	function __destruct()
	{
		file_put_contents($this->filename,
			json_encode($this->data, JSON_PRETTY_PRINT));
	}

	public function __toString()
	{
		$query = 'SELECT * FROM '.basename($this->filename).' WHERE '.
			json_encode($this->where);
		//echo $query, BR;
		return $query;
	}

	function fetchAll($res_or_query, $index_by_key = NULL)
	{
		//return $this->data;
		$data = [];
		reset($this->data);
		while (!is_null(key($this->data))) {
			$newRow = $this->fetchAssoc($this->data);
			//debug($newRow);
			if (!empty($newRow)) {
				$data[] = $newRow;
			}
			next($this->data);
		}
		return $data;
	}

	function fetchAssoc($res)
	{
		$row = null;
		if ($this->where) {
			while (!is_null(key($this->data))) {
				$row = current($this->data);
				if (self::matchWhere($row, $this->where)) {
					return $row;
				}
				// only if false (next() is called in fetchAll())
				next($this->data);
			}
			$row = null;	// last row was not returned
		} else {
			$row = current($this->data);
		}
//		debug($row, $this->where);
		return $row;
	}

	function numRows($res = NULL)
	{
		return sizeof($this->data);
	}

	function runInsertQuery($table, array $data)
	{
		$this->data[] = $data;
	}

	function runUpdateQuery($table, array $data, array $where)
	{
		foreach ($this->data as &$row) {
			if ($this->matchWhere($row, $where)) {
				$row = array_merge($row, $data);
			}
		}
	}

	static function matchWhere(array $row, array $where)
	{
		foreach ($where as $key => $val) {
			$matchOne = (string)$row[$key] == (string)$val;
			//debug($row[$key], $val, $matchOne);
			if (!$matchOne) {
				return false;
			}
		}
		return true;
	}

	function runSelectQuery($table, array $where = [], $order = '', $addSelect = null)
	{
		$this->where = $where;
		return $this;
	}

}
