<?php

namespace nadlib\Test;

use Request;
use spidgorny\nadlib\HTTP\URL;

class MockRequest extends Request
{

	public static function getLocation($isUTF8 = false): URL
	{
		return URL::from('http://mock.request.tld/some/folder/');
	}

}
