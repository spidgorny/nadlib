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
		return json_decode($this->predis->get($key), false, 512 /*JSON_THROW_ON_ERROR*/);
	}

	public function set($key, $val)
	{
		return $this->predis->set(json_encode($key), $val);
	}

	public function isValid($key = null, $expire = 0)
	{
		return $this->predis->ttl($key);
	}
}
