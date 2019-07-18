<?php

trait JsonController
{

    public function afterConstruct()
    {
        $request = Request::getInstance();
        $request->set('ajax', true);
    }

    public function validateAuthorization($registeredApps)
	{
		$headers = apache_request_headers();
		$authorization = ifsetor($headers['Authorization']);
		//debug($headers, $authorization);
		if (!$authorization || !in_array($authorization, $registeredApps)) {
			return $this->error(new LoginException('Authorization failed.'), 401);
		}
	}

    public function __invoke()
    {
		list($request, $arguments) = $this->getActionAndArguments();
        return call_user_func_array([$this, $request], $arguments);
    }

    public function getActionAndArguments()
	{
		//        debug($_SERVER);
		$requestURI = ifsetor($_SERVER['REQUEST_URI']);
		$url = new \spidgorny\nadlib\HTTP\URL($requestURI);
		$levels = $url->getPath()->getLevels();

		// next after /API/
		//llog(get_class($this));
		$last = null;
		$arguments = [];
		foreach ($levels as $i => $el) {
			if ($el == get_class($this)) {
				$last = ifsetor($levels[$i+1]);
				$arguments = array_slice($levels, $i+2);    // rest are args
				break;
			}
		}
		if (!$last) {
			$last = last($levels);
		}
		$request = trim($last, '/\\ ');
		return [$request, $arguments];
	}

    public function error(Exception $e, $httpCode = 500)
    {
        $message = '[' . get_class($e) . ']' . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getFile() . '#' . $e->getLine();
        llog($message);
        http_response_code($httpCode);
        return $this->json([
            'status' => 'error',
            'error_type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'request' => $_REQUEST,
            'headers' => getallheaders(),
        ]);
    }

    public function json($key)
    {
        header('Content-Type: application/json');
        return json_encode($key, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

}