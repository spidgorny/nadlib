<?php

class Date extends Time {
	const HUMAN = 'd.m.Y';
	const SYSTEM = 'Y-m-d';
	
	function __construct($input = NULL, $relativeTo = NULL) {
		parent::__construct($input, $relativeTo);
		$this->modify('Y-m-d \G\M\T');
		$this->updateDebug();
	}

	function getMySQL() {
		return gmdate('Y-m-d', $this->time);
	}

	function getISO() {
		return gmdate('Y-m-d', $this->time);
	}

	function updateDebug() {
		$this->debug = gmdate('Y-m-d H:i \G\M\T', $this->time);
		$this->human = $this->getHumanDateTime();
	}

}
