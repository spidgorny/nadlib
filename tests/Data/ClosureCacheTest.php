<?php

namespace Data;

use ClosureCache;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 22.06.2016
 * Time: 16:42
 */
class ClosureCacheTest extends TestCase
{

	public function test_it(): void
	{
		/*
		$cc = new ClosureCache(
			function () {
				return rand(0, PHP_INT_MAX);
			}
		);*/

		$cc = ClosureCache::getInstance('test', function (): int {
			return random_int(0, PHP_INT_MAX);
		});
		$first = $cc->get();
		$second = $cc->get();
		static::assertEquals($first, $second);
	}

}
