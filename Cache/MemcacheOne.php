<?php

class MemcacheOne
{

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var int
	 */
	protected $expires;

	protected \MemcacheFile $mf;

	/**
	 * @var mixed
	 */
	protected $value;

	public function __construct($key, $expires = 3600)
	{
		$this->key = $key;
		$this->expires = $expires;
		$this->mf = new MemcacheFile();
		$this->value = $this->mf->get($this->key, $this->expires);
	}

	public function is_Set(): bool
	{
		return (bool) $this->value;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function set($newValue): void
	{
		$this->mf->set($this->key, $newValue);
		$this->value = $newValue;
	}

	public function getAge(): \Duration
	{
		return $this->mf->getAge($this->key);
	}

	public function map(): string
	{
		return $this->mf->map($this->key);
	}

}
