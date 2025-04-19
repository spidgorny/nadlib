<?php

namespace nadlib\Test;

use Request;

class MockRequest extends Request
{

	public static function getLocation($isUTF8 = false): string
	{
		return 'http://mock.request.tld/some/folder/';
	}

}
