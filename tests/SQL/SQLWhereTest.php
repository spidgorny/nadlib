<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 02.02.2016
 * Time: 17:45
 */
class SQLWhereTest extends PHPUnit_Framework_TestCase
{

	function test_add()
	{
		$sq = new SQLWhere();
		$sq->add(new SQLWhereEqual('deleted', false));
		$sql = $sq->__toString();
		$sql = $this->trim($sql);
		$this->assertEquals('WHERE NOT "deleted"', $this->normalize($sql));
	}

	function test_addArray()
	{
		$sq = new SQLWhere();
		$sq->addArray([
			'a' => 'b',
		]);
		$sql = $sq->__toString();
		$sql = $this->trim($sql);
		$this->assertEquals("WHERE \"a\" = 'b'", $sql);
	}

	function test_InvalidArgumentException()
	{
		$this->setExpectedException(InvalidArgumentException::class);
		$sq = new SQLWhere();
		$sq->add([
			'a' => 'b',
		]);
	}

	function trim($sql)
	{
		$sql = preg_replace('!/\*.*?\*/!s', '', $sql);
		$sql = str_replace("\n", ' ', $sql);
		$sql = str_replace("\t", ' ', $sql);
		$sql = preg_replace('/ +/', ' ', $sql);
		$sql = trim($sql);
		//echo $sql, BR;
		return $sql;
	}

	public function normalize($string)
	{
		// https://stackoverflow.com/questions/643113/regex-to-strip-comments-and-multi-line-comments-and-empty-lines
		$string = preg_replace('!/\*.*?\*/!s', '', $string);
		$string = preg_replace('/\s*$^\s*/m', "\n", $string);
		$string = preg_replace('/[ \t\r\n]+/', ' ', $string);
		return trim($string);
	}

}
