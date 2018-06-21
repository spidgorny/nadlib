<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-08-03
 * Time: 22:28
 */


class UploaderTest extends PHPUnit_Framework_TestCase
{

	function test_GetPostedFiles()
	{
		$source = [
			'files' => [
				0 => [
					'name' => 'desktop.ini',
					'type' => 'application/octet-stream',
					'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4B.tmp',
					'error' => 0,
					'size' => 282,
				],
				1 => [
					'name' => 'pocketshare_windows.bat',
					'type' => 'application/octet-stream',
					'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4C.tmp',
					'error' => 0,
					'size' => 255,
				],
			],
		];
		$_FILES = $source;
		$u = new Uploader();
		$result = $u->GetPostedFiles();
		var_export($result);
		$this->assertEquals($source, $result);
	}

	function test_GetPostedFiles_on_broken()
	{
		$source = [
			'files' => [
				'name' => [
					0 => 'desktop.ini',
					1 => 'pocketshare_windows.bat',
				],
				'type' => [
					0 => 'application/octet-stream',
					1 => 'application/octet-stream',
				],
				'tmp_name' => [
					0 => 'C:\\wamp\\vdrive\\.tmp\\phpDF4B.tmp',
					1 => 'C:\\wamp\\vdrive\\.tmp\\phpDF4C.tmp',
				],
				'error' => [
					0 => 0,
					1 => 0,
				],
				'size' => [
					0 => 282,
					1 => 255,
				],
			],
		];
		$must = [
			'files' => [
				0 => [
					'name' => 'desktop.ini',
					'type' => 'application/octet-stream',
					'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4B.tmp',
					'error' => 0,
					'size' => 282,
				],
				1 => [
					'name' => 'pocketshare_windows.bat',
					'type' => 'application/octet-stream',
					'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4C.tmp',
					'error' => 0,
					'size' => 255,
				],
			],
		];
		$_FILES = $source;
		$u = new Uploader();
		$result = $u->GetPostedFiles();
		$this->assertEquals($must, $result);
	}

}
