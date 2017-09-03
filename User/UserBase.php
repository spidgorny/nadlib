<?php

abstract class UserBase extends OODBase implements UserModelInterface {

	public $table = 'user';

	var $idField = 'id';

	protected $prefs = array();

	public static $instances = array();

	/**
	 * $id is intentionally not = NULL in order to force using getInstance()
	 * protected will not work because OODBase::__construct is public
	 *
	 * @param int|array $id
	 */
	public function __construct($id = NULL) {
		parent::__construct($id);
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
		if (isset($this->db) && $this->db->isConnected() && $this->id && $this->prefs) {

			// this is just an example - move it to the app class
			//$this->update(array('prefs' => serialize($this->prefs)));
		}
	}

	/**
	 *
	 * @param string $login
	 * @param string $password - plain text password (no, it's md5'ed already)
	 * @return boolean
	 */
	function checkPassword($login, $password) {
		$query = $this->db->getSelectQuery($this->table, array($this->idField => $login));
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
	 * Will NOT md5 password inside as Client is UserBased.
	 *
	 * @param array $data
	 * @throws Exception
	 * @return void
	 */
	function insertUniqEmail(array $data) {
		//debug($data);
		if ($data['email']) {
			$this->findInDB(array('email' => $data['email']));
			if ($this->id) {
				throw new Exception('Such e-mail is already used. <a href="?c=ForgotPassword">Forgot password?</a>');
			} else {
				//$data['password'] = md5($data['password']);
				$this->insertNoUserCheck($data);
			}
		} else {
			$index = Index::getInstance();
			debug(__METHOD__);
			$index->error('No email provided.');
		}
	}

	function insertNoUserCheck(array $data) {
		$data['ctime'] = new SQLDateTime();
		$data['email'] = ifsetor($data['email']) ? $data['email'] : NULL;		/// will set '' to NULL IMPORTANT!
		Index::getInstance()->log(get_called_class().'::'.__FUNCTION__, $data);
		$query = $this->db->getInsertQuery($this->table, $data);
		//debug($query);
		$this->db->perform($query);
		unset($data['ctime']);
		$this->findInDB($data);
	}

	function setPref($key, $val) {
		$this->prefs[$key] = $val;
	}

	function getPref($key) {
		return ifsetor($this->prefs[$key]);
	}

	function getAllPrefs() {
		return $this->prefs;
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
		//debug($this);
		return !!$this->id;
	}

	function getHTML() {
		$content = '<div class="user">
			<img src="'.$this->getGravatarURL(24).'" class="gravatar24">'.
			$this->getName().
			'</div>';
		return $content;
	}

	function getGravatarURL($gravatarSize = 50) {
		return '//www.gravatar.com/avatar/'.md5(
			strtolower(
				trim(
					ifsetor($this->data['email'])
				)
			)
		).'?s='.intval($gravatarSize);
	}

}
