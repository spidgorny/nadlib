<?php

class URLGet {

	/**
	 * @var string
	 */
	protected $url;

	public $timeout = 10;

	protected $html = '';

	protected $logger;

	/**
	 *
	 * @param string $url
	 */
	public function __construct($url) {
		$this->url = $url;
		$this->logger = Index::getInstance()->controller;
		//$this->fetch();
	}

	/**
	 * @var CURL info
	 */
	public $info;

	/**
	 * @var Proxy
	 */
	protected $proxy;

	/**
	 * @param bool $proxy - it was a proxy object, but now it's boolean
	 * as a new proxy will get generation
	 * @param int $retries
	 */
	public function fetch($proxy = false, $retries = 1) {
		$start = microtime(true);
		$this->logger->log('<a href="'.$this->url.'">'.$this->url.'</a>', __CLASS__);
		for ($i = 0; $i < $retries; $i++) {
			try {
				if (function_exists('curl_init')) {
					$this->logger->log('CURL is enabled');
					$curlParams = array();
					if ($proxy) {
						$this->logger->log('Proxy is defined');
						$this->proxy = Proxy::getRandom();
						$curlParams[CURLOPT_PROXY] = $this->proxy;
					} else {
						$this->logger->log('No Proxy');
					}
					$html = $this->fetchCURL($curlParams);
				} else {
					$this->logger->log('CURL is disabled');
					$html = $this->fetchFOpen();
				}
			} catch (Exception $e) {
				$this->logger->log($e->getMessage(), __CLASS__);
			}
			if ($html) {
				$this->logger->log('Download successful. Data size: '.strlen($html).' bytes');
				break;
			}
		}
		$this->logger->log($this->url.' ('.number_format(microtime(true)-$start, 3, '.', '').')', __CLASS__);
		$this->html = $html;
	}

	public function fetchFOpen() {
		$ctx = stream_context_create(array(
		    'http' => array(
		        'timeout' => $this->timeout,
		    )
		));
		$html = @file_get_contents($this->url, 0, $ctx);
		return $html;
	}

	public function fetchCURL(array $options = array()) {
		$this->logger->log(__METHOD__.'('.$this->url.')', __METHOD__);
		$process = curl_init($this->url);
		//curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
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
		$header = substr($response, 0, $header_size);
		$html = substr($response, $header_size);

		$this->info = curl_getinfo($process);
		$this->logger->log('URLGet Info: '.json_encode($this->info, JSON_PRETTY_PRINT));
		$this->logger->log('URLGet Errno: '.curl_errno($process));
		$this->logger->log('URLGet HTTP code: '.$this->info['http_code']);
		$this->logger->log('URLGet Header: '.$header);
		//debug($this->info);
		if (curl_errno($process)){
			//debug('Curl error: ' . curl_error($process));
		}
		curl_close($process);

		if (!$html || $this->info['http_code'] != 200) {
			if ($this->proxy) {
				//Controller::log('Using proxy: '.$proxy.': FAIL', __CLASS__);
				$this->proxy->fail();
			}
			//debug($this->info);
			throw new Exception('failed to read URL: '.$this->url);
		} else {
			if ($this->proxy) {
				//Controller::log('Using proxy: '.$proxy.': OK', __CLASS__);
				$this->proxy->ok();
			}
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

}
