<?php

class FakeMailer {

	var $emails;

	var $subject;

	var $body;

	function __construct($emails, $subject, $body)
	{
		$this->emails = $emails;
		$this->subject = $subject;
		$this->body = $body;
	}

	function send()
	{
		echo 'Sending mail to '.$this->emails.' "'.$this->subject.'"', BR;
	}

}
