<?php

class RenderException
{

	protected \Exception $e;

	// HTTP error code
	protected $code;

	public function __construct(Exception $e, $code = 500)
	{
		$this->e = $e;
		$this->code = $code;
	}

	public function render(string $wrapClass = 'ui-state-error alert alert-error alert-danger padding flash flash-warn flash-error'): \JSONResponse|array
	{
		$e = $this->e;
		$traceAsString = $e->getTraceAsString();
		$traceAsString = str_replace(getcwd(), "", $traceAsString);
		if (Request::isCLI()) {
			echo get_class($e),
			' #', $e->getCode(),
			': ', $e->getMessage(), BR;
			echo $traceAsString, BR;
			return [''];
		}

		http_response_code($this->code ?: $e->getCode());
		header('X-Exception:' . get_class($this->e));
		$message = $this->e->getMessage();
		$message = str_replace(array("\n", "\r"), " ", $message);
		header('X-Message:' . $message);

		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
		header('X-Accept:' . $accept);
		if ($accept === 'application/json') {
			Request::getInstance()->set('ajax', true);
			return new JSONResponse([
				'status' => 'error',
				'class' => get_class($this->e),
				'message' => $this->e->getMessage(),
				'trace' => trimExplode(PHP_EOL, $this->e->getTraceAsString()),
			], $e->getCode());
		}

		$message = $e->getMessage();
		$message = ($message instanceof HtmlString || $message[0] === '<')
			? $message . ''
			: htmlspecialchars($message);
		$content = '<div class="' . $wrapClass . '">
				<h1>' . get_class($e) .
			($e->getCode() ? ' (Code: ' . $e->getCode() . ')' : '') . '</h1>' .
			'<h3>' . nl2br($message) . '</h3>';
		$content .= 'In ' . $e->getFile() . ' on line ' . $e->getLine() . '<br/>';
		$content .= $e->getPrevious() instanceof \Throwable ? 'Previous: ' . $e->getPrevious()->getMessage() . '<br/>' : '';
		$content .= '<hr class="my-3"/><pre style="text-align: left; white-space: pre-wrap;">' .
			htmlspecialchars($traceAsString) . '</pre>';

		if ($e instanceof LoginException) {
			// catch this exception in your app Index class, it can't know what to do with all different apps
			//$lf = new LoginForm();
			//$content .= $lf;
		}

		if ($e instanceof Exception404) {
			$e->sendHeader();
		}

		if ($e instanceof DatabaseException) {
			$content .= '<p>Query: ' . htmlspecialchars($e->getQuery()) . '</p>';
		}

		return [$content, '</div>'];
	}
}
