<?php

class AlterTablePostgres extends AlterTableHandler implements AlterTableInterface
{

	/**
	 * @param string $table
	 * @param TableField[] $columns
	 * @return string
	 */
	public function getCreateQuery($table, array $columns): string
	{
		$set = [];
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

	public function getAlterQuery($table, $oldName, TableField $index): string
	{
		return sprintf('ALTER TABLE %s ALTER COLUMN %s ', $table, $oldName) . $index->field .
			' ' . $index->type .
			' ' . (($index['len'] > 0) ? ' (' . $index['len'] . ')' : '') .
			' ' . ($index['not null'] ? 'NOT NULL' : 'NULL');
	}

	public function getAddQuery($table, TableField $index): string
	{
		return 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $index->field .
			' ' . $index->type .
			' ' . $this->getFieldParams($index);
	}

	public function getFieldParams(TableField $index): string
	{
		return
			' ' . ($index->isNull ? 'NULL' : 'NOT NULL');
	}

}
