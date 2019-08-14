<?php

/**
 * Class DIContainer
 * http://fabien.potencier.org/article/17/on-php-5-3-lambda-functions-and-closures
 */
class DIContainer
{

	protected $values = array();

	function __set($id, $value)
	{
		$this->values[$id] = $value;
	}

	function __get($id)
	{
		if (!isset($this->values[$id])) {
			throw new InvalidArgumentException(sprintf('Value "%s" is not defined.', $id));
		}
		$v = $this->values[$id];
		/*		print_r(array(
					'id' => $id,
					'type' => gettype($v),
					'callable' => is_callable($v),
					'object' => is_object($v),
				)); echo BR;*/

		return is_callable($v) && is_object($v)
			? $v($this)
			: $v;
	}

	function asShared($callable)
	{
		return function ($c) use ($callable) {
			static $object;

			if (is_null($object)) {
				$object = $callable($c);
			}

			return $object;
		};
	}

}
