<?php

class NadlibTest extends PHPUnit_Framework_TestCase {

	function test_requireAll() {
		//return;
		$skip = array(
			'MemcacheMemory',
		);
		require_once 'class.AppController4Test.php';
		class_alias('AppController4Test', 'AppController');

		$files = glob('**/*');
		foreach ($files as $file) {
			if (preg_match('/class\..*\.php$/', $file)) {
				$class = trimExplode('.', basename($file));
				$class = $class[1];
				if (!in_array($class, $skip)) {
					echo $class."\n";
					if (!class_exists($class, false)) {
						/** @noinspection PhpIncludeInspection */
						require_once $file;
					}
				}
			}
		}
		$this->assertTrue(true);
	}

}
