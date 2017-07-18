<?php

class Model {

	var $table;

	var $idField = 'id';

	var $titleColumn = 'name';

	var $itemClassName = '?';

	/**
	 * @var DBInterface|SQLBuilder
	 */
	var $db;

	function __construct(DBInterface $db)
	{
		$this->db = $db;
	}

	function getCollection()
	{
		$col = Collection::createForTable($this->table);
		$col->idField = $this->idField;
		$col->itemClassName = $this->itemClassName;
		// because it will try to run query on DBLayerJSON
		$col->count = $this->getCount();
		return $col;
	}

	function getModel($id)
	{
		$model = call_user_func([$this->itemClassName, 'getInstance'], $id);
		return $model;
	}

	function renderList()
	{
		$content = [];
		$col = $this->getCollection();
		$content[] = $col->renderList();
		return $content;
	}

	function insert(array $data)
	{
		$data[$this->idField] = RandomStringGenerator::likeYouTube();
		return $this->db->runInsertQuery($this->table, $data);
	}

	function getCount()
	{
		return $this->db->numRows($res = null);
	}

	function getByID($id)
	{
		$found = $this->getCollection()->findInData([
			$this->idField => $id,
		]);
		$instance = $this->itemClassName;
		$instance = $instance::getInstance($found);
		return $instance;
	}

}
