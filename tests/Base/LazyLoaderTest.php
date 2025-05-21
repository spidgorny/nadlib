<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 06.06.2017
 * Time: 11:27
 */

namespace Base;

use LazyLoader;
use PHPUnit\Framework\TestCase;
use stdClass;

class LazyLoaderTest extends TestCase
{

	public function test_ll_same_value(): void
	{
		$l1 = new LazyLoader('123');
		$v1 = $l1();
		$v2 = $l1();
		static::assertEquals('123', $v1);
		static::assertEquals('123', $v2);
	}

	public function test_ll_same_object(): void
	{
		$l1 = new LazyLoader(new stdClass());
		$v1 = $l1();
		$v2 = $l1();
		static::assertEquals(spl_object_hash($v1), spl_object_hash($v2));
	}

	public function test_ll_time_once(): void
	{
		$l1 = new LazyLoader(function (): void {
			sleep(1);
		});
		$start = microtime(true);
		$l1();
		$l1();
		$duration = microtime(true) - $start;
//		echo __METHOD__, ': ', $duration, PHP_EOL;
		static::assertLessThan(2, $duration);
	}

}
