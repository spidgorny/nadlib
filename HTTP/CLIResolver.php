<?php

class CLIResolver implements ResolverInterface {

	function getController() {
		$request = Request::getInstance();
		$argv = array_filter($_SERVER['argv'], function ($el) {
			return !str_startsWith($el, '--');	// remove params
		});
		$controller = sizeof($argv) ? first($argv) : null;
		if (DEVELOPMENT && $controller) {
//			echo 'ArgV: ', implode(' ', $_SERVER['argv']);
//			echo 'Controller: ' . $controller, BR;
		}
		$request->setArray($request->parseParameters());
		return $controller;
	}

}
