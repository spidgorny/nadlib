<?php

namespace nadlib\Test;

class MockRequest extends \Request
{

	public static function getLocation($isUTF8 = false)
	{
		return 'http://mock.request.tld/some/folder/';
	}

}
