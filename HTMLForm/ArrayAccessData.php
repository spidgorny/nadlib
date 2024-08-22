<?php

trait ArrayAccessData
{

	public $data = [];

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	/**
	 * ifsetor() here will not work:
	 * Only variable references should be returned by reference
	 * @param mixed $offset
	 * @return mixed
	 */
	public function &offsetGet($offset)
	{
		$ref = $this->data[$offset] ?? null;
		return $ref;
	}

	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}

	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}

}
