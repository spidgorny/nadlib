<?php

class ArrayPlusTest extends IteratorArrayAccessTest {

	/**
	 * @var ArrayPlus $ai
	 */
	protected $ai;

	public function setUp()
	{
		$this->ai = new ArrayPlus(array(
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		));
	}

	public function test_typoscript()
	{
		$a = array(
			'a' => 'b',
			'c' => array(
				'd' => 'e',
				'f' => array(
					'g' => 'h',
				),
			),
		);
		$b = ArrayPlus::create($a)->typoscript();
		//debug($b);
		$this->assertEquals(array(
			'a' => 'b',
			'c.d' => 'e',
			'c.f.g' => 'h',
		), $b);
	}

	public function test_unset()
	{
		unset($this->ai[1]);
		$this->assertEquals(array(
			0 => 'a',
			'slawa' => 'test'
		), $this->ai->getData());
	}

	public function test_addColumn()
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

	public function test_remap()
	{
		$a = ArrayPlus::create([
			'a' => 'b',
			'c' => 'd',
		]);
		$remap = $a->remap([
			'A' => 'a',
		]);
//		debug($remap);
		$this->assertEquals([
			'A' => 'b',
		], $remap->getData());
	}

	public function test_mapBoth()
	{
		$a = ArrayPlus::create([
			'a' => 'b',
		]);
		$b = $a->mapBoth(function ($key, $el) {
			return $key;
		});
		$this->assertEquals([
			'a',
		], $b->getKeys()->getData());
	}

	public function test_insertBefore()
	{
		$a = ArrayPlus::create(['asd', 'split' => 'a', 'after']);
		$a->insertBefore('split', 'someshit');
		$this->assertEquals(['asd', 'someshit', 'split' => 'a', 'after'], $a->getData());
	}

	public function test_without()
	{
		$a = ArrayPlus::create(['asd' => 1, 'qwe' => 2]);
		$b = $a->without(['asd']);
		$this->assertEquals(['qwe' => 2], $b->getArrayCopy());
	}

}
