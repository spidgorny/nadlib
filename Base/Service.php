<?php

namespace nadlib;

class Service
{

	protected \nadlib\IndexInterface $index;

	public function __construct(IndexInterface $index)
	{
		$this->index = $index;
	}

}
