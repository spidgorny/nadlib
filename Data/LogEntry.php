<?php

class LogEntry {

	var $time;

	var $action;

	var $data;

	function __construct($action, $data) {
		$this->time = microtime(true);
		$this->action = $action;
		$this->data = $data;
	}

	function __toString() {
		$floating = substr($this->time - floor($this->time), 2);	// cut 0 from 0.1
		$floating = substr($floating, 0, 4);
		return implode("\t", [
			date('H:i:s', $this->time).'.'.$floating,
			$this->action,
			$this->data ? substr(json_encode($this->data), 0, 100) : NULL
		]).BR;
	}

}
