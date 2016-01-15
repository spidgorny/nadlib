<?php

interface AlterTableInterface {

	/**
	 * CREATE TABLE $table ...
	 * @param $table
	 * @param array $columns
	 * @return mixed
	 */
	function getCreateQuery($table, array $columns);

	/**
	 * ALTER TABLE CHANGE COLUMN $oldName ...
	 * @param $table
	 * @param $oldName
	 * @param TableField $index
	 * @return mixed
	 */
	function getAlterQuery($table, $oldName, TableField $index);

	/**
	 * ALTER TABLE ADD COLUMN ...
	 * @param $table
	 * @param TableField $index
	 * @return mixed
	 */
	function getAddQuery($table, TableField $index);

	function getFieldParams(TableField $index);

}
