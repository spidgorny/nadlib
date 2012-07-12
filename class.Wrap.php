<?php

class Wrap {
	protected $wrap;	// string with |

	function  __construct($strWrap, $arrWrap2 = NULL) {
		if ($arrWrap2) {
			$this->wrap = $strWrap.'|'.$arrWrap2;
		} else {
			$this->wrap = $strWrap;
		}
	}

	function __toString() {
		return 'Wrap Object';
	}

	function wrap($str) {
		return str_replace('|', $str, $this->wrap);
	}

}
