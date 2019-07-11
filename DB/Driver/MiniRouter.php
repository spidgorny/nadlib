<?php

use spidgorny\nadlib\HTTP\URL;

class MiniRouter
{

	public function handleRequest()
	{
		//debug($_SERVER);
		llog($_SERVER['REQUEST_URI']);
		$requestURL = new URL($_SERVER['REQUEST_URI']);
//debug($requestURL, $requestURL->getPath().'', is_file($requestURL->getPath()));
		$staticPath = $requestURL->getPath();
		if ($staticPath) {
			$fullPath = __DIR__.$staticPath;
			if (is_file($fullPath)) {
				llog($fullPath);
				return false;
			} else {
				$parts = trimExplode('/', $staticPath);
				$first = first($parts);
				if ($first && !class_exists($first)) {
					http_response_code(404);
					echo 'Class '.$first.' not found';
					return;
				}
			}
		}
	}

}