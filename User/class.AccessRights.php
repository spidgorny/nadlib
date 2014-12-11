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

	function __construct($idGroup) {
		$this->init($this->groupID = $idGroup);
	}

	function init($idGroup) {
		$qb = Config::getInstance()->qb;
		$res = $qb->runSelectQuery($this->accessTable.' /**/
			LEFT OUTER JOIN '.$this->groupAccessTable.' ON (
				'.$this->accessTable.'.id = '.$this->groupAccessTable.'.'.$this->id_useraccess.'
				AND '.$this->id_usergroup.' = '.$idGroup.')',
			array(), 'ORDER BY '.$this->accessTable.'.name',
			$this->accessTable.'.*, '.$this->groupAccessTable.'.id as affirmative');
		$data = Config::getInstance()->db->fetchAll($res);
		//debug($GLOBALS['i']->db->lastQuery);
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

}
