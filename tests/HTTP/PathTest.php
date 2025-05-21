<?php

namespace HTTP;

use Path;

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2016-01-23
 * Time: 01:10
 */
class PathTest extends \PHPUnit\Framework\TestCase
{

	public function test_Windows(): void
	{
		//$p = new Path(getcwd());
		$p = new Path("C:\\folderone\\two\\three");

		//debug($p->aPath, $p->sPath);
		static::assertGreaterThan(3, count($p->aPath));
		static::assertEquals('C:', $p->aPath[0]);
		static::assertStringStartsWith('C:/', $p . '');
	}

	public function test_cap_Windows(): void
	{
		$source = cap(getcwd());
		$p = new Path($source);
//		debug($p->aPath, $p->sPath);
		// all windows slash except last
		$source = str_replace('\\', '/', getcwd()) . '/';
		static::assertEquals($source, $p->implode());
	}

	public function test_isAbsolute(): void
	{
		$path = new Path('asd/qwe');
		static::assertFalse($path->isAbsolute());
		$path = new Path('dev-jobz/Topic/hyperledger');
		static::assertFalse($path->isAbsolute());
	}

	public function test_appRoot(): void
	{
		$al = AutoLoad::getInstance();
		$al->getAppRoot();
//		debug($appRoot . '');
//		$this->assertEquals(dirname(dirname(dirname(__FILE__))), $appRoot.'');
		static::markTestSkipped();
	}

	public function test_relativeFromAppRoot(): void
	{
		static::markTestSkipped(
			'Cannot work from nadlib as a standalone.'
		);

		//$source = 'components/jquery/jquery.js?1453328048';
		$source = 'components/bootstrap/less/bootstrap.js?1453328048';
		$path = new Path($source);
		$relative = $path->relativeFromAppRoot();
		//debug($relative.'');
		static::assertContains($relative . '', [
			'nadlib/' . $source,
			'vendor/spidgorny/nadlib/' . $source,
			'Users/DEPIDSVY/nadlib/' . $source,
			'tests/HTTP/' . $source,
		]);
	}

	public function test_append(): void
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('ftp'));
		static::assertEquals('/var/www/htdocs/ftp', $path . '');
	}

	public function test_append_capped(): void
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('ftp/'));
		static::assertEquals('/var/www/htdocs/ftp/', $path . '');
	}

	public function test_back_path(): void
	{
		$path = new Path('../ftp');
		static::assertEquals(['..', 'ftp'], $path->aPath);
	}

	public function test_append_back(): void
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('../ftp'));
		static::assertEquals('/var/www/ftp', $path . '');
	}

	public function test_append_back_twice(): void
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('../../ftp'));
		static::assertEquals('/var/ftp', $path . '');
	}

	public function test_remove(): void
	{
		$path = new Path('/var/www/htdocs/');
		$path->remove('/var/www');
		static::assertEquals('/htdocs/', $path . '');
	}

	public function test_setFile(): void
	{
		$path = new Path('xxx');
		$path->setFile('yyy');
		static::assertEquals('yyy', $path . '');
	}

	public function test_setFile_empty(): void
	{
		$path = new Path('xxx');
		$path->setFile('');
		static::assertEquals('', $path . '');
	}

}
