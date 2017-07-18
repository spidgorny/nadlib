<?php

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
	 * @var array
	 */
	var $headers = array();

	/**
	 * @var array
	 */
	var $params = array();

	function __construct($to, $subject, $bodytext) {
		$this->to = $to;
		$this->subject = $subject;
		$this->bodytext = $bodytext;
		$this->headers['X-Mailer'] = 'X-Mailer: PHP/' . phpversion();
		$this->headers['MIME-Version'] = 'MIME-Version: 1.0';
		if (strpos($this->bodytext, '<') !== FALSE) {
			$this->headers['Content-Type'] = 'Content-Type: text/html; charset=utf-8';
		} else {
			$this->headers['Content-Type'] = 'Content-Type: text/plain; charset=utf-8';
		}
		$this->headers['Content-Transfer-Encoding'] = 'Content-Transfer-Encoding: 8bit';
		if ($mailFrom = Index::getInstance()->mailFrom) {
			$this->headers['From'] = 'From: '.$mailFrom;
			// get only the pure email from "Somebody <sb@somecompany.de>"
            $arMailFrom = explode('<', $mailFrom);
            $mailFromOnly =	(strpos($this->bodytext, '<') !== FALSE)
				? substr(next($arMailFrom), 0, -1)
				: ''; //$mailFrom;
			if ($mailFromOnly) {
				$this->params['-f'] = '-f'.$mailFromOnly;	// no space
			}
		}
	}

	function send() {
		if (HTMLFormValidate::validMail($this->to)) {
			mail($this->to, $this->getSubject(), $this->getBodyText(), implode("\n", $this->headers)."\n", implode(' ', $this->params));
		} else {
			throw new Exception('Invalid email address');
		}
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
     * @param string $subject
     * @param string $message
     * @param mixed $to
     * @param mixed $cc
     * @param mixed $bcc
     * @param array $attachments
     * @param array $additionalSenders This will be added to
     * @throws Exception
     * @return int Number of recipients who were accepted for delivery.
     */
    public static function sendSwiftMailerEmail($subject, $message, $to, $cc = null, $bcc = null, $attachments = array(), $additionalSenders = array())
    {
        if (!class_exists('Swift_Mailer')) {
            throw new Exception('SwiftMailer not installed!');
        }

        /** @var Swift_Message $message */
        $message = Swift_Message::newInstance()
            ->setSubject($subject)
            ->setBody($message)
        ;

        $message->setFrom(Index::getInstance()->mailFromSwiftMailer);
        if (!empty($additionalSenders)) {
            foreach ($additionalSenders as $address) {
                empty($address) ?: $message->addFrom($address);
            }
        }

        if (!empty($to)) {
            foreach ($to as $address) {
                empty($address) ?: $message->addTo($address);
            }
        }

        if (!empty($cc)) {
            foreach ($cc as $address) {
                empty($address) ?: $message->addCc($address);
            }
        }

        if (!empty($bcc)) {
            foreach ($bcc as $address) {
                empty($address) ?: $message->addBcc($address);
            }
        }

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                empty($attachment) ?: $message->attach(Swift_Attachment::fromPath($attachment));
            }
        }

        $transport = Swift_SendmailTransport::newInstance();
        $mailer = Swift_Mailer::newInstance($transport);
        return $mailer->send($message);
    }

}
