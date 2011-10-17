<?php

class Duration extends Time {

	function  __construct($input = NULL) {
		if ($input instanceof Time) {
			$this->time = $input->time;
			$this->updateDebug();
		} else {
			parent::__construct($input.' GMT', 0);
		}
	}

	function __toString() {
		return floor($this->time / 3600/24).gmdate('\d H:i:s', $this->time).' ('.$this->time.')';
	}

	function format() {
		die(__METHOD__.' - don\'t use.');
	}

	function nice() {
		$h = floor($this->time / 3600);
		$m = floor($this->time % 3600 / 60);
		$content = array();
		if ($h) {
			$content[] = $h . 'h';
		}
		if ($m) {
			$content[] = $m . 'm';
		}
		$content = implode('&nbsp;', $content);
		$content = $content ? $content : '-';
		return $content;
	}

}
