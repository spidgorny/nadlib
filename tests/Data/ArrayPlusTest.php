<?php

namespace Data;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use ArrayPlus;

class ArrayPlusTest extends TestCase
{

	/**
	 * @var ArrayPlus $ai
	 */
	protected $ai;

	public function test_typoscript(): void
	{
		$a = [
			'a' => 'b',
			'c' => [
				'd' => 'e',
				'f' => [
					'g' => 'h',
				],
			],
		];
		$b = ArrayPlus::create($a)->typoscript();
		//debug($b);
		static::assertEquals([
			'a' => 'b',
			'c.d' => 'e',
			'c.f.g' => 'h',
		], $b);
	}

	public function test_unset(): void
	{
		unset($this->ai[1]);
		static::assertEquals([
			0 => 'a',
			'slawa' => 'test'
		], $this->ai->getData());
	}

	public function test_addColumn(): void
	{
		$this->ai->makeTable('col1');
		$this->ai->addColumn('nr', function ($row, $i) {
			return $i;
		});
		static::assertEquals([
			[
				'col1' => 'a',
				'nr' => 0,
			],
			[
				'col1' => 'b',
				'nr' => 1,
			],
			'slawa' => [
				'col1' => 'test',
				'nr' => 'slawa',
			]
		], (array)$this->ai);
	}

	public function test_remap(): void
	{
		$a = ArrayPlus::create([
			'a' => 'b',
			'c' => 'd',
		]);
		$remap = $a->remap([
			'A' => 'a',
		]);
//		debug($remap);
		static::assertEquals([
			'A' => 'b',
		], $remap->getData());
	}

	public function test_mapBoth(): void
	{
		$a = ArrayPlus::create([
			'a' => 'b',
		]);
		$b = $a->mapBoth(function ($key, $el) {
			return $key;
		});
		static::assertEquals([
			'a',
		], $b->getKeys()->getData());
	}

	public function test_insertBefore(): void
	{
		$a = ArrayPlus::create(['asd', 'split' => 'a', 'after']);
		$a->insertBefore('split', 'someshit');
		static::assertEquals(['asd', 'someshit', 'split' => 'a', 'after'], $a->getData());
	}

	public function test_without(): void
	{
		$a = ArrayPlus::create(['asd' => 1, 'qwe' => 2]);
		$b = $a->without(['asd']);
		static::assertEquals(['qwe' => 2], $b->getArrayCopy());
	}

	public function test_any(): void
	{
		$fixture01 = ArrayPlus::create([0, 1]);
		static::assertTrue($fixture01->any(function ($x) {
			return $x;
		}));
		$fixture00 = ArrayPlus::create([0, 0]);
		static::assertFalse($fixture00->any(function ($x) {
			return $x;
		}));
		$fixture11 = ArrayPlus::create([1, 1]);
		static::assertTrue($fixture11->any(function ($x) {
			return $x;
		}));
	}

	public function test_all(): void
	{
		$fixture01 = ArrayPlus::create([0, 1]);
		static::assertFalse($fixture01->all(function ($x) {
			return $x;
		}));
		$fixture00 = ArrayPlus::create([0, 0]);
		static::assertFalse($fixture00->all(function ($x) {
			return $x;
		}));
		$fixture11 = ArrayPlus::create([1, 1]);
		static::assertTrue($fixture11->all(function ($x) {
			return $x;
		}));
	}

	public function test_none(): void
	{
		$fixture01 = ArrayPlus::create([0, 1]);
		static::assertFalse($fixture01->none(function ($x) {
			return $x;
		}));
		$fixture00 = ArrayPlus::create([0, 0]);
		static::assertTrue($fixture00->none(function ($x) {
			return $x;
		}));
		$fixture11 = ArrayPlus::create([1, 1]);
		static::assertFalse($fixture11->none(function ($x) {
			return $x;
		}));
	}

	protected function setUp(): void
	{
		$this->ai = new ArrayPlus([
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		]);
	}

}
