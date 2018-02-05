<?php

class MockRequest {

	var $log = [];

	function __call($function, array $args)
	{
		$this->log[] = (object)[
			'function' => $function,
			'args' => $args,
		];
	}

}
