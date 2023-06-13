<?php

namespace nadlib\HTTP;

interface SessionInterface
{

	function get($key, $default = null);

	function save($key, $val);

	function has($key);

	function getAll();

	function delete($key);

}
