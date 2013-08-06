<?php

class Exception404 extends Exception {

	protected $message = 'The page in the URL is not found. Check the menu items. In case you see this message often please contact the site administrator.';

	function sendHeader() {
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	}



}
