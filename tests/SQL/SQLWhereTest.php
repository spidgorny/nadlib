<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 02.02.2016
 * Time: 17:45
 */
class SQLWhereTest extends PHPUnit_Framework_TestCase
{

	public function test_add()
	{
		$sq = new SQLWhere();
		$sq->add(new SQLWhereEqual('deleted', false));
		$sql = $sq->__toString();
		$sql = $this->trim($sql);
		$this->assertEquals("WHERE NOT \"deleted\"", $sql);
	}

	public function test_addArray()
	{
		$sq = new SQLWhere();
		$sq->addArray([
			'a' => 'b',
		]);
		$sql = $sq->__toString();
		$sql = $this->trim($sql);
		$this->assertEquals("WHERE \"a\" = 'b'", $sql);
	}

	public function test_InvalidArgumentException()
	{
		$this->setExpectedException(InvalidArgumentException::class);
		$sq = new SQLWhere();
		$sq->add([
			'a' => 'b',
		]);
	}

	public function trim($sql)
	{
		$sql = str_replace("\n", ' ', $sql);
		$sql = str_replace("\t", ' ', $sql);
		$sql = preg_replace('/ +/', ' ', $sql);
		$sql = trim($sql);
//		echo $sql, BR;
		return $sql;
	}

	public function setExpectedException($exception)
	{
		if (method_exists($this, 'expectException')) {
			$this->expectException($exception);
		} else {
			parent::setExpectedException($exception);
		}
	}

}
