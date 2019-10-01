<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2016-01-23
 * Time: 01:10
 */
class PathTest extends PHPUnit_Framework_TestCase
{

	function test_Windows()
	{
		$p = new Path(getcwd());
		//debug($p->aPath, $p->sPath);
		$this->assertGreaterThan(3, sizeof($p->aPath));
		$this->assertEquals('C:', $p->aPath[0]);
		$this->assertStringStartsWith('C:/', $p . '');
	}

	function test_cap_Windows()
	{
		$source = cap(getcwd());
		$p = new Path($source);
//		debug($p->aPath, $p->sPath);
		// all windows slash except last
		$source = str_replace('\\', '/', getcwd()) . '/';
		$this->assertEquals($source, $p->implode());
	}

	function test_isAbsolute()
	{
		$path = new Path('asd/qwe');
		$this->assertFalse($path->isAbsolute());
		$path = new Path('dev-jobz/Topic/hyperledger');
		$this->assertFalse($path->isAbsolute());
	}

	function test_appRoot()
	{
		$al = AutoLoad::getInstance();
		$appRoot = $al->getAppRoot();
		//debug($appRoot . '');
		$this->assertStringEndsWith(basename(getcwd()) . '/', $appRoot.'');
	}

	function test_relativeFromAppRoot()
	{
		$this->markTestSkipped();
		//$source = 'components/jquery/jquery.js?1453328048';
		$source = 'components/bootstrap/less/bootstrap.js?1453328048';
		$path = new Path($source);
		$relative = $path->relativeFromAppRoot();
		debug($relative.'');
		$this->assertContains($relative . '', [
			'nadlib/' . $source,
			'vendor/spidgorny/nadlib/' . $source,
			'Users/DEPIDSVY/nadlib/' . $source,
			'tests/HTTP/' . $source,
		]);
	}

	function test_append()
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('ftp'));
		$this->assertEquals('/var/www/htdocs/ftp', $path . '');
	}

	function test_append_capped()
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('ftp/'));
		$this->assertEquals('/var/www/htdocs/ftp/', $path . '');
	}

	function test_back_path()
	{
		$path = new Path('../ftp');
		$this->assertEquals(['..', 'ftp'], $path->aPath);
	}

	function test_append_back()
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('../ftp'));
		$this->assertEquals('/var/www/ftp', $path . '');
	}

	function test_append_back_twice()
	{
		$path = new Path('/var/www/htdocs/');
		$path->append(new Path('../../ftp'));
		$this->assertEquals('/var/ftp', $path . '');
	}

	function test_remove()
	{
		$path = new Path('/var/www/htdocs/');
		$path->remove('/var/www');
		$this->assertEquals('/htdocs/', $path . '');
	}

}
