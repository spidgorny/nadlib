<?php

class CLIResolver implements ResolverInterface
{

	function getController()
	{
		$request = Request::getInstance();
		$argv = array_filter($_SERVER['argv'], function ($el) {
			return !str_startsWith($el, '--');    // remove params
		});
		unset($argv[0]);	// path to the index.php file
//		pre_print_r($_SERVER['argv'], $argv);
		$controller = sizeof($argv) ? first($argv) : null;
		if (DEVELOPMENT && $controller) {
//			echo 'ArgV: ', implode(' ', $_SERVER['argv']);
//			echo 'Controller: ' . $controller, BR;
		}
		$request->setArray($request->parseParameters());
		return $controller;
	}

}
