<?php

/**
 * Class AccessRights represents all the rights of a specific group
 */
class AccessRights {
	protected $accessTable = 'access';
	protected $groupAccessTable = 'department_access';
	protected $id_usergroup = 'id_department';
	protected $id_useraccess = 'id_access';

	public $groupID;

	protected $arCache = array();

	/**
	 * @var DBInterface
	 */
	protected $db;

	function __construct($idGroup) {
		TaylorProfiler::start($profiler = Debug::getBackLog(7, 0, BR, false));
		$this->db = Config::getInstance()->getDB();
		$this->init($this->groupID = $idGroup);
		TaylorProfiler::stop($profiler);
	}

	function init($idGroup) {
		$res = $this->db->runSelectQuery($this->accessTable.' /**/
			LEFT OUTER JOIN '.$this->groupAccessTable.' ON (
				'.$this->accessTable.'.id = '.$this->groupAccessTable.'.'.$this->id_useraccess.'
				AND '.$this->id_usergroup.' = '.$idGroup.')',
			array(), 'ORDER BY '.$this->accessTable.'.name',
			$this->accessTable.'.*, '.$this->groupAccessTable.'.id as affirmative');
		$data = $this->db->fetchAll($res);
		//debug($data);
		$data = new ArrayPlus($data);
		$data = $data->column_assoc('name', 'affirmative')->getData();
		foreach ($data as &$affirmative) {
			$affirmative = $affirmative ? TRUE : FALSE;
		}
		$this->arCache = $data;
		//debug($this->arCache);
	}

	function can($what) {
		//debug($what, $this->arCache);
		return isset($this->arCache[$what]) ? $this->arCache[$what] : NULL;
	}

	function getList() {
		return $this->arCache;
	}

	function render() {
		return new UL($this->arCache);
	}

	function __sleep() {
		$vars = get_object_vars($this);
		$keys = array_keys($vars);
		$keys = array_combine($keys, $keys);
		$types = array_map('gettype2', $vars);
		unset($keys['db'], $types['db']);
		//debug(array_combine($keys, $types));
		return $keys;
	}

}
