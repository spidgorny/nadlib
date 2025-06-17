<?php

interface MailerInterface
{

	public function __construct($to, $subject, $body);

	public function send();

	public function setTO(array $param);

	public function setCC(array $param);

	public function setBCC(array $param);

	public function setSubject(string $param);

	public function setAttachments($attachments);

	public function getBody($message);

	public function setFrom(array $array);

}
