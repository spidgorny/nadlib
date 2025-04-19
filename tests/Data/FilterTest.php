<?php

use nadlib\Controller\Filter;

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.05.2016
 * Time: 14:05
 */
class FilterTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var Filter
	 */
	protected $f;

	public function test_init(): void
	{
//		$this->assertEquals(10, $this->f->default);
//		$this->assertEquals(20, $this->f->request);
//		$this->assertEquals(30, $this->f->prefs);
		// @phpstan-ignore-next-line
		$this->assertEquals('request', $this->f->a);

		$this->assertEquals(10, $this->f['default']);
		$this->assertEquals(20, $this->f['request']);
		$this->assertEquals(30, $this->f['prefs']);
		$this->assertEquals('request', $this->f['a']);

		// @phpstan-ignore-next-line
		$this->assertEquals('cascade', $this->f->two);
	}

	public function test_arrayCopy(): void
	{
		$this->assertEquals([
			'a' => 'request',
			'two' => 'cascade',
			'default' => 10,
			'prefs' => 30,
			'request' => 20,
		], $this->f->getArrayCopy());
	}

	protected function setUp(): void
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
