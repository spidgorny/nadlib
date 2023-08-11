<?php

class LDAPLogin
{

	/**
	 * @var string
	 */
	var $LDAP_HOST;

	/**
	 * @var string
	 */
	var $LDAP_BASEDN;

	private $_ldapconn;

	/**
	 * @var LDAPUser
	 */
	public $data;

	public $error = null;

	/**
	 * @var LDAPUser or a descendant
	 */
	public $userClass = LDAPUser::class;

	public function __construct($host, $base)
	{
		$this->LDAP_HOST = $host;
		$this->LDAP_BASEDN = $base;
	}

	private function _connectLdap()
	{
		if (!$this->_ldapconn) {
			$this->reconnect();
		}
	}

	function reconnect()
	{
		if ($this->_ldapconn) {
			ldap_unbind($this->_ldapconn);
		}
		$this->_ldapconn = ldap_connect($this->LDAP_HOST)
		or die("Couldn't connect to the LDAP server.");
		ldap_set_option($this->_ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->_ldapconn, LDAP_OPT_REFERRALS, 0);
	}

	/**
	 * What if the password contains special characters?
	 * @param $string
	 * @return string
	 */
	private function _sanitizeLdap($string)
	{
		return trim(preg_replace('/[^a-zA-Z0-9]+/', '', $string));
	}

	public function bind($loginDN, $password)
	{
		$this->_connectLdap();
		return ldap_bind($this->_ldapconn, $loginDN, $password);
	}

	/**
	 * @param $username
	 * @param $password
	 * @return bool|LDAPUser|void
	 * @throws LoginException
	 */
	public function authLdap($username, $password)
	{
		$this->_connectLdap();

		if (($username == null) || ($password == null)) {
			$this->error = "Fields cannot be blank.";
			return false;
		}

		if ($this->_ldapconn) {
			$filter = "(&(objectClass=user)(objectCategory=person)(cn=" . $this->_sanitizeLdap($username) . "))";
			//echo $filter;
			$attributes = ['dn', 'uid', 'fullname', 'givenname', 'firstname'];

//			debug($this->_ldapconn, $this->LDAP_BASEDN, $filter);
			$search = ldap_search($this->_ldapconn, $this->LDAP_BASEDN, $filter/*, $attributes*/);
			if ($search) {
				$info = ldap_get_entries($this->_ldapconn, $search);
				//echo getDebug($info);

				if ($info['count'] == 0) {
					$this->error = "User not found";
					return false;
				}

				for ($i = 0; $i < $info['count']; $i++) {
					//$this->reconnect();
					// Warning: ldap_bind(): Unable to bind to server: Invalid credentials
					$ldapbind = @ldap_bind($this->_ldapconn, $info[$i]['dn'], $this->_sanitizeLdap($password));

					if ($ldapbind) {
						$this->userClass->initLDAP($info[$i]);
						return $this->userClass;
					} else {
						$this->error = "LDAP login failed.";
						//echo getDebug($ldapbind);
						return false;
					}
				}
			} else {
				throw new LoginException(error_get_last());
			}
		}
		return false;
	}

	/**
	 * Substring searches fail on any attribute with a DN syntax
	 * http://support.novell.com/docs/Tids/Solutions/10092520.html
	 * @param $group
	 * @return array
	 */
	public function getUsersFrom($group)
	{
		//$query = '(&(objectCategory=user)(groupMembership=ou=Application_Development))';
		//$query = '(&(objectClass=inetOrgPerson)(groupMembership=cn=Prog_DevApp,ou=Application_Development,ou=FFM2,ou=NOE,o=NWW))';
		//$query = '(&(objectClass=inetOrgPerson)(groupMembership=*cn=Application_Development,ou=FFM2,ou=NOE,o=NWW))';
		$query = '(&(objectClass=inetOrgPerson))';
		//$query = '(cn=*)';
		//$query = '(|(name=memberof)(cn=memberof)(sn=memberof)(displayName=memberof)(givenName=memberof)(uid=memberof)(initials=memberof)(gecos=memberof)(ou=memberof)(dc=memberof)(o=memberof)(group=memberof)(dmdName=memberof)(sAMAccountName=memberof)(description=memberof)(labeledURI=memberof))';
		$this->_connectLdap();

		$search = ldap_search($this->_ldapconn, $group, $query);
		$info = ldap_get_entries($this->_ldapconn, $search);
		unset($info['count']);
		foreach ($info as &$user) {
			$user = new LDAPUser($user);
		}
		return $info;
	}

	/**
	 * @param $query
	 * @return LDAPUser[]
	 */
	public function query($query)
	{
		$this->_connectLdap();

		$search = ldap_search($this->_ldapconn, $this->LDAP_BASEDN, $query, [], null, 50);
		$info = ldap_get_entries($this->_ldapconn, $search);
		unset($info['count']);
		foreach ($info as &$user) {
			$user = new LDAPUser($user);
		}
		return $info;
	}

}
