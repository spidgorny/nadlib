<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 19.12.13
 * Time: 15:03
 */

class RequestTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Request $r
	 */
	protected $r;

	function setUp() {
		$this->r = Request::getInstance();
	}

	function test_set() {
		$this->r->set('a', 'b');
		$this->assertEquals('b', $this->r->getTrim('a'));
	}

	function test_unset() {
		//debug($this->r);
		$this->r->set('a', 'b');
		$this->r->un_set('a');
		$this->assertEmpty($this->r->getTrim('a'));
	}

}
