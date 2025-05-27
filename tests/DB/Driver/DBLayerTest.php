<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 10.10.2018
 * Time: 11:14
 */

namespace DB\Driver;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use DBLayer;

class DBLayerTest extends TestCase
{

	public function testQuoteKey(): void
	{
		$db = new DBLayer();
		$quoted = $db->quoteKey('login');
		static::assertEquals('"login"', $quoted);
		$function = 'trim(login)';
		$unquoted = $db->quoteKey($function);
		static::assertEquals($function, $unquoted);
	}
}
