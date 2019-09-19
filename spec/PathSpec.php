<?php

namespace spec;

use Path as Path;
use PhpSpec\ObjectBehavior;

class PathSpec extends ObjectBehavior
{

	function __construct()
	{
		require_once __DIR__ . '/../init.php';
		require_once __DIR__ . '/../HTTP/Path.php';
		require_once __DIR__ . '/../HTTP/Request.php';
	}

	function it_is_initializable()
	{
		$this->beConstructedWith(new Path(getcwd()));
		$this->shouldHaveType(Path::class);
	}

	function it_normalizes_home_page()
	{
		define('DEVELOPMENT', true);
		$this->beConstructedWith(new Path('/~depidsvy/floorplan/details'));
		$this->normalizeHomePage();
		$this->__toString()->shouldBe('depidsvy/public_html/floorplan/details');
	}

	function it_normalizes_home_page_2()
	{
		define('DEVELOPMENT', true);
		$this->beConstructedWith('/srv/www/htdocs/~depidsvy/floorplan/details/AboutAccessRights');
		$this->normalizeHomePage();
		$this->__toString()->shouldBe('depidsvy/public_html/floorplan/details');
	}

}
