<?php

trait DatabaseManipulation
{

	public static function getTableName()
	{
		return null;
	}

	public static function insert(DBInterface $db, array $data)
	{
		$table = static::getTableName();
		$ok = $db->runInsertQuery($table, $data);
		return $ok;
	}

}
