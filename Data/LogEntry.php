<?php

class LogEntry
{

	/**
     * @var float
     */
    public $time;

	/**
     * @var string
     */
    public $action;

	public $data;

	public static $log2file;

	public static function initLogging(): void
	{
		self::$log2file = DEVELOPMENT;
	}

	public function __construct(string $action, $data)
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

	public function __toString(): string
	{
		$floating = substr($this->time - floor($this->time), 2);    // cut 0 from 0.1
		$floating = substr($floating, 0, 4);

		$sData = $this->shorten($this->data);
		$paddedAction = $this->action;
		if (strlen($paddedAction) < 20) {
			$paddedAction = str_pad($paddedAction, 20, ' ', STR_PAD_RIGHT);
		}

		return implode("\t", [
			date('H:i:s', (int)$this->time) . '.' . $floating,
			new HTMLTag('strong', [], $paddedAction),
			$this->data ? $sData : null
		]);
	}

	/**
     * Render function for multiple log entries
     * @param LogEntry[] $log
     */
    public static function getLogFrom(array $log): array
	{
		$prevTime = count($log) ? $log[0]?->time : null;
		$log = collect($log)->map(fn(LogEntry $x): string => '[' . number_format($x->time - $prevTime, 4, '.') . '] ' . $x->__toString())->toArray();
		return [
			'<pre class="debug" style="font-family: monospace; white-space: pre-wrap;">',
			implode(PHP_EOL, $log),
			'</pre>',
		];
	}

	/**
     * @param mixed $data
     */
    public function shorten($data): string
	{
		if (is_string($data) || is_int($data)) {
			$sData = $data;
		} else {
			$jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
			$sData = json_encode($data, JSON_THROW_ON_ERROR | $jsonOptions);
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
