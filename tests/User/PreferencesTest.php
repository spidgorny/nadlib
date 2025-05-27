<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 06.12.2018
 * Time: 17:00
 */

namespace User;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use Preferences;

class PreferencesTest extends TestCase
{

	public function testGet(): void
	{
		$user = (object)[
			'data' => [
				'prefs' => '',
			]
		];
		$p = new Preferences($user);
		$is123 = $p->get('not existing', 123);
		static::assertEquals(123, $is123);
	}
}
