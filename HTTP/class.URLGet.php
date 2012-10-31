<?php

class URLGet {
	protected $url;

	protected $timeout = 5;

	protected $html = '';

	/**
	 *
	 * @param string $url
	 */
	public function __construct($url) {
		$this->url = $url;
		//$this->fetch();
	}

	public function fetch() {
		$start = microtime(true);
		//Controller::log('<a href="'.$this->url.'">'.$this->url.'</a>', __CLASS__);
		do {
			try {
				if (function_exists('curl_init')) {
					$html = $this->fetchCURL();
				} else {
					$html = $this->fetchFOpen();
				}
			} catch (Exception $e) {
				//Controller::log($e->getMessage(), __CLASS__);
			}
		} while (!$html);
		Controller::log($this->url.' ('.number_format(microtime(true)-$start, 3, '.', '').')', __CLASS__);
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

	public function fetchCURL() {
		//$proxy = Proxy::getRandom();
		$process = curl_init($this->url);
		//curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		//curl_setopt($process, CURLOPT_HEADER, 1);
		//curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		//if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		//if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		//curl_setopt($process, CURLOPT_ENCODING , $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, $this->timeout);
		//curl_setopt($process, CURLOPT_POSTFIELDS, $data);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		if ($proxy) {
			curl_setopt($process, CURLOPT_PROXY, $proxy);
		}
		//curl_setopt($process, CURLOPT_POST, 1);
		$html = curl_exec($process);
		$info = curl_getinfo($process);
		curl_close($process);
		//debug($info);
		//debug($proxy);

		if (!$html || $info['http_code'] != 200) {
			if ($proxy) {
				//Controller::log('Using proxy: '.$proxy.': FAIL', __CLASS__);
				$proxy->update(array('fail' => $proxy->data['fail']+1));
			}
			throw new Exception('failed to read URL: '.$this->url);
		} else {
			if ($proxy) {
				//Controller::log('Using proxy: '.$proxy.': OK', __CLASS__);
				$proxy->update(array('ok' => $proxy->data['ok']+1));
			}
		}
		return $html;
	}

	public function __toString() {
		return $this->html;
	}

}
