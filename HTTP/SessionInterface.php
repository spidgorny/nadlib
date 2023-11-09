<?php

namespace nadlib\HTTP;

interface SessionInterface
{

	public function get($key);

	public function save($key, $val);

	public function has($key);

	public function getAll();

	public function delete($key);

}
