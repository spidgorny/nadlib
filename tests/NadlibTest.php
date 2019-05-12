<?php

class NadlibTest extends PHPUnit\Framework\TestCase
{

	function test_requireAll()
	{

		$this->markTestIncomplete(
			'AppController was not found. Line 29.'
		);

		//return;
		$skip = array(
			'MemcacheMemory',
			'DBInterface',
			'SQLQuery',
		);
		require_once 'AppController4Test.php';
//		class_alias('AppController4Test', 'AppController');

		$files = glob('**/*');
		foreach ($files as $file) {
			if (preg_match('/class\..*\.php$/', $file)) {
				$class = trimExplode('.', basename($file));
				$class = $class[1];
				if (!in_array($class, $skip)) {
					//echo $class."\n";
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
