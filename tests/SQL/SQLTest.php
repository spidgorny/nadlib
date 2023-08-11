<?php

class SQLTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var dbLayerBase|SQLBuilder
	 */
	var $db;

	function setup()
	{
		$this->db = Config::getInstance()->getDB();
	}

	function test_SQLNow_PG()
	{
		$now = new SQLNow();
		$string = $now . '';

		$this->assertEquals('CURRENT_TIMESTAMP', $string);
	}

	function test_SQLNow_PG_update_no_quote()
	{
		$now = new SQLNow();
		$update = [
			'mtime' => $now,
		];
		$query = $this->db->getUpdateQuery('asd', $update, ['id' => 1]);

		$expected = "UPDATE asd
SET mtime = CURRENT_TIMESTAMP
WHERE
id = '1' /* numeric */";
		$expected = str_replace("\r", '', $expected);
		$this->assertEquals($expected, $query);
	}

	function test_SQLNow_PG_insert_no_quote()
	{
		$now = new SQLNow();
		$update = [
			'mtime' => $now,
		];
		$query = $this->db->getInsertQuery('asd', $update);

		$expected = "INSERT INTO asd (mtime) VALUES (CURRENT_TIMESTAMP)";
		$expected = str_replace("\r", '', $expected);
		$this->assertEquals($expected, $query);
	}

}
