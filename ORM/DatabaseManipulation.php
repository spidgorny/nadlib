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
		return $db->runInsertQuery($table, $data);
	}

	public function update(array $update)
	{
		return $this->db->runUpdateQuery(static::getTableName(), $update, [
			'id' => $this->id,
		]);
	}

}
