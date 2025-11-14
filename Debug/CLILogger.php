<?php

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class CLILogger implements LoggerInterface
{

	/**
	 * Legacy method for backward compatibility
	 * @param string $method
	 * @param mixed $data
	 * @return void
	 */
	public function info($method, $data = null): void
	{
		if (func_num_args() === 1) {
			// PSR-3 compatible call: info($message)
			$this->log(LogLevel::INFO, $method, []);
		} else {
			// Legacy call: info($method, $data)
			$this->logMessage(LogLevel::INFO, $method, $data);
		}
	}

	/**
	 * PSR-3 compatible log method
	 * @param mixed $level
	 * @param string|\Stringable $message
	 * @param array $context
	 * @return void
	 */
	public function log($level, $message, array $context = []): void
	{
		$this->logMessage($level, $message, $context);
	}

	/**
	 * Internal logging method that handles the actual output
	 * @param string $level
	 * @param string $method
	 * @param mixed $data
	 * @return void
	 */
	protected function logMessage(string $level, string $method, $data = null): void
	{
		$output = $this->formatData($data);
		echo '[', strtoupper($level), '][', $method, '] ', $output, BR;
	}

	/**
	 * Format data for output
	 * @param mixed $data
	 * @return string
	 */
	protected function formatData($data): string
	{
		if ($data === null) {
			return '<null>';
		}

		if (is_array($data)) {
			return print_r($data, true);
		}

		if (is_scalar($data)) {
			return '<' . gettype($data) . '> ' . $data;
		}

		if (is_object($data)) {
			return get_class($data);
		}

		try {
			return json_encode($data, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			return '<unable to encode: ' . $e->getMessage() . '>';
		}
	}

	public function emergency($message, array $context = []): void
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	public function alert($message, array $context = []): void
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	public function critical($message, array $context = []): void
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	public function error($message, array $context = []): void
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	public function warning($message, array $context = []): void
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	public function notice($message, array $context = []): void
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	public function debug($message, array $context = []): void
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}
}
