<?php

/**
 * Class SignalClient - send vitality information to the signal server
 */
class SignalClient {

	/**
	 * @var string URL
	 */
	var $endpoint;

	/**
	 * @var \GuzzleHttp\Client
	 */
	var $guzzle;

	function __construct($endpoint, GuzzleHttp\Client $guzzle)
	{
		$this->endpoint = $endpoint;
		$this->guzzle = $guzzle;
	}

	function success($message)
	{
		$this->guzzle->post($this->endpoint.'/success', [
			'message' => $message,
		]);
	}

	function exception(Exception $e) {
		$this->guzzle->post($this->endpoint.'/exception', [
			'code' => $e->getCode(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'message' => $e->getMessage(),
			'trace' => $e->getTraceAsString(),
		]);
	}

	function error($message)
	{
		$this->guzzle->post($this->endpoint.'/error', [
			'message' => $message,
		]);
	}

}
