<?php

use SendGrid\Response;

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

	public $headers = [];

	public $params = [];

	public $attachments = [];

	public $sendFrom;

	public function __construct($to, $subject, $body)
	{
		$this->to = $to;
		$this->subject = $subject;
		$this->bodytext = $body;
		$this->sendFrom = Config::getInstance()->mailFrom;
	}

	public function attach($name, $mime, $content): void
	{
		$this->attachments[] = [
			'name' => $name,
			'mime' => $mime,
			'content' => $content,
		];
	}

	public function debug(): \slTable
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

	public function getSubject(): string
	{
		return '=?utf-8?B?' . base64_encode($this->subject) . '?=';
	}

	public static function isHTML($bodyText): bool
	{
//		return strpos($bodyText, '<') !== FALSE;
		return $bodyText !== '' && $bodyText[0] === '<';
	}

	public function getBodyText(): string
	{
		return str_replace("\n.", "\n..", $this->bodytext);
	}

	public function from($from): array
	{
		$this->sendFrom = $from;
		return $this->sendFrom;
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

		if ($validEmails === count($emails)) {
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

	public function getPlainText(): string
	{
		return strip_tags($this->bodytext);
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
}
