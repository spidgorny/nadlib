<?php

namespace HTTP;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Uploader;
use UploadException;

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-08-03
 * Time: 22:28
 */
class UploaderTest extends \PHPUnit\Framework\TestCase
{

	public function test_GetPostedFiles_single(): void
	{
		$source = [
			'file' => [
				'name' => 'pocketshare_windows.bat',
				'type' => 'application/octet-stream',
				'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4C.tmp',
				'error' => 0,
				'size' => 255,
			],
		];
		$result = Uploader::GetPostedFiles($source);
		static::assertEquals($source, $result);
	}

	public function test_GetPostedFiles(): void
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
		$result = Uploader::GetPostedFiles($source);
//		debug($result);
		static::assertEquals($source, $result);
	}

	public function test_GetPostedFiles_on_broken(): void
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
		$result = Uploader::GetPostedFiles();
		static::assertEquals($must, $result);
	}

	public function test_moveUploadFly(): void
	{
		if (!class_exists(Filesystem::class)) {
			static::markTestSkipped('League\Flysystem\Filesystem not installed');
		}

		if (getenv('USER') === 'jenkins') {
			static::markTestSkipped('Fill fail when run from Jenkins anyway');
		}

		$u = new Uploader();
		$fly = new Filesystem(new InMemoryFilesystemAdapter());
		$_FILES['test'] = [
			'name' => 'desktop.png',
			'type' => 'application/octet-stream',
			'tmp_name' => __FILE__,
			'error' => 0,
			'size' => 282,
		];
		try {
			$result = $u->moveUploadFly('test', $fly, 'desktop.png');
			static::assertTrue($result);
		} catch (UploadException $uploadException) {
			static::fail($uploadException->getMessage());
		}
	}

}
