<?php

class PathResolver implements ResolverInterface {

	/**
	 * @var Request
	 */
	var $request;

	function __construct()
	{
		$this->request = Request::getInstance();
	}

	function getController($returnDefault = true)
	{
		$levels = $this->request->getURLLevels();
//		debug($levels);
		if ($levels) {
			$levels = array_reverse($levels);
			$last = NULL;
			foreach ($levels as $class) {
				// RewriteRule should not contain "?c="
				nodebug(
					$class,
					class_exists($class . 'Controller'),
					class_exists($class));
				// to simplify URL it first searches for the corresponding controller
				if ($class && class_exists($class . 'Controller')) {    // this is untested
					$last = $class . 'Controller';
					break;
				}
				if (class_exists($class)) {
					$last = $class;
					break;
				}
			}    // foreach
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
			$controller = NULL;
		}
		return $controller;
	}

}
