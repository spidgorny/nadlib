<?php

class MockRequest
{

	public $log = [];

	public function __call($function, array $args)
	{
		$this->log[] = (object)[
			'function' => $function,
			'args' => $args,
		];
	}

}
