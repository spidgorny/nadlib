<?php

trait JsonController
{

    public function afterConstruct()
    {
        $this->request->set('ajax', true);
        $this->user = new NoUser();	// prevent API to hijack user session
		$this->config->setUser($this->user);
    }

    public function validateAuthorization($registeredApps)
	{
		$authorization = $this->request->getHeader('Authorization');
//		llog($authorization);
		//debug($headers, $authorization);
		if (!$authorization || !in_array($authorization, $registeredApps)) {
			throw new LoginException('Authorization failed.', 401);
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


    public function jsonError(Exception $e, $httpCode = 500, array $extraData = [])
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
            'stack_trace' => DEVELOPMENT ? trimExplode("\n", $e->getTraceAsString()) : null,
            'request' => $this->request->getAll(),
            'headers' => getallheaders(),
			'timestamp' => date('Y-m-d H:i:s'),
        ] + $extraData);
    }

    public function json($key)
    {
        header('Content-Type: application/json');
        $key['duration'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
		$response = json_encode($key, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS);
		error_log($response);
		return $response;
    }

}
