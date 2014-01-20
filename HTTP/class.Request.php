<?php

class Request {
	protected $data = array();
	public $defaultController;

	/**
	 * @var URL
	 */
	public $url;

	function __construct(array $array = NULL) {
		$this->data = !is_null($array) ? $array : $_REQUEST;
		$this->defaultController = Config::getInstance()->defaultController;
		if (ini_get('magic_quotes_gpc')) {
			$this->data = $this->deQuote($this->data);
		}

		$this->url = new URL(isset($_SERVER['SCRIPT_URL']) ? $_SERVER['SCRIPT_URL'] : $_SERVER['REQUEST_URI']);
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
		return $this->getString($name);
	}

	function getString($name) {
		return isset($this->data[$name]) ? strval($this->data[$name]) : '';
	}

	function getTrim($name) {
		return trim($this->getString($name));
	}

	function int($name) {
		return isset($this->data[$name]) ? intval($this->data[$name]) : 0;
	}

	function getInt($name) {
		return $this->int($name);
	}

	function getIntOrNULL($name) {
		return $this->is_set($name) ? $this->int($name) : NULL;
	}

	function getIntIn($name, array $assoc) {
		$id = $this->getIntOrNULL($name);
		if (!is_null($id) && !in_array($id, array_keys($assoc))) {
			debug($id, array_keys($assoc));
			throw new Exception($name.' is not part of allowed collection.');
		}
		return $id;
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
		return isset($this->data[$name]) ? (array)($this->data[$name]) : array();
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
	function getTime($name, $rel = NULL) {
		if ($this->is_set($name)) {
			return new Time($this->getTrim($name), $rel);
		}
	}

	/**
	 * Will return Date object
	 *
	 * @param string $name
	 * @return Date
	 */
	function getDate($name, $rel = NULL) {
		if ($this->is_set($name)) {
			return new Date($this->getTrim($name), $rel);
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
		$last = end($this->getURLLevels());
		$controller = $this->getCoalesce(
			'c',
			$last
				? $last
				: $this->defaultController
		);
		//debug($controller, $this->getTrim('c'), $this->getURLLevels(), $last, $this->defaultController, $this->data);
		return $controller;
	}

	/**
	 * Will require modifications when realurl is in place
	 *
	 * @return object
	 */
	function getController() {
		$c = $this->getControllerString();
		if (!is_object($c)) {
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
		$return = $rr->getControllerString();
		//debug($_SERVER['HTTP_REFERER'], $url, $rr, $return);
		return $return ? $return : $this->defaultController;
	}

	function redirect($controller) {
		Index::getInstance()->__destruct();
		if (!headers_sent()
//			|| DEVELOPMENT
		) {
			header('Location: '.$controller);
		}
		echo '<a href="'.$controller.'">'.$controller.'</a>';
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
		//debug($url);
		return $url;
	}

	public static function getInstance() {
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
		return $this->isPOST() || $this->getBool('submit') || $this->getBool('btnSubmit') ;
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
	 * Will overwrite.
	 * @param array $plus
	 */
	function setArray(array $plus) {
		foreach ($plus as $key => $val) {
			$this->data[$key] = $val;
		}
	}
	
	function getURLLevel($level) {
		$path = $this->getURLLevels();
		return isset($path[$level]) ? $path[$level] : NULL;
	}

	function getURLLevels() {
		$path = $this->url->getPath();
		$path = trimExplode('/', $path);
		//debug($path);
		return $path;
	}

	/**
	 * Overwriting - no
	 * @param array $plus
	 */
	function append(array $plus) {
		$this->data += $plus;
	}

	/**
	 * Overwriting - yes
	 * @param array $plus
	 */
	function overwrite(array $plus) {
		foreach ($plus as $key => $val) {
			$this->data[$key] = $val;
		}
	}

	function apacheModuleRewrite() {
		if (function_exists('apache_get_modules')) {
			$modules = apache_get_modules();
			//debug($modules);
			$mod_rewrite = in_array('mod_rewrite', $modules);
		} else {
			$mod_rewrite =  getenv('HTTP_MOD_REWRITE')=='On' ? true : false;
		}
		return $mod_rewrite;
	}

	static function removeCookiesFromRequest() {
		if (false !== strpos(ini_get('variables_order'), 'C')) {
			//debug($_COOKIE, ini_get('variables_order'));
			foreach ($_COOKIE as $key => $_) {
				if (isset($_GET[$key])) {
					$_REQUEST[$key] = $_GET[$key];
				} else if (isset($_POST[$key])) {
					$_REQUEST[$key] = $_POST[$key];
				}

				if (isset($_REQUEST[$key])) {
					unset($_REQUEST[$key]);
				}
			}
		}
	}

	static function isCLI() {
		//return isset($_SERVER['argc']);
		return php_sapi_name() == 'cli';
	}

	/**
	 * http://stackoverflow.com/questions/190759/can-php-detect-if-its-run-from-a-cron-job-or-from-the-command-line
	 * @return bool
	 */
	function isCron() {
		return !isset($_SERVER['TERM']);
	}

	function debug() {
		return get_object_vars($this);
	}

}
