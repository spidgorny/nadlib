<?php

class Bytes {

	var $value;

	var $suffix = array(
		'b' => 'b',
		'k' => 'kb',
		'm' => 'mb',
		'g' => 'gb',
		't' => 'tb',
		'p' => 'pb',
	);

	var $precision = 3;

	function __construct($bytes) {
		$iBytes = (string)(int)$bytes;
		$sBytes = (string)$bytes;
		//echo $bytes, TAB, $iBytes, TAB, $sBytes, BR, $sBytes === $iBytes, BR;
		if ($sBytes === $iBytes) {
			$this->value = $bytes;
		} else {
			$this->value = $this->return_bytes($bytes);
		}
	}

	/**
	 * http://stackoverflow.com/a/1336624
	 * @param $val
	 * @return int|string
	 */
	static function return_bytes($val) {
		$val = trim($val);
		if (strlen($val)) {
			$last = strtolower($val[strlen($val) - 1]);
			$val = intval($val);
			switch ($last) {
				// The 'G' modifier is available since PHP 5.1.0
				case 'g':
					$val *= 1024 * 1024 * 1024;
					break;
				case 'm':
					$val *= 1024 * 1024;
					break;
				case 'k':
					$val *= 1024;
					break;
			}
		}

		return $val;
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

	public function getBytes() {
		return $this->value;
	}

}
