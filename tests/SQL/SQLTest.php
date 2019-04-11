<?php

function normalize($s)
{
	return implode(PHP_EOL, trimExplode("\n", $s));
}

class SQLTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var DBLayerBase|SQLBuilder
	 */
	protected $db;

	public function setup()
	{
		$this->db = Config::getInstance()->getDB();
	}

	public function test_SQLNow_PG()
	{
		$now = new SQLNow();
		$string = $now . '';

		$this->assertEquals('now()', $string);
	}

	public function test_SQLNow_PG_update_no_quote()
	{
		$now = new SQLNow();
		$update = array(
			'mtime' => $now,
		);
		$query = $this->db->getUpdateQuery('asd', $update, array('id' => 1));

		$expected = "UPDATE \"asd\"
SET \"mtime\" = now()
WHERE
\"id\" = '1' ";
		$this->assertEquals(normalize($expected), normalize($query));
//		$this->assertEquals($expected, $query);
	}

	public function test_SQLNow_PG_insert_no_quote()
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

}
