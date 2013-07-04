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
			$mailFromOnly =	(strpos($this->bodytext, '<') !== FALSE)
				? substr(next(explode('<', $mailFrom)), 0, -1)
				: $mailFrom;
			$this->params['-f'] = '-f'.$mailFromOnly;
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

}
