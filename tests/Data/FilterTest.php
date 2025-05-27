<?php

namespace Data;

use nadlib\Controller\Filter;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.05.2016
 * Time: 14:05
 */
class FilterTest extends TestCase
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
		static::assertEquals('request', $this->f->a);

		static::assertEquals(10, $this->f['default']);
		static::assertEquals(20, $this->f['request']);
		static::assertEquals(30, $this->f['prefs']);
		static::assertEquals('request', $this->f['a']);

		// @phpstan-ignore-next-line
		static::assertEquals('cascade', $this->f->two);
	}

	public function test_arrayCopy(): void
	{
		static::assertEquals([
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
