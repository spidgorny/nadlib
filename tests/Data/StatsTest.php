<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 28.09.2018
 * Time: 16:37
 */

namespace nadlib\tests\Data;

use nadlib\Data\Stats;

class StatsTest extends \PHPUnit\Framework\TestCase
{

	/**
	 * Totally random values should have large STDDEV
	 */
	public function testSD()
	{
		$set = [];
		foreach (range(0, 10000) as $i) {
			$set[] = rand(0, 100);
		}
		$dev = Stats::cv($set);
//		debug($dev);
		$this->assertGreaterThan(0.57, $dev);
	}

}
