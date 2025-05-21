<?php

class StringFunctionsTest extends \PHPUnit\Framework\TestCase
{

	public function test_cap(): void
	{
		static::assertEquals('asd/', cap('asd'));
	}

	public function test_contains(): void
	{
		static::assertTrue(contains('abc', 'b'));
	}

	public function test_containsAny(): void
	{
		static::assertTrue(containsAny('bc', ['a', 'bc', 'cde']));
	}

	public function test_path_plus(): void
	{
		static::assertEquals('/asd/qwe', path_plus('/asd', 'qwe'));
		static::assertEquals('/asd/qwe', path_plus('/asd/', 'qwe'));
		static::assertEquals('/asd/qwe', path_plus('/asd/', '/qwe'));
		static::assertEquals('/asd/qwe', path_plus('/asd/', '/qwe/'));
		static::assertEquals('asd/qwe', path_plus('asd/', '/qwe/'));
	}

	public function test_path_plus_twice(): void
	{
		static::assertEquals('/asd/qwe/zxc', path_plus('/asd', 'qwe', 'zxc'));
	}

	public function test_str_contains(): void
	{
		static::assertFalse(str_contains('q', 'abc'));
	}

	public function test_str_endsWith(): void
	{
		static::assertTrue(str_endsWith('abc', 'bc'));
		static::assertFalse(str_endsWith('abc', 'qwe'));
	}

	public function test_str_icontains(): void
	{
		static::assertTrue(str_icontains('ABC', 'bc'));
	}

	public function test_str_replace_once(): void
	{
		static::assertEquals('qwe**', str_replace_once('*', 'e', 'qw***'));
	}

	public function test_str_startsWith(): void
	{
		static::assertTrue(str_startsWith('abc', 'a'));
		static::assertFalse(str_startsWith('abc', 'q'));
	}

	public function test_tab2nbsp(): void
	{
		static::assertEquals("a&nbsp;&nbsp;b", tab2nbsp("a\tb", 2));
	}

	public function test_tabify(): void
	{
		static::assertEquals("a\tb", tabify(['a', 'b']));
	}

	public function test_toCamelCase(): void
	{
		static::assertEquals('Tocamelcase', toCamelCase('tocamelcase'));
	}

	public function test_toDatabaseKey(): void
	{
		static::assertEquals('this_-should__be_key', toDatabaseKey('this-should_beKey'));
	}

	public function test_trimExplode(): void
	{
		$path = '/asd/qwe///';
		$parts = trimExplode('/', $path);
		static::assertEquals(['asd', 'qwe'], $parts);
	}

	public function test_unquote(): void
	{
		static::assertEquals('asd', unquote('"asd"'));
	}

}
