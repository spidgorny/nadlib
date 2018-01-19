<?php

class MarshalParams {

	var $object;

	/**
	 * @var Request
	 */
	var $request;

	function __construct($object) {
		$this->object = $object;
		$this->request = Request::getInstance();
	}

	/**
	 * @param $container
	 * @param $constructor
	 * @return array
	 */
	public static function getFunctionArguments($container, ReflectionMethod $constructor)
	{
		$init = []; // parameter values to the constructor
		$params = $constructor->getParameters();
		foreach ($params as $param) {
			$name = $param->getName();
			if ($param->isArray() || $param->isDefaultValueAvailable()) {
				$init[$name] = $param->getDefaultValue();
			} else {
				$type = $param->getType();
				if ($type) {
					if ($type->isBuiltin()) {
						$init[$name] = $param->getDefaultValue();
					} else {
						$typeClass = method_exists($type, 'getName')
							? $type->getName()
							: $type->__toString();
						//					debug($typeClass);
						$init[$name] = call_user_func([$container, 'get' . $typeClass]);
					}
				} else {
					$init[$name] = null;
				}
			}
		}
		return $init;
	}

	function call($method) {
		return $this->callMethodByReflection($this->object, $method);
	}

	/**
	 * Will detect parameter types and call getInstance() or new $class
	 * @param $proxy
	 * @param $method
	 * @return mixed
	 */
	private function callMethodByReflection($proxy, $method) {
		$r = new ReflectionMethod($proxy, $method);
		if ($r->getNumberOfParameters()) {
			$assoc = array();
			foreach ($r->getParameters() as $param) {
				$name = $param->getName();
				if ($this->request->is_set($name)) {
					$assoc[$name] = $this->getParameterByReflection($param);
				} elseif ($param->isDefaultValueAvailable()) {
					$assoc[$name] = $param->getDefaultValue();
				} else {
					$assoc[$name] = NULL;
				}
			}
			//debug($assoc);
			$content = call_user_func_array(array($proxy, $method), $assoc);
		} else {
			$content = $proxy->$method();
		}
		return $content;
	}

	function getParameterByReflection(ReflectionParameter $param) {
		$name = $param->getName();
		if ($param->isArray()) {
			$return = $this->request->getArray($name);
		} else {
			$return = $this->request->getTrim($name);
			$paramClassRef = $param->getClass();
			//debug($param->getPosition(), $paramClassRef, $paramClassRef->getName());
			if ($paramClassRef && class_exists($paramClassRef->getName())) {
				$paramClass = $paramClassRef->getName();
//				debug($param->getPosition(), $paramClass,
//				method_exists($paramClass, 'getInstance'));
				if (method_exists($paramClass, 'getInstance')) {
					$obj = $paramClass::getInstance($return);
					$return = $obj;
				} else {
					$obj = new $paramClass($assoc[$name]);
					$return = $obj;
				}
			}
		}
		return $return;
	}

	static function makeInstanceWithInjection($class, $container)
	{
		$cr = new ReflectionClass($class);
		$constructor = $cr->getConstructor();
		if ($constructor) {
			$init = self::getFunctionArguments($container, $constructor);
//			debug($class, $constructor->getName(), $init);
			// PHP 7
			//$instance = new $class(...$init);
			$reflector = new ReflectionClass($class);
			$instance = $reflector->newInstanceArgs($init);
		} else {
			$instance = new $class();
		}
		return $instance;
	}

}
