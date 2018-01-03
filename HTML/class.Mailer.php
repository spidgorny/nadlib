<?php

/**
 * Class Mailer - simple mail sending class which supports either plain text or HTML
 * mails. No attachments. Use SwiftMailer for anything more complicated. Takes care
 * of the UTF-8 in subjects.
 */
class Mailer {

	/**
	 * @var string
	 */
	var $to;

	/**
	 * @var string
	 */
	var $subject;

	/**
	 * @var string
	 */
	var $bodytext;

	/**
	 * Need to repeat key inside the value
	 * From => From: somebody
	 * @var array
	 */
	var $headers = array();

	/**
	 * @var array
	 */
	var $params = array();

	var $from;

	var $fromName;

	function __construct($to, $subject, $bodytext)
	{
		if (is_array($to)) {
			$this->to = implode(', ', $to);
		} else {
			$this->to = trim($to);
		}
		$this->subject = trim($subject);
		$this->bodytext = $bodytext;
		$this->headers['X-Mailer'] = 'X-Mailer: PHP/' . phpversion();
		$this->headers['MIME-Version'] = 'MIME-Version: 1.0';
		if (strpos($this->bodytext, '<') !== FALSE) {
			$this->headers['Content-Type'] = 'Content-Type: text/html; charset=utf-8';
		} else {
			$this->headers['Content-Type'] = 'Content-Type: text/plain; charset=utf-8';
		}
		$this->headers['Content-Transfer-Encoding'] = 'Content-Transfer-Encoding: 8bit';
	}

	function setFrom($mailFrom)
	{
		// name <email@company.com>
		$split = trimExplode('<', $mailFrom);
		if (sizeof($split) == 2) {
			$this->fromName = $split[0];
			$this->from = str_replace('>', '', $split[1]);
		}
		$this->headers['From'] = 'From: ' . $mailFrom;
		// get only the pure email from "Somebody <sb@somecompany.de>"
		$arMailFrom = explode('<', $mailFrom);
		$mailFromOnly = (strpos($this->bodytext, '<') !== FALSE)
			? substr(next($arMailFrom), 0, -1)
			: ''; //$mailFrom;
		if ($mailFromOnly) {
			$this->params['-f'] = '-f' . $mailFromOnly;    // no space
		}
	}

	/**
	 * @throws Exception
	 */
	function send()
	{
		if (HTMLFormValidate::validEmail($this->to)) {
			$res = mail($this->to,
				$this->getSubject(),
				$this->getBodyText(),
				implode("\n", $this->headers) . "\n",
				implode(' ', $this->params));
			if (!$res) {
				throw new Exception('Email sending to ' . $this->to . ' failed');
			}
		} else {
			throw new Exception('Invalid email address');
		}
		return $res;
	}

	function getSubject()
	{
		$subject = '=?utf-8?B?' . base64_encode($this->subject) . '?=';
		return $subject;
	}

	function getBodyText()
	{
		$bodytext = str_replace("\n.", "\n..", $this->bodytext);
		return $bodytext;
	}

	function debug()
	{
		$assoc = array();
		$assoc['to'] = $this->to;
		$assoc['subject'] = $this->getSubject();
		$assoc['bodytext'] = $this->getBodyText();
		$assoc['headers'] = new htmlString(implode("<br />", $this->headers));
		$assoc['params'] = implode(' ', $this->params);
		return slTable::showAssoc($assoc);
	}

	/**
	 * Method to send emails via SwiftMailer.
	 * Throws an Exception if SwiftMailer is not installed.
	 *
	 * Uses sendmail to deliver messages.
	 *
	 * @param mixed $cc
	 * @param mixed $bcc
	 * @param array $attachments
	 * @param array $additionalSenders This will be added to
	 * @throws Exception
	 * @return int|array Either number of recipients who were accepted for delivery OR an array of failed recipients
	 */
	function sendSwiftMailerEmail($cc = null, $bcc = null, $attachments = array(), $additionalSenders = array())
	{
		$message = $this->getSwiftMessage($cc, $bcc, $attachments, $additionalSenders);

		$transport = new Swift_SendmailTransport();
		$mailer = new Swift_Mailer($transport);
		$failedRecipients = array();
		$sent = $mailer->send($message, $failedRecipients);

		return !empty($failedRecipients) ? $failedRecipients : $sent;
	}

	/**
	 * @param $cc
	 * @param $bcc
	 * @param $attachments
	 * @param $additionalSenders
	 * @return Swift_Message
	 * @throws Exception
	 */
	public function getSwiftMessage($cc = null, $bcc = null, $attachments = array(), $additionalSenders = array())
	{
		if (!class_exists('Swift_Mailer')) {
			throw new Exception('SwiftMailer not installed!');
		}

		/** @var Swift_Message $message */
		$message = new Swift_Message($this->subject, $this->bodytext);

		$message->setFrom($this->from, $this->fromName);

		if (!empty($additionalSenders)) {
			foreach ($additionalSenders as $address) {
				$message->addFrom(key($address));
			}
		}

		$to = trimExplode(';', $this->to);
		foreach ($to as $address) {
			$message->addTo(trim($address));
		}

		if (!empty($cc)) {
			foreach ($cc as $address) {
				$message->addCc($address);
			}
		}

		if (!empty($bcc)) {
			foreach ($bcc as $address) {
				$message->addBcc($address);
			}
		}

		if (!empty($attachments)) {
			foreach ($attachments as $attachment) {
				$message->attach(Swift_Attachment::fromPath($attachment));
			}
		}
		return $message;
	}

}
