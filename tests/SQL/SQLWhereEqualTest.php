<?php

namespace SQL;

use AsIs;
use DBInterface;
use PHPUnit\Framework\TestCase;
use SQLWhereEqual;

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.11.2018
 * Time: 16:53
 */
class SQLWhereEqualTest extends TestCase
{

	/** @var DBInterface */
	protected $db;

	protected function setUp(): void
	{
		parent::setUp();
		self::markTestSkipped('PG dependent');
//		$this->db = Config::getInstance()->getDB();
	}

	public function testGetWhereItem(): void
	{
		$swe = new SQLWhereEqual('field', 15);
		$swe->injectDB($this->db);

		$sql = $swe->__toString();
		static::assertEquals('"field" = \'15\'', $sql);
	}

	public function testGetWhereItemAsIs(): void
	{
		$swe = new SQLWhereEqual('field', new AsIs('15'));
		$swe->injectDB($this->db);

		$sql = $swe->__toString();
		static::assertEquals('"field" = 15', $sql);
	}

}
