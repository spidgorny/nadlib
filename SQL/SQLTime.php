<?php

class SQLTime {
	/**
	 * @var Time
	 */
	protected $time;

	function __construct(Time $t) {
		$this->time = $t;
	}

	function __toString() {
		return $this->time->format('Y-m-d H:i:s');
	}

}
