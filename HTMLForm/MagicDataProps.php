<?php

trait MagicDataProps
{

	public $data = [];

	public function __get($name)
	{
		return ifsetor($this->data[$name], null);
	}

	public function __set($name, $value)
	{
		return $this->data[$name] = $value;
	}

	public function __isset($name)
	{
		return $this->data[$name] ?? null;
	}

}
