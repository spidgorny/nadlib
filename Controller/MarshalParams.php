<?php

class MarshalParams
{

	public $object;

	/**
	 * @var Request
	 */
	public $request;

	function __construct($object)
	{
		$this->object = $object;
		$this->request = Request::getInstance();
	}

	function call($method)
	{
		return $this->callMethodByReflection($this->object, $method);
	}

	/**
	 * Will detect parameter types and call getInstance() or new $class
	 * @param $proxy
	 * @param $method
	 * @return mixed
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

	function getParameterByReflection(ReflectionParameter $param)
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
					$obj = new $paramClass($assoc[$name]);
					$return = $obj;
				}
			}
		}
		return $return;
	}

}
