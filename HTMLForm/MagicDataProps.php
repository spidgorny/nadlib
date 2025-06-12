<?php

trait MagicDataProps
{

	public $data = [];

	public function __get($name): mixed
	{
		return ifsetor($this->data[$name], null);
	}

	public function __set($name, $value): void
	{
		$this->data[$name] = $value;
	}

	public function __isset($name): bool
	{
		return $this->data[$name] ?? false;
	}

}
