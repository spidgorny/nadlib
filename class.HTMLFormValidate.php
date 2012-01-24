<?php

class HTMLFormValidate {
	protected $desc;

	function __construct(array &$desc) {
		$this->desc = &$desc;
	}

	function validate() {
		foreach ($this->desc as $field => &$d) {
			if (!$d['optional'] && !$d['value']) {
				$d['error'] = 'This field is obligatory.';
				$error = TRUE;
			} elseif ($field == 'email' && $d['value'] && !preg_match("/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i", $d['value'])) {
				$d['error'] = 'Not a valid e-mail.';
				$error = TRUE;
			} elseif ($field == 'password' && strlen($d['value']) < 6) {
				$d['error'] = 'Password is too short. Min 6 characters, please. It\'s for your own safety.';
				$error = TRUE;
			} elseif ($d['max'] && $d['value'] > $d['max']) {
				$d['error'] = 'Value too large. Maximum: '.$d['max'];
				$error = TRUE;
			} elseif ($d['value'] && $d['validate'] == 'in_array' && !in_array($d['value'], $d['validateArray'])) {
				$d['error'] = $d['validateError'];
				$error = TRUE;
			} elseif ($d['value'] && $d['validate'] == 'id_in_array' && !in_array($d['idValue'], $d['validateArray'])) { // something typed
				$d['error'] = $d['validateError'];
				$error = TRUE;
			}
		}
		return !$error;
	}

	function getDesc() {
		return $this->desc;
	}

}
