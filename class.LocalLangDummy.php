<?php

class LocalLangDummy {

	function T($name) {
		return $name;
	}

}

function __($s) {
	return $s;
}

