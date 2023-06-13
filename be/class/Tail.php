<?php

class Tail extends AppController {

	function render() {
		$log = ini_get('error_log');
		if (!$log) {
			$log = '/var/log/apache2/error_log';
			$log = is_file($log) ? $log : NULL;
		}
		if (!$log) {
			$log = 'z:\\.sys\\apache2\\logs\\error.log';
			echo $log, BR;
			$log = is_file($log) ? $log : NULL;
		}
		echo 'log: ', $log, BR;
		if ($log) {
			passthru('tail -f ' . $log);
		}
	}

}
