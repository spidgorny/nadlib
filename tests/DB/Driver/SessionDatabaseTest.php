<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-08-19
 * Time: 02:34
 */

use nadlib\SessionDatabase;

class SessionDatabaseTest extends PHPUnit_Framework_TestCase {

	function test_runInsertQuery()
	{
		$db = new SessionDatabase();
		$table = 'application';
		$db->createTable($table);
		$db->runInsertQuery($table, ['a' => 'b']);
		$db->runInsertQuery($table, ['a' => 'b']);
		$this->assertEquals(2, sizeof($db->getRowsIn($table)));
	}

}
