<?php

namespace Data;


use AppDev\OnlineRequestSystem\Framework\TestCase;

if (!\defined('DEVELOPMENT')) {
	\define('DEVELOPMENT', true);
}


/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 23.11.2016
 * Time: 11:45
 */
class ArrayFunctionsTest extends TestCase
{

	public function test_unique_multidim_array_thru(): void
	{
		$fixture = [
			'a' => 'b',
			'c' => 'b', // del
			'e' => [
				'a' => 'b',
				'b' => 'b',  // del
			],
		];
		$unique = unique_multidim_array_thru($fixture);
//		debug($unique);
		static::assertEquals([
			'a' => 'b',
			'e' => [
				'a' => 'b',
			]
		], $unique);
	}

	public function test_array_find(): void
	{
		$data = [
			'asd',
			'qwe',
			'123',
		];
		static::assertEquals('qwe', array_find_fast(static fn($line): bool => str_startsWith($line, 'q'), $data));
	}

}
