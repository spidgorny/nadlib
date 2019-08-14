<?php

class HTMLFormValidate
{

	/**
	 * Reference to the form object which contains the $desc as well as other vars
	 * @var HTMLFormTable
	 */
	protected $form;

	/**
	 * Reference to the $desc in the form
	 * @var array
	 */
	protected $desc;

	function __construct(HTMLFormTable $form)
	{
		$this->form = $form;
		$this->desc = &$this->form->desc;
	}

	function validate()
	{
		$error = false;
		foreach ($this->desc as $field => &$d) {
			if ($d instanceof HTMLFormTable) {
				$v2 = new HTMLFormValidate($d);
				$validateResult = $v2->validate();
				$error = $error || !$validateResult;
				//$d->desc = $v2->getDesc();
				//debug('updateDesc', $d->getFieldset());
			} elseif (is_array($d)) {
				if ($d['mustBint']) {
					$d['value'] = intval($d['value']);
				}
				$type = $d['type'];
				$isCheckbox = in_array($type, array(
					'check',
					'checkbox',
					'captcha',
					'recaptcha',
					'recaptchaAjax',
					'select',
				));
				$d = $this->validateField($field, $d, $type, $isCheckbox);
				$error = $error || $d['error'];
			} else {
				d($this->desc);
				throw new InvalidArgumentException(__METHOD__);
			}
		}
		return !$error;
	}

	function validateField($field, array $d, $type, $isCheckbox)
	{
		$value = $d['value'];
		$isHidden = $type == 'hidden';
		if (!$d['optional'] && (
				!($value) || (!$d['allow0'] && !isset($d['value'])))
			&& !$isCheckbox && !$isHidden) {
			$d['error'] = __('Field "%1" is obligatory.', $d['label'] ?: $field);
			//debug(array($field, $type, $value, $isCheckbox));
		} elseif ($type instanceof Collection) {
			// all OK, avoid calling __toString on the collection
		} elseif ($d['mustBset'] && !isset($d['value'])) {    // must be before 'obligatory'
			$d['error'] = __('Field "%1" must be set', $d['label'] ?: $field);
		} elseif ($d['obligatory'] && !$value) {
			$d['error'] = __('Field "%1" is obligatory', $d['label'] ?: $field);
		} elseif ($type == 'email' || $field == 'email' && $value && !$this->validMail($value)) {
			$d['error'] = __('Not a valid e-mail in field "%1"', $d['label'] ?: $field);
		} elseif ($field == 'password' && strlen($value) < 6) {
			$d['error'] = __('Password is too short. Min 6 characters, please. It\'s for your own safety');
		} elseif ($field == 'securePassword' && !$this->securePassword($value)) {
			$d['error'] = 'Password must contain at least 8 Characters. One number and one upper case letter. It\'s for your own safety';
		} elseif ($d['min'] && ($value < $d['min'])) {
			//debug(__METHOD__, $value, $d['min']);
			$d['error'] = __('Value in field "%1" is too small. Minimum: %2', $d['label'] ?: $field, $d['min']);
		} elseif ($d['max'] && ($value > $d['max'])) {
			$d['error'] = __('Value in field "%1" is too large. Maximum: %2', $d['label'] ?: $field, $d['max']);
		} elseif ($d['minlen'] && strlen($value) < $d['minlen']) {
			$d['error'] = __('Value in field "%" is too short. Minimum: %2. Actual: %3', $d['label'] ?: $field, $d['minlen'], strlen($value));
		} elseif ($d['maxlen'] && strlen($value) > $d['maxlen']) {
			$d['error'] = __('Value in field "%1" is too long. Maximum: %2. Actual: %3', $d['label'] ?: $field, $d['maxlen'], strlen($value));
		} elseif ($type == 'recaptcha' || $type == 'recaptchaAjax') {
			//debug($_REQUEST);
			if ($_REQUEST["recaptcha_challenge_field"] && $_REQUEST["recaptcha_response_field"]) {
				require_once('lib/recaptcha-php-1.10/recaptchalib.php');
				$f = new HTMLForm();
				$resp = recaptcha_check_answer(
					$f->privatekey,
					$_SERVER["REMOTE_ADDR"],
					$_REQUEST["recaptcha_challenge_field"],
					$_REQUEST["recaptcha_response_field"]);
				//debug($resp);
				if (!$resp->is_valid) {
					$d['error'] = __($resp->error);
				}
			} else {
				$d['error'] = __('Field "%1" is obligatory.', $d['label'] ?: $field);
			}
		} elseif ($value && $d['validate'] == 'in_array' && !in_array($value, $d['validateArray'])) {
			$d['error'] = $d['validateError'];
		} elseif ($value && $d['validate'] == 'id_in_array' && !in_array($d['idValue'], $d['validateArray'])) { // something typed
			$d['error'] = $d['validateError'];
		} elseif ($d['validate'] == 'int' && strval(intval($value)) != $value) {
			$d['error'] = __('Value "%1" must be integer', $d['label'] ?: $field);
		} elseif ($d['validate'] == 'date' && strtotime($value) === false) {
			$d['error'] = __('Value "%1" must be date', $d['label'] ?: $field);
		} elseif ($d['validate'] == 'multiEmail' && !self::validateEmailAddresses($value, $inValid)) {
			$d['error'] = __('Value "%1" contains following invalid email addresses: "%2"', $d['label'] ?: $field, implode(', ', $inValid));
		} else {
			unset($d['error']);
			//debug($field, $value, strval(intval($value)), $value == strval(intval($value)));
			if ($field == 'xsrf') {
				//debug($value, $_SESSION['HTMLFormTable']['xsrf'][$this->form->class]);
				if ($value != $_SESSION['HTMLFormTable']['xsrf'][$this->form->class]) {
					$d['error'] = __('XSRF token validation failed.');
				}
			}
		}

		if ($d['dependant'] && $isCheckbox && $value) { // only checked should be validated
			//t3lib_div::debug(array($field, $value, $isCheckbox));
			$f2 = new HTMLFormTable($d['dependant']);
			$fv = new HTMLFormValidate($f2);
			if (!$fv->validate()) {
				$d['dependant'] = $fv->getDesc();
				$d['error'] = implode("<br />\n", $fv->getErrorList());
			}
		}
		return $d;
	}

	function securePassword($value)
	{
		/*
		* REGEX used for password strength check
		*  (?=.*\\d.*)      : at least one Digit
		*  (?=.*[a-zA-Z].*) : any Letters
		*  (?=.*[A-Z])      : at least one Uppercase
		*  {8,}             : 8 Length
		*/
		$passwordRegex = '/(?=.*\\d.*)(?=.*[a-zA-Z].*)(?=.*[A-Z]).{8,}/';
		return (preg_match($passwordRegex, $value));
	}

	function getDesc()
	{
		return $this->desc;
	}

	static function validMail($email)
	{
		return preg_match("/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i", $email);
	}

	function getErrorList()
	{
		$list = array();
		foreach ($this->desc as $key => $desc) {
			if ($desc['error']) {
				$list[$key] = $desc['error'];
			}
		}
		return $list;
	}

	/**
	 * If Swift_Mail is installed, Swift_Validate will be used
	 *
	 * @param $value should contain multiple email addresses (comma separated)
	 * @param array $invalid contains invalid entries (pass by reference)
	 * @return bool
	 */
	public static function validateEmailAddresses($value, &$invalid = array())
	{
		$value = trim($value);
		if (empty($value)) {
			return true;
		}

		$emailAddresses = preg_split('/\s*,\s*/', $value);
		foreach ($emailAddresses as &$emailAddress) {
			if ((class_exists('Swift_Validate') && !Swift_Validate::email($emailAddress)) ||
				!self::validMail($value)) {
				$invalid[] = $emailAddress;
			}
		}
		return empty($invalid);
	}
}
