<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-11
 * Time: 21:50
 */

namespace nadlib\Test;

use PHPUnit_Framework_TestCase;

class OODBaseTest extends PHPUnit_Framework_TestCase
{

	public $sut;

	function setUp()
	{
		$this->sut = new SpecificOODBase();
	}

	function test_getBool()
	{
		$set = [
			0 => false,
			1 => true,
			't' => true,
			'f' => false,
			'true' => true,
			'false' => false,
			'' => false,
			'asd' => false,
			'0' => false,
			'123' => true,
		];
		foreach ($set as $source => $expected) {
//			echo $source, ' => ', $expected, BR;
			$this->assertEquals($expected, $this->sut->getBool($source));
		}
	}

}
