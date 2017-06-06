<?php

class CLILogger {

	function log($method, $data) {
		if (is_array($data)) {
			$output = print_r($data, true);
		} elseif (is_scalar($data)) {
			$output = '<'.gettype($data).'> '.$data;
		} elseif (is_object($data)) {
			$output = get_class($data);
		} else {
			$output = json_encode($data);
		}
		echo '[', $method, '] ', $output, BR;
	}

}
