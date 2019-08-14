<?php

class Preferences
{
	/**
	 * Enter description here...
	 *
	 * @var User
	 */
	protected $user;

	public $prefs = array();    // for debug

	function __construct(User $user)
	{
		$this->user = $user;
		$this->prefs = unserialize($this->user->data['prefs']);
	}

	function set($key, $val)
	{
		$this->prefs[$key] = $val;
	}

	function get($key)
	{
		//debug(__METHOD__, $key);
		return $this->prefs[$key];
	}

	function getSetPref($key, $prio1 = NULL, $prio3 = NULL)
	{
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
		*/
		$this->setPref($key, $val);
		return $val;
	}

	function serialize()
	{
		return serialize($this->prefs);
	}

	function getData()
	{
		return $this->prefs;
	}

}
