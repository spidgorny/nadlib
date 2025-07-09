<?php

use Psr\Log\LoggerInterface;

class ErrorLogLogger implements LoggerInterface
{

	public function info($method, array $data = []): void
	{
		$this->log('INFO', $method, $data);
	}

	public function log($level, $method, array $data = []): void
	{
		$output = json_encode($data, JSON_THROW_ON_ERROR);
		/** @noinspection ForgottenDebugOutputInspection */
		error_log($level . '[' . $method . '] ' . $output);
	}

	public function emergency($message, array $context = []): void
	{
		$this->log('EMERGENCY', $message, $context);
	}

	public function alert($message, array $context = []): void
	{
		$this->log('ALERT', $message, $context);
	}

	public function critical($message, array $context = []): void
	{
		$this->log('CRITICAL', $message, $context);
	}

	public function error($message, array $context = []): void
	{
		$this->log('ERROR', $message, $context);
	}

	public function warning($message, array $context = []): void
	{
		$this->log('WARNING', $message, $context);
	}

	public function notice($message, array $context = []): void
	{
		$this->log('NOTICE', $message, $context);
	}

	public function debug($message, array $context = []): void
	{
		$this->log('DEBUG', $message, $context);
	}
}
