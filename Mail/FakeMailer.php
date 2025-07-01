<?php

class FakeMailer implements MailerInterface
{

	public $to;

	public $emails;

	public $subject;

	public $bodytext;

	public function __construct($emails, $subject, $bodytext)
	{
		$this->emails = $emails;
		$this->subject = $subject;
		$this->bodytext = $bodytext;
	}

	public function sendSwiftMailerEmail($cc = null, $bcc = null, array $attachments = [], array $additionalSenders = [])
	{
		//pre_print_r(__METHOD__, get_object_vars($this), func_get_args());
		$message = $this->getSwiftMessage($cc, $bcc, $attachments, $additionalSenders);

		$transport = new Swift_NullTransport();  // <== null
		$mailer = new Swift_Mailer($transport);
		return $mailer->send($message);
	}

	public function getSwiftMessage($cc, $bcc, array $attachments, array $additionalSenders): Swift_Message
	{
		$orsMailer = new SwiftMailer($this->emails, $this->subject, $this->bodytext);
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

	public function setTO(array $param)
	{
		// TODO: Implement setTO() method.
	}

	public function setSubject(string $param)
	{
		// TODO: Implement setSubject() method.
	}

	public function getBody($message)
	{
		// TODO: Implement getBody() method.
	}

	public function setFrom(array $array)
	{
		// TODO: Implement setFrom() method.
	}
}
