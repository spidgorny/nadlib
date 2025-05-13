<?php

class FakeMailer implements MailerInterface
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

	public function sendSwiftMailerEmail($cc = null, $bcc = null, array $attachments = [], array $additionalSenders = [])
	{
		//pre_print_r(__METHOD__, get_object_vars($this), func_get_args());
		$message = $this->getSwiftMessage($cc, $bcc, $attachments, $additionalSenders);

		$transport = new Swift_NullTransport();  // <== null
		$mailer = new Swift_Mailer($transport);
		$failedRecipients = [];
		$sent = $mailer->send($message, $failedRecipients);

		return count($failedRecipients) ? $failedRecipients : $sent;
	}

	public function getSwiftMessage($cc, $bcc, array $attachments, array $additionalSenders): \Swift_Message
	{
		$orsMailer = new SwiftMailer($this->emails, $this->subject, $this->body);
		return $orsMailer->getSwiftMessage($cc, $bcc, $attachments, $additionalSenders);
	}

	public function send(): void
	{
		//		echo 'Sending mail "' . $this->subject . '" to [' . $emails . ']', BR;
	}

	public function setCC(array $param)
	{
		// TODO: Implement setCC() method.
	}

	public function setBCC(array $param)
	{
		// TODO: Implement setBCC() method.
	}

	public function setAttachments($attachments)
	{
		// TODO: Implement setAttachments() method.
	}
}
