<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 02.02.2016
 * Time: 17:45
 */
class SQLWhereTest extends NadlibTestCase
{

	/** @var DBInterface */
	protected $db;

	protected function setUp(): void
	{
		parent::setUp();
		self::markTestSkipped('PG dependent');
		$this->db = Config::getInstance()->getDB();
	}

	public function test_add(): void
	{
		$sq = new SQLWhere();
		$sq->injectDB($this->db);
		$sq->add(new SQLWhereEqual('deleted', false));

		$sql = $sq->__toString();
		$sql = $this->trim($sql);
		$this->assertEquals('WHERE NOT "deleted"', $sql);
	}

	public function trim($sql): string
	{
		$sql = str_replace("\n", ' ', $sql);
		$sql = str_replace("\t", ' ', $sql);
		$sql = preg_replace('/ +/', ' ', $sql);
//		echo $sql, BR;
		return trim($sql);
	}

	public function test_addArray(): void
	{
		$sq = new SQLWhere();
		$sq->injectDB($this->db);
		$sq->addArray([
			'a' => 'b',
		]);
		$sql = $sq->__toString();
		$sql = $this->trim($sql);
		$this->assertEquals("WHERE \"a\" = 'b'", $sql);
	}

	public function test_InvalidArgumentException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$sq = new SQLWhere();
		$sq->add([
			'a' => 'b',
		]);
	}

	public function setExpectedException(string $exceptionName): void
	{
		$this->expectException($exceptionName);
	}

}
