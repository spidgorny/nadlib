<?php

use nadlib\Proxy;

class URLGet
{

	public $timeout = 10;
	/**
	 * @var Index
	 */
	public $logger;
	/**
	 * @var array info from CURL
	 */
	public $info;
	/**
	 * for file_get_content()
	 * @var array
	 */
	public $context = [];
	/**
	 * @var array
	 */
	public $curlParams = [];
	public $headers = [];
	/**
	 * @var string
	 */
	protected $url;
	protected $html = '';
	/**
	 * @var ?Proxy
	 */
	protected $proxy;

	/**
	 *
	 * @param string $url
	 * @param $logger object with method log()
	 */
	public function __construct($url, $logger = null)
	{
		$this->url = $url;
		$this->logger = $logger;
		$this->context = [
			'http' => [
				'timeout' => $this->timeout,
			]
		];
	}

	public function getURL()
	{
		return $this->url;
	}

	public function setProxy(string $host, string $username, string $password): void
	{
		$this->proxy = new Proxy([
			'id' => -1,
			'proxy' => 'http://' . $username . ':' . $password . '@' . $host,
		]);
	}

	/**
	 * @param int $retries
	 * @return false|string|null
	 */
	public function fetch($retries = 1): string|null|false
	{
		$start = microtime(true);
		$this->log(__METHOD__, '<a href="' . $this->url . '">' . $this->url . '</a>');
		$html = null;
		for ($i = 0; $i < $retries; $i++) {
			try {
				$html = $this->fetchAny();
			} catch (Exception $e) {
				$this->log(__METHOD__, $e->getMessage());
			}

			if ($html) {
				$this->log(__METHOD__, 'Download successful. Data size: ' . strlen($html) . ' bytes');
				break;
			}
		}

		$this->log(__METHOD__, $this->url . ' (' . number_format(microtime(true) - $start, 3, '.', '') . ' sec)');
		$this->html = $html;
		return $this->html;
	}

	public function log($method, $message): void
	{
		if ($this->logger) {
			$this->logger->log($method, $message);
		}
	}

	public function fetchAny(): string|false
	{
		if (function_exists('curl_init')) {
			$this->log(__METHOD__, 'CURL is enabled');
			if ($this->proxy) {
				$this->log(__METHOD__, 'Proxy is defined');
				if (!($this->proxy instanceof Proxy)) {
					$this->proxy = Proxy::getRandomOrBest();
				}

				$curlParams[CURLOPT_PROXY] = $this->proxy . '';
			} else {
				$this->log(__METHOD__, 'No Proxy');
			}

			$html = $this->fetchCURL($this->curlParams);
		} else {
			$this->log(__METHOD__, 'CURL is disabled');
			$html = $this->fetchFOpen();
		}

		return $html;
	}

	public function fetchCURL(array $options = []): string
	{
		$this->log(__METHOD__, $this->url . '');
		$process = curl_init($this->url);
		$headers = ArrayPlus::create($this->headers)->getHeaders("\r\n");
		$headers = trimExplode("\r\n", $headers);
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($process, CURLOPT_HEADER, true);
		//curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		//if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		//if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		//curl_setopt($process, CURLOPT_ENCODING , $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, $this->timeout);
		//curl_setopt($process, CURLOPT_POSTFIELDS, $data);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);
		//curl_setopt($process, CURLOPT_POST, 1);

		curl_setopt_array($process, $options);

		$response = curl_exec($process);
		$header_size = curl_getinfo($process, CURLINFO_HEADER_SIZE);
		$this->log(__METHOD__, $header_size);
		$headers = substr($response, 0, $header_size);
		$headlines = explode("\n", $headers);
		$headlines = array_map('trim', $headlines); // empty line
		if (array_search("sorry, untested", $headlines, true)) {
//			$header_size += strlen($headlines[0]);  // Proxy response
//			$headers = substr($response, 0, $header_size);
			$html = substr($response, $header_size);
		} else {
			//pre_print_r($headlines);

			$html = substr($response, $header_size);
		}

		$this->info = curl_getinfo($process);
		$this->log(__METHOD__,
			'URLGet Info: ' .
			json_encode($this->info, JSON_PRETTY_PRINT));
		$this->log(__METHOD__, 'URLGet Errno: ' . curl_errno($process));
		$this->log(__METHOD__, 'URLGet HTTP code: ' . $this->info['http_code']);
		$this->log(__METHOD__, 'URLGet Headers: ' . $headers);
		//debug($this->info);
		if (curl_errno($process) !== 0) {
			debug('Curl error: ' . curl_error($process));
		}

		curl_close($process);

		$this->html = $html;
		if (/*!$html || */ $this->info['http_code'] != 200) {    // when downloading large file directly to file system
			//debug($this->info);
			throw new Exception('failed to read URL: ' . $this->url);
		}

		return $html;
	}

	public function fetchFOpen(): string|false
	{
		if ($this->headers) {
			$this->context['http']['header'] = ArrayPlus::create($this->headers)->getHeaders("\r\n");
		}

		//debug($this->context);
		$ctx = stream_context_create($this->context);
		$html = file_get_contents($this->url, false, $ctx);
		$this->info = $http_response_header;
		return $html;
	}

	public function __toString(): string
	{
		return strval($this->html) . '';
	}

	public function getContent(): string
	{
		return strval($this->html) . '';
	}

	public function setProxyObject(Proxy $useProxy): void
	{
		$this->proxy = $useProxy;
	}

}
