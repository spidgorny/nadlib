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
		//$db = Config::getInstance()->db;
		//echo __METHOD__.' '.$db->getCaller(3).' '.$this->__toString().'<br />'."\n";
	}

	function __toString() {
		return 'Wrap Object ('.strlen($this->wrap1).', '.strlen($this->wrap2).')';
	}

	function debug() {
		return $this->__toString();
	}

	function wrap($str) {
		return $this->wrap1.$str.$this->wrap2;
	}

	/**
	 * @param $w1
	 * @param $w2
	 * @return Wrap
	 */
	static function make($w1, $w2 = '') {
		return new self($w1, $w2);
	}

}
