<?php

class AlterTableMySQL extends AlterTableHandler implements AlterTableInterface
{

	/**
     * @param string $table
     * @param TableField[] $columns
     */
    public function getCreateQuery($table, array $columns): string
	{
		$set = [];
		foreach ($columns as $row) {
			$col = TableField::init((array)$row);
			$set[] = $this->db->quoteKey($col->field) . ' ' . $col->type . $this->getFieldParams($col);
		}

		//debug($col);
		return 'CREATE TABLE ' . $table . ' (' . implode(",\n", $set) . ');';
	}

	public function getFieldParams(TableField $index): string
	{
		$default = $index->default
			? (in_array($index->default, $this->db->getReserved())
				? $index->default
				: $this->db->quoteSQL($index->default))
			: '';
		return ' ' . trim(
				(($index->isNull == 'NO') ? 'NOT NULL' : 'NULL') .
				' ' . ($index->collation ? 'COLLATE ' . $index->collation : '') .
				' ' . ($index->default ? "DEFAULT " . $default : '') .        // must not be quoted for CURRENT_TIMESTAMP
				' ' . ($index->comment ? "COMMENT '" . $index->comment . "'" : '') .
				' ' . (($index->key == 'PRI') ? "PRIMARY KEY" : '') .
				' ' . implode(' ', $index->extra));
	}

	/**
     * @param string $table
     * @param string $oldName
     */
    public function getAlterQuery($table, $oldName, TableField $index): string
	{
		return 'ALTER TABLE ' . $table . ' CHANGE ' . $oldName . ' ' . $index->field .
			' ' . $index->type .
			' ' . (($index->isNull == 'NO') ? 'NOT NULL' : 'NULL') .
			' ' . ($index->collation ? 'COLLATE ' . $index->collation : '') .
			' ' . ($index->default ? "DEFAULT '" . $index->default . "'" : '') .
			' ' . ($index->comment ? "COMMENT '" . $index->comment . "'" : '') .
			' ' . implode(' ', $index->extra);
	}

	public function getAddQuery($table, TableField $index): string
	{
		return 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $index->field .
			' ' . $index->type .
			$this->getFieldParams($index);
	}

}
