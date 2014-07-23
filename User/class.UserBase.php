<?php

abstract class UserBase extends OODBase {
	public $table = 'user';
	protected $prefs = array();

	/**
	 * $id is intentionally not = NULL in order to force using getInstance()
	 * protected will not work because OODBase::__construct is public
	 *
	 * @param int $id
	 */
	public function __construct($id = NULL) {
		parent::__construct($id);
	}

	/**
	 * @param null $id
	 * @return User
	 */
	public static function getInstance($id = NULL) {
		if (!($obj = self::$instances[$id])) {
			$static = 'User'; //get_class($this);
			$obj = new $static($id);
			$id = $obj->id;
			if (!self::$instances[$id]) {
				self::$instances[$id] = $obj;
			} else {
				$obj = self::$instances[$id];
			}
		}
		return $obj;
	}

	public static function unsetInstance($id) {
		unset(self::$instances[$id]);
		//debug(self::$instances);
	}

	/**
	 * Some code to detect that the user is already logged-in by using cookies or session.
	 * Commented as it only belongs to SessionUser or CookieUser
	 */
	//abstract function autologin();

	function __destruct() {
		//debug($this->prefs);
		//debug($this->db);
		//debug($this->id);
		if ($this->db && $this->id) {
			$this->update(array('prefs' => serialize($this->prefs)));
		}
	}

	/**
	 *
	 * @param unknown_type $login
	 * @param unknown_type $password - plain text password (no, it's md5'ed already)
	 * @return unknown
	 */
	function checkPassword($login, $password) {
		$qb = Config::getInstance()->qb;
		$query = $qb->getSelectQuery($this->table, array('email' => $login));
		//debug($query);
		$row = $this->db->fetchAssoc($query);
		//debug(array($login, $password, $row['password']));
		$ok = $row['password'] && $row['password'] == $password;
		if ($ok) {
			$this->init($row);
		}
		return $ok;
	}

	/**
	 * Will md5 password inside.
	 *
	 * @param array $data
	 * @return unknown
	 */
	function insert(array $data) {
		$this->findInDB(array('email' => $data['email']));
		if ($this->id) {
			throw new Exception("Such e-mail is already used. <a href=\"?c=ForgotPassword\">Forgot password?</a>");
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

	function getHTML() {
		$content = '<div class="user">
			<img src="'.$this->getGravatarURL(24).'" class="gravatar24">'.
			$this->getName().
			'</div>';
		return $content;
	}

	function getGravatarURL($gravatarSize = 50) {
		return 'http://www.gravatar.com/avatar/'.md5(strtolower(trim($this->data['email']))).'?s='.intval($gravatarSize);
	}

}
