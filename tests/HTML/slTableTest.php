<?php

namespace HTML;

use PHPUnit\Framework\TestCase;
use Request;
use slTable;

class slTableTest extends TestCase
{

	public function test_construct(): void
	{
		$s = new slTable();
		static::assertEquals([
			'class' => 'nospacing',
		], $s->more);
	}

	public function test_construct_with_more(): void
	{
		$s = new slTable([], 'class="whatever"');
		static::assertEquals([
			'class' => 'whatever',
		], $s->more);
	}

	public function test_construct_with_more_array(): void
	{
		$s = new slTable([], ['class' => "whatever"]);
		static::assertEquals([
			'class' => 'whatever',
		], $s->more);
	}

	public function test_construct_with_more_id(): void
	{
		$s = new slTable([], ['class' => "whatever", 'id' => 'qwe']);
		static::assertEquals([
			'class' => 'whatever',
			'id' => 'qwe',
		], $s->more);
		static::assertEquals('qwe', $s->ID);
	}

	public function test_construct_with_more_id_string(): void
	{
		$s = new slTable([], 'id="qwe"');
		static::assertEquals([
			'id' => 'qwe',
		], $s->more);
		static::assertEquals('qwe', $s->ID);
	}

	public function test_construct_with_more_id_string_more(): void
	{
		$s = new slTable([], 'id="qwe" cellpadding="2"');
		static::assertEquals([
			'id' => 'qwe',
			'cellpadding' => 2,
		], $s->more);
		static::assertEquals('qwe', $s->ID);
	}

	public function test_detectSortBy(): void
	{
		$s = new slTable([
			['a' => 2, 2, 3],
			['a' => 4, 5, 6],
			['a' => 1, 5, 6],
		]);
		$request = new Request();
		$request->setArray(['slTable' => [
			'sortBy' => 'a',
		]]);
		$s->setRequest($request);
		$s->detectSortBy();
		static::assertEquals('a', $s->sortBy);
	}

	public function test_detectSortBy_no_data(): void
	{
		$s = new slTable([]);
		$request = new Request();
		$request->clear();

		$s->setRequest($request);
		$s->detectSortBy();
		static::assertEquals([], $s->thes);
		static::assertEquals(null, $s->sortBy);
	}

	public function test_detectSortBy_no_request(): void
	{
		$s = new slTable([
			['a' => 1],
		]);
		$request = new Request();
		$request->clear();

		$s->setRequest($request);
		$s->sortable = true;    // required for detectSortBy()
		$s->detectSortBy();
		static::assertEquals([
			'a' => [
				'name' => 'a',
			],
		], $s->thes);
		static::assertEquals('a', $s->sortBy);
	}

	public function test_detectSortBy_no_request_no_sort(): void
	{
		$s = new slTable([
			['a' => 1],
		]);
		$request = new Request();
		$request->clear();

		$s->setRequest($request);
		$s->sortable = false;    // required for detectSortBy()
		$s->detectSortBy();
		static::assertEquals([], $s->thes);
		static::assertEquals(null, $s->sortBy);
	}

	public function test_has_header(): void
	{
		$s = new slTable([
			['a' => 1],
		]);
//		$s->generateThes();	//  fill $s->thes
//		$s->generateThead();
//		llog('thes', $s->thes);
//		llog('gen', $s->generation);

		$html = $s->getContent();
//		echo(tidy_repair_string($html, ['indent' => true]));
		static::assertStringContainsString('<th>a</th>', $html);
	}

	public function test_td_class(): void
	{
		$s = new slTable([
			['a' => 1, '###TD_CLASS###' => 'asd'],
		]);
		$s->ID = '8ebde336af5b22305e70fccf9607caa4';

		$html = $s->getContent();
//		llog($html);
		static::assertEquals(2, substr_count($html, '<tr'));
		static::assertEquals(2, substr_count($html, '</tr'));
	}

}
