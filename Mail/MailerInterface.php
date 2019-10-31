<?php

interface MailerInterface
{

	public function __construct($to, $subject, $body);

	public function send();

	public function sendSwiftMailerEmail($cc = null, $bcc = null, array $attachments = [], array $additionalSenders = []);

}
