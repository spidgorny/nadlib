<?php

trait ArrayAccessData
{

	public $data = [];

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->data[$offset]);
	}

	/**
	 * ifsetor() here will not work:
	 * Only variable references should be returned by reference
	 */
	public function &offsetGet(mixed $offset): mixed
	{
		$ref = $this->data[$offset] ?? null;
		return $ref;
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (is_null($offset)) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->data[$offset]);
	}

}
