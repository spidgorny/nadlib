<?php

class AlterTableMySQL extends AlterTableHandler {

	function getCreateQuery($table, array $columns) {
		$set = array();
		foreach ($columns as $col) {
			$set[] = $this->db->quoteKey($col['Field']).' '.$col['Type'].$this->getFieldParams($col);
		}
		//debug($col);
		return 'CREATE TABLE '.$table.' ('.implode(",\n", $set).');';
	}

	function getFieldParams(array $index) {
		$default = $index['Default']
			? (in_array($index['Default'], $this->db->getReserved())
				? $index['Default']
				: $this->db->quoteSQL($index['Default']))
			: '';
		return ' '.trim(
			(($index['Null'] == 'NO') ? 'NOT NULL' : 'NULL').
			' '.($index['Collation'] ? 'COLLATE '.$index['Collation'] : '').
			' '.($index['Default'] ? "DEFAULT ".$default : '').		// must not be quoted for CURRENT_TIMESTAMP
			' '.($index['Comment'] ? "COMMENT '".$index['Comment']."'" : '').
			' '.(($index['Key'] == 'PRI') ? "PRIMARY KEY" : '').
			' '.$index['Extra']);
	}

	function getAlterQuery($table, $oldName, array $index) {
		$query = 'ALTER TABLE '.$table.' MODIFY COLUMN '.$oldName.' '.$index['Field'].
			' '.$index['Type'].
			' '.(($index['Null'] == 'NO') ? 'NOT NULL' : 'NULL').
			' '.($index['Collation'] ? 'COLLATE '.$index['Collation'] : '').
			' '.($index['Default'] ? "DEFAULT '".$index['Default']."'" : '').
			' '.($index['Comment'] ? "COMMENT '".$index['Comment']."'" : '').
			' '.$index['Extra'];
		$link = $this->a($this->makeURL(array(
			'c' => get_class($this),
			'file' => basename($this->jsonFile),
			'action' => 'runSQL',
			'table' => $table,
			'sql' => $query,
		)), $query);
		return $link;
	}

	function getAddQuery($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$index['Field'].
			' '.$index['Type'].$this->getFieldParams($index);
		$link = $this->a($this->makeURL(array(
			'c' => get_class($this),
			'file' => basename($this->jsonFile),
			'action' => 'runSQL',
			'table' => $table,
			'sql' => $query,
		)), $query);
		return $link;
	}

}
