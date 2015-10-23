<?php

class AlterTableSQLite extends AlterTableHandler {

	function getCreateQuery($table, array $columns) {
		$set = array();
		foreach ($columns as $row) {
			$col = TableField::init($row);
			$set[] = $col->field.' '.$col->type.' '
					.($col->isNull ? 'NULL' : 'NOT NULL');
		}
		//debug($col);
		return 'CREATE TABLE '.$table.' ('.implode(",\n", $set).');';
	}

	function getFieldParams(TableField $index) {
		$default = $index->default
				? (in_array($index->default, $this->db->getReserved())
						? $index->default
						: $this->db->quoteSQL($index->default))
				: '';
		return ' '.trim(
				(($index->isNull == 'NO') ? 'NOT NULL' : 'NULL').
				' '.($index->collation ? 'COLLATE '.$index->collation : '').
				' '.($index->default ? "DEFAULT ".$default : '').		// must not be quoted for CURRENT_TIMESTAMP
				' '.($index->comment ? "COMMENT '".$index->comment."'" : '').
				' '.(($index->key == 'PRI') ? "PRIMARY KEY" : '').
				' '.implode(' ', $index->extra));
	}

	function getAlterQuery($table, TableField $index) {
		//' '.(($index['len'] > 0) ? ' ('.$index['len'].')' : '').
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$index->field.
			' '.$index->type.
			' '.($index->isNull ? 'NULL' : 'NOT NULL');
		return $query;
	}

	function getChangeQuery($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ALTER COLUMN '.$index['Field'].' '.$index['Field'].
			' '.$index['Type'].
			' '.(($index['len'] > 0) ? ' ('.$index['len'].')' : '').
			' '.($index['not null'] ? 'NOT NULL' : 'NULL');
		return $query;
	}

	function getAddQuery($table, TableField $index) {
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$index->field.
				' '.$index->type.$this->getFieldParams($index);
		return $query;
	}

}
