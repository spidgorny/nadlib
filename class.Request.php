<?php

class Request {
	protected $data = array();

	function __construct(array $array = NULL) {
		$this->data = !is_null($array) ? $array : $_REQUEST;
		if (ini_get('magic_quotes_gpc')) {
			$this->data = $this->deQuote($this->data);
		}
	}

	function deQuote(array $request) {
		foreach ($request as &$el) {
			if (is_array($el)) {
				$el = $this->deQuote($el);
			} else {
				$el = stripslashes($el);
			}
		}
		return $request;
	}

	function set($var, $val) {
		$this->data[$var] = $val;
	}

	function un_set($name) {
		unset($this->data[$name]);
	}

	function string($name) {
		return (string)$this->data[$name];
	}

	function getString($name) {
		return strval($this->data[$name]);
	}

	function getTrim($name) {
		return trim($this->getString($name));
	}

	function int($name) {

		return intval($this->data[$name]);
	}

	function getInt($name) {
		return $this->int($name);
	}

	function getFloat($name) {
		return floatval($this->data[$name]);
	}

	function bool($name) {
		return $this->data[$name] ? TRUE : FALSE;
	}

	function getBool($name) {
		return $this->bool($name);
	}

	/**
	 * Will return timestamp
	 * Converts string date compatible with strtotime() into timestamp (integer)
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

	/**
	 * Makes sure it's an integer
	 * @param string $name
	 * @return int
	 */
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

	function getFile($name, $prefix = NULL, $prefix2 = NULL) {
		$files = $prefix ? $_FILES[$prefix] : $_FILES;
		//debug($files);
		if ($prefix2 && $files) {
			foreach ($files as &$row) {
				$row = $row[$prefix2];
			}
		}
		if ($files) {
			foreach ($files as &$row) {
				$row = $row[$name];
			}
		}
		//debug($files);
		return $files;
	}

	function getSubRequest($name) {
		return new Request($this->getArray($name));
	}

	function getCoalesce($a, $b) {
		$a = $this->getTrim($a);
		return $a ? $a : $b;
	}

	function getControllerString() {
		//debug(Config::getInstance()->defaultController); exit();
		$c = $this->getTrim('c');
		return $c ? $c : Config::getInstance()->defaultController;
	}

	/**
	 * Will require modifications when realurl is in place
	 *
	 * @return object
	 */
	function getController() {
		$c = $this->getControllerString();
		if (!$c) {
			$ret = $GLOBALS['i']->controller; // default
		}
		if (!is_object($ret)) {
			if (class_exists($c)) {
				$ret = new $c();
			} else {
				throw new Exception('Class '.$c.' can\'t be found.');
			}
		}
		return $ret;
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

	function getLocation() {
		$docRoot = dirname($_SERVER['PHP_SELF']);
		if (strlen($docRoot) == 1) {
			$docRoot = '/';
		} else {
			$docRoot .= '/';
		}
		$url = Request::getRequestType().'://'.$_SERVER['HTTP_HOST'].$docRoot;
		//$GLOBALS['i']->content .= $url;
		return $url;
	}

	function getInstance() {
		static $instance = NULL;
		if (!$instance) $instance = new self();
		return $instance;
	}

	function isAjax() {
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
		return $this->getBool('ajax') || (strtolower($headers['X-Requested-With']) == strtolower('XMLHttpRequest'));
	}

	function getHeader($name) {
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
		return $headers[$name];
	}

	function getJson($name) {
		return json_decode($this->getTrim($name), true);
	}

	function isSubmit() {
		return $this->isPOST() || $this->getBool('submit');
	}

	function getDateFromYMD($name) {
		$date = $this->getInt($name);
		if ($date) {
			$y = substr($date, 0, 4);
			$m = substr($date, 4, 2);
			$d = substr($date, 6, 2);
			$date = strtotime("$y-$m-$d");
			$date = new Date($date);
		} else {
			$date = NULL;
		}
		return $date;
	}

	/**
	 * http://www.zen-cart.com/forum/showthread.php?t=164174
	 */
	static function getRequestType() {
		$request_type = (((isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1'))) ||
						 (isset($_SERVER['HTTP_X_FORWARDED_BY']) && strpos(strtoupper($_SERVER['HTTP_X_FORWARDED_BY']), 'SSL') !== false) ||
						 (isset($_SERVER['HTTP_X_FORWARDED_HOST']) && (strpos(strtoupper($_SERVER['HTTP_X_FORWARDED_HOST']), 'SSL') !== false || strpos(strtoupper($_SERVER['HTTP_X_FORWARDED_HOST']), str_replace('https://', '', HTTPS_SERVER)) !== false)) ||
						 (isset($_SERVER['SCRIPT_URI']) && strtolower(substr($_SERVER['SCRIPT_URI'], 0, 6)) == 'https:') ||
						 (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && ($_SERVER['HTTP_X_FORWARDED_SSL'] == '1' || strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on')) ||
						 (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'ssl' || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')) ||
						 (isset($_SERVER['HTTP_SSLSESSIONID']) && $_SERVER['HTTP_SSLSESSIONID'] != '') ||
						 (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')) ? 'https' : 'http';
		return $request_type;
	}

	function isPOST() {
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	function getAll() {
		return $this->data;
	}

	function getMethod() {
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * @return URL
	 */
	function getURL() {
		$url = new URL($_SERVER['SCRIPT_URL'] ? $_SERVER['SCRIPT_URL'] : $_SERVER['REQUEST_URI']);
		$url->setDocumentRoot(Config::getInstance()->documentRoot);
		return $url;
	}

	function getURLLevel($level) {
		$url = $this->getURL();
		$path = $url->getPath();
		$path = trimExplode('/', $path);
		//debug($path);
		return $path[$level];
	}

}
