<?php

namespace HTMLForm;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use HTMLForm;
use Request;

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-22
 * Time: 17:04
 */
class HTMLFormTest extends TestCase
{

	public function test_id(): void
	{
		$f = new HTMLForm('action', __CLASS__);
		$sForm = $f->getContent();
		static::assertStringContainsString('id="' . __CLASS__ . '"', $sForm);
	}

	public function test_formTag(): void
	{
		$f = new HTMLForm();
		$tag = $f->getFormTag();
		//echo $tag, PHP_EOL;
		static::assertEquals('<form method="POST">' . "\n", $tag);
	}

	public function test_input(): void
	{
		$f = new HTMLForm();
		$f->input('name', 'value', ['more' => 'more'], 'text', 'class');

		$sInput = $f->getBuffer();
//		echo $sInput, PHP_EOL;
		static::assertContains(
			'<input type="text" class="text class" name="name" value="value" more="more" />', $sInput
		);
	}

	public function test_input_more(): void
	{
		$f = new HTMLForm();
		$f->input('name', 'value', [
			'class' => 'more',
			'id' => 'more',
			'type' => 'more',
			'name' => 'more',
			'value' => 'more',
		], 'text', 'class');
		$sInput = $f->getBuffer();
		//echo $sInput, PHP_EOL;
		static::assertContains(
			'<input type="text" class="text class more" name="name" value="value" id="more" />', $sInput
		);
	}

	public function test_set(): void
	{
		$f = new HTMLForm();
		$f->set('asd', 'k1', [
			'options' => [
				'k0' => 'o0',
				'k1' => 'o1',
				'k2' => 'o2',
			],
		]);
		$html = $f->getBuffer();
//		debug($html);
		static::assertContains('"k1" checked', $html);
	}

	public function test_set_multiple(): void
	{
		$f = new HTMLForm();
		$f->set('asd', ['k1', 'k2'], [
			'options' => [
				'k0' => 'o0',
				'k1' => 'o1',
				'k2' => 'o2',
			],
		]);
		$html = $f->getBuffer();
//		debug($html);
		static::assertContains('"k1" checked', $html);
		static::assertContains('"k2" checked', $html);
	}

	public function test_keyset_multiple(): void
	{
		$f = new HTMLForm();
		$f->set('asd', ['k1', 'k2'], [
			'options' => [
				'k0' => 'o0',
				'k1' => 'o1',
				'k2' => 'o2',
			],
		]);
		$html = $f->getBuffer();
//		debug($html);
		static::assertContains('"k1" checked', $html);
		static::assertContains('"k2" checked', $html);
	}

	protected function setUp(): void
	{
		parent::setUp();
		@define('BR', Request::isWindows()
			? "\r\n" : "\n");
	}

}
