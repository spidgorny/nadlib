<?php

class AlterTableMySQL extends AlterTableHandler {

	var $jsonFile;

	/**
	 * @param $table
	 * @param array[] $columns
	 * @return string
	 */
	function getCreateQuery($table, array $columns) {
		$set = array();
		foreach ($columns as $row) {
			$col = TableField::init($row);
			$set[] = $this->db->quoteKey($col->field).' '.$col->type . $this->getFieldParams($col);
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

	function getAlterQuery($table, $oldName, TableField $index) {
		$controller = Index::getInstance()->controller;
		$query = 'ALTER TABLE '.$table.' MODIFY COLUMN '.$oldName.' '.$index->field.
			' '.$index->type.
			' '.(($index->isNull == 'NO') ? 'NOT NULL' : 'NULL').
			' '.($index->collation ? 'COLLATE '.$index->collation : '').
			' '.($index->default ? "DEFAULT '".$index->default."'" : '').
			' '.($index->comment ? "COMMENT '".$index->comment."'" : '').
			' '.implode(' ', $index->extra);
		$link = $controller->a($controller->getURL(array(
			'c' => get_class($this),
			'file' => basename($this->jsonFile),
			'action' => 'runSQL',
			'table' => $table,
			'sql' => $query,
		)), $query);
		return $link;
	}

	function getAddQuery($table, TableField $index) {
		$controller = Index::getInstance()->controller;
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$index->field.
			' '.$index->type.$this->getFieldParams($index);
		$link = $controller->a($controller->getURL(array(
			'c' => get_class($this),
			'file' => basename($this->jsonFile),
			'action' => 'runSQL',
			'table' => $table,
			'sql' => $query,
		)), $query);
		return $link;
	}

}
