<?php

class ArrayIteratorPlusTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var ArrayIterator $ai
	 */
	protected $ai;

	protected function setUp(): void
	{
		$this->ai = new ArrayIterator([
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		]);
	}

	public function test_ArrayIterator_foreach(): void
	{
		$ai = new ArrayIterator($this->ai->getArrayCopy());
		$content = '';
		foreach ($ai as $a) {
			$content .= $a;
		}

		$this->assertEquals('abtest', $content);
	}

	public function test_ArrayIteratorPlus_foreach(): void
	{
		$content = '';
		foreach ($this->ai as $a) {
			$content .= $a;
		}

		$this->assertEquals('abtest', $content);
	}

}
