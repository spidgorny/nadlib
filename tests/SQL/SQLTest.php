<?php

namespace SQL;

use DBLayerBase;
use DBPlacebo;
use PHPUnit\Framework\TestCase;
use SQLBuilder;
use SQLNow;

class SQLTest extends TestCase
{

	/**
	 * @var DBLayerBase|SQLBuilder
	 */
	protected $db;

	protected function setUp(): void
	{
		self::markTestSkipped('PG dependent');
//		$this->db = Config::getInstance()->getDB();
	}

	public function test_SQLNow_PG(): void
	{
		$now = new SQLNow();
		$now->injectDB($this->db);

		$string = $now . '';

		static::assertEquals('now()', $string);
	}

	public function test_SQLNow_PG_update_no_quote(): void
	{
		if ($this->db instanceof DBPlacebo) {
			static::markTestSkipped('DBPlacebo has different SQL');
		}

		$now = new SQLNow();
		$now->injectDB($this->db);

		$update = [
			'mtime' => $now,
		];
		$query = $this->db->getUpdateQuery('asd', $update, ['id' => 1]);

		$expected = "UPDATE \"asd\"
SET \"mtime\" = now()
WHERE
\"id\" = '1' ";
		static::assertEquals($this->normalize($expected), $this->normalize($query));
//		$this->assertEquals($expected, $query);
	}

	public function normalize($s): string
	{
		return implode(PHP_EOL, trimExplode("\n", $s));
	}

	public function test_SQLNow_PG_insert_no_quote(): void
	{
		if ($this->db instanceof DBPlacebo) {
			static::markTestSkipped('DBPlacebo has different SQL');
		}

		$now = new SQLNow();
		$update = [
			'mtime' => $now,
		];
		$query = $this->db->getInsertQuery('asd', $update);

		$expected = 'INSERT INTO "asd" ("mtime") VALUES (now())';
		$expected = str_replace("\r", '', $expected);
		static::assertEquals($expected, $query);
	}

}
