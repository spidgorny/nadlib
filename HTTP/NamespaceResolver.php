<?php

class NamespaceResolver implements ResolverInterface
{

	/**
	 * @var Request
	 */
	var $request;

	function __construct(array $tryNS = [])
	{
		$this->request = Request::getInstance();
		$this->ns = $tryNS;
	}

	function getController($returnDefault = true)
	{
		$levels = $this->request->getURLLevels();
		if ($levels) {
			$levels = array_reverse($levels);
			$last = null;
			foreach ($levels as $class) {
				$last = $this->tryNamespaces($class);
				if ($last) {
					break;
				}
			}
			if ($last) {
				$controller = $last;
			} else {
				$controller = $this->getDefault($returnDefault);
			}
		} else {
			$controller = $this->getDefault($returnDefault);
		}
		return $controller;
	}

	function getDefault($returnDefault)
	{
		if ($returnDefault && class_exists('Config')) {
			// not good as we never get 404
			$controller = Config::getInstance()->defaultController;
		} else {
			$controller = null;
		}
		return $controller;
	}

	function tryNamespaces($class)
	{
		$last = null;
		foreach ($this->ns as $prefix) {
			$classWithNS = $prefix . $class;
			if (class_exists($classWithNS)) {
				$last = $classWithNS;
				break;
			}
		}
		return $last;
	}

}
