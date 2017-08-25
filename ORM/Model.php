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

	/**
	 * @var DBInterface|SQLBuilder
	 */
	protected $db;

	public $id;

	function __construct(DBInterface $db = null, array $data = [])
	{
		$this->setDB($db);
		$this->setData($data);
	}

	public function setDB(DBInterface $db)
	{
		$this->db = $db;
	}

	public function getName()
	{
		// override this
		return null;
	}

	/**
	 * @return ArrayPlus
	 */
	public function getData()
	{
		$data = $this->db->fetchAllSelectQuery($this->table, []);
		if (!($data instanceof ArrayPlus)) {
			$data = new ArrayPlus($data);
		}
		return $data;
	}

	function renderList()
	{
		$list = array();
		if ($this->getData()->count()) {
			foreach ($this->getData() as $id => $row) {
				$this->setData($row);
				if (method_exists($this, 'render')) {
					$content = $this->render();
				} elseif (method_exists($this, 'getSingleLink')) {
					$link = $this->getSingleLink();
					if ($link) {
						$content = new HTMLTag('a', array(
							'href' => $link,
						), $this->getName());
					} else {
						$content = $this->getName();
					}
				} else {
					$content = $this->getName();
				}
				$list[$id] = $content;
			}
			return new UL($list);
		}
		return null;
	}

	function insert(array $data)
	{
		if (!isset($data[$this->idField])) {
			$data[$this->idField] = RandomStringGenerator::likeYouTube();
		}
		return $this->db->runInsertQuery($this->table, $data);
	}

	function getByID($id)
	{
		$found = $this->db->fetchOneSelectQuery($this->table, [
			$this->idField => $id,
		]);
		if ($found) {
			$this->setData($found);
		} else {
			$this->unsetData();
		}
		return $this;
	}

	function getFormFromModel()
	{
		$desc = [];
		$fields = $this->getFields();
		foreach ($fields as $field => $dc) {
			$desc[$field] = [
				'label' => $dc->get('label') ?: $dc->getDescription(),
				'type' => $dc->get('type') ?: 'text',
				// optional is true by default
				'optional' => $dc->is_set('optional') || !$dc->is_set('required'),
			];
		}
		return $desc;
	}

	function getFields()
	{
		$fields = [];
		foreach (get_object_vars($this) as $fieldName => $_) {
			$field = new ReflectionProperty(get_class($this), $fieldName);
			$sComment = $field->getDocComment();
			if ($sComment) {
				$dc = new DocCommentParser($sComment);
				//debug($field->getName(), $sComment, $dc->getAll());
				if ($dc->is_set('label')) {
					$fields[$fieldName] = $dc;
				}
			}
		}
		return $fields;
	}

	function getVisibleFields()
	{
		// TODO
	}

	static function getInstance(array $data)
	{
		$obj = new self(null);
		$obj->setDB(Config::getInstance()->getDB());
		$obj->setData($data);
		return $obj;
	}

	/**
	 * Different models may extend this to covert between
	 * different data types in DB and in runtime.
	 * @param array $data
	 */
	function setData(array $data)
	{
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}

	public function unsetData()
	{
		foreach ($this->getFields() as $field => $dc) {
			$this->$field = null;
		}
	}

	function id()
	{
		return $this->id;
	}

	function get($field)
	{
		return ifsetor($this->$field);
	}

	/**
	 * @param array $where
	 * @param string $orderBy
	 * @return array[]
	 */
	public function queryData(array $where, $orderBy = 'ORDER BY id DESC')
	{
		$data = $this->db->fetchAllSelectQuery($this->table, $where, $orderBy);
		return $data;
	}

	/**
	 * @param array $where
	 * @param string $orderBy
	 * @return ArrayPlus
	 */
	public function queryObjects(array $where, $orderBy = 'ORDER BY id DESC')
	{
		$data = $this->queryData($where, $orderBy);
		$list = new ArrayPlus();
		foreach ($data as $row) {
			$list->append(new static($this->db, $row));
		}
		return $list;
	}

	public function asArray()
	{
		$data = get_object_vars($this);
		unset($data['table']);
		unset($data['idField']);
		unset($data['titleColumn']);
		unset($data['db']);
		return $data;
	}

}
