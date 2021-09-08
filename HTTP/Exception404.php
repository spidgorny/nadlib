<?php

class Exception404 extends Exception
{

	protected $message = 'The requested page URL is not found. Check the menu items. In case you see this message often please contact the site administrator. Slug: ';

	public function __construct($message = "", $code = 0, Exception $previous = null)
	{
		parent::__construct($this->message . '"' . $message . '"', $code, $previous);
	}

	function sendHeader()
	{
		if (!headers_sent()) {
//			header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
			http_response_code(404);
		}
	}

}
