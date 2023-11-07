<?php

if (!function_exists('get_overriden_methods')) {

	/**
	 * http://djomla.blog.com/2011/02/16/php-versions-5-2-and-5-3-get_called_class/
	 */
	if (!function_exists('get_called_class')) {
		function get_called_class($bt = false, $l = 1)
		{
			if (!$bt) $bt = debug_backtrace();
			if (!isset($bt[$l])) throw new Exception("Cannot find called class -> stack level too deep.");
			if (!isset($bt[$l]['type'])) {
				throw new Exception ('type not set');
			} else switch ($bt[$l]['type']) {
				case '::':
					$lines = file($bt[$l]['file']);
					$i = 0;
					$callerLine = '';
					do {
						$i++;
						$callerLine = $lines[$bt[$l]['line'] - $i] . $callerLine;
						$findLine = stripos($callerLine, $bt[$l]['function']);
					} while ($callerLine && $findLine === false);
					$callerLine = $lines[$bt[$l]['line'] - $i] . $callerLine;
					preg_match('/([a-zA-Z0-9\_]+)::' . $bt[$l]['function'] . '/',
						$callerLine,
						$matches);
					if (!isset($matches[1])) {
						// must be an edge case.
						throw new RuntimeException("Could not find caller class: originating method call is obscured.");
					}
					switch ($matches[1]) {
						case 'self':
						case 'parent':
							// phpstan-ignore-next-line
							return get_called_class($bt, $l + 1);
						default:
							return $matches[1];
					}
				// won't get here.
				case '->':
					switch ($bt[$l]['function']) {
						case '__get':
							// edge case -> get class of calling object
							if (!is_object($bt[$l]['object'])) {
								throw new RuntimeException("Edge case fail. __get called on non object.");
							}
							return get_class($bt[$l]['object']);
						default:
							return $bt[$l]['class'];
					}

				default:
					throw new RuntimeException("Unknown backtrace method type");
			}
		}
	}

	/**
	 * http://www.php.net/manual/en/function.get-class-methods.php
	 * @param string $class
	 * @return array|null
	 * @throws ReflectionException
	 */
	function get_overriden_methods($class)
	{
		$rClass = new ReflectionClass($class);
		$array = null;

		foreach ($rClass->getMethods() as $rMethod) {
			try {
				// attempt to find method in parent class
				new ReflectionMethod($rClass->getParentClass()->getName(),
					$rMethod->getName());
				// check whether method is explicitly defined in this class
				if ($rMethod->getDeclaringClass()->getName()
					== $rClass->getName()
				) {
					// if so, then it is overriden, so add to array
					$array[] = $rMethod->getName();
				}
			} catch (Exception $e) { /* was not in parent class! */
			}
		}

		return $array;
	}

}
