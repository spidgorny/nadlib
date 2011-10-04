<?php

/**
 * Use $content instanceof htmlString ? $content : htmlspecialchars($content);
 *
 */
class htmlString {
	protected $value = '';

	function __construct($input) {
		$this->value = $input;
	}

	function __toString() {
		return $this->value.'';
	}

}