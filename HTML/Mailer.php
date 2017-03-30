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

	var $cc;

	var $bcc;

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

	function __construct($to, $subject, $bodytext) {
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
		if (class_exists('Config')) {
			if ($mailFrom = ifsetor(Config::getInstance()->mailFrom)) {
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
		}
	}

	function send() {
		$emails = trimExplode(',', $this->to);
		$validEmails = 0;
		foreach ($emails as $e) {
			$validEmails += HTMLFormValidate::validEmail($e);
		}
		if ($validEmails == sizeof($emails)) {
			$res = mail($this->to,
				$this->getSubject(),
				$this->getBodyText(),
				implode("\n", $this->headers)."\n",
				implode(' ', $this->params));
			if (!$res) {
				throw new MailerException('Email sending to '.$this->to.' failed');
			}
		} else {
			throw new MailerException('Invalid email address: '.$this->to);
		}
		return $res;
	}

	function appendPlainText() {
		$htmlMail = $this->bodytext;
		$mailText = $this->getPlainText();
		//create a boundary for the email. This
		$boundary = uniqid('np');

		//headers - specify your from email address and name here
		//and specify the boundary for the email
		$this->headers["MIME-Version"] = '1.0';
		$this->headers['Content-Type'] = "multipart/alternative;boundary=" . $boundary;

		//here is the content body
		$message = "This is a MIME encoded message.";
		$message .= "\r\n\r\n--" . $boundary . "\r\n";
		$message .= "Content-type: text/plain;charset=utf-8\r\n\r\n";

		//Plain text body
		$message .= $mailText;
		$message .= "\r\n\r\n--" . $boundary . "\r\n";
		$message .= "Content-type: text/html;charset=utf-8\r\n\r\n";

		//Html body
		$message .= $htmlMail;
		$message .= "\r\n\r\n--" . $boundary . "--";
		$this->bodytext = $message;
		return $res;
	}

	function getSubject() {
		$subject = '=?utf-8?B?'.base64_encode($this->subject).'?=';
		return $subject;
	}

	function getBodyText() {
		$bodytext = str_replace("\n.", "\n..", $this->bodytext);
		return $bodytext;
	}

	function debug() {
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
     * @param mixed $to
     * @param mixed $cc
     * @param mixed $bcc
     * @param array $attachments
     * @param array $additionalSenders This will be added to
     * @throws Exception
     * @return int|array Either number of recipients who were accepted for delivery OR an array of failed recipients
     */
    public function sendSwiftMailerEmail(
    	array $to, array $cc = null, array $bcc = null,
		array $attachments = array(),
		array $additionalSenders = array())
    {
        if (!class_exists('Swift_Mailer')) {
            throw new Exception('SwiftMailer not installed!');
        }

		if ($_SERVER['HTTP_USER_AGENT'] == 'Detectify') {
			return NULL;
		}

		$messageHTML = $this->getBodyText();
        $messageText = $this->getPlainText();

        /** @var Swift_Message $message */
        $message = Swift_Message::newInstance()
            ->setSubject($this->subject)
            ->setBody($messageHTML, 'text/html')
			->addPart($messageText, 'text/plain')
        ;

		$index = Index::getInstance();
//		$r = new ReflectionClass(Index::class);
//		debug($r->getFileName(),
//			array_keys(get_object_vars($index)),
//		$index->mailFromSwiftMailer);
		$message->setFrom($index->mailFromSwiftMailer);

        if (!empty($to)) {
            foreach ($to as $address) {
                empty($address)
	                ? NULL
	                : $message->addTo(trim($address));
            }
        }

        if (!empty($cc)) {
            foreach ($cc as $address) {
                empty($address)
	                ? NULL
	                : $message->addCc($address);
            }
        }

        if (!empty($bcc)) {
            foreach ($bcc as $address) {
                empty($address)
	                ? NULL
	                : $message->addBcc($address);
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
					? NULL
					: $message->addFrom($address, $name);
			}
		}

//		debug($message->getFrom()); die;

		$transport = Swift_SendmailTransport::newInstance();
        $mailer = Swift_Mailer::newInstance($transport);
        $failedRecipients = array();

        $sent = $mailer->send($message, $failedRecipients);

        return !empty($failedRecipients) ? $failedRecipients : $sent;
    }

	/**
	 * http://stackoverflow.com/questions/8781911/remove-non-ascii-characters-from-string-in-php
	 * @param string $attachment
	 * @return string
	 */
	public function getShortFilename($attachment) {
		$pathinfo = pathinfo($attachment);
		$ext = $pathinfo['extension'];

		$filename = $pathinfo['filename'];
		$filename = filter_var($filename, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
		$filename = preg_replace('/([\s])\1+/', ' ', $filename);
		$filename = str_replace(' ', '_', $filename);

		$extLen = 1 + strlen($ext);
		$shortFile = substr($filename, 0, 63 - $extLen)
			. '.' . $ext;
		return $shortFile;
	}

	function getPlainText() {
		if (class_exists('HTMLPurifier_Config')) {
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.Allowed', '');
			$purifier = new HTMLPurifier($config);
			$mailText = $purifier->purify($this->bodytext);
//			$mailText = str_replace("\n\n", "\n", $mailText);
//			$mailText = str_replace("\r\n\r\n", "\r\n", $mailText);
			$mailText = explode(PHP_EOL, $mailText);	// keep blank lines
			$mailText = array_map('trim', $mailText);
			$mailText = implode(PHP_EOL, $mailText);
		} else {
			$mailText = strip_tags($this->bodytext);
		}
		return $mailText;
	}

}
