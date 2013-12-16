<?php

class SQLDateTime extends Time {

	function __toString() {
		return $this->format('Y-m-d H:i:s');
	}

}