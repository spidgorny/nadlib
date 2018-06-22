<?php

class SQLBuilderTest extends PHPUnit_Framework_TestCase
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
