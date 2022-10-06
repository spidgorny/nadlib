<?php

trait JsonController
{

	public function afterConstruct()
	{
		$this->request->set('ajax', true);
		$this->user = new APIUser();  // prevent API to hijack user session
		$this->config->setUser($this->user);
	}

	public function isDevServer()
	{
//		llog(__METHOD__, DEVELOPMENT, $_SERVER['HTTP_HOST'], gethostname());
		return DEVELOPMENT &&
			$_SERVER['HTTP_HOST'] === 'localhost:2000' &&
			gethostname() === '761K7Y2';
	}

	/**
	 * @param array $registeredApps
	 * @throws LoginException
	 */
	public function validateAuthorization(array $registeredApps)
	{

		if (self::$public) {
			return;
		}
		$authorization = $this->request->getHeader('Authorization');
//		llog($authorization);
		//debug($headers, $authorization);
		invariant($authorization, 'No Authorization Header');
		if (!in_array($authorization, $registeredApps, false)) {
			throw new LoginException('Authorization failed.', 401);
		}
	}

	public function __invoke($arg1 = null)
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

		if ($levels[0] === 'API') {
			$levels = array_slice($levels, 1);
		}
		// next after /API/
		//llog(get_class($this));
		$last = null;
		$arguments = [];
		foreach ($levels as $i => $el) {
			$isThisController = $el === get_class($this);
			if ($isThisController) {
				$last = ifsetor($levels[$i]);
				$arguments = array_slice($levels, $i + 1);    // rest are args
				break;
			}
		}
		if (!$last) {
			$last = last($levels);
		}
		$request = trim($last, '/\\ ');
		$request = explode('.', $request)[0];
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
				'back_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
			] + $extraData);
	}

	public function json($key)
	{
		header('Content-Type: application/json');
		$key['duration'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
		$jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
		/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
		if (is_numeric(JSON_UNESCAPED_LINE_TERMINATORS)) {
			/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
			$jsonOptions |= JSON_UNESCAPED_LINE_TERMINATORS;
		}
		$response = json_encode($key, $jsonOptions);
//		error_log($response);
		return $response;
	}

}
