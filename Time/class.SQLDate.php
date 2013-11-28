<?php

class SQLDate extends Date {

	function __toString() {
		return $this->format('Y-m-d');
	}

}