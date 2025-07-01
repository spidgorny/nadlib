<?php

namespace spidgorny\nadlib\HTTP;

use AutoLoad;
use CurlHandle;
use Exception;
use LogEntry;
use nadlib\Proxy;
use Path;
use Request;
use URLGet;

class URL
{

	/**
	 * @var string
	 */
	public $url;

	/**
	 * scheme, user, pass, host, port, path, query, fragment
	 *
	 * @var array
	 */
	public $components = [];

	/**
	 * $this->components['query'] decomposed into an array
	 * @var array
	 */
	public $params = [];

	/**
	 * @var string
	 */
	public $documentRoot = '';
	/**
	 * @var array
	 */
	public $log = [];
	/**
	 * @var array
	 */
	public $cookies = [];
	public $headers = [];
	public $user_agent;
	public $cookie_file;
	public $compression;
	/**
	 * @var ?Proxy
	 */
	public $proxy;
	/**
	 * = $this->components['path']
	 * @var Path
	 */
	protected $path;

	/**
	 * @param string|URL $url - if not specified then the current page URL is reconstructed
	 * @param array $params
	 */
	public function __construct($url = null, array $params = [])
	{
		if ($url instanceof URL) {
			//return $url;	// doesn't work
//			throw new \RuntimeException(__METHOD__);
			foreach (get_object_vars($url) as $key => $val) {
				$this->$key = $val;
			}
		} elseif (empty($url)) { // empty string should not default to localhost
			$http = Request::getRequestType();
			//debug($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'], $_SERVER);
			$host = ifsetor($_SERVER['HTTP_X_FORWARDED_HOST'], ifsetor($_SERVER['HTTP_HOST']));
			if ($host) {
				$url = $http . '://' . $host . ifsetor($_SERVER['REQUEST_URI'], '/');
			} else {
				$url = $http . '://' . (gethostname() ?: 'localhost') . '/';
			}

			$this->parseURL($url);
		} else {
			$this->parseURL($url);
		}

		if ($params !== []) {
			$this->addParams($params);    // setParams was deleting all filters from the URL
		}

//		if (class_exists('Config')) {
//			$this->setDocumentRoot(Config::getInstance()->documentRoot);
//		}
		// infinite recursion
//		$this->setDocumentRoot(Request::getInstance()->getDocumentRoot());
		$this->setDocumentRoot(Request::getDocumentRootByRequest());
	}

	/**
	 * @param $url string
	 */
	public function parseURL(string $url): void
	{
		$this->components = parse_url($url);
		//pre_print_r($this->components);
		if (!$this->components) {
			// parse_url(/pizzavanti-gmbh/id:3/10.09.2012@10:30/488583b0e1f3d90d48906281f8e49253.html)
			// [function.parse-url]: Unable to parse URL
			$request = Request::getExistingInstance();
			if ($request) {
				//debug(substr($request->getLocation(), 0, -1).$url);
				$callStack = debug_backtrace();
				foreach ($callStack as &$call) {
					$call = ifsetor($call['class']) . ifsetor($call['type']) . $call['function'];
				}

				if (ifsetor($_COOKIE['d'])) {
					print_r($callStack);
					ob_end_flush();
				}

				// prevent infinite loop
				if (!in_array('Request::getLocation', $callStack) &&
					!in_array('Request->getLocation', $callStack)
				) {
					$this->components = parse_url(substr($request->getLocation(), 0, -1) . $url);
				} else {
					//debug($this->components);
				}
			}
		}

		//debug($url, $request ? 'Request::getExistingInstance' : '');
		if (isset($this->components['path'])) {
			$this->path = new Path($this->components['path']);
			// keep the original intact, just in case
//			$this->components['path'] = $this->path;
			//pre_print_r([__METHOD__, $this->components, get_class($this->path)]);
		} else {
			$this->path = new Path('/');
		}

		if (isset($this->components['query'])) {
			parse_str($this->components['query'], $this->params);
		}
	}

	/**
	 * New params have priority
	 * @return $this
	 */
	public function addParams(array $params = []): static
	{
		$this->params = $params + $this->params;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	public function buildQuery(): string
	{
		$queryString = http_build_query($this->params, '_');
		//parse_str($queryString, $queryStringTest);
		//debug($this->params, $queryStringTest);
		return str_replace('#', '%23', $queryString);
	}

	public function setDocumentRoot($root): static
	{
		$this->documentRoot = $root;
		//debug($this);
		return $this;
	}

	public static function from($url = null, array $params = []): self
	{
		return new self($url, $params);
	}

	public static function make(array $params = []): self
	{
		$url = new self();
		$url->setParams($params);
		return $url;
	}

	/**
	 * @static
	 */
	public static function getCurrent(): URL
	{
		return new URL();
	}

	/**
	 * Works well when both paths are absolute.
	 * Comparing server path to URL path does not work.
	 * http://stackoverflow.com/a/2638272/417153
	 * @param string $from
	 * @param string $to
	 */
	public static function getRelativePath($from, $to): string
	{
//		0 && debug(
//			$_SERVER['DOCUMENT_ROOT'],
//			$from,
//			$to,
//			__FILE__,
//			trimExplode(':', ini_get('open_basedir')),
//			$_SERVER
//		);
		//		exit;
		// some compatibility fixes for Windows paths
		$from = self::getPathFolders($from);
		$to = self::getPathFolders($to);
		$relPath = $to;

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
				}

				if (isset($relPath[0])) {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}

		//debug($from, $to, $relPath);
		return implode('/', $relPath);
	}

	/**
	 * "asd/qwe\zxc/" => ['asd', 'qwe', 'zxc']
	 * Takes care of Windows path and removes empty
	 * @param $from
	 * @return array
	 */
	public static function getPathFolders($from): array
	{
		//		ob_start();
		//		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		if (!ini_get('open_basedir')) {
			$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		}

		$from = str_replace('\\', '/', $from);
		$from = explode('/', $from);
		return array_filter($from);
	}

	public static function getScriptWithPath()
	{
		//if ($_SERVER['SCRIPT_FILENAME']{0} != '/') {
		// Pedram: we have to use __FILE__ constant in order to be able to execute phpUnit tests within PHPStorm
		// C:\Users\DEJOKMAJ\AppData\Local\Temp\ide-phpunit.php
		if (Request::isCLI()) {
			$scriptWithPath = $_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF']; // can be relative!!!

			//debug($scriptWithPath);
			// this below may not work since __FILE__ is class.URL.php and not index.php
			// but this our last chance for CLI/Cron
			if (!$scriptWithPath || !Path::isItAbsolute($scriptWithPath)) {    // relative not OK
				if (basename(__FILE__) === __FILE__) {    // index.php
					$scriptWithPath = getcwd() . '/' . __FILE__;
				} else {
					$scriptWithPath = __FILE__;
				}
			}
		} else {
			$scriptWithPath = $_SERVER['SCRIPT_FILENAME'];
			$scriptWithPath = str_replace('/kunden', '', $scriptWithPath); // 1und1.de

			// add /data001/ to /data001/srv/www/htdocs
			// in virtual environments (symlink)
			$scriptWithPath = realpath($scriptWithPath);
		}

		return $scriptWithPath;
	}

	/**
	 * @param string $path1
	 * @param string $path2
	 */
	public static function getCommonRoot($path1, $path2): array
	{
		$path1 = self::getPathFolders($path1);
		$path2 = self::getPathFolders($path2);
		//debug($path1, $path2, $common);
		return array_intersect($path1, $path2);
	}

	/**
	 * @param string $string - source page name
	 * @param bool $preserveSpaces - leaves spaces
	 * @return string                - converted to URL friendly name
	 */
	public static function friendlyURL($string, $preserveSpaces = false): string
	{
		$string = preg_replace("`\[.*\]`U", "", $string);
		$string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i', '-', $string);
		$string = htmlentities($string, ENT_COMPAT, 'utf-8');
		$string = preg_replace("`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i", "\\1", $string);
		if (!$preserveSpaces) {
			$string = preg_replace(["`[^a-z0-9]`i", "`[-]+`"], "-", $string);
		}

		return strtolower(trim($string, '-'));
	}

	public static function getSlug($string): string
	{
		$string = mb_strtolower($string);
		$string = preg_replace("` +`", "-", $string);
		$string = str_replace('/', '-', $string);
		$string = str_replace('\\', '-', $string);
		$string = str_replace('"', '-', $string);
		$string = str_replace("'", '-', $string);
		return trim($string);
	}

	public function unsetParam($param): static
	{
		unset($this->params[$param]);
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	public function getParam($param)
	{
		return ifsetor($this->params[$param]);
	}

	public function forceParams(array $params = []): static
	{
		$this->params = array_merge($this->params, $params);    // keep default order but overwrite
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	public function appendParams(array $params): void
	{
		$this->params += $params;
		$this->components['query'] = $this->buildQuery();
	}

	public function __clone()
	{
		$this->path = clone $this->path;
	}

	public function reset(): void
	{
		$this->components['path'] = $this->documentRoot;
		$this->components['query'] = '';
		$this->clearParams();
	}

	public function clearParams(): static
	{
		$this->setParams([]);
		return $this;
	}

	/**
	 * Defines the filename in the URL
	 * @param $name
	 * @return $this
	 */
	public function setBasename($name): static
	{
		$this->path->setFile($name);
		return $this;
	}

	/**
	 * Lowercase guaranteed
	 * @return string
	 */
	public function getExtensionLC(): string
	{
		$ext = $this->getExtension();
		return mb_strtolower($ext);
	}

	public function getExtension(): string
	{
		$basename = $this->getBasename();
		return pathinfo($basename, PATHINFO_EXTENSION);
	}

	public function getBasename(): string
	{
		return basename($this->getPath());
	}

	/**
	 * @return Path
	 */
	public function getPath()
	{
		$path = $this->path;

		if ($this->documentRoot !== '/') {
			//$path = str_replace($this->documentRoot, '', $path);	// WHY???
		}

//		nodebug([
//			'class($this->path)' => get_class($this->path),
//			'$this->path' => $this->path . '',
//			'documentRoot' => $this->documentRoot . '',
//			'class($path)' => get_class($path),
//			'path' => $path . '',
//		]);
		return $path;
	}

	/**
	 * @param string|Path $path
	 * @return $this
	 */
	public function setPath($path): static
	{
		$this->components['path'] = $path instanceof Path ? $path : new Path($path);
		$this->path = $this->components['path'];
		return $this;
	}

	public function setFragment($name): static
	{
		if ($name[0] === '#') {
			$name = substr($name, 1);
		}

		$this->components['fragment'] = $name;
		return $this;
	}

	public function getRequest()
	{
		$r = Request::getInstance($this->params ?: []);
		$r->url = $this;
		return $r;
	}

	public function GET(): string|false
	{
		return file_get_contents($this->buildURL());
	}

	/**
	 * http://de2.php.net/manual/en/function.parse-url.php#85963
	 *
	 * @throws Exception
	 */
	public function buildURL($parsed = null): string
	{
		if (!$parsed) {
			// to make sure manual manipulations are not possible (although it's already protected?)
			$this->components['query'] = $this->buildQuery();
			$this->components['path'] = $this->path->__toString();
			$parsed = $this->components;
		}

		invariant(is_array($parsed), 'Parsed URL must be an array');

		$uri = isset($parsed['scheme'])
			? $parsed['scheme'] . ':' . ((strtolower($parsed['scheme']) === 'mailto') ? '' : '//')
			: '';
		$uri .= isset($parsed['user'])
			? $parsed['user'] . (isset($parsed['pass']) ? ':' . $parsed['pass'] : '') . '@'
			: '';

		$uri .= $parsed['host'] ?? '';
		$uri .= isset($parsed['port']) ? ':' . $parsed['port'] : '';

		if (isset($parsed['path'])) {
			$uri .= (str_starts_with($parsed['path'], '/')) ?
				$parsed['path'] : (($uri === '' || $uri === '0' ? '' : '/') . $parsed['path']);
		}

		$uri .= $parsed['query'] ? '?' . $parsed['query'] : '';

		$fragmentPart = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
		return $uri . $fragmentPart;
	}

	/**
	 * @throws Exception
	 */
	public function __toString(): string
	{
		if (ifsetor($this->components['host'])) {
			return $this->buildURL();
		}

		$url = '';
//			if (ifsetor($this->components['path'])
//				&& $this->components['path'] != '/') {
//				$url .= $this->components['path'];
//			}
		$url .= $this->path . '';
		if (ifsetor($this->components['query'])) {
			$url .= '?' . $this->components['query'];
		}

		$fragment = ifsetor($this->components['fragment']);
		if ($fragment && $fragment !== '#') {
			$url .= '#' . $this->components['fragment'];
		}

		//debug($this->components, $url);
		return $url . '';
	}

	public function POST($login = null, $password = null): string|false
	{
		$auth = null;
		if ($login) {
			$auth = "Authorization: Basic " . base64_encode($login . ':' . $password) . PHP_EOL;
		}

		$stream = [
			'http' => [
				'method' => 'POST',
				'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL . $auth,
				'content' => $this->components['query'],
			],
		];
		$context = stream_context_create($stream);

		$noQuery = $this->components;
		unset($noQuery['query']);
		$url = $this->buildURL($noQuery);
		return file_get_contents($url, false, $context);
	}

	public function CURL(): bool|string
	{
		$process = $this->getCURL();
		$return = curl_exec($process);
		curl_close($process);
		return $return;
	}

	public function getCURL(): CurlHandle|false
	{
		$process = curl_init($this->__toString());
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, true);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == true) {
			curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		}

		if ($this->cookies == true) {
			curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		}

		curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		if ($this->proxy) {
			curl_setopt($process, CURLOPT_PROXY, $this->proxy);
		}

		curl_setopt($process, CURLOPT_POSTFIELDS, $this->buildQuery());
		curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($process, CURLOPT_POST, true);
		return $process;
	}

	public function getURLGet(): URLGet
	{
		return new URLGet($this->__toString());
	}

	/**
	 * @return string
	 */
	public function getDomain()
	{
		return $this->components['host'];
	}

	/**
	 * http://www.php.net/manual/en/function.realpath.php#71334
	 * @param $address
	 * @return string
	 */
	public function canonicalize($address): string
	{
		$address = explode('/', $address);
		$keys = array_keys($address, '..');

		foreach ($keys as $keypos => $key) {
			array_splice($address, $key - ($keypos * 2 + 1), 2);
		}

		$address = implode('/', $address);
		return str_replace('./', '', $address);
	}

	public function resolve($relativeURL): string|false
	{
		return $this->url_to_absolute($this->__toString(), $relativeURL);
	}

	/**
	 * http://nadeausoftware.com/articles/2008/05/php_tip_how_convert_relative_url_absolute_url
	 * @param $baseUrl
	 * @param $relativeUrl
	 */
	private function url_to_absolute(string $baseUrl, $relativeUrl): false|string
	{
		// If relative URL has a scheme, clean path and return.
		$r = $this->split_url($relativeUrl);
		if ($r === false) {
			$this->log('Unable to split', $relativeUrl);
			return false;
		}

		if (!empty($r['scheme'])) {
			if (!empty($r['path']) && $r['path'][0] == '/') {
				$r['path'] = $this->url_remove_dot_segments($r['path']);
			}

			return $this->join_url($r);
		}

		// Make sure the base URL is absolute.
		$b = $this->split_url($baseUrl);
		if ($b === false || empty($b['scheme']) || empty($b['host'])) {
			$this->log('unable to split', $baseUrl);
			return false;
		}

		$r['scheme'] = $b['scheme'];

		// If relative URL has an authority, clean path and return.
		if (isset($r['host'])) {
			if (!empty($r['path'])) {
				$r['path'] = $this->url_remove_dot_segments($r['path']);
			}

			return $this->join_url($r);
		}

		unset($r['port']);
		unset($r['user']);
		unset($r['pass']);

		// Copy base authority.
		$r['host'] = $b['host'];
		if (isset($b['port'])) {
			$r['port'] = $b['port'];
		}

		if (isset($b['user'])) {
			$r['user'] = $b['user'];
		}

		if (isset($b['pass'])) {
			$r['pass'] = $b['pass'];
		}

		// If relative URL has no path, use base path
		if (empty($r['path'])) {
			if (!empty($b['path'])) {
				$r['path'] = $b['path'];
			}

			if (!isset($r['query']) && isset($b['query'])) {
				$r['query'] = $b['query'];
			}

			return $this->join_url($r);
		}

		//debug($relativeUrl, $relativeUrl.'', $r);
		// If relative URL path doesn't start with /, merge with base path
		if ($r['path'][0] != '/') {
			$base = mb_strrchr($b['path'], '/', true, 'UTF-8');
			if ($base === false) {
				$base = '';
			}

			$r['path'] = $base . '/' . $r['path'];
		}

		$r['path'] = $this->url_remove_dot_segments($r['path']);
		return $this->join_url($r);
	}

	/**
	 * PHP's standard parse_url( ) looks useful. It splits apart a URL and returns an associative array containing the
	 * scheme, host, path, and so on. It works well on simple URLs like "http://example.com/index.htm". However, it has
	 * problems parsing complex URLs, like "http://example.com/redirect?url=http://elsewhere.com". It is confused by
	 * some relative URLs, such as "//example.com/index.htm". And it doesn't properly handle URLs using IPv6 addresses.
	 * The parser also is not as strict as it should be and will allow illegal characters and invalid URL structure.
	 * This makes it hard to use parse_url( ) reliably for validating links in link checkers and other tools.
	 * http://nadeausoftware.com/articles/2008/05/php_tip_how_parse_and_build_urls
	 * @param      $url
	 * @param bool $decode
	 */
	public function split_url($url, $decode = true): false|array
	{
		$parts = [];
		$xunressub = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
		$xpchar = $xunressub . ':@%';

		$xscheme = '([a-zA-Z][a-zA-Z\d+-.]*)';

		$xuserinfo = '(([' . $xunressub . '%]*)' .
			'(:([' . $xunressub . ':%]*))?)';

		$xipv4 = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

		$xipv6 = '(\[([a-fA-F\d.:]+)\])';

		$xhost_name = '([a-zA-Z\d\-.%]+)';

		$xhost = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
		$xport = '(\d*)';
		$xauthority = '((' . $xuserinfo . '@)?' . $xhost .
			'?(:' . $xport . ')?)';

		$xslash_seg = '(\/[' . $xpchar . ']*)';
		$xpath_authabs = '((\/\/' . $xauthority . ')((\/[' . $xpchar . ']*)*))';
		$xpath_rel = '([' . $xpchar . ']+' . $xslash_seg . '*)';
		$xpath_abs = '(\/(' . $xpath_rel . ')?)';
		$xapath = '(' . $xpath_authabs . '|' . $xpath_abs .
			'|' . $xpath_rel . ')';

		$xqueryfrag = '([' . $xpchar . '\/?' . ']*)';

		$xurl = '^(' . $xscheme . ':)?' . $xapath . '?' .
			'(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


		// Split the URL into components.
		if (!preg_match('!' . $xurl . '!', $url, $m)) {
			return false;
		}

		if (isset($m[2]) && ($m[2] !== '' && $m[2] !== '0')) {
			$parts['scheme'] = strtolower($m[2]);
		}

		if (isset($m[7]) && ($m[7] !== '' && $m[7] !== '0')) {
			$parts['user'] = isset($m[9]) ? $m[9] : '';
		}

		if (isset($m[10]) && ($m[10] !== '' && $m[10] !== '0')) {
			$parts['pass'] = $m[11];
		}

		if (isset($m[13]) && ($m[13] !== '' && $m[13] !== '0')) {
			$h = $parts['host'] = $m[13];
		} elseif (isset($m[14]) && ($m[14] !== '' && $m[14] !== '0')) {
			$parts['host'] = $m[14];
		} elseif (isset($m[16]) && ($m[16] !== '' && $m[16] !== '0')) {
			$parts['host'] = $m[16];
		} elseif (isset($m[5]) && ($m[5] !== '' && $m[5] !== '0')) {
			$parts['host'] = '';
		}

		if (isset($m[17]) && ($m[17] !== '' && $m[17] !== '0')) {
			$parts['port'] = $m[18];
		}

		if (isset($m[19]) && ($m[19] !== '' && $m[19] !== '0')) {
			$parts['path'] = $m[19];
		} elseif (isset($m[21]) && ($m[21] !== '' && $m[21] !== '0')) {
			$parts['path'] = $m[21];
		} elseif (isset($m[25]) && ($m[25] !== '' && $m[25] !== '0')) {
			$parts['path'] = $m[25];
		}

		if (isset($m[27]) && ($m[27] !== '' && $m[27] !== '0')) {
			$parts['query'] = $m[28];
		}

		if (isset($m[29]) && ($m[29] !== '' && $m[29] !== '0')) {
			$parts['fragment'] = $m[30];
		}

		if (!$decode) {
			return $parts;
		}

		if (isset($parts['user']) && ($parts['user'] !== '' && $parts['user'] !== '0')) {
			$parts['user'] = rawurldecode($parts['user']);
		}

		if (isset($parts['pass']) && ($parts['pass'] !== '' && $parts['pass'] !== '0')) {
			$parts['pass'] = rawurldecode($parts['pass']);
		}

		if (isset($parts['path'])) {
			$parts['path'] = rawurldecode($parts['path']);
		}

		if (isset($h)) {
			$parts['host'] = rawurldecode($parts['host']);
		}

		if (isset($parts['query']) && ($parts['query'] !== '' && $parts['query'] !== '0')) {
			$parts['query'] = rawurldecode($parts['query']);
		}

		if (isset($parts['fragment']) && ($parts['fragment'] !== '' && $parts['fragment'] !== '0')) {
			$parts['fragment'] = rawurldecode($parts['fragment']);
		}

		return $parts;
	}

	protected function log($action, $data = null)
	{
		$this->log[] = new LogEntry($action, $data);
	}

	public function url_remove_dot_segments($path): string
	{
		// multi-byte character explode
		$inSegs = preg_split('!/!u', $path);
		$outSegs = [];
		foreach ($inSegs as $seg) {
			if ($seg === '' || $seg === '.') {
				continue;
			}

			if ($seg === '..') {
				array_pop($outSegs);
			} else {
				$outSegs[] = $seg;
			}
		}

		$outPath = implode('/', $outSegs);
		if ($path[0] === '/') {
			$outPath = '/' . $outPath;
		}

		// compare last multi-byte character against '/'
		if ($outPath !== '/' && (mb_strlen($path) - 1) == mb_strrpos($path, '/', 0, 'UTF-8')
		) {
			$outPath .= '/';
		}

		return $outPath;
	}

	public function join_url($parts, $encode = true): string
	{
		if ($encode) {
			if (isset($parts['user'])) {
				$parts['user'] = rawurlencode($parts['user']);
			}

			if (isset($parts['pass'])) {
				$parts['pass'] = rawurlencode($parts['pass']);
			}

			if (isset($parts['host']) &&
				!preg_match('!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'])
			) {
				$parts['host'] = rawurlencode($parts['host']);
			}

			if (!empty($parts['path'])) {
				$parts['path'] = preg_replace(
					'!%2F!ui',
					'/',
					rawurlencode($parts['path'])
				);
			}

			if (isset($parts['query'])) {
				$parts['query'] = rawurlencode($parts['query']);
			}

			if (isset($parts['fragment'])) {
				$parts['fragment'] = rawurlencode($parts['fragment']);
			}
		}

		$url = '';
		if (!empty($parts['scheme'])) {
			$url .= $parts['scheme'] . ':';
		}

		if (isset($parts['host'])) {
			$url .= '//';
			if (isset($parts['user'])) {
				$url .= $parts['user'];
				if (isset($parts['pass'])) {
					$url .= ':' . $parts['pass'];
				}

				$url .= '@';
			}

			if (preg_match('!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'])) {
				$url .= '[' . $parts['host'] . ']';
			} // IPv6
			else {
				$url .= $parts['host'];
			}

			// IPv4 or name
			if (isset($parts['port'])) {
				$url .= ':' . $parts['port'];
			}

			if (!empty($parts['path']) && $parts['path'][0] != '/') {
				$url .= '/';
			}
		}

		if (!empty($parts['path'])) {
			$url .= $parts['path'];
		}

		if (isset($parts['query'])) {
			$url .= '?' . $parts['query'];
		}

		if (isset($parts['fragment'])) {
			$url .= '#' . $parts['fragment'];
		}

		return $url;
	}

	public function setRelativePath($pathPlus): void
	{
		$newPath = $this->url_to_absolute($this->__toString(), $pathPlus);
		//debug($this->__toString(), $pathPlus, $newPath);
		$this->parseURL($newPath);
	}

	public function makeAbsolute(): static
	{
		if (!ifsetor($this->components['scheme'])) {
			$this->components['scheme'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
				? 'https'
				: 'http';
		}

		if (!ifsetor($this->components['host'])) {
			$this->components['host'] = $_SERVER['HTTP_HOST'];
		}

		if (!ifsetor($this->components['path'])) {
			$this->components['path'] = $_SERVER['REQUEST_URI'];
		}

		return $this;
	}

	public function getScheme()
	{
		return ifsetor($this->components['scheme']);
	}

	public function getHost()
	{
		return ifsetor($this->components['host']);
	}

	public function setHost($host): void
	{
		$this->components['host'] = $host;
	}

	public function getPort()
	{
		return ifsetor($this->components['port']);
	}

	public function getUser()
	{
		return ifsetor($this->components['user']);
	}

	public function getPass()
	{
		return ifsetor($this->components['pass']);
	}

	public function getHash()
	{
		return ifsetor($this->components['fragment']);
	}

	/**
	 * @param array $queryString - array of objects with (->name, ->value)
	 */
	public function setParamsFromHAR($queryString): void
	{
		foreach ($queryString as $pair) {
			$this->setParam($pair->name, $pair->value);
		}
	}

	/**
	 * @param $param
	 * @param $value
	 */
	public function setParam($param, $value): static
	{
		$this->params[$param] = $value;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	public function replaceController($newController): static
	{
		if (is_array($newController)) {
			$newController = implode('/', $newController);
		}

		$path = $this->getPath();
		$diff = '';
		if ($this->documentRoot != '/') {
			$diff = str_replace($this->documentRoot, '', $path);
		}

		debug([
			'original' => $path . '',
			'docroot' => $this->documentRoot,
			'diff' => $diff,
			'replace-by' => $newController,
		]);
		$path = str_replace($diff, '/' . $newController, $path);
		$this->setPath($path);
		return $this;
	}

	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Replaces parameters completely (with empty array?)
	 * @return $this
	 */
	public function setParams(array $params = []): static
	{
		$this->params = $params;
		$this->components['query'] = $this->buildQuery();
		return $this;
	}

	public function makeRelative(): static
	{
		$al = AutoLoad::getInstance();
		$path = $this->getPath();
		//		debug($path.'', $path->isAbsolute(), $al->getAppRoot().'');
		if ($path->isAbsolute() && $path->exists()) {
			$this->setPath($path->relativeFromAppRoot());
		} else {
			$new = array_diff($path->aPath, $al->getAppRoot()->aPath);
			$this->setPath(new Path(implode('/', $new)));
		}

		return $this;
	}

	public function exists(): int|false
	{
		$AgetHeaders = @get_headers($this->buildURL());
		return preg_match("|200|", $AgetHeaders[0]);
	}

	public function appendString(string $path): static
	{
		$this->path->appendString($path);
		return $this;
	}

	public function toString(): string
	{
		return $this->__toString();
	}

	public function setPass(string $newPassword): static
	{
		$this->components['pass'] = $newPassword;
		return $this;
	}

}
