<?php

class AlterTablePostgres extends AlterTableHandler implements AlterTableInterface
{

	function getCreateQuery($table, array $columns)
	{
		$set = [];
		foreach ($columns as $col) {
			$set[] = $col['name'] . ' ' . $col['type'] . ' ' . ($col['notnull'] ? 'NOT NULL' : 'NULL');
		}
		//debug($col);
		return 'CREATE TABLE ' . $table . ' (' . implode(",\n", $set) . ');';
	}

	function getAlterQuery($table, $oldName, TableField $index)
	{
		$query = "ALTER TABLE {$table} ALTER COLUMN $oldName " . $index->field .
			' ' . $index->type .
			' ' . (($index['len'] > 0) ? ' (' . $index['len'] . ')' : '') .
			' ' . ($index['not null'] ? 'NOT NULL' : 'NULL');
		return $query;
	}

	function getAddQuery($table, TableField $index)
	{
		$query = 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $index->field .
			' ' . $index->type .
			' ' . $this->getFieldParams($index);
		return $query;
	}

	function getFieldParams(TableField $index)
	{
		return
			' ' . ($index->isNull ? 'NULL' : 'NOT NULL');
	}

}
