<?php

class Request {
	protected $data = array();
	public $defaultController;

	/**
	 * @var URL
	 */
	public $url;

	static protected $instance;

	function __construct(array $array = NULL) {
		$this->data = !is_null($array) ? $array : $_REQUEST;
		$this->defaultController = class_exists('Config') ? Config::getInstance()->defaultController : '';
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

	static function getInstance() {
		return self::$instance = self::$instance ? self::$instance : new self();
	}

	static function getExistingInstance() {
		return self::$instance;
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

	/**
	 * Checks that trimmed value isset in the supplied array
	 * @param $name
	 * @param array $options
	 * @throws Exception
	 * @return string
	 */
	function getOneOf($name, array $options) {
		$value = $this->getTrim($name);
		if (!isset($options[$value])) {
			debug($value, $options);
			throw new Exception(__METHOD__.' is throwing an exception.');
		}
		return $value;
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

	/**
	 * Checks for keys, not values
	 *
	 * @param $name
	 * @param array $assoc
	 * @return null
	 */
	function getIntIn($name, array $assoc) {
		$id = $this->getIntOrNULL($name);
		if (!is_null($id) && !in_array($id, array_keys($assoc))) {
			$id = NULL;
		}
		return $id;
	}

	function getIntInException($name, array $assoc) {
		$id = $this->getIntOrNULL($name);
		if (!is_null($id) && !in_array($id, array_keys($assoc))) {
			debug($id, array_keys($assoc));
			throw new Exception($name.' is not part of allowed collection.');
		}
		return $id;
	}

	function getIntRequired($name) {
		$id = $this->getIntOrNULL($name);
		if (!$id) {
			throw new Exception($name.' parameter is required.');
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
	 * @param string $name
	 * @throws Exception
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

	function getTrimArray($name) {
		$list = $this->getArray($name);
		$list = array_map('trim', $list);
		return $list;
	}

	function getSubRequestByPath(array $name) {
		$current = $this;
		reset($name);
		do {
			$next = current($name);
			$current = $current->getSubRequest($next);
			//debug($name, $next, $current->getAll());
		} while (next($name));
		return $current;
	}

	function getArrayByPath(array $name) {
		$subRequest = $this->getSubRequestByPath($name);
		return $subRequest->getAll();
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
	 * @param string $name
	 * @param null $rel
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
	 * @param null $rel
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
		if ($this->isCLI()) {
			$controller = $_SERVER['argv'][1];
			$this->data += $this->parseParameters();
			//debug($this->data);
		} else {
			$controller = $this->getTrim('c');
			// to simplofy URL it first searches for the corresponding controller
			$ptr = &Config::getInstance()->config['autoload']['notFoundException'];
			$tmp = $ptr;
			$ptr = false;
			if ($controller && class_exists($controller.'Controller')) {
				$controller = $controller.'Controller';
			}
			$ptr = $tmp;
			//$controller = end(explode('/', $controller)); // in case it's with subfolder
			// ^ commented as subfolders need be used for BEmenu
			if (!$controller) {
				$levels = $this->getURLLevels();
				//debug($levels);
				$levels = array_reverse($levels);
				foreach ($levels as $class) {
					//debug($class, class_exists($class.'Controller'), class_exists($class));
					// to simplofy URL it first searches for the corresponding controller
					if ($class && class_exists($class.'Controller')) {	// this is untested
						$last = $class.'Controller';
						break;
					}
					if (class_exists($class)) {
						$last = $class;
						break;
					}
				}
				$controller = $last;
			}
		}   // cli
        if (!$controller) {
            $controller = $this->defaultController;
			//debug('Using default controller', $controller);
        }
		nodebug(array(
			'result' => $controller,
			'c' => $this->getTrim('c'),
			'levels' => $this->getURLLevels(),
			'last' => $last,
			'default' => $this->defaultController,
			'data' => $this->data));
		return $controller;
	}

	/**
	 * Will require modifications when realurl is in place
	 *
	 * @throws Exception
	 * @return object
	 */
	function getController() {
		$c = $this->getControllerString();
		if (!is_object($c)) {
			if (class_exists($c)) {
				$ret = new $c();
			} else if ($c) {
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
		if (class_exists('Index') && Index::getInstance() && method_exists(Index::getInstance(), '__destruct')) {
			Index::getInstance()->__destruct();
		}
		if (!headers_sent()
//			|| DEVELOPMENT
		) {
			header('Location: '.$controller);
		} else {
			echo 'Redirecting to <a href="'.$controller.'">'.$controller.'</a>
			<script>
				document.location = "'.$controller.'";
			</script>';
		}
		exit();
	}

	/**
	 * Returns the full URL to the document root of the current site
	 * @return string
	 */
	static function getLocation() {
		if (class_exists('Config')) {
			$c = Config::getInstance();
			$docRoot = $c->documentRoot;
		} else {
			$docRoot = dirname($_SERVER['PHP_SELF']);
		}
		if (strlen($docRoot) == 1) {
			$docRoot = '/';
		} else {
			$docRoot .= '/';
		}
		$url = Request::getRequestType().'://'.(
			$_SERVER['HTTP_X_FORWARDED_HOST'] ?: $_SERVER['HTTP_HOST']
		).$docRoot;
		//$GLOBALS['i']->content .= $url;
		//debug($url);
		return $url;
	}

	/**
	 * Returns the current page URL as is. Similar to $_SERVER['REQUEST_URI'].
	 *
	 * @return URL
	 */
	function getURL() {
		return $this->url;
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
		$request_type =
			(((isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1'))) ||
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

	/**
	 * @return array
	 */
	function getURLLevels() {
		$path = $this->url->getPath();
		if (strlen($path) > 1) {	// "/"
			$path = trimExplode('/', $path);
			//debug($this->url->getPath(), $path);
		} else {
			$path = array();
		}
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
			$mod_rewrite =  getenv('HTTP_MOD_REWRITE')=='On' ? true : false ;
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

	function getNameless($index, $alternative = NULL) {
		$levels = $this->getURLLevels();

		/* From DCI */
		// this spoils ORS menu!
/*		$controller = $this->getControllerString();
		foreach ($levels as $l => $name) {
			unset($levels[$l]);
			if ($name == $controller) {
				break;
			}
		}
		$levels = array_values($levels);	// reindex
		/* } */

		return $levels[$index] ? $levels[$index] : $this->getTrim($alternative);
	}

	static function isCLI() {
		return isset($_SERVER['argc']);
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

	function getFilePathName($name) {
		$filename = $this->getTrim($name);
		//debug(getcwd(), $filename, realpath($filename));
		$filename = realpath($filename);
		return $filename;
	}

	function getFilename($name) {
		//filter_var($this->getTrim($name), ???)
		$filename = $this->getTrim($name);
		$filename = basename($filename);	// optionally use realpath()
		return $filename;
	}

	/**
	 * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
	 * @see http://www.php.net/manual/en/function.getopt.php#83414
	 *
	 * Supports:
	 * -e
	 * -e <value>
	 * --long-param
	 * --long-param=<value>
	 * --long-param <value>
	 * <value>
	 *
	 * @param array $noopt List of parameters without values
	 * @return array
	 */
	function parseParameters($noopt = array()) {
		$result = array();
		$params = $GLOBALS['argv'];
		// could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
		reset($params);
		while (list($tmp, $p) = each($params)) {
			if ($p{0} == '-') {
				$pname = substr($p, 1);
				$value = true;
				if ($pname{0} == '-') {
					// long-opt (--<param>)
					$pname = substr($pname, 1);
					if (strpos($p, '=') !== false) {
						// value specified inline (--<param>=<value>)
						list($pname, $value) = explode('=', substr($p, 2), 2);
					}
				}
				// check if next parameter is a descriptor or a value
				$nextparm = current($params);
				if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
				$result[$pname] = $value;
			} else {
				// param doesn't belong to any option
				$result[] = $p;
			}
		}
		return $result;
	}

	/**
	 * http://stackoverflow.com/a/6127748/417153
	 * @return bool
	 */
	function isRefresh() {
		return isset($_SERVER['HTTP_CACHE_CONTROL']) &&
			$_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
	}

}
