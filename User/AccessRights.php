<?php

/**
 * Class AccessRights represents all the rights of a specific group
 */
class AccessRights implements AccessRightsInterface
{
	protected $accessTable = 'access';
	protected $groupAccessTable = 'department_access';
	protected $id_usergroup = 'id_department';
	protected $id_useraccess = 'id_access';

	public $groupID;

	protected $arCache = [];

	/**
	 * @var DBInterface
	 */
	protected $db;

	protected $query;

	public function __construct($idGroup)
	{
		TaylorProfiler::start($profiler = Debug::getBackLog(7, 0, BR, false));
		$this->db = Config::getInstance()->getDB();
		$this->groupID = $idGroup;
		$this->reload();
		TaylorProfiler::stop($profiler);
	}

	public function reload()
	{
		$this->init($this->groupID);
	}

	public function init($idGroup)
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
		$this->query = $this->db->lastQuery;
		//		debug($this->query);
		//debug($data);
		$data = new ArrayPlus($data);
		$data = $data->column_assoc('name', 'affirmative')->getData();
		foreach ($data as &$affirmative) {
			$affirmative = $affirmative ? true : false;
		}
		$this->arCache = $data;
		//debug($this->arCache);
	}

	public function can($what)
	{
		//debug($what, $this->arCache);
		if (isset($this->arCache[$what])) {
			return $this->arCache[$what];
		} else {
			throw new AccessDeniedException('Checking non-existing access-right: ' . $what);
		}
	}

	public function getList()
	{
		return $this->arCache;
	}

	public function getQuery()
	{
		return $this->query;
	}

	public function render()
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
		return $keys;
	}

	/**
	 * @param string $wherePlus
	 * @return AccessRightModel[]|ArrayPlus
	 * @throws Exception
	 */
	public function getAllRights($wherePlus = 'WHERE 1 = 1')
	{
		$accessRights = $this->db->fetchAll("
		SELECT * FROM {$this->accessTable}
		$wherePlus 
		ORDER BY name");
		$accessRights = new ArrayPlus($accessRights);
		$accessRights = $accessRights->IDalize('id');
		$accessRights = $accessRights->convertTo(AccessRightModel::class);
		return $accessRights;
	}

	public function setAccess($name, $value)
	{
		$this->arCache[$name] = $value;
	}
}
