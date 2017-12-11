<?php

namespace nadlib;

class Service {

	/**
	 * @var IndexInterface
	 */
	var $index;

	function __construct(IndexInterface $index)
	{
		$this->index = $index;
	}

}
