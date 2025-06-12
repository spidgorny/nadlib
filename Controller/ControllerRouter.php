<?php

class ControllerRouter
{

	public function __construct(protected string $slug)
	{
	}

	public function getControllerName()
	{
		$class = $this->slug;
		if (class_exists($class)) {
			return $class;
		}

		$slugParts = explode('/', $class);
		$slugParts = array_reverse($slugParts);
		foreach ($slugParts as $class) {
//		llog(__METHOD__, $slugParts, $class, class_exists($class));
			if (class_exists($class)) {
				return $class;
			}
		}

		return null;
	}

	/**
	 * @param string $className
	 * @param Config $config
	 * @return object
	 * @throws ReflectionException
	 */
	public function makeControllerInstance(string $className, ConfigInterface $config): object
	{
//		llog('makeController', $class);
		// if you want to use DI, you should override this method
//		if (method_exists($this->config, 'getDI')) {
//			$di = $this->config->getDI();
//			$this->controller = $di->get($className);
//		} else {
		// v2

		// this does not supply DI arguments to the controller constructor
//		$this->controller = new $class();
		$ms = new MarshalParams($config);
		$instance = $ms->make($className);

//		llog($class, get_class($this->controller));
		if (method_exists($instance, 'postInit')) {
			$instance->postInit();
		}

		return $instance;
	}

}
