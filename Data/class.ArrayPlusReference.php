<?php

$tmp = error_reporting(error_reporting() ^ E_STRICT);

class ArrayPlusReference extends ArrayPlus
{

	function __construct(array &$a = array())
	{
		$this->setData($a);
	}

	static function create(array &$data = array())
	{
		$self = new self($data);
		return $self;
	}

	/**
	 * @return array
	 */
	function &getData()
	{
		return (array)$this;
	}

}

error_reporting($tmp);

function APR(array &$a = array())
{
	return ArrayPlusReference::create($a);
}
