<?php

abstract class UserBase extends OODBase {
	protected $table = 'user';
	protected $prefs = array();
	protected static $instances = array();

	/**
	 * $id is intentionally not = NULL in order to force using getInstance()
	 * protected will not work because OODBase::__construct is public
	 *
	 * @param unknown_type $id
	 */
	public function __construct($id) {
		parent::__construct($id);
	}

	function __destruct() {
		//debug($this->prefs);
		//debug($this->db);
		//debug($this->id);
		if ($this->db && $this->id) {
			$this->update(array('prefs' => serialize($this->prefs)));
		}
	}

	/**
	 * Will NOT md5 password inside as Client is UserBased.
	 *
	 * @param array $data
	 * @return unknown
	 */
	function insert(array $data) {
		$this->findInDB(array('email' => $data['email']));
		if ($this->id) {
			throw new Exception('Such e-mail is already used. <a href="?c=ForgotPassword">Forgot password?</a>');
		} else {
			//$data['password'] = md5($data['password']);
			return $this->insertNoUserCheck($data);
		}
	}

	function insertNoUserCheck(array $data) {
		$data['ctime'] = new AsIs('NOW()');
		$qb = Config::getInstance()->qb;
		$query = $qb->getInsertQuery($this->table, $data);
		//debug($query);
		$this->db->perform($query);
		unset($data['ctime']);
		$this->findInDB($data);
	}

	function setPref($key, $val) {
		$this->prefs[$key] = $val;
	}

	function getPref($key) {
		return $this->prefs[$key];
	}

	function getSetPref($key, $prio1 = NULL, $prio3 = NULL) {
		$prio2 = $this->getPref($key);
		if ($prio1 != NULL) {
			$val = $prio1;
		} else if ($prio2 != NULL) {
			$val = $prio2;
		} else {
			$val = $prio3;
		}
/*		debug(array(
			$prio1,
			$prio2,
			$prio3,
			$val,
		));
*/		$this->setPref($key, $val);
		return $val;
	}

	function isAuth() {
		return $this->id ? true : false;
	}

}
