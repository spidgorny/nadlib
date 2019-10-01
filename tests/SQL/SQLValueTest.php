<?php

class SQLValueTest extends \PHPUnit\Framework\TestCase
{

	public function test_SQLValue1()
	{
		$sv = new SQLValue('asd');
		$this->assertEquals('$0$', (string)$sv);
	}

	public function test_SQLValue1_5()
	{
		$sv = new SQLFunction('lower', new SQLValue('asd'));
		$this->assertEquals('lower($0$)', (string)$sv);
	}

	public function test_SQLValue2()
	{
		$sw = new SQLWhereEqual('field', new SQLValue('asd'));
		$sql = (string)$sw;
		$this->assertEquals('"field" = $0$', $this->normalize($sql));
		$this->assertEquals('asd', $sw->getParameter());
	}

	public function test_SQLValue3()
	{
		$sw = new SQLWhere(['field' => new SQLValue('asd')]);
		$this->assertEquals('field', first($sw->partsAsObjects())->getField());
		$this->assertInstanceOf(SQLWhereEqual::class, first($sw->partsAsObjects()));
		$this->assertInstanceOf(SQLValue::class, first($sw->getAsArray()));
		$objects = $sw->debug();
//		llog($objects);

		$this->assertEquals('WHERE "field" = $1', $this->normalize((string)$sw));
		$this->assertEquals(['asd'], $sw->getParameters());
	}

	public function test_SQLValue4()
	{
		$qb = new SQLBuilder(new DBLayerNOEPLTS(null, null, null));
		$sql = $qb->getSelectQuery('table', [
			'field' => new SQLValue('asd')
		]);
//		debug($sql->debug());
		$this->assertEquals('SELECT "table".* FROM "table" WHERE "field" = $1', $this->normalize((string)$sql));
		$this->assertEquals(['asd'], $sql->getParameters());
	}

	public function normalize($string)
	{
		// https://stackoverflow.com/questions/643113/regex-to-strip-comments-and-multi-line-comments-and-empty-lines
		$string = preg_replace('!/\*.*?\*/!s', '', $string);
		$string = preg_replace('/\s*$^\s*/m', "\n", $string);
		$string = preg_replace('/[ \t\r\n]+/', ' ', $string);
		return trim($string);
	}

}
