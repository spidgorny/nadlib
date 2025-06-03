<?php

class AlterTableHandler implements AlterTableInterface
{

	/**
	 * @var DBLayerPDO
	 */
	public $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function sameType(array $index1, array $index2): bool
	{
		$t1 = $index1['Type'];
		$t2 = $index2['Type'];
		return $this->sameTypeString($t1, $t2);
	}

	public function sameFieldType($index1, $index2): bool
	{
		return $this->sameTypeString($index1->type, $index2->type);
	}

	/**
	 * @param string $t1
	 * @param string $t2
	 * @todo compare using TableField
	 */
	public function sameTypeString($t1, $t2): bool
	{
		$int = ['int(11)', 'INTEGER', 'integer', 'tinyint(1)', 'int', 'tinyint(4)'];
		$text = ['text', 'varchar(255)', 'tinytext'];
		$time = ['numeric', 'timestamp', 'datetime'];
		$real = ['real', 'double', 'float'];
		$bool = ['binary(1)', 'bool', 'boolean'];
		if ($t1 == $t2) {
			return true;
		}

		if (in_array($t1, $int) && in_array($t2, $int)) {
			return true;
		}

		if (in_array($t1, $text) && in_array($t2, $text)) {
			return true;
		}

		if (in_array($t1, $time) && in_array($t2, $time)) {
			return true;
		}

		if (in_array($t1, $real) && in_array($t2, $real)) {
			return true;
		}

		if (in_array($t1, $bool) && in_array($t2, $bool)) {
			return true;
		}

		return false;
	}

	public function getCreateQuery($table, array $columns)
	{
		// TODO: Implement getCreateQuery() method.
	}

	public function getAlterQuery($table, $oldName, TableField $index)
	{
		// TODO: Implement getAlterQuery() method.
	}

	public function getAddQuery($table, TableField $index)
	{
		// TODO: Implement getAddQuery() method.
	}

	public function getFieldParams(TableField $index)
	{
		// TODO: Implement getFieldParams() method.
	}
}
