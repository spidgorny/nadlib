<?php

class SQLBuilderTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	public function setUp()
	{
		$this->db = Config::getInstance()->getDB();
	}

	public function test_getSelectQuery()
	{
		if ($this->db instanceof DBLayerPDO && $this->db->isMySQL()) {
			$this->markTestSkipped('MySQL has different SQL');
		}
		if ($this->db instanceof DBPlacebo) {
			$this->markTestSkipped('DBPlacebo has different SQL');
		}
		$qb = new SQLBuilder($this->db);
		$query = $qb->getSelectQueryString('table', [
			'a' => 'b',
		], 'ORDER BY c');
		$must = "SELECT \"table\".*
FROM \"table\"
WHERE
\"a\" = 'b'
ORDER BY c";
		$must = str_replace("\r\n", "\n", $must);
//		debug($must, $query);
		$this->assertEquals($must, $query);
	}

	public function test_getSelectQueryP()
	{
		if ($this->db instanceof DBLayerPDO && $this->db->isMySQL()) {
			$this->markTestSkipped('MySQL has different SQL');
		}
		if ($this->db instanceof DBPlacebo) {
			$this->markTestSkipped('DBPlacebo has different SQL');
		}
		$query = SQLSelectQuery::getSelectQueryP($this->db, 'table', [
			'a' => new SQLLikeContains('b'),
		], 'ORDER BY c');
		$must = "SELECT \"table\".*
FROM \"table\"
WHERE
\"a\" ILIKE '%' || $1 || '%'
ORDER BY c";
		$must = $this->implodeSQL($must);
		$sQuery = $query->getQuery();
		$sQuery = $this->implodeSQL($sQuery);
//		debug($must, $sQuery, $query->getParameters());
		$this->assertEquals($must, $sQuery);
	}

	public function implodeSQL($sql)
	{
		$sql = strtr($sql, [
			" " => '',
			"\t" => '',
			"\r" => '',
			"\n" => '',
		]);
		return $sql;
	}

}
