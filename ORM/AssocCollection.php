<?php

class AssocCollection
{

	/**
     * @var mixed[]
     */
    public $data;

	public function __construct(array $data = [])
	{
		$this->data = $data;
	}

}
