<?php

namespace Data;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use Bytes;

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 18.01.2016
 * Time: 11:34
 */
class BytesTest extends TestCase
{

	public function test_return_bytes(): void
	{
		$mb = \ini_get('memory_limit');
		$b = new Bytes($mb);
		$b2 = Bytes::return_bytes($mb);
		static::assertEquals($b->getBytes(), $b2);
	}

}
