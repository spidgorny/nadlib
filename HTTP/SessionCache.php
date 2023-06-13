<?php

class SessionCache {

	/**
	 * @var Session
	 */
	protected $session;

	function __construct($class)
	{
		$this->session = new Session($class);
	}

	static function wrap($class, $method, $getter)
	{
		$sc = new SessionCache($class);
		$val = $sc->session->get($method);
		if (!$val) {
			$val = $getter();
			$sc->session->save($method, $val);
		}
		return $val;
	}

}
