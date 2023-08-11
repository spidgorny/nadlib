<?php

class LogEntry
{

	var $time;

	var $action;

	var $data;

	static $log2file;

	static function initLogging()
	{
		self::$log2file = DEVELOPMENT;
	}

	function __construct($action, $data)
	{
		$this->time = microtime(true);
		$this->action = $action;
		$this->data = $data;
		if (self::$log2file) {
			$sData = $this->shorten($data);
			$r = Request::getInstance();
			//$ip = $r->getClientIP();	// this is very expensive
			$ip = $r->getBrowserIP();
			error_log($ip . ' ' . $action . ' ' . $sData);
		}
	}

	function __toString()
	{
		$floating = substr($this->time - floor($this->time), 2);    // cut 0 from 0.1
		$floating = substr($floating, 0, 4);
		$sData = $this->shorten($this->data);
		return implode("\t", [
				date('H:i:s', $this->time) . '.' . $floating,
				$this->action,
				$this->data ? $sData : NULL
			]) . BR;
	}

	static function getLogFrom(array $log)
	{
		return [
			'<div class="debug" style="font-family: monospace">',
			$log,
			'</div>',
		];
	}

	/**
	 * @param $data
	 * @return bool|float|int|string
	 */
	public function shorten($data)
	{
		if (is_scalar($data)) {
			$sData = $data;
		} else {
			$sData = json_encode($data);
		}

		if (contains($sData, '<')) {
			$sData = htmlspecialchars($sData);    // no tags
		} elseif (contains($sData, "\n")) {
			$sData = '<pre>' . substr($sData, 0, 1000) . '</pre>';
		} else {
			$sData = substr($sData, 0, 1000);
		}
		return $sData;
	}

}
