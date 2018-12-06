<?php

class FakeMailer
{

	public $emails;

	public $subject;

	public $body;

	public function __construct($emails, $subject, $body)
	{
		$this->emails = $emails;
		$this->subject = $subject;
		$this->body = $body;
	}

	public function send()
	{
		$emails = is_array($this->emails)
			? implode('; ', $this->emails)
			: $this->emails;
//		echo 'Sending mail "' . $this->subject . '" to [' . $emails . ']', BR;
	}

}
