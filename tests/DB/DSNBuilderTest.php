<?php

namespace DB;

use DSNBuilder;

class DSNBuilderTest extends \PHPUnit\Framework\TestCase
{

	public function testMake(): void
	{
		$builder = DSNBuilder::make('sqlite', null, null, null, '/asd/qwe.sqlite');
		$dsn = $builder->__toString();
//		debug($dsn);
		static::assertEquals('sqlite:/asd/qwe.sqlite', $dsn);
	}
}
