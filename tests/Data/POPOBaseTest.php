<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 10.12.2018
 * Time: 14:08
 */

namespace Data;

use DateTime;
use POPOBase;

class JustIdAndName extends POPOBase
{

	/**
	 * @var int
	 */
	public $id;

	public $name;

	/**
	 * @var DateTime
	 */
	public $date;

}

class POPOBaseTest extends \PHPUnit\Framework\TestCase
{

	public function test__construct(): void
	{
		self::markTestSkipped('PG dependent');
		$struct = [
			'id' => '1',
			'name' => '@slawa',
			'date' => '2018-12-10',
		];
		$json = (object)$struct;

		$p = new JustIdAndName($json);
		static::assertNotEmpty($p->id);
		static::assertTrue(is_int($p->id));
		static::assertTrue(is_string($p->name));
		static::assertInstanceOf(DateTime::class, $p->date);
	}
}
