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

	public function getSendGridMail()
	{
		$config = Config::getInstance();
		$from = new SendGrid\Email(null, $config->mailFrom);
		$to = new SendGrid\Email(null, $this->to);
		$content = new SendGrid\Content('text/plain', $this->getPlainText());
		return new SendGrid\Mail($from, $this->subject, $to, $content);
	}

	public function attach($name, $mime, $content): void
	{
		$this->attachments[] = [
			'name' => $name,
			'mime' => $mime,
			'content' => $content,
		];
	}

	public function getSubject(): string
	{
		return '=?utf-8?B?' . base64_encode($this->subject) . '?=';
	}

	public function getBodyText(): string
	{
		return str_replace("\n.", "\n..", $this->bodytext);
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

	public static function isHTML($bodyText): bool
	{
//		return strpos($bodyText, '<') !== FALSE;
		return $bodyText !== '' && $bodyText[0] === '<';
	}

	public function from($from): void
	{
		$this->sendFrom = $from;
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

	public function __construct($to, $subject, $body)
	{
		$this->to = $to;
		$this->subject = $subject;
		$this->bodytext = $body;
		$this->sendFrom = Config::getInstance()->mailFrom;
	}

	public function getPlainText(): string
	{
		return strip_tags($this->bodytext);
	}

}
