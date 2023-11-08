<?php

class MemCacheRedis implements MemcacheInterface
{

	protected $predis;

	public function __construct(Predis\Client $predis)
	{
		$this->predis = $predis;
	}

	public function get($key)
	{
		return $this->predis->get($key);
	}

	public function set($key, $val)
	{
		return $this->predis->set($key, $val);
	}

	public function isValid($key = null, $expire = 0)
	{
		return $this->predis->ttl($key);
	}
}
