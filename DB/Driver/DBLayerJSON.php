<?php

class DBLayerJSON extends DBLayerBase implements DBInterface {

	var $filename;

	var $data = [];

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

	function fetchAll($res_or_query, $index_by_key = NULL)
	{
		return $this->data;
	}

	function fetchAssoc($res)
	{
		return current($this->data);
	}

	function runInsertQuery($table, array $data)
	{
		$this->data[] = $data;
	}

	function numRows($res = NULL)
	{
		return sizeof($this->data);
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
			$matchOne = $row[$key] == $val;
			if (!$matchOne) {
				return false;
			}
		}
		return true;
	}

}
