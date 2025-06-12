<?php

interface HasGetter
{

	public function get(string $name, ...$args): mixed;

}
