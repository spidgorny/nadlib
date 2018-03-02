<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 06.04.2017
 * Time: 14:34
 */

namespace nadlib\Base;

use AppRootDetector;
use Request;

class AppRootDetectorTest extends \PHPUnit_Framework_TestCase {

	function test_run() {
		echo 'isCLI: ', Request::isCLI(), BR;
		echo 'cwd: ', getcwd(), BR;
		echo 'dirname(cwd): ', dirname(getcwd()), BR;
		echo 'dirname(dirname(cwd)): ', dirname(dirname(getcwd())), BR;
		$ad = new AppRootDetector();
		echo 'AppRoot: ', $ad->get().'', BR;
		$this->assertContains('vendor/spidgorny/nadlib', $ad->get().'');
	}

}
