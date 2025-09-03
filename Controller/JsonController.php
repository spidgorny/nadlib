<?php

use spidgorny\nadlib\HTTP\URL;

trait JsonController
{

	public static $publicAPI = false;

	public function afterConstruct(): void
	{
		$this->request->set('ajax', true);
		$this->user = new DCIAPIUser();  // prevent API to hijack user session
		$this->config->setUser($this->user);
	}

	/**
	 * @throws LoginException
	 */
	public function validateAuthorization(array $registeredApps): void
	{
		[$actionClass] = $this->getActionAndArguments();
		$obj = new $actionClass();
		if ($obj::$publicAPI) {
			return;
		}

//		$headers = $this->request->getHeaders();
//		llog('apache_headers in JsonController for', get_class($this), $headers);

		$authorization = $this->request->getHeader('Authorization');
		if ($authorization) {
//		llog($authorization);
			//debug($headers, $authorization);
			invariant($authorization, new AccessDeniedException('No Authorization Header', 401));
			if (!in_array($authorization, $registeredApps, true)) {
				throw new LoginException('Authorization failed.', 401);
			}
		}

		startSessionDCI();
		llog('session', $_SESSION);
		$user = DCI::getInstance()->loginFromHTTP();
		if ($user?->isAdmin()) {
			return;
		}

		throw new AccessDeniedException('No valid Authorization', 403);
	}

	public function getActionAndArguments(): array
	{
		// debug($_SERVER);
		$requestURI = ifsetor($_SERVER['REQUEST_URI']);
		$url = new URL($requestURI);
		$levels = $url->getPath()->getLevels();

		if ($levels[0] === 'stage') {
			$levels = array_slice($levels, 1);
		}

		if ($levels[0] === 'API') {
			$levels = array_slice($levels, 1);
		}

//		llog('API Levels', $levels);

		// next after /API/
		//llog(get_class($this));
		$last = null;
		$arguments = [];
		$thisParents = new ReflectionClass($this);
		$thisParents = [
			get_class($this),
			$thisParents->getParentClass()->getName(),
			'SoftwareImage',
			'SoftwareImg',
			AddVersion::class,
			AddJob::class,
			ArchiveComplete::class,
			UpsertSoftware::class,
			UpsertVersion::class,
			VersionFiles::class,
		];
//		llog('$thisParents', $thisParents);
		foreach (array_reverse($levels) as $i => $el) {
			$isThisController = in_array($el, $thisParents);
//			llog(get_class($this), $el, 'isThisController:', $isThisController);
			if ($isThisController) {
				$last = $el;
				$arguments = array_slice($levels, count($levels) - $i);    // rest are args
//				llog('arguments', $arguments);
				break;
			}
		}

		if (!$last) {
			$last = last($levels);
		}

		$request = trim($last, '/\\ ');
		$request = explode('.', $request)[0];
//		llog(['request' => $request, 'arguments' => $arguments]);
		return [$request, $arguments];
	}

	public function __invoke(): mixed
	{
		[$request, $arguments] = $this->getActionAndArguments();
		return call_user_func_array([$this, $request], $arguments);
	}

	public function jsonError(Exception $e, $httpCode = 500, array $extraData = [])
	{
		$message = '[' . get_class($e) . ']' . PHP_EOL . $e->getMessage() . PHP_EOL . $e->getFile() . '#' . $e->getLine();
		llog('jsonError', get_class($this), $message);
		http_response_code($httpCode);
		return $this->json([
				'status' => 'error',
				'error_type' => get_class($e),
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'stack_trace' => isDev() ? trimExplode("\n", $e->getTraceAsString()) : null,
				'request' => $this->request->getAll(),
				'headers' => getallheaders(),
				'timestamp' => date('Y-m-d H:i:s'),
				'back_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
				'duration' => microtime(true) - $_REQUEST['REQUEST_TIME_FLOAT'],
			] + $extraData);
	}

	public function json(array $key): string
	{
		header('Content-Type: application/json');
		$key['duration'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
		return json_encode($key, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}

}
