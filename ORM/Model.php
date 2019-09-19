<?php

class Model
{

	public $table;

	public $idField = 'id';

	public $titleColumn = 'name';

	public $itemClassName = '?';

	/**
	 * @var DBInterface|SQLBuilder
	 */
	public $db;

	function __construct(DBInterface $db)
	{
		$this->db = $db;
	}

	function getCollection()
	{
		$col = Collection::createForTable($this->table);
		return $col;
	}

	function getModel($id)
	{
		$model = call_user_func([$this->itemClassName, 'getInstance'], $id);
		return $model;
	}

}
