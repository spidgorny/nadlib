<?php

namespace HTML;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use HTMLTag;
use SQLSelectQuery;

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 22.01.2016
 * Time: 17:27
 */
class HTMLTagTest extends TestCase
{

	public function test_parse_simple(): void
	{
		$str = '<a>';
		$tag = HTMLTag::parse($str);
		static::assertEquals('a', $tag->tag);
	}

	public function test_parse_simple_space(): void
	{
		$str = ' <a > ';
		$tag = HTMLTag::parse($str);
		static::assertEquals('a', $tag->tag);
	}

	public function test_parse_attrib(): void
	{
		$str = '<a href="http://asd.com/">';
		$tag = HTMLTag::parse($str);
		static::assertEquals('a', $tag->tag);
		static::assertEquals('http://asd.com/', $tag->attr['href']);
	}

	public function test_parse_inner(): void
	{
		$str = '<a href="http://asd.com/">Text</a>';
		$tag = HTMLTag::parse($str);
		static::assertEquals('a', $tag->tag);
		static::assertEquals('http://asd.com/', $tag->attr['href']);
		static::assertEquals('Text', $tag->content);
	}

	public function test_parse_recursive(): void
	{
		$str = '<a href="http://asd.com/"><b>Text</b></a>';
		$tag = HTMLTag::parse($str, true);
		static::assertEquals('a', $tag->tag);
		static::assertEquals('http://asd.com/', $tag->attr['href']);
		static::assertIsArray($tag->content);
		//pre_print_r($tag);
	}

	public function test_parse_recursive_back(): void
	{
		$str = "<a href=\"http://asd.com/\"><b>Text</b>\n</a>\n";
		$tag = HTMLTag::parse($str, true);
		static::assertEquals('a', $tag->tag);
		static::assertEquals('http://asd.com/', $tag->attr['href']);
		static::assertIsArray($tag->content);
		$back = $tag->__toString();
		//pre_print_r($str, $back);
		static::assertEquals($str, $back);
	}

	/**
	 * @throws \JsonException
	 */
	public function test_pre(): void
	{
		$title = HTMLTag::pre(json_encode('something', JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), ['style' => [
			'white-space' => 'pre-wrap'
		]]);
		static::assertEquals('<pre style="white-space: pre-wrap">&quot;something&quot;</pre>' . "\n", $title . '');
	}

	public function test_div_with_array_content(): void
	{
		$tag = HTMLTag::div([
			HTMLTag::span(['a', 'c']),
			HTMLTag::span('b'),
		]);
		static::assertEquals('<div><span>ac</span> <span>b</span> </div>', SQLSelectQuery::trim($tag));
	}

}
