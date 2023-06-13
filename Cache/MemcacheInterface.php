<?php

interface MemcacheInterface
{

	function get($key);

	function set($key, $val);

	function isValid($key = NULL, $expire = 0);

}
