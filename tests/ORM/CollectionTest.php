<?php

use nadlib\NadlibTestCase;

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 02.02.2016
 * Time: 16:42
 */
class CollectionTest extends NadlibTestCase
{

	public function test_lazyMemeberIterator(): void
	{
		$this->markTestSkipped('RequestCollection was not found.');

//		$rc = new RequestCollection();
//		$rc->orderBy = 'ORDER BY ctime DESC LIMIT 10';
//
//		$iterator = $rc->getLazyMemberIterator('ORSRequest');
//		$current = $iterator->current();
//		debug($current);
	}

	/**
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 * @throws Exception
	 */
	public function test_immutability_with_count(): void
	{
		$db = new DBPlacebo();
		$db->setQB(new SQLBuilder($db));

		$c = new Collection(null, [], '', $db);
		$query100 = $c->getQueryWithLimit();
		// @todo: fix empty WHERE clause
		$this->assertEqualsIgnoreSpaces('SELECT
"Collection".*
FROM "Collection"
 WHERE
ORDER BY id', $query100 . '');
	}

	public function test_findInData()
	{
		$c = new Collection(null, [], '', new DBPlacebo());
		$c->setData([['id' => 1], ['id' => 2], ['id' => 3]]);
		$this->assertEquals(['id' => 2], $c->findInData(['id' => 2]));
	}

}
