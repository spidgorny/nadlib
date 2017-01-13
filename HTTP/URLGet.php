<?php

class URLGet {

	/**
	 * @var string
	 */
	protected $url;

	public $timeout = 10;

	protected $html = '';

	/**
	 * @var Index
	 */
	public $logger;

	/**
	 * @var CURL info
	 */
	public $info;

	/**
	 * @var Proxy
	 */
	protected $proxy;

	/**
	 * for file_get_content()
	 * @var array
	 */
	public $context = array();

	/**
	 * @var array
	 */
	public $curlParams = array();

	public $headers = array();

	/**
	 *
	 * @param string $url
	 * @param $logger object with method log()
	 */
	public function __construct($url, $logger) {
		$this->url = $url;
		$this->logger = $logger;
		$this->context = array(
			'http' => array(
				'timeout' => $this->timeout,
			)
		);
	}

	function setProxy($host, $username, $password) {
		$this->proxy = new Proxy(array(
			'id' => -1,
			'proxy' => 'http://'.$username.':'.$password.'@'.$host,
		));
	}

	/**
	 * @param int $retries
	 * @internal param bool|Proxy $proxy - it was a proxy object, but now it's boolean
	 * as a new proxy will get generation
	 */
	public function fetch($retries = 1) {
		$start = microtime(true);
		$this->logger->log(__METHOD__, '<a href="'.$this->url.'">'.$this->url.'</a>');
		$html = NULL;
		for ($i = 0; $i < $retries; $i++) {
			try {
				if (function_exists('curl_init')) {
					$this->logger->log(__METHOD__, 'CURL is enabled');
					if ($this->proxy) {
						$this->logger->log(__METHOD__, 'Proxy is defined');
						if (!($this->proxy instanceof Proxy)) {
							$this->proxy = Proxy::getRandomOrBest();
						}
						$curlParams[CURLOPT_PROXY] = $this->proxy.'';
					} else {
						$this->logger->log(__METHOD__, 'No Proxy');
					}
					$html = $this->fetchCURL($this->curlParams);
				} else {
					$this->logger->log(__METHOD__, 'CURL is disabled');
					$html = $this->fetchFOpen();
				}
			} catch (Exception $e) {
				$this->logger->log(__METHOD__, $e->getMessage());
			}
			if ($html) {
				$this->logger->log(__METHOD__, 'Download successful. Data size: '.strlen($html).' bytes');
				break;
			}
		}
		$this->logger->log(__METHOD__, $this->url.' ('.number_format(microtime(true)-$start, 3, '.', '').' sec)');
		$this->html = $html;
	}

	public function fetchFOpen() {
		if ($this->headers) {
			$this->context['http']['header'] = ArrayPlus::create($this->headers)->getHeaders("\r\n");
		}
		//debug($this->context);
		$ctx = stream_context_create($this->context);
		$html = file_get_contents($this->url, 0, $ctx);
		return $html;
	}

	public function fetchCURL(array $options = array()) {
		$this->logger->log(__METHOD__, $this->url.'');
		$process = curl_init($this->url);
		$headers = ArrayPlus::create($this->headers)->getHeaders("\r\n");
		$headers = trimExplode("\r\n", $headers);
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($process, CURLOPT_HEADER, 1);
		//curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		//if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		//if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		//curl_setopt($process, CURLOPT_ENCODING , $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, $this->timeout);
		//curl_setopt($process, CURLOPT_POSTFIELDS, $data);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		//curl_setopt($process, CURLOPT_POST, 1);

		curl_setopt_array($process, $options);

		$response = curl_exec($process);
		$header_size = curl_getinfo($process, CURLINFO_HEADER_SIZE);
		$this->logger->log(__METHOD__, $header_size);
		$headers = substr($response, 0, $header_size);
		$headlines = explode("\n", $headers);
		$headlines = array_map('trim', $headlines); // empty line
		if (array_search("", $headlines)) {
//			$header_size += strlen($headlines[0]);  // Proxy response
//			$headers = substr($response, 0, $header_size);
			$html = substr($response, $header_size);
		} else {
			$html = substr($response, $header_size);
		}

		$this->info = curl_getinfo($process);
		$this->logger->log(__METHOD__, 'URLGet Info: '.json_encode($this->info, defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : NULL));
		$this->logger->log(__METHOD__, 'URLGet Errno: '.curl_errno($process));
		$this->logger->log(__METHOD__, 'URLGet HTTP code: '.$this->info['http_code']);
		$this->logger->log(__METHOD__, 'URLGet Headers: '.$headers);
		//debug($this->info);
		if (curl_errno($process)){
			debug('Curl error: ' . curl_error($process));
		}
		curl_close($process);

		$this->html = $html;
		if (/*!$html || */$this->info['http_code'] != 200) {	// when downloading large file directly to file system
			//debug($this->info);
			throw new Exception('failed to read URL: '.$this->url);
		}
		return $html;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return strval($this->html).'';
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return strval($this->html).'';
	}

	public function setProxyObject(Proxy $useProxy) {
		$this->proxy = $useProxy;
	}

}
