<?php

/**
 * Class HTMLFormRecaptcha
 * http://stackoverflow.com/questions/16890975/using-recaptcha-via-proxy-in-php
 */
class HTMLFormRecaptcha {

	var $publickey;
	protected $privatekey;

	function __construct() {
		$this->publickey = Config::getInstance()->recaptcha['publickey'];
		$this->privatekey = Config::getInstance()->recaptcha['privatekey'];
		if (!$this->publickey || !$this->privatekey) {
			throw new Exception(__METHOD__);
		}
	}

	function getForm(array $desc) {
		require_once 'lib/recaptcha-php-1.10/recaptchalib.php';
		$content = recaptcha_get_html($this->publickey, $desc['error']);
		return $content;
	}

	function getFormAjax(array $desc) {
		$content = '<script type="text/javascript" src="http://api.recaptcha.net/js/recaptcha_ajax.js?error='.htmlspecialchars($desc['captcha-error']).'"></script>
		<div id="recaptcha_div"></div>
 		<script>
 			Recaptcha.create("'.$this->publickey.'", "recaptcha_div");
 		</script>
 		<input type="hidden" name="'.$desc['name'].'">
 		<!--input type="hidden" name="recaptcha_challenge_field"-->
 		<!--input type="hidden" name="recaptcha_response_field"-->';
		return $content;
	}

	function validate($field, array $d) {
		if ($_REQUEST["recaptcha_challenge_field"] && $_REQUEST["recaptcha_response_field"] ) {
			define("RECAPTCHA_VERIFY_SERVER", gethostbyname("www.google.com"));
			require_once('lib/recaptcha-php-1.10/recaptchalib.php');
			$resp = recaptcha_check_answer(
				$this->privatekey,
				$_SERVER["REMOTE_ADDR"],
				$_REQUEST["recaptcha_challenge_field"],
				$_REQUEST["recaptcha_response_field"]);
			//debug($resp);
			if (!$resp->is_valid) {
				return __('Recaptcha: ' . $resp->error);
			}
		} else {
			return __('Field "%1" is obligatory.', $d['label'] ?: $field);
		}
	}

}
