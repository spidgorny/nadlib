<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2016-01-23
 * Time: 01:10
 */
class PathTest extends PHPUnit_Framework_TestCase
{

	function test_relativeFromAppRoot()
	{
		//$source = 'components/jquery/jquery.js?1453328048';
		$source = 'components/bootstrap/less/bootstrap.js?1453328048';
		$path = new Path($source);
		$relative = $path->relativeFromAppRoot();
		//debug($relative.'');
		$this->assertTrue(in_array($relative . '', [
			'nadlib/' . $source,
			'vendor/spidgorny/nadlib/' . $source,
			'Users/DEPIDSVY/nadlib/' . $source,
		]));
	}

}
