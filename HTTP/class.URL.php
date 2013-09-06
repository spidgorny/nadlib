<?php

class URL {
	public $url;

	/**
	 * scheme, user, pass, host, port, path, query, fragment
	 *
	 * @var array
	 */
	public $components = array();

	public $params = array();

	public $documentRoot = '';

	/**
	 * @param null $url - if not specified then the current page URL is reconstructed
	 * @param array $params
	 */
	function __construct($url = NULL, array $params = array()) {
		if (!$url) {
			$http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
			$url = $http . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		}
		$this->components = @parse_url($url);
		if (!$this->components) {	//  parse_url(/pizzavanti-gmbh/id:3/10.09.2012@10:30/488583b0e1f3d90d48906281f8e49253.html) [function.parse-url]: Unable to parse URL
			$request = Request::getExistingInstance();
			if ($request) {
				//debug(substr($request->getLocation(), 0, -1).$url);
				$this->components = parse_url(substr($request->getLocation(), 0, -1).$url);
			}
		}
		if (isset($this->components['query'])) {
			parse_str($this->components['query'], $this->params);
		}
		if ($params) {
			$this->addParams($params);	// setParams was deleting all filters from the URL
		}
		if (class_exists('Config')) {
			$this->setDocumentRoot(Config::getInstance()->documentRoot);
		}
	}

	static function make(array $params = array()) {
		$url = new self();
		$url->setParams($params);
		return $url;
	}

	function setParam($param, $value) {
		$this->params[$param] = $value;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	function unsetParam($param) {
		unset($this->params[$param]);
	}

	function getParam($param) {
		return $this->params[$param];
	}

	/**
	 * Replaces parameters completely (with empty array?)
	 * @param array $params
	 * @return $this
	 */
	function setParams(array $params = array()) {
		$this->params = $params;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	/**
	 * New params have priority
	 * @param array $params
	 * @return $this
	 */
	function addParams(array $params = array()) {
		$this->params = $params + $this->params;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	function forceParams(array $params = array()) {
		$this->params = array_merge($this->params, $params);	// keep default order but overwrite
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	function clearParams() {
		$this->setParams(array());
		return $this;
	}

	function appendParams(array $params) {
		$this->params += $params;
		$this->components['query'] = $this->buildQuery();
	}

	function getPath() {
		$path = $this->components['path'];
		$path = str_replace($this->documentRoot, '', $path);
		//debug($this->components['path'], $this->documentRoot, $path);
		return $path;
	}

	function setPath($path) {
		$this->components['path'] = $path;
	}

	/**
	 * Defines the filename in the URL
	 * @param $name
	 */
	function setBasename($name) {
		$this->components['path'] .= $name;
	}

	function setDocumentRoot($root) {
		$this->documentRoot = $root;
		//debug($this);
	}

	function setFragment($name) {
		$this->components['fragment'] = $name;
	}

	function buildQuery() {
		return str_replace('#', '%23', http_build_query($this->params));
	}

	/**
	 * http://de2.php.net/manual/en/function.parse-url.php#85963
	 *
	 * @param null $parsed
	 * @return string
	 */
	function buildURL($parsed = NULL) {
		if (!$parsed) {
			$parsed = $this->components;
		}
	    if (!is_array($parsed)) {
	        return false;
	    }

	    $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
	    $uri .= isset($parsed['user']) ? $parsed['user'].(isset($parsed['pass']) ? ':'.$parsed['pass'] : '').'@' : '';
	    $uri .= isset($parsed['host']) ? $parsed['host'] : '';
	    $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';

	    if (isset($parsed['path'])) {
	        $uri .= (substr($parsed['path'], 0, 1) == '/') ?
	            $parsed['path'] : ((!empty($uri) ? '/' : '' ) . $parsed['path']);
	    }

	    $uri .= /*isset*/($parsed['query']) ? '?'.$parsed['query'] : '';
	    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

	    return $uri;
	}

	function __toString() {
		$url = $this->buildURL();
		//debug($this->components, $url);
		return $url.'';
	}

	function getRequest() {
		$r = new Request($this->params ? $this->params : array());
		$r->url = $this;
		return $r;
	}

	/**
	 * @static
	 * @return URL
	 */
	static function getCurrent() {
		return new URL();
	}

	function GET() {
		return file_get_contents($this->buildURL());
	}

	function POST($login = NULL, $password = NULL) {
		if ($login) {
			$auth = "Authorization: Basic ".base64_encode($login.':'.$password) . PHP_EOL;
		}
		$stream = array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL . $auth,
				'content' => $this->components['query'],
			),
		);
		$context = stream_context_create($stream);

		$noQuery = $this->components;
		unset($noQuery['query']);
		$url = $this->buildURL($noQuery);
		return file_get_contents($url, false, $context);
	}

	/*
$process = curl_init($url);
curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
curl_setopt($process, CURLOPT_HEADER, 1);
curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
curl_setopt($process, CURLOPT_ENCODING , $this->compression);
curl_setopt($process, CURLOPT_TIMEOUT, 30);
if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
curl_setopt($process, CURLOPT_POSTFIELDS, $data);
curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($process, CURLOPT_POST, 1);
$return = curl_exec($process);
curl_close($process);
return $return; */

	function exists() {
		$AgetHeaders = @get_headers($this->buildURL());
		return preg_match("|200|", $AgetHeaders[0]);
	}

}
