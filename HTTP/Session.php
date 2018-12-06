<?php

class Session implements SessionInterface
{

	public $prefix;

	public function __construct($prefix = null)
	{
		$this->prefix = $prefix;
		if (!self::isActive()) {
			$this->start();
		}
	}

	public function start()
	{
		if (!Request::isPHPUnit() && !Request::isCLI()) {
			// not using @ to see when session error happen
			session_start();
		}
	}

	public static function isActive()
	{
		//debug(session_id(), !!session_id(), session_status(), $_SESSION['FloatTime']);
		if (function_exists('session_status')) {
			// somehow PHP_SESSION_NONE is the status when $_SESSION var exists
			// PHP_SESSION_NONE removed as it's a major problem
			return in_array(session_status(), [PHP_SESSION_ACTIVE]);
		} else {
			return !!session_id() && isset($_SESSION);
		}
	}

	public function get($key)
	{
		if ($this->prefix) {
			return ifsetor($_SESSION[$this->prefix][$key]);
		} else {
			return ifsetor($_SESSION[$key]);
		}
	}

	public function set($key, $val)
	{
		$this->save($key, $val);
	}

	public function save($key, $val)
	{
		if ($this->prefix) {
			$_SESSION[$this->prefix][$key] = $val;
		} else {
			$_SESSION[$key] = $val;
		}
	}

	public function __get($name)
	{
		return $this->get($name);
	}

	public function __set($name, $value)
	{
		$this->save($name, $value);
	}

	public function clearAll()
	{
		unset($_SESSION[$this->prefix]);
	}

	public function has($key)
	{
		return isset($_SESSION[$this->prefix][$key]);
	}

	public function append($key, $val)
	{
		if ($this->prefix) {
			$_SESSION[$this->prefix][$key][] = $val;
		} else {
			$_SESSION[$key][] = $val;
		}
	}

	public function getAll()
	{
		return ifsetor($_SESSION[$this->prefix], []);
	}

	public function delete($string)
	{
		if ($this->prefix) {
			unset($_SESSION[$this->prefix][$string]);
		} else {
			unset($_SESSION[$string]);
		}
	}

}
