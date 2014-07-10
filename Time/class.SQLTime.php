<?php

/**
 * Class SQLTime makes sure that time objects is rendered into SQL compatible time string
 */

class SQLTime extends Time {

	function __toString() {
		return date('Y-m-d H:i:s', $this->time);
	}

}
