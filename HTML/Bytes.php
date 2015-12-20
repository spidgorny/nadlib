<?php

class Bytes {

	var $value;

	var $suffix = [
		'b' => 'b',
		'k' => 'kb',
		'm' => 'mb',
		'g' => 'gb',
		't' => 'tb',
		'p' => 'pb',
	];

	var $precision = 3;

	function __construct($bytes) {
		$this->value = $bytes;
	}

	function __toString() {
		return $this->renderDynamic();
	}

	function renderDynamic() {
		if ($this->value < 1024) {
			return $this->value . $this->suffix['b'];
		} elseif ($this->value > 1024*1024*1024) {
			return round($this->value / 1024/1024/1024, $this->precision) . $this->suffix['g'];
		} elseif ($this->value > 1024*1024) {
			return round($this->value / 1024/1024, $this->precision) . $this->suffix['m'];
		} elseif ($this->value > 1024) {
			return round($this->value / 1024, $this->precision) . $this->suffix['k'];
		}
		return '?';
	}

}
