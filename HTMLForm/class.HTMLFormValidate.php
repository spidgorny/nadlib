<?php

class HTMLFormValidate {
	protected $desc;

	function __construct(array &$desc) {
		$this->desc = &$desc;
	}

	function validate() {
		$error = false;
		foreach ($this->desc as $field => &$d) {
			if ($d instanceof HTMLFormTable) {
				$v2 = new HTMLFormValidate($d->desc);
				$validateResult = $v2->validate();
				$error = $error || !$validateResult;
				//$d->desc = $v2->getDesc();
				//debug('updateDesc', $d->getFieldset());
			} else {
				if ($d['mustBint']) {
					$d['value'] = intval($d['value']);
				}
				$type = $d['type'];
				$value = $d['value'];
				$isCheckbox = in_array($type, array(
					'check',
					'checkbox',
					'captcha',
					'recaptcha',
					'recaptchaAjax',
				));
				$d = $this->validateField($field, $d, $type, $value, $isCheckbox);
				$error = $error || $d['error'];
			}
		}
		return !$error;
	}

	function validateField($field, $d, $type, $value, $isCheckbox) {
		if (!$d['optional'] && (
			!($value) || (!$d['allow0'] && !isset($value)))
			&& !$isCheckbox) {
			$d['error'] = 'This field is obligatory.';
			//t3lib_div::debug(array($field, $type, $value, $isCheckbox));
			$error = TRUE;
		} elseif ($type instanceof Collection) {
			// all OK, avoid calling __toString on the collection
		} elseif ($d['mustBset'] && !isset($value)) {	// must be before 'obligatory'
			$e['error'] = 'This field must be set';
			$error = true;
		} elseif ($d['obligatory'] && !$value) {
			$d['error'] = 'This field is obligatory.';
			$error = TRUE;
		} elseif ($field == 'email' && $value && !$this->validMail($value)) {
			$d['error'] = 'Not a valid e-mail.';
			$error = TRUE;
		} elseif ($field == 'password' && strlen($value) < 6) {
			$d['error'] = 'Password is too short. Min 6 characters, please. It\'s for your own safety.';
			$error = TRUE;
		} elseif ($d['min'] && $value < $d['min']) {
			$d['error'] = 'Minimum: '.$d['min'];
			$error = TRUE;
		} elseif ($d['max'] && $value > $d['max']) {
			$d['error'] = 'Value too large. Maximum: '.$d['max'];
			$error = TRUE;
		} elseif ($type == 'recaptcha' || $type == 'recaptchaAjax') {
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
		} elseif ($value && $d['validate'] == 'in_array' && !in_array($value, $d['validateArray'])) {
			$d['error'] = $d['validateError'];
			$error = TRUE;
		} elseif ($value && $d['validate'] == 'id_in_array' && !in_array($d['idValue'], $d['validateArray'])) { // something typed
			$d['error'] = $d['validateError'];
			$error = TRUE;
		} elseif ($d['validate'] == 'int' && strval(intval($value)) != $value) {
			$d['error'] = 'Must be integer';
			$error = TRUE;
		} elseif ($d['validate'] == 'date' && strtotime($value) === false) {
			$d['error'] = 'Must be date';
			$error = TRUE;
		} else {
			//debug($field, $value, strval(intval($value)), $value == strval(intval($value)));
			if ($field == 'date') {
				//debug(strtotime($value));
			}
		}

		if ($d['dependant'] && $isCheckbox && $value) { // only checked should be validated
			//t3lib_div::debug(array($field, $value, $isCheckbox));
			$fv = new HTMLFormValidate($d['dependant']);
			if (!$fv->validate()) {
				$error = true;
				$d['dependant'] = $fv->getDesc();
				$d['error'] = 'Error';
			}
		}
		return $d;
	}


	function getDesc() {
		return $this->desc;
	}

	function validMail($email) {
		return preg_match("/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i", $email);
	}

}
