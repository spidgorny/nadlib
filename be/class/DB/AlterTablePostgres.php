<?php

class AlterTablePostgres extends AlterTableHandler implements AlterTableInterface {

	/**
	 * @param string $table
	 * @param TableField[] $columns
	 * @return mixed|string
	 */
	function getCreateQuery($table, array $columns)
	{
		$set = array();
		foreach ($columns as $col) {
			$sCol = $col->field . ' ' . $col->type . ' ';
			$sCol .= $col->isNull() ? 'NULL ' : 'NOT NULL ';
			if ($col->references) {
				$sCol .= 'REFERENCES ' . $col->references . ' ';
			}
			$set[] = $sCol;
			//debug($col);
		}
		return 'CREATE TABLE ' . $table . ' (' . PHP_EOL .
			implode(",\n", $set) . ');';
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
