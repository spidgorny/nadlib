<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.05.2016
 * Time: 14:05
 */
class FilterTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var Filter
	 */
	public $f;

	function test_init()
	{
		$this->assertEquals(10, $this->f->default);
		$this->assertEquals(20, $this->f->request);
		$this->assertEquals(30, $this->f->prefs);
		$this->assertEquals('request', $this->f->a);

		$this->assertEquals(10, $this->f['default']);
		$this->assertEquals(20, $this->f['request']);
		$this->assertEquals(30, $this->f['prefs']);
		$this->assertEquals('request', $this->f['a']);

		$this->assertEquals('cascade', $this->f->two);
	}

	function test_arrayCopy()
	{
		$this->assertEquals([
			'a' => 'request',
			'two' => 'cascade',
			'default' => 10,
			'prefs' => 30,
			'request' => 20,
		], $this->f->getArrayCopy());
	}

	/**
	 * @return Filter
	 */
	public function setup()
	{
		$f = new Filter();
		$f->setDefault([
			'a' => 'default',
			'default' => 10
		]);
		$f->setPreferences([
			'a' => 'preferences',
			'prefs' => 30,
			'two' => 'cascade'
		]);
		$f->setRequest([
			'a' => 'request',
			'request' => 20
		]);
		$this->f = $f;
		//var_dump($f);
	}

}
