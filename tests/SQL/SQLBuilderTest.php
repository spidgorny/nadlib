<?php

use nadlib\NadlibTestCase;

class SQLBuilderTest extends NadlibTestCase
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	protected function setUp(): void
	{
		$this->db = Config::getInstance()->getDB();
	}

	public function test_getSelectQuery(): void
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

	public function test_getSelectQueryP(): void
	{
		if ($this->db instanceof DBLayerPDO && $this->db->isMySQL()) {
			$this->markTestSkipped('MySQL has different SQL');
		}

		if ($this->db instanceof DBPlacebo) {
			$this->markTestSkipped('DBPlacebo has different SQL');
		}

		$query = new SQLSelectQuery($this->db, new SQLSelect('*'), new SQLFrom('table'), new SQLWhere([
			'a' => new SQLLikeContains('b'),
		]), null, null, null, new SQLOrder('ORDER BY c'));

		$must = "SELECT *
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

	public function implodeSQL($sql): string
	{
		return strtr($sql, [
			" " => '',
			"\t" => '',
			"\r" => '',
			"\n" => '',
		]);
	}

	public function testGetFirstWord(): void
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

	public function testGetFirstWordAgain(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$room = SQLBuilder::getFirstWord(null);
		$this->assertEquals('room', $room);
	}

	public function testGetFirstWordFromPDO(): void
	{
		$pdo = new DBLayerPDO();
		$pdo->setQB(new SQLBuilder($pdo));

		$room = SQLBuilder::getFirstWord('room');
		$this->assertEquals('room', $room);
	}

	public function testGroupBy(): void
	{
		$qb = new SQLBuilder($this->db);
		$query = $qb->getSelectQuery('table', [
			'a' => 'b',
		], 'GROUP BY c');
		$this->assertEquals("SELECT*FROM\"table\"WHERE\"a\"='b'GROUPBYc", $this->implodeSQL($query));
	}

	public function testTableOptions(): void
	{
		$qb = new SQLBuilder($this->db);
		$options = $qb->getTableOptions('version', 'versionname', [
			'relproject' => $_SESSION['sesProject'],
		], 'ORDER BY date desc');
		llog($options);
	}

}
