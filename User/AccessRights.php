<?php

/**
 * Class AccessRights represents all the rights of a specific group
 */
class AccessRights extends OODBase implements AccessRightsInterface
{

	public $groupID;
	/**
	 * @var array
	 * @public for dehydration
	 */
	public $arCache = [];
	public $query;
	protected $accessTable = 'access';
	protected $groupAccessTable = 'department_access';
	protected $id_usergroup = 'id_department';
	protected $id_useraccess = 'id_access';
	/**
	 * @var DBInterface
	 */
	protected $db;

	/**
	 * AccessRights constructor.
	 * @param null|int $idGroup - can be null for dehydration
	 * @throws Exception
	 */
	public function __construct($idGroup = null, ?DBInterface $db = null)
	{
		$this->db = $db;
		$this->groupID = $idGroup;
		if ($this->groupID) {
			$this->reload();
		}
	}

	public function reload(): void
	{
		$this->init($this->groupID);
	}

	public function init($idGroup): void
	{
		$res = $this->db->runSelectQuery(
			$this->accessTable . ' /**/
			LEFT OUTER JOIN ' . $this->groupAccessTable . ' ON (
				' . $this->accessTable . '.id = ' . $this->groupAccessTable . '.' . $this->id_useraccess . '
				AND ' . $this->id_usergroup . ' = ' . $idGroup . ')',
			[],
			'ORDER BY ' . $this->accessTable . '.name',
			$this->accessTable . '.*, ' . $this->groupAccessTable . '.id as affirmative'
		);
		$data = $this->db->fetchAll($res);
		$this->query = $this->db->getLastQuery();
		//		debug($this->query);
		//debug($data);
		$data = new ArrayPlus($data);
		$data = $data->column_assoc('name', 'affirmative')->getData();
		foreach ($data as &$affirmative) {
			$affirmative = (bool)$affirmative;
		}

		$this->arCache = $data;
//		llog('arCache for', $this->groupID, $this->arCache);
	}

	public function can($what)
	{
		if (isset($this->arCache[$what])) {
			return $this->arCache[$what];
		}

		throw new AccessDeniedException('Checking non-existing access-right: ' . $what);
	}

	public function getList()
	{
		return $this->arCache;
	}

	public function getQuery()
	{
		return $this->query;
	}

	public function render(): UL
	{
		return new UL($this->arCache);
	}

	public function __sleep()
	{
		$vars = get_object_vars($this);
		$keys = array_keys($vars);
		$keys = array_combine($keys, $keys);

		$types = array_map('typ', $vars);
		unset($keys['db'], $types['db']);
		//debug(array_combine($keys, $types));
		return array_keys($keys);
	}

	/**
	 * @param array $wherePlus
	 * @return ArrayPlus
	 * @throws Exception
	 */
	public function getAllRights($wherePlus = [], $className = null)
	{
		$accessRights = $this->db->fetchAllSelectQuery($this->accessTable, $wherePlus, "ORDER BY name");
		$accessRights = new ArrayPlus($accessRights);
		$accessRights = $accessRights->IDalize('id');
		if ($className) {
			$accessRights = $accessRights->convertTo($className);
		}

		return $accessRights;
	}

	public function setAccess($name, $value): void
	{
		$this->arCache[$name] = $value;
	}

	public function dehydrate(): array
	{
		return [
			'class' => get_class($this),
			'groupID' => $this->groupID,
			'arCache' => $this->arCache,
			'query' => null,
		];
	}
}
