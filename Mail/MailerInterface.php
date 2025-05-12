<?php

interface MailerInterface
{

	public function __construct($to, $subject, $body);

	public function send();

	public function setCC(array $param);

	public function setBCC(array $param);

	public function setAttachments($attachments);

}
