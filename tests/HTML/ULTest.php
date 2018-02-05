<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 29.01.2016
 * Time: 11:30
 */
class ULTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var UL
	 */
	var $ul;

	function setUp() {
		$this->ul = new UL([
			'slawa' => 'Slawa',
			'marina' => 'Marina',
		]);
	}

	function test_noLink() {
		$render = $this->ul->render();
		$this->assertContains('<li>Slawa</li>', $render);
	}

	function test_linkWrap() {
		$this->ul->makeClickable();
		$render = $this->ul->render();
		$this->assertContains('<li><a href="slawa">Slawa</a></li>', $render);
	}

	function test_linkFunc() {
		$this->ul->linkFunc = function ($class, $name) {
			return "$class=>$name";
		};
		$this->ul->linkWrap = '<link>###LINK###</link><text>|</text>';
		$render = $this->ul->render();
		$this->assertContains('<li><link>slawa=>Slawa</link><text>Slawa</text>', $render);
	}

}