<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 29.01.2016
 * Time: 11:30
 */
class ULTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var UL
	 */
	protected $ul;

	protected function setUp(): void
	{
		$this->ul = new UL([
			'slawa' => 'Slawa',
			'marina' => 'Marina',
		]);
	}

	public function test_noLink(): void
	{
		$render = $this->ul->render();
		$this->assertContains('<li class="active">Slawa</li>', $render);
	}

	public function test_linkWrap(): void
	{
		$this->ul->makeClickable();
		$render = $this->ul->render();
		$this->assertContains('<li class="active"><a href="slawa">Slawa</a></li>', $render);
	}

	public function test_linkFunc(): void
	{
		$this->ul->linkFunc = function ($class, $name): string {
			return sprintf('%s=>%s', $class, $name);
		};
		$this->ul->linkWrap = '<link>###LINK###</link><text>|</text>';

		$render = $this->ul->render();
		$this->assertContains('<li class="active"><link>slawa=>Slawa</link><text>Slawa</text>', $render);
	}

}
