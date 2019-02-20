<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.11.2018
 * Time: 16:53
 */


class SQLWhereEqualTest extends PHPUnit\Framework\TestCase
{

	public function testGetWhereItem()
	{
		$swe = new SQLWhereEqual('field', 15);
		$sql = $swe->__toString();
		$this->assertEquals('"field" = \'15\'', $sql);
	}

	public function testGetWhereItemAsIs()
	{
		$swe = new SQLWhereEqual('field', new AsIs('15'));
		$sql = $swe->__toString();
		$this->assertEquals('"field" = 15', $sql);
	}

}
