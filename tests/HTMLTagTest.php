<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 22.01.2016
 * Time: 17:27
 */
class HTMLTagTest extends PHPUnit_Framework_TestCase {

	public function test_parse_simple() {
		$str = '<a>';
		$tag = HTMLTag::parse($str);
		$this->assertEquals('a', $tag->tag);
	}

	public function test_parse_simple_space() {
		$str = ' <a > ';
		$tag = HTMLTag::parse($str);
		$this->assertEquals('a', $tag->tag);
	}

	public function test_parse_attrib() {
		$str = '<a href="http://asd.com/">';
		$tag = HTMLTag::parse($str);
		$this->assertEquals('a', $tag->tag);
		$this->assertEquals('http://asd.com/', $tag->attr['href']);
	}

	public function test_parse_inner() {
		$str = '<a href="http://asd.com/">Text</a>';
		$tag = HTMLTag::parse($str);
		$this->assertEquals('a', $tag->tag);
		$this->assertEquals('http://asd.com/', $tag->attr['href']);
		$this->assertEquals('Text', $tag->content);
	}

}
