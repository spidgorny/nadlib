<?php

class SQLJoin
{

	protected $parts = [];

	public function __construct($join = null)
	{
		if (is_array($join)) {
			$this->parts = $join;
		} else {
			$this->parts[] = $join;
		}
	}

	public function add($join): void
	{
		if (!in_array($join, $this->parts)) {
			$this->parts[] = $join;
		}
	}

	public function __toString(): string
	{
		if ($this->parts) {
			return implode("\n", $this->parts);
		}
        
		return '';
	}

}
