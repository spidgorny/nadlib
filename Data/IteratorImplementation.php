<?php

class IteratorImplementation
{

	public $data = [];

	private int $position = 0;

	public function rewind(): void
	{
		$this->position = 0;
	}

	public function current()
	{
		return $this->data[$this->position];
	}

	public function key(): int
	{
		return $this->position;
	}

	public function next(): void
	{
		++$this->position;
	}

	public function valid(): bool
	{
		return isset($this->data[$this->position]);
	}

}
