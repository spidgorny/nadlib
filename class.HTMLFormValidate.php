<?php

class HTMLFormValidate {
	protected $desc;

	function __construct(array &$desc) {
		$this->desc = &$desc;
	}

	function validate() {
		foreach ($this->desc as $field => &$d) {
			if (!$d['optional'] && !($d['value']) && !in_array($d['type'], array(
				'check',
				'checkbox',
				'captcha',
				'recaptcha',
				'recaptchaAjax',
			))) {
				$d['error'] = 'This field is obligatory.';
				$error = TRUE;
			} elseif ($field == 'email' && $d['value'] && !$this->validMail($d['value'])) {
				$d['error'] = 'Not a valid e-mail.';
				$error = TRUE;
			} elseif ($field == 'password' && strlen($d['value']) < 6) {
				$d['error'] = 'Password is too short. Min 6 characters, please. It\'s for your own safety.';
				$error = TRUE;
			} elseif ($d['min'] && $d['value'] < $d['min']) {
				$d['error'] = 'Minimum: '.$d['min'];
				$error = TRUE;
			} elseif ($d['max'] && $d['value'] > $d['max']) {
				$d['error'] = 'Maximum: '.$d['max'];
				$error = TRUE;
			} elseif ($d['type'] == 'recaptcha' || $d['type'] == 'recaptchaAjax') {
				//debug($_REQUEST);
				if ($_REQUEST["recaptcha_challenge_field"] && $_REQUEST["recaptcha_response_field"] ) {
					require_once('lib/recaptcha-php-1.10/recaptchalib.php');
					$f = new HTMLForm();
					$resp = recaptcha_check_answer (
						$f->privatekey,
						$_SERVER["REMOTE_ADDR"],
						$_REQUEST["recaptcha_challenge_field"],
						$_REQUEST["recaptcha_response_field"]);
		                //debug($resp);
					if (!$resp->is_valid) {
						$d['error'] = __($resp->error);
						$error = TRUE;
					}
				} else {
					$d['error'] = __('This field is obligatory.');
					$error = TRUE;
				}
			}

			if ($d['dependant']) {
				$fv = new HTMLFormValidate($d['dependant']);
				if (!$fv->validate()) {
					$d['dependant'] = $fv->getDesc();
				}
			}
		}
		return !$error;
	}

	function getDesc() {
		return $this->desc;
	}

	function validMail($email) {
		return preg_match("/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i", $email);
	}

}