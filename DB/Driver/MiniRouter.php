<?php

use spidgorny\nadlib\HTTP\URL;

class MiniRouter
{

	public function handleRequest()
	{
		if (!ifsetor($_SERVER['REQUEST_URI'])) {
			return true;
		}
		//debug($_SERVER);
		llog($_SERVER['REQUEST_URI']);
		$requestURL = new URL($_SERVER['REQUEST_URI']);
		//debug($requestURL, $requestURL->getPath().'', is_file($requestURL->getPath()));
		$staticPath = $requestURL->getPath();
		if ($staticPath) {
			$fullPath = realpath(__DIR__.'/../../../../..'.$staticPath);
			llog($fullPath);
			if (is_file($fullPath)) {
				llog($fullPath);
				return false;
			} else {
				$parts = trimExplode('/', $staticPath);
				$first = first($parts);
				if ($first && !class_exists($first)) {
					http_response_code(404);
					header('X-Path: '.$fullPath);
					echo 'Class '.$first.' not found';
					return;
				}
			}
		}
		return $staticPath;	// true means PHP
	}

}
