<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 22.12.2015
 * Time: 13:37
 */
require_once 'init.php';

class MailerTest extends PHPUnit_Framework_TestCase {

	function test_getShortFilename() {
		$sut = new Mailer('spidgorny@gmail.com', 'test', 'test');
		$filename = './request/97777/RP Nintendo EA3P40 The Legend of Zelda Tri Force Heroes Checklists.zip';
		$short = $sut->getShortFilename($filename);
		$this->assertEquals('RP Nintendo EA3P40 The Legend of Zelda Tri Force Heroes Che.zip', $short);
	}

}

$mt = new MailerTest();
$mt->test_getShortFilename();
