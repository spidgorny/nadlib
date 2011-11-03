<?php

class SQLSelect {
	protected $parts = array();

	function __construct($parts) {
		if (is_array($parts)) {
			$this->parts = $parts;
		} else if ($parts) {
			$this->parts[] = $parts;
		} else {
			$this->parts[] = '*';
		}
	}

	function __toString() {
		return implode(', ', $this->parts);
	}

}