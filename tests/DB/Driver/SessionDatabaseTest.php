<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-08-19
 * Time: 02:34
 */

namespace DB\Driver;

use nadlib\SessionDatabase;
use PHPUnit\Framework\TestCase;

class SessionDatabaseTest extends TestCase
{

	public function test_runInsertQuery(): void
	{
		$db = new SessionDatabase();
		$table = 'application';
		$db->createTable($table);
		$db->runInsertQuery($table, ['a' => 'b']);
		$db->runInsertQuery($table, ['a' => 'b']);
		static::assertEquals(2, $db->getRowsIn($table));
	}

}
