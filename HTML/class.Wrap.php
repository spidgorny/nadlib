<?php

class Wrap {
	protected $wrap1, $wrap2;

	function  __construct($strWrap, $arrWrap2 = NULL) {
		if ($arrWrap2) {
			$this->wrap1 = $strWrap;
			$this->wrap2 = $arrWrap2;
		} else {
			list($this->wrap1, $this->wrap2) = explode('|', $strWrap);
		}
	}

	function __toString() {
		return 'Wrap Object';
	}

	function wrap($str) {
		return $this->wrap1.$str.$this->wrap2;
	}

}
