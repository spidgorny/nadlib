<?php

class Model {

	var $table;

	var $idField = 'id';

	var $titleColumn = 'name';

	var $itemClassName = '?';

	/**
	 * @var DBInterface|SQLBuilder
	 */
	protected $db;

	function __construct(DBInterface $db = null)
	{
		$this->db = $db;
	}

	public function setDB(DBInterface $db)
	{
		$this->db = $db;
	}

	function getCollection(array $where = [], $orderBy = null)
	{
		$col = Collection::createForTable($this->table);
		$col->idField = $this->idField;
		$col->itemClassName = $this->itemClassName;
		$col->objectifyByInstance = method_exists($this->itemClassName, 'getInstance');
		$col->where = $where;
		if ($orderBy) {
			$col->orderBy = $orderBy;
		}
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
		$col = $this->getCollection();
		$content = $col->renderList();
		return $content;
	}

	function insert(array $data)
	{
		$data[$this->idField] = RandomStringGenerator::likeYouTube();
		return $this->db->runInsertQuery($this->table, $data);
	}

	function getCount()
	{
		//return $this->getCollection()->getCount();
		return $this->db->numRows('SELECT count(*) FROM '.$this->table);
	}

	function getByID($id)
	{
		$found = $this->getCollection()->findInData([
			$this->idField => $id,
		]);
		$className = $this->itemClassName;
		$instance = $className::getInstance($found);
		return $instance;
	}

	function getFormFromModel()
	{
		$desc = [];
		foreach (get_object_vars($this) as $fieldName => $_) {
			$field = new ReflectionProperty(get_class($this), $fieldName);
			$sComment = $field->getDocComment();
			if ($sComment) {
				$dc = new DocCommentParser($sComment);
				//debug($field->getName(), $sComment, $dc->getAll());
				if ($dc->is_set('column')) {
					$desc[$field->getName()] = [
						'label' => $dc->get('label') ?: $dc->getDescription(),
						'type' => $dc->get('type') ?: 'text',
						// optional is true by default
						'optional' => $dc->is_set('optional') || !$dc->is_set('required'),
					];
				}
			}
		}
		return $desc;
	}

	static function getInstance(array $data)
	{
		$obj = new self(null);
		$obj->setDB(Config::getInstance()->getDB());
		$obj->setData($data);
		return $obj;
	}

	function setData(array $data)
	{
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}

}
