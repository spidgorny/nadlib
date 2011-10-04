<?php

class Request {
	protected $data;

	function __construct(array $request = NULL) {
		$this->data = !is_null($request) ? $request : $_REQUEST;
	}

	function un_set($name) {
		unset($this->data[$name]);
	}

	function getString($name) {
		return strval($this->data[$name]);
	}

	function getTrim($name) {
		return trim($this->getString($name));
	}

	function getInt($name) {
		return intval($this->data[$name]);
	}

	function getFloat($name) {
		return floatval($this->data[$name]);
	}

	function getBool($name) {
		return $this->data[$name] ? TRUE : FALSE;
	}

	/**
	 * Will return timestamp
	 *
	 * @param unknown_type $name
	 * @return int
	 */
	function getTimestampFromString($name) {
		$string = $this->getTrim($name);
		$val = strtotime($string);
		if ($val == -1) {
			throw new Exception("Invalid input for date ($name): $string");
		}
		return $val;
	}


	function getArray($name) {
		return (array)($this->data[$name]);
	}

	function getTimestamp($name) {
		return $this->getInt($name);
	}

	function is_set($name) {
		return isset($this->data[$name]);
	}

	/**
	 * Will return Time object
	 *
	 * @param unknown_type $name
	 * @return Time
	 */
	function getTime($name) {
		if ($this->is_set($name)) {
			return new Time($this->getTrim($name));
		}
	}

	/**
	 * Will return Date object
	 *
	 * @param unknown_type $name
	 * @return Date
	 */
	function getDate($name) {
		if ($this->is_set($name)) {
			return new Date($this->getTrim($name));
		}
	}

	/**
	 * Will require modifications when realurl is in place
	 *
	 * @return unknown
	 */
	function getController() {
		$c = $this->getTrim('c');
		return $c ? $c : 'Home';
	}

	function setNewController($class) {
		$this->data['c'] = $class;
	}

	function getRefererController() {
		$url = new URL($_SERVER['HTTP_REFERER']);
		$rr = $url->getRequest();
		//debug($rr);
		$return = $rr->getTrim('c');
		return $return ? $return : 'Overview';
	}

	function redirect($controller) {
		$GLOBALS['i']->user->destruct();
		if (FALSE && DEVELOPMENT) {
			echo '<a href="'.$controller.'">'.$controller.'</a>';
		} else {
			header('Location: '.$controller);
		}
		unset($GLOBALS['i']->user);
		exit();
	}

	function isAjax() {
		$headers = apache_request_headers();
		return strtolower($headers['X-Requested-With']) == strtolower('XMLHttpRequest');
	}

	function getJson($name) {
		return json_decode($this->getTrim($name), true);
	}

	function isSubmit() {
		return $this->isPOST() || $this->getBool('submit');
}

	function isPOST() {
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	function getAll() {
		return $this->data;
	}

}
