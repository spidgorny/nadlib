<?php

class CLIResolver implements ResolverInterface
{

	function getController()
	{
		$request = Request::getInstance();
		$controller = ifsetor($_SERVER['argv'][1]);
		echo 'Controller: ' . $controller, BR;
		$request->setArray($request->parseParameters());
		return $controller;
	}

}
