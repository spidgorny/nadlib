<?php

/**
 * Class ArrayPlusReference
 * @mixin ArrayPlus
 */
class ArrayPlusReference /*extends ArrayPlus */
{

	public function __construct(array &$a = [])
	{
		$this->setData($a);
	}

	public static function create(array &$data = []): self
	{
		return new self($data);
	}

	public function &getData(): array
	{
		return (array)$this;
	}

}

function APR(array &$a = []): \ArrayPlusReference
{
	return ArrayPlusReference::create($a);
}
