<?php

class AlterTableHandler
{

	/**
	 * @var MySQL|DBLayerPDO
	 */
	var $db;

	function __construct($db)
	{
		$this->db = $db;
	}

	function sameType($index1, $index2)
	{
		$t1 = $index1['Type'];
		$t2 = $index2['Type'];
		return $this->sameTypeString($t1, $t2);
	}

	function sameFieldType(TableField $index1, TableField $index2)
	{
		return $this->sameTypeString($index1->type, $index2->type);
	}

	/**
	 * @param string $t1
	 * @param string $t2
	 * @return bool
	 * @todo compare using TableField
	 */
	function sameTypeString($t1, $t2)
	{
		$int = ['int(11)', 'INTEGER', 'integer', 'tinyint(1)', 'int', 'tinyint(4)'];
		$text = ['text', 'varchar(255)', 'tinytext'];
		$time = ['numeric', 'timestamp', 'datetime'];
		$real = ['real', 'double', 'float'];
		$bool = ['binary(1)', 'bool', 'boolean'];
		if ($t1 == $t2) {
			return true;
		} elseif (in_array($t1, $int) && in_array($t2, $int)) {
			return true;
		} elseif (in_array($t1, $text) && in_array($t2, $text)) {
			return true;
		} elseif (in_array($t1, $time) && in_array($t2, $time)) {
			return true;
		} elseif (in_array($t1, $real) && in_array($t2, $real)) {
			return true;
		} elseif (in_array($t1, $bool) && in_array($t2, $bool)) {
			return true;
		} else {
			return false;
		}
	}

}
