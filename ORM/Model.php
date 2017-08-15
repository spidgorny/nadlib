<?php

/**
 * Class Model - this is a replacement class for OODBase and Collection.
 * It's inconvenient to have two classes representing the same database table.
 * It's inconvenient to configure output of these two classes separately.
 * Control sorting and other things separately.
 * It's been written to represent a single source of truth for the whole model.
 * It was trying to use OODBase and Collection classes and convert to them.
 * But due to the missing Dependency Injection in these classed
 * we need to redesign the whole concept and deal with the data here.
 *
 * Principles:
 * - a Model represents a single entry
 * - a collection of Models of the same type are represented by ArrayPlus
 * - don't put collection methods directly into the Model - try to use ArrayPlus
 * - or add Traits that deal with multiple rows
 */
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
		$this->setDB($db);
	}

	public function setDB(DBInterface $db)
	{
		$this->db = $db;
	}

	function getCollection(array $where = [], $orderBy = null)
	{
		$col = Collection::createForTable($this->db, $this->table);
		$col->idField = $this->idField;
		$col->itemClassName = $this->itemClassName;
		$col->objectifyByInstance = method_exists($this->itemClassName, 'getInstance');
		$col->where = $where;
		if ($orderBy) {
			$col->orderBy = $orderBy;
		}

		// because it will try to run query on DBLayerJSON
		// that's OK because we don't use DBLayerJSON anymore
//		$col->count = $this->getCount();
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

	/**
	 * TODO: implement numRows in a way to get the amount of data from the query
	 * object.
	 * @return int
	 */
	function getCount()
	{
		// don't uncomment as this leads to recursive calls to $this->getCollection()
		return $this->getCollection()->getCount();
//		return $this->db->numRows('SELECT count(*) FROM '.$this->table);
	}

	function getByID($id)
	{
		$found = $this->getCollection()->findInData([
			$this->idField => $id,
		]);
		$className = $this->itemClassName;
		/** @var OODBase $instance */
		$instance = $className::getInstance($found);
		$instance->setDB($this->db);
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
