<?php

class SQLTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var dbLayerBase|SQLBuilder
	 */
	public $db;

	function setup()
	{
		$this->db = Config::getInstance()->getDB();
	}

	function test_SQLNow_PG()
	{
		$now = new SQLNow();
		$string = $now . '';

		$this->assertEquals('now()', $string);
	}

	function test_SQLNow_PG_update_no_quote()
	{
		$now = new SQLNow();
		$update = array(
			'mtime' => $now,
		);
		$query = $this->db->getUpdateQuery('asd', $update, array('id' => 1));

		$expected = "UPDATE \"asd\"
SET \"mtime\" = now()
WHERE
\"id\" = '1' /* numeric */";
		$expected = str_replace("\r", '', $expected);
		$this->assertEquals($this->normalize($expected), $this->normalize($query));
	}

	function test_SQLNow_PG_insert_no_quote()
	{
		$now = new SQLNow();
		$update = array(
			'mtime' => $now,
		);
		$query = $this->db->getInsertQuery('asd', $update);

		$expected = "INSERT INTO \"asd\" (\"mtime\") VALUES (now())";
		$expected = str_replace("\r", '', $expected);
		$this->assertEquals($expected, $query);
	}

	public function normalize($string)
	{
		// https://stackoverflow.com/questions/643113/regex-to-strip-comments-and-multi-line-comments-and-empty-lines
		$string = preg_replace('!/\*.*?\*/!s', '', $string);
		$string = preg_replace('/\s*$^\s*/m', "\n", $string);
		$string = preg_replace('/[ \t\r\n]+/', ' ', $string);
		return trim($string);
	}

}
