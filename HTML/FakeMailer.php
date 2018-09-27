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
		$emails = is_array($this->emails)
			? implode('; ', $this->emails)
			: $emails;
		echo 'Sending mail "'.$this->subject.'" to ['.$emails.']', BR;
	}

}
