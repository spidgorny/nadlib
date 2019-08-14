<?php

class dbLayerSQLite extends dbLayerBase implements DBInterface
{

	/**
	 * @var string
	 */
	var $file;

	/**
	 * @var resource
	 */
	var $connection;

	/**
	 * @var string
	 */
	var $lastQuery;

	/**
	 * @var SQLiteResult
	 */
	var $result;

	function __construct($file)
	{
		$this->file = $file;
		$this->connection = new SQLiteDatabase($this->file);
	}

	function perform($query)
	{
		$this->lastQuery = $query;
		$this->result = $this->connection->query($query);
		return $this->result;
	}

	/**
	 * @param $res SQLiteResult
	 * @return mixed
	 */
	function numRows($res)
	{
		return $res->numRows();
	}

	function affectedRows()
	{
		$this->result->numRows();
	}

	function getTables()
	{
		$this->perform("SELECT * FROM dbname.sqlite_master WHERE type='table'");
		return $this->fetchAll($this->result);
	}

	function lastInsertID()
	{
		return $this->connection->lastInsertRowid();
	}

	function free($res)
	{
		// nothing
	}

	function quoteKey($key)
	{
		return '`' . $key . '`';
	}

	function escapeBool($value)
	{
		return intval(!!$value);
	}

}
