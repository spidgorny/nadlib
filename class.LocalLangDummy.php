<?php

class LocalLangDummy {

	function T($name) {
		return $name;
	}

}

function __($code, $r1 = null, $r2 = null, $r3 = null) {
	return Index::getInstance()->ll->T($code, $r1, $r2, $r3);
}
