<?php

class URL {
	protected $url;
	protected $components = array();
	protected $params;

	function __construct($url) {
		$this->components = parse_url($url);
		parse_str($this->components['query'], $this->params);
		//debug($this);
	}

	function setParam($param, $value) {
		$this->params[$param] = $value;
		$this->components['query'] = $this->buildQuery();
	}

	function buildQuery() {
		return str_replace('#', '%23', http_build_query($this->params));
	}

	/**
	 * http://de2.php.net/manual/en/function.parse-url.php#85963
	 *
	 * @return unknown
	 */
	function buildURL() {
		$parsed = $this->components;
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

	    $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
	    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

	    return $uri;
	}

	function __toString() {
		return $this->buildURL();
	}

	function getRequest() {
		$r = new Request($this->params);
		return $r;
	}

	/**
	 * @static
	 * @return URL
	 */
	static function getCurrent() {
		return new URL($_SERVER['REQUEST_URI']);
	}

}
