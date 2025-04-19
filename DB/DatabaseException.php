<?php

class DatabaseException extends Exception
{

	public $query;

	public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, $query = null)
	{
		parent::__construct($message, $code, $previous);
		$this->setQuery($query);
	}

	public function setQuery($q): void
	{
		$this->query = $q;
	}

	public function getQuery()
	{
		return $this->query;
	}

}
