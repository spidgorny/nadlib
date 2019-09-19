<?php

class slTableTest extends PHPUnit_Framework_TestCase
{

	function test_construct()
	{
		$s = new slTable();
		$this->assertEquals($s->more, [
			'class' => 'nospacing',
		]);
	}

	function test_construct_with_more()
	{
		$s = new slTable([], 'class="whatever"');
		$this->assertEquals($s->more, [
			'class' => 'whatever',
		]);
	}

	function test_construct_with_more_array()
	{
		$s = new slTable([], ['class' => "whatever"]);
		$this->assertEquals($s->more, [
			'class' => 'whatever',
		]);
	}

	function test_construct_with_more_id()
	{
		$s = new slTable([], ['class' => "whatever", 'id' => 'qwe']);
		$this->assertEquals([
			'class' => 'whatever',
			'id' => 'qwe',
		], $s->more);
		$this->assertEquals('qwe', $s->ID);
	}

	function test_construct_with_more_id_string()
	{
		$s = new slTable([], 'id="qwe"');
		$this->assertEquals([
			'id' => 'qwe',
		], $s->more);
		$this->assertEquals('qwe', $s->ID);
	}

	function test_construct_with_more_id_string_more()
	{
		$s = new slTable([], 'id="qwe" cellpadding="2"');
		$this->assertEquals([
			'id' => 'qwe',
			'cellpadding' => 2,
		], $s->more);
		$this->assertEquals('qwe', $s->ID);
	}

}
