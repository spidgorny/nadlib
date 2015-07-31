<?php

class URL {

	/**
	 * @var string
	 */
	public $url;

	/**
	 * scheme, user, pass, host, port, path, query, fragment
	 *
	 * @var array
	 */
	public $components = array();

	/**
	 * $this->components['query'] decomposed into an array
	 * @var array
	 */
	public $params = array();

	/**
	 * @var string
	 */
	public $documentRoot = '';

	/**
	 * = $this->components['path']
	 * @var Path
	 */
	protected $path;

	/**
	 * @param null $url - if not specified then the current page URL is reconstructed
	 * @param array $params
	 */
	function __construct($url = NULL, array $params = array()) {
		if ($url instanceof URL) {
			//return $url;	// doesn't work
		}
		if (!isset($url)) { // empty string should not default to localhost
			$http = Request::getRequestType();
			//debug($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'], $_SERVER);
			$host = ifsetor($_SERVER['HTTP_X_FORWARDED_HOST'], ifsetor($_SERVER['HTTP_HOST']));
			if ($host) {
				$url = $http . '://' . $host . $_SERVER['REQUEST_URI'];
			} else {
				$url = $http . '://localhost/';
			}
			$this->parseURL($url);
		} else {
			$this->parseURL($url);
		}
		if ($params) {
			$this->addParams($params);	// setParams was deleting all filters from the URL
		}
		if (class_exists('Config')) {
			$this->setDocumentRoot(Config::getInstance()->documentRoot);
		}
	}

	function parseURL($url) {
		$this->components = @parse_url($url);
		//pre_print_r($this->components);
		if (!$this->components) {	//  parse_url(/pizzavanti-gmbh/id:3/10.09.2012@10:30/488583b0e1f3d90d48906281f8e49253.html) [function.parse-url]: Unable to parse URL
			$request = Request::getExistingInstance();
			if ($request) {
				//debug(substr($request->getLocation(), 0, -1).$url);
				$callStack = debug_backtrace();
				foreach ($callStack as &$call) {
					$call = $call['class'] . $call['type'] . $call['function'];
				}
				if ($_COOKIE['d']) {
					print_r($callStack);
					ob_end_flush();
				}
				// prevent infinite loop
				if (!in_array('Request::getLocation', $callStack) &&
					!in_array('Request->getLocation', $callStack)
				) {
					$this->components = parse_url(substr($request->getLocation(), 0, -1) . $url);
				} else {
					debug($this->components);
				}
			}
		}
		//debug($url, $request ? 'Request::getExistingInstance' : '');
		if (isset($this->components['path'])) {
			$this->path = new Path($this->components['path']);
			$this->components['path'] = $this->path;
		}
		if (isset($this->components['query'])) {
			parse_str($this->components['query'], $this->params);
		}
	}

	static function make(array $params = array()) {
		$url = new self();
		$url->setParams($params);
		return $url;
	}

	/**
	 * @param $param
	 * @param $value
	 * @return static
	 */
	public function setParam($param, $value) {
		$this->params[$param] = $value;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	function unsetParam($param) {
		unset($this->params[$param]);
	}

	function getParam($param) {
		return ifsetor($this->params[$param]);
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

	/**
	 * @return Path
	 */
	function getPath() {
		$path = $this->path;
		if ($this->documentRoot != '/') {
			//$path = str_replace($this->documentRoot, '', $path);	// WHY???
			$path = new Path($path);
		}
		//debug($this->path.'', $this->documentRoot, $path.'');
		return $path;
	}

	function setPath($path) {
		$this->components['path'] = $path instanceof Path ? $path : new Path($path);
		$this->path = $this->components['path'];
	}

	/**
	 * Defines the filename in the URL
	 * @param $name
	 */
	function setBasename($name) {
		$this->path->setFile($name);
	}

	function getBasename() {
		return basename($this->getPath());
	}

	function getExtension() {
		$basename = $this->getBasename();
		$ext = pathinfo($basename, PATHINFO_EXTENSION);
		return $ext;
	}

	/**
	 * Lowercase guaranteed
	 * @return mixed|string
	 */
	function getExtensionLC() {
		$ext = $this->getExtension();
		$ext = mb_strtolower($ext);
		return $ext;
	}

	function setDocumentRoot($root) {
		$this->documentRoot = $root;
		//debug($this);
	}

	function setFragment($name) {
		$this->components['fragment'] = $name;
	}

	function buildQuery() {
		$queryString = http_build_query($this->params, '_');
		$queryString = str_replace('#', '%23', $queryString);
		//parse_str($queryString, $queryStringTest);
		//debug($this->params, $queryStringTest);
		return $queryString;
	}

	/**
	 * http://de2.php.net/manual/en/function.parse-url.php#85963
	 *
	 * @param null $parsed
	 * @return string
	 */
	function buildURL($parsed = NULL) {
		if (!$parsed) {
			$this->components['query'] = $this->buildQuery(); // to make sure manual manipulations are not possible (although it's already protected?)
			$parsed = $this->components;
		}
	    if (!is_array($parsed)) {
	        return false;
	    }

	    $uri = isset($parsed['scheme'])
			? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '' : '//')
			: '';
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

	public function __toString() {
		$url = $this->buildURL();
		//debug($this->components, $url);
		return $url.'';
	}

	public function getRequest() {
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

	function getCURL() {
		$process = curl_init($this->__toString());
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 1);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
		curl_setopt($process, CURLOPT_POSTFIELDS, $this->buildQuery());
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_POST, 1);
		return $process;
	}

	function CURL() {
		$process = $this->getCURL();
		$return = curl_exec($process);
		curl_close($process);
		return $return;
	}

	function getURLGet() {
		return new URLGet($this->__toString());
	}

	function exists() {
		$AgetHeaders = @get_headers($this->buildURL());
		return preg_match("|200|", $AgetHeaders[0]);
	}

	/**
	 * Works well when both paths are absolute.
	 * Comparing server path to URL path does not work.
	 * http://stackoverflow.com/a/2638272/417153
	 * @param string $from
	 * @param string $to
	 * @return string
	 */
	static function getRelativePath($from, $to) {
		// some compatibility fixes for Windows paths
		$from = self::getPathFolders($from);
		$to = self::getPathFolders($to);
		$relPath  = $to;

		foreach ($from as $depth => $dir) {
			// find first non-matching dir
			//debug($depth, $dir, $to[$depth]);
			if (isset($to[$depth]) && $dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if ($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else if (is_array($relPath) && isset($relPath[0])) {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		//debug($from, $to, $relPath);
		return implode('/', $relPath);
	}

	static function getScriptWithPath() {
		//if ($_SERVER['SCRIPT_FILENAME']{0} != '/') {
		// Pedram: we have to use __FILE__ constant in order to be able to execute phpUnit tests within PHPStorm
        // C:\Users\DEJOKMAJ\AppData\Local\Temp\ide-phpunit.php
        if (Request::isCLI()) {
	        $scriptWithPath = isset($_SERVER['SCRIPT_FILENAME'])
		        ? $_SERVER['SCRIPT_FILENAME']
		        : $_SERVER['PHP_SELF']; // can be relative!!!

	        //debug($scriptWithPath);
	        // this below may not work since __FILE__ is class.URL.php and not index.php
	        // but this our last chance for CLI/Cron
	        if (!$scriptWithPath || !startsWith($scriptWithPath, '/')) {    // relative not OK
		        if (basename(__FILE__) == __FILE__) {	// index.php
					$scriptWithPath = getcwd().'/'.__FILE__;
				} else {
					$scriptWithPath = __FILE__;
				}
	        }
		} else {
			$scriptWithPath = $_SERVER['SCRIPT_FILENAME'];
			$scriptWithPath = str_replace('/kunden', '', $scriptWithPath); // 1und1.de
		}
		return $scriptWithPath;
	}

	/**
	 * @return string
	 */
	function getDomain() {
		return $this->components['host'];
	}

	/**
	 * "asd/qwe\zxc/" => ['asd', 'qwe', 'zxc']
	 * Takes care of Windows path and removes empty
	 * @param $from
	 * @return array
	 */
	static function getPathFolders($from) {
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		$from = str_replace('\\', '/', $from);
		$from = explode('/', $from);
		$from = array_filter($from);
		return $from;
	}

	/**
	 * @param string $path1
	 * @param string $path2
	 * @return string
	 */
	static function getCommonRoot($path1, $path2) {
		$path1 = self::getPathFolders($path1);
		$path2 = self::getPathFolders($path2);
		$common = array_intersect($path1, $path2);
		//debug($path1, $path2, $common);
		return $common;
	}

	/**
	 * http://www.php.net/manual/en/function.realpath.php#71334
	 * @param $address
	 */
	function canonicalize($address) {
		$address = explode('/', $address);
		$keys = array_keys($address, '..');

		foreach($keys AS $keypos => $key)
		{
			array_splice($address, $key - ($keypos * 2 + 1), 2);
		}

		$address = implode('/', $address);
		$address = str_replace('./', '', $address);
	}

	/**
	 * http://nadeausoftware.com/articles/2008/05/php_tip_how_convert_relative_url_absolute_url
	 * @param $baseUrl
	 * @param $relativeUrl
	 * @return mixed
	 */
	function url_to_absolute( $baseUrl, $relativeUrl )
	{
		// If relative URL has a scheme, clean path and return.
		$r = $this->split_url( $relativeUrl );
		if ( $r === FALSE )
			return FALSE;
		if ( !empty( $r['scheme'] ) )
		{
			if ( !empty( $r['path'] ) && $r['path'][0] == '/' )
				$r['path'] = $this->url_remove_dot_segments( $r['path'] );
			return $this->join_url( $r );
		}

		// Make sure the base URL is absolute.
		$b = $this->split_url( $baseUrl );
		if ( $b === FALSE || empty( $b['scheme'] ) || empty( $b['host'] ) )
			return FALSE;
		$r['scheme'] = $b['scheme'];

		// If relative URL has an authority, clean path and return.
		if ( isset( $r['host'] ) )
		{
			if ( !empty( $r['path'] ) )
				$r['path'] = $this->url_remove_dot_segments( $r['path'] );
			return $this->join_url( $r );
		}
		unset( $r['port'] );
		unset( $r['user'] );
		unset( $r['pass'] );

		// Copy base authority.
		$r['host'] = $b['host'];
		if ( isset( $b['port'] ) ) $r['port'] = $b['port'];
		if ( isset( $b['user'] ) ) $r['user'] = $b['user'];
		if ( isset( $b['pass'] ) ) $r['pass'] = $b['pass'];

		// If relative URL has no path, use base path
		if ( empty( $r['path'] ) )
		{
			if ( !empty( $b['path'] ) )
				$r['path'] = $b['path'];
			if ( !isset( $r['query'] ) && isset( $b['query'] ) )
				$r['query'] = $b['query'];
			return $this->join_url( $r );
		}

		//debug($relativeUrl, $relativeUrl.'', $r);
		// If relative URL path doesn't start with /, merge with base path
		if ( $r['path'][0] != '/' )
		{
			$base = mb_strrchr( $b['path'], '/', TRUE, 'UTF-8' );
			if ( $base === FALSE ) $base = '';
			$r['path'] = $base . '/' . $r['path'];
		}
		$r['path'] = $this->url_remove_dot_segments( $r['path'] );
		return $this->join_url( $r );
	}

	function url_remove_dot_segments( $path )
	{
		// multi-byte character explode
		$inSegs  = preg_split( '!/!u', $path );
		$outSegs = array( );
		foreach ( $inSegs as $seg )
		{
			if ( $seg == '' || $seg == '.')
				continue;
			if ( $seg == '..' )
				array_pop( $outSegs );
			else
				array_push( $outSegs, $seg );
		}
		$outPath = implode( '/', $outSegs );
		if ( $path[0] == '/' )
			$outPath = '/' . $outPath;
		// compare last multi-byte character against '/'
		if ( $outPath != '/' &&
			(mb_strlen($path)-1) == mb_strrpos( $path, '/', 'UTF-8' ) )
			$outPath .= '/';
		return $outPath;
	}

	/**
	 * PHP's standard parse_url( ) looks useful. It splits apart a URL and returns an associative array containing the scheme, host, path, and so on. It works well on simple URLs like "http://example.com/index.htm". However, it has problems parsing complex URLs, like "http://example.com/redirect?url=http://elsewhere.com". It is confused by some relative URLs, such as "//example.com/index.htm". And it doesn't properly handle URLs using IPv6 addresses. The parser also is not as strict as it should be and will allow illegal characters and invalid URL structure. This makes it hard to use parse_url( ) reliably for validating links in link checkers and other tools.
	 * http://nadeausoftware.com/articles/2008/05/php_tip_how_parse_and_build_urls
	 * @param $url
	 * @param bool $decode
	 * @return mixed
	 */
	function split_url( $url, $decode=TRUE )
	{
		$xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
		$xpchar        = $xunressub . ':@%';

		$xscheme       = '([a-zA-Z][a-zA-Z\d+-.]*)';

		$xuserinfo     = '((['  . $xunressub . '%]*)' .
			'(:([' . $xunressub . ':%]*))?)';

		$xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

		$xipv6         = '(\[([a-fA-F\d.:]+)\])';

		$xhost_name    = '([a-zA-Z\d-.%]+)';

		$xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
		$xport         = '(\d*)';
		$xauthority    = '((' . $xuserinfo . '@)?' . $xhost .
			'?(:' . $xport . ')?)';

		$xslash_seg    = '(/[' . $xpchar . ']*)';
		$xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
		$xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
		$xpath_abs     = '(/(' . $xpath_rel . ')?)';
		$xapath        = '(' . $xpath_authabs . '|' . $xpath_abs .
			'|' . $xpath_rel . ')';

		$xqueryfrag    = '([' . $xpchar . '/?' . ']*)';

		$xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' .
			'(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


		// Split the URL into components.
		if ( !preg_match( '!' . $xurl . '!', $url, $m ) )
			return FALSE;

		if ( !empty($m[2]) )        $parts['scheme']  = strtolower($m[2]);

		if ( !empty($m[7]) ) {
			if ( isset( $m[9] ) )   $parts['user']    = $m[9];
			else            $parts['user']    = '';
		}
		if ( !empty($m[10]) )       $parts['pass']    = $m[11];

		if ( !empty($m[13]) )       $h=$parts['host'] = $m[13];
		else if ( !empty($m[14]) )  $parts['host']    = $m[14];
		else if ( !empty($m[16]) )  $parts['host']    = $m[16];
		else if ( !empty( $m[5] ) ) $parts['host']    = '';
		if ( !empty($m[17]) )       $parts['port']    = $m[18];

		if ( !empty($m[19]) )       $parts['path']    = $m[19];
		else if ( !empty($m[21]) )  $parts['path']    = $m[21];
		else if ( !empty($m[25]) )  $parts['path']    = $m[25];

		if ( !empty($m[27]) )       $parts['query']   = $m[28];
		if ( !empty($m[29]) )       $parts['fragment']= $m[30];

		if ( !$decode )
			return $parts;
		if ( !empty($parts['user']) )
			$parts['user']     = rawurldecode( $parts['user'] );
		if ( !empty($parts['pass']) )
			$parts['pass']     = rawurldecode( $parts['pass'] );
		if ( !empty($parts['path']) )
			$parts['path']     = rawurldecode( $parts['path'] );
		if ( isset($h) )
			$parts['host']     = rawurldecode( $parts['host'] );
		if ( !empty($parts['query']) )
			$parts['query']    = rawurldecode( $parts['query'] );
		if ( !empty($parts['fragment']) )
			$parts['fragment'] = rawurldecode( $parts['fragment'] );
		return $parts;
	}

	function join_url( $parts, $encode=TRUE )
	{
		if ( $encode )
		{
			if ( isset( $parts['user'] ) )
				$parts['user']     = rawurlencode( $parts['user'] );
			if ( isset( $parts['pass'] ) )
				$parts['pass']     = rawurlencode( $parts['pass'] );
			if ( isset( $parts['host'] ) &&
				!preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
				$parts['host']     = rawurlencode( $parts['host'] );
			if ( !empty( $parts['path'] ) )
				$parts['path']     = preg_replace( '!%2F!ui', '/',
					rawurlencode( $parts['path'] ) );
			if ( isset( $parts['query'] ) )
				$parts['query']    = rawurlencode( $parts['query'] );
			if ( isset( $parts['fragment'] ) )
				$parts['fragment'] = rawurlencode( $parts['fragment'] );
		}

		$url = '';
		if ( !empty( $parts['scheme'] ) )
			$url .= $parts['scheme'] . ':';
		if ( isset( $parts['host'] ) )
		{
			$url .= '//';
			if ( isset( $parts['user'] ) )
			{
				$url .= $parts['user'];
				if ( isset( $parts['pass'] ) )
					$url .= ':' . $parts['pass'];
				$url .= '@';
			}
			if ( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) )
				$url .= '[' . $parts['host'] . ']'; // IPv6
			else
				$url .= $parts['host'];             // IPv4 or name
			if ( isset( $parts['port'] ) )
				$url .= ':' . $parts['port'];
			if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
				$url .= '/';
		}
		if ( !empty( $parts['path'] ) )
			$url .= $parts['path'];
		if ( isset( $parts['query'] ) )
			$url .= '?' . $parts['query'];
		if ( isset( $parts['fragment'] ) )
			$url .= '#' . $parts['fragment'];
		return $url;
	}

	function setRelativePath($pathPlus) {
		$newPath = $this->url_to_absolute($this->__toString(), $pathPlus);
		//debug($this->__toString(), $pathPlus, $newPath);
		$this->parseURL($newPath);
	}

	/**
	 * @param string $string		- source page name
	 * @param bool $preserveSpaces	- leaves spaces
	 * @return string				- converted to URL friendly name
	 */
	static function friendlyURL($string, $preserveSpaces = false) {
		$string = preg_replace("`\[.*\]`U", "", $string);
		$string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i','-',$string);
		$string = htmlentities($string, ENT_COMPAT, 'utf-8');
		$string = preg_replace( "`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i","\\1", $string );
		if (!$preserveSpaces) {
			$string = preg_replace( array("`[^a-z0-9]`i", "`[-]+`") , "-", $string);
		}
		return strtolower(trim($string, '-'));
	}

	static function getSlug($string) {
		$string = mb_strtolower($string);
		$string = preg_replace("` +`", "-", $string);
		$string = trim($string);
		return $string;
	}

	public function makeAbsolute() {
		if (!ifsetor($this->components['scheme'])) {
			$this->components['scheme'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'
				? 'https'
				: 'http';
		}
		if (!ifsetor($this->components['host'])) {
			$this->components['host'] = $_SERVER['HTTP_HOST'];
		}
		if (!ifsetor($this->components['path'])) {
			$this->components['path'] = $_SERVER['REQUEST_URI'];
		}
	}

	function getHost() {
		return $this->components['host'];
	}

	function getPort() {
		return $this->components['port'];
	}

	function getUser() {
		return $this->components['user'];
	}

	function getPass() {
		return $this->components['pass'];
	}

	function getHash() {
		return $this->components['fragment'];
	}
	
	/**
	 * @param array $queryString - array of objects with (->name, ->value)
	 */
	public function setParamsFromHAR($queryString) {
		foreach ($queryString as $pair) {
			$this->setParam($pair->name, $pair->value);
		}
	}

	public function replaceController($newController) {
		if (is_array($newController)) {
			$newController = implode('/', $newController);
		}
		$path = $this->getPath();
		$diff = str_replace($this->documentRoot, '', $path);
		//debug($path, $this->documentRoot, $diff);
		$path = str_replace($diff, $newController, $path);
		$this->setPath($path);
		return $this;
	}

}
