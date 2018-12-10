<?php

/**
 * Class MarshalParams
 * Poor programmers' dependency injection solution
 * Will check the reflection of the class to see which parameters need to be injected
 */
class MarshalParams
{

	/**
	 * @var object
	 * This is an object with functions which require DI or
	 * Container should have functions starting with 'get' and the class name
	 */
	public $object;

	/**
	 * @var Request
	 */
	public $request;

	public function __construct($object)
	{
		$this->object = $object;
		$this->request = Request::getInstance();
	}

	/**
	 * @param $class
	 * @return object
	 * @throws ReflectionException
	 */
	public function make($class)
	{
		return self::makeInstanceWithInjection($class, $this->object);
	}

	/**
	 * @param $class
	 * @param $container
	 * @return object
	 * @throws ReflectionException
	 */
	public static function makeInstanceWithInjection($class, $container)
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

	/**
	 * @param $container
	 * @param $constructor
	 * @return array
	 * @throws ReflectionException
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
				if (method_exists($param, 'getType')) {
					$type = $param->getType();
				} else {
					$type = $param->getClass()->name;
				}
				if ($type) {
					if (!is_string($type) && $type->isBuiltin()) {
						$init[$name] = $param->getDefaultValue();
					} else {
						$typeClass = method_exists($type, 'getName')
							? $type->getName()
							: $type . '';
						$typeGenerator = 'get' . $typeClass;
//						debug($typeClass, get_class($container), $typeGenerator);
						if (method_exists($container, $typeGenerator)) {
							$init[$name] = call_user_func([$container, $typeGenerator]);
						} else {
							// build the dependency
							$init[$name] = self::makeInstanceWithInjection($typeClass, $container);
						}
					}
				} else {
					$init[$name] = null;
				}
			}
		}
		return $init;
	}

	/**
	 * @param $method
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function call($method)
	{
		return $this->callMethodByReflection($this->object, $method);
	}

	/**
	 * Will detect parameter types and call getInstance() or new $class
	 * @param $proxy
	 * @param $method
	 * @return mixed
	 * @throws ReflectionException
	 */
	private function callMethodByReflection($proxy, $method)
	{
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
					$assoc[$name] = null;
				}
			}
			//debug($assoc);
			$content = call_user_func_array(array($proxy, $method), $assoc);
		} else {
			$content = $proxy->$method();
		}
		return $content;
	}

	public function getParameterByReflection(ReflectionParameter $param)
	{
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
					$obj = new $paramClass(/*$assoc[$name]*/);
					$return = $obj;
				}
			}
		}
		return $return;
	}

}
