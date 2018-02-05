<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-22
 * Time: 17:04
 */
class HTMLFormTest extends PHPUnit\Framework\TestCase {

	function __construct($name = NULL, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		@define('BR', Request::isWindows()
			? "\r\n" : "\n");
	}

	function test_id()
	{
		$f = new HTMLForm('action', __CLASS__);
		$sForm = $f->getContent();
		$this->assertContains('id="'.__CLASS__.'"', $sForm);
	}

	function test_formTag()
	{
		$f = new HTMLForm();
		$tag = $f->getFormTag();
		//echo $tag, PHP_EOL;
		$this->assertEquals('<form method="POST">'."\n", $tag);
	}

	function test_input()
	{
		$f = new HTMLForm();
		$f->input('name', 'value', ['more' => 'more'], 'text', 'class');
		$sInput = $f->getBuffer();
//		echo $sInput, PHP_EOL;
		$this->assertContains(
			'<input type="text" class="text class" name="name" value="value" more="more" />', $sInput
		);
	}

	function test_input_more()
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
		$this->assertContains(
			'<input type="text" class="text class more" name="name" value="value" id="more" />', $sInput
		);
	}

	function test_set()
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
		$this->assertContains('"k1" checked', $html);
	}

	function test_set_multiple()
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
		$this->assertContains('"k1" checked', $html);
		$this->assertContains('"k2" checked', $html);
	}

	function test_keyset_multiple()
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
		$this->assertContains('"k1" checked', $html);
		$this->assertContains('"k2" checked', $html);
	}

}
