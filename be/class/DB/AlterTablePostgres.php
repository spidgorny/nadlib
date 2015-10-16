<?php

class AlterTablePostgres extends AlterTableHandler {

	function getCreateQuery($table, array $columns) {
		$set = array();
		foreach ($columns as $col) {
			$set[] = $col['name'].' '.$col['type'].' '.($col['notnull'] ? 'NOT NULL' : 'NULL');
		}
		//debug($col);
		return 'CREATE TABLE '.$table.' ('.implode(",\n", $set).');';
	}

	function getAlterQuery($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$index['Field'].
			' '.$index['Type'].
			' '.(($index['len'] > 0) ? ' ('.$index['len'].')' : '').
			' '.($index['not null'] ? 'NOT NULL' : 'NULL');
		return $query;
	}

	function getChangeQuery($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ALTER COLUMN '.$index['Field'].' '.$index['Field'].
			' '.$index['Type'].
			' '.(($index['len'] > 0) ? ' ('.$index['len'].')' : '').
			' '.($index['not null'] ? 'NOT NULL' : 'NULL');
		return $query;
	}

}
