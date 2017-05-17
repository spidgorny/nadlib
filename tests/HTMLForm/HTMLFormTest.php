<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-22
 * Time: 17:04
 */
class HTMLFormTest extends PHPUnit_Framework_TestCase {

	function test_set() {
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

	function test_set_multiple() {
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

	function test_keyset_multiple() {
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
