<?php

interface AlterTableInterface
{

	/**
	 * CREATE TABLE $table ...
	 * @param string $table
	 * @param array $columns
	 * @return mixed
	 */
	function getCreateQuery($table, array $columns);

	/**
	 * ALTER TABLE CHANGE COLUMN $oldName ...
	 * @param string $table
	 * @param string $oldName
	 * @param TableField $index
	 * @return mixed
	 */
	function getAlterQuery($table, $oldName, TableField $index);

	/**
	 * ALTER TABLE ADD COLUMN ...
	 * @param string $table
	 * @param TableField $index
	 * @return mixed
	 */
	function getAddQuery($table, TableField $index);

	function getFieldParams(TableField $index);

}
