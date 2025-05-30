<?php

/**
 * Class Mailer - simple mail sending class which supports either plain text or HTML
 * mails. No attachments. Use SwiftMailer for anything more complicated. Takes care
 * of the UTF-8 in subjects.
 */
class Mailer implements MailerInterface
{

	/**
	 * @var string
	 */
	public $to = [];

	public $cc;

	public $bcc;

	/**
	 * @var string
	 */
	public $subject;

	/**
	 * @var string
	 */
	public $bodytext;

	/**
	 * Need to repeat key inside the value
	 * From => From: somebody
	 * @var array
	 */
	public $headers = [];

	/**
	 * @var array
	 */
	public $params = [];

	public $attachments;

	public $from;

	public $fromName;

	public function __construct($to, $subject, $bodyText)
	{
		if ($to && !is_array($to)) {
			$sep = str_contains($to, ';') ? ';' : ',';
			$this->to = trimExplode($sep, $to);
		} else {
			$this->to = $to ?: [];
		}
		$this->to = array_unique($this->to);
		$this->subject = trim($subject);
		$this->bodytext = $bodyText;
		$this->headers['X-Mailer'] = 'X-Mailer: PHP/' . phpversion();
		$this->headers['MIME-Version'] = 'MIME-Version: 1.0';
		if (self::isHTML($this->bodytext)) {
			$this->headers['Content-Type'] = 'Content-Type: text/html; charset=utf-8';
		} else {
			$this->headers['Content-Type'] = 'Content-Type: text/plain; charset=utf-8';
		}
		$this->headers['Content-Transfer-Encoding'] = 'Content-Transfer-Encoding: 8bit';
		if (class_exists('Config')) {
			$mailFrom = ifsetor(Config::getInstance()->mailFrom);
			if ($mailFrom) {
				$this->from($mailFrom);
			}
		}
	}

	public static function isHTML($bodyText)
	{
//		return strpos($bodyText, '<') !== FALSE;
		return $bodyText !== '' && $bodyText[0] === '<';
	}

	/**
	 * @param $mailFrom string
	 */
	public function from($mailFrom)
	{
		// name <email@company.com>
		$split = trimExplode('<', $mailFrom);
		if (count($split) === 2) {
			$this->fromName = $split[0];
			$this->from = str_replace('>', '', $split[1]);
		} else {
			$this->fromName = $mailFrom;
			$this->from = $mailFrom;
		}
		$this->headers['From'] = 'From: ' . $mailFrom;
		// get only the pure email from "Somebody <sb@somecompany.de>"
		$arMailFrom = explode('<', $mailFrom);
		if (count($arMailFrom) === 2) {
			$mailFromOnly = (strpos($this->bodytext, '<') !== false)
				? substr(next($arMailFrom), 0, -1)
				: ''; //$mailFrom;
			if ($mailFromOnly) {
				$this->params['-f'] = '-f' . $mailFromOnly;    // no space
			}
		}
		return [$this->from => $this->fromName];
	}

	public function appendPlainText()
	{
		$htmlMail = $this->bodytext;
		$mailText = $this->getPlainText();
		$this->attach('text', 'text/plain', $mailText);
		$this->attach('html', 'text/html', $htmlMail);
		$this->rebuildMessage();
	}

	/**
	 * Should not be called more than once since it corrupts
	 * $this->bodytext
	 */
	public function rebuildMessage()
	{
		//create a boundary for the email. This
		$boundary = uniqid('np');

		//headers - specify your from email address and name here
		//and specify the boundary for the email
		$this->headers['MIME-Version'] = 'MIME-Version: 1.0';
		$this->headers['Content-Type'] = 'Content-Type: multipart/mixed; boundary=' . $boundary;

		//here is the content body
		$message = "This is a MIME encoded message.\r\n";
		$message .= "\r\n";

		foreach ($this->attachments as $a) {
			$message .= '--' . $boundary . "\r\n";

			//Plain text body
			if (str_startsWith($a['mime'], 'text/plain')) {
				$message .= "Content-Type: {$a['mime']};charset=utf-8\r\n";
				$message .= "Content-Disposition: inline\r\n";
				$message .= "\r\n";
				$message .= $a['content'];
				$message .= "\r\n";
			} else {
				$message .= "Content-transfer-encoding: base64\r\n";
				$message .= "Content-Disposition: attachment; filename={$a['name']}\r\n";
				$message .= "Content-Type: {$a['mime']}\r\n";
				$message .= "\r\n";
				$base64 = base64_encode($a['content']);
				$base64 = chunk_split($base64);
				$message .= $base64;
				$message .= "\r\n";
			}
		}

		$message .= "\r\n\r\n--" . $boundary . '--';
		$this->bodytext = $message;
	}

	public function attach($name, $mime, $content)
	{
		$this->attachments[] = [
			'name' => $name,
			'mime' => $mime,
			'content' => $content,
		];
	}

	public function getSubject()
	{
		$subject = '=?utf-8?B?' . base64_encode($this->subject) . '?=';
		return $subject;
	}

	public function getBodyText()
	{
		$bodyText = str_replace("\n.", "\n..", $this->bodytext);
		return $bodyText;
	}

	public function debug()
	{
		$assoc = [];
		$assoc['to'] = $this->to;
		$assoc['subject'] = $this->getSubject();
		$assoc['isHTML'] = self::isHTML($this->bodytext);
		$assoc['headers'] = new HtmlString(implode('<br />', $this->headers));
		$assoc['params'] = implode(' ', $this->params);
		$assoc['bodyText'] = nl2br($this->getBodyText());
		return slTable::showAssoc($assoc);
	}

	/**
	 * @return bool
	 * @throws MailerException
	 */
	public function send()
	{
		$emails = $this->to;
		$validEmails = 0;
		foreach ($emails as $e) {
			$validEmails += HTMLFormValidate::validEmail($e);
		}
		if ($validEmails === sizeof($emails)) {
			$res = mail(
				implode(', ', $this->to),
				$this->getSubject(),
				$this->getBodyText(),
				implode("\n", $this->headers) . "\n",
				implode(' ', $this->params)
			);
			if (!$res) {
				throw new MailerException('Email sending to [' . implode(', ', $this->to) . '] failed');
			}
		} else {
			throw new MailerException('Invalid email address: ' . implode(', ', $this->to));
		}
		return $res;
	}

	public function getPlainText()
	{
		if (class_exists('HTMLPurifier_Config')) {
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.Allowed', '');
			$purifier = new HTMLPurifier($config);
			$mailText = $purifier->purify($this->bodytext);
//			$mailText = str_replace("\n\n", "\n", $mailText);
//			$mailText = str_replace("\r\n\r\n", "\r\n", $mailText);
			$mailText = explode(PHP_EOL, $mailText);    // keep blank lines
			$mailText = array_map('trim', $mailText);
			$mailText = implode(PHP_EOL, $mailText);
		} else {
			$mailText = strip_tags($this->bodytext);
		}
		return $mailText;
	}

	public function getSendGridMail()
	{
		$config = Config::getInstance();
		$from = new SendGrid\Email(null, $config->mailFrom);
		$to = new SendGrid\Email(null, $this->to);
		$content = new SendGrid\Content('text/plain', $this->getPlainText());
		$mail = new SendGrid\Mail($from, $this->subject, $to, $content);
		return $mail;
	}

	/**
	 * @return \SendGrid\Response
	 */
	public function sendGrid()
	{
		$config = Config::getInstance();
		$mail = $this->getSendGridMail();

		$sg = $config->getSendGrid();

		/** @var $response \SendGrid\Response */
		$response = $sg->client->mail()->send()->post($mail);
		return $response;
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
	 * @return int|array Either number of recipients who were accepted for delivery OR an array of failed recipients
	 * @throws Exception
	 */
	function sendSwiftMailerEmail($cc = null, $bcc = null, $attachments = [], $additionalSenders = [])
	{
		$message = $this->getSwiftMessage($cc, $bcc, $attachments, $additionalSenders);

		$transport = new Swift_SendmailTransport();
		$mailer = new Swift_Mailer($transport);
		$failedRecipients = [];
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
	public function getSwiftMessage($cc = null, $bcc = null, $attachments = [], $additionalSenders = [])
	{
		$messageHTML = $this->getBodyText();
		$messageText = $this->getPlainText();

		/** @var Swift_Message $message */
		$message = new Swift_Message();
		$message->setSubject($this->subject)
			->setBody($messageHTML, 'text/html')
			->addPart($messageText, 'text/plain');
		$message->setCharset('utf-8');

		llog('mailFromSwiftMailer', DCI_EMAILFROM, $this->from, $this->fromName);

		$message->setFrom($this->from, $this->fromName);

		if (!empty($additionalSenders)) {
			foreach ($additionalSenders as $address) {
				$message->addFrom(key($address));
			}
		}

		if (is_string($this->to)) {
			$to = trimExplode(';', $this->to);
		} else {
			$to = $this->to;
		}
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
				if (is_string($attachment)) {
					$smAttachment = Swift_Attachment::fromPath($attachment);
					$shortFile = $this->getShortFilename($attachment);
					$smAttachment->setFilename($shortFile);
					$message->attach($smAttachment);
				} else {
					$message->attach($attachment);
				}
			}
		}

		if (!empty($additionalSenders)) {
			foreach ($additionalSenders as $address => $name) {
				empty($address)
					? null
					: $message->addFrom($address, $name);
			}
		}

		return $message;
	}

}
