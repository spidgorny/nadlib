<?php

/**
 * Class JSONResponse
 * Usage: return new JSONResponse(['success' => 'ok']);
 */
class JSONResponse
{

	public $json = null;

	public function __construct($json)
	{
		$this->json = $json;
	}

	public function __toString()
	{
		Request::getInstance()->set('ajax', true);
		$options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
		if (defined('JSON_UNESCAPED_LINE_TERMINATORS')) {
			$options |= JSON_UNESCAPED_LINE_TERMINATORS;
		}
		$json = json_encode($this->json + [
			'duration' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
		], $options) . '';
		header('Content-Type: application/json');
		return $json;
	}

}
