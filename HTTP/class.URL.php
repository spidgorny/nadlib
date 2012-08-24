<?php

class URL {
	public $url;
	public $components = array();
	public $params;
	public $documentRoot = '';

	function __construct($url = NULL, array $params = array()) {
		if (!$url) {
			$http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
			$url = $http . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		}
		$this->components = parse_url($url);
		parse_str($this->components['query'], $this->params);
		if ($params) {
			$this->setParams($params);
		}
	}

	function setParam($param, $value) {
		$this->params[$param] = $value;
		$this->components['query'] = $this->buildQuery();
	}

	function getParam($param) {
		return $this->params[$param];
	}

	function setParams(array $params = array()) {
		$this->params = $params;
		$this->components['query'] = $this->buildQuery();
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
	 * @return unknown
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
		return $url;
	}

	function getRequest() {
		$r = new Request($this->params);
		$r->url = $this;
		return $r;
	}

	/**
	 * @static
	 * @return URL
	 */
	static function getCurrent() {
		return new URL($_SERVER['REQUEST_URI']);
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

}
