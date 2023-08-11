<?php

class ArrayPlusTest extends IteratorArrayAccessTest
{

	/**
	 * @var ArrayPlus $ai
	 */
	protected $ai;

	function setUp()
	{
		$this->ai = new ArrayPlus([
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		]);
	}

	function test_typoscript()
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
		$this->assertEquals([
			'a' => 'b',
			'c.d' => 'e',
			'c.f.g' => 'h',
		], $b);
	}

	function test_unset()
	{
		unset($this->ai[1]);
		$this->assertEquals([
			0 => 'a',
			'slawa' => 'test'
		], $this->ai->getData());
	}

	function test_addColumn()
	{
		$this->ai->makeTable('col1');
		$this->ai->addColumn('nr', function ($row, $i) {
			return $i;
		});
		$this->assertEquals([
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

}
