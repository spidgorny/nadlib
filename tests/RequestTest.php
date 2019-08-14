<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 19.12.13
 * Time: 15:03
 */

class RequestTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var Request $r
	 */
	protected $r;

	function setUp()
	{
		$this->r = Request::getInstance();
	}

	function test_set()
	{
		$this->r->set('a', 'b');
		$this->assertEquals('b', $this->r->getTrim('a'));
	}

	function test_unset()
	{
		//debug($this->r);
		$this->r->set('a', 'b');
		$this->r->un_set('a');
		$this->assertEmpty($this->r->getTrim('a'));
	}

	function test_getTrim()
	{
		$this->r->set('a', ' some words' . "\n\t");
		$this->assertEquals('some words', $this->r->getTrim('a'));
	}

	/**
	 * @expectedException Exception
	 */
	function test_getTrimRequired()
	{
		$this->r->set('a', '  ');
		$this->r->getTrimRequired('a');
	}

	/**
	 * @expectedException Exception
	 */
	function test_getOneOf()
	{
		$this->r->set('a', 'b');
		$this->r->getOneOf('a', array('c'));
	}

	function test_getInt()
	{
		$this->r->set('i', '10');
		$this->assertEquals(10, $this->r->getInt('i'));
	}

	function test_getInt0()
	{
		$this->r->set('i', '10');
		$this->assertEquals(0, $this->r->getInt('new'));
	}

	function test_getIntOrNULL()
	{
		$this->assertNull($this->r->getIntOrNULL('new'));
	}

	function test_getIntIn()
	{
		$this->r->set('i', 10);
		$this->assertEquals(10, $this->r->getIntIn('i', array(
			9 => '',
			10 => '',
			11 => '',
		)));
	}

	function test_getIntIn0()
	{
		$this->r->set('i', 10);
		$this->assertNull($this->r->getIntIn('i', array(
			9 => '',
			11 => '',
		)));
	}

}
