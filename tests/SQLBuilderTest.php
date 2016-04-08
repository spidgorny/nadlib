<?php

class SQLBuilderTest extends PHPUnit_Framework_TestCase {

	var $db;

	function setUp() {

		$this->markTestSkipped(
			'Blocked because of testing in refactor.'
		);
		
		$this->db = Config::getInstance()->getDB();
	}

	function test_getSelectQuery() {
		$qb = new SQLBuilder($this->db);
		$query = $qb->getSelectQueryString('table', [
			'a' => 'b',
		], 'ORDER BY c');
		$must = "SELECT \"table\".*
FROM \"table\"
WHERE
a = 'b'
ORDER BY c";
		$must = str_replace("\r\n", "\n", $must);
		debug($must, $query);
		$this->assertEquals($must, $query);
	}

	function test_getSelectQueryP() {
		$qb = new SQLBuilder($this->db);
		$query = $qb->getSelectQueryP('table', [
			'a' => new SQLLikeContains('b'),
		], 'ORDER BY c');
		$must = "SELECT \"table\".*
FROM \"table\"
WHERE
a ILIKE '%' || $1 || '%'
ORDER BY c";
		$must = $this->implodeSQL($must);
		$sQuery = $query->getQuery();
		$sQuery = $this->implodeSQL($sQuery);
		debug($must, $sQuery, $query->getParameters());
		$this->assertEquals($must, $sQuery);
	}

	function implodeSQL($sql) {
		$sql = strtr($sql, [
			" " => '',
			"\t" => '',
			"\r" => '',
			"\n" => '',
		]);
		return $sql;
	}

}
