<?php

class CLIResolver implements ResolverInterface
{

	public function getController()
	{
		$request = Request::getInstance();
		$argv = array_filter($_SERVER['argv'], static function ($el): bool {
			return !str_startsWith($el, '--');    // remove params
		});
		unset($argv[0]);  // path to the index.php file
//		pre_print_r($_SERVER['argv'], $argv);
		$controller = $argv !== [] ? first($argv) : null;
//		llog('ArgV', implode(' ', $_SERVER['argv']));
//		llog('Controller: ' . $controller);
		$request->setArray($request->parseParameters());
		return $controller;
	}

}
