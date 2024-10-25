<?php

trait DirectDataAccess
{

	public $data = [];

	public function __get(mixed $key): mixed
	{
		return $this->data[$key] ?? null;
	}

	public function __set(mixed $key, $val): mixed
	{
		$this->data[$key] = $val;
	}

	public function __isset(mixed $key): bool
	{
		return (bool)$this->data[$key];
	}

}
