<?php

class NadlibTest extends PHPUnit_Framework_TestCase {

	function test_requireAll() {
		//return;
		$skip = array(
		);
		require_once 'class.AppController.php';

		$files = glob('**/*');
		foreach ($files as $file) {
			if (preg_match('/class\..*\.php$/', $file)) {
				$class = trimExplode('.', basename($file));
				$class = $class[1];
				if (!in_array($class, $skip)) {
					echo $class."\n";
					require_once $file;
				}
			}
		}
		$this->assertTrue(true);
	}

}
