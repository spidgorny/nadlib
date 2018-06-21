<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 15.03.2017
 * Time: 17:59
 */
class PGArrayTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	/**
	 * @var PGArray
	 */
	protected $sut;

	public function setUp()
	{
		$config = Config::getInstance();
		$this->db = $config->getDB();
		$pga = new PGArray($this->db);
		$this->sut = $pga;
	}

	public function test_setPGArray_simple()
	{
		$str = $this->sut->setPGArray([1, "a", '/"\\']);
//		echo $str, BR;
		if (!($str instanceof AsIs)) {
			$str = "'" . $str . "'";
		}
		$row = $this->db->fetchAssoc("select " . $str . "::varchar[] as array");
//		debug($row['array']);
		$this->assertEquals('{1,a,"/\\"\\\\"}', $row['array']);
	}

	public function test_lineBreak()
	{
		$pga = new PGArray($this->db);
		$fixture = [
			'b',
			"d
e",
		];
//		var_export($fixture);
//		echo PHP_EOL;

		$string = $pga->setPGArray($fixture);
//		var_export($string . '');
//		echo PHP_EOL;
//		echo 'fixture: ', $this->serialize($fixture), PHP_EOL;
//		echo 'encode: ', $this->serialize($string.''), PHP_EOL;
		$this->assertEquals("ARRAY['b','d
e']", $string . '');
	}

	public function test_lineBreak_decode()
	{
		$pga = new PGArray($this->db);
		$decode = $pga->getPGArray('{"b","d
e"}');
//		var_export($decode);
//		echo PHP_EOL;
//		echo 'fixture: ', $this->serialize($fixture), PHP_EOL;
//		echo 'decode: ', $this->serialize($decode), PHP_EOL;
		$fixture = [
			'b',
			"d
e",
		];
		$this->assertEquals($fixture, $decode);
	}

	public function serialize($var)
	{
		$serial = serialize($var);
		$serial = str_replace("\n", '{0x0A}', $serial);
		$serial = str_replace("\r", '{0x0D}', $serial);
		return $serial;
	}

	public function test_PGArray_toString()
	{
		$fixture = [
			1,
			2,
			"slawa",
			"multi
line"
		];
		$pga = new PGArray($this->db, $fixture);
		$insert = $this->db->getInsertQuery('asd', [
			'arrayField' => $pga,
		]);
//		debug($insert);
		$this->assertEquals("INSERT INTO \"asd\" (\"arrayField\") VALUES (ARRAY['1', '2', 'slawa', 'multi
line'])", $insert);
	}

}

/*
SHOW standard_conforming_strings;
-- set standard_conforming_strings = 'off';
-- SHOW standard_conforming_strings;
select '{"AppBundle\Model\\\Version\\RisVersion:1912477"}'::varchar[] as array;
select ('{"AppBundle\Model\\\Version\\RisVersion:1912477"}'::varchar[])[1] as array;
select ARRAY['AppBundle\Model\Version\RisVersion:1912477'] as array;
select (ARRAY['AppBundle\Model\Version\RisVersion:1912477'])[1] as el3;
 */
