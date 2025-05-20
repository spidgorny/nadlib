<?php

class NadlibTest extends AppDev\OnlineRequestSystem\Framework\TestCase
{

	public function test_requireAll(): void
	{

		$this->markTestSkipped(
			'AppController was not found. Line 29.'
		);

		//return;
		$skip = [
			'MemcacheMemory',
			'DBInterface',
			'SQLQuery',
		];
		require_once __DIR__ . '/AppController4Test.php';
//		class_alias('AppController4Test', 'AppController');

		$files = glob('**/*');
		foreach ($files as $file) {
			if (preg_match('/class\..*\.php$/', $file)) {
				$class = trimExplode('.', basename($file));
				$class = $class[1];
				//echo $class."\n";
                if (!in_array($class, $skip) && !class_exists($class, false)) {
					/** @noinspection PhpIncludeInspection */
                    require_once $file;
				}
			}
		}

		$this->assertTrue(true);
	}

}
