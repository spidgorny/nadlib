<?php

class SQLBuilderTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	public function setUp()
	{
//		$this->db = Config::getInstance()->getDB();
		$db = new DBPlacebo();
		$qb = new SQLBuilder($db);
		$db->setQB($qb);
		Config::getInstance()->setDB($db);
		$this->db = $db;
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

	public function testGetFirstWord()
	{
		$room = SQLBuilder::getFirstWord('room');
		$this->assertEquals('room', $room);
		$room = SQLBuilder::getFirstWord('room AND something else');
		$this->assertEquals('room', $room);
		$room = SQLBuilder::getFirstWord('room
AND something else');
		$this->assertEquals('room', $room);
		$room = SQLBuilder::getFirstWord('room' . TAB . 'AND something else');
		$this->assertEquals('room', $room);
		$this->expectException(InvalidArgumentException::class);
		$room = SQLBuilder::getFirstWord('');
		$this->assertEquals('room', $room);
	}

	public function testGetFirstWordAgain()
	{
		$this->expectException(InvalidArgumentException::class);
		$room = SQLBuilder::getFirstWord(null);
		$this->assertEquals('room', $room);
	}

	public function testGetFirstWordFromPDO()
	{
		$pdo = new DBLayerPDO();
		$pdo->setQB(new SQLBuilder($pdo));
		$room = SQLBuilder::getFirstWord('room');
		$this->assertEquals('room', $room);
	}

	public function testPlaceholder()
	{
		$this->assertEquals('$1', $this->db->getPlaceholder());
		$col = new \AppBundle\Model\Person\RisPersonCollection();
		$query = $col->getQueryWithLimit();
		$this->assertEquals('SELECT "person".* FROM "person" WHERE NOT "hidden" AND "trim(login)" <> \'\' ORDER BY name, surname', SQLSelectQuery::trim($query->__toString()));

		// add WHERE name='John'
		$col->reset();
		$col->where['name'] = 'John';
		$query = $col->getQueryWithLimit();
		$this->assertEquals('SELECT "person".* FROM "person" WHERE NOT "hidden" AND "trim(login)" <> \'\' AND "name" = \'John\' ORDER BY name, surname', SQLSelectQuery::trim($query->__toString()));

		// add SQLLike
		$col->reset();
		$col->where['name'] = new SQLLike('John');
		$query = $col->getQueryWithLimit();
		$this->assertEquals('SELECT "person".* FROM "person" WHERE NOT "hidden" AND "trim(login)" <> \'\' AND "name" ILIKE \'\' || $1 || \'\' ORDER BY name, surname', SQLSelectQuery::trim($query->__toString()));

		// same query, but second time to test getPlaceholder()
		$col->reset();  // this is important
		$col->where['name'] = new SQLLike('John');
		$query = $col->getQueryWithLimit();
		$this->assertEquals('SELECT "person".* FROM "person" WHERE NOT "hidden" AND "trim(login)" <> \'\' AND "name" ILIKE \'\' || $1 || \'\' ORDER BY name, surname', SQLSelectQuery::trim($query->__toString()));

		// multiple parameters
		$col->reset();  // this is important
		$col->where['name'] = new SQLOr([
			new SQLLike('John'),
			new SQLLike('Doe'),
		]);
		$query = $col->getQueryWithLimit();
		$this->assertEquals('SELECT "person".* FROM "person" WHERE NOT "hidden" AND "trim(login)" <> \'\' AND ("name" ILIKE \'\' || $1 || \'\' OR "name" ILIKE \'\' || $2 || \'\') ORDER BY name, surname', SQLSelectQuery::trim($query->__toString()));
		// repeat should call resetQueryParameters() and start from $1 again
		$this->assertEquals('SELECT "person".* FROM "person" WHERE NOT "hidden" AND "trim(login)" <> \'\' AND ("name" ILIKE \'\' || $1 || \'\' OR "name" ILIKE \'\' || $2 || \'\') ORDER BY name, surname', SQLSelectQuery::trim($query->__toString()));

		$params = $query->getParameters();
//		llog($params);
		$this->assertEquals(['John', 'Doe'], $params);

		// perform
		$rows = $col->getData();
		$this->assertEquals([], $rows->getData());

		// with limit should return an object so that we can use getParameters()
		$query = $col->getQueryWithLimit();
		$this->assertInstanceOf(SQLSelectQuery::class, $query);

		// retrieveDataFromDB
		$rows = $col->getCollectionQuery()->retrieveData();
		llog($rows);
	}
}
