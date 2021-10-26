<?php

class RenderException
{

	/**
	 * @var Exception
	 */
	protected $e;

	public function __construct(Exception $e)
	{
		$this->e = $e;
	}

	public function render($wrapClass = 'ui-state-error alert alert-error alert-danger padding flash flash-warn flash-error')
	{
		$e = $this->e;
		if (Request::isCLI()) {
			echo get_class($e),
			' #', $e->getCode(),
			': ', $e->getMessage(), BR;
			echo $e->getTraceAsString(), BR;
			return '';
		}

		http_response_code($e->getCode());

		$message = $e->getMessage();
		$message = ($message instanceof htmlString ||
			$message[0] === '<')
			? $message . ''
			: htmlspecialchars($message);
		$content = '<div class="' . $wrapClass . '">
				' . get_class($e) .
			($e->getCode() ? ' (' . $e->getCode() . ')' : '') . BR .
			nl2br($message);
		if (DEVELOPMENT || 0) {
			$content .= BR . '<hr />' . '<div style="text-align: left">' .
				nl2br($e->getTraceAsString()) . '</div>';
			//$content .= getDebug($e);
		}
		$content .= '</div>';
		if ($e instanceof LoginException) {
			// catch this exception in your app Index class, it can't know what to do with all different apps
			//$lf = new LoginForm();
			//$content .= $lf;
		} elseif ($e instanceof Exception404) {
			$e->sendHeader();
		}

		return $content;
	}
}
